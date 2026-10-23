<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerLoginLink extends Model
{
    use HasFactory;

    /**
     * Panjang kode base62 yang dipakai di URL /m/<kode>. 16 karakter, bukan 8:
     * link ini permanen, jadi penebak punya waktu tak terbatas dan peluangnya
     * dikali jumlah link aktif. 62^16 ≈ 4,8 × 10^28 menutup perhitungan itu,
     * dan URL-nya masih pendek untuk WhatsApp.
     */
    public const CODE_LENGTH = 16;

    protected $fillable = [
        'customer_account_id',
        'code_hash',
        'single_use',
        'expires_at',
        'used_at',
        'used_ip',
        'use_count',
        'revoked_at',
        'revoked_by',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'single_use' => 'boolean',
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
            'revoked_at' => 'datetime',
            'use_count' => 'integer',
        ];
    }

    /**
     * expires_at null = tidak pernah kedaluwarsa.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isUsedUp(): bool
    {
        return $this->single_use && $this->used_at !== null;
    }

    /**
     * Link masih bisa dipakai customer untuk masuk. Selama ini true, admin
     * harus mencabutnya dulu sebelum boleh membuat link baru.
     */
    public function isUsable(): bool
    {
        return ! $this->isRevoked() && ! $this->isExpired() && ! $this->isUsedUp();
    }

    /**
     * Urutan cek sama dengan CustomerAuthController::redeemLoginLink(), supaya
     * status di ERP sama dengan alasan penolakan yang dilihat customer.
     */
    public function status(): string
    {
        return match (true) {
            $this->isRevoked() => 'revoked',
            $this->isExpired() => 'expired',
            $this->isUsedUp() => 'used',
            default => 'active',
        };
    }

    public function scopeActive($query)
    {
        return $query->whereNull('revoked_at');
    }

    /**
     * random_int, bukan rand/mt_rand/uniqid — yang terakhir dapat diprediksi,
     * dan di sini itu berarti tamu bisa menebak link login orang lain.
     */
    public static function generateCode(): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $code = '';

        for ($i = 0; $i < self::CODE_LENGTH; $i++) {
            $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $code;
    }

    public static function hashCode(string $code): string
    {
        return hash('sha256', $code);
    }

    public function customerAccount()
    {
        return $this->belongsTo(CustomerAccount::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function revoker()
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }
}
