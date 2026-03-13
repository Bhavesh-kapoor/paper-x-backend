<?php

namespace App\Services;

use App\Domain\MatchEngine\MatchEngineOrchestrator;
use App\Enums\InquiryIntent;
use App\Enums\InquiryStatus;
use App\Enums\InquiryType;
use App\Enums\NavigationType;
use App\Enums\NotificationType;
use App\Enums\SessionStatus;
use App\Enums\ConverterStatus;
use App\Models\Converter;
use App\Models\Inquiry;
use App\Models\MatchingSession;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

class JobworkService
{
    public function __construct(
        protected MatchEngineOrchestrator $matchEngineOrchestrator,
        protected MatchmakingService $matchmakingService,
        protected NotificationService $notificationService
    ) {
    }

    /**
     * Converter posts to find job work (offer their capacity).
     */
    public function postFindJobwork(array $data, int $userId): array
    {
        return DB::transaction(function () use ($data, $userId) {
            $converter = Converter::where('user_id', $userId)->firstOrFail();

            if (!$converter->profile_complete || $converter->status !== ConverterStatus::ACTIVE) {
                throw new \Exception('Converter profile must be complete and active to post job work', 400);
            }

            $timeline = (string) ($data['timeline'] ?? 'Normal');
            $urgency = strtolower($timeline) === 'urgent' ? 'urgent' : 'normal';

            $postingFeeBase = 50;
            $urgencyAddon = $urgency === 'urgent' ? 20 : 0;
            $postingFeeAmount = $postingFeeBase + $urgencyAddon;

            $wallet = Wallet::firstOrCreate(
                ['user_id' => $userId],
                ['balance' => 0, 'status' => 'ACTIVE']
            );

            $tx = $wallet->deductCredits(
                $postingFeeAmount,
                'Post jobwork (find) requirement fee',
                'REQUIREMENT_POSTED',
                null,
                'inquiry',
                ['source' => 'converter_jobwork_find']
            );

            if ($tx === null) {
                throw new \Exception('Insufficient wallet balance. Please purchase credits first.', 400);
            }

            $minOrderQty = isset($data['minimum_order_quantity'])
                ? (float) $data['minimum_order_quantity']
                : 0.0;

            $specs = [
                'jobwork_type' => $data['jobwork_type'],
                'machinery_available' => $data['machinery_available'],
                'sample_available' => (bool) ($data['sample_available'] ?? false),
                'sample_image' => $data['sample_image'] ?? null,
                'city' => isset($data['city']) && (string) $data['city'] !== '' ? (string) $data['city'] : null,
            ];

            $location = isset($data['location']) && (string) $data['location'] !== ''
                ? (string) $data['location']
                : $converter->factory_city;
            $latitude = isset($data['latitude']) && $data['latitude'] !== null
                ? (float) $data['latitude']
                : ($converter->factory_latitude ?? null);
            $longitude = isset($data['longitude']) && $data['longitude'] !== null
                ? (float) $data['longitude']
                : ($converter->factory_longitude ?? null);

            $title = sprintf(
                '%s Jobwork - Looking for work',
                ucfirst($data['jobwork_type'])
            );

            $inquiry = Inquiry::create([
                'poster_id' => $converter->id,
                'poster_type' => 'converter',
                'brand_id' => null,
                'title' => $title,
                'description' => $data['special_instructions'] ?? null,
                'status' => InquiryStatus::MATCHING,
                'urgency' => $urgency,
                'inquiry_type' => InquiryType::JOB,
                'intent' => InquiryIntent::SELL,
                'job_type' => $data['jobwork_type'],
                'quantity' => $minOrderQty,
                'quantity_unit' => 'pieces',
                'timeline' => $timeline,
                'special_needs' => $data['special_instructions'] ?? null,
                'location' => $location,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'posting_fee_paid' => true,
                'posting_fee_amount' => $postingFeeAmount,
                'visibility' => 'converters',
                'specs' => $specs,
            ]);

            $session = MatchingSession::create([
                'inquiry_id' => $inquiry->id,
                'status' => SessionStatus::ACTIVE,
                'locked_at' => now(),
                'expires_at' => now()->addHours(24),
                'discovery_start' => now(),
                'active_session_start' => now(),
                'is_visible_to_dealers' => false,
                'is_visible_to_brand' => false,
            ]);

            $inquiry->load(['items', 'materials', 'session']);
            $result = $this->matchEngineOrchestrator->runMatchmaking($inquiry);

            $dealerIds = $result['dealer_ids'] ?? [];
            $converterIds = $result['converter_ids'] ?? [];
            $machineDealerIds = $result['machine_dealer_ids'] ?? [];

            $matchedRecipientsCount = count($dealerIds) + count($converterIds) + count($machineDealerIds);

            if ($matchedRecipientsCount > 0 && $converter->user_id) {
                $posterUserId = (int) $converter->user_id;
                $this->notificationService->create(
                    $posterUserId,
                    NotificationType::MATCH_FOUND,
                    'New Match Found',
                    'Your jobwork post has new matching converters.',
                    NavigationType::SESSION,
                    (string) $session->id,
                    [
                        'inquiry_id' => $inquiry->id,
                        'material_name' => $inquiry->title ?? 'Jobwork',
                        'counterparty_name' => 'Responder',
                        'view_target' => 'poster',
                        'poster_user_id' => $posterUserId,
                    ],
                    sprintf('match_found_poster_%s_%s', $inquiry->id, $posterUserId)
                );
            }

            $this->matchmakingService->notifyMatchedRecipients(
                $inquiry,
                $dealerIds,
                $converterIds,
                $machineDealerIds
            );

            return [
                'inquiry_id' => $inquiry->id,
                'session_id' => $session->id,
                'matched_converters_count' => count($converterIds),
            ];
        });
    }

