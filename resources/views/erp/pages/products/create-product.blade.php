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
    <div class="main-content m-0 m-md-2 m-lg-2 p-0 p-md-0 p-lg-0 pt-2 pt-md-0">
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
                                                <tr>
                                                    <td>
                                                        <select name="units[0][unit_id]" class="form-control unit-select">
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

{{-- @push('scripts')
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

                const baseUnitSelect = $('#base_unit_id');
                if (!baseUnitSelect.val()) {
                    showError(baseUnitSelect, 'Base Unit wajib dipilih');
                    isValid = false;
                }

                let selectedUnits = [];
                let hasBaseUnitInList = false;
                let unitRowsValid = true;
                const baseUnitId = baseUnitSelect.val();

                $('#productUnitBody tr').each(function() {
                    const unitSelect = $(this).find('select[name*="[unit_id]"]');
                    const conversionInput = $(this).find('input[name*="[conversion_value]"]');
                    const salePriceInput = $(this).find('input[name*="[sale_price]"]');

                    const unitId = unitSelect.val();
                    const conversionValue = conversionInput.val();
                    const salePrice = salePriceInput.val();

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

                    if (unitId === baseUnitId) {
                        hasBaseUnitInList = true;

                        if (parseFloat(conversionValue) !== 1) {
                            showError(conversionInput, 'Base Unit harus conversion 1');
                            unitRowsValid = false;
                        }
                    }
                });

                if (!unitRowsValid) {
                    isValid = false;
                }

                if (baseUnitId && !hasBaseUnitInList) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Base Unit belum ada',
                        text: 'Base Unit wajib dimasukkan juga ke Product Units dengan conversion 1.',
                    });

                    isValid = false;
                }

                // Jika valid, format angka dan submit
                if (isValid) {
                    ['price', 'fixed_cost'].forEach(id => {
                        const input = document.getElementById(id);
                        if (!input) return;

                        let raw = input.dataset.raw || input.value;
                        if (raw === '') raw = '0';
                        raw = raw.replace(/\./g, '').replace(',', '.');
                        input.value = raw;
                    });

                    document.querySelectorAll('.unit-money-field').forEach(input => {
                        let raw = input.dataset.raw || input.value;
                        if (raw === '') raw = '0';
                        raw = raw.replace(/\./g, '').replace(',', '.');
                        input.value = raw;
                    });

                    document.querySelectorAll('.conversion-input').forEach(input => {
                        input.value = input.value.replace(',', '.');
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

            $('#base_unit_id').on('change', function() {
                removeError($(this));
            });

            $(document).on('change input', '.unit-select, .conversion-input, .unit-money-field', function() {
                removeError($(this));
            });

            let unitRowIndex = 1;

            const productUnitBody = document.getElementById('productUnitBody');
            const addProductUnitButton = document.getElementById('addProductUnit');

            function formatNumberInput(input) {
                input.addEventListener('input', function() {
                    let val = this.value.replace(/[^\d,]/g, '');
                    let parts = val.split(',');

                    let integerPart = parts[0] ?
                        parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.') :
                        '';

                    val = parts.length > 1 ? `${integerPart},${parts[1]}` : integerPart;

                    this.value = val;
                    this.dataset.raw = val.replace(/\./g, '').replace(',', '.');
                });
            }

            function bindUnitMoneyFields() {
                document.querySelectorAll('.unit-money-field').forEach(input => {
                    if (input.dataset.bound === '1') return;

                    input.dataset.bound = '1';
                    formatNumberInput(input);
                });
            }

            function bindConversionFields() {
                document.querySelectorAll('.conversion-input').forEach(input => {
                    if (input.dataset.bound === '1') return;

                    input.dataset.bound = '1';

                    input.addEventListener('input', function() {
                        this.value = this.value.replace(/[^0-9.,]/g, '');
                        this.value = this.value.replace(',', '.');
                    });
                });
            }

            addProductUnitButton.addEventListener('click', function() {
                const row = document.createElement('tr');

                row.innerHTML = `
        <td>
            <select name="units[${unitRowIndex}][unit_id]" class="form-control unit-select">
                <option value="">Choose Unit</option>
                @foreach ($productUnits as $unit)
                    <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                @endforeach
            </select>
        </td>
        <td>
            <input type="text" name="units[${unitRowIndex}][conversion_value]"
                class="form-control conversion-input" placeholder="1">
        </td>
        <td>
            <input type="text" name="units[${unitRowIndex}][sale_price]"
                class="form-control unit-money-field" placeholder="0">
        </td>
        <td>
            <input type="text" name="units[${unitRowIndex}][purchase_price]"
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

                bindUnitMoneyFields();
                bindConversionFields();
            });

            document.addEventListener('click', function(e) {
                const removeButton = e.target.closest('.btn-remove-unit');

                if (!removeButton) return;

                const rows = productUnitBody.querySelectorAll('tr');

                if (rows.length <= 1) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Tidak bisa dihapus',
                        text: 'Minimal harus ada satu product unit.',
                    });
                    return;
                }

                removeButton.closest('tr').remove();
            });

            bindUnitMoneyFields();
            bindConversionFields();
        });
    </script>
@endpush --}}

@push('scripts')
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const form = document.getElementById('productForm');

            let unitRowIndex = 1;

            const productUnitBody = document.getElementById('productUnitBody');
            const addProductUnitButton = document.getElementById('addProductUnit');

            // ========== FORMAT ANGKA INDONESIA ==========
            function formatMoneyInput(input) {
                input.addEventListener('input', function() {
                    let val = this.value.replace(/[^\d,]/g, '');
                    let parts = val.split(',');

                    let integerPart = parts[0] ?
                        parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.') :
                        '';

                    val = parts.length > 1 ? `${integerPart},${parts[1]}` : integerPart;

                    this.value = val;
                    this.dataset.raw = val.replace(/\./g, '').replace(',', '.');

                    removeError($(this));
                });
            }

            // ========== FORMAT INPUT PRICE & FIXED COST ==========
            ['price', 'fixed_cost'].forEach(id => {
                const input = document.getElementById(id);
                if (!input) return;

                formatMoneyInput(input);
            });

            // ========== FORMAT SALE PRICE DI PRODUCT UNITS ==========
            function bindUnitMoneyFields() {
                document.querySelectorAll('.unit-money-field').forEach(input => {
                    if (input.dataset.bound === '1') return;

                    input.dataset.bound = '1';
                    formatMoneyInput(input);
                });
            }

            // ========== FORMAT CONVERSION ==========
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

            bindUnitMoneyFields();
            bindConversionFields();

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

            // ========== ERROR HANDLER ==========
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

            // ========== AUTO REMOVE ERROR ==========
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

            // ========== ADD PRODUCT UNIT ROW ==========
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

                bindUnitMoneyFields();
                bindConversionFields();
            });

            // ========== REMOVE PRODUCT UNIT ROW ==========
            document.addEventListener('click', function(e) {
                const removeButton = e.target.closest('.btn-remove-unit');

                if (!removeButton) return;

                removeButton.closest('tr').remove();
            });

            // ========== VALIDASI SUBMIT ==========
            $('#productForm').on('submit', function(e) {
                e.preventDefault();

                let isValid = true;

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

                // Validasi Product Units tambahan
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

                    // Kalau row kosong total, abaikan.
                    // Jadi produk boleh cuma pakai Pcs saja.
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
                    // Bersihkan price dan fixed_cost
                    ['price', 'fixed_cost'].forEach(id => {
                        const input = document.getElementById(id);
                        if (!input) return;

                        let raw = input.dataset.raw || input.value;

                        if (raw === '') raw = '0';

                        raw = raw.replace(/\./g, '').replace(',', '.');

                        input.value = raw;
                    });

                    // Bersihkan sale_price unit tambahan
                    document.querySelectorAll('.unit-money-field').forEach(input => {
                        let raw = input.dataset.raw || input.value;

                        if (raw === '') raw = '0';

                        raw = raw.replace(/\./g, '').replace(',', '.');

                        input.value = raw;
                    });

                    // Bersihkan conversion
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
