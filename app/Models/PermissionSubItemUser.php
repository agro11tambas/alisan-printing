<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PermissionSubItemUser extends Model
{
    protected $table = 'permission_sub_item_users';
    protected $fillable = ['user_id', 'permission_sub_item_id'];
}
