<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Inquiry;
use App\Policies\InquiryPolicy;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Inquiry::class => InquiryPolicy::class,
    ];

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
        // Map polymorphic types for inquiries poster relationship and notifications
        // This prevents Laravel from trying to use full class names
        \Illuminate\Database\Eloquent\Relations\Relation::enforceMorphMap([
            'dealer' => \App\Models\Dealer::class,
            'converter' => \App\Models\Converter::class,
            'machine_dealer' => \App\Models\MachineDealer::class,
            'brand' => \App\Models\Brand::class,
            'inquiry' => \App\Models\Inquiry::class,
            'session' => \App\Models\MatchingSession::class,
        ]);
        
        // Handle morph map errors globally
        \Illuminate\Database\Eloquent\Relations\Relation::requireMorphMap(false);
    }
}
