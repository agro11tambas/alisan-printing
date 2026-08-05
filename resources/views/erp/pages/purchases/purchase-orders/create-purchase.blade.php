@extends('erp.layouts.main')

@push('styles')
    <style>
        /* 🔹 Perbesar font pada select bawaan (kalau belum diinisialisasi Select2) */
        .select-product {
            font-size: 16px !important;
            padding: 8px 10px !important;
            height: 42px !important;
        }

        /* 🔹 Perbesar font di dalam Select2 container */
        .select2-container--bootstrap-5 .select2-selection--single {
            height: 42px !important;
            font-size: 16px !important;
            line-height: 42px !important;
        }

        /* 🔹 Perbesar teks hasil pilihan */
        .select2-selection__rendered {
            font-size: 16px !important;
            line-height: 42px !important;
            padding-left: 10px !important;
        }

        /* 🔹 Perbesar teks di dropdown Select2 */
        .select2-results__option {
            font-size: 16px !important;
            padding: 4px 8px !important;
        }

        /* 🔹 Perbesar ikon dropdown */
        .select2-selection__arrow {
            height: 42px !important;
            right: 10px !important;
        }

        .product-item {
            border-radius: 12px;
            margin-bottom: 12px;
        }

        .product-grid {
            display: grid;
            grid-template-columns: minmax(420px, 4fr) 180px 150px 52px;
            gap: 10px;
            align-items: start;
        }

        .product-grid-header {
            font-size: 14px;
            color: #64748b;
            font-weight: 500;
        }

        .product-col-span-2 {
            grid-column: span 1;
        }

        .product-grid .form-group {
            display: flex;
            flex-direction: column;
        }

        .product-grid .form-group>label,
        .product-delete-col>label {
            display: none !important;
        }

        .product-grid .form-control,
        .product-grid .select2-container--bootstrap-5 .select2-selection--single {
            height: 44px !important;
        }

        .product-delete-col .delete-row {
            height: 44px;
            width: 44px;
            padding: 0;
        }
        @include('erp.pages.partials.transaction-form-mobile-styles')
    </style>
