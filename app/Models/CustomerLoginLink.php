<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerLoginLink extends Model
{
    use HasFactory;

    /**
     * Panjang kode base62 yang dipakai di URL /m/<kode>. Dipatok pendek supaya
     * link enak dikirim lewat WhatsApp; endpoint penebusannya wajib di-throttle.
     */
    public const CODE_LENGTH = 8;

    protected $fillable = [
        'customer_account_id',
        'code_hash',
        'single_use',
        'expires_at',
        'used_at',
        'used_ip',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'single_use' => 'boolean',
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    /**
     * 8 karakter base62 ≈ 47 bit. random_int, bukan rand/mt_rand/uniqid —
     * yang terakhir dapat diprediksi, dan di sini itu berarti tamu bisa
     * menebak link login orang lain.
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
}
