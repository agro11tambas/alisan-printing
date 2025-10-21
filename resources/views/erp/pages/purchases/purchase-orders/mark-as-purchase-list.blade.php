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
    <div class="main-content">
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
                                    {{-- Invoice Number --}}
                                    <div class="row mb-3 align-items-center">
                                        <div class="col-lg-2">
                                            <label for="purchase_number" class="fw-semibold">Invoice Number:</label>
                                        </div>
                                        <div class="col-lg-10 mb-0">
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="purchase_number"
                                                    name="purchase_number" value="{{ $purchase->purchase_number }}">
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Purchase Date --}}
                                    {{-- Purchase Date --}}
                                    <div class="row mb-3 align-items-center">
                                        <div class="col-lg-2">
                                            <label for="purchase_date" class="fw-semibold">Purchase Date:</label>
                                        </div>
                                        <div class="col-lg-10 mb-0">
                                            <div class="input-group">
                                                <input type="date" class="form-control" id="purchase_date"
                                                    name="purchase_date"
                                                    value="{{ $purchase->purchase_date->format('Y-m-d') }}">
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Due Date --}}
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


                                    {{-- Supplier --}}
                                    <div class="row mb-3 align-items-center">
                                        <div class="col-lg-2">
                                            <label for="suppliers" class="fw-semibold">Supplier:</label>
                                        </div>
                                        <div class="col-lg-10 mb-0">
                                            <div class="input-group">
                                                <input type="text" class="form-control"
                                                    value="{{ $purchase->supplier->name ?? '-' }}" readonly>
                                                <input type="hidden" name="suppliers" value="{{ $purchase->supplier_id }}">
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Transaction type --}}
                                    <div class="row mb-3 align-items-center">
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
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Products Table --}}
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
                                                            <input type="hidden" name="product[]"
                                                                value="{{ $item->product_id }}">
                                                            <input type="text" class="form-control"
                                                                value="{{ $item->purchaseProduct->name ?? ($item->purchaseProduct_name ?? '-') }}"
                                                                readonly>
                                                        </td>
                                                        <td><input type="text" inputmode="numeric" name="qty[]"
                                                                class="form-control qty" value="{{ $item->quantity }}"
                                                                readonly></td>
                                                        <td><input type="text" inputmode="numeric" name="price[]"
                                                                class="form-control price"></td>
                                                        <td><input type="text" inputmode="numeric" name="freight[]"
                                                                class="form-control freight"></td>
                                                        <td><input type="text" inputmode="numeric" name="total[]"
                                                                class="form-control total" readonly></td>
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
        // === FORMAT ANGKA RIBUAN (EN-US STYLE) ===
        function formatRibuan(angka) {
            if (angka === null || angka === undefined) return '';
            const str = angka.toString().replace(/[^0-9.-]/g, '');
            const parts = str.split('.');
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            return parts.join('.');
        }

        // === HAPUS FORMAT KOMA JADI ANGKA MURNI ===
        function unformatRibuan(angka) {
            if (!angka) return 0;
            return parseFloat(angka.toString().replace(/,/g, '')) || 0;
        }

        // === PERHITUNGAN TIAP BARIS ===
        function updateRowTotal(row) {
            const qty = parseFloat(unformatRibuan(row.find(".qty").val())) || 0;
            const price = parseFloat(unformatRibuan(row.find(".price").val())) || 0;
            const freight = parseFloat(unformatRibuan(row.find(".freight").val())) || 0;

            const total = qty * (price + freight);
            row.find(".total").val(total.toFixed(2));
            calc_total();
        }

        // === PERHITUNGAN TOTAL AKHIR ===
        function calc_total() {
            let subtotalProduct = 0,
                subtotalFreight = 0;

            $('#tab_logic tbody tr').each(function() {
                const qty = parseFloat(unformatRibuan($(this).find('.qty').val())) || 0;
                const price = parseFloat(unformatRibuan($(this).find('.price').val())) || 0;
                const freight = parseFloat(unformatRibuan($(this).find('.freight').val())) || 0;
                subtotalProduct += qty * price;
                subtotalFreight += qty * freight;
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

            $("#total_amount_product_display").val(formatRibuan(totalProduct.toFixed(2)));
            $("#total_amount_freight_display").val(formatRibuan(subtotalFreight.toFixed(2)));
            $("#sub_total_display").val(formatRibuan(subTotal.toFixed(2)));
            $("#tax_amount_display").val(formatRibuan(taxAmount.toFixed(2)));
            $("#total_amount_display").val(formatRibuan(grandTotal.toFixed(2)));
        }

        // === tampilkan error di bawah field ===
        function showError(el, message) {
            // hapus dulu error lama
            $(el).removeClass('is-invalid');
            $(el).siblings('.invalid-feedback').remove();
            $(el).closest('td, .input-group, .col-lg-10, .form-group')
                .find('.invalid-feedback').remove();

            // tambahkan class invalid
            $(el).addClass('is-invalid');

            // buat pesan error di bawah elemen
            const feedback = $('<div class="invalid-feedback d-block"></div>').text(message);

            // sisipkan setelah input, atau setelah parent cell
            if ($(el).closest('td').length) {
                $(el).after(feedback);
            } else {
                $(el).parent().append(feedback);
            }
        }

        // === hapus error kalau user betulin input ===
        $(document).on("input change", "input, select", function() {
            $(this).removeClass("is-invalid");
            $(this).siblings(".invalid-feedback").remove();
        });

        // === INIT SELECT2 ===
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

        // === PAGE READY ===
        $(document).ready(function() {
            initSelect2('.select-product');
            initSelect2('#suppliers');
            calc_total();

            // Format angka saat input
            $(document).on('input', '.qty, .price, .freight', function() {
                let val = $(this).val().replace(/,/g, '');
                if (val !== '' && !isNaN(val)) {
                    $(this).val(formatRibuan(val));
                }
                updateRowTotal($(this).closest('tr'));
            });

            $(document).on('input', '#tax_percent', calc_total);

            // === VALIDASI & HAPUS KOMA PAS SUBMIT ===
            $('#purchaseForm').on('submit', function(e) {
                let isValid = true;
                $('.invalid-feedback').remove();
                $('.is-invalid').removeClass('is-invalid');

                const purchaseNumber = $('#purchase_number');
                const purchaseDate = $('#purchase_date');
                const supplier = $('input[name="suppliers"]'); // fix disini
                const transactionType = $('#transaction_type');

                if (!purchaseNumber.val().trim()) {
                    isValid = false;
                    showError(purchaseNumber, 'Nomor invoice wajib diisi');
                }
                if (!purchaseDate.val().trim()) {
                    isValid = false;
                    showError(purchaseDate, 'Tanggal pembelian wajib diisi');
                }
                if (!supplier.val()) {
                    isValid = false;
                    showError($('input[readonly][value="{{ $purchase->supplier->name ?? '-' }}"]'),
                        'Supplier wajib dipilih');
                }
                if (!transactionType.val()) {
                    isValid = false;
                    showError(transactionType, 'Tipe transaksi wajib dipilih');
                }

                // validasi tabel produk
                $('#tab_logic tbody tr').each(function() {
                    const qty = $(this).find('input[name="qty[]"]');
                    const price = $(this).find('input[name="price[]"]');
                    const freight = $(this).find('input[name="freight[]"]');

                    if (!qty.val() || parseFloat(qty.val().replace(/,/g, '')) <= 0) {
                        isValid = false;
                        showError(qty, 'Qty wajib diisi');
                    }
                    if (!price.val() || parseFloat(price.val().replace(/,/g, '')) <= 0) {
                        isValid = false;
                        showError(price, 'Harga wajib diisi');
                    }
                    if (freight.val() === '' || isNaN(parseFloat(freight.val().replace(/,/g,
                            '')))) {
                        isValid = false;
                        showError(freight, 'Freight wajib diisi (isi 0 jika tidak ada)');
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    return false;
                }

                // hapus koma sebelum submit
                $('.qty, .price, .freight, .total').each(function() {
                    $(this).val($(this).val().replace(/,/g, ''));
                });
            });

            // === Due Date otomatis ===
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
                        customDueDate.val('').prop('readonly', true);
                        return;
                }
                const formatted = dueDate.toISOString().split('T')[0];
                customDueDate.val(formatted).prop('readonly', true);
            }

            dueDateSelect.on('change', setDueDate);
            purchaseDateEl.on('change', setDueDate);
        });
    </script>
@endpush
