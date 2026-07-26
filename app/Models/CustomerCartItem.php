<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerCartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_account_id',
        'cart_item_key',
        'quantity',
        'is_selected',
        'item_data',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'is_selected' => 'boolean',
            'item_data' => 'array',
        ];
    }

    public function customerAccount()
    {
        return $this->belongsTo(CustomerAccount::class);
    }
}
