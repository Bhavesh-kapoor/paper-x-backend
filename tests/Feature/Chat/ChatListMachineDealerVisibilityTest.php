<?php

namespace Tests\Feature\Chat;

use App\Enums\ConverterStatus;
use App\Enums\InquiryStatus;
use App\Enums\MachineDealerStatus;
use App\Enums\SessionStatus;
use App\Models\ChatMessage;
use App\Models\Converter;
use App\Models\Inquiry;
use App\Models\MachineDealer;
use App\Models\MatchmakingLog;
use App\Models\MatchingSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatListMachineDealerVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_converter_chat_list_includes_machine_dealer_responder(): void
    {
        [$converterUser, $machineDealerUser, $inquiry, $session] = $this->seedConverterMachineDealerSession();

        $token = $converterUser->createToken('chat-list-converter')->plainTextToken;
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->getJson('/api/v1/sessions/chat-list');

        $response->assertStatus(200);

        $payload = $response->json('data');
        if (isset($payload['data']) && is_array($payload['data'])) {
            $payload = $payload['data'];
        }

        $row = collect($payload)->firstWhere('session_id', $session->id);

        $this->assertNotNull($row, 'Expected chat list to contain the machine dealer responder row.');
        $this->assertSame($inquiry->id, $row['inquiry_id']);
        $this->assertSame($machineDealerUser->id, $row['partner_id']);
        $this->assertSame('Machine Dealer Co', $row['partner_company']);
    }

    public function test_machine_dealer_responder_can_fetch_chat_messages_for_matched_session(): void
    {
        [, $machineDealerUser, , $session] = $this->seedConverterMachineDealerSession();

        ChatMessage::create([
            'session_id' => $session->id,
            'sender_id' => $machineDealerUser->id,
            // SQLite test schema may only allow DEALER/BRAND enum values.
            'sender_type' => 'DEALER',
            'message' => 'Hello converter',
            'attachment_path' => null,
            'status' => 'SENT',
        ]);

        $token = $machineDealerUser->createToken('chat-messages-machine-dealer')->plainTextToken;
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->getJson('/api/v1/sessions/chat/' . $session->id);

        $response->assertStatus(200)
            ->assertJsonPath('data.0.message', 'Hello converter');
    }

    /**
     * @return array{0: User, 1: User, 2: Inquiry, 3: MatchingSession}
     */
    private function seedConverterMachineDealerSession(): array
    {
        $converterUser = User::factory()->create([
            'primary_role' => 'converter',
            'name' => 'Converter User',
            'company_name' => 'Converter Co',
        ]);

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

        $machineDealerUser = User::factory()->create([
            'primary_role' => 'machineDealer',
            'name' => 'Machine Dealer User',
            'company_name' => 'Machine Dealer Co',
        ]);

        $machineDealer = MachineDealer::create([
            'user_id' => $machineDealerUser->id,
            'company_name' => 'Machine Dealer Co',
            'contact_person_name' => 'MD Contact',
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

        $inquiry = Inquiry::create([
            'poster_id' => $converter->id,
            'poster_type' => 'converter',
            'title' => 'Machine: 1-Color Offset Machine',
            'description' => 'Need machine',
            'status' => InquiryStatus::MATCHING,
            'urgency' => 'normal',
            'inquiry_type' => 'machine',
            'intent' => 'sell',
            'quantity' => 1,
            'quantity_unit' => 'pieces',
            'latitude' => 19.0760,
            'longitude' => 72.8777,
            'location' => 'Mumbai',
            'visibility' => 'all',
            'is_visible_to_dealers' => true,
            'is_visible_to_brand' => false,
        ]);

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

        MatchmakingLog::create([
            'inquiry_id' => $inquiry->id,
            'session_id' => $session->id,
            'machine_dealer_id' => $machineDealer->id,
            'is_visible' => true,
            'responded_at' => now(),
            'visible_to_dealer_at' => now(),
            'priority_score' => 88,
            'material_match' => true,
            'finish_match' => true,
            'thickness_match' => true,
            'location_match' => true,
            'score_breakdown' => [],
        ]);

        return [$converterUser, $machineDealerUser, $inquiry, $session];
    }
}

