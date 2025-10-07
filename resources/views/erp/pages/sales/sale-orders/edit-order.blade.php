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
                                            <input type="date" class="form-control" id="order_date" name="order_date" value="{{ old('order_date', isset($order->order_date) ? \Carbon\Carbon::parse($order->order_date)->format('Y-m-d') : date('Y-m-d')) }}">
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
                                            $bgColors = ['bg-danger', 'bg-warning', 'bg-primary', 'bg-indigo', 'bg-success'];
                                            @endphp
                                            <select class="form-select form-control max-select" data-select2-selector="tag" id="customers" name="customers[]">
                                                <option disabled selected hidden>Choose Customer</option>
                                                @foreach ($customers as $index => $customer)
                                                @php
                                                $bg = $bgColors[$loop->index % count($bgColors)];
                                                @endphp
                                                <option value="{{ $customer->id }}" data-bg="{{ $bg }}" {{ $customer->id == $order->customer_id ? 'selected' : '' }}>{{ $customer->name }}</option>
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
                                            <select class="form-select form-control max-select" data-select2-selector="tag" id="addresses" name="address_id">
                                                <option disabled hidden>Pilih alamat</option>
                                                @if($order->customer)
                                                @foreach($order->customer->addresses as $index => $address)
                                                <option value="{{ $address->id }}" data-map="{{ $address->google_maps }}"
                                                    {{ $order->address_id == $address->id ? 'selected' : '' }}>
                                                    Alamat ke-{{ $index + 1 }} - {{ $address->address }}
                                                </option>
                                                @endforeach
                                                @endif
                                            </select>
                                        </div>
                                        <div id="google-maps-link" class="mt-2">
                                            @if($order->address)
                                            @if($order->address->google_maps)
                                            <a href="{{ $order->address->google_maps }}" target="_blank" class="btn btn-sm btn-outline-primary mt-2">
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
                                            @foreach($order->orderItems as $index => $item)
                                            <tr id="addr{{ $index }}">
                                                <td>{{ $index + 1 }}</td>

                                                {{-- Product --}}
                                                <td>
                                                    <select class="form-control select-product" name="product[]" id="product_{{ $index }}" data-select2-selector="status"
                                                        data-selected-id="{{ $item->satuan == 'satuan' ? $item->product_id : $item->product_bundle_id }}"
                                                        data-selected-type="{{ $item->satuan }}">
                                                        <option value="" disabled hidden>Pilih produk</option>
                                                        @foreach($products as $prod)
                                                        <option value="satuan_{{ $prod->id }}"
                                                            {{ $item->satuan == 'satuan' && $item->product_id == $prod->id ? 'selected' : '' }}
                                                            data-price="{{ $prod->price }}"
                                                            data-discounts='@json($prod->discounts ?? [])'
                                                            data-categories='@json($prod->categories ?? [])'
                                                            data-type="satuan">
                                                            {{ $prod->name }}
                                                        </option>
                                                        @endforeach
                                                        @foreach($productBundles as $bundle)
                                                        <option value="bundle_{{ $bundle->id }}"
                                                            {{ $item->satuan == 'bundle' && $item->product_bundle_id == $bundle->id ? 'selected' : '' }}
                                                            data-price="{{ $bundle->price }}"
                                                            data-discounts='@json($bundle->discounts ?? [])'
                                                            data-categories='@json($bundle->categories ?? [])'
                                                            data-type="bundle">
                                                            {{ $bundle->name }} (Bundle)
                                                        </option>
                                                        @endforeach
                                                    </select>
                                                </td>

                                                <!-- <td>
                                                    </td> -->
                                                <input type="hidden" class="form-control product-type" name="product_type[]" id="product_type_{{ $index }}" value="{{ $item->satuan }}" readonly>
                                                <td><input type="number" name="qty[]" class="form-control qty" id="qty_{{ $index }}" min="1" value="{{ $item->quantity }}"></td>
                                                <td>
                                                    <input type="text" class="form-control price_before_discount_display" readonly
                                                        value="{{ number_format($item->price, 2, ',', '.') }}">
                                                    <input type="hidden" name="price_before_discount[]" class="price_before_discount"
                                                        id="price_before_discount_{{ $index }}" value="{{ number_format($item->price, 2, '.', '') }}">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control total_before_discount_display" readonly
                                                        value="{{ number_format($item->price * $item->quantity, 2, ',', '.') }}">
                                                    <input type="hidden" name="total_before_discount[]" class="total_before_discount"
                                                        id="total_before_discount_{{ $index }}" value="{{ number_format($item->price * $item->quantity, 2, '.', '') }}">
                                                </td>

                                                <td class="text-center">
                                                    <div class="d-flex justify-content-center">
                                                        <button type="button" class="btn btn-danger delete-row">
                                                            <i class="feather-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>

                                                {{-- Hidden fields --}}
                                                <input type="hidden" name="price_after_discount[]" class="form-control price_after_discount"
                                                    id="price_after_discount_{{ $index }}" value="{{ $item->price_after_discount }}">
                                                <input type="hidden" name="total_after_discount[]" class="form-control total_after_discount"
                                                    id="total_after_discount_{{ $index }}" value="{{ $item->total_after_discount }}">
                                            </tr>
                                            @endforeach
                                        </tbody>

                                    </table>
                                </div>
                                <div class="d-flex justify-content-end gap-2 mt-3">
                                    <!-- <button type="button" id="delete_row" class="btn btn-md bg-soft-danger text-danger">Delete</button> -->
                                    <button type="button" id="add_row" class="btn btn-md btn-primary">Add Items</button>
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
                                                        <th class="fs-10 text-dark text-uppercase">Sub Total (Before Discount)</th>
                                                        <td>
                                                            <input type="text" id="sub_total_display" class="form-control" readonly>
                                                            <input type="hidden" name="sub_total" id="sub_total">
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th class="fs-10 text-dark text-uppercase">Total Discount</th>
                                                        <td>
                                                            <input type="text" id="total_discount_display" class="form-control text-danger" readonly>
                                                            <input type="hidden" name="total_discount" id="total_discount">
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th class="fs-10 text-dark text-uppercase bg-gray-100">Grand Total</th>
                                                        <td>
                                                            <input type="text" id="total_amount_display" class="form-control bg-gray-100 fw-700 text-success" readonly>
                                                            <input type="hidden" name="total_amount" id="total_amount">
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
    const customerAddresses = <?php echo json_encode($customers->mapWithKeys(function ($customer) {
                                    return [$customer->id => $customer->addresses->map(function ($address) {
                                        return [
                                            'id' => $address->id,
                                            'address' => $address->address,
                                            'google_maps' => $address->google_maps,
                                        ];
                                    })];
                                })); ?>;
