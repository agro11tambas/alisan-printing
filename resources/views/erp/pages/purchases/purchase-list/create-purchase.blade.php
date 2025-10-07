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
    <div class="main-content">
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
                                    <div class="row mb-3 align-items-center">
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
                                    <div class="row mb-3 align-items-center">
                                        <div class="col-lg-2">
                                            <label for="purchase_date" class="fw-semibold">Purchase Date:</label>
                                        </div>
                                        <div class="col-lg-10 mb-0">
                                            <div class="input-group">
                                                <input type="date" class="form-control" id="purchase_date"
                                                    name="purchase_date" value="{{ date('Y-m-d') }}">
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
                                    <div class="row mb-3 align-items-center">
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
                                    </div>
                                    <!-- <div class="row mb-3 align-items-center">
                                                <div class="col-lg-2">
                                                    <label for="image" class="fw-semibold">Upload Bond</label>
                                                </div>
                                                <div class="col-lg-10 mb-0">
                                                    <div class="input-group">
                                                        <input type="file" class="form-control" id="image" name="image" accept="image/*" value="{{ old('image') }}">
                                                    </div>
                                                    @if (isset($purchase->image) && $purchase->image)
    <img src="{{ asset('storage/' . $purchase->image) }}"
                                                        alt="Bond Image"
                                                        style="max-width: 100px; margin-top: 10px; border-radius: 10px" />
    @endif
                                                </div>
                                            </div> -->
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
                                                    <th class="text-center wd-100">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr id="addr0">
                                                    <td>1</td>
                                                    <td>
                                                        <select class="form-control select-product"
                                                            data-select2-selector="status" name="product[]"
                                                            id="product_0">
                                                            <option value="" disabled selected hidden>Pilih produk
                                                            </option>
                                                            @foreach ($products as $product)
                                                                <option value="{{ $product->id }}"
                                                                    data-price="{{ $product->price }}">
                                                                    [{{ $product->sku }}] {{ $product->name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                    <td><input type="number" name="qty[]" class="form-control qty"
                                                            id="qty_0" placeholder="Qty" min="1"></td>
                                                    <td><input type="number" name="price[]" class="form-control price"
                                                            id="price_0"></td>
                                                    <td><input type="number" name="freight[]"
                                                            class="form-control freight" id="freight_0" value="0">
                                                    </td>
                                                    <td>
                                                        {{-- hidden raw value (submit) --}}
                                                        <input type="hidden" name="total[]" class="form-control total"
                                                            id="total_0">
                                                        {{-- display formatted --}}
                                                        <input type="text" class="form-control total_display" readonly>
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="d-flex justify-content-center">
                                                            <button type="button" class="btn btn-danger delete-row">
                                                                <i class="feather-trash-2"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="d-flex justify-content-end mt-3">
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
        <div class="col-lg-12">
            <div class="card stretch stretch-full">
                <div class="card-body p-0">
                    <div class="table-responsive">
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
@endsection

@push('scripts')
    <script>
        // === FORMAT ANGKA ===
        function formatNumber(num) {
            return new Intl.NumberFormat('id-ID', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(num);
        }

        // === PERHITUNGAN TIAP BARIS ===
        function updateRowTotal(row) {
            const qty = parseFloat(row.find(".qty").val()) || 0;
            const price = parseFloat(row.find(".price").val()) || 0;
            const freight = parseFloat(row.find(".freight").val()) || 0;
            const total = qty * (price + freight);

            row.find(".total").val(total.toFixed(2));
            row.find(".total_display").val(formatNumber(total));

            calc();
        }

        // === PERHITUNGAN TOTAL AKHIR ===
        // function calc() {
        //     let subtotal = 0;
        //     $(".total").each(function() {
        //         subtotal += parseFloat($(this).val()) || 0;
        //     });

        //     const taxPercent = parseFloat($("#tax_percent").val()) || 0;
        //     const taxAmount = (subtotal * taxPercent) / 100;
        //     const grandTotal = subtotal + taxAmount;

        //     $("#sub_total").val(subtotal.toFixed(2));
        //     $("#tax_amount").val(taxAmount.toFixed(2));
        //     $("#total_amount").val(grandTotal.toFixed(2));

        //     $("#sub_total_display").val(formatNumber(subtotal));
        //     $("#tax_amount_display").val(formatNumber(taxAmount));
        //     $("#total_amount_display").val(formatNumber(grandTotal));
        // }

        function calc() {
            let subtotalProduct = 0;
            let subtotalFreight = 0;

            $("#tab_logic tbody tr").each(function() {
                const qty = parseFloat($(this).find(".qty").val()) || 0;
                const price = parseFloat($(this).find(".price").val()) || 0;
                const freight = parseFloat($(this).find(".freight").val()) || 0;

                subtotalProduct += qty * price;
                subtotalFreight += qty * freight;
            });

            const taxPercent = parseFloat($("#tax_percent").val()) || 0;
            const taxAmount = (subtotalProduct * taxPercent) / 100;

            const totalProduct = subtotalProduct + taxAmount;
            const totalFreight = subtotalFreight;
            const grandTotal = totalProduct + totalFreight;

            // set hidden value
            $("#sub_total").val(subtotalProduct.toFixed(2));
            $("#tax_amount").val(taxAmount.toFixed(2));
            $("#total_amount_product").val(totalProduct.toFixed(2));
            $("#total_amount_freight").val(totalFreight.toFixed(2));
            $("#total_amount").val(grandTotal.toFixed(2));

            // tampilkan formatted
            $("#sub_total_display").val(formatNumber(subtotalProduct));
            $("#tax_amount_display").val(formatNumber(taxAmount));
            $("#total_amount_product_display").val(formatNumber(totalProduct));
            $("#total_amount_freight_display").val(formatNumber(totalFreight));
            $("#total_amount_display").val(formatNumber(grandTotal));
        }

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
            // Inisialisasi semua Select2
            function initSelect2(el) {
                $(el).select2({
                    placeholder: 'Pilih opsi',
                    width: '100%',
                    matcher: (params, data) => {
                        if ($.trim(params.term) === '') return data;
                        return data.text.toLowerCase().includes(params.term.toLowerCase()) ? data :
                            null;
                    },
                    // templateResult: formatOption,
                    // templateSelection: formatOption
                });
            }

            $('.select-product').each(function() {
                initSelect2(this);
            });

            // Tambah row produk
            $('#add_row').on('click', function() {
                const tableBody = $('#tab_logic tbody');
                const rowCount = tableBody.find('tr').length;
                const $newRow = tableBody.find('tr:first').clone();

                $newRow.attr('id', 'addr' + rowCount);
                $newRow.find('td:first').text(rowCount + 1);
                $newRow.find('input').val('');
                $newRow.find('.freight').val('0');
                $newRow.find('.total').val('0.00');
                $newRow.find('.total_display').val('');
                $newRow.find('.select2').remove();

                $newRow.find('select').removeClass('select2-hidden-accessible').val('');
                tableBody.append($newRow);
                initSelect2($newRow.find('.select-product'));
            });

            // Hapus row
            $(document).on('click', '.delete-row', function() {
                if ($('#tab_logic tbody tr').length > 1) {
                    $(this).closest('tr').remove();
                    calc();
                }
            });

            // Produk berubah => isi harga
            $(document).on('change', '.select-product', function() {
                const row = $(this).closest('tr');
                const selectedOption = $(this).find('option:selected');
                const price = parseFloat(selectedOption.data('price')) || 0;
                row.find('.price').val(price.toFixed(2));
                updateRowTotal(row);
            });

            // Qty/price/freight berubah
            $(document).on('input', '.qty, .price, .freight', function() {
                updateRowTotal($(this).closest('tr'));
            });

            // Tax berubah
            // $(document).on('input', '#tax_percent', calc);
            // calc();

            // Tax berubah
            $(document).on('input', '#tax_percent', function() {
                const taxPercent = parseFloat($(this).val()) || 0;

                // Ubah semua harga berdasarkan tax
                $(".price").each(function() {
                    const row = $(this).closest('tr');
                    const originalPrice = parseFloat($(this).data('original')) || parseFloat($(this)
                        .val()) || 0;

                    // Simpan harga asli pertama kali
                    if (!$(this).data('original')) {
                        $(this).data('original', originalPrice);
                    }

                    // Hitung harga baru dengan tax
                    const newPrice = originalPrice * (1 + (taxPercent / 100));
                    $(this).val(newPrice.toFixed(2));

                    updateRowTotal(row);
                });

                calc();
            });

            // AUTO DUE DATE
            const optionEl = document.getElementById('due_date_option');
            const dateInput = document.getElementById('custom_due_date');
            const purchaseDateInput = document.getElementById('purchase_date');

            function updateDueDate() {
                const val = optionEl.value;
                let newDate = null;
                let baseDate = purchaseDateInput.value ? new Date(purchaseDateInput.value) : new Date();

                if (val === 'today') newDate = baseDate;
                else if (val === '1_week') newDate = new Date(baseDate.setDate(baseDate.getDate() + 7));
                else if (val === '1_month') newDate = new Date(baseDate.setMonth(baseDate.getMonth() + 1));
                else if (val === '3_months') newDate = new Date(baseDate.setMonth(baseDate.getMonth() + 3));
                else if (val === 'custom') {
                    dateInput.readOnly = false;
                    dateInput.value = "";
                    return;
                }

                dateInput.readOnly = true;
                if (newDate) {
                    const yyyy = newDate.getFullYear();
                    const mm = String(newDate.getMonth() + 1).padStart(2, '0');
                    const dd = String(newDate.getDate()).padStart(2, '0');
                    dateInput.value = `${yyyy}-${mm}-${dd}`;
                }
            }
            optionEl.addEventListener('change', updateDueDate);
            purchaseDateInput.addEventListener('change', updateDueDate);
            updateDueDate();
        });

        // === VALIDASI FRONTEND ===
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('purchaseForm');

            form.addEventListener('submit', function(e) {
                let isValid = true;

                // hapus error lama
                form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

                // invoice number
                const purchaseNumber = document.getElementById('purchase_number');
                if (!purchaseNumber.value.trim()) {
                    isValid = false;
                    showError(purchaseNumber, 'Invoice number wajib diisi');
                }

                // supplier
                const supplierSelect = $('#suppliers');
                if (!supplierSelect.val()) {
                    isValid = false;
                    showError(supplierSelect[0], 'Supplier wajib dipilih');
                }

                // produk
                const rows = form.querySelectorAll('#tab_logic tbody tr');
                rows.forEach((row, i) => {
                    const product = row.querySelector('select[name="product[]"]');
                    const qty = row.querySelector('input[name="qty[]"]');
                    if (!product.value) {
                        isValid = false;
                        showError(product, `Produk pada baris ${i + 1} wajib dipilih`);
                    }
                    if (!qty.value || parseFloat(qty.value) < 1) {
                        isValid = false;
                        showError(qty, 'Qty minimal 1');
                    }
                });

                if (!isValid) e.preventDefault();
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
                    const parent = el.closest('.input-group') || el.parentNode;
                    const existing = parent.querySelector('.invalid-feedback');
                    if (existing) existing.remove();

                    const feedback = document.createElement('div');
                    feedback.className = 'invalid-feedback d-block';
                    feedback.textContent = message;
                    parent.appendChild(feedback);
                }
            }

            // Hapus error saat user ubah input
            form.querySelectorAll('input, select').forEach(el => {
                el.addEventListener('input', () => {
                    el.classList.remove('is-invalid');
                    const next = el.parentNode.querySelector('.invalid-feedback');
                    if (next) next.remove();
                });
            });
        });
    </script>
@endpush