    /**
     * Converter posts to give job work (outsource to other converters).
     */
    public function postGiveJobwork(array $data, int $userId): array
    {
        return DB::transaction(function () use ($data, $userId) {
            $converter = Converter::where('user_id', $userId)->firstOrFail();

            if (!$converter->profile_complete || $converter->status !== ConverterStatus::ACTIVE) {
                throw new \Exception('Converter profile must be complete and active to post job work', 400);
            }

            $timeline = (string) ($data['timeline'] ?? 'Normal');
            $urgency = strtolower($timeline) === 'urgent' ? 'urgent' : 'normal';

            $postingFeeBase = 50;
            $urgencyAddon = $urgency === 'urgent' ? 20 : 0;
            $postingFeeAmount = $postingFeeBase + $urgencyAddon;

            $wallet = Wallet::firstOrCreate(
                ['user_id' => $userId],
                ['balance' => 0, 'status' => 'ACTIVE']
            );

            $tx = $wallet->deductCredits(
                $postingFeeAmount,
                'Post jobwork (give) requirement fee',
                'REQUIREMENT_POSTED',
                null,
                'inquiry',
                ['source' => 'converter_jobwork_give']
            );

            if ($tx === null) {
                throw new \Exception('Insufficient wallet balance. Please purchase credits first.', 400);
            }

            $quantity = isset($data['quantity']) ? (float) $data['quantity'] : 0.0;

            $specs = [
                'jobwork_type' => $data['jobwork_type'],
                'raw_materials' => $data['raw_materials'] ?? null,
                'size' => $data['size'] ?? null,
                'size_unit' => $data['size_unit'] ?? null,
                'thickness' => $data['thickness'] ?? null,
                'thickness_unit' => $data['thickness_unit'] ?? null,
                'grade_finish' => $data['grade_finish'] ?? null,
                'quality_requirements' => $data['quality_requirements'] ?? null,
                'other_instructions' => $data['other_instructions'] ?? null,
            ];

            $title = sprintf(
                '%s Jobwork - Outsourcing %s pcs',
                ucfirst($data['jobwork_type']),
                $quantity > 0 ? (string) $quantity : ''
            );

            $location = $data['delivery_location'] ?? $converter->factory_city;
            $latitude = $data['latitude'] ?? $converter->factory_latitude;
            $longitude = $data['longitude'] ?? $converter->factory_longitude;

            $combinedInstructionsParts = [];
            if (!empty($data['quality_requirements'])) {
                $combinedInstructionsParts[] = 'Quality: ' . $data['quality_requirements'];
            }
            if (!empty($data['other_instructions'])) {
                $combinedInstructionsParts[] = $data['other_instructions'];
            }
            $combinedInstructions = implode(' | ', $combinedInstructionsParts);

            $inquiry = Inquiry::create([
                'poster_id' => $converter->id,
                'poster_type' => 'converter',
                'brand_id' => null,
                'title' => $title,
                'description' => $combinedInstructions ?: null,
                'status' => InquiryStatus::MATCHING,
                'urgency' => $urgency,
                'inquiry_type' => InquiryType::JOB,
                'intent' => InquiryIntent::BUY,
                'job_type' => $data['jobwork_type'],
                'quantity' => $quantity,
                'quantity_unit' => 'pieces',
                'timeline' => $timeline,
                'special_needs' => $combinedInstructions ?: null,
                'location' => $location,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'posting_fee_paid' => true,
                'posting_fee_amount' => $postingFeeAmount,
                'visibility' => 'converters',
                'specs' => $specs,
            ]);

            $session = MatchingSession::create([
                'inquiry_id' => $inquiry->id,
                'status' => SessionStatus::ACTIVE,
                'locked_at' => now(),
                'expires_at' => now()->addHours(24),
                'discovery_start' => now(),
                'active_session_start' => now(),
                'is_visible_to_dealers' => false,
                'is_visible_to_brand' => false,
            ]);

            $inquiry->load(['items', 'materials', 'session']);
            $result = $this->matchEngineOrchestrator->runMatchmaking($inquiry);

            $dealerIds = $result['dealer_ids'] ?? [];
            $converterIds = $result['converter_ids'] ?? [];
            $machineDealerIds = $result['machine_dealer_ids'] ?? [];

            $matchedRecipientsCount = count($dealerIds) + count($converterIds) + count($machineDealerIds);

            if ($matchedRecipientsCount > 0 && $converter->user_id) {
                $posterUserId = (int) $converter->user_id;
                $this->notificationService->create(
                    $posterUserId,
                    NotificationType::MATCH_FOUND,
                    'New Match Found',
                    'Your jobwork requirement has new matching converters.',
                    NavigationType::SESSION,
                    (string) $session->id,
                    [
                        'inquiry_id' => $inquiry->id,
                        'material_name' => $inquiry->title ?? 'Jobwork',
                        'counterparty_name' => 'Responder',
                        'view_target' => 'poster',
                        'poster_user_id' => $posterUserId,
                    ],
                    sprintf('match_found_poster_%s_%s', $inquiry->id, $posterUserId)
                );
            }

            $this->matchmakingService->notifyMatchedRecipients(
                $inquiry,
                $dealerIds,
                $converterIds,
                $machineDealerIds
            );

            return [
                'inquiry_id' => $inquiry->id,
                'session_id' => $session->id,
                'matched_converters_count' => count($converterIds),
            ];
        });
    }
}

