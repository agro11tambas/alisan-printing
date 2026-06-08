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
    <div class="main-content m-0 m-md-2 m-lg-2 p-0 p-md-0 p-lg-0 pt-2 pt-md-0">
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
                                            value="{{ old('price', number_format($product->price ?? 0, 2, ',', '.')) }}"
                                            placeholder="0">
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
                            @php
                                $pcsUnitId = optional($pcsUnit)->id;

                                $existingUnitConversions = $product->unitConversions
                                    ->filter(function ($conversion) use ($pcsUnitId) {
                                        return (int) $conversion->unit_id !== (int) $pcsUnitId;
                                    })
                                    ->values();
                            @endphp

                            <div class="row mb-3 align-items-start">
                                <div class="col-lg-2">
                                    <label class="fw-semibold">Product Units</label>
                                </div>

                                <div class="col-lg-10 mb-0">
                                    <div class="table-responsive">
                                        <table class="table table-bordered align-middle" id="productUnitTable">
                                            <thead>
                                                <tr>
                                                    <th style="width: 30%">Unit</th>
                                                    <th style="width: 25%">Conversion to Pcs</th>
                                                    <th style="width: 30%">Sale Price</th>
                                                    <th style="width: 10%">Action</th>
                                                </tr>
                                            </thead>

                                            <tbody id="productUnitBody">
                                                @forelse ($existingUnitConversions as $index => $conversion)
                                                    <tr>
                                                        <td>
                                                            <select name="units[{{ $index }}][unit_id]"
                                                                class="form-control unit-select">
                                                                <option value="">Choose Unit</option>

                                                                @foreach ($productUnits as $unit)
                                                                    @if (strtolower($unit->name) !== 'pcs')
                                                                        <option value="{{ $unit->id }}"
                                                                            {{ (int) $conversion->unit_id === (int) $unit->id ? 'selected' : '' }}>
                                                                            {{ $unit->name }}
                                                                        </option>
                                                                    @endif
                                                                @endforeach
                                                            </select>
                                                        </td>

                                                        <td>
                                                            <input type="text"
                                                                name="units[{{ $index }}][conversion_value]"
                                                                class="form-control conversion-input"
                                                                value="{{ old('units.' . $index . '.conversion_value', rtrim(rtrim(number_format($conversion->conversion_value ?? 0, 2, '.', ''), '0'), '.')) }}"
                                                                placeholder="Contoh: 1000">
                                                        </td>

                                                        <td>
                                                            <input type="text"
                                                                name="units[{{ $index }}][sale_price]"
                                                                class="form-control unit-money-field"
                                                                value="{{ old('units.' . $index . '.sale_price', number_format($conversion->sale_price ?? 0, 2, ',', '.')) }}"
                                                                placeholder="0">
                                                        </td>

                                                        <td class="text-center">
                                                            <button type="button"
                                                                class="btn btn-danger btn-sm btn-remove-unit">
                                                                <i class="feather-trash-2"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td>
                                                            <select name="units[0][unit_id]"
                                                                class="form-control unit-select">
                                                                <option value="">Choose Unit</option>

                                                                @foreach ($productUnits as $unit)
                                                                    @if (strtolower($unit->name) !== 'pcs')
                                                                        <option value="{{ $unit->id }}">
                                                                            {{ $unit->name }}</option>
                                                                    @endif
                                                                @endforeach
                                                            </select>
                                                        </td>

                                                        <td>
                                                            <input type="text" name="units[0][conversion_value]"
                                                                class="form-control conversion-input"
                                                                placeholder="Contoh: 1000">
                                                        </td>

                                                        <td>
                                                            <input type="text" name="units[0][sale_price]"
                                                                class="form-control unit-money-field" placeholder="0">
                                                        </td>

                                                        <td class="text-center">
                                                            <button type="button"
                                                                class="btn btn-danger btn-sm btn-remove-unit">
                                                                <i class="feather-trash-2"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>

                                    <button type="button" class="btn btn-light-brand btn-sm" id="addProductUnit">
                                        <i class="feather-plus me-2"></i>
                                        Add Unit
                                    </button>

                                    <small class="text-muted d-block mt-2">
                                        Pcs otomatis dihitung sebagai 1. Di sini cukup isi unit tambahan seperti Dus, Pack,
                                        Roll, dll.
                                    </small>
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

{{-- @push('scripts')
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
@endpush --}}

