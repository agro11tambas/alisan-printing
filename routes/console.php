<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Katalog dihangatkan dari cron supaya tidak ada request web yang membangunnya.
// withoutOverlapping: satu rebuild bisa makan menit-menitan, jangan sampai dua
// cron menumpuk dan malah menghabiskan proses yang mau dihemat.
Schedule::command('catalog:warm')->everyFiveMinutes()->withoutOverlapping();

Schedule::command('orders:update-overdue')->daily();
Schedule::command('stock:snapshot')->dailyAt('00:00');
Schedule::command('stock:snapshot')->dailyAt('23:59');

// Harga modal FIFO dihitung ulang dari nol tiap malam: satu purchase yang
// diedit atau dibackdate mengubah alokasi seluruh penjualan sesudahnya, jadi
// menghitung ulang lebih bisa dipercaya daripada menambal alokasi lama.
Schedule::command('cost:rebuild-fifo')->dailyAt('01:00')->withoutOverlapping();
