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
        // Map polymorphic types for inquiries poster relationship
        // This prevents Laravel from trying to use full class names
        \Illuminate\Database\Eloquent\Relations\Relation::enforceMorphMap([
            'dealer' => \App\Models\Dealer::class,
            'converter' => \App\Models\Converter::class,
            'machine_dealer' => \App\Models\MachineDealer::class,
            'brand' => \App\Models\Brand::class,
        ], false); // false = don't throw exception if type not found
    }
}
