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
        'quantity_verified_at',
        'quantity_verified_by',
        'image',
        'waybill_image',
        'invoice_image',
        'supplier_waybill_number',
        'expedition_waybill_number',
        'expedition_waybill_image',
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
        'quantity_verified_at' => 'datetime',
    ];

    /**
     * Label approval_status yang dipakai di layar. Nilai di database sengaja
     * dibiarkan apa adanya supaya data lama tetap terbaca.
     *
     * PO sekarang cuma wadah grouping per supplier (tanpa produk), jadi yang
     * dipakai tinggal Draft (belum ada PL), Completed PL (ada PL yang masih
     * berjalan), dan Completed. Approved/Partial hanya ada di data lama.
     */
    public const APPROVAL_STATUS_LABELS = [
        'Draft' => 'Belum ada PL',
        'Approved' => 'Belum ada PL',
        'Partial' => 'Purchase List',
        'Completed PL' => 'Purchase List',
        'Completed' => 'Completed',
    ];

    /**
     * "Completed PL" menutupi dua keadaan yang jauh berbeda: barangnya memang
     * belum masuk semua, atau barangnya sudah lengkap tapi PL-nya belum lunas.
     * Keadaan kedua dikasih label sendiri supaya di layar tidak terbaca seperti
     * Stock In-nya yang belum beres.
     */
    public const LABEL_AWAITING_PAYMENT = 'Lengkap · Belum Lunas';

    /**
     * PO dianggap masih berjalan selama belum semua Purchase List-nya stock in.
     */
    public const APPROVAL_PROGRESS_STATUSES = ['Draft', 'Approved', 'Partial', 'Completed PL'];

    public const APPROVAL_COMPLETED_STATUSES = ['Completed'];

    public function getApprovalStatusLabelAttribute(): string
    {
        return $this->approvalStatusLabel();
    }

    /**
     * Sama dengan accessor di atas, tapi pemanggil yang sudah punya hasil
     * hitungan (mis. listing yang mengagregat di SQL) bisa mengopernya supaya
     * tidak ada query tambahan per baris.
     */
    public function approvalStatusLabel(?bool $stockInCompleted = null, ?bool $fullyPaid = null): string
    {
        if ($this->isAwaitingPurchaseListPayment($stockInCompleted, $fullyPaid)) {
            return self::LABEL_AWAITING_PAYMENT;
        }

        $status = $this->approval_status ?: 'Draft';

        return self::APPROVAL_STATUS_LABELS[$status] ?? $status;
    }

    /**
     * Barangnya sudah masuk semua, yang menahan status Completed tinggal
     * pembayaran PL. Syaratnya dibuat persis sama dengan cabang 'Completed'
     * di syncApprovalProgress().
     */
    public function isAwaitingPurchaseListPayment(?bool $stockInCompleted = null, ?bool $fullyPaid = null): bool
    {
        if (($this->approval_status ?: 'Draft') !== 'Completed PL') {
            return false;
        }

        return ($stockInCompleted ?? $this->purchaseListItemsFullyStockedIn())
            && ! ($fullyPaid ?? $this->purchaseListsFullyPaid());
    }

    /**
     * Seluruh item Purchase List anak sudah stock in penuh.
     */
    public function purchaseListItemsFullyStockedIn(): bool
    {
        $listItems = $this->purchaseListItemsQuery()->with('inventoryItems')->get();

        return $listItems->isNotEmpty()
            && $listItems->every(fn (PurchaseItem $item) => $item->isFullyStockedIn());
    }

    /**
     * Item milik seluruh Purchase List anak PO ini. PO tidak lagi punya item
     * sendiri, jadi hubungannya lewat parent_purchase_id, bukan
     * source_purchase_item_id.
     */
    public function purchaseListItemsQuery()
    {
        return PurchaseItem::whereIn(
            'purchase_id',
            self::where('parent_purchase_id', $this->id)->select('id')
        );
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id')->withTrashed();
    }

    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class, 'purchase_id');
    }

    /**
     * Inventory item milik seluruh item purchase ini. Dipakai listing PO untuk
     * menjumlahkan realisasi Stock In tiap PL anak lewat satu withSum.
     */
    public function purchaseInventoryItems()
    {
        return $this->hasManyThrough(
            InventoryItem::class,
            PurchaseItem::class,
            'purchase_id',      // FK di purchase_items -> purchases
            'purchase_item_id', // FK di inventory_items_2 -> purchase_items
            'id',
            'id'
        );
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

    public function quantityVerifiedBy()
    {
        return $this->belongsTo(User::class, 'quantity_verified_by')->withTrashed();
    }

    /**
     * Quantity Purchase List sudah diverifikasi. Tanggalnya sekaligus jadi
     * penandanya, supaya tidak ada flag terpisah yang bisa tidak sinkron.
     */
    public function isQuantityVerified(): bool
    {
        return $this->quantity_verified_at !== null;
    }

    /** Verifikasi quantity hanya berlaku untuk Purchase List turunan PO. */
    public function canVerifyQuantity(): bool
    {
        return $this->status === 'Purchase List'
            && $this->parent_purchase_id !== null
            && ! $this->isQuantityVerified();
    }

    /**
     * Hitung ulang approval_status PO dari Purchase List anaknya:
     * Draft (belum ada PL) -> Completed PL (ada PL yang belum beres) ->
     * Completed (semua PL sudah stock in penuh DAN lunas). Karena PO adalah
     * wadah permanen per supplier, status bisa kembali ke Completed PL begitu
     * ada PL baru.
     */
    public function syncApprovalProgress(): void
    {
        if ($this->status !== 'Purchase Orders') {
            return;
        }

        $listItems = $this->purchaseListItemsQuery()->with('inventoryItems')->get();

        $status = match (true) {
            $listItems->isEmpty() => 'Draft',
            $listItems->every(fn (PurchaseItem $item) => $item->isFullyStockedIn())
                && $this->purchaseListsFullyPaid() => 'Completed',
            default => 'Completed PL',
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
