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
                <li class="breadcrumb-item">Edit Purchase Order</li>
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
                        <span>Update Purchase Order</span>
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
                @if ($hasPurchaseList)
                    <div class="alert alert-warning">
                        <i class="feather-alert-triangle me-2"></i>
                        PO ini sudah memiliki Purchase List. Supplier dikunci, produk yang sudah dibuatkan
                        Purchase List tidak dapat dihapus/diganti unitnya, dan qty-nya hanya boleh dinaikkan
                        (minimal sebesar qty yang sudah dibuatkan Purchase List).
                    </div>
                @endif
                <form action="/erp/purchases/purchase-orders/update/{{ $purchase->id }}" method="POST" id="purchaseForm">
                    @csrf
                    @method('PUT')
                    <div class="card stretch stretch-full">
                        <div class="card-body">
                            <div class="row mb-2">
                                <div class="col-lg-2">
                                    <label for="purchase_date" class="fw-semibold">Purchase Date:</label>
                                </div>
                                <div class="col-lg-10">
                                    <input type="datetime-local" class="form-control" id="purchase_date"
                                        name="purchase_date"
                                        value="{{ old('purchase_date', \Carbon\Carbon::parse($purchase->purchase_date)->format('Y-m-d\TH:i')) }}">
                                </div>
                            </div>

                            <div class="row mb-2">
                                <div class="col-lg-2">
                                    <label for="suppliers" class="fw-semibold">Supplier:</label>
                                </div>
                                <div class="col-lg-10">
                                    <select class="form-select form-control max-select" id="suppliers"
                                        name="{{ $hasPurchaseList ? 'suppliers_display' : 'suppliers' }}"
                                        {{ $hasPurchaseList ? 'disabled' : '' }}>
                                        <option disabled selected hidden>Choose supplier</option>
                                        @foreach ($suppliers as $supplier)
                                            <option value="{{ $supplier->id }}"
                                                {{ $supplier->id == $purchase->supplier_id ? 'selected' : '' }}>
                                                {{ $supplier->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @if ($hasPurchaseList)
                                        <input type="hidden" name="suppliers" value="{{ $purchase->supplier_id }}">
                                        <small class="text-muted">Supplier dikunci karena PO sudah memiliki Purchase List.</small>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card stretch stretch-full">
                        <div class="card-body">
                            <div class="mb-2">
                                <h5 class="fw-bold">Products:</h5>
                            </div>
                            <input type="hidden" name="inventory_warehouse_id" value="1">

                            <div class="product-grid product-grid-header mb-1">
                                <div class="product-col-span-2">Product</div>
                                <div>Unit</div>
                                <div>Qty</div>
                                <div></div>
                            </div>

                            <div id="product_list">
                                @forelse ($purchase->purchaseItems as $index => $item)
                                    @php
                                        // Qty item ini yang sudah dibuatkan Purchase List → jadi batas bawah edit.
                                        $allocated = (float) ($allocations[$item->id] ?? 0);
                                        $isLocked = $allocated > 0;
                                    @endphp
                                    <div class="product-item" data-index="{{ $index }}"
                                        data-locked="{{ $isLocked ? 1 : 0 }}">
                                        <div class="product-grid">
                                            <input type="hidden" name="purchase_item_ids[]" value="{{ $item->id }}">

                                            <div class="form-group product-col-span-2">
                                                <label>Product</label>
                                                <select class="form-control select-product"
                                                    name="{{ $isLocked ? 'product_display[]' : 'product[]' }}"
                                                    {{ $isLocked ? 'disabled' : '' }}>
                                                    <option value="" disabled
                                                        {{ !$item->product_id ? 'selected hidden' : '' }}>
                                                        Pilih produk
                                                    </option>
                                                    @foreach ($products as $product)
                                                        <option value="{{ $product->id }}"
                                                            {{ $item->product_id == $product->id ? 'selected' : '' }}>
                                                            [{{ $product->sku }}] {{ $product->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @if ($isLocked)
                                                    <input type="hidden" name="product[]" value="{{ $item->product_id }}">
                                                @endif
                                            </div>

                                            <div class="form-group">
                                                <label>Unit</label>
                                                <select class="form-control select-unit"
                                                    name="{{ $isLocked ? 'product_unit_id_display[]' : 'product_unit_id[]' }}"
                                                    {{ $isLocked ? 'disabled' : '' }}>
                                                    <option value="" data-name="Pcs" data-ratio="1" data-conversion="1"
                                                        {{ !$item->product_unit_conversion_id ? 'selected' : '' }}>
                                                        Default Unit
                                                    </option>

                                                    @foreach ($item->purchaseProduct?->unitConversions ?? [] as $conversion)
                                                        <option value="{{ $conversion->id }}"
                                                            data-name="{{ $conversion->unit->name ?? 'Pcs' }}"
                                                            data-ratio="{{ $conversion->ratio_value ?? 1 }}"
                                                            data-conversion="{{ $conversion->conversion_value ?? 1 }}"
                                                            {{ $item->product_unit_conversion_id == $conversion->id ? 'selected' : '' }}>
                                                            {{ $conversion->unit->name ?? 'Pcs' }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @if ($isLocked)
                                                    <input type="hidden" name="product_unit_id[]"
                                                        value="{{ $item->product_unit_conversion_id }}">
                                                @endif

                                                <input type="hidden" name="unit_name[]" class="unit-name"
                                                    value="{{ $item->unit_name ?? 'Pcs' }}">
                                                <input type="hidden" name="unit_conversion_value[]"
                                                    class="unit-conversion-value"
                                                    value="{{ $item->unit_conversion_value ?? 1 }}">
                                            </div>

                                            <div class="form-group">
                                                <label>Qty</label>
                                                <input type="text" inputmode="numeric" name="qty[]"
                                                    class="form-control qty" data-min-qty="{{ $allocated }}"
                                                    value="{{ number_format($item->quantity ?? 0, 0, ',', '.') }}">
                                                @if ($isLocked)
                                                    <small class="text-muted">
                                                        Min {{ number_format($allocated, 0, ',', '.') }}
                                                        {{ $item->unit_name ?? 'Pcs' }} (sudah dibuatkan PL)
                                                    </small>
                                                @endif
                                            </div>

                                            <div class="product-delete-col">
                                                <label>&nbsp;</label>
                                                <button type="button" class="btn btn-danger delete-row"
                                                    {{ $isLocked ? 'disabled' : '' }}
                                                    @if ($isLocked) title="Item sudah dibuatkan Purchase List" @endif>
                                                    <i class="feather-trash-2"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="product-item" data-index="0">
                                        <div class="product-grid">
                                            <input type="hidden" name="purchase_item_ids[]" value="">

                                            <div class="form-group product-col-span-2">
                                                <label>Product</label>
                                                <select class="form-control select-product" name="product[]">
                                                    <option value="" disabled selected hidden>Pilih produk</option>
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
                                                        Default Unit
                                                    </option>
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
                                @endforelse
                            </div>

                            <template id="product_item_template">
                                <div class="product-item" data-index="__index__">
                                    <div class="product-grid">
                                        <input type="hidden" name="purchase_item_ids[]" value="">

                                        <div class="form-group product-col-span-2">
                                            <label>Product</label>
                                            <select class="form-control select-product" name="product[]">
                                                <option value="" disabled selected hidden>Pilih produk</option>
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
                                                    Default Unit
                                                </option>
                                            </select>

                                            <input type="hidden" name="unit_name[]" class="unit-name" value="Pcs">
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
                                <button type="button" id="add_row" class="btn btn-md btn-primary">Add Items</button>
                            </div>
                        </div>
                    </div>

                    @include('erp.pages.partials.transaction-mobile-actions', [
                        'backUrl' => '/erp/purchases/purchase-orders',
                        'formId' => 'purchaseForm',
                        'submitLabel' => 'Update Purchase Order',
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

                const $purchaseUnit = $unitSelect.find('option').filter(function() {
                    return Number($(this).data('is-purchase-unit')) === 1;
                });

                if ($purchaseUnit.length) {
                    $unitSelect.val($purchaseUnit.first().val());
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

        $(document).on('select2:open', () => {
            setTimeout(() => {
                document.querySelector('.select2-container--open .select2-search__field')?.focus();
            }, 50);
        });

        $(document).ready(function() {
            initSelect2('.select-product');
            initSelect2('#suppliers');

            $('.product-item').each(function() {
                syncSelectedUnit($(this));
            });

            $(document).on('change', '.select-product', function() {
                const $row = $(this).closest('.product-item');
                loadProductUnits($row);
            });

            $(document).on('change', '.select-unit', function() {
                const $row = $(this).closest('.product-item');
                syncSelectedUnit($row);
            });

            // Format angka qty
            $(document).on('input', '.qty', function() {
                const val = $(this).val().replace(/\D/g, '');
                $(this).val(formatRibuan(val));
            });

            $('#add_row').on('click', function() {
                const list = $('#product_list');
                const index = list.find('.product-item').length;

                let template = $('#product_item_template').html();
                template = template.replace(/__index__/g, index);

                const $newRow = $(template);
                list.append($newRow);

                initSelect2($newRow.find('.select-product'));
            });

            // Delete row
            $(document).on('click', '.delete-row', function() {
                if ($('.product-item').length > 1) {
                    $(this).closest('.product-item').remove();
                }
            });

            // Form validation
            $('#purchaseForm').on('submit', function(e) {
                let isValid = true;
                $(this).find('.is-invalid').removeClass('is-invalid');
                $(this).find('.invalid-feedback').remove();

                const date = $('#purchase_date');
                const supplier = $('#suppliers');

                if (!date.val().trim()) {
                    isValid = false;
                    date.addClass('is-invalid');
                }

                if (!supplier.val()) {
                    isValid = false;
                    supplier.addClass('is-invalid');
                }

                $('.product-item').each(function() {
                    const $row = $(this);

                    const product = $row.find('select[name="product[]"]');
                    const qty = $row.find('input[name="qty[]"]');
                    const qtyValue = unformatRibuan(qty.val());

                    syncSelectedUnit($row);

                    if (!product.val()) {
                        isValid = false;
                        product.addClass('is-invalid');
                    }

                    if (!qty.val().trim() || qtyValue <= 0) {
                        isValid = false;
                        qty.addClass('is-invalid');
                    }

                    // Qty tidak boleh turun di bawah qty yang sudah dibuatkan Purchase List.
                    const minQty = parseFloat(qty.data('min-qty')) || 0;
                    if (minQty > 0 && qtyValue < minQty) {
                        isValid = false;
                        qty.addClass('is-invalid');
                        qty.after(
                            `<div class="invalid-feedback d-block">Qty minimal ${formatRibuan(minQty)} karena sudah dibuatkan Purchase List.</div>`
                        );
                    }
                });

                $('.qty').each(function() {
                    $(this).val(unformatRibuan($(this).val()));
                });

                if (!isValid) e.preventDefault();
            });
        });

        // $(document).on('select2:open', () => {
        //     setTimeout(() => {
        //         document.querySelector('.select2-container--open .select2-search__field')?.focus();
        //     }, 50);
        // });
    </script>
@endpush
