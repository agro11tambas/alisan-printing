<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class PermissionUser extends Pivot
{
    protected $table = 'permission_users';
    protected $fillable = ['user_id', 'permission_id'];
}
