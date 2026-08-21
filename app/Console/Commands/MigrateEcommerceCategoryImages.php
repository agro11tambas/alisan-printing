<?php

namespace App\Console\Commands;

use App\Models\EcommerceProductCategory;
use App\Services\WebsiteRevalidator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MigrateEcommerceCategoryImages extends Command
{
    protected $signature = 'ecommerce:migrate-category-images';

    protected $description = 'Copy legacy ecommerce category images from storage into public uploads';

    public function handle(WebsiteRevalidator $websiteRevalidator): int
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

                $filename = basename(str_replace('\\', '/', $image));
                $newPath = 'ecommerce-categories/' . $filename;
                $destination = public_path('uploads/' . $newPath);

                if (File::exists($destination)) {
                    if ($category->image !== $newPath) {
                        $category->update(['image' => $newPath]);
                    }

                    $existing++;

                    return;
                }

                $source = collect([
                    public_path('uploads/' . $image),
                    storage_path('app/public/' . $image),
                ])->first(fn (string $path) => File::exists($path));

                if (! $source) {
                    $missing++;
                    $this->warn("Category {$category->id}: source image not found ({$image}).");

                    return;
                }

                File::ensureDirectoryExists(dirname($destination));
                File::copy($source, $destination);
                $category->update(['image' => $newPath]);
                $copied++;
            });

        $websiteRevalidator->categories();

        $this->info("Category images: {$copied} copied, {$existing} already public, {$missing} missing.");

        return self::SUCCESS;
    }
}
