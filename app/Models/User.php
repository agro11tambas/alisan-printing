<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Permission;
use App\Models\PermissionSubItem;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Parent permissions (main modules)
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'permission_users')
            ->withTimestamps();
    }

    /**
     * Sub permissions (sub menu items)
     */
    public function permissionSubItems()
    {
        return $this->belongsToMany(PermissionSubItem::class, 'permission_sub_item_users')
            ->withTimestamps();
    }

    /**
     * Check parent permission
     */
    public function hasPermission(string $slug): bool
    {
        return $this->permissions->contains('slug', $slug);
    }

    /**
     * Check sub permission
     */
    public function hasSubPermission(string $slug): bool
    {
        return $this->permissionSubItems->contains('slug', $slug);
    }

    public function defectProducts()
    {
        return $this->hasMany(DefectProduct::class, 'user_id');
    }
}
