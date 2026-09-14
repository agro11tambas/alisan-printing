<?php

namespace App\Console\Commands;

use App\Models\DesignItem;
use Illuminate\Console\Command;

/**
 * Isi kolom `design_items.note` dari catatan gambar di JSON `preview_image`.
 *
 * Sebelum perubahan ini catatan design hanya tersimpan per gambar di dalam
 * `preview_image` ([{file, note}, ...]) dan kolom `note` tidak pernah diisi.
 * Sekarang controller mengisinya setiap preview berubah; command ini untuk
 * mengisi data lama sekali jalan supaya Assign List cukup membaca kolom `note`.
 *
 * Default-nya hanya melaporkan. Tambahkan --apply untuk benar-benar menulis.
 * Baris yang `note`-nya sudah sama tidak disentuh, jadi aman dijalankan ulang.
 */
class SyncDesignItemNotes extends Command
{
    protected $signature = 'design:sync-item-notes
        {--apply : Tulis perubahan ke database (tanpa ini hanya laporan)}
        {--force : Timpa juga note yang sudah terisi tapi berbeda dari preview}';

    protected $description = 'Salin catatan gambar dari preview_image ke kolom note design_items';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $force = (bool) $this->option('force');

        if (! $apply) {
            $this->warn('MODE LAPORAN. Tidak ada yang ditulis. Tambahkan --apply untuk menerapkan.');
        }

        $updated = 0;
        $skippedSame = 0;
        $skippedFilled = 0;
        $skippedEmpty = 0;

        // termasuk yang soft-deleted: history assign masih bisa menunjuk ke sana
        DesignItem::withTrashed()
            ->whereNotNull('preview_image')
            ->select(['id', 'preview_image', 'note'])
            ->orderBy('id')
            ->chunkById(500, function ($items) use ($apply, $force, &$updated, &$skippedSame, &$skippedFilled, &$skippedEmpty) {
                foreach ($items as $item) {
                    $fromPreview = $item->noteFromPreview();
                    $current = trim((string) $item->note) ?: null;

                    if ($fromPreview === null) {
                        $skippedEmpty++;
                        continue;
                    }

                    if ($current === $fromPreview) {
                        $skippedSame++;
                        continue;
                    }

                    // note yang sudah diisi manual jangan ditimpa diam-diam
                    if ($current !== null && ! $force) {
                        $skippedFilled++;
                        $this->line("  #{$item->id} dilewati, note sudah terisi: \"{$current}\" (preview: \"{$fromPreview}\")");
                        continue;
                    }

                    $updated++;

                    if ($apply) {
                        // update langsung tanpa event/timestamp biar updated_at tidak berubah
                        DesignItem::withTrashed()->whereKey($item->id)->toBase()->update(['note' => $fromPreview]);
                    } elseif ($this->getOutput()->isVerbose()) {
                        $this->line("  #{$item->id}: \"{$fromPreview}\"");
                    }
                }
            });

        $this->newLine();
        $this->info(($apply ? 'Diperbarui' : 'Akan diperbarui') . ": {$updated}");
        $this->line("Sudah sama: {$skippedSame}");
        $this->line("Preview tanpa catatan: {$skippedEmpty}");

        if ($skippedFilled > 0) {
            $this->warn("Note sudah terisi & berbeda (dilewati): {$skippedFilled}. Pakai --force untuk menimpa.");
        }

        if (! $apply && $updated > 0) {
            $this->warn('Jalankan ulang dengan --apply untuk menerapkan.');
        }

        return self::SUCCESS;
    }
}
