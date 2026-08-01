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
        'customer_account_id',
        'customer_address_id',
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
        'business_name',
        'shipping_address',
        'google_maps',
        'mode',
        'verified',
        'notes',
        'delivery_image',
        'transaction_group_id',
        'deleted_by',
        'deleted_notes',
        'discount_active',
        'user_id',
    ];

    protected $casts = [
        'order_date' => 'datetime',
        'due_date' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customers::class, 'customer_id')->withTrashed();
    }

    public function customerAddress(): BelongsTo
    {
        return $this->belongsTo(CustomerAddresses::class, 'customer_address_id')->withTrashed();
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
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
        return $this->belongsTo(User::class, 'deleted_by')->withTrashed();
    }

    public function deliveryOrders()
    {
        return $this->hasMany(DeliveryOrder::class, 'order_id');
    }

    public function canceledProducts()
    {
        return $this->hasMany(CanceledProduct::class, 'order_id');
    }

    public function designs()
    {
        return $this->hasMany(Design::class, 'order_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function customerAccount()
    {
        return $this->belongsTo(CustomerAccount::class, 'customer_account_id');
    }

    public function getOrderWhatsappNumberAttribute(): ?string
    {
        return $this->customerAccount?->whatsapp_number
            ?: $this->customer?->phone;
    }

    // public function updatePaymentStatus()
    // {
    //     if ($this->paid_amount >= $this->grand_total) {
    //         // Lunas atau lebih
    //         $this->payment_status = $this->paid_amount > $this->grand_total ? 'Overpaid' : 'Paid';
    //     } else {
    //         // Belum lunas
    //         if ($this->due_date && \Carbon\Carbon::parse($this->due_date)->isPast()) {
    //             // Kalau due date sudah lewat → Overdue
    //             $this->payment_status = 'Overdue';
    //         } else {
    //             // Belum lewat due date → bisa unpaid/partially paid
    //             $this->payment_status = $this->paid_amount > 0 ? 'Partially Paid' : 'Unpaid';
    //         }
    //     }

    //     $this->save();
    // }

    public function updatePaymentStatus()
    {
        if ($this->paid_amount >= $this->grand_total) {
            // Lunas atau lebih
            $this->payment_status = $this->paid_amount > $this->grand_total
                ? 'Overpaid'
                : 'Paid';
        } else {
            // Belum lunas
            $this->payment_status = $this->paid_amount > 0
                ? 'Partially Paid'
                : 'Unpaid';
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
                $order->orderItems()->get()->each->forceDelete();

                $order->orderProgress()->get()->each(function ($progress) {
                    $progress->items()->get()->each->forceDelete();
                    $progress->forceDelete();
                });

                $order->deliveryOrders()->get()->each(function ($deliveryOrder) {
                    $deliveryOrder->items()->get()->each->forceDelete();
                    $deliveryOrder->forceDelete();
                });

                $order->designs()->get()->each(function ($design) {
                    $design->items()->get()->each->forceDelete();
                    $design->forceDelete();
                });

                $order->orderEditHistories()->forceDelete();
                $order->saleReturns()->get()->each->forceDelete();
                $order->accountTransactions()->forceDelete();

                $order->Inventories()
                    ->whereNull('canceled_product_id')
                    ->get()
                    ->each
                    ->forceDelete();
            } else {
                $order->orderItems()->get()->each->delete();

                $order->orderProgress()->get()->each(function ($progress) {
                    $progress->items()->get()->each->delete();
                    $progress->delete();
                });

                $order->deliveryOrders()->get()->each(function ($deliveryOrder) {
                    $deliveryOrder->items()->get()->each->delete();
                    $deliveryOrder->delete();
                });

                $order->designs()->get()->each(function ($design) {
                    $design->items()->get()->each->delete();
                    $design->delete();
                });

                $order->orderEditHistories()->delete();
                $order->saleReturns()->get()->each->delete();
                $order->accountTransactions()->delete();

                $order->Inventories()
                    ->whereNull('canceled_product_id')
                    ->get()
                    ->each
                    ->delete();
            }
        });

        static::restoring(function ($order) {
            $order->orderItems()->withTrashed()->get()->each->restore();

            $order->orderProgress()->withTrashed()->get()->each(function ($progress) {
                $progress->items()->withTrashed()->get()->each->restore();
                $progress->restore();
            });

            $order->deliveryOrders()->withTrashed()->get()->each(function ($deliveryOrder) {
                $deliveryOrder->items()->withTrashed()->get()->each->restore();
                $deliveryOrder->restore();
            });

            $order->designs()->withTrashed()->get()->each(function ($design) {
                $design->items()->withTrashed()->get()->each->restore();
                $design->restore();
            });

            $order->orderEditHistories()->withTrashed()->restore();
            $order->saleReturns()->withTrashed()->get()->each->restore();
            $order->accountTransactions()->withTrashed()->restore();

            $order->canceledProducts()
                ->where('type', 'from_order_delete')
                ->forceDelete();
        });
    }
}
