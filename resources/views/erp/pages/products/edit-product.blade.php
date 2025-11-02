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
                            {{-- <div class="row mb-3 align-items-center">
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
                            </div> --}}

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
                                            value="{{ old('price', number_format($product->price ?? 0, 2, ',', '.')) }}" placeholder="0">
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
                                            value="{{ old('fixed_cost', number_format($product->fixed_cost ?? 0, 2, ',', '.')) }}"
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
        // ========== FUNGSI ERROR (GLOBAL SCOPE) ==========
        function showError(element, message) {
            // Cari parent col-lg-10 untuk naruh error di bawah field
            const parent = element.closest('.col-lg-10');

            // Hapus error lama jika ada
            parent.find('.error-message').remove();
            element.addClass('is-invalid');

            // Untuk select2
            if (element.hasClass('select2-hidden-accessible')) {
                element.next('.select2').find('.select2-selection').addClass('is-invalid');
            }

            // Tambah pesan error di bawah input-group
            const errorDiv = $('<div class="error-message text-danger small mt-1"></div>').text(message);
            parent.append(errorDiv);
        }

        function removeError(element) {
            // Cari parent col-lg-10 untuk hapus error
            const parent = element.closest('.col-lg-10');
            parent.find('.error-message').remove();
            element.removeClass('is-invalid');

            // Untuk select2
            if (element.hasClass('select2-hidden-accessible')) {
                element.next('.select2').find('.select2-selection').removeClass('is-invalid');
            }
        }

        document.addEventListener("DOMContentLoaded", () => {
            const form = document.getElementById('productForm');

            // ========== IMAGE PREVIEW ==========
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

            // ========== FORMAT INPUT PRICE & FIXED COST ==========
            ['price', 'fixed_cost'].forEach(id => {
                const input = document.getElementById(id);
                if (!input) return;

                // ✅ Format awal saat halaman dimuat (smart decimal: hapus ,00)
                if (input.value) {
                    let raw = input.value.toString().replace(/\./g, '').replace(',', '.');
                    let num = parseFloat(raw);
                    if (!isNaN(num)) {
                        // Smart format: 123.00 → 123 | 123.45 → 123,45
                        if (num % 1 === 0) {
                            // Bilangan bulat
                            input.value = num.toLocaleString('id-ID', {
                                minimumFractionDigits: 0,
                                maximumFractionDigits: 0
                            });
                        } else {
                            // Ada desimal
                            input.value = num.toLocaleString('id-ID', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            });
                        }
                        input.dataset.raw = num;
                    }
                }

                // ✅ Event focus: kosongkan jika value = 0
                input.addEventListener('focus', function() {
                    if (this.value === '0') {
                        this.value = '';
                        this.dataset.wasZero = 'true';
                    }
                });

                // ✅ Event blur: kembalikan ke 0 jika kosong
                input.addEventListener('blur', function() {
                    if (this.value.trim() === '' && this.dataset.wasZero === 'true') {
                        this.value = '0';
                    }
                    delete this.dataset.wasZero;
                });

                // ✅ Event input: format angka
                input.addEventListener('input', function() {
                    let val = this.value.replace(/[^\d,]/g, '');
                    const parts = val.split(',');

                    let integerPart = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');

                    if (parts.length > 1) {
                        let decimalPart = parts[1].substring(0, 2); // max 2 digit
                        val = `${integerPart},${decimalPart}`;
                    } else {
                        val = integerPart;
                    }

                    this.value = val;
                    this.dataset.raw = val;

                    removeError($(this));
                });
            });

            // ========== AUTO REMOVE ERROR SAAT INPUT ==========
            ['name', 'sku', 'price', 'fixed_cost'].forEach(id => {
                const input = $('#' + id);
                if (input.length) {
                    input.on('input', function() {
                        removeError($(this));
                    });
                }
            });

            // Untuk select (stock)
            $('#stock').on('change', function() {
                removeError($(this));
            });

            // Untuk select2 (categories & tags)
            $('#categories, #tags').on('change', function() {
                removeError($(this));
            });

            // ========== VALIDASI SUBMIT (KONSISTEN DENGAN REQUEST STOCK) ==========
            $('#productForm').on('submit', function(e) {
                e.preventDefault();

                let isValid = true;

                // Hapus semua error sebelumnya
                $('.error-message').remove();
                $('.is-invalid').removeClass('is-invalid');

                // Validasi Name
                const nameInput = $('input[name="name"]');
                if (!nameInput.val() || nameInput.val().trim() === '') {
                    showError(nameInput, 'Nama Produk wajib diisi');
                    isValid = false;
                }

                // Validasi SKU
                const skuInput = $('input[name="sku"]');
                if (!skuInput.val() || skuInput.val().trim() === '') {
                    showError(skuInput, 'SKU wajib diisi');
                    isValid = false;
                }

                // Validasi Stock
                const stockSelect = $('select[name="stock"]');
                if (!stockSelect.val() || stockSelect.val().trim() === '') {
                    showError(stockSelect, 'Stock wajib dipilih');
                    isValid = false;
                }

                // Validasi Price
                const priceInput = $('input[name="price"]');
                if (!priceInput.val() || priceInput.val().trim() === '') {
                    showError(priceInput, 'Price wajib diisi');
                    isValid = false;
                }

                // Validasi Fixed Cost
                const fixedCostInput = $('input[name="fixed_cost"]');
                if (!fixedCostInput.val() || fixedCostInput.val().trim() === '') {
                    showError(fixedCostInput, 'Fixed Cost wajib diisi');
                    isValid = false;
                }

                // Validasi Categories
                const categoriesSelect = $('#categories');
                if (!categoriesSelect.val() || categoriesSelect.val().length === 0) {
                    showError(categoriesSelect, 'Minimal satu kategori harus dipilih');
                    isValid = false;
                }

                // Validasi Tags
                const tagsSelect = $('#tags');
                if (!tagsSelect.val() || tagsSelect.val().length === 0) {
                    showError(tagsSelect, 'Minimal satu tag harus dipilih');
                    isValid = false;
                }

                // Jika valid, format angka dan submit
                if (isValid) {
                    ['price', 'fixed_cost'].forEach(id => {
                        const input = document.getElementById(id);
                        if (!input) return;

                        let raw = input.dataset.raw || input.value;

                        // Hapus spasi, titik ribuan, ubah koma jadi titik
                        raw = raw.toString().replace(/\s/g, '');
                        raw = raw.replace(/\.(?=\d{3}(,|$))/g, '');
                        raw = raw.replace(',', '.');

                        if (raw === '' || isNaN(parseFloat(raw))) raw = '0';

                        input.value = raw;
                    });

                    this.submit();
                } else {
                    // Scroll ke error pertama
                    const firstError = $('.is-invalid').first();
                    if (firstError.length) {
                        $('html, body').animate({
                            scrollTop: firstError.offset().top - 100
                        }, 300);
                    }
                }
            });
        });
    </script>
@endpush
