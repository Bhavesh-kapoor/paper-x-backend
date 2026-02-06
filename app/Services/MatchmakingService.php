<?php

namespace App\Services;

use App\Models\Inquiry;
use App\Models\InquiryItem;
use App\Models\Dealer;
use App\Models\Converter;
use App\Models\MachineDealer;
use App\Models\MatchmakingLog;
use App\Models\MatchingSession;
use App\Enums\InquiryStatus;
use App\Enums\InquiryType;
use App\Enums\SessionStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MatchmakingService
{
    /** Top N matches: 50 for normal, 100 for urgent (plan defaults; admin-configurable later) */
    private const TOP_N_NORMAL = 50;
    private const TOP_N_URGENT = 100;

    /**
     * Tolerance configuration based on urgency
     * As per the matchmaking brief document
     */
    private const TOLERANCE_CONFIG = [
        'normal' => [
            'thickness_mm' => 0.2, // ±0.2mm
            'gsm_percent' => 10,    // ±10%
            'sheet_dimension_inch' => 2, // ±2" per side
            'reel_width_inch' => 1, // ±1"
            'qty_min_percent' => 60, // seller_qty >= 60% of buyer_qty
            'radius_km' => 50, // default radius
        ],
        'urgent' => [
            'thickness_mm' => 0.3, // ±0.3mm
            'gsm_percent' => 20,    // ±20%
            'sheet_dimension_inch' => 4, // ±4" per side
            'reel_width_inch' => 2, // ±2"
            'qty_min_percent' => 20, // seller_qty >= 20% of buyer_qty
            'radius_km' => 100, // expanded radius for urgent
        ],
    ];

    /**
     * Scoring weights as per the matchmaking brief
     */
    private const SCORING_WEIGHTS = [
        'spec_similarity' => 50, // 50%
        'distance' => 20,        // 20%
        'seller_activity' => 20, // 20%
        'freshness' => 10,       // 10%
    ];

    /**
     * Get allowed viewer roles for this inquiry (visibility matrix).
     * Used to pre-filter which roles can see/be matched to this post.
     *
     * @return string[] e.g. ['dealer', 'converter'] or ['converter', 'machine_dealer']
     */
    public function getVisibilityAllowedRoles(Inquiry $inquiry): array
    {
        $posterType = $inquiry->poster_type;
        $inquiryType = $inquiry->inquiry_type?->value ?? $inquiry->inquiry_type ?? 'material';
        $visibility = $inquiry->visibility ?? 'all';

        // Brand post: only converters
        if ($posterType === 'brand') {
            return ['converter'];
        }

        // Machine post (converter or machine_dealer poster): converters + machine_dealers
        if ($inquiryType === 'machine') {
            if ($visibility === 'converters') {
                return ['converter'];
            }
            if ($visibility === 'machine_dealers' || $visibility === 'machine_brokers') {
                return ['machine_dealer'];
            }
            return ['converter', 'machine_dealer'];
        }

        // Material post (dealer or converter poster): dealers + converters
        if ($visibility === 'dealers') {
            return ['dealer'];
        }
        if ($visibility === 'converters') {
            return ['converter'];
        }
        return ['dealer', 'converter'];
    }

    /**
     * Find and match recipients (dealers, converters, machine dealers) for an inquiry.
     * Uses visibility matrix and registration-based matching; supports multi-role logs.
     *
     * @param Inquiry $inquiry
     * @param int|null $maxRecipients Override top N (default: 50 normal, 100 urgent)
     * @return array Array of ['type' => 'dealer'|'converter'|'machine_dealer', 'id' => int] for notifications
     */
    public function findMatchingDealers(Inquiry $inquiry, ?int $maxRecipients = null): array
    {
        DB::beginTransaction();
        try {
            $inquiryItems = $inquiry->items;
            $isUrgent = $inquiry->urgency === 'urgent';
            $tolerance = self::TOLERANCE_CONFIG[$isUrgent ? 'urgent' : 'normal'];

            if ($maxRecipients === null || $maxRecipients < 1) {
                $maxRecipients = $isUrgent ? self::TOP_N_URGENT : self::TOP_N_NORMAL;
            }
            $maxDealers = $maxRecipients; // used internally for legacy variable names

            if ($inquiryItems->isEmpty()) {
                // Fallback to old structure if no items; return format matches buildMatchedRecipients
                $legacyDealerIds = $this->findMatchingDealersLegacy($inquiry, $maxDealers);
                return array_map(fn ($id) => ['type' => 'dealer', 'id' => (int) $id], $legacyDealerIds);
            }
            
            $matchedResults = [];
            
            // PRIORITY 1: Match with opposite intent posts (buyer <-> seller)
            $inquiryIntent = $inquiry->intent?->value ?? $inquiry->intent;
            
            if ($inquiryIntent === 'buy') {
                // Find matching seller posts
                $matchedSellerPosts = $this->findMatchingSellerPosts($inquiry, $inquiryItems, $tolerance, $maxDealers);
                $matchedResults = array_merge($matchedResults, $matchedSellerPosts);
            } elseif ($inquiryIntent === 'sell') {
                // Find matching buyer posts
                $matchedBuyerPosts = $this->findMatchingBuyerPosts($inquiry, $inquiryItems, $tolerance, $maxDealers);
                $matchedResults = array_merge($matchedResults, $matchedBuyerPosts);
            }
            
            // PRIORITY 2: Fallback to profile-based matching if not enough matches
            if (count($matchedResults) < $maxDealers) {
                $remainingSlots = $maxDealers - count($matchedResults);
                
                // Get all active dealers with their details
                $dealers = Dealer::with(['materials', 'locations', 'materialDetails', 'user'])
                    ->where('status', 'active')
                    ->where('profile_complete', true)
                    ->get();
                
                $profileMatches = [];
                
                foreach ($dealers as $dealer) {
                    // Skip self-matching
                    if ($inquiry->poster_type === 'dealer' && $inquiry->poster_id === $dealer->id) {
                        continue;
                    }
                    
                    // Skip if already matched via post matching
                    $alreadyMatched = false;
                    foreach ($matchedResults as $existingMatch) {
                        if (isset($existingMatch['dealer_id']) && $existingMatch['dealer_id'] === $dealer->id) {
                            $alreadyMatched = true;
                            break;
                        }
                    }
                    if ($alreadyMatched) {
                        continue;
                    }
                    
                    $score = $this->calculateDealerMatchScore($inquiry, $inquiryItems, $dealer, $tolerance);
                    
                    if ($score['total_score'] > 0) {
                        $profileMatches[] = [
                            'dealer_id' => $dealer->id,
                            'score' => $score,
                            'match_source' => 'profile',
                        ];
                    }
                }
                
                // Sort and take top remaining slots
                usort($profileMatches, function ($a, $b) {
                    return $b['score']['total_score'] <=> $a['score']['total_score'];
                });
                
                $matchedResults = array_merge($matchedResults, array_slice($profileMatches, 0, $remainingSlots));
            }
            
            // Sort all matches by total score descending
            usort($matchedResults, function ($a, $b) {
                return $b['score']['total_score'] <=> $a['score']['total_score'];
            });
            
            // Take top N matches
            $matchedResults = array_slice($matchedResults, 0, $maxDealers);
            
            // Create matchmaking logs with match classification
            $rank = 0;
            foreach ($matchedResults as $match) {
                $rank++;
                
                // Determine match type based on score breakdown
                $matchType = $this->classifyMatch($match['score']);
                
                $scoreBreakdown = array_merge($match['score'], [
                    'match_type' => $matchType,
                    'rank' => $rank,
                    'distance_km' => $match['score']['distance_km'] ?? null,
                    'match_source' => $match['match_source'] ?? 'profile',
                    'matched_inquiry_id' => $match['matched_inquiry_id'] ?? null,
                    'matched_poster_type' => $match['matched_poster_type'] ?? null,
                    'fields_matched' => $this->deriveFieldsMatched($match['score']),
                ]);

                $logData = [
                    'inquiry_id' => $inquiry->id,
                    'dealer_id' => $match['dealer_id'] ?? null,
                    'converter_id' => $match['converter_id'] ?? null,
                    'machine_dealer_id' => $match['machine_dealer_id'] ?? null,
                    'session_id' => $inquiry->session?->id,
                    'material_match' => $match['score']['material_match'],
                    'finish_match' => $match['score']['finish_match'],
                    'thickness_match' => $match['score']['thickness_match'],
                    'location_match' => $match['score']['location_match'],
                    'thickness_tolerance_percent' => $match['score']['tolerance_percent'] ?? null,
                    'thickness_tolerance_absolute' => $match['score']['tolerance_absolute'] ?? null,
                    'priority_score' => (int) round($match['score']['total_score'] ?? 0),
                    'score_breakdown' => $scoreBreakdown,
                    'is_visible' => true,
                    'visible_to_dealer_at' => now(),
                ];

                MatchmakingLog::create($logData);
            }

            // Update inquiry visibility
            $inquiry->update([
                'is_visible_to_dealers' => true,
                'matched_dealers_count' => count($matchedResults),
                'matching_started_at' => now(),
            ]);

            DB::commit();

            // Return matched recipients for notifications: ['type' => 'dealer'|'converter'|'machine_dealer', 'id' => int]
            return $this->buildMatchedRecipients($matchedResults);
            
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            Log::error('Database error during matchmaking', [
                'inquiry_id' => $inquiry->id,
                'error' => $e->getMessage(),
                'sql' => $e->getSql() ?? null,
            ]);
            throw new \Exception('Database error during matchmaking: ' . $e->getMessage(), 500, $e);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Matchmaking failed', [
                'inquiry_id' => $inquiry->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new \Exception('Matchmaking failed for inquiry #' . $inquiry->id . ': ' . $e->getMessage(), 500, $e);
        }
    }

    /**
     * Build array of matched recipients for notifications.
     * @param array $matchedResults
     * @return array<int, array{type: string, id: int}>
     */
    private function buildMatchedRecipients(array $matchedResults): array
    {
        $recipients = [];
        foreach ($matchedResults as $match) {
            if (!empty($match['dealer_id'])) {
                $recipients[] = ['type' => 'dealer', 'id' => (int) $match['dealer_id']];
            }
            if (!empty($match['converter_id'])) {
                $recipients[] = ['type' => 'converter', 'id' => (int) $match['converter_id']];
            }
            if (!empty($match['machine_dealer_id'])) {
                $recipients[] = ['type' => 'machine_dealer', 'id' => (int) $match['machine_dealer_id']];
            }
        }
        return $recipients;
    }

    /**
     * Derive "reason" / fields_matched from score for API.
     * @param array $score
     * @return string[]
     */
    private function deriveFieldsMatched(array $score): array
    {
        $fields = [];
        if (!empty($score['material_match'])) {
            $fields[] = 'material';
        }
        if (!empty($score['thickness_match'])) {
            $fields[] = 'thickness';
        }
        if (!empty($score['finish_match'])) {
            $fields[] = 'finish';
        }
        if (!empty($score['location_match'])) {
            $fields[] = 'location';
        }
        return $fields;
    }

    /**
     * Classify match type based on score
     * @param array $score
     * @return string exact_match | slight_variation | nearest
     */
    private function classifyMatch(array $score): string
    {
        // Exact match: material, thickness, and finish all match
        if ($score['material_match'] && $score['thickness_match'] && $score['finish_match']) {
            return 'exact_match';
        }
        
        // Slight variation: material matches but thickness or finish has variation
        if ($score['material_match'] && ($score['thickness_match'] || $score['finish_match'])) {
            return 'slight_variation';
        }
        
        // Nearest: some match but not all criteria
        return 'nearest';
    }
    
    /**
     * Calculate match score for a dealer against an inquiry
     * Implements weighted scoring as per matchmaking brief
     */
    private function calculateDealerMatchScore(Inquiry $inquiry, $inquiryItems, Dealer $dealer, array $tolerance): array
    {
        $score = [
            'material_match' => false,
            'finish_match' => false,
            'thickness_match' => false,
            'location_match' => false,
            'tolerance_percent' => null,
            'tolerance_absolute' => null,
            'material_score' => 0,
            'finish_score' => 0,
            'thickness_score' => 0,
            'location_score' => 0,
            'activity_score' => 0,
            'freshness_score' => 0,
            'priority_bonus' => 0,
            'total_score' => 0,
            'distance_km' => null,
        ];
        
        // ==========================================
        // 1. MATERIAL MATCHING (spec_similarity component)
        // ==========================================
        $inquiryMaterials = $inquiryItems->pluck('material_id')->filter();
        $dealerMaterials = $dealer->materials->pluck('id');
        
        if ($inquiryMaterials->isNotEmpty() && $dealerMaterials->isNotEmpty()) {
            $materialMatch = $inquiryMaterials->intersect($dealerMaterials)->isNotEmpty();
            if ($materialMatch) {
                $score['material_match'] = true;
                $score['material_score'] = 15; // Part of spec_similarity (50%)
            } else {
                // Same materials present but no ID match: try category match (inquiry material's category vs dealer materials' category)
                $inquiryCategoryNames = \App\Models\Material::whereIn('id', $inquiryMaterials->toArray())
                    ->pluck('category')
                    ->filter();
                if ($inquiryCategoryNames->isNotEmpty()) {
                    $dealerMaterialCategories = $dealer->materials->pluck('category')->filter();
                    if ($dealerMaterialCategories->intersect($inquiryCategoryNames)->isNotEmpty()) {
                        $score['material_match'] = true;
                        $score['material_score'] = 12; // Slightly lower for category match
                    }
                }
            }
        } else {
            // Fallback: category matching when inquiry has material_category (name) or category from material_id
            $inquiryCategories = $inquiryItems->pluck('material_category')->filter();
            if ($inquiryCategories->isNotEmpty()) {
                $dealerMaterialCategories = $dealer->materials->pluck('category')->filter();
                if ($dealerMaterialCategories->intersect($inquiryCategories)->isNotEmpty()) {
                    $score['material_match'] = true;
                    $score['material_score'] = 12;
                }
            }
            // Also try Material->category for inquiry items that have material_id
            if (!$score['material_match'] && $inquiryMaterials->isNotEmpty()) {
                $inquiryCategoryNames = \App\Models\Material::whereIn('id', $inquiryMaterials->toArray())
                    ->pluck('category')
                    ->filter();
                if ($inquiryCategoryNames->isNotEmpty()) {
                    $dealerMaterialCategories = $dealer->materials->pluck('category')->filter();
                    if ($dealerMaterialCategories->intersect($inquiryCategoryNames)->isNotEmpty()) {
                        $score['material_match'] = true;
                        $score['material_score'] = 12;
                    }
                }
            }
        }
        
        // ==========================================
        // 2. FINISH/COATING MATCHING (spec_similarity component)
        // ==========================================
        $inquiryFinishes = $inquiryItems->pluck('finish_coating')->filter();
        if ($inquiryFinishes->isNotEmpty()) {
            // Check dealer's material details for finish matching
            if ($dealer->materialDetails) {
                $dealerFinishIds = $dealer->materialDetails->pluck('finish_ids')->flatten()->filter();
                if ($dealerFinishIds->isNotEmpty()) {
                    $score['finish_match'] = true;
                    $score['finish_score'] = 10;
                }
            } else {
                // Assume match if no specific finish data
                $score['finish_match'] = true;
                $score['finish_score'] = 5;
            }
        } else {
            // No finish requirement, consider it a match
            $score['finish_match'] = true;
            $score['finish_score'] = 5;
        }
        
        // ==========================================
        // 3. THICKNESS/GSM MATCHING (spec_similarity component)
        // ==========================================
        foreach ($inquiryItems as $item) {
            if ($item->thickness_unit === 'gsm' && $item->thickness_gsm) {
                $gsmTolerance = ($item->thickness_gsm * $tolerance['gsm_percent']) / 100;
                $minGsm = $item->thickness_gsm - $gsmTolerance;
                $maxGsm = $item->thickness_gsm + $gsmTolerance;
                
                // Check dealer's thickness ranges
                if ($dealer->materialDetails) {
                    foreach ($dealer->materialDetails as $detail) {
                        $ranges = $detail->thickness_ranges ?? [];
                        foreach ($ranges as $range) {
                            if (isset($range['min']) && isset($range['max'])) {
                                if ($range['min'] <= $maxGsm && $range['max'] >= $minGsm) {
                                    $score['thickness_match'] = true;
                                    $score['thickness_score'] = 15;
                                    $score['tolerance_percent'] = $tolerance['gsm_percent'];
                                    break 2;
                                }
                            }
                        }
                    }
                }
                
                // If no specific range data, consider it a potential match
                if (!$score['thickness_match']) {
                    $score['thickness_match'] = true;
                    $score['thickness_score'] = 10;
                    $score['tolerance_percent'] = $tolerance['gsm_percent'];
                }
                break;
            } elseif ($item->thickness_unit === 'mm' && $item->thickness_mm) {
                $mmTolerance = $tolerance['thickness_mm'];
                $minMm = $item->thickness_mm - $mmTolerance;
                $maxMm = $item->thickness_mm + $mmTolerance;
                
                // Check dealer's thickness ranges
                if ($dealer->materialDetails) {
                    foreach ($dealer->materialDetails as $detail) {
                        $ranges = $detail->thickness_ranges ?? [];
                        foreach ($ranges as $range) {
                            if (isset($range['min_mm']) && isset($range['max_mm'])) {
                                if ($range['min_mm'] <= $maxMm && $range['max_mm'] >= $minMm) {
                                    $score['thickness_match'] = true;
                                    $score['thickness_score'] = 15;
                                    $score['tolerance_absolute'] = $mmTolerance;
                                    break 2;
                                }
                            }
                        }
                    }
                }
                
                // If no specific range data, consider it a potential match
                if (!$score['thickness_match']) {
                    $score['thickness_match'] = true;
                    $score['thickness_score'] = 10;
                    $score['tolerance_absolute'] = $mmTolerance;
                }
                break;
            }
        }
        
        // If no thickness requirement, consider it a match
        if (!$score['thickness_match'] && $inquiryItems->where('thickness_gsm', '!=', null)->isEmpty() 
            && $inquiryItems->where('thickness_mm', '!=', null)->isEmpty()) {
            $score['thickness_match'] = true;
            $score['thickness_score'] = 5;
        }
        
        // ==========================================
        // 4. LOCATION/DISTANCE SCORING (distance component - 20%)
        // ==========================================
        if ($inquiry->latitude && $inquiry->longitude && $dealer->locations->isNotEmpty()) {
            $nearestDistance = null;
            
            foreach ($dealer->locations as $location) {
                if ($location->latitude && $location->longitude) {
                    $distance = $this->calculateDistance(
                        $inquiry->latitude,
                        $inquiry->longitude,
                        $location->latitude,
                        $location->longitude
                    );
                    
                    if ($nearestDistance === null || $distance < $nearestDistance) {
                        $nearestDistance = $distance;
                    }
                }
            }
            
            if ($nearestDistance !== null) {
                $score['distance_km'] = round($nearestDistance, 1);
                
                // Within radius gets full location score
                if ($nearestDistance <= $tolerance['radius_km']) {
                    $score['location_match'] = true;
                    // Score decreases linearly with distance
                    $score['location_score'] = max(0, self::SCORING_WEIGHTS['distance'] * (1 - ($nearestDistance / $tolerance['radius_km'])));
                } elseif ($nearestDistance <= $tolerance['radius_km'] * 2) {
                    // Partial score for nearby (within 2x radius)
                    $score['location_match'] = true;
                    $score['location_score'] = max(0, self::SCORING_WEIGHTS['distance'] * 0.3);
                }
            }
        } else {
            // If no location requirement (visibility = "All India"), give partial score
            $score['location_match'] = true;
            $score['location_score'] = self::SCORING_WEIGHTS['distance'] * 0.5;
        }
        
        // ==========================================
        // 5. SELLER ACTIVITY/RESPONSE SCORE (20%)
        // ==========================================
        // Calculate based on dealer's past activity
        $responseCount = 0;
        if ($dealer->user_id) {
            $responseCount = \App\Models\Response::where('responder_id', $dealer->user_id)
                ->where('responder_type', 'dealer')
                ->where('created_at', '>=', now()->subDays(30))
                ->count();
        }
        
        $activityScore = min(self::SCORING_WEIGHTS['seller_activity'], $responseCount * 2);
        $score['activity_score'] = $activityScore;
        
        // ==========================================
        // 6. FRESHNESS/PRIORITY BONUS (10%)
        // ==========================================
        // Check if dealer is authorized agent (priority bonus)
        if ($dealer->materialDetails) {
            $isAuthorized = $dealer->materialDetails->where('agent_type', 'AUTHORIZED_AGENT')->isNotEmpty();
            if ($isAuthorized) {
                $score['priority_bonus'] = 5;
            }
        }
        
        // Account creation freshness (newer active dealers get slight bonus)
        $accountAge = $dealer->created_at->diffInDays(now());
        if ($accountAge <= 30) {
            $score['freshness_score'] = self::SCORING_WEIGHTS['freshness'] * 0.5;
        } elseif ($accountAge <= 90) {
            $score['freshness_score'] = self::SCORING_WEIGHTS['freshness'] * 0.3;
        }
        
        // ==========================================
        // TOTAL SCORE CALCULATION
        // ==========================================
        $score['total_score'] = round(
            $score['material_score'] +
            $score['finish_score'] +
            $score['thickness_score'] +
            $score['location_score'] +
            $score['activity_score'] +
            $score['freshness_score'] +
            $score['priority_bonus'],
            1
        );
        
        return $score;
    }
    
    /**
     * Find matching seller posts for a buyer inquiry
     * Matches buyer posts (intent='buy') with seller posts (intent='sell')
     */
    private function findMatchingSellerPosts(Inquiry $buyerInquiry, $buyerItems, array $tolerance, int $maxMatches): array
    {
        $matchedPosts = [];
        
        // Find all active seller posts (intent='sell') that are not locked/expired
        $sellerPosts = Inquiry::with(['items', 'poster'])
            ->where('intent', 'sell')
            ->where('status', InquiryStatus::MATCHING)
            ->where('id', '!=', $buyerInquiry->id) // Don't match with self
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->get();
        
        foreach ($sellerPosts as $sellerPost) {
            // Skip if same poster
            if ($buyerInquiry->poster_type === $sellerPost->poster_type && 
                $buyerInquiry->poster_id === $sellerPost->poster_id) {
                continue;
            }
            
            $sellerItems = $sellerPost->items;
            if ($sellerItems->isEmpty()) {
                continue;
            }
            
            // Calculate match score between buyer and seller posts
            $score = $this->calculatePostToPostMatchScore(
                $buyerInquiry, 
                $buyerItems, 
                $sellerPost, 
                $sellerItems, 
                $tolerance
            );
            
            if ($score['total_score'] > 0) {
                // Determine dealer_id or converter_id based on seller post poster
                $dealerId = null;
                if ($sellerPost->poster_type === 'dealer') {
                    $dealerId = $sellerPost->poster_id;
                } elseif ($sellerPost->poster_type === 'converter') {
                    // For converter posts, we'll store converter info in score_breakdown
                    // and try to find associated dealer if any
                    $converter = \App\Models\Converter::find($sellerPost->poster_id);
                    if ($converter && $converter->user_id) {
                        // Check if converter's user has a dealer profile
                        $dealer = \App\Models\Dealer::where('user_id', $converter->user_id)->first();
                        if ($dealer) {
                            $dealerId = $dealer->id;
                        }
                    }
                }
                
                $matchedPosts[] = [
                    'dealer_id' => $dealerId,
                    'score' => $score,
                    'match_source' => 'seller_post',
                    'matched_inquiry_id' => $sellerPost->id,
                    'matched_poster_type' => $sellerPost->poster_type,
                ];
            }
        }
        
        // Sort by score descending
        usort($matchedPosts, function ($a, $b) {
            return $b['score']['total_score'] <=> $a['score']['total_score'];
        });
        
        return array_slice($matchedPosts, 0, $maxMatches);
    }
    
    /**
     * Find matching buyer posts for a seller inquiry
     * Matches seller posts (intent='sell') with buyer posts (intent='buy')
     */
    private function findMatchingBuyerPosts(Inquiry $sellerInquiry, $sellerItems, array $tolerance, int $maxMatches): array
    {
        $matchedPosts = [];
        
        // Find all active buyer posts (intent='buy') that are not locked/expired
        $buyerPosts = Inquiry::with(['items', 'poster'])
            ->where('intent', 'buy')
            ->where('status', InquiryStatus::MATCHING)
            ->where('id', '!=', $sellerInquiry->id) // Don't match with self
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->get();
        
        foreach ($buyerPosts as $buyerPost) {
            // Skip if same poster
            if ($sellerInquiry->poster_type === $buyerPost->poster_type && 
                $sellerInquiry->poster_id === $buyerPost->poster_id) {
                continue;
            }
            
            $buyerItems = $buyerPost->items;
            if ($buyerItems->isEmpty()) {
                continue;
            }
            
            // Calculate match score between seller and buyer posts
            $score = $this->calculatePostToPostMatchScore(
                $sellerInquiry, 
                $sellerItems, 
                $buyerPost, 
                $buyerItems, 
                $tolerance
            );
            
            if ($score['total_score'] > 0) {
                // Determine dealer_id or converter_id based on buyer post poster
                $dealerId = null;
                if ($buyerPost->poster_type === 'dealer') {
                    $dealerId = $buyerPost->poster_id;
                } elseif ($buyerPost->poster_type === 'converter') {
                    // For converter posts, try to find associated dealer
                    $converter = \App\Models\Converter::find($buyerPost->poster_id);
                    if ($converter && $converter->user_id) {
                        $dealer = \App\Models\Dealer::where('user_id', $converter->user_id)->first();
                        if ($dealer) {
                            $dealerId = $dealer->id;
                        }
                    }
                }
                
                $matchedPosts[] = [
                    'dealer_id' => $dealerId,
                    'score' => $score,
                    'match_source' => 'buyer_post',
                    'matched_inquiry_id' => $buyerPost->id,
                    'matched_poster_type' => $buyerPost->poster_type,
                ];
            }
        }
        
        // Sort by score descending
        usort($matchedPosts, function ($a, $b) {
            return $b['score']['total_score'] <=> $a['score']['total_score'];
        });
        
        return array_slice($matchedPosts, 0, $maxMatches);
    }
    
    /**
     * Calculate match score between two posts (buyer <-> seller)
     * This is the core matching logic for post-to-post matching
     */
    private function calculatePostToPostMatchScore(
        Inquiry $post1, 
        $items1, 
        Inquiry $post2, 
        $items2, 
        array $tolerance
    ): array {
        $score = [
            'material_match' => false,
            'finish_match' => false,
            'thickness_match' => false,
            'location_match' => false,
            'quantity_match' => false,
            'tolerance_percent' => null,
            'tolerance_absolute' => null,
            'material_score' => 0,
            'finish_score' => 0,
            'thickness_score' => 0,
            'location_score' => 0,
            'quantity_score' => 0,
            'freshness_score' => 0,
            'total_score' => 0,
            'distance_km' => null,
        ];
        
        // ==========================================
        // 1. MATERIAL MATCHING
        // ==========================================
        $materials1 = $items1->pluck('material_id')->filter();
        $materials2 = $items2->pluck('material_id')->filter();
        
        if ($materials1->isNotEmpty() && $materials2->isNotEmpty()) {
            $materialMatch = $materials1->intersect($materials2)->isNotEmpty();
            if ($materialMatch) {
                $score['material_match'] = true;
                $score['material_score'] = 25; // Higher weight for post-to-post matching
            }
        } else {
            // Fallback: category matching
            $categories1 = $items1->pluck('material_category')->filter();
            $categories2 = $items2->pluck('material_category')->filter();
            if ($categories1->isNotEmpty() && $categories2->isNotEmpty()) {
                if ($categories1->intersect($categories2)->isNotEmpty()) {
                    $score['material_match'] = true;
                    $score['material_score'] = 20;
                }
            }
        }
        
        // ==========================================
        // 2. THICKNESS/GSM MATCHING
        // ==========================================
        foreach ($items1 as $item1) {
            foreach ($items2 as $item2) {
                if ($item1->thickness_unit === 'gsm' && $item2->thickness_unit === 'gsm' && 
                    $item1->thickness_gsm && $item2->thickness_gsm) {
                    $gsmTolerance = ($item1->thickness_gsm * $tolerance['gsm_percent']) / 100;
                    $minGsm = $item1->thickness_gsm - $gsmTolerance;
                    $maxGsm = $item1->thickness_gsm + $gsmTolerance;
                    
                    if ($item2->thickness_gsm >= $minGsm && $item2->thickness_gsm <= $maxGsm) {
                        $score['thickness_match'] = true;
                        $score['thickness_score'] = 20;
                        $score['tolerance_percent'] = $tolerance['gsm_percent'];
                        break 2;
                    }
                } elseif ($item1->thickness_unit === 'mm' && $item2->thickness_unit === 'mm' && 
                          $item1->thickness_mm && $item2->thickness_mm) {
                    $mmTolerance = $tolerance['thickness_mm'];
                    $minMm = $item1->thickness_mm - $mmTolerance;
                    $maxMm = $item1->thickness_mm + $mmTolerance;
                    
                    if ($item2->thickness_mm >= $minMm && $item2->thickness_mm <= $maxMm) {
                        $score['thickness_match'] = true;
                        $score['thickness_score'] = 20;
                        $score['tolerance_absolute'] = $mmTolerance;
                        break 2;
                    }
                }
            }
        }
        
        // ==========================================
        // 3. QUANTITY MATCHING
        // ==========================================
        $qty1 = $post1->quantity ?? 0;
        $qty2 = $post2->quantity ?? 0;
        
        if ($qty1 > 0 && $qty2 > 0) {
            // Check if seller quantity meets minimum requirement
            $minRequiredQty = ($qty1 * $tolerance['qty_min_percent']) / 100;
            if ($qty2 >= $minRequiredQty) {
                $score['quantity_match'] = true;
                // Score based on how close to required quantity
                $qtyRatio = min(1.0, $qty2 / $qty1);
                $score['quantity_score'] = 15 * $qtyRatio;
            }
        }
        
        // ==========================================
        // 4. LOCATION/DISTANCE SCORING
        // ==========================================
        if ($post1->latitude && $post1->longitude && $post2->latitude && $post2->longitude) {
            $distance = $this->calculateDistance(
                $post1->latitude,
                $post1->longitude,
                $post2->latitude,
                $post2->longitude
            );
            
            $score['distance_km'] = round($distance, 1);
            
            if ($distance <= $tolerance['radius_km']) {
                $score['location_match'] = true;
                // Score decreases linearly with distance
                $score['location_score'] = max(0, 20 * (1 - ($distance / $tolerance['radius_km'])));
            } elseif ($distance <= $tolerance['radius_km'] * 2) {
                // Partial score for nearby (within 2x radius)
                $score['location_match'] = true;
                $score['location_score'] = max(0, 20 * 0.3);
            }
        } else {
            // If no location requirement (visibility = "All India"), give partial score
            $score['location_match'] = true;
            $score['location_score'] = 20 * 0.5;
        }
        
        // ==========================================
        // 5. FRESHNESS SCORE (newer posts get bonus)
        // ==========================================
        $post2Age = $post2->created_at->diffInDays(now());
        if ($post2Age <= 1) {
            $score['freshness_score'] = 10; // Very fresh
        } elseif ($post2Age <= 3) {
            $score['freshness_score'] = 7; // Fresh
        } elseif ($post2Age <= 7) {
            $score['freshness_score'] = 5; // Recent
        }
        
        // ==========================================
        // TOTAL SCORE CALCULATION
        // ==========================================
        $score['total_score'] = round(
            $score['material_score'] +
            $score['thickness_score'] +
            $score['quantity_score'] +
            $score['location_score'] +
            $score['freshness_score'],
            1
        );
        
        return $score;
    }
    
    /**
     * Legacy matching for inquiries without items
     */
    private function findMatchingDealersLegacy(Inquiry $inquiry, int $maxDealers): array
    {
        // Simplified matching based on inquiry materials and location
        $dealers = Dealer::with(['materials', 'locations'])
            ->where('status', 'active')
            ->where('profile_complete', true)
            ->get();
        
        $matchedDealers = [];
        $tolerance = self::TOLERANCE_CONFIG[$inquiry->urgency === 'urgent' ? 'urgent' : 'normal'];
        
        foreach ($dealers as $dealer) {
            // Skip self-matching
            if ($inquiry->poster_type === 'dealer' && $inquiry->poster_id === $dealer->id) {
                continue;
            }
            
            $score = 0;
            $distanceKm = null;
            $materialMatch = false;
            $locationMatch = false;
            
            // Material match
            $inquiryMaterials = $inquiry->materials->pluck('id');
            $dealerMaterials = $dealer->materials->pluck('id');
            if ($inquiryMaterials->intersect($dealerMaterials)->isNotEmpty()) {
                $score += 30;
                $materialMatch = true;
            }
            
            // Location match
            if ($inquiry->latitude && $inquiry->longitude) {
                foreach ($dealer->locations as $location) {
                    if ($location->latitude && $location->longitude) {
                        $distance = $this->calculateDistance(
                            $inquiry->latitude,
                            $inquiry->longitude,
                            $location->latitude,
                            $location->longitude
                        );
                        if ($distanceKm === null || $distance < $distanceKm) {
                            $distanceKm = $distance;
                        }
                        if ($distance <= $tolerance['radius_km']) {
                            $score += max(0, 30 - ($distance / 10));
                            $locationMatch = true;
                            break;
                        }
                    }
                }
            }
            
            if ($score > 0) {
                $matchType = 'nearest';
                if ($materialMatch && $locationMatch) {
                    $matchType = 'exact_match';
                } elseif ($materialMatch || $locationMatch) {
                    $matchType = 'slight_variation';
                }
                
                $matchedDealers[] = [
                    'dealer_id' => $dealer->id,
                    'score' => [
                        'total_score' => $score,
                        'material_match' => $materialMatch,
                        'location_match' => $locationMatch,
                        'thickness_match' => false,
                        'finish_match' => false,
                        'distance_km' => $distanceKm,
                        'match_type' => $matchType,
                    ],
                ];
            }
        }
        
        usort($matchedDealers, function ($a, $b) {
            return $b['score']['total_score'] <=> $a['score']['total_score'];
        });
        
        // Create matchmaking logs for legacy matches (multi-role schema: converter_id/machine_dealer_id null)
        $rank = 0;
        foreach (array_slice($matchedDealers, 0, $maxDealers) as $match) {
            $rank++;
            MatchmakingLog::create([
                'inquiry_id' => $inquiry->id,
                'dealer_id' => $match['dealer_id'],
                'converter_id' => null,
                'machine_dealer_id' => null,
                'session_id' => $inquiry->session?->id,
                'material_match' => $match['score']['material_match'],
                'finish_match' => $match['score']['finish_match'] ?? false,
                'thickness_match' => $match['score']['thickness_match'] ?? false,
                'location_match' => $match['score']['location_match'],
                'priority_score' => (int) ($match['score']['total_score'] ?? 0),
                'score_breakdown' => array_merge($match['score'], ['rank' => $rank, 'fields_matched' => []]),
                'is_visible' => true,
                'visible_to_dealer_at' => now(),
            ]);
        }

        return array_column(array_slice($matchedDealers, 0, $maxDealers), 'dealer_id');
    }
    
    /**
     * Calculate distance between two coordinates (Haversine formula)
     */
    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // km
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earthRadius * $c;
    }
    
    /**
     * Hide inquiry from non-selected dealers after lock
     */
    public function hideFromNonSelectedDealers(Inquiry $inquiry, array $selectedDealerIds): void
    {
        MatchmakingLog::where('inquiry_id', $inquiry->id)
            ->whereNotIn('dealer_id', $selectedDealerIds)
            ->update([
                'is_visible' => false,
                'hidden_from_dealer_at' => now(),
            ]);
    }
    
    /**
     * Notify matched recipients (dealers, converters, machine dealers) about new inquiry.
     * Accepts array of ['type' => 'dealer'|'converter'|'machine_dealer', 'id' => int], or legacy int[] (dealer IDs).
     * Tiered: top 25 immediate, next 25 delayed, rest in-app only.
     */
    public function notifyMatchedDealers(Inquiry $inquiry, array $matchedRecipients): void
    {
        $userIds = $this->resolveUserIdsFromMatchedRecipients($matchedRecipients);
        $userIds = array_values(array_unique(array_filter($userIds)));

        $tier1 = array_slice($userIds, 0, 25);
        $tier2 = array_slice($userIds, 25, 25);
        $tier3 = array_slice($userIds, 50, null);

        foreach ($tier1 as $userId) {
            $this->createNotificationForUser((int) $userId, $inquiry, 'immediate');
        }
        foreach ($tier2 as $userId) {
            $this->createNotificationForUser((int) $userId, $inquiry, 'delayed');
        }
        foreach ($tier3 as $userId) {
            $this->createNotificationForUser((int) $userId, $inquiry, 'in_app');
        }
    }

    /**
     * Resolve user_id from matched recipients (array of type+id or legacy dealer IDs).
     * @return int[]
     */
    private function resolveUserIdsFromMatchedRecipients(array $matchedRecipients): array
    {
        $userIds = [];
        foreach ($matchedRecipients as $item) {
            if (is_array($item) && isset($item['type'], $item['id'])) {
                $userId = $this->resolveUserIdByTypeAndId($item['type'], (int) $item['id']);
                if ($userId) {
                    $userIds[] = $userId;
                }
            } elseif (is_numeric($item)) {
                $dealer = Dealer::find((int) $item);
                if ($dealer && $dealer->user_id) {
                    $userIds[] = $dealer->user_id;
                }
            }
        }
        return $userIds;
    }

    private function resolveUserIdByTypeAndId(string $type, int $id): ?int
    {
        if ($type === 'dealer') {
            $dealer = Dealer::find($id);
            return $dealer?->user_id;
        }
        if ($type === 'converter') {
            $converter = Converter::find($id);
            return $converter?->user_id;
        }
        if ($type === 'machine_dealer') {
            $machineDealer = MachineDealer::find($id);
            return $machineDealer?->user_id;
        }
        return null;
    }

    /**
     * Create in-app notification for a user (by user_id).
     */
    private function createNotificationForUser(int $userId, Inquiry $inquiry, string $tier): void
    {
        try {
            if ($tier === 'delayed') {
                Log::info('Delayed notification scheduled', [
                    'user_id' => $userId,
                    'inquiry_id' => $inquiry->id,
                    'delay_minutes' => rand(10, 30),
                ]);
                return;
            }

            \App\Models\Notification::create([
                'user_id' => $userId,
                'type' => \App\Enums\NotificationType::NEW_OPPORTUNITY,
                'title' => 'New Matching Opportunity',
                'message' => "A new requirement matches your profile: {$inquiry->title}",
                'notifiable_type' => Inquiry::class,
                'notifiable_id' => $inquiry->id,
                'read' => false,
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to create notification', [
                'user_id' => $userId,
                'inquiry_id' => $inquiry->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    /**
     * Get matches for an inquiry (used by getMatchmakingResponses API)
     * Returns both matched dealers from logs AND actual responses
     */
    public function getMatchesForInquiry(Inquiry $inquiry, string $filter = 'all'): array
    {
        // Get matchmaking logs for this inquiry (dealer, converter, or machine_dealer)
        // Load response only; dealer/converter/machineDealer are lazy-loaded in buildResponderBlockFromLog to avoid "undefined relationship" when some logs have only converter_id or machine_dealer_id
        $logsQuery = MatchmakingLog::where('inquiry_id', $inquiry->id)
            ->where('is_visible', true)
            ->with(['response']);

        if ($filter === 'responded') {
            // Only dealers who have expressed interest (responded_at or submitted a response)
            $logsQuery->where(function ($q) {
                $q->whereNotNull('responded_at')->orWhereNotNull('response_id');
            });
        } elseif ($filter !== 'all') {
            $logsQuery->whereRaw("JSON_EXTRACT(score_breakdown, '$.match_type') = ?", [$filter]);
        }

        $logs = $logsQuery->orderBy('priority_score', 'desc')->get();

        $matches = [];
        foreach ($logs as $log) {
            $responderBlock = $this->buildResponderBlockFromLog($log, $inquiry->status === InquiryStatus::LOCKED);
            if ($responderBlock === null) {
                continue;
            }

            $scoreBreakdown = $log->score_breakdown ?? [];
            $matches[] = [
                'id' => $log->id,
                'match_type' => $scoreBreakdown['match_type'] ?? $this->determineMatchType($log),
                'distance_km' => $scoreBreakdown['distance_km'] ?? null,
                'dealer' => $responderBlock, // API keeps 'dealer' key for backward compat; may be converter/machine_dealer
                'responder_type' => $responderBlock['responder_type'] ?? 'dealer',
                'quantity_offered' => $log->response?->quantity_offered ?? 0,
                'quoted_price' => $log->response?->quoted_price ?? null,
                'price_status' => $log->response?->price_status ?? null,
                'additional_details' => $log->response?->additional_details ?? null,
                'responded_at' => $log->response?->created_at?->toIso8601String() ?? $log->responded_at?->toIso8601String() ?? $log->visible_to_dealer_at?->toIso8601String(),
                'is_shortlisted' => $log->is_selected ?? false,
                'has_responded' => $log->response_id !== null || $log->responded_at !== null,
                'match_score' => $log->priority_score,
                'reason' => $scoreBreakdown['fields_matched'] ?? [],
            ];
        }

        return $matches;
    }

    /**
     * Build responder block for getMatchesForInquiry from log (dealer, converter, or machine_dealer).
     * @return array{id: int|null, company_name: string|null, location: string, responder_type: string}|null
     */
    private function buildResponderBlockFromLog(MatchmakingLog $log, bool $revealIdentity): ?array
    {
        if ($log->dealer_id && $log->dealer) {
            $d = $log->dealer;
            $user = $d->user;
            $location = $d->locations->first();
            return [
                'id' => $revealIdentity ? $d->id : null,
                'company_name' => $revealIdentity ? ($user?->company_name ?? $user?->name ?? 'Unknown') : null,
                'location' => $location ? ($location->city . ', ' . ($location->state ?? '')) : ($user?->city ?? 'Unknown'),
                'responder_type' => 'dealer',
            ];
        }
        if ($log->converter_id && $log->converter) {
            $c = $log->converter;
            $user = $c->user;
            $loc = trim(($c->factory_city ?? '') . ', ' . ($c->factory_state ?? ''));
            return [
                'id' => $revealIdentity ? $c->id : null,
                'company_name' => $revealIdentity ? ($user?->company_name ?? $user?->name ?? 'Unknown') : null,
                'location' => $loc ?: ($user?->city ?? 'Unknown'),
                'responder_type' => 'converter',
            ];
        }
        if ($log->machine_dealer_id && $log->machineDealer) {
            $m = $log->machineDealer;
            $user = $m->user;
            return [
                'id' => $revealIdentity ? $m->id : null,
                'company_name' => $revealIdentity ? ($m->company_name ?? $user?->name ?? 'Unknown') : null,
                'location' => $m->city ?? $m->location ?? ($user?->city ?? 'Unknown'),
                'responder_type' => 'machine_dealer',
            ];
        }
        return null;
    }
    
    /**
     * Determine match type from log data
     */
    private function determineMatchType(MatchmakingLog $log): string
    {
        if ($log->material_match && $log->thickness_match && $log->finish_match) {
            return 'exact_match';
        }
        if ($log->material_match && ($log->thickness_match || $log->finish_match)) {
            return 'slight_variation';
        }
        return 'nearest';
    }
}


