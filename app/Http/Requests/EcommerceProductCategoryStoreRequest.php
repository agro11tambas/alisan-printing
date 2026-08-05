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
            'parent_id' => $this->input('parent_id') ?: null,
        ]);
    }

    public function rules(): array
    {
        return [
            'parent_id' => ['nullable', 'integer', 'exists:ecommerce_product_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:ecommerce_product_categories,slug'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:4096'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'parent_id.exists' => 'Parent category tidak ditemukan.',
            'name.required' => 'Nama category wajib diisi.',
            'slug.required' => 'Slug wajib diisi.',
            'slug.unique' => 'Slug category sudah digunakan.',
            'image.image' => 'File image harus berupa gambar.',
            'image.mimes' => 'Image harus berformat jpeg, png, jpg, gif, atau webp.',
            'image.max' => 'Ukuran image maksimal 4MB.',
            'sort_order.integer' => 'Sort order harus berupa angka.',
        ];
    }
}
