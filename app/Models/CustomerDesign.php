<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerDesign extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'customer_designs';

    protected $fillable = [
        'customer_id',
        'title',
        'notes',
        'images',
        'created_by',
    ];

    protected $casts = [
        'images' => 'array',
        'deleted_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customers::class, 'customer_id')->withTrashed();
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    /**
     * Daftar gambar yang sudah dipastikan berbentuk [{file, note}, ...].
     *
     * Kolomnya JSON bebas, jadi baris lama/rusak jangan sampai bikin
     * halaman error — yang tidak sesuai bentuk langsung dibuang.
     *
     * @return array<int, array{file: string, note: string}>
     */
    public function imageList(): array
    {
        $images = $this->images;

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
}
