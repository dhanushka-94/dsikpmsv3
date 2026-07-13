<?php

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

if (! function_exists('app_version')) {
    function app_version(): string
    {
        return app(\App\Services\SoftwareVersion::class)->current();
    }
}

if (! function_exists('dsi_datetime')) {
    /**
     * Display date + time with AM/PM.
     */
    function dsi_datetime(mixed $value, string $fallback = '—'): string
    {
        $date = dsi_parse_datetime($value);

        return $date?->format('Y-m-d h:i A') ?? $fallback;
    }
}

if (! function_exists('dsi_datetimesec')) {
    /**
     * Display date + time (with seconds) with AM/PM.
     */
    function dsi_datetimesec(mixed $value, string $fallback = '—'): string
    {
        $date = dsi_parse_datetime($value);

        return $date?->format('Y-m-d h:i:s A') ?? $fallback;
    }
}

if (! function_exists('dsi_time')) {
    /**
     * Display time with AM/PM.
     */
    function dsi_time(mixed $value, bool $withSeconds = false, string $fallback = '—'): string
    {
        $date = dsi_parse_datetime($value);

        if (! $date) {
            return $fallback;
        }

        return $date->format($withSeconds ? 'h:i:s A' : 'h:i A');
    }
}

if (! function_exists('dsi_datetime_short')) {
    /**
     * Short display like "Jul 13, 10:59 PM".
     */
    function dsi_datetime_short(mixed $value, string $fallback = '—'): string
    {
        $date = dsi_parse_datetime($value);

        return $date?->format('M j, g:i A') ?? $fallback;
    }
}

if (! function_exists('dsi_parse_datetime')) {
    function dsi_parse_datetime(mixed $value): ?CarbonInterface
    {
        if ($value instanceof CarbonInterface) {
            return $value;
        }

        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }
}
