<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        require_once app_path('Support/helpers.php');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Carbon::macro('dsiDateTime', fn () => $this->format('Y-m-d h:i A'));
        Carbon::macro('dsiDateTimeSec', fn () => $this->format('Y-m-d h:i:s A'));
        Carbon::macro('dsiTime', fn (bool $withSeconds = false) => $this->format($withSeconds ? 'h:i:s A' : 'h:i A'));
        Carbon::macro('dsiDateTimeShort', fn () => $this->format('M j, g:i A'));
    }
}
