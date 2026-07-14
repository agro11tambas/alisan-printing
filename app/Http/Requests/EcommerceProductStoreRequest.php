<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class EcommerceProductStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $variantGroups = $this->input('variant_groups', []);
        $variantGroups = is_array($variantGroups) ? $variantGroups : [];

        foreach ($variantGroups as $groupIndex => &$group) {
            if (isset($group['options']) && is_array($group['options'])) {
                foreach ($group['options'] as &$option) {
                    $option['is_active'] = filter_var($option['is_active'] ?? false, FILTER_VALIDATE_BOOLEAN);
                }
            }
        }

        $variantCombinations = $this->input('variant_combinations', []);
        $variantCombinations = is_array($variantCombinations) ? $variantCombinations : [];
        foreach ($variantCombinations as &$combination) {
            $combination['is_active'] = filter_var($combination['is_active'] ?? false, FILTER_VALIDATE_BOOLEAN);
        }

        $this->merge([
            'is_active' => filter_var($this->input('is_active', false), FILTER_VALIDATE_BOOLEAN),
            'slug' => Str::slug($this->input('slug') ?: $this->input('title')),
            'multiple_qty' => $this->normalizeNumber($this->input('multiple_qty', 1)) ?? 1,
            'min_qty' => $this->normalizeNumber($this->input('min_qty', 1)) ?? 1,
            'max_qty' => $this->normalizeNumber($this->input('max_qty')),
            'sort_order' => 0,
            'variant_groups' => $variantGroups,
            'variant_combinations' => $variantCombinations,
        ]);
    }

    public function rules(): array
    {
        return [
            'category_ids' => ['required', 'array', 'min:1'],
            'category_ids.*' => ['exists:ecommerce_product_categories,id'],
            'unit_id' => ['required', 'exists:product_units,id'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:ecommerce_products,slug'],
            'brand' => ['nullable', 'string', 'max:255'],
            'main_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:4096'],
            'gallery_images' => ['nullable', 'array'],
            'gallery_images.*' => ['image', 'mimes:jpeg,png,jpg,gif,webp', 'max:4096'],
            'main_video' => ['nullable', 'file', 'mimes:mp4,mov,avi,webm,mkv', 'max:51200'],
            'description' => ['nullable', 'string'],
            'multiple_qty' => ['required', 'integer', 'min:1'],
            'min_qty' => ['required', 'integer', 'min:1'],
            'max_qty' => ['required', 'integer', 'min:1', 'gte:min_qty'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],

            'variant_groups' => ['required', 'array', 'min:1'],
            'variant_groups.*.name' => ['required', 'string', 'max:255'],
            'variant_groups.*.options' => ['required', 'array', 'min:1'],
            'variant_groups.*.options.*.alias' => ['required', 'string', 'max:255'],
            'variant_groups.*.options.*.product_id' => ['required', 'exists:products,id'],
            'variant_groups.*.options.*.image' => ['nullable', 'image', 'max:4096'],
            'variant_groups.*.options.*.is_active' => ['nullable', 'boolean'],

            'variant_combinations' => ['nullable', 'array'],
            'variant_combinations.*.product_option_product_id' => ['nullable', 'integer'],
            'variant_combinations.*.lid_option_product_id' => ['nullable', 'integer'],
            'variant_combinations.*.image' => ['nullable', 'image', 'max:4096'],
            'variant_combinations.*.is_active' => ['nullable', 'boolean'],
        ];
    }

    private function normalizeNumber($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = preg_replace('/[^\d]/', '', (string) $value);

        return $normalized === '' ? null : $normalized;
    }

    public function messages(): array
    {
        return [
            'category_ids.required' => 'Category wajib dipilih.',
            'category_ids.array' => 'Format category tidak valid.',
            'category_ids.min' => 'Minimal satu Category wajib dipilih.',
            'unit_id.required' => 'Unit wajib dipilih.',
            'title.required' => 'Title wajib diisi.',
            'slug.required' => 'Slug wajib diisi.',
            'slug.unique' => 'Slug product sudah digunakan.',
            'main_image.image' => 'Main image harus berupa gambar.',
            'main_video.mimes' => 'Main video harus berformat mp4, mov, avi, webm, atau mkv.',
            'max_qty.required' => 'Maximum Qty wajib diisi.',
            'max_qty.gte' => 'Maximum Qty harus lebih besar atau sama dengan Minimum Qty.',
            'variant_groups.required' => 'Minimal satu Variant Group wajib dibuat.',
            'variant_groups.min' => 'Minimal satu Variant Group wajib dibuat.',
            'variant_groups.*.name.required' => 'Nama Variant Group wajib diisi.',
            'variant_groups.*.options.required' => 'Minimal satu Variant Option wajib dibuat.',
            'variant_groups.*.options.min' => 'Minimal satu Variant Option wajib dibuat.',
            'variant_groups.*.options.*.alias.required' => 'Alias Variant Option wajib diisi.',
            'variant_groups.*.options.*.product_id.required' => 'ERP Product wajib dipilih.',
            'variant_groups.*.options.*.product_id.exists' => 'ERP Product tidak valid.',
            'variant_groups.*.options.*.image.image' => 'Image option harus berupa file gambar.',
            'variant_combinations.*.image.image' => 'Image combination harus berupa file gambar.',
        ];
    }
}
