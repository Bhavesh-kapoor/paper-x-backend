<?php

namespace Tests\Feature\Jobwork;

use App\Enums\ConverterStatus;
use App\Enums\InquiryIntent;
use App\Enums\InquiryStatus;
use App\Enums\InquiryType;
use App\Models\Converter;
use App\Models\ConverterType;
use App\Models\Inquiry;
use App\Models\MatchingSession;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ConverterJobworkApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\EnsureTokenExist::class);
    }

    private function createConverterUserWithWallet(float $walletBalance = 200): array
    {
        $user = User::factory()->create([
            'primary_role' => 'converter',
        ]);

        $converter = Converter::create([
            'user_id' => $user->id,
            'factory_city' => 'Test City',
            'factory_state' => 'Test State',
            'factory_latitude' => 19.0,
            'factory_longitude' => 72.0,
            'status' => ConverterStatus::ACTIVE,
            'profile_complete' => true,
        ]);

        // Attach at least one job_work converter type so CandidateResolver
        // can treat this converter as eligible for jobwork inquiries.
        $jobWorkType = ConverterType::firstOrCreate(
            ['name' => 'Generic Job Work', 'category' => 'job_work'],
            ['sort_order' => 1000]
        );
        $converter->converterTypes()->attach($jobWorkType->id);

        $wallet = Wallet::create([
            'user_id' => $user->id,
            'balance' => $walletBalance,
            'status' => 'ACTIVE',
        ]);

        return [$user, $converter, $wallet];
    }

    public function test_converter_can_post_find_jobwork_successfully(): void
    {
        [$user] = $this->createConverterUserWithWallet(200);
        Sanctum::actingAs($user);

        $payload = [
            'jobwork_type' => 'printing',
            'machinery_available' => '4-color offset press',
            'timeline' => 'Normal',
            'minimum_order_quantity' => 1000,
            'special_instructions' => 'Short runs welcome',
            'sample_available' => true,
            'sample_image' => 'https://example.com/sample.jpg',
        ];

        $response = $this->postJson('/api/v1/converter/jobwork/find', $payload);
        $response->assertStatus(201);

        $data = $response->json('data') ?? [];
        $this->assertArrayHasKey('inquiry_id', $data);
        $this->assertArrayHasKey('session_id', $data);

        $inquiryId = (int) $data['inquiry_id'];
        $sessionId = (int) $data['session_id'];

        $inquiry = Inquiry::findOrFail($inquiryId);
        $this->assertSame('converter', $inquiry->poster_type);
        $this->assertSame(InquiryType::JOB, $inquiry->inquiry_type);
        $this->assertSame(InquiryIntent::SELL, $inquiry->intent);
        $this->assertSame('printing', $inquiry->job_type);
        $this->assertSame('converters', $inquiry->visibility);
        $this->assertTrue($inquiry->posting_fee_paid);
        $this->assertSame(InquiryStatus::MATCHING, $inquiry->status);

        $session = MatchingSession::findOrFail($sessionId);
        $this->assertSame($inquiryId, $session->inquiry_id);
    }

    public function test_converter_can_post_give_jobwork_successfully(): void
    {
        [$user] = $this->createConverterUserWithWallet(200);
        Sanctum::actingAs($user);

        $payload = [
            'jobwork_type' => 'die-cutting',
            'raw_materials' => '400 gsm board',
            'quantity' => 5000,
            'quality_requirements' => 'Tolerance ±0.5mm',
            'timeline' => 'Urgent',
            'delivery_location' => 'Test Delivery City',
            'latitude' => 19.1,
            'longitude' => 72.1,
            'other_instructions' => 'Need within 3 days',
        ];

        $response = $this->postJson('/api/v1/converter/jobwork/give', $payload);
        $response->assertStatus(201);

        $data = $response->json('data') ?? [];
        $this->assertArrayHasKey('inquiry_id', $data);
        $this->assertArrayHasKey('session_id', $data);

        $inquiryId = (int) $data['inquiry_id'];
        $sessionId = (int) $data['session_id'];

        $inquiry = Inquiry::findOrFail($inquiryId);
        $this->assertSame('converter', $inquiry->poster_type);
        $this->assertSame(InquiryType::JOB, $inquiry->inquiry_type);
        $this->assertSame(InquiryIntent::BUY, $inquiry->intent);
        $this->assertSame('die-cutting', $inquiry->job_type);
        $this->assertSame('converters', $inquiry->visibility);
        $this->assertTrue($inquiry->posting_fee_paid);
        $this->assertSame(InquiryStatus::MATCHING, $inquiry->status);

        $session = MatchingSession::findOrFail($sessionId);
        $this->assertSame($inquiryId, $session->inquiry_id);
    }

    public function test_post_find_jobwork_fails_when_insufficient_wallet_balance(): void
    {
        [$user] = $this->createConverterUserWithWallet(0);
        Sanctum::actingAs($user);

        $payload = [
            'jobwork_type' => 'printing',
            'machinery_available' => 'Any',
            'timeline' => 'Normal',
        ];

        $response = $this->postJson('/api/v1/converter/jobwork/find', $payload);
        $response->assertStatus(400);
        $this->assertStringContainsString(
            'Insufficient wallet balance',
            (string) ($response->json('message') ?? '')
        );
    }
}

