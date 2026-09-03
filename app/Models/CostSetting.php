<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Setelan modul HPP FIFO.
 */
class CostSetting extends Model
{
    protected $table = 'cost_settings';

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['key', 'value'];

    /**
     * Tanggal mulai pembukuan FIFO.
     *
     * Dipakai saat stok direset lalu Opening Stock & Rate diisi ulang: opening
     * stock dianggap kondisi PADA tanggal ini, sehingga seluruh stock in dan
     * penjualan sebelum tanggal ini diabaikan. Tanpa batas ini, batch lama dari
     * riwayat stock in akan ditumpuk di atas opening stock yang baru dan stok
     * FIFO-nya jadi dobel.
     */
    public const START_DATE = 'fifo_start_date';

    public static function startDate(): ?Carbon
    {
        $value = static::where('key', self::START_DATE)->value('value');

        return $value ? Carbon::parse($value)->startOfDay() : null;
    }

    public static function setStartDate(?string $date): void
    {
        static::updateOrCreate(
            ['key' => self::START_DATE],
            ['value' => $date ?: null]
        );
    }
}
