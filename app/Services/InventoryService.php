<?php

namespace App\Services;

use App\Models\OrderItem;

class InventoryService
{
    public function allocateStock(OrderItem $orderItem, $requestQty)
    {
        if ($orderItem->product_id) {
            // Order item adalah produk tunggal
            $this->allocateSingleProduct($orderItem, $requestQty);
        }

        if ($orderItem->product_bundle_id) {
            // Order item adalah bundle
            $this->allocateBundle($orderItem, $requestQty);
        }
    }

    protected function allocateSingleProduct(OrderItem $orderItem, $requestQty)
    {
        $remainingQty = $requestQty;

        $inventoryItems = $orderItem->inventoryItems()
            ->where('remaining_stock_in', '>', 0)
            ->orderBy('id', 'asc') // FIFO
            ->get();

        foreach ($inventoryItems as $inventory) {
            if ($remainingQty <= 0) break;

            $take = min($inventory->remaining_stock_in, $remainingQty);

            $inventory->remaining_stock_in -= $take;
            $inventory->stock_out += $take;
            $inventory->save();

            $inventory->inventoryStockOut()->create([
                'order_item_id' => $orderItem->id,
                'quantity'      => $take,
            ]);

            $remainingQty -= $take;
        }

        if ($remainingQty > 0) {
            throw new \Exception("Stok tidak cukup untuk produk {$orderItem->product->name}. Kurang {$remainingQty}");
        }
    }

    protected function allocateBundle(OrderItem $orderItem, $requestQty)
    {
        $bundle = $orderItem->productBundle()->with('items.product')->first();

        foreach ($bundle->items as $bundleItem) {
            // qty yg dibutuhkan = qty order × qty bundle item
            $neededQty = $requestQty * $bundleItem->quantity;

            // Cari orderItem "bayangan" buat tiap produk bundle
            $fakeOrderItem = new OrderItem([
                'id'        => $orderItem->id, // biar tetap terhubung
                'product_id' => $bundleItem->product_id,
            ]);

            // Hubungkan manual relasi ke inventoryItems
            $fakeOrderItem->setRelation(
                'inventoryItems',
                $bundleItem->product->inventoryItems()
                    ->where('remaining_stock_in', '>', 0)
                    ->orderBy('id', 'asc')
                    ->get()
            );

            // Alokasikan stok seperti produk biasa
            $this->allocateSingleProduct($fakeOrderItem, $neededQty);
        }
    }
}
