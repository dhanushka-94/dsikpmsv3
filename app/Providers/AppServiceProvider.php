<?php

/*
| DSI KPI Monitoring System
| Developed by olexto Digital Solutions - info@olexto.com - https://olexto.com
*/

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
        $timezone = (string) config('app.timezone', 'Asia/Colombo');

        date_default_timezone_set($timezone);
        Carbon::setLocale((string) config('app.locale', 'en'));

        Carbon::macro('dsiDate', fn () => $this->timezone(config('app.timezone'))->format('d M Y'));
        Carbon::macro('dsiDateTime', fn () => $this->timezone(config('app.timezone'))->format('d M Y h:i A'));
        Carbon::macro('dsiDateTimeSec', fn () => $this->timezone(config('app.timezone'))->format('d M Y h:i:s A'));
        Carbon::macro('dsiTime', fn (bool $withSeconds = false) => $this->timezone(config('app.timezone'))->format($withSeconds ? 'h:i:s A' : 'h:i A'));
        Carbon::macro('dsiDateTimeShort', fn () => $this->timezone(config('app.timezone'))->format('M j, g:i A'));
    }
}
