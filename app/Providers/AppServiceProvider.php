<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
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
