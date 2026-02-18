<?php

namespace App\Services;

use App\Models\Inquiry;
use App\Models\InquiryItem;
use App\Models\Dealer;
use App\Models\Converter;
use App\Models\MatchmakingLog;
use App\Models\MatchingSession;
use App\Enums\DealerStatus;
use App\Enums\ConverterStatus;
use App\Enums\InquiryStatus;
use App\Enums\SessionStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MatchmakingService
{
    /**
     * Find and match dealers for an inquiry
     * 
     * @param Inquiry $inquiry
     * @param int $maxDealers Maximum number of dealers to match (default: 10)
     * @return array Array of matched dealer IDs with scores
     */
    public function findMatchingDealers(Inquiry $inquiry, int $maxDealers = 10): array
    {
        DB::beginTransaction();
        try {
            // Get inquiry items for matching
            $inquiryItems = $inquiry->items;
            
            if ($inquiryItems->isEmpty()) {
                // Fallback to old structure if no items
                return $this->findMatchingDealersLegacy($inquiry, $maxDealers);
            }
            
            // Get all active dealers
            $dealers = Dealer::with(['materials', 'locations'])
                ->where('status', DealerStatus::ACTIVE)
                ->where('profile_complete', true)
                ->get();
            
            $matchedDealers = [];
            
            foreach ($dealers as $dealer) {
                // Exclude the poster when they are a dealer (don't match post to its author)
                if ($inquiry->poster_type === 'dealer' && $dealer->id === $inquiry->poster_id) {
                    continue;
                }
                $score = $this->calculateDealerMatchScore($inquiry, $inquiryItems, $dealer);
                
                if ($score['total_score'] > 0) {
                    $matchedDealers[] = [
                        'dealer_id' => $dealer->id,
                        'score' => $score,
                    ];
                }
            }
            
            // Sort by total score descending
            usort($matchedDealers, function ($a, $b) {
                return $b['score']['total_score'] <=> $a['score']['total_score'];
            });
            
            // Take top N dealers
            $matchedDealers = array_slice($matchedDealers, 0, $maxDealers);
            
            // Create matchmaking logs
            foreach ($matchedDealers as $match) {
                MatchmakingLog::create([
                    'inquiry_id' => $inquiry->id,
                    'dealer_id' => $match['dealer_id'],
                    'material_match' => $match['score']['material_match'],
                    'finish_match' => $match['score']['finish_match'],
                    'thickness_match' => $match['score']['thickness_match'],
                    'location_match' => $match['score']['location_match'],
                    'thickness_tolerance_percent' => $match['score']['tolerance_percent'] ?? null,
                    'thickness_tolerance_absolute' => $match['score']['tolerance_absolute'] ?? null,
                    'priority_score' => $match['score']['total_score'],
                    'score_breakdown' => $match['score'],
                    'is_visible' => true,
                    'visible_to_dealer_at' => now(),
                ]);
            }
            
            // Update inquiry visibility and total matched count
            $totalMatchmakingLogs = MatchmakingLog::where('inquiry_id', $inquiry->id)->count();
            $inquiry->update([
                'is_visible_to_dealers' => true,
                'matched_dealers_count' => $totalMatchmakingLogs,
                'matching_started_at' => now(),
            ]);

            // Auto-lock only when 10 people have *expressed interest* (responded_at), not on initial match
            $this->lockSessionIfResponseThresholdReached($inquiry, 10);
            
            DB::commit();
            
            return array_column($matchedDealers, 'dealer_id');
            
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
     * Find and match converters for an inquiry.
     * Uses converter registration/profile data (raw materials, factory location) similar to dealer matching.
     *
     * @param Inquiry $inquiry
     * @param int $maxConverters Maximum number of converters to match (default: 10)
     * @return array Array of matched converter IDs with scores
     */
    public function findMatchingConverters(Inquiry $inquiry, int $maxConverters = 10): array
    {
        DB::beginTransaction();
        try {
            $inquiryItems = $inquiry->items;

            if ($inquiryItems->isEmpty()) {
                // For now, skip legacy path for converters if there are no items
                return [];
            }

            $converters = Converter::with(['rawMaterials'])
                ->where('status', ConverterStatus::ACTIVE)
                ->where('profile_complete', true)
                ->get();

            $matchedConverters = [];

            foreach ($converters as $converter) {
                // Exclude the poster when they are a converter (don't match post to its author)
                if ($inquiry->poster_type === 'converter' && $converter->id === $inquiry->poster_id) {
                    continue;
                }

                $score = $this->calculateConverterMatchScore($inquiry, $inquiryItems, $converter);

                if ($score['total_score'] > 0) {
                    $matchedConverters[] = [
                        'converter_id' => $converter->id,
                        'score' => $score,
                    ];
                }
            }

            usort($matchedConverters, function ($a, $b) {
                return $b['score']['total_score'] <=> $a['score']['total_score'];
            });

            $matchedConverters = array_slice($matchedConverters, 0, $maxConverters);

            foreach ($matchedConverters as $match) {
                MatchmakingLog::create([
                    'inquiry_id' => $inquiry->id,
                    'converter_id' => $match['converter_id'],
                    'material_match' => $match['score']['material_match'],
                    'finish_match' => $match['score']['finish_match'],
                    'thickness_match' => $match['score']['thickness_match'],
                    'location_match' => $match['score']['location_match'],
                    'thickness_tolerance_percent' => $match['score']['tolerance_percent'] ?? null,
                    'thickness_tolerance_absolute' => $match['score']['tolerance_absolute'] ?? null,
                    'priority_score' => $match['score']['total_score'],
                    'score_breakdown' => $match['score'],
                    'is_visible' => true,
                    'visible_to_dealer_at' => now(),
                ]);
            }

            // Ensure matching_started_at is set when converters are matched as well
            if ($matchedConverters) {
                $inquiry->update([
                    'matching_started_at' => $inquiry->matching_started_at ?? now(),
                ]);
            }

            $this->lockSessionIfResponseThresholdReached($inquiry, 10);

            DB::commit();

            return array_column($matchedConverters, 'converter_id');
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            Log::error('Database error during converter matchmaking', [
                'inquiry_id' => $inquiry->id,
                'error' => $e->getMessage(),
                'sql' => $e->getSql() ?? null,
            ]);
            throw new \Exception('Database error during converter matchmaking: ' . $e->getMessage(), 500, $e);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Converter matchmaking failed', [
                'inquiry_id' => $inquiry->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new \Exception('Converter matchmaking failed for inquiry #' . $inquiry->id . ': ' . $e->getMessage(), 500, $e);
        }
    }

    /**
     * Calculate match score for a dealer against an inquiry
     */
    private function calculateDealerMatchScore(Inquiry $inquiry, $inquiryItems, Dealer $dealer): array
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
            'distance_km' => null,
            'priority_bonus' => 0,
            'total_score' => 0,
        ];
        
        // Material matching (category-based)
        $inquiryMaterials = $inquiryItems->pluck('material_id')->filter();
        $dealerMaterials = $dealer->materials->pluck('id');
        
        if ($inquiryMaterials->isNotEmpty() && $dealerMaterials->isNotEmpty()) {
            $materialMatch = $inquiryMaterials->intersect($dealerMaterials)->isNotEmpty();
            if ($materialMatch) {
                $score['material_match'] = true;
                $score['material_score'] = 30; // Base score for material match
            }
        } else {
            // Fallback: category matching
            $inquiryCategories = $inquiryItems->pluck('material_category')->filter();
            if ($inquiryCategories->isNotEmpty()) {
                $dealerMaterialCategories = $dealer->materials->pluck('category')->filter();
                if ($dealerMaterialCategories->intersect($inquiryCategories)->isNotEmpty()) {
                    $score['material_match'] = true;
                    $score['material_score'] = 25; // Slightly lower for category match
                }
            }
        }
        
        // Finish/Coating matching
        $inquiryFinishes = $inquiryItems->pluck('finish_coating')->filter();
        if ($inquiryFinishes->isNotEmpty()) {
            // Check if dealer has matching finishes (assuming dealer has finishes relation)
            // This would need to be implemented based on your schema
            $score['finish_match'] = true; // Placeholder
            $score['finish_score'] = 15;
        }
        
        // Thickness matching with tolerance
        $tolerancePercent = $inquiry->urgency === 'urgent' ? 10.0 : 5.0;
        $toleranceAbsolute = $inquiry->urgency === 'urgent' ? 0.3 : 0.2;
        
        foreach ($inquiryItems as $item) {
            if ($item->thickness_unit === 'gsm' && $item->thickness_gsm) {
                $minGsm = $item->getThicknessGsmMin();
                $maxGsm = $item->getThicknessGsmMax();
                
                // Check if dealer has materials in this range
                // This is simplified - you'd check dealer's material thickness ranges
                $score['thickness_match'] = true; // Placeholder
                $score['thickness_score'] = 25;
                $score['tolerance_percent'] = $tolerancePercent;
                break;
            } elseif ($item->thickness_unit === 'mm' && $item->thickness_mm) {
                $minMm = $item->getThicknessMmMin();
                $maxMm = $item->getThicknessMmMax();
                
                // Check if dealer has materials in this range
                $score['thickness_match'] = true; // Placeholder
                $score['thickness_score'] = 25;
                $score['tolerance_absolute'] = $toleranceAbsolute;
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
        // 4. LOCATION/DISTANCE SCORING
        // When config matchmaking.use_location_in_matching is false, we treat as "all India":
        // everyone gets full location score; distance_km is still computed for display.
        // ==========================================
        $useLocation = config('matchmaking.use_location_in_matching', false);
        $radiusKm = $inquiry->urgency === 'urgent' ? 100 : 50;

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
            }
            if (!$useLocation) {
                $score['location_match'] = true;
                $score['location_score'] = 20;
            } else {
                if ($nearestDistance !== null) {
                    if ($nearestDistance <= $radiusKm) {
                        $score['location_match'] = true;
                        $score['location_score'] = max(0, 20 * (1 - ($nearestDistance / $radiusKm)));
                    } elseif ($nearestDistance <= $radiusKm * 2) {
                        $score['location_match'] = true;
                        $score['location_score'] = max(0, 20 * 0.3);
                    }
                }
            }
        } else {
            $score['location_match'] = true;
            $score['location_score'] = $useLocation ? 10 : 20;
        }
        
        // Priority bonuses
        // 1. Authorized mill agent (if dealer has this flag)
        // 2. Faster response history (would need to query past responses)
        // 3. Higher deal success rate (would need to query past deals)
        
        // Placeholder for priority bonuses
        $score['priority_bonus'] = 10; // Base bonus
        
        // Calculate total score
        $score['total_score'] = round(
            $score['material_score'] +
            $score['finish_score'] +
            $score['thickness_score'] +
            $score['location_score'] +
            $score['priority_bonus'],
            1
        );

        return $score;
    }

    /**
     * Calculate match score for a converter against an inquiry.
     * Mirrors dealer scoring but uses converter raw materials and factory location.
     */
    private function calculateConverterMatchScore(Inquiry $inquiry, $inquiryItems, Converter $converter): array
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
            'distance_km' => null,
            'priority_bonus' => 0,
            'total_score' => 0,
        ];

        // Material matching (category-based)
        $inquiryMaterials = $inquiryItems->pluck('material_id')->filter();
        $converterMaterials = $converter->rawMaterials->pluck('id');

        if ($inquiryMaterials->isNotEmpty() && $converterMaterials->isNotEmpty()) {
            $materialMatch = $inquiryMaterials->intersect($converterMaterials)->isNotEmpty();
            if ($materialMatch) {
                $score['material_match'] = true;
                $score['material_score'] = 30;
            }
        }

        // Finish/Coating matching – placeholder similar to dealers
        $inquiryFinishes = $inquiryItems->pluck('finish_coating')->filter();
        if ($inquiryFinishes->isNotEmpty()) {
            $score['finish_match'] = true;
            $score['finish_score'] = 15;
        }

        // Thickness matching with tolerance (same placeholder logic as dealers)
        $tolerancePercent = $inquiry->urgency === 'urgent' ? 10.0 : 5.0;
        $toleranceAbsolute = $inquiry->urgency === 'urgent' ? 0.3 : 0.2;

        foreach ($inquiryItems as $item) {
            if ($item->thickness_unit === 'gsm' && $item->thickness_gsm) {
                $score['thickness_match'] = true;
                $score['thickness_score'] = 25;
                $score['tolerance_percent'] = $tolerancePercent;
                break;
            } elseif ($item->thickness_unit === 'mm' && $item->thickness_mm) {
                $score['thickness_match'] = true;
                $score['thickness_score'] = 25;
                $score['tolerance_absolute'] = $toleranceAbsolute;
                break;
            }
        }

        if (
            !$score['thickness_match'] &&
            $inquiryItems->where('thickness_gsm', '!=', null)->isEmpty() &&
            $inquiryItems->where('thickness_mm', '!=', null)->isEmpty()
        ) {
            $score['thickness_match'] = true;
            $score['thickness_score'] = 5;
        }

        // Location scoring using converter factory coordinates
        $useLocation = config('matchmaking.use_location_in_matching', false);
        $radiusKm = $inquiry->urgency === 'urgent' ? 100 : 50;

        if ($inquiry->latitude && $inquiry->longitude && $converter->factory_latitude && $converter->factory_longitude) {
            $distance = $this->calculateDistance(
                (float) $inquiry->latitude,
                (float) $inquiry->longitude,
                (float) $converter->factory_latitude,
                (float) $converter->factory_longitude
            );
            $score['distance_km'] = round($distance, 1);

            if (!$useLocation) {
                $score['location_match'] = true;
                $score['location_score'] = 20;
            } else {
                if ($distance <= $radiusKm) {
                    $score['location_match'] = true;
                    $score['location_score'] = max(0, 20 * (1 - ($distance / $radiusKm)));
                } elseif ($distance <= $radiusKm * 2) {
                    $score['location_match'] = true;
                    $score['location_score'] = max(0, 20 * 0.3);
                }
            }
        } else {
            $score['location_match'] = true;
            $score['location_score'] = $useLocation ? 10 : 20;
        }

        // Priority bonuses – placeholder
        $score['priority_bonus'] = 10;

        $score['total_score'] = round(
            $score['material_score'] +
            $score['finish_score'] +
            $score['thickness_score'] +
            $score['location_score'] +
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
        // When config matchmaking.use_location_in_matching is false, treat as all India.
        // ==========================================
        $useLocation = config('matchmaking.use_location_in_matching', false);
        
        if ($post1->latitude && $post1->longitude && $post2->latitude && $post2->longitude) {
            $distance = $this->calculateDistance(
                $post1->latitude,
                $post1->longitude,
                $post2->latitude,
                $post2->longitude
            );
            $score['distance_km'] = round($distance, 1);
        }
        
        if (!$useLocation) {
            $score['location_match'] = true;
            $score['location_score'] = 20;
        } elseif ($post1->latitude && $post1->longitude && $post2->latitude && $post2->longitude) {
            $distance = $score['distance_km'] ?? 0;
            if ($distance <= $tolerance['radius_km']) {
                $score['location_match'] = true;
                $score['location_score'] = max(0, 20 * (1 - ($distance / $tolerance['radius_km'])));
            } elseif ($distance <= $tolerance['radius_km'] * 2) {
                $score['location_match'] = true;
                $score['location_score'] = max(0, 20 * 0.3);
            }
        } else {
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
            ->where('status', DealerStatus::ACTIVE)
            ->where('profile_complete', true)
            ->get();
        
        $matchedDealers = [];
        
        foreach ($dealers as $dealer) {
            if ($inquiry->poster_type === 'dealer' && $dealer->id === $inquiry->poster_id) {
                continue;
            }
            $score = 0;
            
            // Material match
            $inquiryMaterials = $inquiry->materials->pluck('id');
            $dealerMaterials = $dealer->materials->pluck('id');
            if ($inquiryMaterials->intersect($dealerMaterials)->isNotEmpty()) {
                $score += 30;
            }
            
            // Location: when use_location_in_matching is false, treat as all India (add score so no one excluded)
            $useLocation = config('matchmaking.use_location_in_matching', false);
            $radiusKm = 50;
            if ($inquiry->latitude && $inquiry->longitude) {
                foreach ($dealer->locations as $location) {
                    if ($location->latitude && $location->longitude) {
                        $distance = $this->calculateDistance(
                            $inquiry->latitude,
                            $inquiry->longitude,
                            $location->latitude,
                            $location->longitude
                        );
                        if ($useLocation && $distance <= $radiusKm) {
                            $score += max(0, 30 - ($distance / 10));
                            break;
                        }
                    }
                }
            }
            if (!$useLocation) {
                $score += 15; // All India: fixed score so location doesn't exclude anyone
            }
            
            if ($score > 0) {
                $matchedDealers[] = [
                    'dealer_id' => $dealer->id,
                    'score' => ['total_score' => $score],
                ];
            }
        }
        
        usort($matchedDealers, function ($a, $b) {
            return $b['score']['total_score'] <=> $a['score']['total_score'];
        });
        
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
     * Get matchmaking responses for an inquiry (for the poster's "Session Details" / matchmaking responses screen).
     * Returns visible MatchmakingLogs with dealer info, distance, match score, and optional response data.
     *
     * @param Inquiry $inquiry
     * @param string $filter One of: all, responded, exact_match, slight_variation (nearest is applied by controller via sort)
     * @return array List of response items for API
     */
    public function getMatchesForInquiry(Inquiry $inquiry, string $filter = 'all'): array
    {
        $logs = MatchmakingLog::where('inquiry_id', $inquiry->id)
            ->where('is_visible', true)
            ->with(['dealer.user', 'dealer.locations', 'response'])
            ->get();

        $inquiryLat = $inquiry->latitude ? (float) $inquiry->latitude : null;
        $inquiryLon = $inquiry->longitude ? (float) $inquiry->longitude : null;

        $items = [];
        foreach ($logs as $log) {
            $dealer = $log->dealer;
            if (!$dealer) {
                continue;
            }

            $user = $dealer->user;
            $companyName = $user ? ($user->company_name ?? $user->name ?? 'Potential Match') : 'Potential Match';

            $locationStr = 'Unknown';
            $distanceKm = null;
            $firstLocation = $dealer->locations->first();
            if ($firstLocation) {
                $parts = array_filter([$firstLocation->city, $firstLocation->state, $firstLocation->address]);
                $locationStr = implode(', ', $parts) ?: 'Unknown';
                if ($inquiryLat !== null && $inquiryLon !== null && $firstLocation->latitude !== null && $firstLocation->longitude !== null) {
                    $distanceKm = round($this->calculateDistance(
                        $inquiryLat,
                        $inquiryLon,
                        (float) $firstLocation->latitude,
                        (float) $firstLocation->longitude
                    ), 2);
                }
            }

            $scoreBreakdown = is_array($log->score_breakdown) ? $log->score_breakdown : [];
            $materialMatch = $log->material_match ?? $scoreBreakdown['material_match'] ?? false;
            $finishMatch = $log->finish_match ?? $scoreBreakdown['finish_match'] ?? false;
            $thicknessMatch = $log->thickness_match ?? $scoreBreakdown['thickness_match'] ?? false;
            $matchType = ($materialMatch && $finishMatch && $thicknessMatch) ? 'exact_match' : 'slight_variation';
            $matchScore = $log->priority_score ?? 0;

            $response = $log->response;
            $hasResponded = $log->responded_at !== null;
            $quantityOffered = $response ? (float) ($response->quantity_offered ?? 0) : 0;
            $quotedPrice = $response && $response->quoted_price !== null ? (float) $response->quoted_price : null;
            $priceStatus = $response && $response->price_status !== null ? $response->price_status : null;
            $additionalDetails = $response ? ($response->additional_details ?? null) : null;
            $respondedAt = $log->responded_at ? $log->responded_at->toIso8601String() : '';
            $isShortlisted = (bool) ($log->is_selected ?? false);

            switch ($filter) {
                case 'responded':
                    if (!$hasResponded) {
                        continue 2;
                    }
                    break;
                case 'exact_match':
                    if ($matchType !== 'exact_match') {
                        continue 2;
                    }
                    break;
                case 'slight_variation':
                    if ($matchType !== 'slight_variation') {
                        continue 2;
                    }
                    break;
                case 'all':
                case 'nearest':
                default:
                    break;
            }

            $items[] = [
                'id' => $log->id,
                'match_type' => $matchType,
                'distance_km' => $distanceKm,
                'dealer' => [
                    'id' => $dealer->id,
                    'company_name' => $companyName,
                    'location' => $locationStr,
                ],
                'quantity_offered' => $quantityOffered,
                'quoted_price' => $quotedPrice,
                'price_status' => $priceStatus,
                'additional_details' => $additionalDetails,
                'responded_at' => $respondedAt,
                'is_shortlisted' => $isShortlisted,
                'has_responded' => $hasResponded,
                'match_score' => $matchScore,
            ];
        }

        return $items;
    }

    /**
     * Auto-lock session when 10 people have responded (expressed interest – responded_at set).
     * Post moves from Inquiries to Locked sessions on dashboard.
     */
    public function lockSessionIfResponseThresholdReached(Inquiry $inquiry, int $threshold = 10): void
    {
        $count = MatchmakingLog::where('inquiry_id', $inquiry->id)->whereNotNull('responded_at')->count();
        if ($count < $threshold) {
            return;
        }

        $session = MatchingSession::where('inquiry_id', $inquiry->id)->first();
        if (!$session || $session->locked_at !== null) {
            return;
        }

        $session->update([
            'status' => SessionStatus::LOCKED,
            'locked_at' => now(),
        ]);
        $inquiry->update([
            'status' => InquiryStatus::LOCKED,
            'locked_at' => now(),
        ]);
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
     * Notify matched dealers about new inquiry
     */
    public function notifyMatchedDealers(Inquiry $inquiry, array $dealerIds): void
    {
        // This would integrate with your NotificationService
        // Placeholder for notification logic
        foreach ($dealerIds as $dealerId) {
            // Create notification for dealer
            // NotificationService::create(...)
        }
    }
}


