<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PermissionSubItem extends Model
{
    use HasFactory;

    protected $table = 'permission_sub_items';

    protected $fillable = [
        'permission_id',
        'name',
        'slug',
    ];

    public function parent()
    {
        return $this->belongsTo(Permission::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'permission_sub_item_users')
            ->withTimestamps();
    }
}