@endpush

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Purchase Order</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Purchase Order</li>
                <li class="breadcrumb-item">Create Purchase Order</li>
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
                    <a href="/erp/purchases/purchase-orders" class="btn btn-light-brand">
                        <i class="feather-arrow-left me-2"></i>
                        <span>Back</span>
                    </a>
                    <button type="submit" class="btn btn-primary" form="purchaseForm">
                        <i class="feather-plus me-2"></i>
                        <span>Add Purchase Order</span>
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
    <div class="main-content transaction-form-page m-0 m-md-2 m-lg-2 p-0 p-md-0 p-lg-0 pt-1 pt-md-0">
        <div class="row">
            <div class="col-12">
                <form action="/erp/purchases/purchase-orders/store" method="POST" id="purchaseForm"
                    enctype="multipart/form-data">
                    @csrf
                    @method('POST')
                    <div class="card stretch stretch-full">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="row mb-2 align-items-center">
                                        <div class="col-lg-2">
                                            <label for="purchase_date" class="fw-semibold">Purchase Date:</label>
                                        </div>
                                        <div class="col-lg-10 mb-0">
                                            <div class="input-group">
                                                <input type="datetime-local" class="form-control" id="purchase_date"
                                                    name="purchase_date" value="{{ date('Y-m-d\TH:i') }}">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mb-2 align-items-center">
                                        <div class="col-lg-2">
                                            <label for="suppliers" class="fw-semibold">Supplier:</label>
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
                                                <select class="form-select form-control max-select"
                                                    data-select2-selector="tag" id="suppliers" name="suppliers">
                                                    <option disabled selected hidden>Choose upplier</option>
                                                    @foreach ($suppliers as $index => $supplier)
                                                        @php
                                                            $bg = $bgColors[$index % count($bgColors)];
                                                        @endphp
                                                        <option value="{{ $supplier->id }}" data-bg="{{ $bg }}">
                                                            {{ $supplier->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-2">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="mb-2">
                                    <h5 class="fw-bold">Add Products:</h5>
                                </div>
                                <input type="hidden" name="inventory_warehouse_id" id="inventory_warehouse_id"
                                    value="1">

                                <div class="product-grid product-grid-header mb-1">
                                    <div class="product-col-span-2">Product</div>
                                    <div>Unit</div>
                                    <div>Qty</div>
                                    <div></div>
                                </div>

                                <div id="product_list">
                                    <div class="product-item" data-index="0">
                                        <div class="product-grid">
                                            <div class="form-group product-col-span-2">
                                                <label>Product</label>
                                                <select class="form-control select-product" data-select2-selector="status"
                                                    name="product[]" id="product_0">
                                                    <option value="" disabled selected hidden>Pilih produk
                                                    </option>
                                                    @foreach ($products as $product)
                                                        <option value="{{ $product->id }}">
                                                            [{{ $product->sku }}] {{ $product->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="form-group">
                                                <label>Unit</label>
                                                <select class="form-control select-unit" name="product_unit_id[]"
                                                    id="product_unit_0">
                                                    <option value="" data-name="Pcs" data-ratio="1" data-conversion="1" selected>
                                                        Default Unit</option>
                                                </select>

                                                <input type="hidden" name="unit_name[]" class="unit-name"
                                                    value="Pcs">
                                                <input type="hidden" name="unit_conversion_value[]"
                                                    class="unit-conversion-value" value="1">
                                            </div>

                                            <div class="form-group">
                                                <label>Qty</label>
                                                <input type="text" inputmode="numeric" name="qty[]"
                                                    class="form-control qty" value="0">
                                            </div>

                                            <div class="product-delete-col">
                                                <label>&nbsp;</label>
                                                <button type="button" class="btn btn-danger delete-row">
                                                    <i class="feather-trash-2"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <template id="product_item_template">
                                    <div class="product-item" data-index="__index__">
                                        <div class="product-grid">
                                            <div class="form-group product-col-span-2">
                                                <label>Product</label>
                                                <select class="form-control select-product" name="product[]">
                                                    <option value="" disabled selected hidden>Pilih produk
                                                    </option>
                                                    @foreach ($products as $product)
                                                        <option value="{{ $product->id }}">
                                                            [{{ $product->sku }}] {{ $product->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="form-group">
                                                <label>Unit</label>
                                                <select class="form-control select-unit" name="product_unit_id[]">
                                                    <option value="" data-name="Pcs" data-ratio="1" data-conversion="1" selected>
                                                        Default Unit</option>
                                                </select>

                                                <input type="hidden" name="unit_name[]" class="unit-name"
                                                    value="Pcs">
                                                <input type="hidden" name="unit_conversion_value[]"
                                                    class="unit-conversion-value" value="1">
                                            </div>

                                            <div class="form-group">
                                                <label>Qty</label>
                                                <input type="text" inputmode="numeric" name="qty[]"
                                                    class="form-control qty" value="0">
                                            </div>

                                            <div class="product-delete-col">
                                                <label>&nbsp;</label>
                                                <button type="button" class="btn btn-danger delete-row">
                                                    <i class="feather-trash-2"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <div class="d-flex justify-content-end mt-2">
                                    <button type="button" id="add_row" class="btn btn-md btn-primary">Add
                                        Items</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- <div class="card stretch stretch-full">
                    </div> --}}
                    @include('erp.pages.partials.transaction-mobile-actions', [
                        'backUrl' => '/erp/purchases/purchase-orders',
                        'formId' => 'purchaseForm',
                        'submitLabel' => 'Store Purchase Order',
                    ])
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @php
        $productUnitsJson = $products->mapWithKeys(function ($product) {
            return [
                $product->id => $product->unitConversions
                    ->map(function ($conversion) use ($product) {
                        return [
                            'id' => $conversion->id,
                            'unit_name' => optional($conversion->unit)->name ?? 'Pcs',
                            'ratio_value' => $conversion->ratio_value ?? 1,
                            'conversion_value' => $conversion->conversion_value ?? 1,
                            'is_purchase_unit' => (int) $conversion->unit_id === (int) $product->purchase_unit_id,
                        ];
                    })
                    ->values(),
            ];
        });
    @endphp

    <script>
        const productUnits = @json($productUnitsJson);

        function initSelect2(el) {
            $(el).select2({
                theme: 'bootstrap-5',
                placeholder: 'Pilih opsi',
                width: '100%',
                matcher: (params, data) => {
                    if ($.trim(params.term) === '') return data;
                    return data.text.toLowerCase().includes(params.term.toLowerCase()) ? data : null;
                }
            });
        }

        function formatRibuan(num) {
            if (num === null || num === undefined || num === '') return '';
            num = num.toString().replace(/\D/g, '');
            return num.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        function unformatRibuan(str) {
            if (!str) return 0;
            return parseFloat(str.toString().replace(/\./g, '')) || 0;
        }

        function showError(el, message) {
            const $el = $(el);

            if ($el.hasClass('select2-hidden-accessible')) {
                const $container = $el.siblings('.select2');
                $container.next('.invalid-feedback').remove();

                const feedback = $('<div class="invalid-feedback d-block"></div>').text(message);
                $container.after(feedback);
            } else {
                $el.addClass('is-invalid');

                let $container = $el.closest('.input-group');
                if ($container.length === 0) $container = $el.parent();

                $container.find('.invalid-feedback').remove();

                const feedback = $('<div class="invalid-feedback d-block"></div>').text(message);
                $container.append(feedback);
            }
        }

        function loadProductUnits($row) {
            const productId = $row.find('.select-product').val();
            const $unitSelect = $row.find('.select-unit');
            const $unitName = $row.find('.unit-name');
            const $unitConversionValue = $row.find('.unit-conversion-value');

            $unitSelect.empty();

            if (productId && productUnits[productId]) {
                productUnits[productId].forEach(unit => {
                    $unitSelect.append(`
                <option value="${unit.id}"
                    data-name="${unit.unit_name}"
                    data-ratio="${unit.ratio_value}"
                    data-conversion="${unit.conversion_value}"
                    data-is-purchase-unit="${unit.is_purchase_unit ? 1 : 0}">
                    ${unit.unit_name}
                </option>
            `);
                });

                const $largestUnit = $unitSelect.find('option').filter(function() {
                    return Number($(this).data('is-purchase-unit')) === 1;
                });

                if ($largestUnit.length) {
                    $unitSelect.val($largestUnit.first().val());
                } else {
                    const $fallbackUnit = $unitSelect.find('option').toArray().reduce((largest, option) => {
                        if (!largest) return option;

                        return Number($(option).data('conversion')) >
                            Number($(largest).data('conversion')) ? option : largest;
                    }, null);

                    $unitSelect.val($fallbackUnit?.value ?? '');
                }

                syncSelectedUnit($row);
            } else {
                $unitSelect.append(`
            <option value="" data-name="Pcs" data-ratio="1" data-conversion="1" selected>
                Default Unit
            </option>
        `);

                $unitName.val('Pcs');
                $unitConversionValue.val(1);
            }
        }

        function syncSelectedUnit($row) {
            const selected = $row.find('.select-unit option:selected');

            $row.find('.unit-name').val(selected.data('name') || 'Pcs');
            $row.find('.unit-conversion-value').val(selected.data('conversion') || 1);
        }

        $(document).ready(function() {
            initSelect2('.select-product');
            initSelect2('#suppliers');

            $(document).on('select2:open', () => {
                setTimeout(() => {
                    document.querySelector('.select2-container--open .select2-search__field')
                        ?.focus();
                }, 50);
            });

            $(document).on('change', '.select-product', function() {
                const $row = $(this).closest('.product-item');
                loadProductUnits($row);
            });

            $(document).on('change', '.select-unit', function() {
                const $row = $(this).closest('.product-item');
                syncSelectedUnit($row);
            });

            $(document).on('input', '.qty', function() {
                const val = $(this).val().replace(/\D/g, '');
                $(this).val(formatRibuan(val));
            });

            $(document).on('focus', '.qty', function() {
                const currentVal = unformatRibuan($(this).val());
                if (currentVal === 0) {
                    $(this).val('');
                }
            });

            $(document).on('blur', '.qty', function() {
                const val = $(this).val().trim();
                if (val === '' || val === null || val === undefined) {
                    $(this).val('0');
                }
            });

            $(document).on("change input",
                "#purchase_date, #suppliers, select[name='product[]'], select[name='product_unit_id[]'], input[name='qty[]']",
                function() {
                    if ($(this).hasClass("select2-hidden-accessible")) {
                        $(this).siblings('.select2').next('.invalid-feedback').remove();
                    } else {
                        this.classList.remove("is-invalid");
                        $(this).siblings(".invalid-feedback").remove();
                    }
                }
            );

            $('#add_row').on('click', function() {
                const list = $('#product_list');
                const index = list.find('.product-item').length;

                let template = $('#product_item_template').html();
                template = template.replace(/__index__/g, index);

                const $newRow = $(template);
                list.append($newRow);

                initSelect2($newRow.find('.select-product'));
            });

            $(document).on('click', '.delete-row', function() {
                if ($('.product-item').length > 1) {
                    $(this).closest('.product-item').remove();
                }
            });

            $('#purchaseForm').on('submit', function(e) {
                let isValid = true;

                $(this).find('.is-invalid').removeClass('is-invalid');
                $(this).find('.invalid-feedback').remove();

                const purchaseDate = $('#purchase_date');
                if (!purchaseDate.val().trim()) {
                    isValid = false;
                    showError(purchaseDate[0], 'Tanggal pembelian wajib diisi');
                }

                const supplier = $('#suppliers');
                if (!supplier.val()) {
                    isValid = false;
                    showError(supplier[0], 'Supplier wajib dipilih');
                }

                $('.product-item').each(function() {
                    const $row = $(this);
                    const product = $row.find('select[name="product[]"]');
                    const qty = $row.find('input[name="qty[]"]');
                    const qtyValue = unformatRibuan(qty.val());

                    syncSelectedUnit($row);

                    if (!product.val()) {
                        isValid = false;
                        showError(product[0], 'Produk wajib dipilih');
                    }

                    if (!qty.val().trim() || qtyValue <= 0) {
                        isValid = false;
                        showError(qty[0], 'Qty wajib diisi');
                    }
                });

                $('.qty').each(function() {
                    $(this).val(unformatRibuan($(this).val()));
                });

                if (!isValid) {
                    e.preventDefault();
                }
            });
        });
    </script>
@endpush
