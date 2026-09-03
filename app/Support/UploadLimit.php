<?php

namespace App\Support;

/**
 * Batas ukuran upload yang benar-benar berlaku di server ini.
 *
 * Aturan validasi sempat menulis max:10240 (10 MB) padahal PHP hanya menerima
 * upload_max_filesize 2 MB. File di antara kedua angka itu ditolak PHP lebih
 * dulu, sehingga Laravel hanya melihat upload gagal dan menolaknya dengan pesan
 * "file bukan gambar" — membingungkan dan tidak menyebut ukuran sama sekali.
 *
 * Kelas ini membaca limit PHP yang sesungguhnya supaya aturan dan pesan error
 * ikut menyesuaikan tiap server, tanpa perlu diubah manual saat konfigurasi
 * hosting berbeda.
 */
class UploadLimit
{
    /** Batas efektif dalam kilobyte, untuk dipakai pada rule `max:`. */
    public static function maxKilobytes(): int
    {
        $limits = array_filter([
            self::toBytes(ini_get('upload_max_filesize')),
            self::toBytes(ini_get('post_max_size')),
        ]);

        if ($limits === []) {
            return 2048;
        }

        // Sisakan sedikit ruang untuk field lain dalam satu POST.
        $bytes = min($limits) * 0.9;

        return max((int) floor($bytes / 1024), 256);
    }

    /** Rule validasi lengkap untuk sebuah field gambar opsional. */
    public static function imageRule(): string
    {
        return 'nullable|image|mimes:jpeg,png,jpg,gif|max:'.self::maxKilobytes();
    }

    /** Pesan yang menyebut angkanya, supaya user tahu harus mengecilkan berapa. */
    public static function imageMessages(string $field): array
    {
        $mb = round(self::maxKilobytes() / 1024, 1);

        return [
            // PHP menolak file yang melewati upload_max_filesize SEBELUM Laravel
            // sempat mengukurnya, jadi yang gagal adalah rule `uploaded`, bukan
            // `max`. Pesan bawaannya ("failed to upload") tidak menyebut ukuran
            // sama sekali sehingga user tidak tahu harus berbuat apa.
            $field.'.uploaded' => 'Foto gagal diunggah karena ukurannya melebihi batas server ('.$mb.' MB). Foto ulang atau kecilkan dulu fotonya.',
            $field.'.max' => 'Ukuran foto maksimal '.$mb.' MB. Foto dari kamera HP biasanya lebih besar, silakan foto ulang atau kecilkan dulu.',
            $field.'.image' => 'File yang diunggah bukan gambar, atau ukurannya melebihi batas server ('.$mb.' MB).',
            $field.'.mimes' => 'Format foto harus JPG, PNG, atau GIF.',
        ];
    }

    /** Ubah notasi ini PHP ("2M", "8M", "512K") jadi byte. */
    private static function toBytes(?string $value): int
    {
        $value = trim((string) $value);

        if ($value === '') {
            return 0;
        }

        $number = (float) $value;
        $unit = strtolower(substr($value, -1));

        return (int) match ($unit) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => $number,
        };
    }
}
