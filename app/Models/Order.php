<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'status_edited',
        'customer_id',
        'order_number',
        'order_date',
        'due_date',
        'total_amount',
        'status',
        'payment_method',
        'payment_status',
        'discount',
        'grand_total',
        'paid_amount',
        'remaining_amount',
        'shipping_address',
        'google_maps',
        'notes',
        'delivery_image',
        'transaction_group_id',
        'deleted_by',
        'deleted_notes',
    ];

    protected $casts = [
        'order_date' => 'datetime',
        'due_date' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customers::class, 'customer_id');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class)->withTrashed();
    }

    public function saleReturns()
    {
        return $this->hasMany(SaleReturn::class, 'sale_order_id');
    }

    public function deliveries()
    {
        return $this->hasMany(Delivery::class);
    }

    public function Inventories()
    {
        return $this->hasMany(Inventory::class, 'order_id');
    }

    public function orderProgress()
    {
        return $this->hasMany(OrderProgress::class);
    }

    public function accountTransactions()
    {
        return $this->hasMany(AccountTransaction::class, 'order_id', 'id');
    }

    public function orderEditHistories()
    {
        return $this->hasMany(OrderEditHistory::class, 'order_id');
    }

    public function deletedByUser()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function deliveryOrders()
    {
        return $this->hasMany(DeliveryOrder::class, 'order_id');
    }

    public function canceledProducts()
    {
        return $this->hasMany(CanceledProduct::class, 'order_id');
    }

    // Optional: total completed quantity across all items
    // public function getCompletedQuantityAttribute(): int
    // {
    //     return $this->items->sum('completed_quantity');
    // }

    // public function getTotalQuantityAttribute(): int
    // {
    //     return $this->items->sum('quantity');
    // }

    // public function getProgressPercentAttribute(): float
    // {
    //     return $this->total_quantity > 0
    //         ? round(($this->completed_quantity / $this->total_quantity) * 100, 2)
    //         : 0;
    // }

    public function updatePaymentStatus()
    {
        if ($this->paid_amount >= $this->grand_total) {
            // Lunas atau lebih
            $this->payment_status = $this->paid_amount > $this->grand_total ? 'Overpaid' : 'Paid';
        } else {
            // Belum lunas
            if ($this->due_date && \Carbon\Carbon::parse($this->due_date)->isPast()) {
                // Kalau due date sudah lewat → Overdue
                $this->payment_status = 'Overdue';
            } else {
                // Belum lewat due date → bisa unpaid/partially paid
                $this->payment_status = $this->paid_amount > 0 ? 'Partially Paid' : 'Unpaid';
            }
        }

        $this->save();
    }

    public function hasStockOut()
    {
        return InventoryItem::whereHas('inventory', function ($q) {
            $q->where('order_id', $this->id)
                ->where('status', 'Stock Out');
        })->where('stock_out', '>', 0)->exists();
    }

    public function getIsFullyReturnedAttribute()
    {
        // total qty order
        $totalOrdered = $this->orderItems()->sum('quantity');

        // total qty yang sudah direturn
        $totalReturned = \App\Models\SaleReturnItem::whereHas('saleReturn', function ($q) {
            $q->where('sale_order_id', $this->id);
        })->sum('quantity');

        return $totalOrdered > 0 && $totalOrdered <= $totalReturned;
    }

    public function getHasDeliveryListAttribute(): bool
    {
        return $this->deliveryOrders()
            ->whereHas('shipments', function ($q) {
                $q->where('status', 'Finished');
            })
            ->exists();
    }

    protected static function booted()
    {
        static::deleting(function ($order) {
            if ($order->isForceDeleting()) {
                // pakai each supaya event anak terpanggil
                $order->orderItems()->withTrashed()->get()->each->forceDelete();
                $order->orderProgress()->withTrashed()->get()->each->forceDelete();
                $order->deliveryOrders()->withTrashed()->get()->each->forceDelete();

                $order->orderEditHistories()->forceDelete();
                $order->saleReturns()->forceDelete();
                $order->accountTransactions()->forceDelete();
                // $order->canceledProducts()->forceDelete();

                $order->Inventories()
                    ->whereNull('canceled_product_id') // hanya inventory normal
                    ->get()
                    ->each
                    ->forceDelete();
            } else {
                $order->orderItems()->get()->each->delete();
                $order->orderProgress()->get()->each->delete();
                $order->deliveryOrders()->get()->each->delete();

                $order->orderEditHistories()->delete();
                $order->saleReturns()->delete();
                $order->accountTransactions()->delete();
                // $order->canceledProducts()->delete();

                $order->Inventories()
                    ->whereNull('canceled_product_id') // hanya inventory normal
                    ->get()
                    ->each
                    ->delete();
            }
        });

        static::restoring(function ($order) {
            $order->orderItems()->withTrashed()->get()->each->restore();
            $order->orderProgress()->withTrashed()->get()->each->restore();
            $order->deliveryOrders()->withTrashed()->get()->each->restore();

            $order->orderEditHistories()->withTrashed()->restore();
            $order->saleReturns()->withTrashed()->restore();
            $order->accountTransactions()->withTrashed()->restore();
            $order->canceledProducts()
                ->where('type', 'from_order_delete')
                ->forceDelete();
        });
    }
}