</script>
<script>
    const products = @json($productsJson);
    const bundles = @json($productBundlesJson);

    // Satukan jadi satu array dengan penanda type
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

    // Populate dropdown produk
    function populateProducts(selectEl, selectedId = null, selectedType = null) {
        $(selectEl).empty().append('<option value="" disabled selected hidden>Pilih produk</option>');
        allProducts.forEach(item => {
            const option = $('<option>', {
                    value: item.type + '_' + item.id,
                    text: `[${item.sku || '-'}] ${item.name}` + (item.type === 'bundle' ? '' : '')
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

    function formatNumber(num) {
        return new Intl.NumberFormat('id-ID', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(num);
    }

    // Hitung diskon per baris
    function calculateRow(row) {
        const selectedOption = row.find('select[name="product[]"] option:selected');
        const basePrice = parseFloat(selectedOption.data('price')) || 0;
        const discounts = selectedOption.data('discounts') || [];
        const categories = selectedOption.data('categories') || [];
        const qty = parseFloat(row.find('input[name="qty[]"]').val()) || 0;

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
                if (discount.minimum_based_on === 'Quantity of Items' && qty >= discount.minimum_qty_or_amount) eligible = true;
                else if (discount.minimum_based_on === 'Purchase Amount' && totalBeforeDiscount >= discount.minimum_qty_or_amount) eligible = true;
            } else if (discount.apply_on === 'Category') {
                let totalQtyCategory = 0;
                let totalAmountCategory = 0;

                $('select[name="product[]"]').each(function(i, el) {
                    const opt = $(el).find('option:selected');
                    const cats = opt.data('categories') || [];
                    const price = parseFloat(opt.data('price')) || 0;
                    const qtyVal = parseFloat($(`input[name="qty[]"]`).eq(i).val()) || 0;

                    if (cats.some(c => c.id === discount.category_id)) {
                        totalQtyCategory += qtyVal;
                        totalAmountCategory += price * qtyVal;
                    }
                });

                if (discount.minimum_based_on === 'Quantity of Items' && totalQtyCategory >= discount.minimum_qty_or_amount) eligible = true;
                else if (discount.minimum_based_on === 'Purchase Amount' && totalAmountCategory >= discount.minimum_qty_or_amount) eligible = true;
            }

            if (eligible) {
                if (discount.type === 'Percentage') finalPrice = priceBeforeDiscount - (priceBeforeDiscount * (discount.amount / 100));
                else finalPrice = Math.max(0, priceBeforeDiscount - discount.amount);
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
        let subTotal = 0;
        let totalAfterDiscount = 0;

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

        // display values (yang terlihat user, pakai ribuan + koma)
        $("#sub_total_display").val(formatNumber(subTotal));
        $("#total_discount_display").val(formatNumber(totalDiscount));
        $("#total_amount_display").val(formatNumber(totalAfterDiscount));
    }

    // Set product type otomatis & hitung ulang
    function updateRowTypeAndPrice(row) {
        const selectedOption = row.find('select[name="product[]"] option:selected');
        if (!selectedOption.length) return;
        const type = selectedOption.data('type') || '';
        row.find('.product-type').val(type);
        calculateRow(row);
    }

    function initSelect2() {
        $('[data-select2-selector="status"]').select2({
            placeholder: 'Pilih produk',
            width: '100%'
        }).each(function() {
            if ($(this).hasClass('select-product')) {
                const selectedVal = $(this).val();
                const selectedType = $(this).closest('tr').find('.product-type').val();
                const selectedId = selectedVal ? selectedVal.split('_')[1] : null;
                populateProducts(this, selectedId, selectedType);
            }
        });
    }

    // Set produk terpilih dari data-selected-id & data-selected-type
    $('select.select-product').each(function() {
        const selectedId = $(this).data('selected-id');
        const selectedType = $(this).data('selected-type');
        populateProducts(this, selectedId, selectedType);
    });


    document.addEventListener('DOMContentLoaded', function() {
        initSelect2();
        recalcAllRows();

        let rowCount = document.querySelectorAll('#tab_logic tbody tr').length;

        // Tambah row baru
        $('#add_row').on('click', function() {
            const tableBody = $('#tab_logic tbody');
            const newRow = $(`
            <tr id="addr${rowCount}">
                <td>${rowCount + 1}</td>
                <td>
                    <select class="form-control select-product" name="product[]" id="product_${rowCount}" data-select2-selector="status">
                        <option value="" disabled selected hidden>Pilih produk</option>
                    </select>
                </td>
                <input type="hidden" name="product_type[]" class="form-control product-type" readonly>
                <td><input type="number" name="qty[]" class="form-control qty" min="1" value="1"></td>
                <td><input type="number" name="price_before_discount[]" class="form-control price_before_discount" readonly></td>
                <td><input type="number" name="total_before_discount[]" class="form-control total_before_discount" readonly></td>
                <td class="text-center">
                    <div class="d-flex justify-content-center">
                        <button type="button" class="btn btn-danger delete-row">
                            <i class="feather-trash"></i>
                        </button>
                    </div>
                </td>
                <input type="hidden" name="price_after_discount[]" class="form-control price_after_discount" readonly>
                <input type="hidden" name="total_after_discount[]" class="form-control total_after_discount" readonly>
            </tr>
        `);

            tableBody.append(newRow);
            initSelect2(newRow.find('.select-product'));
            rowCount++;
        });

        // Hapus row per baris
        $(document).on('click', '.delete-row', function() {
            $(this).closest('tr').remove();

            // Re-index nomor urut
            $('#tab_logic tbody tr').each(function(i, el) {
                $(el).find('td:first').text(i + 1);
            });

            rowCount = $('#tab_logic tbody tr').length;
            recalcAllRows();
        });

        // Event change product
        $(document).on('change', 'select[name="product[]"]', function() {
            const row = $(this).closest('tr');
            updateRowTypeAndPrice(row);
            recalcAllRows();
        });

        // Event input qty
        $(document).on('input', 'input[name="qty[]"]', recalcAllRows);
    });

    $(document).ready(function() {
        // Inisialisasi data alamat berdasarkan customer yang sudah dipilih
        const initialCustomerId = $('#customers').val();
        if (initialCustomerId) {
            updateAddresses(initialCustomerId);
        }

        $('#customers').on('change', function() {
            const customerId = $(this).val();
            updateAddresses(customerId);
        });

        $('#addresses').on('change', function() {
            updateGoogleMapsLink();
        });

        function updateAddresses(customerId) {
            const addresses = customerAddresses[customerId] || [];
            const $addressSelect = $('#addresses');
            const selectedAddressId = "{{ $order->address_id ?? '' }}";

            $addressSelect.empty().append('<option disabled hidden>Pilih alamat</option>');

            addresses.forEach(function(address, index) {
                const isSelected = address.id == selectedAddressId;
                $addressSelect.append(
                    `<option value="${address.id}" data-map="${address.google_maps}" ${isSelected ? 'selected' : ''}>
                        Alamat ke-${index + 1} - ${address.address}
                    </option>`
                );
            });

            updateGoogleMapsLink();
        }

        function updateGoogleMapsLink() {
            const selectedOption = $('#addresses').find('option:selected');
            const mapUrl = selectedOption.data('map');

            if (mapUrl) {
                $('#google-maps-link').html(`
                    <a href="${mapUrl}" target="_blank" class="btn btn-sm btn-outline-primary mt-2">
                        Lihat di Google Maps
                    </a>
                `);
            } else {
                $('#google-maps-link').empty();
            }
        }
    });

    // Fungsi tampilkan error bootstrap
    function showError(element, message) {
        // Hapus pesan error lama
        $(element).next(".invalid-feedback").remove();

        // Tambah pesan error baru
        $(element).after(`<div class="invalid-feedback">${message}</div>`);

        // Tambahkan kelas is-invalid
        $(element).addClass("is-invalid");
    }

    // Hapus error kalau user pilih sesuatu
    $("#customers, #addresses").on("change", function() {
        $(this).removeClass("is-invalid");
        $(this).next(".invalid-feedback").remove();
    });

    // Validasi saat submit form
    $("form").on("submit", function(e) {
        let valid = true;

        if (!$("#customers").val()) {
            showError($("#customers"), "Customer wajib dipilih");
            valid = false;
        }

        if (!$("#addresses").val()) {
            showError($("#addresses"), "Alamat wajib dipilih");
            valid = false;
        }

        if (!valid) {
            e.preventDefault(); // stop submit
        }
    });
</script>
@endpush