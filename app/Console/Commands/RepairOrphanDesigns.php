<?php

namespace App\Console\Commands;

use App\Models\DeliveryOrder;
use App\Models\Design;
use App\Models\DesignItem;
use App\Models\Order;
use App\Models\OrderProgress;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Perbaiki order yang nyangkut akibat pengecekan mode yang di-hardcode
 * di markAsSaleList dan DesignController::verify.
 *
 * Dua gejalanya:
 *   A. Order sudah Sale List tapi tidak punya Design sama sekali, karena
 *      mode-nya bukan 'printing' / 'polosan' (misal 'sablon').
 *   B. Design dibuat langsung status Verified tapi OrderProgress-nya tidak
 *      pernah dibuat, jadi hilang dari modul Design (filter default Pending)
 *      sekaligus hilang dari Waiting List (yang membaca OrderProgress).
 *
 * Default-nya hanya melaporkan. Tambahkan --apply untuk benar-benar menulis.
 */
class RepairOrphanDesigns extends Command
{
    protected $signature = 'design:repair-orphans {--apply : Tulis perubahan ke database (tanpa ini hanya laporan)}';

    protected $description = 'Cari dan perbaiki order/design yang nyangkut karena pengecekan mode hardcode';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        if (! $apply) {
            $this->warn('MODE LAPORAN. Tidak ada yang ditulis. Tambahkan --apply untuk memperbaiki.');
        }

        $this->newLine();
        $countA = $this->handleMissingDesigns($apply);
        $this->newLine();
        $countB = $this->handleStuckVerified($apply);

        $this->newLine();
        $this->info("Ringkasan: kasus A = {$countA}, kasus B = {$countB}.");

        if (! $apply && ($countA + $countB) > 0) {
            $this->warn('Jalankan ulang dengan --apply untuk menerapkan.');
        }

