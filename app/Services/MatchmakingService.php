<?php

namespace App\Services;

use App\Models\Inquiry;
use App\Models\InquiryItem;
use App\Models\Dealer;
use App\Models\MatchmakingLog;
use App\Models\MatchingSession;
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
                ->where('status', 'active')
                ->where('profile_complete', true)
                ->get();
            
            $matchedDealers = [];
            
            foreach ($dealers as $dealer) {
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
            
            // Update inquiry visibility
            $inquiry->update([
                'is_visible_to_dealers' => true,
                'matched_dealers_count' => count($matchedDealers),
                'matching_started_at' => now(),
            ]);
            
            DB::commit();
            
            return array_column($matchedDealers, 'dealer_id');
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Matchmaking failed', [
                'inquiry_id' => $inquiry->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
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
        
        // Location matching
        if ($inquiry->latitude && $inquiry->longitude && $dealer->locations->isNotEmpty()) {
            foreach ($dealer->locations as $location) {
                if ($location->latitude && $location->longitude) {
                    $distance = $this->calculateDistance(
                        $inquiry->latitude,
                        $inquiry->longitude,
                        $location->latitude,
                        $location->longitude
                    );
                    
                    // Prioritize nearby dealers (within 100km)
                    if ($distance <= 100) {
                        $score['location_match'] = true;
                        $score['location_score'] = max(0, 30 - ($distance / 10)); // Closer = higher score
                        break;
                    }
                }
            }
        }
        
        // Priority bonuses
        // 1. Authorized mill agent (if dealer has this flag)
        // 2. Faster response history (would need to query past responses)
        // 3. Higher deal success rate (would need to query past deals)
        
        // Placeholder for priority bonuses
        $score['priority_bonus'] = 10; // Base bonus
        
        // Calculate total score
        $score['total_score'] = 
            $score['material_score'] +
            $score['finish_score'] +
            $score['thickness_score'] +
            $score['location_score'] +
            $score['priority_bonus'];
        
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
        
        foreach ($dealers as $dealer) {
            $score = 0;
            
            // Material match
            $inquiryMaterials = $inquiry->materials->pluck('id');
            $dealerMaterials = $dealer->materials->pluck('id');
            if ($inquiryMaterials->intersect($dealerMaterials)->isNotEmpty()) {
                $score += 30;
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
                        if ($distance <= 100) {
                            $score += max(0, 30 - ($distance / 10));
                            break;
                        }
                    }
                }
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

