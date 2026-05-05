<?php

namespace App\Providers;

use App\Models\ChatThread;
use App\Policies\ChatThreadPolicy;
use App\Services\Payments\Contracts\RazorpayClient;
use App\Services\Payments\RazorpayGatewayClient;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Razorpay\Api\Api;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(RazorpayClient::class, function () {
            $keyId = (string) config('services.razorpay.key_id', '');
            $secret = (string) config('services.razorpay.key_secret', '');

            return new RazorpayGatewayClient(new Api($keyId, $secret));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        Gate::policy(ChatThread::class, ChatThreadPolicy::class);

        // Map polymorphic types for inquiries poster relationship, notifications, and Sanctum tokens
        // User must be in the map or OTP verify (createToken) throws "morph map" → 500
        \Illuminate\Database\Eloquent\Relations\Relation::enforceMorphMap([
            'user' => \App\Models\User::class,
            'dealer' => \App\Models\Dealer::class,
            'converter' => \App\Models\Converter::class,
            'machine_dealer' => \App\Models\MachineDealer::class,
            'brand' => \App\Models\Brand::class,
            'inquiry' => \App\Models\Inquiry::class,
            'session' => \App\Models\MatchingSession::class,
        ]);

        // Allow unlisted morph types so existing DB rows (e.g. tokenable_type = 'App\Models\User') don't break
        \Illuminate\Database\Eloquent\Relations\Relation::requireMorphMap(false);
    }
}
