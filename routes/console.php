<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('orders:update-overdue')->daily();
Schedule::command('stock:snapshot')->dailyAt('00:00');
Schedule::command('stock:snapshot')->dailyAt('23:59');
