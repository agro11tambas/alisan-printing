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
                <li class="breadcrumb-item">Create Sale Orders</li>
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
                        <span>Add Sale Order</span>
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
                <form action="/erp/sales/sale-orders/store" method="POST" id="orderForm">
                    @csrf
                    @method('POST')
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
                                                    value="{{ date('Y-m-d') }}">
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
                                                        <option value="{{ $customer->id }}" data-bg="{{ $bg }}">
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
                                                    data-select2-selector="tag" id="addresses" name="addresses[]">
                                                    <option disabled selected hidden>Pilih alamat</option>
                                                </select>
                                            </div>
                                            <div id="google-maps-link" class="mt-2"></div>
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
                                                    <th class="text-center wd-50">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tab_logic_body">
                                                <tr id="addr0">
                                                    <td>1</td>

                                                    {{-- Product --}}
                                                    <td>
                                                        <select class="form-control select-product"
                                                            data-select2-selector="status" name="product[]" id="product_0">
                                                            <option value="" disabled selected hidden>Pilih produk
                                                            </option>
                                                        </select>
                                                    </td>

                                                    {{-- Product Type --}}
                                                    <input type="hidden" name="product_type[]"
                                                        class="form-control product-type" id="product_type_0" readonly>

                                                    <td><input type="number" name="qty[]" class="form-control qty"
                                                            id="qty_0" min="1" value="1"></td>

                                                    {{-- Price & Total Before Discount --}}
                                                    <!-- <td><input type="number" name="price_before_discount[]" class="form-control price_before_discount" id="price_before_discount_0" readonly></td>
                                                    <td><input type="number" name="total_before_discount[]" class="form-control total_before_discount" id="total_before_discount_0" readonly></td> -->
                                                    <td>
                                                        <input type="text"
                                                            class="form-control price_before_discount_display" readonly>
                                                        <input type="hidden" name="price_before_discount[]"
                                                            class="price_before_discount">
                                                    </td>
                                                    <td>
                                                        <input type="text"
                                                            class="form-control total_before_discount_display" readonly>
                                                        <input type="hidden" name="total_before_discount[]"
                                                            class="total_before_discount">
                                                    </td>

                                                    {{-- Delete Row --}}
                                                    <td class="text-center">
                                                        <div class="d-flex justify-content-center">
                                                            <button type="button" class="btn btn-danger delete-row">
                                                                <i class="feather-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>

                                                    {{-- Hidden for after discount --}}
                                                    <input type="hidden" name="price_after_discount[]"
                                                        class="price_after_discount">
                                                    <input type="hidden" name="total_after_discount[]"
                                                        class="total_after_discount">
                                                </tr>

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

        // Populate produk ke dalam <select>
        function populateProducts(selectEl) {
            $(selectEl).empty().append('<option value="" disabled selected hidden>Pilih produk</option>');
            allProducts.forEach(item => {
                $('<option>', {
                        value: item.id,
                        text: `[${item.sku || '-'}] ${item.name}`,
                    })
                    .data('price', item.price)
                    .data('discounts', item.discounts || [])
                    .data('categories', item.categories || [])
                    .data('type', item.type)
                    .data('sku', item.sku || '')
                    .appendTo(selectEl);
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

            // gabungkan diskon kategori
            categories.forEach(cat => {
                if (cat.discounts) {
                    allDiscounts = allDiscounts.concat(cat.discounts);
                }
            });

            // cek eligibility diskon
            allDiscounts.forEach(discount => {
                let eligible = false;

                if (discount.apply_on === 'Product') {
                    if (discount.minimum_based_on === 'Quantity of Items' && qty >= discount
                        .minimum_qty_or_amount) {
                        eligible = true;
                    } else if (discount.minimum_based_on === 'Purchase Amount' && totalBeforeDiscount >= discount
                        .minimum_qty_or_amount) {
                        eligible = true;
                    }
                } else if (discount.apply_on === 'Category') {
                    let totalQtyCategory = 0,
                        totalAmountCategory = 0;

                    $('select[name="product[]"]').each(function(i, el) {
                        const opt = $(el).find('option:selected');
                        const cats = opt.data('categories') || [];
                        const price = parseFloat(opt.data('price')) || 0;
                        const qtyVal = parseFloat($('input[name="qty[]"]').eq(i).val()) || 0;

                        if (cats.some(c => c.id === discount.category_id)) {
                            totalQtyCategory += qtyVal;
                            totalAmountCategory += price * qtyVal;
                        }
                    });

                    if (discount.minimum_based_on === 'Quantity of Items' && totalQtyCategory >= discount
                        .minimum_qty_or_amount) {
                        eligible = true;
                    } else if (discount.minimum_based_on === 'Purchase Amount' && totalAmountCategory >= discount
                        .minimum_qty_or_amount) {
                        eligible = true;
                    }
                }

                if (eligible) {
                    if (discount.type === 'Percentage') {
                        finalPrice = priceBeforeDiscount - (priceBeforeDiscount * (discount.amount / 100));
                    } else {
                        finalPrice = Math.max(0, priceBeforeDiscount - discount.amount);
                    }
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
            $("#total_discount").val((subTotal - totalAfterDiscount).toFixed(2));
            $("#total_amount").val(totalAfterDiscount.toFixed(2));

            $("#sub_total_display").val(formatNumber(subTotal));
            $("#total_discount_display").val(formatNumber(totalDiscount));
            $("#total_amount_display").val(formatNumber(totalAfterDiscount));
        }

        // Event produk
        $(document).on('change', 'select[name="product[]"]', function() {
            const row = $(this).closest('tr');
            const type = $(this).find('option:selected').data('type') || '';
            row.find('.product-type').val(type);
            recalcAllRows();
        });

        // Event qty
        $(document).on('input', 'input[name="qty[]"]', recalcAllRows);

        // Init Select2
        function initSelect2(el) {
            $(el).select2({
                placeholder: 'Pilih produk',
                width: '100%',
                matcher: (params, data) => {
                    if ($.trim(params.term) === '') return data;
                    return data.text.toLowerCase().includes(params.term.toLowerCase()) ? data : null;
                }
            });
            if ($(el).children('option').length === 1) populateProducts(el);
        }

        document.addEventListener('DOMContentLoaded', function() {
            let rowCount = 1;

            // init select awal
            document.querySelectorAll('select.select-product').forEach(el => initSelect2(el));

            // tambah row
            // tambah row
            document.getElementById('add_row').addEventListener('click', function() {
                const tableBody = document.querySelector('#tab_logic_body');
                const rowCount = tableBody.querySelectorAll('tr').length;

                const newRow = document.createElement('tr');
                newRow.id = 'addr' + rowCount;

                newRow.innerHTML = `
                <td>${rowCount + 1}</td>
                <td>
                    <select class="form-control select-product" name="product[]" id="product_${rowCount}">
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
            `;
                tableBody.appendChild(newRow);

                initSelect2(newRow.querySelector('.select-product'));
            });


            // Hapus row per baris
            $(document).on('click', '.delete-row', function() {
                const row = $(this).closest('tr');
                row.remove();

                // Re-index nomor urut
                $('#tab_logic_body tr').each(function(i, el) {
                    $(el).find('td:first').text(i + 1);
                });

                recalcAllRows();
            });

        });

        // === showError versi fix ===
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

        // Hapus error kalau user betulin input
        $(document).on("change input",
            "#customers, #addresses, select[name='product[]'], input[name='qty[]'], input[name='order_date']",
            function() {
                if ($(this).hasClass("select2-hidden-accessible")) {
                    $(this).next('.select2').next('.invalid-feedback').remove();
                } else {
                    this.classList.remove("is-invalid");
                    $(this).siblings(".invalid-feedback").remove();
                }
            });

        // Submit form
        document.getElementById('orderForm').addEventListener('submit', function(e) {
            let isValid = true;

            // reset error
            this.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            this.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

            // order date
            const orderDate = this.querySelector('input[name="order_date"]');
            if (!orderDate.value.trim()) {
                isValid = false;
                showError(orderDate, "Tanggal order wajib diisi");
            }

            // customer
            const customerSelect = $('#customers');
            if (!customerSelect.val() || customerSelect.val().length === 0) {
                isValid = false;
                showError(customerSelect[0], "Customer wajib dipilih");
            }

            // address
            const addressSelect = $('#addresses');
            if (!addressSelect.val() || addressSelect.val().length === 0) {
                isValid = false;
                showError(addressSelect[0], "Alamat wajib dipilih");
            }

            // produk
            const rows = this.querySelectorAll('#tab_logic tbody tr');
            rows.forEach(row => {
                const product = row.querySelector('select[name="product[]"]');
                const qty = row.querySelector('input[name="qty[]"]');
                if (!product.value) {
                    isValid = false;
                    showError(product, "Produk wajib dipilih");
                }
                if (!qty.value || parseInt(qty.value) < 1) {
                    isValid = false;
                    showError(qty, "Qty minimal 1");
                }
            });

            if (!isValid) e.preventDefault();
        });

        // alamat dinamis
        $(document).ready(function() {
            $('#customers').on('change', function() {
                const customerId = $(this).val();
                const addresses = customerAddresses[customerId] || [];
                $('#addresses').empty().append('<option disabled selected hidden>Pilih alamat</option>');
                addresses.forEach((address, i) => {
                    $('#addresses').append(
                        `<option value="${address.id}" data-map="${address.google_maps}">Alamat ke-${i+1} - ${address.address}</option>`
                        );
                });
            });
            $('#addresses').on('change', function() {
                const mapUrl = $(this).find('option:selected').data('map');
                if (mapUrl) {
                    $('#google-maps-link').html(
                        `<a href="${mapUrl}" target="_blank" class="btn btn-sm btn-outline-primary mt-2">Lihat di Google Maps</a>`
                        );
                } else {
                    $('#google-maps-link').empty();
                }
            });
        });

        $(document).on('select2:open', () => {
            setTimeout(() => {
                document.querySelector('.select2-container--open .select2-search__field')?.focus();
            }, 50);
        });
    </script>
@endpush
