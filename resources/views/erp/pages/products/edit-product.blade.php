@extends('erp.layouts.main')

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Products</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Products</li>
                <li class="breadcrumb-item">Edit Products</li>
            </ul>
        </div>
        <div class="page-header-right ms-auto">
            <div class="page-header-right-items">
                <div class="d-flex d-md-none">
                    <a href="javascript:void(0)" class="page-header-right-close-toggle">
                        <i class="feather-arrow-left me-2"></i><span>Back</span>
                    </a>
                </div>
                <div class="d-flex align-items-center gap-2 page-header-right-items-wrapper">
                    <a href="/erp/products" class="btn btn-light-brand">
                        <i class="feather-arrow-left me-2"></i>
                        <span>Back</span>
                    </a>
                    <button type="submit" class="btn btn-primary" form="productForm">
                        <i class="feather-plus me-2"></i>
                        <span>Edit Product</span>
                    </button>
                </div>
            </div>
            <div class="d-md-none d-flex align-items-center">
                <a href="javascript:void(0)" class="page-header-right-open-toggle">
                    <i class="feather-align-right fs-20"></i>
                </a>
            </div>
        </div>
    </div>
@endsection

@section('content')
    @if (session('error'))
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: "{{ session('error') }}",
            });
        </script>
    @endif
    <div class="main-content">
        <div class="row">
            <div class="col-lg-12">
                <div class="card stretch stretch-full">
                    <form action="/erp/products/update/{{ $product->id }}" method="POST" id="productForm"
                        enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <div class="card-body">
                            <div class="row mb-3 align-items-center">
                                <div class="col-lg-2">
                                    <label for="image" class="fw-semibold">Upload Image</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <div class="input-group">
                                        <input type="file" class="form-control" id="image" name="image"
                                            accept="image/*">
                                    </div>
                                    @if ($product->image)
                                        @php
                                            $imageUrl = asset($product->image);
                                        @endphp
                                        <div class="mt-2" id="old-image-container">
                                            <img src="{{ $imageUrl }}" alt="Old Image" id="old-image"
                                                style="max-width: 100px; border-radius: 10px">
                                        </div>
                                    @endif

                                    <div class="mt-2" id="new-image-container" style="display: none;">
                                        <img id="preview-image" src="#" alt="Preview"
                                            style="max-width: 100px; border-radius: 10px">
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-3 align-items-center">
                                <div class="col-lg-2">
                                    <label for="name" class="fw-semibold">Name:</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="name" name="name"
                                            value="{{ old('name', $product->name) }}" placeholder="Name">
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-3 align-items-center">
                                <div class="col-lg-2">
                                    <label for="sku" class="fw-semibold">SKU</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="sku" name="sku"
                                            value="{{ old('sku', $product->sku) }}" placeholder="SKU">
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-3 align-items-center">
                                <div class="col-lg-2">
                                    <label for="stock" class="fw-semibold">Stock</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <div class="input-group">
                                        <select class="form-control" data-select2-selector="status" id="stock"
                                            name="stock">
                                            <option value="instock" data-bg="bg-success"
                                                {{ $product->stock == 'instock' ? 'selected' : '' }}>In Stock</option>
                                            <option value="outofstock" data-bg="bg-warning"
                                                {{ $product->stock == 'outofstock' ? 'selected' : '' }}>Out Of Stock
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-3 align-items-center">
                                <div class="col-lg-2">
                                    <label for="categories" class="fw-semibold">Categories</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <div class="input-group">
                                        @php
                                            $bgColors = [
                                                'bg-danger',
                                                'bg-warning',
                                                'bg-primary',
                                                'bg-indigo',
                                                'bg-success',
                                            ];
                                        @endphp

                                        <select class="form-select form-control max-select" data-select2-selector="tag"
                                            multiple id="categories" name="categories[]">
                                            @foreach ($categories as $index => $category)
                                                @php
                                                    $bg = $bgColors[$index % count($bgColors)];
                                                    $selected = in_array(
                                                        $category->id,
                                                        old(
                                                            'categories',
                                                            $product->categories->pluck('id')->toArray() ?? [],
                                                        ),
                                                    )
                                                        ? 'selected'
                                                        : '';
                                                @endphp
                                                <option value="{{ $category->id }}" data-bg="{{ $bg }}"
                                                    {{ $selected }}>
                                                    {{ $category->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-3 align-items-center">
                                <div class="col-lg-2">
                                    <label for="tags" class="fw-semibold">Merek</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <div class="input-group">
                                        @php
                                            $bgColors = ['bg-primary', 'bg-success', 'bg-teal', 'bg-indigo', 'bg-info'];
                                        @endphp

                                        <select class="form-select form-control max-select" data-select2-selector="tag"
                                            multiple id="tags" name="tags[]">
                                            @foreach ($tags as $index => $tag)
                                                @php
                                                    $bg = $bgColors[$index % count($bgColors)];
                                                    $selected = in_array(
                                                        $tag->id,
                                                        old('tags', $product->tags->pluck('id')->toArray() ?? []),
                                                    )
                                                        ? 'selected'
                                                        : '';
                                                @endphp
                                                <option value="{{ $tag->id }}" data-bg="{{ $bg }}"
                                                    {{ $selected }}>
                                                    {{ $tag->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-3 align-items-center">
                                <div class="col-lg-2">
                                    <label for="price" class="fw-semibold">Price</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="price" name="price"
                                            value="{{ old('price', intval($product->price ?? 0)) }}" placeholder="0">
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-3 align-items-center">
                                <div class="col-lg-2">
                                    <label for="fixed_cost" class="fw-semibold">Fixed Cost</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="fixed_cost" name="fixed_cost"
                                            value="{{ old('fixed_cost', intval($product->fixed_cost ?? 0)) }}"
                                            placeholder="0">
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-3 align-items-center">
                                <div class="col-lg-2">
                                    <label for="description" class="fw-semibold">Description:</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <div class="input-group">
                                        <textarea name="description" id="description" cols="30" rows="5" placeholder="Description"
                                            class="form-control" value="{{ old('description') }}">{{ strip_tags(old('description', $product->description)) }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener("DOMContentLoaded", () => {

            const imageInput = document.getElementById('image');
            if (imageInput) {
                imageInput.addEventListener('change', function(event) {
                    const file = event.target.files[0];
                    const preview = document.getElementById('preview-image');
                    const previewContainer = document.getElementById('new-image-container');
                    const oldImageContainer = document.getElementById('old-image-container');

                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            preview.src = e.target.result;
                            previewContainer.style.display = 'block';
                            if (oldImageContainer) oldImageContainer.style.display = 'none';
                        };
                        reader.readAsDataURL(file);
                    } else {
                        previewContainer.style.display = 'none';
                        if (oldImageContainer) oldImageContainer.style.display = 'block';
                    }
                });
            }

            const form = document.getElementById('productForm');

            ['price', 'fixed_cost'].forEach(id => {
                const input = document.getElementById(id);
                if (!input) return;

                if (input.value) {
                    let clean = input.value.replace(/[^\d]/g, '');
                    input.value = new Intl.NumberFormat('id-ID').format(clean);
                    input.dataset.raw = clean;
                }

                input.addEventListener('input', function() {
                    let clean = this.value.replace(/[^\d]/g, '');
                    if (clean === '') {
                        this.value = '';
                        delete this.dataset.raw;
                        return;
                    }
                    this.value = new Intl.NumberFormat('id-ID').format(clean);
                    this.dataset.raw = clean;
                });
            });

            form.addEventListener('submit', function(e) {
                e.preventDefault();

                form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

                const rules = [{
                        selector: 'input[name="name"]',
                        message: 'Nama Produk wajib diisi'
                    },
                    {
                        selector: 'input[name="sku"]',
                        message: 'SKU wajib diisi'
                    },
                    {
                        selector: 'select[name="stock"]',
                        message: 'Stock wajib diisi'
                    },
                    {
                        selector: 'input[name="price"]',
                        message: 'Price wajib diisi'
                    },
                    {
                        selector: 'input[name="fixed_cost"]',
                        message: 'Fixed Cost wajib diisi'
                    },
                    {
                        selector: '#categories',
                        message: 'Minimal satu kategori harus dipilih',
                        validate: () => $('#categories').val() && $('#categories').val().length > 0
                    },
                    {
                        selector: '#tags',
                        message: 'Minimal satu tag harus dipilih',
                        validate: () => $('#tags').val() && $('#tags').val().length > 0
                    }
                ];

                let isValid = true;
                rules.forEach(rule => {
                    const el = form.querySelector(rule.selector);
                    const val = el?.value ?? '';
                    const valid = rule.validate ? rule.validate(val) : val.trim() !== '';

                    if (!valid) {
                        showError(el, rule.message);
                        isValid = false;
                    }
                });

                ['price', 'fixed_cost'].forEach(id => {
                    const input = document.getElementById(id);
                    if (!input) return;
                    const raw = input.dataset.raw || input.value.replace(/[^\d]/g, '');
                    input.value = raw === '' ? 0 : raw;
                });

                if (isValid) form.submit();
            });

            function showError(input, message) {
                if (!input) return;

                if ($(input).hasClass('select2-hidden-accessible')) {
                    const select2Container = $(input).next('.select2');
                    select2Container.find('.select2-selection').addClass('is-invalid');
                    if (select2Container.next('.invalid-feedback').length === 0) {
                        select2Container.after(`<div class="invalid-feedback d-block">${message}</div>`);
                    }
                } else {
                    input.classList.add('is-invalid');
                    const parent = input.closest('div');
                    if (!parent) return;
                    const feedback = document.createElement('div');
                    feedback.className = 'invalid-feedback';
                    feedback.textContent = message;
                    parent.appendChild(feedback);
                }
            }

        });
    </script>
@endpush
