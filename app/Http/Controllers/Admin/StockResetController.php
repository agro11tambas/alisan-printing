<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StockResetController extends Controller
{
    public function index(): View
    {
        return view('erp.pages.stock-reset.index', [
            'productionStockCount' => DB::table('production_stocks')->count(),
            'inventoryStockCount' => DB::table('inventory_stocks')->count(),
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $request->validate([
            'confirmation' => ['accepted'],
        ], [
            'confirmation.accepted' => 'Konfirmasi reset stok wajib dicentang.',
        ]);

        DB::transaction(function (): void {
            DB::table('production_stocks')->update([
                'available_quantity' => 0,
            ]);

            DB::table('inventory_stocks')->update([
                'inventory_stock' => 0,
                'available_quantity' => 0,
            ]);
        });

        return redirect()
            ->route('stock-reset.index')
            ->with('success', 'Seluruh stok produksi dan inventory berhasil direset menjadi 0.');
    }
}
