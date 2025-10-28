@extends('erp.layouts.main')

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Sale Orders</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Sale Orders</li>
                <li class="breadcrumb-item">Edit Sale Orders</li>
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
                    <a href="/erp/sales/sale-orders" class="btn btn-light-brand">
                        <i class="feather-arrow-left me-2"></i>
                        <span>Back</span>
                    </a>
                    <button type="submit" class="btn btn-primary" form="orderForm">
                        <i class="feather-plus me-2"></i>
                        <span>Edit Sale Order</span>
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
            <div class="col-12">
                <form action="/erp/sales/sale-orders/update/{{ $order->id }}" method="POST" id="orderForm">
                    @csrf
                    @method('PUT')
                    <div class="card stretch stretch-full">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="row mb-3 align-items-center">
                                        <div class="col-lg-2">
                                            <label for="order_date" class="fw-semibold">Order Date:</label>
                                        </div>
                                        <div class="col-lg-10 mb-0">
                                            <div class="input-group">
                                                <input type="date" class="form-control" id="order_date" name="order_date"
                                                    value="{{ old('order_date', isset($order->order_date) ? \Carbon\Carbon::parse($order->order_date)->format('Y-m-d') : date('Y-m-d')) }}">
                                            </div>
                                        </div>
                                    </div>
                                    <!--  -->
                                    <div class="row mb-3 align-items-center">
                                        <div class="col-lg-2">
                                            <label for="customers" class="fw-semibold">Customer:</label>
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
                                                    data-select2-selector="tag" id="customers" name="customers[]">
                                                    <option disabled selected hidden>Choose Customer</option>
                                                    @foreach ($customers as $index => $customer)
                                                        @php
                                                            $bg = $bgColors[$loop->index % count($bgColors)];
                                                        @endphp
                                                        <option value="{{ $customer->id }}" data-bg="{{ $bg }}"
                                                            {{ $customer->id == $order->customer_id ? 'selected' : '' }}>
                                                            {{ $customer->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mb-3 align-items-center">
                                        <div class="col-lg-2">
                                            <label for="addresses" class="fw-semibold">Address:</label>
                                        </div>
                                        <div class="col-lg-10 mb-0">
                                            <div class="input-group">
                                                <select class="form-select form-control max-select"
                                                    data-select2-selector="tag" id="addresses" name="address_id">
                                                    <option disabled hidden>Pilih alamat</option>
                                                    @if ($order->customer)
                                                        @foreach ($order->customer->addresses as $index => $address)
                                                            <option value="{{ $address->id }}"
                                                                data-map="{{ $address->google_maps }}"
                                                                {{ $order->address_id == $address->id ? 'selected' : '' }}>
                                                                Alamat ke-{{ $index + 1 }} - {{ $address->address }}
                                                            </option>
                                                        @endforeach
                                                    @endif
                                                </select>
                                            </div>
                                            <div id="google-maps-link" class="mt-2">
                                                @if ($order->address)
                                                    @if ($order->address->google_maps)
                                                        <a href="{{ $order->address->google_maps }}" target="_blank"
                                                            class="btn btn-sm btn-outline-primary mt-2">
                                                            Lihat di Google Maps
                                                        </a>
                                                    @endif
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card stretch stretch-full">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="mb-4">
                                        <h5 class="fw-bold">Add Products:</h5>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-bordered overflow-hidden" id="tab_logic">
                                            <thead>
                                                <tr class="single-item">
                                                    <th class="text-center wd-50">#</th>
                                                    <th class="text-center wd-450">Product</th>
                                                    <!-- <th class="text-center wd-200">Product Type</th> -->
                                                    <th class="text-center wd-100">Qty</th>
                                                    <th class="text-center wd-150">Price</th>
                                                    <th class="text-center wd-150">Total</th>
                                                    <th class="text-center wd-100">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tab_logic">
                                                @foreach ($order->orderItems as $index => $item)
                                                    <tr id="addr{{ $index }}">
                                                        <td>{{ $index + 1 }}</td>
                                                        <td>
                                                            <select class="form-control select-product" name="product[]"
                                                                id="product_{{ $index }}"
                                                                data-select2-selector="status"
                                                                data-selected-id="{{ $item->satuan == 'satuan' ? $item->product_id : $item->product_bundle_id }}"
                                                                data-selected-type="{{ $item->satuan }}">
                                                                <option value="" disabled hidden>Pilih produk
                                                                </option>
                                                                @foreach ($products as $prod)
                                                                    <option value="satuan_{{ $prod->id }}"
                                                                        {{ $item->satuan == 'satuan' && $item->product_id == $prod->id ? 'selected' : '' }}
                                                                        data-price="{{ $prod->price }}"
                                                                        data-discounts='@json($prod->discounts ?? [])'
                                                                        data-categories='@json($prod->categories ?? [])'
                                                                        data-type="satuan">
                                                                        {{ $prod->name }}
                                                                    </option>
                                                                @endforeach
                                                                @foreach ($productBundles as $bundle)
                                                                    <option value="bundle_{{ $bundle->id }}"
                                                                        {{ $item->satuan == 'bundle' && $item->product_bundle_id == $bundle->id ? 'selected' : '' }}
                                                                        data-price="{{ $bundle->price }}"
                                                                        data-discounts='@json(.$bundle->discounts ?? [])'
                                                                        data-categories='@json($bundle->categories ?? [])'
                                                                        data-type="bundle">
                                                                        {{ $bundle->name }} (Bundle)
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </td>
                                                        <input type="hidden" class="form-control product-type"
                                                            name="product_type[]" id="product_type_{{ $index }}"
                                                            value="{{ $item->satuan }}" readonly>
                                                        <td>
                                                            <input type="text" inputmode="numeric" name="qty[]"
                                                                class="form-control qty" id="qty_{{ $index }}"
                                                                value="{{ number_format($item->quantity, 0, ',', '.') }}">
                                                        </td>

                                                        </td>
                                                        <td>
                                                            @php
                                                                $isOwner = Auth::user()->role === 'Owner';
                                                            @endphp
                                                            <input type="text"
                                                                class="form-control price_before_discount_display"
                                                                @if (!$isOwner) readonly @endif
                                                                value="{{ number_format($item->price, 2, ',', '.') }}">
                                                            <input type="hidden" name="price_before_discount[]"
                                                                class="price_before_discount"
                                                                id="price_before_discount_{{ $index }}"
                                                                value="{{ number_format($item->price, 2, '.', '') }}">
                                                        </td>
                                                        <td>
                                                            <input type="text"
                                                                class="form-control total_before_discount_display" readonly
                                                                value="{{ number_format($item->price * $item->quantity, 2, ',', '.') }}">
                                                            <input type="hidden" name="total_before_discount[]"
                                                                class="total_before_discount"
                                                                id="total_before_discount_{{ $index }}"
                                                                value="{{ number_format($item->price * $item->quantity, 2, '.', '') }}">
                                                        </td>

                                                        <td class="text-center">
                                                            <div class="d-flex justify-content-center">
                                                                <button type="button" class="btn btn-danger delete-row">
                                                                    <i class="feather-trash"></i>
                                                                </button>
                                                            </div>
                                                        </td>

                                                        <input type="hidden" name="price_after_discount[]"
                                                            class="form-control price_after_discount"
                                                            id="price_after_discount_{{ $index }}"
                                                            value="{{ $item->price_after_discount }}">
                                                        <input type="hidden" name="total_after_discount[]"
                                                            class="form-control total_after_discount"
                                                            id="total_after_discount_{{ $index }}"
                                                            value="{{ $item->total_after_discount }}">
                                                    </tr>
                                                @endforeach
                                            </tbody>

                                        </table>
                                    </div>
                                    <div class="d-flex justify-content-end gap-2 mt-3">
                                        <!-- <button type="button" id="delete_row" class="btn btn-md bg-soft-danger text-danger">Delete</button> -->
                                        <button type="button" id="add_row" class="btn btn-md btn-primary">Add
                                            Items</button>
                                    </div>
                                </div>
                                <div class="col-lg-12">
                                    <div class="row justify-content-end">
                                        <div class="col-lg-4 mt-3">
                                            <div class="mb-4">
                                                <h5 class="fw-bold">Grand Total:</h5>
                                            </div>
                                            <div class="table-responsive">
                                                <table class="table table-bordered" id="tab_logic_total">
                                                    <tbody>
                                                        <tr>
                                                            <th class="fs-10 text-dark text-uppercase">Sub Total (Before
                                                                Discount)</th>
                                                            <td>
                                                                <input type="text" id="sub_total_display"
                                                                    class="form-control" readonly>
                                                                <input type="hidden" name="sub_total" id="sub_total">
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th class="fs-10 text-dark text-uppercase">Total Discount</th>
                                                            <td>
                                                                <input type="text" id="total_discount_display"
                                                                    class="form-control text-danger" readonly>
                                                                <input type="hidden" name="total_discount"
                                                                    id="total_discount">
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th class="fs-10 text-dark text-uppercase bg-gray-100">Grand
                                                                Total</th>
                                                            <td>
                                                                <input type="text" id="total_amount_display"
                                                                    class="form-control bg-gray-100 fw-700 text-success"
                                                                    readonly>
                                                                <input type="hidden" name="total_amount"
                                                                    id="total_amount">
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const customerAddresses = <?php echo json_encode(
            $customers->mapWithKeys(function ($customer) {
                return [
                    $customer->id => $customer->addresses->map(function ($address) {
                        return [
                            'id' => $address->id,
                            'address' => $address->address,
                            'google_maps' => $address->google_maps,
                        ];
                    }),
                ];
            }),
        ); ?>;

        const products = @json($productsJson);
        const bundles = @json($productBundlesJson);

        const allProducts = [
            ...products.map(p => ({
                ...p,
                type: 'satuan'
            })),
            ...bundles.map(b => ({
                ...b,
                type: 'bundle'
            })),
        ];

        function formatNumber(num) {
            return new Intl.NumberFormat('id-ID', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(num);
        }

        function populateProducts(selectEl, selectedId = null, selectedType = null) {
            $(selectEl).empty().append('<option value="" disabled selected hidden>Pilih produk</option>');
            allProducts.forEach(item => {
                const option = $('<option>', {
                        value: item.type + '_' + item.id,
                        text: `[${item.sku || '-'}] ${item.name}`
                    })
                    .data('price', item.price)
                    .data('discounts', item.discounts || [])
                    .data('categories', item.categories || [])
                    .data('type', item.type);

                if (selectedId && selectedType === item.type && selectedId == item.id) {
                    option.prop('selected', true);
                }

                $(selectEl).append(option);
            });
        }

        function calculateRow(row) {
            const selectedOption = row.find('select[name="product[]"] option:selected');
            const basePrice = parseFloat(selectedOption.data('price')) || 0;
            const discounts = selectedOption.data('discounts') || [];
            const categories = selectedOption.data('categories') || [];
            const qty = parseFloat(row.find('input[name="qty[]"]').val().replace(/\./g, '')) || 0;

            const priceBeforeDiscount = basePrice;
            const totalBeforeDiscount = basePrice * qty;
            let finalPrice = priceBeforeDiscount;
            let allDiscounts = [...discounts];

            categories.forEach(cat => {
                if (cat.discounts) allDiscounts = allDiscounts.concat(cat.discounts);
            });

            allDiscounts.forEach(discount => {
                let eligible = false;

                if (discount.apply_on === 'Product') {
                    if (discount.minimum_based_on === 'Quantity of Items' && qty >= discount.minimum_qty_or_amount)
                        eligible = true;
                    else if (discount.minimum_based_on === 'Purchase Amount' && totalBeforeDiscount >= discount
                        .minimum_qty_or_amount)
                        eligible = true;
                } else if (discount.apply_on === 'Category') {
                    let totalQtyCategory = 0;
                    let totalAmountCategory = 0;

                    $('select[name="product[]"]').each(function(i, el) {
                        const opt = $(el).find('option:selected');
                        const cats = opt.data('categories') || [];
                        const price = parseFloat(opt.data('price')) || 0;
                        const qtyVal = parseFloat($('input[name="qty[]"]').eq(i).val().replace(/\./g,
                            '')) || 0;
                        0;

                        if (cats.some(c => c.id === discount.category_id)) {
                            totalQtyCategory += qtyVal;
                            totalAmountCategory += price * qtyVal;
                        }
                    });

                    if (discount.minimum_based_on === 'Quantity of Items' && totalQtyCategory >= discount
                        .minimum_qty_or_amount)
                        eligible = true;
                    else if (discount.minimum_based_on === 'Purchase Amount' && totalAmountCategory >= discount
                        .minimum_qty_or_amount)
                        eligible = true;
                }

                if (eligible) {
                    if (discount.type === 'Percentage')
                        finalPrice = priceBeforeDiscount - (priceBeforeDiscount * (discount.amount / 100));
                    else
                        finalPrice = Math.max(0, priceBeforeDiscount - discount.amount);
                }
            });

            const totalAfterDiscount = finalPrice * qty;

            row.find('input.price_before_discount').val(priceBeforeDiscount.toFixed(2));
            row.find('input.total_before_discount').val(totalBeforeDiscount.toFixed(2));
            row.find('input.price_after_discount').val(finalPrice.toFixed(2));
            row.find('input.total_after_discount').val(totalAfterDiscount.toFixed(2));

            row.find('input.price_before_discount_display').val(formatNumber(priceBeforeDiscount));
            row.find('input.total_before_discount_display').val(formatNumber(totalBeforeDiscount));
        }

        function recalcAllRows() {
            $('tr[id^="addr"]').each(function() {
                calculateRow($(this));
            });
            calcTotalSummary();
        }

        function calcTotalSummary() {
            let subTotal = 0,
                totalAfterDiscount = 0;

            $(".total_before_discount").each(function() {
                subTotal += parseFloat($(this).val()) || 0;
            });
            $(".total_after_discount").each(function() {
                totalAfterDiscount += parseFloat($(this).val()) || 0;
            });

            const totalDiscount = subTotal - totalAfterDiscount;

            $("#sub_total").val(subTotal.toFixed(2));
            $("#total_discount").val(totalDiscount.toFixed(2));
            $("#total_amount").val(totalAfterDiscount.toFixed(2));

            $("#sub_total_display").val(formatNumber(subTotal));
            $("#total_discount_display").val(formatNumber(totalDiscount));
            $("#total_amount_display").val(formatNumber(totalAfterDiscount));
        }

        function initSelect2() {
            $('[data-select2-selector="status"]').select2({
                placeholder: 'Pilih produk',
                width: '100%'
            }).each(function() {
                const selectedVal = $(this).val();
                const selectedType = $(this).closest('tr').find('.product-type').val();
                const selectedId = selectedVal ? selectedVal.split('_')[1] : null;
                populateProducts(this, selectedId, selectedType);
            });
        }

        $(document).ready(function() {
            initSelect2();
            recalcAllRows();

            let rowCount = $('#tab_logic tbody tr').length;

            $('#add_row').on('click', function() {
                const tableBody = $('#tab_logic tbody');
                const newRow = $(`
                <tr id="addr${rowCount}">
                    <td>${rowCount + 1}</td>
                    <td>
                        <select class="form-control select-product" name="product[]" data-select2-selector="status"></select>
                    </td>
                    <input type="hidden" name="product_type[]" class="product-type" readonly>
                    <td><input type="text" inputmode="numeric" name="qty[]" class="form-control qty" value="1"></td>
                    <td><input type="text" class="form-control price_before_discount_display" readonly>
                        <input type="hidden" name="price_before_discount[]" class="price_before_discount"></td>
                    <td><input type="text" class="form-control total_before_discount_display" readonly>
                        <input type="hidden" name="total_before_discount[]" class="total_before_discount"></td>
                    <td class="text-center">
                        <button type="button" class="btn btn-danger delete-row">
                            <i class="feather-trash"></i>
                        </button>
                    </td>
                    <input type="hidden" name="price_after_discount[]" class="price_after_discount">
                    <input type="hidden" name="total_after_discount[]" class="total_after_discount">
                </tr>
            `);

                tableBody.append(newRow);
                populateProducts(newRow.find('.select-product'));
                newRow.find('.select-product').select2({
                    placeholder: 'Pilih produk',
                    width: '100%'
                });
                rowCount++;
            });

            $(document).on('click', '.delete-row', function() {
                $(this).closest('tr').remove();
                $('#tab_logic tbody tr').each(function(i, el) {
                    $(el).find('td:first').text(i + 1);
                });
                recalcAllRows();
            });

            $(document).on('change', 'select[name="product[]"]', function() {
                const row = $(this).closest('tr');
                const type = $(this).find('option:selected').data('type') || '';
                row.find('.product-type').val(type);
                recalcAllRows();
            });

            $(document).on('input', 'input[name="qty[]"]', function() {
                let val = $(this).val().replace(/\D/g, '');
                if (val.length > 12) val = val.substring(0, 12);
                $(this).val(val.replace(/\B(?=(\d{3})+(?!\d))/g, '.'));
                recalcAllRows();
            });

            $('#orderForm').on('submit', function() {
                $('input[name="qty[]"]').each(function() {
                    $(this).val($(this).val().replace(/\./g, ''));
                });
            });
        });
    </script>
@endpush
