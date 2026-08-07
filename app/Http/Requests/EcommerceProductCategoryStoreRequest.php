<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class EcommerceProductCategoryStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Str::slug($this->input('slug') ?: $this->input('name')),
            'sort_order' => 0,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:ecommerce_product_categories,slug'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:4096'],
            'sort_order' => ['nullable', 'integer', 'min:0'],

            // Sub category yang dipilih dari category yang sudah ada
            'existing_child_ids' => ['nullable', 'array'],
            'existing_child_ids.*' => ['integer', 'exists:ecommerce_product_categories,id'],
            'existing_children' => ['nullable', 'array'],
            'existing_children.*.name' => ['nullable', 'string', 'max:255'],

            // Sub category baru yang diketik langsung di form main category
            'subcategories' => ['nullable', 'array'],
            'subcategories.*.name' => ['nullable', 'string', 'max:255'],
            'subcategories.*.description' => ['nullable', 'string'],
            'subcategories.*.image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:4096'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama category wajib diisi.',
            'slug.required' => 'Slug wajib diisi.',
            'slug.unique' => 'Slug category sudah digunakan.',
            'image.image' => 'File image harus berupa gambar.',
            'image.mimes' => 'Image harus berformat jpeg, png, jpg, gif, atau webp.',
            'image.max' => 'Ukuran image maksimal 4MB.',
            'sort_order.integer' => 'Sort order harus berupa angka.',
            'existing_child_ids.*.exists' => 'Sub category yang dipilih tidak ditemukan.',
            'subcategories.*.name.max' => 'Nama sub category maksimal 255 karakter.',
            'subcategories.*.image.image' => 'File image sub category harus berupa gambar.',
            'subcategories.*.image.mimes' => 'Image sub category harus berformat jpeg, png, jpg, gif, atau webp.',
            'subcategories.*.image.max' => 'Ukuran image sub category maksimal 4MB.',
        ];
    }
}
