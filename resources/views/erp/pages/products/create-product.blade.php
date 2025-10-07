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
            <li class="breadcrumb-item">Create Products</li>
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
                    <span>Add Product</span>
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
@if(session('error'))
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
                <form action="/erp/products/store" method="POST" id="productForm" enctype="multipart/form-data">
                    @csrf
                    @method('POST')
                    <div class="card-body">
                        <div class="row mb-3 align-items-center">
                            <div class="col-lg-2">
                                <label for="image" class="fw-semibold">Upload Image</label>
                            </div>
                            <div class="col-lg-10 mb-0">
                                <div class="input-group">
                                    <input type="file" class="form-control" id="image" name="image" accept="image/*" value="{{ old('image') }}">
                                </div>
                                <img id="preview-image" src="#" alt="Preview" style="display:none; max-width: 100px; margin-top: 10px; border-radius: 10px" />
                            </div>
                        </div>
                        <div class="row mb-3 align-items-center">
                            <div class="col-lg-2">
                                <label for="name" class="fw-semibold">Name:</label>
                            </div>
                            <div class="col-lg-10 mb-0">
                                <div class="input-group">
                                    <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" placeholder="Name">
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3 align-items-center">
                            <div class="col-lg-2">
                                <label for="sku" class="fw-semibold">SKU</label>
                            </div>
                            <div class="col-lg-10 mb-0">
                                <div class="input-group">
                                    <input type="text" class="form-control" id="sku" name="sku" value="{{ old('sku') }}" placeholder="SKU">
                                </div>
                            </div>
                        </div>
                        <!-- <div class="row mb-3 align-items-center">
                            <div class="col-lg-2">
                                <label for="stock" class="fw-semibold">Stock</label>
                            </div>
                            <div class="col-lg-10 mb-0">
                                <div class="input-group">
                                    <select class="form-control" data-select2-selector="status" id="stock" name="stock">
                                        <option value="instock" data-bg="bg-success" selected>In Stock</option>
                                        <option value="outofstock" data-bg="bg-warning">Out Of Stock</option>
                                    </select>
                                </div>
                            </div>
                        </div> -->
                        <div class="row mb-3 align-items-center">
                            <div class="col-lg-2">
                                <label for="categories" class="fw-semibold">Categories</label>
                            </div>
                            <div class="col-lg-10 mb-0">
                                <div class="input-group">
                                    @php
                                    $bgColors = ['bg-danger', 'bg-warning', 'bg-primary', 'bg-indigo', 'bg-success'];
                                    @endphp

                                    <select class="form-select form-control max-select" data-select2-selector="tag" multiple id="categories" name="categories[]">
                                        @foreach ($categories as $index => $category)
                                        @php
                                        $bg = $bgColors[$index % count($bgColors)];
                                        @endphp
                                        <option value="{{ $category->id }}" data-bg="{{ $bg }}">{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3 align-items-center">
                            <div class="col-lg-2">
                                <label for="tags" class="fw-semibold">Tags</label>
                            </div>
                            <div class="col-lg-10 mb-0">
                                <div class="input-group">
                                    @php
                                    $bgColors = ['bg-primary', 'bg-success', 'bg-teal', 'bg-indigo', 'bg-info'];
                                    @endphp

                                    <select class="form-select form-control max-select" data-select2-selector="tag" multiple id="tags" name="tags[]">
                                        @foreach ($tags as $index => $tag)
                                        @php
                                        $bg = $bgColors[$index % count($bgColors)];
                                        @endphp
                                        <option value="{{ $tag->id }}" data-bg="{{ $bg }}">{{ $tag->name }}</option>
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
                                    <input type="text" class="form-control" id="price" name="price" value="{{ old('price') }}" placeholder="Price">
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3 align-items-center">
                            <div class="col-lg-2">
                                <label for="description" class="fw-semibold">Description:</label>
                            </div>
                            <div class="col-lg-10 mb-0">
                                <div class="input-group">
                                    <textarea name="description" id="description" cols="30" rows="5" placeholder="Description" class="form-control" value="{{ old('description') }}"></textarea>
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
    document.getElementById('image').addEventListener('change', function(event) {
        const file = event.target.files[0];
        const preview = document.getElementById('preview-image');

        if (file) {
            const reader = new FileReader();

            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            };

            reader.readAsDataURL(file);
        } else {
            preview.src = '#';
            preview.style.display = 'none';
        }
    });

    document.getElementById('productForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const form = this;
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

        const rules = [{
                selector: 'input[name="name"]',
                message: 'Nama Tag wajib diisi'
            },
            {
                selector: 'input[name="sku"]',
                message: 'SKU Wajib diisi',
            },
            // {
            //     selector: 'select[name="stock"]',
            //     message: 'Stock Wajib diisi',
            // },
            {
                selector: 'input[name="price"]',
                message: 'Price Wajib diisi'
            },
            // {
            //     selector: 'select[name="categories[]"]',
            //     message: 'Minimal satu kategori harus dipilih'
            // },
            // {
            //     selector: 'select[name="tags[]"]',
            //     message: 'Minimal satu tag harus dipilih'
            // }
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

        if (isValid) form.submit();
    });

    function showError(input, message) {
        if (!input) return;
        input.classList.add('is-invalid');
        const parent = input.closest('div');
        if (!parent) return;
        const feedback = document.createElement('div');
        feedback.className = 'invalid-feedback';
        feedback.textContent = message;
        parent.appendChild(feedback);
    }
</script>
@endpush