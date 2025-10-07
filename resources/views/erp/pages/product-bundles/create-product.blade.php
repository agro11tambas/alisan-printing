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
            <li class="breadcrumb-item">Create Product Bundles</li>
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
                <a href="/erp/products/product-bundles" class="btn btn-light-brand">
                    <i class="feather-arrow-left me-2"></i>
                    <span>Back</span>
                </a>
                <button type="submit" class="btn btn-primary" form="productBundleForm">
                    <i class="feather-plus me-2"></i>
                    <span>Add Product Bundles</span>
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
                <form action="/erp/products/product-bundles/store" method="POST" id="productBundleForm">
                    @csrf
                    <div class="card-body">

                        {{-- Pilih Produk --}}
                        <div class="row mb-3 align-items-center">
                            <div class="col-lg-2">
                                <label class="fw-semibold">Pilih Produk:</label>
                            </div>
                            <div class="col-lg-10 mb-0">
                                <div class="input-group">
                                    <select class="form-select form-control max-select" multiple id="products" name="products[]" data-select2-selector="tag">
                                        @foreach($products as $product)
                                        <option value="{{ $product->id }}" data-name="{{ $product->name }}">
                                            {{ $product->name }} - {{ $product->sku }} (Rp{{ number_format($product->price) }})
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                                <small class="text-muted">Pilih minimal dua produk untuk bundle</small>
                            </div>
                        </div>

                        {{-- Nama Bundle (Auto) --}}
                        <div class="row mb-3 align-items-center">
                            <div class="col-lg-2">
                                <label for="name" class="fw-semibold">Name:</label>
                            </div>
                            <div class="col-lg-10 mb-0">
                                <input type="text" class="form-control" id="name" name="name" readonly>
                            </div>
                        </div>

                        {{-- SKU --}}
                        <div class="row mb-3 align-items-center">
                            <div class="col-lg-2">
                                <label for="sku" class="fw-semibold">SKU</label>
                            </div>
                            <div class="col-lg-10 mb-0">
                                <input type="text" class="form-control" id="sku" name="sku" value="{{ old('sku') }}" placeholder="SKU">
                            </div>
                        </div>

                        {{-- Harga --}}
                        <div class="row mb-3 align-items-center">
                            <div class="col-lg-2">
                                <label for="price" class="fw-semibold">Price</label>
                            </div>
                            <div class="col-lg-10 mb-0">
                                <input type="number" class="form-control" id="price" name="price" value="{{ old('price') }}" placeholder="Price">
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
    document.getElementById('products').addEventListener('change', function() {
        let selected = Array.from(this.selectedOptions).map(opt => opt.dataset.name);
        document.getElementById('name').value = selected.join(' + ');
    });

    $(document).ready(function() {
        $('#products').select2({
            placeholder: "Pilih produk",
            tags: true, // biar bisa masukin text yang gak ada di list
            createTag: function(params) {
                let term = $.trim(params.term);
                if (term === '') {
                    return null;
                }
                return {
                    id: term,
                    text: term,
                    newTag: true // custom property
                };
            },
            matcher: function(params, data) {
                // Kalau input kosong, tampilkan semua
                if ($.trim(params.term) === '') {
                    return data;
                }

                // Filter pencarian
                if (data.text.toLowerCase().indexOf(params.term.toLowerCase()) > -1) {
                    return data;
                }

                // Tetap return null jika tidak cocok (biar gak double)
                return null;
            }
        });

        // Update field name bundle saat produk dipilih
        $('#products').on('change', function() {
            let selected = $(this).find(':selected').map(function() {
                return $(this).data('name') || $(this).text();
            }).get();
            $('#name').val(selected.join(' + '));
        });
    });

    $('#products').on('select2:select', function(e) {
        let data = e.params.data;
        $(this).find(`option[value="${data.id}"]`).attr('data-name', data.name);
    });

    $('#products').on('select2:select', function(e) {
        let data = e.params.data;
        $(this).find(`option[value="${data.id}"]`).attr('data-name', data.name);
    });

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

    document.getElementById('productBundleForm').addEventListener('submit', function(e) {
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