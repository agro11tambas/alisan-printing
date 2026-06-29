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
                            <input type="hidden" id="price" name="price" value="0">
                            <div class="row mb-3 align-items-center">
                                <div class="col-lg-2">
                                    <label for="base_unit_id" class="fw-semibold">Base Unit</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <select class="form-control" id="base_unit_id" name="base_unit_id">
                                        <option value="">Choose Base Unit</option>
                                        @foreach ($productUnits as $unit)
                                            <option value="{{ $unit->id }}"
                                                {{ old('base_unit_id', $product->base_unit_id) == $unit->id ? 'selected' : '' }}>
                                                {{ $unit->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            @php
                                $existingUnitConversions = old('units')
                                    ? collect(old('units'))
                                    : $product->unitConversions->values();

                                $unitRowIndex = max($existingUnitConversions->count(), 1);
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
                                                    <th style="width: 18%">Unit</th>
                                                    {{-- <th style="width: 18%">Conversion to Base Unit</th> --}}
                                                    <th style="width: 18%">Rasio</th>
                                                    <th style="width: 18%">Fixed Cost</th>
                                                    <th style="width: 18%">Margin</th>
                                                    <th style="width: 20%">Sale Price</th>
                                                    <th style="width: 8%">Action</th>
                                                </tr>
                                            </thead>

                                            <tbody id="productUnitBody">
                                                @forelse ($existingUnitConversions as $index => $conversion)
                                                    @php
                                                        $unitId = old(
                                                            "units.$index.unit_id",
                                                            is_array($conversion)
                                                                ? $conversion['unit_id'] ?? null
                                                                : $conversion->unit_id,
                                                        );
                                                        $conversionValue = old(
                                                            "units.$index.conversion_value",
                                                            is_array($conversion)
                                                                ? $conversion['ratio_value'] ?? null
                                                                : $conversion->ratio_value,
                                                        );
                                                        $fixedCost = old(
                                                            "units.$index.fixed_cost",
                                                            is_array($conversion)
                                                                ? $conversion['fixed_cost'] ?? 0
                                                                : $conversion->fixed_cost ?? 0,
                                                        );
                                                        $margin = old(
                                                            "units.$index.margin",
                                                            is_array($conversion)
                                                                ? $conversion['margin'] ?? 0
                                                                : $conversion->margin ?? 0,
                                                        );
                                                        $salePrice = old(
                                                            "units.$index.sale_price",
                                                            is_array($conversion)
                                                                ? $conversion['sale_price'] ?? 0
                                                                : $conversion->sale_price ?? 0,
                                                        );
                                                    @endphp

                                                    <tr>
                                                        <td>
                                                            <select name="units[{{ $index }}][unit_id]"
                                                                class="form-control unit-select">
                                                                <option value="">Choose Unit</option>
                                                                @foreach ($productUnits as $unit)
                                                                    <option value="{{ $unit->id }}"
                                                                        {{ (int) $unitId === (int) $unit->id ? 'selected' : '' }}>
                                                                        {{ $unit->name }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </td>

                                                        <td>
                                                            <input type="text"
                                                                name="units[{{ $index }}][conversion_value]"
                                                                class="form-control conversion-input"
                                                                value="{{ rtrim(rtrim(number_format((float) $conversionValue, 2, '.', ''), '0'), '.') }}"
                                                                placeholder="Contoh: 1000">
                                                        </td>

                                                        <td>
                                                            <input type="text"
                                                                name="units[{{ $index }}][fixed_cost]"
                                                                class="form-control unit-money-field"
                                                                value="{{ number_format((float) $fixedCost, 2, ',', '.') }}"
                                                                placeholder="0">
                                                        </td>

                                                        <td>
                                                            <input type="text"
                                                                name="units[{{ $index }}][margin]"
                                                                class="form-control unit-money-field"
                                                                value="{{ number_format((float) $margin, 2, ',', '.') }}"
                                                                placeholder="0">
                                                        </td>

                                                        <td>
                                                            <input type="text"
                                                                name="units[{{ $index }}][sale_price]"
                                                                class="form-control unit-money-field sale-price-input"
                                                                value="{{ number_format((float) $salePrice, 2, ',', '.') }}"
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
                                                                    <option value="{{ $unit->id }}">
                                                                        {{ $unit->name }}</option>
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
                                                @endforelse
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
                if (!value) return '0';

                let raw = value.toString().trim();

                if (raw.includes(',') && raw.includes('.')) {
                    raw = raw.replace(/\./g, '').replace(',', '.');
                } else if (raw.includes('.') && /^\d{1,3}(\.\d{3})+$/.test(raw)) {
                    raw = raw.replace(/\./g, '');
                } else {
                    raw = raw.replace(',', '.');
                }

                if (raw === '' || isNaN(parseFloat(raw))) {
                    return '0';
                }

                return raw;
            }

            function formatMoneyID(num) {
                if (num % 1 === 0) {
                    return num.toLocaleString('id-ID', {
                        minimumFractionDigits: 0,
                        maximumFractionDigits: 0
                    });
                }

                return num.toLocaleString('id-ID', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }

            // function calculateSalePrice(row) {
            //     const fixedCostInput = row.find('input[name*="[fixed_cost]"]');
            //     const marginInput = row.find('input[name*="[margin]"]');
            //     const salePriceInput = row.find('input[name*="[sale_price]"]');

            //     const fixedCost = parseFloat(normalizeMoneyValue(fixedCostInput.val())) || 0;
            //     const margin = parseFloat(normalizeMoneyValue(marginInput.val())) || 0;

            //     const salePrice = fixedCost + margin;

            //     salePriceInput.val(formatMoneyID(salePrice));
            //     salePriceInput[0].dataset.raw = salePrice.toString();

            //     removeError(salePriceInput);
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

            // function calculateUnitPrices() {
            //     const baseRow = getBaseRatioOneRow();

            //     if (!baseRow) return;

            //     const baseFixedCostInput = baseRow.find('input[name*="[fixed_cost]"]');
            //     const baseMarginInput = baseRow.find('input[name*="[margin]"]');

            //     const baseFixedCost = parseFloat(normalizeMoneyValue(baseFixedCostInput.val())) || 0;
            //     const baseMargin = parseFloat(normalizeMoneyValue(baseMarginInput.val())) || 0;

            //     $('#productUnitBody tr').each(function() {
            //         const row = $(this);

            //         const ratioInput = row.find('.conversion-input');
            //         const fixedCostInput = row.find('input[name*="[fixed_cost]"]');
            //         const marginInput = row.find('input[name*="[margin]"]');
            //         const salePriceInput = row.find('input[name*="[sale_price]"]');

            //         const ratio = parseFloat(ratioInput.val().replace(',', '.')) || 0;
            //         if (ratio <= 0) return;

            //         let fixedCost;
            //         let margin;

            //         if (ratio === 1) {
            //             fixedCost = baseFixedCost;
            //             margin = baseMargin;
            //         } else {
            //             fixedCost = baseFixedCost / ratio;
            //             margin = baseMargin / ratio;
            //         }

            //         const salePrice = fixedCost + margin;

            //         fixedCostInput.val(formatMoneyID(fixedCost));
            //         fixedCostInput[0].dataset.raw = fixedCost.toString();

            //         marginInput.val(formatMoneyID(margin));
            //         marginInput[0].dataset.raw = margin.toString();

            //         salePriceInput.val(formatMoneyID(salePrice));
            //         salePriceInput[0].dataset.raw = salePrice.toString();

            //         removeError(fixedCostInput);
            //         removeError(marginInput);
            //         removeError(salePriceInput);
            //     });
            // }

            function calculateUnitPrices() {
                const baseRow = getBaseRatioOneRow();

                if (!baseRow) return;

                const baseFixedCostInput = baseRow.find('input[name*="[fixed_cost]"]');
                const baseFixedCost = parseFloat(normalizeMoneyValue(baseFixedCostInput.val())) || 0;

                $('#productUnitBody tr').each(function() {
                    const row = $(this);

                    const ratioInput = row.find('.conversion-input');
                    const fixedCostInput = row.find('input[name*="[fixed_cost]"]');
                    const marginInput = row.find('input[name*="[margin]"]');
                    const salePriceInput = row.find('input[name*="[sale_price]"]');

                    const ratio = parseFloat(ratioInput.val().replace(',', '.')) || 0;
                    if (ratio <= 0) return;

                    const fixedCost = ratio === 1 ? baseFixedCost : baseFixedCost / ratio;
                    const margin = parseFloat(normalizeMoneyValue(marginInput.val())) || 0;
                    const salePrice = fixedCost + margin;

                    fixedCostInput.val(formatMoneyID(fixedCost));
                    fixedCostInput[0].dataset.raw = fixedCost.toString();

                    salePriceInput.val(formatMoneyID(salePrice));
                    salePriceInput[0].dataset.raw = salePrice.toString();

                    removeError(fixedCostInput);
                    removeError(salePriceInput);
                });
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
                document
                    .querySelectorAll(
                        'input[name*="[fixed_cost]"], input[name*="[margin]"], input[name*="[sale_price]"]')
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

            function syncBaseUnitConversion() {
                const baseUnitId = $('#base_unit_id').val();

                $('.unit-select').each(function() {
                    const row = $(this).closest('tr');
                    const conversionInput = row.find('.conversion-input');

                    // if ($(this).val() === baseUnitId && baseUnitId !== '') {
                    //     conversionInput.val('1');
                    //     conversionInput.prop('readonly', true);
                    // } else {
                    //     conversionInput.prop('readonly', false);
                    // }
                    if ($(this).val() === baseUnitId && baseUnitId !== '') {
                        removeError(conversionInput);
                    }
                });
            }

            $('#base_unit_id').on('change', syncBaseUnitConversion);
            $(document).on('change', '.unit-select', syncBaseUnitConversion);

            bindAllMoneyFields();
            bindConversionFields();
            syncBaseUnitConversion();
            calculateUnitPrices();

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

            // $(document).on('input', 'input[name*="[fixed_cost]"], input[name*="[margin]"]', function() {
            //     const row = $(this).closest('tr');
            //     calculateSalePrice(row);
            // });

            // $(document).on('input', '.conversion-input, input[name*="[fixed_cost]"], input[name*="[margin]"]',
            //     function() {
            //         calculateUnitPrices();
            //     });

            $(document).on('input', '.conversion-input', function() {
                calculateUnitPrices();
            });

            // $(document).on('input',
            //     'input[name*="[fixed_cost]"], input[name*="[margin]"], input[name*="[sale_price]"]',
            //     function() {
            //         const row = $(this).closest('tr');

            //         const fixedCostInput = row.find('input[name*="[fixed_cost]"]');
            //         const marginInput = row.find('input[name*="[margin]"]');
            //         const salePriceInput = row.find('input[name*="[sale_price]"]');

            //         const fixedCost = parseFloat(normalizeMoneyValue(fixedCostInput.val())) || 0;
            //         const margin = parseFloat(normalizeMoneyValue(marginInput.val())) || 0;
            //         const salePrice = parseFloat(normalizeMoneyValue(salePriceInput.val())) || 0;

            //         if ($(this).is(salePriceInput)) {
            //             const newMargin = salePrice - fixedCost;

            //             marginInput.val(formatMoneyID(newMargin));
            //             marginInput[0].dataset.raw = newMargin.toString();
            //         } else {
            //             const newSalePrice = fixedCost + margin;

            //             salePriceInput.val(formatMoneyID(newSalePrice));
            //             salePriceInput[0].dataset.raw = newSalePrice.toString();
            //         }

            //         calculateUnitPrices();
            //     });

            $(document).on('input', 'input[name*="[fixed_cost]"]', function() {
                const row = $(this).closest('tr');

                const marginInput = row.find('input[name*="[margin]"]');
                const salePriceInput = row.find('input[name*="[sale_price]"]');

                const fixedCost = parseFloat(normalizeMoneyValue($(this).val())) || 0;
                const margin = parseFloat(normalizeMoneyValue(marginInput.val())) || 0;
                const salePrice = fixedCost + margin;

                salePriceInput.val(formatMoneyID(salePrice));
                salePriceInput[0].dataset.raw = salePrice.toString();

                calculateUnitPrices();

                removeError($(this));
                removeError(salePriceInput);
            });

            $(document).on('input', 'input[name*="[margin]"]', function() {
                const row = $(this).closest('tr');

                const fixedCostInput = row.find('input[name*="[fixed_cost]"]');
                const salePriceInput = row.find('input[name*="[sale_price]"]');

                const fixedCost = parseFloat(normalizeMoneyValue(fixedCostInput.val())) || 0;
                const margin = parseFloat(normalizeMoneyValue($(this).val())) || 0;
                const salePrice = fixedCost + margin;

                salePriceInput.val(formatMoneyID(salePrice));
                salePriceInput[0].dataset.raw = salePrice.toString();

                removeError($(this));
                removeError(salePriceInput);
            });

            $(document).on('input', 'input[name*="[sale_price]"]', function() {
                removeError($(this));
            });

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
                    ['price'].forEach(id => {
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
