@extends('erp.layouts.main')

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Purchase List</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Purchase List</li>
                <li class="breadcrumb-item">From Purchase Order</li>
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
                        <i class="feather-arrow-left me-2"></i><span>Back</span>
                    </a>
                    <button type="submit" class="btn btn-primary" form="purchaseForm">
                        <i class="feather-plus me-2"></i><span>Save Purchase List</span>
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
    @if ($errors->has('purchase_number'))
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Nomor Invoice Duplikat',
                text: 'Nomor Invoice sudah digunakan!',
            });
        </script>
    @endif
    <div class="main-content m-0 m-md-2 m-lg-2 p-0 p-md-0 p-lg-0 pt-2 pt-md-0">
        <div class="row">
            <div class="col-12">
                <form action="{{ route('purchase-orders.update-purchase-list', $purchase->id) }}" method="POST"
                    id="purchaseForm" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="purchase_id" value="{{ $purchase->id }}">

                    <div class="card stretch stretch-full">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="row mb-3 align-items-center">
                                        <div class="col-lg-2">
                                            <label for="purchase_number" class="fw-semibold">Invoice Number:</label>
                                        </div>
                                        <div class="col-lg-10 mb-0">
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="purchase_number"
                                                    name="purchase_number" value="">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mb-3 align-items-center">
                                        <div class="col-lg-2">
                                            <label for="purchase_date" class="fw-semibold">Purchase Date:</label>
                                        </div>
                                        <div class="col-lg-10 mb-0">
                                            <div class="input-group">
                                                <input type="datetime-local" class="form-control" id="purchase_date"
                                                    name="purchase_date"
                                                    value="{{ $purchase->purchase_date->format('Y-m-d\TH:i') }}">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mb-3 align-items-center">
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
                                            <div id="custom_due_date_wrapper" class="mt-2">
                                                <input type="date" class="form-control" id="custom_due_date"
                                                    name="custom_due_date" readonly>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mb-3 align-items-center">
                                        <div class="col-lg-2">
                                            <label for="suppliers" class="fw-semibold">Supplier:</label>
                                        </div>
                                        <div class="col-lg-10 mb-0">
                                            <div class="input-group">
                                                <select class="form-select form-control select-supplier" id="suppliers"
                                                    name="suppliers" data-select2-selector="tag" required>
                                                    <option value="">Pilih Supplier</option>
                                                    @foreach ($suppliers as $supplier)
                                                        <option value="{{ $supplier->id }}"
                                                            {{ $supplier->id == $purchase->supplier_id ? 'selected' : '' }}>
                                                            {{ $supplier->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <input type="hidden" value="12" name="transaction_type" id="transaction_type">
                                    {{-- <div class="row mb-3 align-items-center">
                                        <div class="col-lg-2">
                                            <label for="transaction_type" class="fw-semibold">Purchase:</label>
                                        </div>
                                        <div class="col-lg-10 mb-0">
                                            <div class="input-group">
                                                <select class="form-select form-control max-select" id="transaction_type"
                                                    data-select2-selector="tag" name="transaction_type">
                                                    <option value="12" selected>Purchase Account</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div> --}}
                                    <div class="row mb-3 align-items-center">
                                        <div class="col-lg-2">
                                            <label for="stock_destination" class="fw-semibold">
                                                Stock Destination:
                                            </label>
                                        </div>
                                        <div class="col-lg-10 mb-0">
                                            <select class="form-select" name="stock_destination" id="stock_destination"
                                                required>
                                                <option value="warehouse">Inventory Warehouse</option>
                                                <option value="production">Production</option>
                                            </select>
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
                                        <input type="hidden" name="inventory_warehouse_id" id="inventory_warehouse_id"
                                            value="1">
                                        <table class="table table-bordered overflow-hidden" id="tab_logic">
                                            <thead>
                                                <tr class="single-item">
                                                    <th class="text-center wd-50">#</th>
                                                    <th class="text-center wd-450">Product</th>
                                                    <th class="text-center wd-150">Qty</th>
                                                    <th class="text-center wd-150">Price</th>
                                                    <th class="text-center wd-150">Freight</th>
                                                    <th class="text-center wd-150">Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($purchase->purchaseItems as $i => $item)
                                                    <tr>
                                                        <td>{{ $i + 1 }}</td>
                                                        <td>
                                                            <select name="product[]" class="form-select select-product"
                                                                data-select2-selector="tag" required>
                                                                <option value="">Pilih Produk</option>
                                                                @foreach ($products as $p)
                                                                    @php
                                                                        $lastPrice = $p->last_price ?? 0;
                                                                    @endphp

                                                                    <option value="{{ $p->id }}"
                                                                        data-price="{{ $lastPrice }}"
                                                                        {{ $p->id == $item->product_id ? 'selected' : '' }}>
                                                                        {{ $p->name }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </td>
                                                        <td><input type="text" inputmode="numeric" name="qty[]"
                                                                class="form-control qty" value="{{ $item->quantity }}">
                                                        </td>
                                                        <td>
                                                            <input type="text" inputmode="numeric" name="price[]"
                                                                class="form-control price" value="0">
                                                        </td>
                                                        <td>
                                                            <input type="text" inputmode="numeric" name="freight[]"
                                                                class="form-control freight" value="0">
                                                        </td>
                                                        <td>
                                                            <input type="text" inputmode="numeric" name="total[]"
                                                                class="form-control total" readonly value="0">
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="row justify-content-end">
                                        <div class="col-lg-4 mt-3">
                                            <div class="mb-4">
                                                <h5 class="fw-bold">Grand Total:</h5>
                                            </div>
                                            <div class="table-responsive">
                                                <table class="table table-bordered" id="tab_logic_total">
                                                    <tbody>
                                                        <tr>
                                                            <th>Total Product</th>
                                                            <td>
                                                                <input type="text" id="total_amount_product_display"
                                                                    class="form-control border-0 bg-transparent p-0"
                                                                    readonly>
                                                                <input type="hidden" name="total_amount_product"
                                                                    id="total_amount_product">
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th>Total Freight</th>
                                                            <td>
                                                                <input type="text" id="total_amount_freight_display"
                                                                    class="form-control border-0 bg-transparent p-0"
                                                                    readonly>
                                                                <input type="hidden" name="total_amount_freight"
                                                                    id="total_amount_freight">
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th>Sub Total</th>
                                                            <td>
                                                                <input type="text" id="sub_total_display"
                                                                    class="form-control border-0 bg-transparent p-0"
                                                                    readonly>
                                                                <input type="hidden" name="sub_total" id="sub_total">
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th>Tax (%)</th>
                                                            <td>
                                                                <div class="input-group">
                                                                    <input type="number" name="tax_percent"
                                                                        id="tax_percent"
                                                                        class="form-control border-0 bg-transparent p-0"
                                                                        value="0" min="0">
                                                                    <span class="input-group-text">%</span>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th>Tax Amount</th>
                                                            <td>
                                                                <input type="text" id="tax_amount_display"
                                                                    class="form-control border-0 bg-transparent p-0"
                                                                    readonly>
                                                                <input type="hidden" name="tax_amount" id="tax_amount">
                                                            </td>
                                                        </tr>
                                                        <tr class="fw-bold">
                                                            <th>Grand Total</th>
                                                            <td>
                                                                <input type="text" id="total_amount_display"
                                                                    class="form-control border-0 bg-transparent p-0 fw-bold"
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
        function formatRibuan(value) {
            if (value === null || value === undefined || value === '') return '';

            const num = parseFloat(value);
            if (isNaN(num)) return '';

            const parts = num.toFixed(2).split('.');
            const intPart = parts[0];
            const decPart = parts[1];

            const formattedInt = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

            // ✅ kalau desimal = 00, gak usah tampilkan
            return decPart === '00' ? formattedInt : `${formattedInt},${decPart}`;
        }

        function unformatRibuan(value) {
            if (!value) return 0;

            value = value.toString().trim();

            // kalau format Indonesia (koma desimal, titik ribuan)
            if (value.includes(',')) {
                value = value.replace(/\./g, '').replace(',', '.');
            } else {
                // kalau cuma titik, artinya ribuan, bukan desimal
                value = value.replace(/\./g, '');
            }

            const num = parseFloat(value);
            return isNaN(num) ? 0 : num;
        }

        /* ==================== PERHITUNGAN TOTAL ==================== */
        function updateRowTotal(row) {
            // ✅ Gunakan unformatRibuan untuk semua field supaya konsisten
            const qty = parseFloat(unformatRibuan(row.find(".qty").val())) || 0;
            const price = parseFloat(unformatRibuan(row.find(".price").val())) || 0;
            const freight = parseFloat(unformatRibuan(row.find(".freight").val())) || 0;

            const total = qty * (price + freight);

            if (total > 0) {
                row.find(".total").val(formatRibuan(total.toFixed(2)));
            } else {
                row.find(".total").val('');
            }

            calc_total();
        }

        function calc_total() {
            let subtotalProduct = 0,
                subtotalFreight = 0;

            $('#tab_logic tbody tr').each(function() {
                const row = $(this);

                // ✅ Qty harus dianggap bilangan bulat (hapus titik)
                const qtyVal = row.find('.qty').val().toString().replace(/\./g, '');
                const qty = parseFloat(qtyVal) || 0;

                // ✅ Price & Freight boleh desimal
                const price = parseFloat(unformatRibuan(row.find('.price').val())) || 0;
                const freight = parseFloat(unformatRibuan(row.find('.freight').val())) || 0;

                subtotalProduct += qty * price;
                subtotalFreight += qty * freight;

                const totalRow = qty * (price + freight);
                row.find('.total').val(totalRow > 0 ? formatRibuan(totalRow.toFixed(2)) : '');
            });

            const taxPercent = parseFloat(unformatRibuan($("#tax_percent").val())) || 0;
            const taxAmount = (subtotalProduct * taxPercent) / 100;

            const totalProduct = subtotalProduct + taxAmount;
            const grandTotal = totalProduct + subtotalFreight;
            const subTotal = subtotalProduct + subtotalFreight;

            $("#total_amount_product").val(totalProduct.toFixed(2));
            $("#total_amount_freight").val(subtotalFreight.toFixed(2));
            $("#sub_total").val(subTotal.toFixed(2));
            $("#tax_amount").val(taxAmount.toFixed(2));
            $("#total_amount").val(grandTotal.toFixed(2));

            $("#total_amount_product_display").val(totalProduct > 0 ? formatRibuan(totalProduct.toFixed(0)) : '0');
            $("#total_amount_freight_display").val(subtotalFreight > 0 ? formatRibuan(subtotalFreight.toFixed(0)) : '0');
            $("#sub_total_display").val(subTotal > 0 ? formatRibuan(subTotal.toFixed(0)) : '0');
            $("#tax_amount_display").val(taxAmount > 0 ? formatRibuan(taxAmount.toFixed(0)) : '0');
            $("#total_amount_display").val(grandTotal > 0 ? formatRibuan(grandTotal.toFixed(0)) : '0');
        }

        /* ==================== SELECT2 & INIT ==================== */
        function initSelect2(el) {
            $(el).select2({
                placeholder: 'Pilih opsi',
                width: '100%',
                matcher: (params, data) => {
                    if ($.trim(params.term) === '') return data;
                    return data.text.toLowerCase().includes(params.term.toLowerCase()) ? data : null;
                }
            });
        }

        /* ==================== DOCUMENT READY ==================== */
        $(document).ready(function() {
            initSelect2('.select-product');
            initSelect2('#suppliers');

            // Format awal
            $('.qty, .price, .freight').each(function() {
                let val = $(this).val();

                // kalau kosong/null/undefined → isi 0
                if (val === '' || val === null || val === undefined) {
                    val = '0';
                }

                const num = unformatRibuan(val);

                // khusus qty, selalu tanpa koma desimal (bilangan bulat)
                if ($(this).hasClass('qty')) {
                    $(this).val(num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.'));
                } else {
                    $(this).val(num > 0 ? formatRibuan(num) : '0');
                }
            });

            // Auto price dari data attribute
            $('#tab_logic tbody tr').each(function() {
                const row = $(this);
                const selected = row.find('.select-product option:selected');
                if (selected.val()) {
                    const lastPrice = parseFloat(selected.data('price')) || 0;
                    row.find('.price').val(formatRibuan(lastPrice.toFixed(2)));
                    updateRowTotal(row);
                }
            });
            calc_total();

            /* Tambah baris baru */
            $('#add_row').on('click', function() {
                const $tbody = $('#tab_logic tbody');
                const $newRow = $tbody.find('tr:first').clone();
                const newIndex = $tbody.find('tr').length;

                $newRow.attr('id', 'addr' + newIndex);
                $newRow.find('td:first').text(newIndex + 1);
                $newRow.find('input').val('0'); // Set default ke 0
                $newRow.find('.select2').remove();
                $newRow.find('select').removeClass('select2-hidden-accessible').val('');

                $tbody.append($newRow);
                initSelect2($newRow.find('.select-product'));
            });

            /* Hapus baris */
            $(document).on('click', '.delete-row', function() {
                if ($('#tab_logic tbody tr').length > 1) {
                    $(this).closest('tr').remove();
                    calc_total();
                }
            });

            /* ==================== INPUT HANDLER ==================== */
            $(document).on('change', '.select-product', function() {
                const row = $(this).closest('tr');
                const selectedOption = $(this).find('option:selected');
                const productId = selectedOption.val();

                const lastPrice = parseFloat(selectedOption.data('price')) || 0;
                row.find('.price').val(formatRibuan(lastPrice.toFixed(2)));
                row.find('.freight').val('0');
                updateRowTotal(row);

                if (!productId) return;
                $.ajax({
                    url: `/erp/purchases/get-latest-price/${productId}`,
                    type: 'GET',
                    success: function(response) {
                        const price = response.price ? parseFloat(response.price) : lastPrice;
                        const freight = response.freight ? parseFloat(response.freight) : 0;
                        row.find('.price').val(formatRibuan(price.toFixed(2)));
                        row.find('.freight').val(formatRibuan(freight.toFixed(2)));
                        updateRowTotal(row);
                    },
                    error: function() {
                        updateRowTotal(row);
                    }
                });
            });

            $(document).on('input', '.qty', function() {
                let val = $(this).val();

                // Hanya izinkan angka dan koma
                val = val.replace(/[^0-9,]/g, '');

                // Kalau ada lebih dari satu koma, hapus sisanya
                const parts = val.split(',');
                if (parts.length > 2) {
                    val = parts[0] + ',' + parts[1];
                }

                // Jangan format dulu kalau baru ngetik koma
                if (val.endsWith(',')) {
                    $(this).val(val);
                    return;
                }

                // Kalau ada desimal (setelah koma)
                if (parts.length === 2) {
                    let intPart = parts[0];
                    let decPart = parts[1].slice(0, 5); // maks 5 digit desimal

                    // Format ribuan untuk bagian integer
                    intPart = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

                    $(this).val(`${intPart},${decPart}`);
                } else {
                    // Belum ada desimal → format biasa
                    val = val.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                    $(this).val(val);
                }

                // ✅ Hitung total realtime
                updateRowTotal($(this).closest('tr'));
            });


            $(document).on('input', '.price, .freight', function() {
                let val = $(this).val();

                // Hanya izinkan angka dan koma
                val = val.replace(/[^0-9,]/g, '');

                // Kalau ada lebih dari satu koma, hapus sisanya
                const parts = val.split(',');
                if (parts.length > 2) {
                    val = parts[0] + ',' + parts[1];
                }

                // Jangan format dulu kalau baru ngetik koma (biar gak kehapus)
                if (val.endsWith(',')) {
                    $(this).val(val);
                    return;
                }

                // Kalau ada desimal (setelah koma)
                if (parts.length === 2) {
                    let intPart = parts[0];
                    let decPart = parts[1].slice(0, 5); // maks 5 digit desimal

                    // Format ribuan untuk bagian integer
                    intPart = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

                    $(this).val(`${intPart},${decPart}`);
                } else {
                    // Belum ada desimal → format biasa
                    val = val.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                    $(this).val(val);
                }

                // ✅ Hitung total realtime tiap ketik
                const row = $(this).closest('tr');
                updateRowTotal(row);
            });

            $(document).on('focus', '.qty', function() {
                const val = unformatRibuan($(this).val());
                if (val === 0 || $(this).val().trim() === '') {
                    $(this).val(''); // kosongkan saat fokus
                }
            });

            $(document).on('blur', '.qty', function() {
                let val = $(this).val().trim();
                const row = $(this).closest('tr');

                // kalau dikosongkan → isi 0
                if (val === '' || val === null || val === undefined) {
                    $(this).val('0');
                } else {
                    // format ulang kalau ada angka
                    $(this).val(formatRibuan(val));
                }

                updateRowTotal(row);
            });

            /* ==================== FOCUS & BLUR HANDLER - Price & Freight ==================== */
            $(document).on('focus', '.price, .freight', function() {
                const val = unformatRibuan($(this).val());
                if (val === 0 || $(this).val().trim() === '') {
                    $(this).val(''); // kosongkan saat fokus
                }
            });

            $(document).on('blur', '.price, .freight', function() {
                let val = $(this).val().trim();
                const row = $(this).closest('tr');

                if (val === '' || val === null || val === undefined) {
                    $(this).val('0');
                } else {
                    // 💡 konversi dulu ke angka real (support koma)
                    const num = unformatRibuan(val);
                    $(this).val(formatRibuan(num));
                }

                updateRowTotal(row);
            });

            $(document).on('input', '#tax_percent', calc_total);

            // $('#purchaseForm').on('submit', function() {
            //     $('.qty, .price, .freight, .total').each(function() {
            //         const cleanVal = unformatRibuan($(this).val());
            //         $(this).val(cleanVal);
            //     });
            // });

            /* ==================== DUE DATE HANDLER ==================== */
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

            $('.price').each(function() {
                const val = $(this).val().trim();
                if (val === '000' || val === '0.00' || val === '0,00' || val === '0.00000' || val ===
                    '0,00000') {
                    $(this).val('0');
                }
            });

        });

        /* ==================== VALIDASI FORM ==================== */
        function showError(el, message) {
            if ($(el).hasClass('select2-hidden-accessible')) {
                const select2Container = $(el).next('.select2');
                select2Container.next('.invalid-feedback').remove();
                const feedback = $('<div class="invalid-feedback d-block">' + message + '</div>');
                select2Container[0].after(feedback[0]);
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

        // $('#purchaseForm').on('submit', function(e) {
        //     let isValid = true;
        //     $(this).find('.is-invalid').removeClass('is-invalid');
        //     $(this).find('.invalid-feedback').remove();

        //     const purchaseNumber = $('#purchase_number');
        //     if (!purchaseNumber.val().trim()) {
        //         isValid = false;
        //         showError(purchaseNumber[0], 'Nomor invoice wajib diisi');
        //     }

        //     const purchaseDate = $('#purchase_date');
        //     if (!purchaseDate.val().trim()) {
        //         isValid = false;
        //         showError(purchaseDate[0], 'Tanggal pembelian wajib diisi');
        //     }

        //     const supplier = $('#suppliers');
        //     if (!supplier.val()) {
        //         isValid = false;
        //         showError(supplier[0], 'Supplier wajib dipilih');
        //     }

        //     const editNote = document.getElementById('edit_note');
        //     if (editNote && !editNote.value.trim()) {
        //         isValid = false;
        //         showError(editNote, 'Catatan edit wajib diisi');
        //     }

        //     const transactionType = $('#transaction_type');
        //     if (!transactionType.val()) {
        //         isValid = false;
        //         showError(transactionType[0], 'Tipe transaksi wajib dipilih');
        //     }

        //     $('#tab_logic tbody tr').each(function() {
        //         const product = $(this).find('select[name="product[]"]');
        //         const qty = $(this).find('input[name="qty[]"]');
        //         const price = $(this).find('input[name="price[]"]');
        //         const freight = $(this).find('input[name="freight[]"]');

        //         if (!product.val()) {
        //             isValid = false;
        //             showError(product[0], 'Produk wajib dipilih');
        //         }
        //         if (!qty.val() || parseFloat(unformatRibuan(qty.val())) <= 0) {
        //             isValid = false;
        //             showError(qty[0], 'Qty harus lebih dari 0');
        //         }
        //         if (!price.val() || parseFloat(unformatRibuan(price.val())) <= 0) {
        //             isValid = false;
        //             showError(price[0], 'Harga harus lebih dari 0');
        //         }

        //         // Freight boleh 0, tapi tidak boleh kosong
        //         const freightVal = freight.val().trim();
        //         if (freightVal === '' || freightVal === null) {
        //             freight.val('0');
        //         } else {
        //             const freightNum = unformatRibuan(freightVal);
        //             if (isNaN(freightNum) || freightNum < 0) {
        //                 isValid = false;
        //                 showError(freight[0], 'Freight harus angka valid (≥ 0)');
        //             }
        //         }
        //     });

        //     if (!isValid) {
        //         e.preventDefault();
        //         const firstError = $(this).find('.is-invalid, .select2 + .invalid-feedback').first();
        //         if (firstError.length) $('html, body').animate({
        //             scrollTop: firstError.offset().top - 100
        //         }, 300);
        //     }
        // });

        $('#purchaseForm').on('submit', function(e) {
            let isValid = true;
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
            $('#tab_logic tbody tr').each(function() {
                const row = $(this);
                const product = row.find('select[name="product[]"]');
                const qty = row.find('input[name="qty[]"]');
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

        /* ==================== CEK NOMOR INVOICE ==================== */
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
                        $('#purchase_number').addClass('is-invalid').val('').focus();
                    } else {
                        $('#purchase_number').removeClass('is-invalid');
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Mengecek Nomor Invoice!',
                        text: 'Silakan coba lagi.',
                        confirmButtonText: 'OK'
                    });
                }
            });
        });
    </script>
@endpush
