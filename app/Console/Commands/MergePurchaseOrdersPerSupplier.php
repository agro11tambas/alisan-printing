<?php

namespace App\Console\Commands;

use App\Models\Purchase;
use App\Services\PurchaseNumberService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Satukan Purchase Order lama jadi satu PO per supplier.
 *
 * PO sekarang cuma wadah grouping: produk dan qty ada di Purchase List
 * anaknya. Data lama masih punya banyak PO per supplier, plus PL yang dibuat
 * tanpa induk. Perintah ini:
 *
 *  1. Memilih PO tertua tiap supplier sebagai PO utama.
 *  2. Memindahkan semua PL (termasuk yang sudah di-trash) dari PO lain milik
 *     supplier yang sama ke PO utama.
 *  3. Memindahkan PL tanpa induk (atau induknya sudah terhapus) ke PO utama
 *     supplier-nya. Supplier yang belum punya PO dibuatkan PO baru.
 *  4. Men-soft delete PO yang sudah kosong, lalu menghitung ulang status
 *     PO utama.
 *
 * Tanpa --execute perintah ini hanya menampilkan rencananya.
 */
class MergePurchaseOrdersPerSupplier extends Command
{
    protected $signature = 'purchase:merge-orders-per-supplier
        {--execute : Jalankan perubahan. Tanpa opsi ini hanya menampilkan rencana}';

    protected $description = 'Gabungkan Purchase Order lama jadi satu PO per supplier dan pindahkan Purchase List-nya';

    public function handle(): int
    {
        $execute = (bool) $this->option('execute');

        $orders = Purchase::where('status', 'Purchase Orders')
            ->orderBy('id')
            ->get(['id', 'purchase_number', 'purchase_date', 'supplier_id']);

        $withoutSupplier = $orders->whereNull('supplier_id');
        $ordersBySupplier = $orders->whereNotNull('supplier_id')->groupBy('supplier_id');
        $activeOrderIds = $orders->pluck('id')->all();

        // PL aktif yang tidak menempel ke PO aktif mana pun.
        $orphanLists = Purchase::where('status', 'Purchase List')
            ->where(fn ($query) => $query
                ->whereNull('parent_purchase_id')
                ->orWhereNotIn('parent_purchase_id', $activeOrderIds ?: [0])
            )
            ->orderBy('purchase_date')
            ->get(['id', 'purchase_number', 'purchase_date', 'supplier_id', 'parent_purchase_id']);

        $orphansWithoutSupplier = $orphanLists->whereNull('supplier_id');
        $orphansBySupplier = $orphanLists->whereNotNull('supplier_id')->groupBy('supplier_id');

        $supplierIds = $ordersBySupplier->keys()->merge($orphansBySupplier->keys())->unique()->values();
        $supplierNames = DB::table('suppliers')->whereIn('id', $supplierIds)->pluck('name', 'id');

        $plan = $supplierIds->map(function ($supplierId) use ($ordersBySupplier, $orphansBySupplier, $supplierNames) {
            $supplierOrders = $ordersBySupplier->get($supplierId, collect());

            return [
                'supplier_id' => (int) $supplierId,
                'supplier_name' => $supplierNames[$supplierId] ?? '#'.$supplierId,
                'main' => $supplierOrders->first(),
                'duplicates' => $supplierOrders->slice(1)->values(),
                'orphans' => $orphansBySupplier->get($supplierId, collect()),
            ];
        })->filter(fn ($row) => $row['duplicates']->isNotEmpty() || $row['orphans']->isNotEmpty())->values();

        $this->printPlan($plan);

        if ($withoutSupplier->isNotEmpty()) {
            $this->warn('PO tanpa supplier dilewati: '.$withoutSupplier->pluck('purchase_number')->implode(', '));
        }

        if ($orphansWithoutSupplier->isNotEmpty()) {
            $this->warn('PL tanpa supplier dilewati: '.$orphansWithoutSupplier->pluck('purchase_number')->implode(', '));
        }

        if ($plan->isEmpty()) {
            $this->info('Tidak ada yang perlu digabung. Setiap supplier sudah punya paling banyak satu PO.');

            return self::SUCCESS;
        }

        if (! $execute) {
            $this->line('');
            $this->warn('Mode simulasi: belum ada yang diubah. Jalankan ulang dengan --execute untuk menerapkan.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($plan) {
            foreach ($plan as $row) {
                $this->mergeSupplier($row);
            }
        });

        $this->info('Selesai. '.$plan->count().' supplier sudah digabung jadi satu PO.');

        return self::SUCCESS;
    }

