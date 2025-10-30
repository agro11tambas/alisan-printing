<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inventory extends Model
{
    use HasFactory;

    use SoftDeletes;

    protected $table = 'inventories_2';

    protected $fillable = [
        'purchase_id',
        'purchase_return_id',
        'order_id',
        'sale_return_id',
        'material_request_id',
        'production_stock_id',
        'canceled_product_id',
        'reject_product_id',
        'material_receipt_id',
        'purchase_number',
        'order_number',
        'material_request_number',
        'date',
        'waybill_number',
        'waybill_image',
        'status',
        'note',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(InventoryItem::class)->withTrashed();
    }

    public function stockIns()
    {
        return $this->hasMany(InventoryStockIn::class);
    }

    public function stockOuts()
    {
        return $this->hasMany(InventoryStockOut::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function purchaseReturn()
    {
        return $this->belongsTo(PurchaseReturn::class);
    }

    public function saleReturn()
    {
        return $this->belongsTo(SaleReturn::class);
    }

    public function materialRequest()
    {
        return $this->belongsTo(MaterialRequest::class, 'material_request_id')->withTrashed();
    }

    public function materialReceipt()
    {
        return $this->belongsTo(MaterialReceipt::class, 'material_receipt_id');
    }

    public function productionStock()
    {
        return $this->belongsTo(ProductionStock::class, 'production_stock_id');
    }

    public function canceledProduct()
    {
        return $this->belongsTo(CanceledProduct::class, 'canceled_product_id');
    }

    public function rejectProduct()
    {
        return $this->belongsTo(RejectProduct::class, 'reject_product_id');
    }

    public function latestStockOutVerification()
    {
        return $this->hasOne(InventoryStockOut::class, 'inventory_id')
            ->latest('verified_at'); // atau kolom waktu yang relevan
    }
}
