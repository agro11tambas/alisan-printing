<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use Carbon\Carbon;

class UpdateOverdueOrders extends Command
{
    protected $signature = 'orders:update-overdue';
    protected $description = 'Update payment status orders yang sudah lewat due date';

    // public function handle()
    // {
    //     $today = Carbon::today();

    //     // ambil semua unpaid & partially paid yang sudah lewat due date
    //     $orders = Order::whereIn('payment_status', ['Unpaid', 'Partially Paid'])
    //         ->whereDate('due_date', '<', $today)
    //         ->get();

    //     foreach ($orders as $order) {
    //         $order->payment_status = 'Overdue';
    //         $order->save();
    //     }

    //     $this->info("Updated {$orders->count()} overdue orders.");
    // }
}
