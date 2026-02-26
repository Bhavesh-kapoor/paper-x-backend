<?php

namespace App\Services;

use App\Enums\MachineDealerStatus;
use App\Enums\InquiryStatus;
use App\Enums\InquiryType;
use App\Enums\SessionStatus;
use App\Models\MachineDealer;
use App\Models\MachineListing;
use App\Models\Inquiry;
use App\Models\MatchingSession;
use App\Domain\MatchEngine\MatchEngineOrchestrator;
use Illuminate\Support\Facades\DB;

class MachineDealerService
{
    public function __construct(
        protected MatchEngineOrchestrator $matchEngineOrchestrator,
    ) {
    }

    public function completeProfile(array $data, int $userId): MachineDealer
    {
        return DB::transaction(function () use ($data, $userId) {
            $createAttributes = [
                'company_name' => $data['company_name'],
                'contact_person_name' => $data['contact_person_name'],
                'gst' => $data['gst'] ?? null,
                'mobile' => $data['mobile'] ?? null,
                'email' => $data['email'] ?? null,
                'city' => $data['city'] ?? null,
                'location' => $data['location'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'primary_machine_category' => $data['primary_machine_category'] ?? null,
                'primary_machine_id' => $data['primary_machine_id'] ?? null,
                'preferred_brand_names' => $data['preferred_brand_names'] ?? null,
                'profile_complete' => true,
                'status' => MachineDealerStatus::ACTIVE,
            ];

            $machineDealer = MachineDealer::firstOrCreate(
                ['user_id' => $userId],
                $createAttributes
            );

            if ($machineDealer->wasRecentlyCreated === false) {
                $machineDealer->update($createAttributes);
            }

            return $machineDealer;
        });
    }

    public function getDashboard(int $userId): array
    {
        $machineDealer = MachineDealer::where('user_id', $userId)->first();
        
        if (!$machineDealer) {
            return [
                'profile_completion_percentage' => 0,
                'active_listings_count' => 0,
                'active_requirements_count' => 0,
                'responses_received_count' => 0,
            ];
        }

        $activeListings = MachineListing::where('machine_dealer_id', $machineDealer->id)
            ->where('status', 'ACTIVE')
            ->count();

        $activeRequirements = Inquiry::where('inquiry_type', InquiryType::MACHINE)
            ->where('status', InquiryStatus::MATCHING)
            ->orWhere('status', InquiryStatus::SESSION_LOCKED)
            ->count();

        $responsesReceived = \App\Models\Response::whereHas('inquiry', function ($query) use ($machineDealer) {
            $query->where('poster_id', $machineDealer->id)
                ->where('poster_type', 'machine_dealer');
        })->count();

        return [
            'profile_completion_percentage' => $machineDealer->profile_complete ? 100 : 0,
            'active_listings_count' => $activeListings,
            'active_requirements_count' => $activeRequirements,
            'responses_received_count' => $responsesReceived,
        ];
    }

    public function postMachine(array $data, int $userId): array
    {
        return DB::transaction(function () use ($data, $userId) {
            $machineDealer = MachineDealer::where('user_id', $userId)->firstOrFail();
            $visibility = $data['visibility'] ?? 'converters';

            // Create machine listing
            $listing = MachineListing::create([
                'machine_dealer_id' => $machineDealer->id,
                'machine_id' => $data['machine_id'],
                'machine_brand_id' => $data['machine_brand_id'] ?? null,
                'machine_type' => $data['machine_type'] ?? null,
                'condition' => $data['condition'] ?? null,
                'intent' => $data['intent'],
                'urgency' => $data['urgency'],
                'description' => $data['description'] ?? null,
                'attachments' => $data['attachments'] ?? null,
                'price' => $data['price'] ?? null,
                'currency' => $data['currency'] ?? 'INR',
                'location' => $data['location'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'status' => 'ACTIVE',
                'posting_fee_paid' => $data['posting_fee_paid'] ?? false,
                'posting_fee_amount' => $data['posting_fee_amount'] ?? null,
            ]);

            // Create inquiry for the listing (quantity/quantity_unit required by inquiries table; use 1 unit for machine)
            $inquiry = Inquiry::create([
                'poster_id' => $machineDealer->id, // Store machine dealer ID, not user ID
                'poster_type' => 'machine_dealer',
                'inquiry_type' => InquiryType::MACHINE,
                'intent' => $data['intent'],
                'title' => $data['title'] ?? "Machine: " . $listing->machine->name,
                'description' => $data['description'] ?? null,
                'urgency' => $data['urgency'],
                'quantity' => 1,
                'quantity_unit' => 'unit',
                'machine_listing_id' => $listing->id,
                'machine_condition' => $data['condition'] ?? null,
                'location' => $data['location'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'status' => InquiryStatus::MATCHING,
                'posting_fee_paid' => $data['posting_fee_paid'] ?? false,
                'posting_fee_amount' => $data['posting_fee_amount'] ?? null,
                'visibility' => $visibility,
            ]);

            // Attach machine to inquiry
            $inquiry->machines()->attach($data['machine_id']);

            $session = MatchingSession::create([
                'inquiry_id' => $inquiry->id,
                'status' => SessionStatus::ACTIVE,
                'locked_at' => null,
                'expires_at' => now()->addHours(24),
                'discovery_start' => now(),
                'active_session_start' => now(),
                'is_visible_to_dealers' => true,
                'is_visible_to_brand' => false,
            ]);

            $inquiry->setRelation('session', $session);

            $this->matchEngineOrchestrator->runMatchmaking($inquiry);

            return [
                'id' => $listing->id,
                'machine_listing_id' => $listing->id,
                'inquiry_id' => $inquiry->id,
                'status' => $inquiry->status->value,
            ];
        });
    }

    public function getActiveListings(int $userId, array $filters = []): array
    {
        $machineDealer = MachineDealer::where('user_id', $userId)->firstOrFail();

        $perPage = $filters['per_page'] ?? 15;

        $listings = MachineListing::where('machine_dealer_id', $machineDealer->id)
            ->where('status', 'ACTIVE')
            ->with(['machine', 'machineBrand'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        $listingsData = $listings->map(function ($listing) {
            $inquiry = Inquiry::where('machine_listing_id', $listing->id)->first();
            return [
                'id' => $listing->id,
                'machine' => $listing->machine->name,
                'machine_brand' => $listing->machineBrand?->name,
                'condition' => $listing->condition,
                'intent' => $listing->intent,
                'status' => $listing->status,
                'responses_count' => $inquiry ? $inquiry->responses()->count() : 0,
                'created_at' => $listing->created_at->toIso8601String(),
            ];
        });

        return [
            'listings' => $listingsData->toArray(),
            'pagination' => [
                'current_page' => $listings->currentPage(),
                'total' => $listings->total(),
                'per_page' => $listings->perPage(),
                'last_page' => $listings->lastPage(),
            ],
        ];
    }

    public function getActiveRequirements(array $filters = []): array
    {
        $query = Inquiry::where('inquiry_type', InquiryType::MACHINE)
            ->whereIn('status', [InquiryStatus::MATCHING, InquiryStatus::SESSION_LOCKED])
            ->with(['machines', 'session']);

        if (isset($filters['intent'])) {
            $query->where('intent', $filters['intent']);
        }

        if (isset($filters['urgency'])) {
            $query->where('urgency', $filters['urgency']);
        }

        if (isset($filters['machine_id'])) {
            $query->whereHas('machines', function ($q) use ($filters) {
                $q->where('machines.id', $filters['machine_id']);
            });
        }

        $inquiries = $query->paginate($filters['per_page'] ?? 15);

        return [
            'requirements' => $inquiries->map(function ($inquiry) {
                return [
                    'id' => $inquiry->id,
                    'inquiry_id' => $inquiry->id,
                    'machine' => $inquiry->machines->first()?->name,
                    'condition' => $inquiry->machine_condition,
                    'intent' => $inquiry->intent->value,
                    'urgency' => $inquiry->urgency,
                    'location' => $inquiry->location,
                    'poster_type' => $inquiry->poster_type,
                    'status' => $inquiry->status->value,
                    'created_at' => $inquiry->created_at->toIso8601String(),
                ];
            }),
            'pagination' => [
                'current_page' => $inquiries->currentPage(),
                'total' => $inquiries->total(),
                'per_page' => $inquiries->perPage(),
                'last_page' => $inquiries->lastPage(),
            ],
        ];
    }
}




