<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DesignItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'design_id',
        'order_item_id',
        'product_id',
        'quantity',
        'completed_quantity',
        'design_file',
        'preview_image',
        'verification_status',
        'verified_by',
        'verified_at',
        'note',
        'product_unit_conversion_id',
        'unit_name',
        'unit_conversion_value',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function design()
    {
        return $this->belongsTo(Design::class, 'design_id');
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }

    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id')->withTrashed();
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by')->withTrashed();
    }

    /**
     * Daftar preview yang sudah dipastikan berbentuk [{file, note}, ...].
     *
     * Kolom `preview_image` JSON bebas dan pernah diisi beberapa versi kode,
     * jadi baris lama/rusak dikembalikan sebagai kosong, bukan error.
     *
     * @return array<int, array{file: string, note: string}>
     */
    public function previewList(): array
    {
        $images = json_decode($this->preview_image ?? '[]', true);

        if (! is_array($images)) {
            return [];
        }

        $clean = [];

        foreach ($images as $image) {
            if (! is_array($image) || empty($image['file'])) {
                continue;
            }

            $clean[] = [
                'file' => (string) $image['file'],
                'note' => (string) ($image['note'] ?? ''),
            ];
        }

        return $clean;
    }

    /**
     * Gabungan catatan semua gambar preview (unik, dipisah " | "), atau null.
     *
     * Ini nilai yang disimpan ke kolom `note` supaya halaman lain cukup
     * membaca satu kolom teks tanpa mengurai JSON preview_image.
     */
    public function noteFromPreview(): ?string
    {
        $notes = collect($this->previewList())
            ->map(fn (array $image) => trim($image['note']))
            ->filter()
            ->unique()
            ->values();

        return $notes->isNotEmpty() ? $notes->implode(' | ') : null;
    }
}
