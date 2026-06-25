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
                            {{-- <div class="row mb-3 align-items-center">
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
                            </div> --}}

                            <div class="row mb-3 align-items-center">
                                <div class="col-lg-2">
                                    <label for="base_unit_id" class="fw-semibold">Base Unit</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <select class="form-control" id="base_unit_id" name="base_unit_id">
                                        <option value="">Choose Base Unit</option>
                                        @foreach ($productUnits as $unit)
                                            <option value="{{ $unit->id }}"
                                                {{ old('base_unit_id') == $unit->id ? 'selected' : '' }}>
                                                {{ $unit->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">
                                        Base unit adalah satuan terkecil produk. Contoh: pcs.
                                    </small>
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
                                                    <th style="width: 20%">Unit</th>
                                                    {{-- <th style="width: 18%">Conversion to Base Unit</th>
                                                     --}}
                                                    <th style="width: 18%">Rasio</th>
                                                    <th style="width: 18%">Fixed Cost</th>
                                                    <th style="width: 18%">Margin</th>
                                                    <th style="width: 18%">Sale Price</th>
                                                    <th style="width: 8%">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="productUnitBody">
                                                <tr>
                                                    <td>
                                                        <select name="units[0][unit_id]" class="form-control unit-select">
                                                            <option value="">Choose Unit</option>
                                                            @foreach ($productUnits as $unit)
                                                                <option value="{{ $unit->id }}">{{ $unit->name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <input type="text" name="units[0][conversion_value]"
                                                            class="form-control conversion-input"
                                                            placeholder="Contoh: 1000">
                                                    </td>
                                                    <td>
                                                        <input type="text" name="units[0][fixed_cost]"
                                                            class="form-control unit-money-field" placeholder="0">
                                                    </td>
                                                    <td>
                                                        <input type="text" name="units[0][margin]"
                                                            class="form-control unit-money-field" placeholder="0">
                                                    </td>
                                                    <td>
                                                        <input type="text" name="units[0][sale_price]"
                                                            class="form-control unit-money-field sale-price-input"
                                                            placeholder="0">
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

                                    {{-- <small class="text-muted d-block mt-2">
                                        Pcs otomatis dihitung sebagai 1. Di sini cukup isi unit tambahan seperti Dus, Pack,
                                        Roll, dll.
                                    </small> --}}
                                    <small class="text-muted d-block mt-2">
                                        Isi rasio perbandingan antar unit. Contoh: DUS=1, PACK=10, PCS=1000 (artinya 1 DUS =
                                        10 PACK = 1000 PCS).
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
            const productUnitBody = document.getElementById('productUnitBody');
            const addProductUnitButton = document.getElementById('addProductUnit');

            let unitRowIndex = $('#productUnitBody tr').length;

            const priceInput = document.getElementById('price');
            if (priceInput) {
                priceInput.value = '0';
                priceInput.dataset.raw = '0';
            }

            function normalizeMoneyValue(value) {
                if (!value) return '0';

                let raw = value.toString().trim();

                // 2.000,50 => 2000.50
                if (raw.includes(',') && raw.includes('.')) {
                    raw = raw.replace(/\./g, '').replace(',', '.');
                }
                // 2.000 => 2000
                else if (raw.includes('.') && /^\d{1,3}(\.\d{3})+$/.test(raw)) {
                    raw = raw.replace(/\./g, '');
                }
                // 2000 => 2000
                else {
                    raw = raw.replace(',', '.');
                }

                if (raw === '' || isNaN(parseFloat(raw))) {
                    return '0';
                }

                return raw;
            }

            function parseMoneyValue(value) {
                return parseFloat(normalizeMoneyValue(value)) || 0;
            }

            function formatRupiahValue(value) {
                const number = parseFloat(value) || 0;

                return number.toLocaleString('id-ID', {
                    minimumFractionDigits: number % 1 === 0 ? 0 : 2,
                    maximumFractionDigits: 2
                });
            }

            function bindMoneyInput(input) {
                if (input.dataset.bound === '1') return;

                input.dataset.bound = '1';

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
                document
                    .querySelectorAll(
                        'input[name*="[fixed_cost]"], input[name*="[margin]"], input[name*="[sale_price]"]'
                    )
                    .forEach(input => {
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

            // function calculateSalePrice(row) {
            //     const fixedCostInput = row.find('input[name*="[fixed_cost]"]');
            //     const marginInput = row.find('input[name*="[margin]"]');
            //     const salePriceInput = row.find('input[name*="[sale_price]"]');

            //     const fixedCost = parseMoneyValue(fixedCostInput.val());
            //     const margin = parseMoneyValue(marginInput.val());

            //     const salePrice = fixedCost + margin;

            //     salePriceInput.val(formatRupiahValue(salePrice));
            //     salePriceInput[0].dataset.raw = salePrice;
            // }

            function getBaseRatioOneRow() {
                let baseRow = null;

                $('#productUnitBody tr').each(function() {
                    const ratioInput = $(this).find('.conversion-input');
                    const ratio = parseFloat(ratioInput.val().replace(',', '.')) || 0;

                    if (ratio === 1) {
                        baseRow = $(this);
                        return false;
                    }
                });

                return baseRow;
            }

            function calculateUnitPrices() {
                const baseRow = getBaseRatioOneRow();

                if (!baseRow) return;

                const baseFixedCostInput = baseRow.find('input[name*="[fixed_cost]"]');
                const baseMarginInput = baseRow.find('input[name*="[margin]"]');

                const baseFixedCost = parseMoneyValue(baseFixedCostInput.val());
                const baseMargin = parseMoneyValue(baseMarginInput.val());

                $('#productUnitBody tr').each(function() {
                    const row = $(this);

                    const ratioInput = row.find('.conversion-input');
                    const fixedCostInput = row.find('input[name*="[fixed_cost]"]');
                    const marginInput = row.find('input[name*="[margin]"]');
                    const salePriceInput = row.find('input[name*="[sale_price]"]');

                    const ratio = parseFloat(ratioInput.val().replace(',', '.')) || 0;
                    if (ratio <= 0) return;

                    const fixedCost = ratio === 1 ? baseFixedCost : baseFixedCost / ratio;
                    const margin = ratio === 1 ? baseMargin : baseMargin / ratio;
                    const salePrice = fixedCost + margin;

                    fixedCostInput.val(formatRupiahValue(fixedCost));
                    fixedCostInput[0].dataset.raw = fixedCost.toString();

                    marginInput.val(formatRupiahValue(margin));
                    marginInput[0].dataset.raw = margin.toString();

                    salePriceInput.val(formatRupiahValue(salePrice));
                    salePriceInput[0].dataset.raw = salePrice.toString();

                    removeError(fixedCostInput);
                    removeError(marginInput);
                    removeError(salePriceInput);
                });
            }

            function syncBaseUnitConversion() {
                const baseUnitId = $('#base_unit_id').val();

                $('.unit-select').each(function() {
                    const row = $(this).closest('tr');
                    const conversionInput = row.find('.conversion-input');

                    // if ($(this).val() === baseUnitId && baseUnitId !== '') {
                    //     conversionInput.val('1');
                    //     conversionInput.prop('readonly', true);
                    //     removeError(conversionInput);
                    // } else {
                    //     conversionInput.prop('readonly', false);
                    // }
                    if ($(this).val() === baseUnitId && baseUnitId !== '') {
                        removeError(conversionInput);
                    }
                });
            }

            bindAllMoneyFields();
            bindConversionFields();
            syncBaseUnitConversion();
            calculateUnitPrices();

            $('#base_unit_id').on('change', function() {
                removeError($(this));
                syncBaseUnitConversion();
            });

            $(document).on('change', '.unit-select', function() {
                syncBaseUnitConversion();
            });

            // $(document).on('input', 'input[name*="[fixed_cost]"], input[name*="[margin]"]', function() {
            //     const row = $(this).closest('tr');
            //     calculateSalePrice(row);
            // });

            $(document).on('input', '.conversion-input, input[name*="[fixed_cost]"], input[name*="[margin]"]',
                function() {
                    calculateUnitPrices();
                });

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

            ['name', 'sku'].forEach(id => {
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

            $(document).on('change input', '.unit-select, .conversion-input, .unit-money-field, .sale-price-input',
                function() {
                    removeError($(this));
                });

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
                            class="form-control conversion-input" placeholder="Contoh: 1000">
                    </td>
                    <td>
                        <input type="text" name="units[${unitRowIndex}][fixed_cost]"
                            class="form-control unit-money-field" placeholder="0">
                    </td>
                    <td>
                        <input type="text" name="units[${unitRowIndex}][margin]"
                            class="form-control unit-money-field" placeholder="0">
                    </td>
                    <td>
                        <input type="text" name="units[${unitRowIndex}][sale_price]"
                            class="form-control unit-money-field sale-price-input" placeholder="0">
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
                syncBaseUnitConversion();
                calculateUnitPrices();
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

                const baseUnitSelect = $('#base_unit_id');
                if (!baseUnitSelect.val()) {
                    showError(baseUnitSelect, 'Base Unit wajib dipilih');
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
                    const fixedCostInput = $(this).find('input[name*="[fixed_cost]"]');
                    const marginInput = $(this).find('input[name*="[margin]"]');
                    const salePriceInput = $(this).find('input[name*="[sale_price]"]');

                    const unitId = unitSelect.val();
                    const conversionValue = conversionInput.val();
                    const fixedCost = fixedCostInput.val();
                    const margin = marginInput.val();
                    const salePrice = salePriceInput.val();

                    const rowIsEmpty =
                        (!unitId || unitId.trim() === '') &&
                        (!conversionValue || conversionValue.trim() === '') &&
                        (!fixedCost || fixedCost.trim() === '') &&
                        (!margin || margin.trim() === '') &&
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

                    if (parseMoneyValue(salePrice) <= 0) {
                        showError(salePriceInput, 'Sale Price wajib lebih dari 0');
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
                    if (priceInput) {
                        priceInput.value = '0';
                    }

                    document.querySelectorAll(
                        'input[name*="[fixed_cost]"], input[name*="[margin]"], input[name*="[sale_price]"]'
                    ).forEach(input => {
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
