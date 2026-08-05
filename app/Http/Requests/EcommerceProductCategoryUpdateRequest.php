<?php

namespace App\Http\Requests;

use App\Models\EcommerceProductCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class EcommerceProductCategoryUpdateRequest extends FormRequest
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
        $category = $this->route('category');
        $categoryId = is_object($category) ? $category->id : $category;

        // Sebuah category tidak boleh jadi child dari dirinya sendiri
        // maupun dari keturunannya (bikin siklus di tree).
        $forbiddenParentIds = $category instanceof EcommerceProductCategory
            ? $category->descendantIds()
            : (array) $categoryId;

        return [
            'parent_id' => [
                'nullable',
                'integer',
                'exists:ecommerce_product_categories,id',
                Rule::notIn($forbiddenParentIds),
            ],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('ecommerce_product_categories', 'slug')->ignore($categoryId),
            ],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:4096'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'parent_id.exists' => 'Parent category tidak ditemukan.',
            'parent_id.not_in' => 'Parent category tidak boleh category ini sendiri atau turunannya.',
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