        return self::SUCCESS;
    }

    /**
     * KASUS A: order Sale List yang sama sekali belum punya Design.
     */
    private function handleMissingDesigns(bool $apply): int
    {
        $orders = Order::where('status', 'Sale List')
            ->whereDoesntHave('designs')
            ->with(['orderItems.priceMode', 'orderItems.productBundle.items.product'])
            ->get()
            ->filter(fn (Order $order) => $order->orderItems->contains(
                fn ($item) => $item->usesProductionFlow()
            ));

        $this->line('== KASUS A: order Sale List tanpa Design ==');

        if ($orders->isEmpty()) {
            $this->line('  (tidak ada)');

            return 0;
        }

        $rows = [];
        $repaired = 0;

        foreach ($orders as $order) {
            $itemCount = 0;

            if ($apply) {
                DB::transaction(function () use ($order, &$itemCount) {
                    $design = Design::create([
                        'order_id' => $order->id,
                        'design_number' => $order->order_number,
                        'date' => now()->format('Y-m-d'),
                        'status' => 'Pending',
                        'notes' => null,
                        'verification_status' => 'pending',
                    ]);

                    $itemCount = $this->createDesignItems($design, $order);
                });

                $repaired++;
            } else {
                $itemCount = $this->countExpectedDesignItems($order);
            }

            $rows[] = [$order->id, $order->order_number, $order->mode, $itemCount];
        }

        $this->table(['Order ID', 'Order Number', 'Mode', 'Design Item'], $rows);
        $this->info($apply
            ? "  {$repaired} Design dibuat dengan status Pending."
            : '  '.count($rows).' order akan dibuatkan Design status Pending.');

        return count($rows);
    }

    /**
     * KASUS B: Design berstatus Verified tapi tidak pernah masuk produksi.
     *
     * Design yang sudah punya OrderProgress atau DeliveryOrder TIDAK disentuh,
     * karena itu berarti sudah diverifikasi manusia dan berjalan normal.
     */
    private function handleStuckVerified(bool $apply): int
    {
        $designs = Design::where('status', 'Verified')
            ->whereNotIn('id', OrderProgress::query()->select('design_id')->whereNotNull('design_id'))
            ->whereNotIn('id', DeliveryOrder::query()->select('design_id')->whereNotNull('design_id'))
            ->with(['items.orderItem.priceMode'])
            ->get()
            ->filter(fn (Design $design) => $design->items->isNotEmpty()
                && $design->items->every(fn ($item) => $item->orderItem?->usesProductionFlow() === true));

        $this->line('== KASUS B: Design Verified tapi tidak pernah masuk produksi ==');

        if ($designs->isEmpty()) {
            $this->line('  (tidak ada)');

            return 0;
        }

        $rows = [];

        foreach ($designs as $design) {
            if ($apply) {
                DB::transaction(function () use ($design) {
                    $design->update([
                        'status' => 'Pending',
                        'verification_status' => 'pending',
                        'verified_by' => null,
                        'verified_at' => null,
                    ]);

                    DesignItem::where('design_id', $design->id)
                        ->update(['verification_status' => 'pending']);
                });
            }

            $rows[] = [
                $design->id,
                $design->order_id,
                $design->design_number,
                $design->items->count(),
                $design->created_at?->format('Y-m-d H:i'),
            ];
        }

        $this->table(['Design ID', 'Order ID', 'Design Number', 'Item', 'Dibuat'], $rows);
        $this->info($apply
            ? '  '.count($rows).' Design dikembalikan ke Pending, siap diverifikasi ulang.'
            : '  '.count($rows).' Design akan dikembalikan ke Pending.');

        return count($rows);
    }

    /**
     * Mirror dari pembuatan DesignItem di SaleOrderController::markAsSaleList.
     */
    private function createDesignItems(Design $design, Order $order): int
    {
        $created = 0;

        foreach ($order->orderItems as $orderItem) {
            if (! $orderItem->usesProductionFlow()) {
                continue;
            }

            $unitData = [
                'product_unit_conversion_id' => $orderItem->satuan === 'satuan'
                    ? $orderItem->product_unit_conversion_id
                    : $orderItem->product_bundle_unit_conversion_id,
                'unit_name' => $orderItem->unit_name,
                'unit_conversion_value' => $orderItem->unit_conversion_value,
            ];

            $base = [
                'design_id' => $design->id,
                'order_item_id' => $orderItem->id,
                'completed_quantity' => 0,
                'design_file' => null,
                'preview_image' => null,
                'verification_status' => 'pending',
            ];

            if ($orderItem->satuan === 'satuan') {
                if (! $orderItem->product_id) {
                    continue;
                }

                DesignItem::create(array_merge($base, $unitData, [
                    'product_id' => $orderItem->product_id,
                    'quantity' => $orderItem->quantity,
                ]));

                $created++;

                continue;
            }

            if ($orderItem->satuan === 'bundle' && $orderItem->productBundle) {
                foreach ($orderItem->productBundle->items as $bundleItem) {
                    if (! $bundleItem->product) {
                        continue;
                    }

                    DesignItem::create(array_merge($base, $unitData, [
                        'product_id' => $bundleItem->product->id,
                        'quantity' => $orderItem->quantity * ($bundleItem->quantity ?? 1),
                    ]));

                    $created++;
                }
            }
        }

        return $created;
    }

    private function countExpectedDesignItems(Order $order): int
    {
        $count = 0;

        foreach ($order->orderItems as $orderItem) {
            if (! $orderItem->usesProductionFlow()) {
                continue;
            }

            if ($orderItem->satuan === 'satuan') {
                $count += $orderItem->product_id ? 1 : 0;

                continue;
            }

            if ($orderItem->satuan === 'bundle' && $orderItem->productBundle) {
                $count += $orderItem->productBundle->items
                    ->filter(fn ($bundleItem) => (bool) $bundleItem->product)
                    ->count();
            }
        }

        return $count;
    }
}
