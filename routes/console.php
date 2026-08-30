<?php

/*
| DSI KPI Monitoring System
| Developed by olexto Digital Solutions - info@olexto.com - https://olexto.com
*/

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
