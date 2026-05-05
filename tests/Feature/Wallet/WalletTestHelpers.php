<?php

namespace Tests\Feature\Wallet;

use App\Models\CreditPack;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

trait WalletTestHelpers
{
    protected function createUserWithToken(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'primary_role' => 'dealer',
        ], $overrides));
    }

    /**
     * @return array{0: string, 1: string} [Authorization Bearer..., Accept]
     */
    protected function authHeaders(User $user): array
    {
        $token = $user->createToken('rzp-test-'.$user->id)->plainTextToken;

        return [
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ];
    }

    /**
     * POST JSON with explicit Sanctum auth.
     * Clears defaultHeaders first so a prior request cannot leave a stale Authorization.
     */
    protected function postJsonAs(User $user, string $uri, array $data = []): TestResponse
    {
        $this->defaultHeaders = [];

        // Forget cached guards so the previous request's authenticated user
        // doesn't leak into this one (Laravel's AuthManager + RequestGuard
        // memoize `$this->user` and would otherwise return the prior caller).
        if ($this->app->bound('auth')) {
            $this->app['auth']->forgetGuards();
        }
        if ($this->app->bound('session')) {
            $this->app['session']->flush();
        }

        return $this->postJson($uri, $data, $this->authHeaders($user));
    }

    protected function postRazorpayWebhook(string $rawJson, string $signature): TestResponse
    {
        return $this->call(
            'POST',
            '/api/v1/webhooks/razorpay',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_X_RAZORPAY_SIGNATURE' => $signature,
            ],
            $rawJson
        );
    }

    protected function seedCreditPack(array $overrides = []): CreditPack
    {
        return CreditPack::query()->create(array_merge([
            'name' => 'Test Pack',
            'slug' => 'test-pack-'.Str::lower(Str::random(10)),
            'credits' => 100,
            'price' => 1000,
            'gst_percentage' => 18,
            'description' => null,
            'validity' => 'Lifetime',
            'is_best_value' => false,
            'is_active' => true,
            'sort_order' => 0,
        ], $overrides));
    }

    protected function checkoutSignature(string $orderId, string $paymentId, string $secret = 'test_secret_key'): string
    {
        return hash_hmac('sha256', $orderId.'|'.$paymentId, $secret);
    }

    protected function webhookSignature(string $rawBody, string $secret = 'whsec_test'): string
    {
        return hash_hmac('sha256', $rawBody, $secret);
    }

    /**
     * @return array{event: string, payload: array<string, mixed>}
     */
    protected function razorpayPaymentCapturedPayload(string $orderId, string $paymentId, int $amountPaise, string $currency = 'INR'): array
    {
        return [
            'entity' => 'event',
            'event' => 'payment.captured',
            'contains' => ['payment'],
            'payload' => [
                'payment' => [
                    'entity' => [
                        'id' => $paymentId,
                        'entity' => 'payment',
                        'amount' => $amountPaise,
                        'currency' => $currency,
                        'status' => 'captured',
                        'order_id' => $orderId,
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array{event: string, payload: array<string, mixed>}
     */
    protected function razorpayPaymentFailedPayload(string $orderId, string $paymentId): array
    {
        return [
            'entity' => 'event',
            'event' => 'payment.failed',
            'contains' => ['payment'],
            'payload' => [
                'payment' => [
                    'entity' => [
                        'id' => $paymentId,
                        'entity' => 'payment',
                        'order_id' => $orderId,
                        'status' => 'failed',
                    ],
                ],
            ],
        ];
    }
}
