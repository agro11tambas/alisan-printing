<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CostSetting;
use App\Services\FifoCostService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Layar modul HPP FIFO.
 *
 * Dua halaman:
 *  - Batch Purchase : snapshot tiap batch stok masuk beserta harga modal dan
 *                     sisanya. Ini "buku besar" yang dipakai FIFO.
 *  - Rincian HPP    : tiap baris penjualan dan batch mana saja yang dimakan,
 *                     supaya angka di export Sale List bisa ditelusuri.
 */
class FifoCostController extends Controller
{
    private const PAGE_SIZE = 50;

    // =====================================================================
    // Batch Purchase (cost_layers)
    // =====================================================================

    public function layers()
    {
        return view('erp.pages.fifo-cost.cost-layers', [
            'startDate' => CostSetting::startDate()?->toDateString(),
        ]);
    }

    /**
     * Simpan tanggal mulai pembukuan FIFO, lalu hitung ulang.
     *
     * Dipakai setelah stok direset dan Opening Stock & Rate diisi ulang: tanpa
     * tanggal ini, batch dari riwayat stock in lama akan ditumpuk di atas
     * opening stock yang baru sehingga stoknya terhitung dua kali.
     */
    public function updateStartDate(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
        ], [
            'start_date.date' => 'Tanggal mulai pembukuan tidak valid.',
        ]);

        CostSetting::setStartDate($request->input('start_date'));

        try {
            app(FifoCostService::class)->rebuild();
        } catch (\Throwable $e) {
            Log::error('Rebuild FIFO setelah ubah tanggal mulai gagal: '.$e->getMessage());

            return back()->with('error', 'Tanggal tersimpan, tapi perhitungan ulang gagal: '.$e->getMessage());
        }

        $message = $request->filled('start_date')
            ? 'Pembukuan FIFO dimulai dari '.$request->input('start_date').'. Stock in dan penjualan sebelum tanggal itu tidak lagi dihitung.'
            : 'Tanggal mulai dikosongkan. Seluruh riwayat kembali dihitung.';

        return back()->with('success', $message);
    }

    public function dataLayers(Request $request)
    {
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', self::PAGE_SIZE);

        $query = DB::table('cost_layers as l')
            ->join('products as p', 'p.id', '=', 'l.product_id')
            ->select([
                'l.id',
                'p.name AS product_name',
                'p.sku AS sku',
                'l.reference',
                'l.source_type',
                'l.layer_date',
                'l.qty_in',
                'l.qty_remaining',
                'l.unit_cost',
            ]);

        if ($request->filled('product_name')) {
            $keyword = '%'.$request->input('product_name').'%';

            $query->where(function ($q) use ($keyword) {
                $q->where('p.name', 'like', $keyword)
                    ->orWhere('p.sku', 'like', $keyword)
                    ->orWhere('l.reference', 'like', $keyword);
            });
        }

        if ($request->filled('start_date')) {
            $query->whereDate('l.layer_date', '>=', $request->input('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->whereDate('l.layer_date', '<=', $request->input('end_date'));
        }

        // Default layar: hanya batch yang stoknya masih ada. Batch habis tetap
        // bisa dilihat karena itu jejak penilaian penjualan lama.
        if ($request->input('only_remaining') === '1') {
            $query->where('l.qty_remaining', '>', 0);
        }

        $total = (clone $query)->count();

        $rows = $query->orderByDesc('l.layer_date')
            ->orderByDesc('l.id')
            ->offset($start)
            ->limit($length)
            ->get()
            ->map(fn ($row) => [
                'product_name' => $row->product_name,
                'sku' => $row->sku ?: '-',
                'reference' => $row->reference ?: '-',
                'source' => $row->source_type === 'opening_stock' ? 'Opening Stock' : 'Stock In Purchase',
                'layer_date' => $row->layer_date ? date('d/m/Y', strtotime($row->layer_date)) : '-',
                'qty_in' => number_format((float) $row->qty_in, 0, ',', '.'),
                'qty_used' => number_format((float) $row->qty_in - (float) $row->qty_remaining, 0, ',', '.'),
                'qty_remaining' => number_format((float) $row->qty_remaining, 0, ',', '.'),
                'unit_cost' => number_format((float) $row->unit_cost, 2, ',', '.'),
                'remaining_value' => number_format((float) $row->qty_remaining * (float) $row->unit_cost, 0, ',', '.'),
            ]);

        return response()->json([
            'data' => $rows,
            'has_more' => $total > ($start + $length),
            'summary' => $this->layerSummary($request),
        ]);
    }

    /** Nilai persediaan menurut sisa batch, untuk kartu ringkasan di atas tabel. */
    private function layerSummary(Request $request): array
    {
        $query = DB::table('cost_layers as l')->join('products as p', 'p.id', '=', 'l.product_id');

        if ($request->filled('product_name')) {
            $keyword = '%'.$request->input('product_name').'%';

            $query->where(function ($q) use ($keyword) {
                $q->where('p.name', 'like', $keyword)
                    ->orWhere('p.sku', 'like', $keyword)
                    ->orWhere('l.reference', 'like', $keyword);
            });
        }

        $row = $query->selectRaw('COUNT(*) AS batches')
            ->selectRaw('SUM(qty_remaining) AS qty_remaining')
            ->selectRaw('SUM(qty_remaining * unit_cost) AS value_remaining')
            ->first();

        return [
            'batches' => number_format((int) $row->batches, 0, ',', '.'),
            'qty_remaining' => number_format((float) $row->qty_remaining, 0, ',', '.'),
            'value_remaining' => number_format((float) $row->value_remaining, 0, ',', '.'),
        ];
    }

    // =====================================================================
    // Rincian HPP penjualan (cost_consumptions)
    // =====================================================================

    public function consumptions()
    {
        return view('erp.pages.fifo-cost.cost-consumptions');
    }

    public function dataConsumptions(Request $request)
    {
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', self::PAGE_SIZE);

        $query = DB::table('cost_consumptions as c')
            ->join('orders as o', 'o.id', '=', 'c.order_id')
            ->join('products as p', 'p.id', '=', 'c.product_id')
            ->leftJoin('cost_layers as l', 'l.id', '=', 'c.cost_layer_id')
            ->whereNull('o.deleted_at')
            ->select([
                'c.id',
                'o.order_number',
                'o.order_date',
                'p.name AS product_name',
                'l.reference AS batch_reference',
                'l.layer_date AS batch_date',
                'c.qty',
                'c.unit_cost',
                'c.subtotal',
                'c.is_estimated',
                'c.is_defect',
                'c.sale_return_item_id',
            ]);

        if ($request->filled('keyword')) {
            $keyword = '%'.$request->input('keyword').'%';

            $query->where(function ($q) use ($keyword) {
                $q->where('o.order_number', 'like', $keyword)
                    ->orWhere('p.name', 'like', $keyword)
                    ->orWhere('l.reference', 'like', $keyword);
            });
        }

        if ($request->filled('start_date')) {
            $query->whereDate('o.order_date', '>=', $request->input('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->whereDate('o.order_date', '<=', $request->input('end_date'));
        }

        if ($request->input('only_estimated') === '1') {
            $query->where('c.is_estimated', 1);
        }

        $total = (clone $query)->count();

        // Terbaru dulu, seperti listing lain di aplikasi. Urutan kedua dan
        // ketiga menjaga baris-baris milik satu invoice tetap berdampingan dan
        // urut sesuai alokasi batch-nya — kalau hanya diurut tanggal, dua
        // invoice bertanggal sama bisa saling menyisip.
        $rows = $query->orderByDesc('o.order_date')
            ->orderByDesc('c.order_id')
            ->orderBy('c.id')
            ->offset($start)
            ->limit($length)
            ->get()
            ->map(function ($row) {
                $type = $row->sale_return_item_id ? 'Retur' : 'Jual';

                if ($row->is_defect) {
                    $type = 'Retur (rusak)';
                }

                return [
                    'order_number' => $row->order_number,
                    'order_date' => $row->order_date ? date('d/m/Y', strtotime($row->order_date)) : '-',
                    'product_name' => $row->product_name,
                    'type' => $type,
                    'batch' => $row->batch_reference
                        ? $row->batch_reference.' ('.date('d/m/Y', strtotime($row->batch_date)).')'
                        : 'Tanpa batch',
                    'qty' => number_format((float) $row->qty, 0, ',', '.'),
                    'unit_cost' => number_format((float) $row->unit_cost, 2, ',', '.'),
                    'subtotal' => number_format((float) $row->subtotal, 0, ',', '.'),
                    'is_estimated' => (bool) $row->is_estimated,
                ];
            });

        return response()->json([
            'data' => $rows,
            'has_more' => $total > ($start + $length),
        ]);
    }

    // =====================================================================
    // Rebuild manual
    // =====================================================================

    /**
     * Hitung ulang seluruh batch dan alokasinya.
     *
     * Normalnya tidak perlu ditekan: simpan/edit Sale List sudah menghitung
     * ulang produk yang terpengaruh, dan cron menjalankan rebuild penuh tiap
     * malam. Tombol ini untuk setelah perbaikan data purchase lama.
     */
    public function rebuild()
    {
        try {
            $stats = app(FifoCostService::class)->rebuild();
        } catch (\Throwable $e) {
            Log::error('Rebuild FIFO gagal: '.$e->getMessage());

            return back()->with('error', 'Gagal menghitung ulang HPP: '.$e->getMessage());
        }

        $message = 'HPP FIFO dihitung ulang: '.number_format($stats['layers']).' batch, '
            .number_format($stats['order_items']).' baris penjualan.';

        if ($stats['estimated_items'] > 0) {
            $message .= ' '.number_format($stats['estimated_items']).' baris memakai harga taksiran.';
        }

        return back()->with('success', $message);
    }
}
