<?php

namespace Tests\Feature\MatchEngine;

use App\Domain\MatchEngine\MatchEngineOrchestrator;
use App\Enums\ConverterStatus;
use App\Enums\InquiryStatus;
use App\Enums\MachineDealerStatus;
use App\Enums\InquiryType;
use App\Models\Converter;
use App\Models\Machine;
use App\Models\MachineDealer;
use App\Models\MatchmakingLog;
use App\Models\MatchingSession;
use App\Models\Inquiry;
use App\Models\User;
use App\Services\ConverterService;
use App\Services\MachineDealerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class MachineFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_lazy_matching_creates_logs_and_scope_for_machine_dealer_on_converter_machine_inquiry(): void
    {
        Config::set('matchmaking.engine_version', 'v2');
        Config::set('matchmaking.auto_match_on_login', true);

        $converterUser = User::factory()->create(['primary_role' => 'converter']);
        $converter = Converter::create([
            'user_id' => $converterUser->id,
            'converter_type_custom' => null,
            'capacity_daily' => 1000,
            'capacity_monthly' => 30000,
            'capacity_unit' => 'sheets',
            'factory_address' => 'Factory Address',
            'factory_city' => 'Mumbai',
            'factory_state' => 'MH',
            'factory_latitude' => 19.0760,
            'factory_longitude' => 72.8777,
            'status' => ConverterStatus::ACTIVE,
            'profile_complete' => true,
        ]);

        $machine = Machine::create([
            'name' => 'Test Machine',
            'type' => 'Printer',
            'description' => 'Test machine description',
        ]);

        /** @var ConverterService $converterService */
        $converterService = app(ConverterService::class);
        $result = $converterService->postMachine([
            'machine_id' => $machine->id,
            'intent' => 'buy',
            'urgency' => 'normal',
            'description' => 'Need machine',
            'attachments' => [],
            'price' => null,
            'currency' => 'INR',
            'location' => 'Mumbai',
            'latitude' => 19.0760,
            'longitude' => 72.8777,
            // Keep test DB compatibility (sqlite enum check) while still matching machine dealers
            'visibility' => 'all',
        ], $converterUser->id);

        $inquiry = Inquiry::findOrFail($result['inquiry_id']);
        $this->assertEquals(InquiryType::MACHINE, $inquiry->inquiry_type);
        $this->assertEquals('all', $inquiry->visibility);
        $this->assertEquals(InquiryStatus::MATCHING, $inquiry->status);

        $session = $inquiry->session;
        $this->assertNotNull($session);

        $this->assertSame(
            0,
            MatchmakingLog::where('inquiry_id', $inquiry->id)->whereNotNull('machine_dealer_id')->count(),
            'No machine_dealer logs should exist before lazy matching'
        );

        $machineDealerUser = User::factory()->create(['primary_role' => 'machine-dealer']);
        $machineDealer = MachineDealer::create([
            'user_id' => $machineDealerUser->id,
            'company_name' => 'MD Co',
            'contact_person_name' => 'Owner',
            'gst' => null,
            'mobile' => '9999999999',
            'email' => 'md@example.com',
            'city' => 'Mumbai',
            'location' => 'Mumbai',
            'latitude' => 19.0760,
            'longitude' => 72.8777,
            'primary_machine_category' => null,
            'primary_machine_id' => null,
            'preferred_brand_names' => null,
            'status' => MachineDealerStatus::ACTIVE,
            'profile_complete' => true,
        ]);

        /** @var MatchEngineOrchestrator $orchestrator */
        $orchestrator = app(MatchEngineOrchestrator::class);
        $orchestrator->ensureMatchesForUser($machineDealerUser);

        $this->assertDatabaseHas('matchmaking_logs', [
            'inquiry_id' => $inquiry->id,
            'machine_dealer_id' => $machineDealer->id,
            'is_visible' => true,
        ]);

        $visibleSessionIds = MatchingSession::visibleToMachineDealer($machineDealer->id)->pluck('id')->all();
        $this->assertContains($session->id, $visibleSessionIds);
    }

    public function test_lazy_matching_writes_compatibility_log_for_machine_dealer_camel_case_role(): void
    {
        Config::set('matchmaking.engine_version', 'v2');
        Config::set('matchmaking.auto_match_on_login', true);

        $converterUser = User::factory()->create(['primary_role' => 'converter']);
        Converter::create([
            'user_id' => $converterUser->id,
            'converter_type_custom' => null,
            'capacity_daily' => 1000,
            'capacity_monthly' => 30000,
            'capacity_unit' => 'sheets',
            'factory_address' => 'Factory Address',
            'factory_city' => 'Mumbai',
            'factory_state' => 'MH',
            'factory_latitude' => 19.0760,
            'factory_longitude' => 72.8777,
            'status' => ConverterStatus::ACTIVE,
            'profile_complete' => true,
        ]);

        $machine = Machine::create([
            'name' => 'Test Machine',
            'type' => 'Printer',
            'description' => 'Test machine description',
        ]);

        /** @var ConverterService $converterService */
        $converterService = app(ConverterService::class);
        $result = $converterService->postMachine([
            'machine_id' => $machine->id,
            'intent' => 'sell',
            'urgency' => 'normal',
            'description' => 'Selling machine',
            'attachments' => [],
            'price' => null,
            'currency' => 'INR',
            'location' => 'Mumbai',
            'latitude' => 19.0760,
            'longitude' => 72.8777,
            'visibility' => 'all',
        ], $converterUser->id);

        $inquiry = Inquiry::findOrFail($result['inquiry_id']);

        $machineDealerUser = User::factory()->create(['primary_role' => 'machineDealer']);
        $machineDealer = MachineDealer::create([
            'user_id' => $machineDealerUser->id,
            'company_name' => 'MD Co',
            'contact_person_name' => 'Owner',
            'gst' => null,
            'mobile' => '9999999999',
            'email' => 'md@example.com',
            'city' => 'Mumbai',
            'location' => 'Mumbai',
            'latitude' => 19.0760,
            'longitude' => 72.8777,
            'primary_machine_category' => null,
            'primary_machine_id' => null,
            'preferred_brand_names' => null,
            'status' => MachineDealerStatus::ACTIVE,
            'profile_complete' => true,
        ]);

        /** @var MatchEngineOrchestrator $orchestrator */
        $orchestrator = app(MatchEngineOrchestrator::class);
        $orchestrator->ensureMatchesForUser($machineDealerUser);

        $this->assertDatabaseHas('matchmaking_logs', [
            'inquiry_id' => $inquiry->id,
            'machine_dealer_id' => $machineDealer->id,
            'is_visible' => true,
        ]);
    }

    public function test_lazy_matching_creates_logs_and_scope_for_converter_on_machine_dealer_listing(): void
    {
        Config::set('matchmaking.engine_version', 'v2');
        Config::set('matchmaking.auto_match_on_login', true);

        $machineDealerUser = User::factory()->create(['primary_role' => 'machine-dealer']);
        $machineDealer = MachineDealer::create([
            'user_id' => $machineDealerUser->id,
            'company_name' => 'MD Co',
            'contact_person_name' => 'Owner',
            'gst' => null,
            'mobile' => '9999999999',
            'email' => 'md@example.com',
            'city' => 'Mumbai',
            'location' => 'Mumbai',
            'latitude' => 19.0760,
            'longitude' => 72.8777,
            'primary_machine_category' => null,
            'primary_machine_id' => null,
            'preferred_brand_names' => null,
            'status' => MachineDealerStatus::ACTIVE,
            'profile_complete' => true,
        ]);

        $machine = Machine::create([
            'name' => 'Test Machine',
            'type' => 'Printer',
            'description' => 'Test machine description',
        ]);

        /** @var MachineDealerService $machineDealerService */
        $machineDealerService = app(MachineDealerService::class);
        $listingResult = $machineDealerService->postMachine([
            'machine_id' => $machine->id,
            'machine_brand_id' => null,
            'machine_type' => null,
            'condition' => 'Working Condition',
            'intent' => 'sell',
            'urgency' => 'normal',
            'description' => 'Selling machine',
            'attachments' => [],
            'price' => null,
            'currency' => 'INR',
            'location' => 'Mumbai',
            'latitude' => 19.0760,
            'longitude' => 72.8777,
            // visibility omitted → defaults to "converters"
        ], $machineDealerUser->id);

        $inquiry = Inquiry::findOrFail($listingResult['inquiry_id']);
        $this->assertEquals(InquiryType::MACHINE, $inquiry->inquiry_type);
        $this->assertEquals('converters', $inquiry->visibility);
        $this->assertEquals(InquiryStatus::MATCHING, $inquiry->status);

        $session = $inquiry->session;
        $this->assertNotNull($session);

        $this->assertSame(
            0,
            MatchmakingLog::where('inquiry_id', $inquiry->id)->whereNotNull('converter_id')->count(),
            'No converter logs should exist before lazy matching'
        );

        $converterUser = User::factory()->create(['primary_role' => 'converter']);
        $converter = Converter::create([
            'user_id' => $converterUser->id,
            'converter_type_custom' => null,
            'capacity_daily' => 1000,
            'capacity_monthly' => 30000,
            'capacity_unit' => 'sheets',
            'factory_address' => 'Factory Address',
            'factory_city' => 'Mumbai',
            'factory_state' => 'MH',
            'factory_latitude' => 19.0760,
            'factory_longitude' => 72.8777,
            'status' => ConverterStatus::ACTIVE,
            'profile_complete' => true,
        ]);

        /** @var MatchEngineOrchestrator $orchestrator */
        $orchestrator = app(MatchEngineOrchestrator::class);
        $orchestrator->ensureMatchesForUser($converterUser);

        $this->assertDatabaseHas('matchmaking_logs', [
            'inquiry_id' => $inquiry->id,
            'converter_id' => $converter->id,
            'is_visible' => true,
        ]);

        $visibleSessionIds = MatchingSession::visibleToConverter($converter->id)->pluck('id')->all();
        $this->assertContains($session->id, $visibleSessionIds);
    }
}

