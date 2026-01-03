<?php

namespace App\Services;

use App\Enums\MachineDealerStatus;
use App\Enums\InquiryStatus;
use App\Enums\InquiryType;
use App\Models\MachineDealer;
use App\Models\MachineListing;
use App\Models\Inquiry;
use Illuminate\Support\Facades\DB;

class MachineDealerService
{
    public function completeProfile(array $data, int $userId): MachineDealer
    {
        return DB::transaction(function () use ($data, $userId) {
            $machineDealer = MachineDealer::firstOrCreate(
                ['user_id' => $userId],
                ['status' => MachineDealerStatus::PENDING]
            );

            $machineDealer->update([
                'company_name' => $data['company_name'],
                'gst' => $data['gst'] ?? null,
                'contact_person_name' => $data['contact_person_name'],
                'mobile' => $data['mobile'] ?? null,
                'email' => $data['email'] ?? null,
                'city' => $data['city'] ?? null,
                'location' => $data['location'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'profile_complete' => true,
                'status' => MachineDealerStatus::ACTIVE,
            ]);

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
            $query->where('poster_id', $machineDealer->user_id)
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

            // Create inquiry for the listing
            $inquiry = Inquiry::create([
                'poster_id' => $userId,
                'poster_type' => 'machine_dealer',
                'inquiry_type' => InquiryType::MACHINE,
                'intent' => $data['intent'],
                'title' => $data['title'] ?? "Machine: " . $listing->machine->name,
                'description' => $data['description'] ?? null,
                'urgency' => $data['urgency'],
                'machine_listing_id' => $listing->id,
                'machine_condition' => $data['condition'] ?? null,
                'location' => $data['location'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'status' => InquiryStatus::MATCHING,
                'posting_fee_paid' => $data['posting_fee_paid'] ?? false,
                'posting_fee_amount' => $data['posting_fee_amount'] ?? null,
            ]);

            // Attach machine to inquiry
            $inquiry->machines()->attach($data['machine_id']);

            return [
                'id' => $listing->id,
                'machine_listing_id' => $listing->id,
                'inquiry_id' => $inquiry->id,
                'status' => $inquiry->status->value,
            ];
        });
    }

    public function getActiveListings(int $userId): array
    {
        $machineDealer = MachineDealer::where('user_id', $userId)->firstOrFail();

        $listings = MachineListing::where('machine_dealer_id', $machineDealer->id)
            ->where('status', 'ACTIVE')
            ->with(['machine', 'machineBrand'])
            ->get();

        return $listings->map(function ($listing) {
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
        })->toArray();
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

