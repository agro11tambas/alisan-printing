<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Purchase extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'purchases';

    protected $fillable = [
        'parent_purchase_id',
        'approval_status',
        'status_edited',
        'purchase_number',
        'purchase_date',
        'due_date',
        'sub_total',
        'tax_percent',
        'tax_amount',
        'total_amount',
        'notes',
        'payment_status',
        'payment_method',
        'paid_amount',
        'remaining_amount',
        'total_amount_product',
        'paid_amount_product',
        'remaining_amount_product',
        'total_amount_freight',
        'paid_amount_freight',
        'remaining_amount_freight',
        'status',
        'verified',
        'image',
        'waybill_image',
        'supplier_id',
        'transaction_group_id',
        'transaction_type',
        'deleted_by',
        'deleted_notes',
        'user_id',
        'stock_destination',
    ];

    protected $casts = [
        'purchase_date' => 'datetime',
        'due_date' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Label approval_status yang dipakai di layar. Nilai di database sengaja
     * dibiarkan apa adanya supaya data lama tetap terbaca.
     */
    public const APPROVAL_STATUS_LABELS = [
        'Draft' => 'Draft',
        'Approved' => 'Verify',
        'Partial' => 'Partial',
        'Completed PL' => 'Purchase List',
        'Completed' => 'Completed',
    ];

    /**
     * PO dianggap masih berjalan selama belum semua Purchase List-nya stock in.
     */
    public const APPROVAL_PROGRESS_STATUSES = ['Draft', 'Approved', 'Partial', 'Completed PL'];

    public const APPROVAL_COMPLETED_STATUSES = ['Completed'];

    public function getApprovalStatusLabelAttribute(): string
    {
        $status = $this->approval_status ?: 'Draft';

        return self::APPROVAL_STATUS_LABELS[$status] ?? $status;
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id')->withTrashed();
    }

    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class, 'purchase_id');
    }

    public function parentPurchase()
    {
        return $this->belongsTo(self::class, 'parent_purchase_id');
    }

    public function purchaseLists()
    {
        return $this->hasMany(self::class, 'parent_purchase_id');
    }

    public function purchaseReturn()
    {
        return $this->hasMany(PurchaseReturn::class, 'purchase_id');
    }

    public function accountTransactions()
    {
        return $this->hasMany(AccountTransaction::class, 'purchase_id', 'id');
    }

    public function purchaseAccount()
    {
        return $this->belongsTo(Account::class, 'transaction_type');
    }

    public function purchaseEditHistories()
    {
        return $this->hasMany(PurchaseEditHistory::class, 'purchase_id');
    }

    public function inventories()
    {
        return $this->hasMany(Inventory::class, 'purchase_id');
    }

    public function deletedByUser()
    {
        return $this->belongsTo(User::class, 'deleted_by')->withTrashed();
    }

    public function defectProducts()
    {
        return $this->hasMany(DefectProduct::class, 'purchase_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    /**
     * Qty PL yang sudah dibuat dari tiap item PO ini (key: purchase_item_id PO).
     * Dipakai untuk menghitung progress PO sekaligus mengunci qty saat PO diedit.
     */
    public function allocatedQuantityPerItem()
    {
        $itemIds = $this->purchaseItems()->pluck('id');

        if ($itemIds->isEmpty()) {
            return collect();
        }

        return PurchaseItem::whereIn('source_purchase_item_id', $itemIds)
            ->selectRaw('source_purchase_item_id, SUM(quantity) as allocated')
            ->groupBy('source_purchase_item_id')
            ->pluck('allocated', 'source_purchase_item_id')
            ->map(fn ($allocated) => (float) $allocated);
    }

    /**
     * Hitung ulang approval_status PO mengikuti progress PL, Stock In, dan
     * pembayaran: Draft -> Approved (sudah verify, belum ada PL) -> Partial
     * (sebagian qty sudah dibuatkan PL) -> Completed PL (semua qty sudah
     * dibuatkan PL) -> Completed (semua PL sudah stock in penuh DAN lunas).
     */
    public function syncApprovalProgress(): void
    {
        if ($this->status !== 'Purchase Orders' || ($this->approval_status ?? 'Draft') === 'Draft') {
            return;
        }

        $items = $this->purchaseItems()->get();
        $ordered = (float) $items->sum('quantity');

        $listItems = PurchaseItem::with('inventoryItems')
            ->whereIn('source_purchase_item_id', $items->pluck('id'))
            ->get();
        $allocated = (float) $listItems->sum('quantity');

        $status = match (true) {
            $allocated <= 0 => 'Approved',
            $ordered <= 0 || $allocated < $ordered => 'Partial',
            default => $listItems->every(fn (PurchaseItem $item) => $item->isFullyStockedIn())
                && $this->purchaseListsFullyPaid()
                    ? 'Completed'
                    : 'Completed PL',
        };

        if (($this->approval_status ?? null) !== $status) {
            $this->update(['approval_status' => $status]);
        }
    }

    /**
     * Seluruh Purchase List anak sudah lunas (produk + freight).
     * PL bernilai nol dianggap lunas karena tidak ada yang perlu dibayar.
     */
    public function purchaseListsFullyPaid(): bool
    {
        $lists = $this->purchaseLists()->get(['id', 'payment_status', 'total_amount']);

        return $lists->isNotEmpty() && $lists->every(
            fn ($list) => in_array($list->payment_status, ['Paid', 'Overpaid'], true)
                || (float) $list->total_amount <= 0
        );
    }

    /**
     * Sinkronkan status PO induk dari sekumpulan purchase item milik PL.
     * Dipakai setelah Stock In mengubah realisasi barang.
     */
    public static function syncApprovalProgressFromPurchaseItems($purchaseItemIds): void
    {
        $purchaseItemIds = collect($purchaseItemIds)->filter()->unique();

        if ($purchaseItemIds->isEmpty()) {
            return;
        }

        $parentIds = self::whereIn('id', PurchaseItem::whereIn('id', $purchaseItemIds)->pluck('purchase_id'))
            ->whereNotNull('parent_purchase_id')
            ->pluck('parent_purchase_id')
            ->unique();

        self::whereIn('id', $parentIds)->get()->each->syncApprovalProgress();
    }

    public function hasStockIn()
    {
        return $this->inventories()
            ->where('status', 'Stock In')
            ->whereHas('items', function ($q) {
                $q->where('stock_in', '>', 0);
            })
            ->exists();
    }

    public function getIsFullyReturnedAttribute(): bool
    {
        $totalPurchased = $this->relationLoaded('purchaseItems')
            ? (float) $this->purchaseItems->sum('quantity')
            : (float) $this->purchaseItems()->sum('quantity');

        $totalReturned = $this->relationLoaded('purchaseReturn')
            ? (float) $this->purchaseReturn
                ->flatMap(fn ($return) => $return->relationLoaded('items') ? $return->items : $return->items()->get())
                ->sum('quantity')
            : (float) PurchaseReturnItem::whereHas('purchaseReturn', function ($query) {
                $query->where('purchase_id', $this->id);
            })->sum('quantity');

        return $totalPurchased > 0 && $totalPurchased <= $totalReturned;
    }

    public function hasInventoryStockIn()
    {
        return $this->inventories()
            ->whereHas('stockIns')
            ->exists();
    }

    public function firstInventoryForStockIn()
    {
        return $this->inventories()
            ->whereHas('stockIns')
            ->first();
    }

    protected static function booted()
    {
        static::deleting(function ($purchase) {
            if ($purchase->isForceDeleting()) {
                $purchase->purchaseItems()->forceDelete();
                $purchase->purchaseReturn()->forceDelete();
                $purchase->purchaseEditHistories()->forceDelete();
                $purchase->accountTransactions()->forceDelete();
                $purchase->inventories()->forceDelete();
                $purchase->defectProducts()->forceDelete();
            } else {
                $purchase->purchaseItems()->delete();
                $purchase->purchaseReturn()->delete();
                $purchase->purchaseEditHistories()->delete();
                $purchase->accountTransactions()->delete();
                $purchase->inventories()->delete();
                $purchase->defectProducts()->delete();
            }
        });

        static::restoring(function ($purchase) {
            $purchase->purchaseItems()->withTrashed()->restore();
            $purchase->purchaseReturn()->withTrashed()->restore();
            $purchase->purchaseEditHistories()->withTrashed()->restore();
            $purchase->accountTransactions()->withTrashed()->restore();
            $purchase->inventories()->withTrashed()->restore();
            $purchase->defectProducts()->withTrashed()->restore();
        });
    }
}