    private function mergeSupplier(array $row): void
    {
        $main = $row['main']
            ? Purchase::lockForUpdate()->findOrFail($row['main']->id)
            : $this->createMainOrder($row);

        foreach ($row['duplicates'] as $duplicate) {
            // withTrashed: PL yang sudah dihapus ikut pindah supaya kalau
            // di-restore tetap muncul di PO yang benar.
            Purchase::withTrashed()
                ->where('parent_purchase_id', $duplicate->id)
                ->update(['parent_purchase_id' => $main->id]);

            $order = Purchase::findOrFail($duplicate->id);

            // PO lama normalnya hanya punya item PO. Kalau ternyata punya
            // transaksi akun / inventory sendiri, jangan dihapus diam-diam.
            if ($order->accountTransactions()->exists() || $order->inventories()->exists() || $order->purchaseReturn()->exists()) {
                $this->warn("PO {$order->purchase_number} punya transaksi/inventory sendiri, tidak dihapus. Cek manual.");

                continue;
            }

            $order->delete_notes ='Digabung ke '.$main->purchase_number.' (purchase:merge-orders-per-supplier)';
            $order->save();
            $order->delete();
        }

        if ($row['orphans']->isNotEmpty()) {
            Purchase::whereIn('id', $row['orphans']->pluck('id'))
                ->update(['parent_purchase_id' => $main->id]);
        }

        $main->refresh()->syncApprovalProgress();
    }

    private function createMainOrder(array $row): Purchase
    {
        // Tanggal PO mengikuti PL tertua supaya filter tanggal tetap masuk akal.
        $purchaseDate = $row['orphans']->min('purchase_date') ?? now();

        $order = Purchase::create([
            'purchase_number' => PurchaseNumberService::generate($purchaseDate),
            'purchase_date' => $purchaseDate,
            'supplier_id' => $row['supplier_id'],
            'status' => 'Purchase Orders',
            'approval_status' => 'Draft',
            'payment_status' => 'Pending',
            'sub_total' => 0,
            'total_amount' => 0,
            'tax_percent' => 0,
            'tax_amount' => 0,
            'total_amount_product' => 0,
            'total_amount_freight' => 0,
            'paid_amount' => 0,
            'remaining_amount' => 0,
            'stock_destination' => null,
            'notes' => 'Dibuat otomatis oleh purchase:merge-orders-per-supplier',
        ]);

        $this->line("PO baru {$order->purchase_number} dibuat untuk {$row['supplier_name']}.");

        return $order;
    }

    private function printPlan(Collection $plan): void
    {
        if ($plan->isEmpty()) {
            return;
        }

        $this->table(
            ['Supplier', 'PO utama', 'PO digabung', 'PL dari PO digabung', 'PL tanpa induk'],
            $plan->map(function (array $row) {
                $movedLists = $row['duplicates']->isEmpty()
                    ? 0
                    : Purchase::withTrashed()->whereIn('parent_purchase_id', $row['duplicates']->pluck('id'))->count();

                return [
                    mb_strimwidth($row['supplier_name'], 0, 30, '...'),
                    $row['main']->purchase_number ?? '(PO baru)',
                    $row['duplicates']->count()
                        ? $row['duplicates']->count().': '.mb_strimwidth($row['duplicates']->pluck('purchase_number')->implode(', '), 0, 60, '...')
                        : '-',
                    $movedLists,
                    $row['orphans']->count(),
                ];
            })->all()
        );
    }
}
