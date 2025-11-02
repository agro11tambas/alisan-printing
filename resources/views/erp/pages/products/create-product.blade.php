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
                    <form action="/erp/products/store" method="POST" id="productForm" enctype="multipart/form-data">
                        @csrf
                        @method('POST')
                        <div class="card-body">
                            {{-- <div class="row mb-3 align-items-center">
                                <div class="col-lg-2">
                                    <label for="image" class="fw-semibold">Upload Image</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <div class="input-group">
                                        <input type="file" class="form-control" id="image" name="image"
                                            accept="image/*" value="{{ old('image') }}">
                                    </div>
                                    <img id="preview-image" src="#" alt="Preview"
                                        style="display:none; max-width: 100px; margin-top: 10px; border-radius: 10px" />
                                </div>
                            </div> --}}
                            <div class="row mb-3 align-items-center">
                                <div class="col-lg-2">
                                    <label for="name" class="fw-semibold">Name:</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="name" name="name"
                                            value="{{ old('name') }}" placeholder="Name">
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
                                            value="{{ old('sku') }}" placeholder="SKU">
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
                                                @endphp
                                                <option value="{{ $category->id }}" data-bg="{{ $bg }}">
                                                    {{ $category->name }}</option>
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
                                                @endphp
                                                <option value="{{ $tag->id }}" data-bg="{{ $bg }}">
                                                    {{ $tag->name }}</option>
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
                                            value="{{ old('price') }}" placeholder="0">
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
                                            value="{{ old('fixed_cost') }}" placeholder="0">
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
                                            class="form-control" value="{{ old('description') }}"></textarea>
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
            const form = document.getElementById('productForm');

            // ========== FORMAT INPUT PRICE & FIXED COST ==========
            ['price', 'fixed_cost'].forEach(id => {
                const input = document.getElementById(id);
                if (!input) return;

                input.addEventListener('input', function() {
                    // Ambil hanya angka dan koma
                    let val = this.value.replace(/[^\d,]/g, '');

                    // Pisahkan antara angka dan desimal (koma)
                    let parts = val.split(',');

                    // Format bagian ribuan (sebelum koma)
                    let integerPart = parts[0] ? parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.') :
                        '';

                    // Gabungkan lagi jika ada desimal
                    val = parts.length > 1 ? `${integerPart},${parts[1]}` : integerPart;

                    // Tampilkan dengan format Indonesia (1.000,25)
                    this.value = val;

                    // Simpan versi raw (float, dengan titik)
                    this.dataset.raw = val.replace(/\./g, '').replace(',', '.');

                    // Hapus error saat user mulai mengetik
                    removeError($(this));
                });
            });

            // ========== IMAGE PREVIEW ==========
            const imageInput = document.getElementById('image');
            if (imageInput) {
                imageInput.addEventListener('change', function(event) {
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
            }

            function showError(element, message) {
                const parent = element.closest('.col-lg-10'); // ✅ Ganti dari .input-group

                parent.find('.error-message').remove();
                element.addClass('is-invalid');

                if (element.hasClass('select2-hidden-accessible')) {
                    element.next('.select2').find('.select2-selection').addClass('is-invalid');
                }

                const errorDiv = $('<div class="error-message text-danger small mt-1"></div>').text(message);
                parent.append(errorDiv); // ✅ Sekarang error muncul di bawah .input-group
            }

            function removeError(element) {
                const parent = element.closest('.col-lg-10'); // ✅ Ganti dari .input-group
                parent.find('.error-message').remove();
                element.removeClass('is-invalid');

                if (element.hasClass('select2-hidden-accessible')) {
                    element.next('.select2').find('.select2-selection').removeClass('is-invalid');
                }
            }

            // ========== AUTO REMOVE ERROR SAAT INPUT ==========
            ['name', 'sku', 'price'].forEach(id => {
                const input = $('#' + id);
                if (input.length) {
                    input.on('input', function() {
                        removeError($(this));
                    });
                }
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

                // Validasi Price
                const priceInput = $('input[name="price"]');
                if (!priceInput.val() || priceInput.val().trim() === '') {
                    showError(priceInput, 'Price wajib diisi');
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
                        if (raw === '') raw = '0';
                        raw = raw.replace(',', '.');
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
