<?php

namespace Tests\Feature\Wallet;

use App\Services\Payments\Contracts\RazorpayClient;
use App\Services\Payments\FakeRazorpayClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class WalletRazorpayTestCase extends TestCase
{
    use RefreshDatabase;
    use WalletTestHelpers;

    protected FakeRazorpayClient $fakeRzp;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeRzp = new FakeRazorpayClient;
        $this->app->instance(RazorpayClient::class, $this->fakeRzp);

        config([
            'services.razorpay.key_id' => 'rzp_test_xxxxx',
            'services.razorpay.key_secret' => 'test_secret_key',
            'services.razorpay.webhook_secret' => 'whsec_test',
            'services.razorpay.currency' => 'INR',
        ]);
    }
}