@push('scripts')
    <script>
        function showError(element, message) {
            const parent = element.closest('.col-lg-10').length ?
                element.closest('.col-lg-10') :
                element.closest('td');

            parent.find('.error-message').remove();
            element.addClass('is-invalid');

            if (element.hasClass('select2-hidden-accessible')) {
                element.next('.select2').find('.select2-selection').addClass('is-invalid');
            }

            const errorDiv = $('<div class="error-message text-danger small mt-1"></div>').text(message);
            parent.append(errorDiv);
        }

        function removeError(element) {
            const parent = element.closest('.col-lg-10').length ?
                element.closest('.col-lg-10') :
                element.closest('td');

            parent.find('.error-message').remove();
            element.removeClass('is-invalid');

            if (element.hasClass('select2-hidden-accessible')) {
                element.next('.select2').find('.select2-selection').removeClass('is-invalid');
            }
        }

        document.addEventListener("DOMContentLoaded", () => {
            const form = document.getElementById('productForm');
            const productUnitBody = document.getElementById('productUnitBody');
            const addProductUnitButton = document.getElementById('addProductUnit');

            let unitRowIndex = $('#productUnitBody tr').length;

            function normalizeMoneyValue(value) {
                let raw = value.toString().replace(/\s/g, '');
                raw = raw.replace(/\.(?=\d{3}(,|$))/g, '');
                raw = raw.replace(',', '.');

                if (raw === '' || isNaN(parseFloat(raw))) {
                    return '0';
                }

                return raw;
            }

            function formatInitialMoney(input) {
                if (!input.value) return;

                const raw = normalizeMoneyValue(input.value);
                const num = parseFloat(raw);

                if (isNaN(num)) return;

                if (num % 1 === 0) {
                    input.value = num.toLocaleString('id-ID', {
                        minimumFractionDigits: 0,
                        maximumFractionDigits: 0
                    });
                } else {
                    input.value = num.toLocaleString('id-ID', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }

                input.dataset.raw = raw;
            }

            function bindMoneyInput(input) {
                if (input.dataset.bound === '1') return;

                input.dataset.bound = '1';

                formatInitialMoney(input);

                input.addEventListener('focus', function() {
                    if (this.value === '0') {
                        this.value = '';
                        this.dataset.wasZero = 'true';
                    }
                });

                input.addEventListener('blur', function() {
                    if (this.value.trim() === '' && this.dataset.wasZero === 'true') {
                        this.value = '0';
                    }

                    delete this.dataset.wasZero;
                });

                input.addEventListener('input', function() {
                    let val = this.value.replace(/[^\d,]/g, '');
                    const parts = val.split(',');

                    let integerPart = parts[0] ?
                        parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.') :
                        '';

                    if (parts.length > 1) {
                        let decimalPart = parts[1].substring(0, 2);
                        val = `${integerPart},${decimalPart}`;
                    } else {
                        val = integerPart;
                    }

                    this.value = val;
                    this.dataset.raw = normalizeMoneyValue(val);

                    removeError($(this));
                });
            }

            function bindAllMoneyFields() {
                ['price', 'fixed_cost'].forEach(id => {
                    const input = document.getElementById(id);
                    if (!input) return;

                    bindMoneyInput(input);
                });

                document.querySelectorAll('.unit-money-field').forEach(input => {
                    bindMoneyInput(input);
                });
            }

            function bindConversionFields() {
                document.querySelectorAll('.conversion-input').forEach(input => {
                    if (input.dataset.bound === '1') return;

                    input.dataset.bound = '1';

                    input.addEventListener('input', function() {
                        this.value = this.value.replace(/[^0-9.,]/g, '');
                        this.value = this.value.replace(',', '.');

                        removeError($(this));
                    });
                });
            }

            bindAllMoneyFields();
            bindConversionFields();

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

                            if (oldImageContainer) {
                                oldImageContainer.style.display = 'none';
                            }
                        };

                        reader.readAsDataURL(file);
                    } else {
                        previewContainer.style.display = 'none';

                        if (oldImageContainer) {
                            oldImageContainer.style.display = 'block';
                        }
                    }
                });
            }

            ['name', 'sku', 'price', 'fixed_cost'].forEach(id => {
                const input = $('#' + id);

                if (input.length) {
                    input.on('input', function() {
                        removeError($(this));
                    });
                }
            });

            $('#categories, #tags').on('change', function() {
                removeError($(this));
            });

            $(document).on('change input', '.unit-select, .conversion-input, .unit-money-field', function() {
                removeError($(this));
            });

            addProductUnitButton.addEventListener('click', function() {
                const row = document.createElement('tr');

                row.innerHTML = `
                    <td>
                        <select name="units[${unitRowIndex}][unit_id]" class="form-control unit-select">
                            <option value="">Choose Unit</option>
                            @foreach ($productUnits as $unit)
                                @if (strtolower($unit->name) !== 'pcs')
                                    <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                                @endif
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <input type="text" name="units[${unitRowIndex}][conversion_value]"
                            class="form-control conversion-input" placeholder="Contoh: 1000">
                    </td>
                    <td>
                        <input type="text" name="units[${unitRowIndex}][sale_price]"
                            class="form-control unit-money-field" placeholder="0">
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-danger btn-sm btn-remove-unit">
                            <i class="feather-trash-2"></i>
                        </button>
                    </td>
                `;

                productUnitBody.appendChild(row);
                unitRowIndex++;

                bindAllMoneyFields();
                bindConversionFields();
            });

            document.addEventListener('click', function(e) {
                const removeButton = e.target.closest('.btn-remove-unit');

                if (!removeButton) return;

                removeButton.closest('tr').remove();
            });

            $('#productForm').on('submit', function(e) {
                e.preventDefault();

                let isValid = true;

                $('.error-message').remove();
                $('.is-invalid').removeClass('is-invalid');

                const nameInput = $('input[name="name"]');
                if (!nameInput.val() || nameInput.val().trim() === '') {
                    showError(nameInput, 'Nama Produk wajib diisi');
                    isValid = false;
                }

                const skuInput = $('input[name="sku"]');
                if (!skuInput.val() || skuInput.val().trim() === '') {
                    showError(skuInput, 'SKU wajib diisi');
                    isValid = false;
                }

                const priceInput = $('input[name="price"]');
                if (!priceInput.val() || priceInput.val().trim() === '') {
                    showError(priceInput, 'Price wajib diisi');
                    isValid = false;
                }

                const fixedCostInput = $('input[name="fixed_cost"]');
                if (!fixedCostInput.val() || fixedCostInput.val().trim() === '') {
                    showError(fixedCostInput, 'Fixed Cost wajib diisi');
                    isValid = false;
                }

                const categoriesSelect = $('#categories');
                if (!categoriesSelect.val() || categoriesSelect.val().length === 0) {
                    showError(categoriesSelect, 'Minimal satu kategori harus dipilih');
                    isValid = false;
                }

                const tagsSelect = $('#tags');
                if (!tagsSelect.val() || tagsSelect.val().length === 0) {
                    showError(tagsSelect, 'Minimal satu tag harus dipilih');
                    isValid = false;
                }

                let selectedUnits = [];
                let unitRowsValid = true;

                $('#productUnitBody tr').each(function() {
                    const unitSelect = $(this).find('select[name*="[unit_id]"]');
                    const conversionInput = $(this).find('input[name*="[conversion_value]"]');
                    const salePriceInput = $(this).find('input[name*="[sale_price]"]');

                    const unitId = unitSelect.val();
                    const conversionValue = conversionInput.val();
                    const salePrice = salePriceInput.val();

                    const rowIsEmpty =
                        (!unitId || unitId.trim() === '') &&
                        (!conversionValue || conversionValue.trim() === '') &&
                        (!salePrice || salePrice.trim() === '');

                    if (rowIsEmpty) {
                        return;
                    }

                    if (!unitId) {
                        showError(unitSelect, 'Unit wajib dipilih');
                        unitRowsValid = false;
                    }

                    if (!conversionValue || parseFloat(conversionValue) <= 0) {
                        showError(conversionInput, 'Conversion wajib lebih dari 0');
                        unitRowsValid = false;
                    }

                    if (!salePrice || salePrice.trim() === '') {
                        showError(salePriceInput, 'Sale Price wajib diisi');
                        unitRowsValid = false;
                    }

                    if (unitId) {
                        if (selectedUnits.includes(unitId)) {
                            showError(unitSelect, 'Unit tidak boleh duplikat');
                            unitRowsValid = false;
                        }

                        selectedUnits.push(unitId);
                    }
                });

                if (!unitRowsValid) {
                    isValid = false;
                }

                if (isValid) {
                    ['price', 'fixed_cost'].forEach(id => {
                        const input = document.getElementById(id);
                        if (!input) return;

                        input.value = normalizeMoneyValue(input.dataset.raw || input.value);
                    });

                    document.querySelectorAll('.unit-money-field').forEach(input => {
                        input.value = normalizeMoneyValue(input.dataset.raw || input.value);
                    });

                    document.querySelectorAll('.conversion-input').forEach(input => {
                        input.value = input.value.replace(',', '.');
                    });

                    this.submit();
                } else {
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
