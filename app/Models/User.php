<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Permission;
use App\Models\PermissionSubItem;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Cache;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

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
        return in_array($slug, $this->permissionSlugs(), true);
    }

    /**
     * Check sub permission
     */
    public function hasSubPermission(string $slug): bool
    {
        return in_array($slug, $this->subPermissionSlugs(), true);
    }

    public function forgetPermissionCache(): void
    {
        Cache::forget($this->permissionCacheKey('permissions'));
        Cache::forget($this->permissionCacheKey('sub-permissions'));
    }

    private function permissionSlugs(): array
    {
        if ($this->relationLoaded('permissions')) {
            return $this->permissions->pluck('slug')->all();
        }

        return Cache::remember(
            $this->permissionCacheKey('permissions'),
            now()->addMinutes(10),
            fn () => $this->permissions()->pluck('permissions.slug')->all()
        );
    }

    private function subPermissionSlugs(): array
    {
        if ($this->relationLoaded('permissionSubItems')) {
            return $this->permissionSubItems->pluck('slug')->all();
        }

        return Cache::remember(
            $this->permissionCacheKey('sub-permissions'),
            now()->addMinutes(10),
            fn () => $this->permissionSubItems()->pluck('permission_sub_items.slug')->all()
        );
    }

    private function permissionCacheKey(string $type): string
    {
        return "user:{$this->getKey()}:{$type}";
    }

    public function defectProducts()
    {
        return $this->hasMany(DefectProduct::class, 'user_id');
    }
}
