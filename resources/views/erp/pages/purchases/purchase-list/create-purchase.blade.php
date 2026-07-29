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
            grid-template-columns: minmax(380px, 4fr) 130px 130px 130px 130px 130px 52px;
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

        @media (max-width: 768px) {
            .card-body {
                padding: 6px !important;
            }

            .product-grid {
                grid-template-columns: 1fr 1fr 1fr;
                gap: 6px;
            }

            .product-col-span-2 {
                grid-column: span 3;
            }

            .product-grid .form-control {
                font-size: 14px !important;
                height: 36px !important;
                padding: 4px 8px !important;
            }

            .product-grid-header {
                display: none;
            }
        }
    </style>
@endpush

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Purchase List</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Purchase List</li>
                <li class="breadcrumb-item">Create Purchase List</li>
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
                    <a href="/erp/purchases/purchase-list" class="btn btn-light-brand">
                        <i class="feather-arrow-left me-2"></i>
                        <span>Back</span>
                    </a>
                    <button type="submit" class="btn btn-primary" form="purchaseForm">
                        <i class="feather-plus me-2"></i>
                        <span>Add Purchase List</span>
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
    <div class="main-content m-0 m-md-2 m-lg-2 p-0 p-md-0 p-lg-0 pt-1 pt-md-0">
        <div class="row">
            <div class="col-12">
                <form action="/erp/purchases/purchase-list/store" method="POST" id="purchaseForm"
                    enctype="multipart/form-data">
                    @csrf
                    @method('POST')
                    <div class="card stretch stretch-full">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="row mb-2 align-items-center">
                                        <div class="col-lg-2">
                                            <label for="purchase_number" class="fw-semibold">Invoice Number:</label>
                                        </div>
                                        <div class="col-lg-10 mb-0">
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="purchase_number"
                                                    name="purchase_number" value="{{ old('purchase_number') }}">
                                            </div>
                                        </div>
                                    </div>
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
                                            <label for="due_date_option" class="fw-semibold">Due Date:</label>
                                        </div>
                                        <div class="col-lg-10 mb-0">
                                            <div class="input-group">
                                                <select class="form-select" id="due_date_option" name="due_date_option"
                                                    style="font-size: 14px;" required>
                                                    <option value="none" selected>Tidak ada due date</option>
                                                    <option value="today">Hari ini</option>
                                                    <option value="1_week">1 Minggu</option>
                                                    <option value="1_month">1 Bulan</option>
                                                    <option value="3_months">3 Bulan</option>
                                                    <option value="custom">Custom</option>
                                                </select>
                                            </div>
                                            <div id="custom_due_date_wrapper" class="mt-1">
                                                <input type="date" class="form-control" id="custom_due_date"
                                                    name="custom_due_date" readonly>
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
                                    <input type="hidden" value="12" name="transaction_type" id="transaction_type">
                                    {{-- <div class="row mb-2 align-items-center">
                                        <div class="col-lg-2">
                                            <label for="transaction_type" class="fw-semibold">Purchase:</label>
                                        </div>
                                        <div class="col-lg-10 mb-0">
                                            <div class="input-group">
                                                <select class="form-select form-control max-select"
                                                    data-select2-selector="tag" id="transaction_type"
                                                    name="transaction_type">
                                                    <option value="12" data-bg="bg-success">Purchase Account</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div> --}}
                                    <div class="row mb-2 align-items-center">
                                        <div class="col-lg-2">
                                            <label for="stock_destination" class="fw-semibold">
                                                Stock Destination:
                                            </label>
                                        </div>
                                        <div class="col-lg-10 mb-0">
                                            <select class="form-select" name="stock_destination" id="stock_destination"
                                                required>
                                                <option value="production">Production</option>
                                                <option value="warehouse">Inventory Warehouse</option>
                                            </select>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-2">
                        <input type="hidden" name="inventory_warehouse_id" id="inventory_warehouse_id" value="1">

                        <div class="mb-2">
                            <h5 class="fw-bold">Add Products:</h5>
                        </div>

                        <div class="product-grid product-grid-header mb-1">
                            <div class="product-col-span-2">Product</div>
                            <div>Unit</div>
                            <div>Qty</div>
                            <div>Price</div>
                            <div>Freight</div>
                            <div>Total</div>
                            <div></div>
                        </div>

                        <div id="product_list">
                            <div class="product-item" data-index="0">
                                <div class="product-grid">
                                    <div class="form-group product-col-span-2">
                                        <label>Product</label>
                                        <select class="form-control select-product" data-select2-selector="status"
                                            name="product[]" id="product_0">
                                            <option value="" disabled selected hidden>Pilih produk</option>
                                            @foreach ($products as $product)
                                                <option value="{{ $product->id }}"
                                                    data-price="{{ $product->last_price ?? 0 }}"
                                                    data-freight="{{ $product->last_freight ?? 0 }}">
                                                    [{{ $product->sku }}] {{ $product->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Unit</label>
                                        <select name="product_unit_id[]" class="form-control product-unit">
                                            <option value="">Pilih unit</option>
                                        </select>
                                        <input type="hidden" name="unit_conversion_value[]"
                                            class="unit-conversion-value">
                                        <input type="hidden" name="unit_name[]" class="unit-name">
                                    </div>

                                    <div class="form-group">
                                        <label>Qty</label>
                                        <input type="text" inputmode="numeric" name="qty[]"
                                            class="form-control qty" value="0">
                                    </div>

                                    <div class="form-group">
                                        <label>Price</label>
                                        <input type="text" inputmode="numeric" name="price[]"
                                            class="form-control price" value="0">
                                    </div>

                                    <div class="form-group">
                                        <label>Freight</label>
                                        <input type="text" inputmode="numeric" name="freight[]"
                                            class="form-control freight" value="0">
                                    </div>

                                    <div class="form-group">
                                        <label>Total</label>
                                        <input type="hidden" name="total[]" class="form-control total">
                                        <input type="text" class="form-control total_display" readonly>
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
                                        <select class="form-control select-product" data-select2-selector="status"
                                            name="product[]">
                                            <option value="" disabled selected hidden>Pilih produk</option>
                                            @foreach ($products as $product)
                                                <option value="{{ $product->id }}"
                                                    data-price="{{ $product->last_price ?? 0 }}"
                                                    data-freight="{{ $product->last_freight ?? 0 }}">
                                                    [{{ $product->sku }}] {{ $product->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Unit</label>
                                        <select name="product_unit_id[]" class="form-control product-unit">
                                            <option value="">Pilih unit</option>
                                        </select>
                                        <input type="hidden" name="unit_conversion_value[]"
                                            class="unit-conversion-value">
                                        <input type="hidden" name="unit_name[]" class="unit-name">
                                    </div>

                                    <div class="form-group">
                                        <label>Qty</label>
                                        <input type="text" inputmode="numeric" name="qty[]"
                                            class="form-control qty" value="0">
                                    </div>

                                    <div class="form-group">
                                        <label>Price</label>
                                        <input type="text" inputmode="numeric" name="price[]"
                                            class="form-control price" value="0">
                                    </div>

                                    <div class="form-group">
                                        <label>Freight</label>
                                        <input type="text" inputmode="numeric" name="freight[]"
                                            class="form-control freight" value="0">
                                    </div>

                                    <div class="form-group">
                                        <label>Total</label>
                                        <input type="hidden" name="total[]" class="form-control total">
                                        <input type="text" class="form-control total_display" readonly>
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

                        {{-- grand total tetap pakai punyamu yang lama --}}
                        <div class="row justify-content-end">
                            <div class="col-lg-4 mt-2">
                                <table class="table table-bordered" id="tab_logic_total">
                                    <tbody>
                                        <tr>
                                            <th>Total Product</th>
                                            <td>
                                                <input type="text" id="total_amount_product_display"
                                                    class="form-control" readonly>
                                                <input type="hidden" name="total_amount_product"
                                                    id="total_amount_product">
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Total Freight</th>
                                            <td>
                                                <input type="text" id="total_amount_freight_display"
                                                    class="form-control" readonly>
                                                <input type="hidden" name="total_amount_freight"
                                                    id="total_amount_freight">
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Sub Total</th>
                                            <td>
                                                <input type="text" id="sub_total_display" class="form-control"
                                                    readonly>
                                                <input type="hidden" name="sub_total" id="sub_total">
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Tax (%)</th>
                                            <td>
                                                <input type="number" name="tax_percent" id="tax_percent"
                                                    class="form-control" value="0" min="0">
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Tax Amount</th>
                                            <td>
                                                <input type="text" id="tax_amount_display" class="form-control"
                                                    readonly>
                                                <input type="hidden" name="tax_amount" id="tax_amount">
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Grand Total</th>
                                            <td>
                                                <input type="text" id="total_amount_display"
                                                    class="form-control fw-bold" readonly>
                                                <input type="hidden" name="total_amount" id="total_amount">
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    {{-- <div class="card stretch stretch-full">
                    </div> --}}
                </form>
            </div>
        </div>
        <div class="col-lg-12">
            <div class="card stretch stretch-full">
                <div class="card-body p-0">
                    <div class="table-responsive">
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        const productsData = @json($productsJson);

        function formatRibuan(value) {
            if (value === null || value === undefined || value === '') return '';

            const num = parseFloat(value);
            if (isNaN(num)) return '';

            // Format ke Indonesia dengan maksimal 5 angka di belakang koma
            let [intPart, decPart] = num.toFixed(5).split('.');
            const formattedInt = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

            // Hapus nol di belakang koma biar lebih rapi
            decPart = decPart.replace(/0+$/, '');

            return decPart ? `${formattedInt},${decPart}` : formattedInt;
        }

        function unformatRibuan(value) {
            if (!value) return 0;

            // Hapus semua karakter kecuali angka, koma, titik, minus
            value = value.toString().replace(/[^0-9,.-]/g, '');

            // Jika mengandung koma → koma dianggap desimal → hapus titik ribuan
            if (value.includes(',')) {
                value = value.replace(/\./g, '').replace(',', '.');
            } else {
                // Jika tidak ada koma → hapus semua titik (anggap titik adalah pemisah ribuan)
                value = value.replace(/\./g, '');
            }

            const num = parseFloat(value);
            return isNaN(num) ? 0 : num;
        }

        function updateRowTotal(row) {
            const qty = parseFloat(unformatRibuan(row.find(".qty").val())) || 0;
            const price = parseFloat(unformatRibuan(row.find(".price").val())) || 0;
            const freight = parseFloat(unformatRibuan(row.find(".freight").val())) || 0;

            const total = qty * (price + freight);

            if (total > 0) {
                const formatted = formatRibuan(total.toFixed(2));
                row.find(".total").val(total.toFixed(2)); // untuk kirim ke backend
                row.find(".total_display").val(formatted); // untuk tampil di UI
            } else {
                row.find(".total").val('');
                row.find(".total_display").val('');
            }

            calc_total();
        }

        function fillProductUnits(row, units, defaultUnitId = null) {
            const unitSelect = row.find('.product-unit');

            unitSelect.empty().append('<option value="">Pilih unit</option>');

            row.find('.unit-conversion-value').val('');
            row.find('.unit-name').val('');

            if (!Array.isArray(units) || units.length === 0) return;

            units.forEach(function(unit) {
                const unitName = unit.unit_name || unit.name || 'PCS';
                const conversionValue = unit.conversion_value || 1;

                unitSelect.append(`
            <option value="${unit.id}"
                data-unit-id="${unit.unit_id}"
                data-unit-name="${unitName}"
                data-conversion-value="${conversionValue}">
                ${unitName}
            </option>
        `);
            });

            const defaultOption = unitSelect.find(`option[data-unit-id="${defaultUnitId}"]`).val();
            unitSelect.val(defaultOption || unitSelect.find('option:eq(1)').val()).trigger('change');
        }

        $(document).on('change', '.product-unit', function() {
            const row = $(this).closest('.product-item');
            const selected = $(this).find('option:selected');

            row.find('.unit-name').val(selected.data('unit-name') || '');
            row.find('.unit-conversion-value').val(selected.data('conversion-value') || 1);
        });

        // 4️⃣ Update fungsi calc_total untuk handle freight = 0
        function calc_total() {
            let subtotalProduct = 0,
                subtotalFreight = 0;

            $('.product-item').each(function() {
                const row = $(this);

                const qty = parseFloat(unformatRibuan(row.find('.qty').val())) || 0;
                const price = parseFloat(unformatRibuan(row.find('.price').val())) || 0;
                const freight = parseFloat(unformatRibuan(row.find('.freight').val())) || 0;

                subtotalProduct += qty * price;
                subtotalFreight += qty * freight;

                const totalRow = qty * (price + freight);

                row.find('.total').val(totalRow > 0 ? totalRow.toFixed(2) : '');
                row.find('.total_display').val(totalRow > 0 ? formatRibuan(totalRow.toFixed(2)) : '');
            });

            const taxPercent = parseFloat(unformatRibuan($("#tax_percent").val())) || 0;
            const taxAmount = (subtotalProduct * taxPercent) / 100;

            const totalProduct = subtotalProduct + taxAmount;
            const subTotal = subtotalProduct + subtotalFreight;
            const grandTotal = totalProduct + subtotalFreight;

            $("#total_amount_product").val(totalProduct.toFixed(2));
            $("#total_amount_freight").val(subtotalFreight.toFixed(2));
            $("#sub_total").val(subTotal.toFixed(2));
            $("#tax_amount").val(taxAmount.toFixed(2));
            $("#total_amount").val(grandTotal.toFixed(2));

            $("#total_amount_product_display").val(formatRibuan(totalProduct.toFixed(2)));
            $("#total_amount_freight_display").val(formatRibuan(subtotalFreight.toFixed(2)));
            $("#sub_total_display").val(formatRibuan(subTotal.toFixed(2)));
            $("#tax_amount_display").val(formatRibuan(taxAmount.toFixed(2)));
            $("#total_amount_display").val(formatRibuan(grandTotal.toFixed(2)));
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

        $(document).ready(function() {
            initSelect2('.select-product');
            initSelect2('#suppliers');

            $('.price, .freight').each(function() {
                let val = $(this).val().trim();

                if (val === '' || isNaN(unformatRibuan(val))) {
                    $(this).val('0');
                } else {
                    const num = unformatRibuan(val);
                    $(this).val(formatRibuan(num));
                }
            });

            $('.product-item').each(function() {
                const row = $(this);
                const selected = row.find('.select-product option:selected');

                if (selected.val()) {
                    const lastPrice = parseFloat(selected.data('price')) || 0;
                    const lastFreight = parseFloat(selected.data('freight')) || 0;

                    row.find('.price').val(formatRibuan(lastPrice.toFixed(2)));
                    row.find('.freight').val(formatRibuan(lastFreight.toFixed(2)));

                    updateRowTotal(row);
                }
            });

            calc_total();

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
                    calc_total();
                }
            });

            $(document).on('change', '.select-product', function() {
                const row = $(this).closest('.product-item');
                const selectedOption = $(this).find('option:selected');

                const productId = String($(this).val());
                const selectedProduct = productsData.find(p => String(p.id) === productId);
                const units = selectedProduct?.units || [];

                const lastPrice = parseFloat(selectedOption.data('price')) || 0;
                const lastFreight = parseFloat(selectedOption.data('freight')) || 0;

                row.find('.price').val(formatRibuan(lastPrice.toFixed(2)));
                row.find('.freight').val(formatRibuan(lastFreight.toFixed(2)));

                fillProductUnits(row, units, selectedProduct?.purchase_unit_id);
                updateRowTotal(row);
            });

            $(document).on('input', '.qty', function() {
                let val = $(this).val().replace(/\D/g, '');

                if (val) {
                    val = val.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                    $(this).val(val);
                } else {
                    $(this).val('');
                }

                updateRowTotal($(this).closest('.product-item'));
            });

            $(document).on('input', '.price', function() {
                let val = $(this).val().replace(/[^\d,]/g, '');
                const parts = val.split(',');
                let integerPart = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');

                val = parts.length > 1 ? `${integerPart},${parts[1].slice(0, 5)}` : integerPart;

                $(this).val(val);
                updateRowTotal($(this).closest('.product-item'));
            });

            $(document).on('input', '#tax_percent', calc_total);

            $(document).on('focus', '.freight', function() {
                const val = $(this).val().trim();
                const num = unformatRibuan(val);

                if (num === 0) {
                    $(this).val('');
                }
            });

            $(document).on('focus', '.price', function() {
                const val = $(this).val().trim();
                const num = unformatRibuan(val);

                if (num === 0) {
                    $(this).val('');
                }
            });

            $(document).on('focus', '.qty', function() {
                const val = $(this).val().replace(/\./g, '');
                const num = parseInt(val);

                if (num === 0) {
                    $(this).val('');
                }
            });

            $(document).on('focus', '#tax_percent', function() {
                const val = $(this).val().trim();
                const num = parseFloat(val);

                if (num === 0) {
                    $(this).val('');
                }
            });
        });

        function showError(el, message) {
            if ($(el).hasClass('select2-hidden-accessible')) {
                const select2Container = $(el).next('.select2');
                select2Container.next('.invalid-feedback').remove();

                const feedback = document.createElement('div');
                feedback.className = 'invalid-feedback d-block';
                feedback.textContent = message;
                select2Container[0].after(feedback);
            } else {
                el.classList.add('is-invalid');
                const container = el.closest('.input-group') || el.parentNode;
                const existing = container.querySelector('.invalid-feedback');
                if (existing) existing.remove();

                const feedback = document.createElement('div');
                feedback.className = 'invalid-feedback';
                feedback.textContent = message;
                feedback.style.display = 'block';
                container.appendChild(feedback);
            }
        }

        $(document).on("change input",
            "#purchase_number, #purchase_date, #suppliers, #transaction_type, select[name='product[]'], input[name='qty[]'], input[name='price[]']",
            function() {
                if ($(this).hasClass("select2-hidden-accessible")) {
                    $(this).next('.select2').next('.invalid-feedback').remove();
                } else {
                    this.classList.remove("is-invalid");
                    $(this).siblings(".invalid-feedback").remove();
                }
            });


        $('#purchaseForm').on('submit', function(e) {
            let isValid = true;
            let ok = true;
            const form = $(this);

            // 🔹 Hapus semua error dan clone lama dulu
            form.find('.is-invalid').removeClass('is-invalid');
            form.find('.invalid-feedback').remove();
            form.find('input[type="hidden"].submit-clone').remove();

            // 🔹 Validasi field utama
            const purchaseNumber = $('#purchase_number');
            if (!purchaseNumber.val().trim()) {
                isValid = false;
                showError(purchaseNumber[0], 'Nomor invoice wajib diisi');
            }

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

            // const editNote = $('#edit_note');
            // if (!editNote.val().trim()) {
            //     isValid = false;
            //     showError(editNote[0], 'Catatan edit wajib diisi');
            // }

            const transactionType = $('#transaction_type');
            if (!transactionType.val()) {
                isValid = false;
                showError(transactionType[0], 'Tipe transaksi wajib dipilih');
            }

            // 🔹 Validasi setiap baris produk
            $('.product-item').each(function() {
                const row = $(this);
                const product = row.find('select[name="product[]"]');
                const qty = row.find('input[name="qty[]"]');
                const unit = row.find('select[name="product_unit_id[]"]');
                const price = row.find('input[name="price[]"]');
                const freight = row.find('input[name="freight[]"]');

                if (!product.val()) {
                    isValid = false;
                    showError(product[0], 'Produk wajib dipilih');
                }

                if (!qty.val() || parseFloat(unformatRibuan(qty.val())) <= 0) {
                    isValid = false;
                    showError(qty[0], 'Qty wajib diisi dan harus lebih dari 0');
                }

                if (!price.val() || parseFloat(unformatRibuan(price.val())) <= 0) {
                    isValid = false;
                    showError(price[0], 'Harga wajib diisi dan harus lebih dari 0');
                }

                if (!unit.val()) {
                    isValid = false;
                    showError(unit[0], 'Unit wajib dipilih');
                }

                const freightVal = freight.val().trim();
                if (freightVal === '' || freightVal === null) {
                    isValid = false;
                    showError(freight[0], 'Freight harus diisi (minimal 0)');
                    freight.val('0');
                } else {
                    const freightNum = unformatRibuan(freightVal);
                    if (isNaN(freightNum) || freightNum < 0) {
                        isValid = false;
                        showError(freight[0], 'Freight harus berupa angka valid (minimal 0)');
                    }
                }
            });

            // 🔹 Jika tidak valid, cegah submit
            if (!isValid) {
                e.preventDefault();

                const firstError = form.find('.is-invalid, .select2 + .invalid-feedback').first();
                if (firstError.length) {
                    $('html, body').animate({
                        scrollTop: firstError.offset().top - 100
                    }, 300);
                }

                return; // stop di sini
            }

            $('.qty, .price, .freight, .total').each(function() {
                const val = $(this).val();
                const num = parseFloat(val.toString().replace(/\./g, '').replace(',', '.'));
                if (isNaN(num)) {
                    ok = false;
                    $(this).addClass('is-invalid');
                } else {
                    $(this).val(num.toFixed(5)); // ubah langsung sebelum submit
                }
            });

            if (!ok) {
                e.preventDefault();
                Swal.fire('Gagal', 'Ada angka tidak valid', 'error');
            }
        });

        $(document).on('input', '.freight', function() {
            let val = $(this).val().replace(/[^\d,]/g, '');

            if (val === '0' || val === '0,00') {
                $(this).val('0');
                updateRowTotal($(this).closest('.product-item'));
                return;
            }

            const parts = val.split(',');
            let integerPart = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            // ✅ maksimal 5 angka di belakang koma
            val = parts.length > 1 ? `${integerPart},${parts[1].slice(0, 5)}` : integerPart;
            $(this).val(val);
            updateRowTotal($(this).closest('.product-item'));
        });

        $(document).on('blur', '.price, .freight, .qty', function() {
            let val = $(this).val().trim();
            if (val === '' || val === null) {
                $(this).val('0');
            } else {
                const num = unformatRibuan(val);
                $(this).val(formatRibuan(num));
            }
            updateRowTotal($(this).closest('.product-item'));
        });

        $(document).ready(function() {
            const purchaseDateEl = $('#purchase_date');
            const dueDateSelect = $('#due_date_option');
            const customDueDate = $('#custom_due_date');

            function setDueDate() {
                const option = dueDateSelect.val();
                const purchaseDate = new Date(purchaseDateEl.val());

                if (!purchaseDate || isNaN(purchaseDate)) return;

                let dueDate = new Date(purchaseDate);
                switch (option) {
                    case 'today':
                        break;
                    case '1_week':
                        dueDate.setDate(dueDate.getDate() + 7);
                        break;
                    case '1_month':
                        dueDate.setMonth(dueDate.getMonth() + 1);
                        break;
                    case '3_months':
                        dueDate.setMonth(dueDate.getMonth() + 3);
                        break;
                    case 'custom':
                        customDueDate.prop('readonly', false);
                        return;
                    default:
                        customDueDate.val('');
                        customDueDate.prop('readonly', true);
                        return;
                }

                const formatted = dueDate.toISOString().split('T')[0];
                customDueDate.val(formatted);
                customDueDate.prop('readonly', true);
            }

            dueDateSelect.on('change', setDueDate);
            purchaseDateEl.on('change', setDueDate);
        });

        // 🔍 Cek invoice number saat user selesai mengetik
        $(document).on('blur', '#purchase_number', function() {
            const purchaseNumber = $(this).val().trim();
            if (!purchaseNumber) return;

            $.ajax({
                url: "{{ route('purchases.check-number') }}",
                type: 'GET',
                data: {
                    purchase_number: purchaseNumber
                },
                success: function(response) {
                    if (response.exists) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Nomor Invoice Sudah Terdaftar!',
                            text: 'Gunakan nomor invoice lain.',
                            confirmButtonText: 'OK'
                        });

                        $('#purchase_number')
                            .addClass('is-invalid')
                            .val('')
                            .focus();
                    } else {
                        $('#purchase_number').removeClass('is-invalid');
                    }
                },
                error: function(xhr, status, error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Mengecek Nomor Invoice!',
                        text: 'Silakan coba lagi.',
                        confirmButtonText: 'OK'
                    });
                }
            });
        });

        $(document).on('select2:open', () => {
            setTimeout(() => {
                document.querySelector('.select2-container--open .select2-search__field')?.focus();
            }, 50);
        });

        document.getElementById('stock_destination').addEventListener('change', function() {
            const statusField = document.getElementById('inventory_status');
            if (!statusField) return;

            if (this.value === 'warehouse') {
                statusField.value = 'Stock In';
            } else if (this.value === 'production') {
                statusField.value = 'Stock In Production';
            }
        });
    </script>
@endpush
