<?php

namespace App\Console\Commands;

use App\Models\EcommerceProductCategory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MigrateEcommerceCategoryImages extends Command
{
    protected $signature = 'ecommerce:migrate-category-images';

    protected $description = 'Copy legacy ecommerce category images from storage into public uploads';

    public function handle(): int
    {
        $copied = 0;
        $existing = 0;
        $missing = 0;

        EcommerceProductCategory::withTrashed()
            ->whereNotNull('image')
            ->select(['id', 'image'])
            ->cursor()
            ->each(function (EcommerceProductCategory $category) use (&$copied, &$existing, &$missing): void {
                $image = trim((string) $category->image);

                if ($image === '' || str_starts_with($image, 'http')) {
                    return;
                }

                $destination = public_path('uploads/' . $image);

                if (File::exists($destination)) {
                    $existing++;

                    return;
                }

                $source = storage_path('app/public/' . $image);

                if (! File::exists($source)) {
                    $missing++;
                    $this->warn("Category {$category->id}: source image not found ({$image}).");

                    return;
                }

                File::ensureDirectoryExists(dirname($destination));
                File::copy($source, $destination);
                $copied++;
            });

        $this->info("Category images: {$copied} copied, {$existing} already public, {$missing} missing.");

        return self::SUCCESS;
    }
}
