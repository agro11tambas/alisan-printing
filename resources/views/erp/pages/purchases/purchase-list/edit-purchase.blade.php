@extends('erp.layouts.main')

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Purchase</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Purchase</li>
                <li class="breadcrumb-item">Edit Purchase</li>
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
                        <span>Edit Purchase</span>
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
                <form action="/erp/purchases/purchase-list/update/{{ $purchase->id }}" method="POST" id="purchaseForm"
                    enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="card stretch stretch-full">
                        <div class="card-body">
                            <div class="row">
                                <input type="hidden" name="status" value="Purchase List">
                                <div class="col-lg-12">
                                    <div class="row mb-3 align-items-center">
                                        <div class="col-lg-2">
                                            <label for="purchase_number" class="fw-semibold">Invoice Number:</label>
                                        </div>
                                        <div class="col-lg-10 mb-0">
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="purchase_number"
                                                    name="purchase_number"
                                                    value="{{ old('purchase_number', $purchase->purchase_number) }}">
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
                                                    name="purchase_date"
                                                    value="{{ old('purchase_date', isset($purchase->purchase_date) ? \Carbon\Carbon::parse($purchase->purchase_date)->format('Y-m-d') : date('Y-m-d')) }}">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mb-3 align-items-center">
                                        <div class="col-lg-2">
                                            <label for="due_date_option" class="fw-semibold">Due Date:</label>
                                        </div>
                                        <div class="col-lg-10 mb-0">
                                            <div class="input-group">
                                                <select class="form-select" name="due_date_option" id="due_date_option"
                                                    style="font-size: 14px;">
                                                    <option value="none"
                                                        {{ $dueDateOption === 'none' ? 'selected' : '' }}>Tidak ada due date
                                                    </option>
                                                    <option value="today"
                                                        {{ $dueDateOption === 'today' ? 'selected' : '' }}>Hari ini</option>
                                                    <option value="1_week"
                                                        {{ $dueDateOption === '1_week' ? 'selected' : '' }}>1 Minggu
                                                    </option>
                                                    <option value="1_month"
                                                        {{ $dueDateOption === '1_month' ? 'selected' : '' }}>1 Bulan
                                                    </option>
                                                    <option value="3_months"
                                                        {{ $dueDateOption === '3_months' ? 'selected' : '' }}>3 Bulan
                                                    </option>
                                                    <option value="custom"
                                                        {{ $dueDateOption === 'custom' ? 'selected' : '' }}>Custom</option>
                                                </select>
                                            </div>
                                            <div id="custom_due_date_wrapper" class="mt-2">
                                                <input type="date" class="form-control" id="custom_due_date"
                                                    name="custom_due_date" value="{{ $customDueDate ?? '' }}"
                                                    {{ $dueDateOption === 'custom' ? '' : 'readonly' }}>
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
                                                        <option value="{{ $supplier->id }}" data-bg="{{ $bg }}"
                                                            {{ $supplier->id == $purchase->supplier_id ? 'selected' : '' }}>
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

                                    <div class="row mb-3 align-items-center">
                                        <div class="col-lg-2">
                                            <label for="edit_note" class="fw-semibold">Edit Note:</label>
                                        </div>
                                        <div class="col-lg-10 mb-0">
                                            <div class="input-group">
                                                <textarea class="form-control" id="edit_note" name="edit_note" rows="2"
                                                    placeholder="Tambahkan catatan edit..."></textarea>
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
                                                @forelse ($purchase->purchaseItems ?? [0] as $index => $item)
                                                    <tr id="addr{{ $index }}">
                                                        <td>{{ $index + 1 }}</td>
                                                        <input type="hidden" name="purchase_item_ids[]"
                                                            value="{{ $item->id }}">
                                                        <td>
                                                            <select class="form-control select-product"
                                                                data-select2-selector="status" name="product[]"
                                                                id="product_{{ $index }}">
                                                                <option value="" disabled
                                                                    {{ !isset($item->product_id) ? 'selected hidden' : '' }}>
                                                                    Pilih produk</option>
                                                                @foreach ($products as $product)
                                                                    <option value="{{ $product->id }}"
                                                                        data-price="{{ $product->price }}"
                                                                        {{ isset($item->product_id) && $product->id == $item->product_id ? 'selected' : '' }}>
                                                                        [{{ $product->sku }}] {{ $product->name }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </td>
                                                        <td><input type="text" inputmode="numeric" name="qty[]" class="form-control qty"
                                                                min="1" value="{{ $item->quantity ?? '' }}"></td>
                                                        <td><input type="text" inputmode="numeric" name="price[]"
                                                                class="form-control price"
                                                                value="{{ $item->price ?? '' }}"></td>
                                                        <td><input type="text" inputmode="numeric" name="freight[]"
                                                                class="form-control freight"
                                                                value="{{ $item->freight ?? 0 }}"></td>
                                                        <td>
                                                            <input type="hidden" name="total[]"
                                                                class="form-control total">
                                                            <input type="text" class="form-control total_display"
                                                                readonly>
                                                        </td>
                                                        <td class="text-center">
                                                            <button type="button" class="btn btn-danger delete-row">
                                                                <i class="feather-trash-2"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr id="addr0">
                                                        <td>1</td>
                                                        <input type="hidden" name="purchase_item_ids[]" value="">
                                                        <td>
                                                            <select class="form-control select-product"
                                                                data-select2-selector="status" name="product[]"
                                                                id="product_0">
                                                                <option value="" disabled selected hidden>Pilih
                                                                    produk</option>
                                                                @foreach ($products as $product)
                                                                    <option value="{{ $product->id }}"
                                                                        data-price="{{ $product->price }}">
                                                                        [{{ $product->sku }}] {{ $product->name }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </td>
                                                        <td><input type="number" name="qty[]" class="form-control qty"
                                                                min="1"></td>
                                                        <td><input type="number" name="price[]"
                                                                class="form-control price"></td>
                                                        <td><input type="number" name="freight[]"
                                                                class="form-control freight" value="0"></td>
                                                        <td>
                                                            <input type="hidden" name="total[]"
                                                                class="form-control total">
                                                            <input type="text" class="form-control total_display"
                                                                readonly>
                                                        </td>
                                                        <td class="text-center">
                                                            <div class="d-flex justify-content-center">
                                                                <button type="button" class="btn btn-danger delete-row">
                                                                    <i class="feather-trash-2"></i>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforelse
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
                                                        <tr class="single-item">
                                                            <th class="fs-10 text-dark text-uppercase">Total Produk</th>
                                                            <td class="">
                                                                <input type="hidden" name="total_amount_product"
                                                                    id="total_amount_product">
                                                                <input type="text" id="total_amount_product_display"
                                                                    class="form-control border-0 bg-transparent p-0"
                                                                    readonly>
                                                            </td>
                                                        </tr>
                                                        <tr class="single-item">
                                                            <th class="fs-10 text-dark text-uppercase">Total Freight</th>
                                                            <td class="">
                                                                <input type="hidden" name="total_amount_freight"
                                                                    id="total_amount_freight">
                                                                <input type="text" id="total_amount_freight_display"
                                                                    class="form-control border-0 bg-transparent p-0"
                                                                    readonly>
                                                            </td>
                                                        </tr>
                                                        <tr class="single-item">
                                                            <th class="fs-10 text-dark text-uppercase">Sub Total</th>
                                                            <td class="">
                                                                {{-- hidden raw --}}
                                                                <input type="hidden" name="sub_total" id="sub_total">
                                                                {{-- display formatted --}}
                                                                <input type="text" id="sub_total_display"
                                                                    class="form-control border-0 bg-transparent p-0"
                                                                    readonly>
                                                            </td>
                                                        </tr>

                                                        <tr class="single-item">
                                                            <th class="fs-10 text-dark text-uppercase">Tax (%)</th>
                                                            <td><input type="number"
                                                                    class="form-control border-0 bg-transparent p-0"
                                                                    id="tax_percent" name="tax_percent"
                                                                    value="{{ $purchase->tax_percent }}"></td>
                                                        </tr>
                                                        <tr class="single-item">
                                                            <th class="fs-10 text-dark text-uppercase">Tax Amount</th>
                                                            <td>
                                                                <input type="hidden" name="tax_amount" id="tax_amount">
                                                                <input type="text" id="tax_amount_display"
                                                                    class="form-control border-0 bg-transparent p-0"
                                                                    readonly>
                                                            </td>
                                                        </tr>
                                                        <tr class="bg-gray-100 single-item">
                                                            <th class="fs-10 text-dark text-uppercase">Grand Total</th>
                                                            <td>
                                                                <input type="hidden" name="total_amount"
                                                                    id="total_amount">
                                                                <input type="text" id="total_amount_display"
                                                                    class="form-control border-0 bg-transparent p-0 fw-bold"
                                                                    readonly>
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
        // === FORMAT ANGKA RIBUAN ===
        function formatRibuan(angka) {
            if (angka === null || angka === undefined) return '';
            const str = angka.toString().replace(/[^0-9.-]/g, '');
            const parts = str.split('.');
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            return parts.join('.');
        }

        // === HAPUS FORMAT KOMA ===
        function unformatRibuan(angka) {
            if (!angka) return 0;
            return parseFloat(angka.toString().replace(/,/g, '')) || 0;
        }

        // === UPDATE ROW TOTAL ===
        function updateRowTotal(row) {
            const qty = parseFloat(unformatRibuan(row.find('.qty').val())) || 0;
            const price = parseFloat(unformatRibuan(row.find('.price').val())) || 0;
            const freight = parseFloat(unformatRibuan(row.find('.freight').val())) || 0;
            const total = qty * (price + freight);

            row.find('.total').val(total.toFixed(2));
            row.find('.total_display').val(formatRibuan(total.toFixed(2)));
            calc_total();
        }

        // === HITUNG TOTAL KESELURUHAN ===
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

            const taxPercent = parseFloat(unformatRibuan($('#tax_percent').val())) || 0;
            const taxAmount = (subtotalProduct * taxPercent) / 100;

            const totalProduct = subtotalProduct + taxAmount;
            const subTotal = subtotalProduct + subtotalFreight;
            const grandTotal = totalProduct + subtotalFreight;

            $('#total_amount_product').val(totalProduct.toFixed(2));
            $('#total_amount_freight').val(subtotalFreight.toFixed(2));
            $('#sub_total').val(subTotal.toFixed(2));
            $('#tax_amount').val(taxAmount.toFixed(2));
            $('#total_amount').val(grandTotal.toFixed(2));

            $('#total_amount_product_display').val(formatRibuan(totalProduct.toFixed(2)));
            $('#total_amount_freight_display').val(formatRibuan(subtotalFreight.toFixed(2)));
            $('#sub_total_display').val(formatRibuan(subTotal.toFixed(2)));
            $('#tax_amount_display').val(formatRibuan(taxAmount.toFixed(2)));
            $('#total_amount_display').val(formatRibuan(grandTotal.toFixed(2)));
        }

        // === INIT SELECT2 ===
        function initSelect2(el) {
            $(el).select2({
                placeholder: 'Pilih produk',
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

            // === FORMAT SEMUA ANGKA EXISTING (edit mode)
            $('.qty, .price, .freight').each(function() {
                const val = $(this).val();
                if (val && !isNaN(val)) $(this).val(formatRibuan(parseFloat(val).toFixed(2)));
            });

            // === HITUNG TOTAL AWAL
            $('#tab_logic tbody tr').each(function() {
                updateRowTotal($(this));
            });
            calc_total();

            // === SAAT INPUT BERUBAH ===
            $(document).on('input', '.qty, .price, .freight', function() {
                updateRowTotal($(this).closest('tr'));
            });

            // === SAAT KELUAR DARI INPUT BARU FORMAT ===
            $(document).on('blur', '.qty, .price, .freight', function() {
                const val = $(this).val().replace(/,/g, '');
                if (val === '' || isNaN(val)) return;
                $(this).val(formatRibuan(parseFloat(val)));
                updateRowTotal($(this).closest('tr'));
            });

            // === SAAT PRODUK DIPILIH ===
            $(document).on('change', '.select-product', function() {
                const row = $(this).closest('tr');
                const price = parseFloat($(this).find('option:selected').data('price')) || 0;
                row.find('.price').val(formatRibuan(price.toFixed(2)));
                updateRowTotal(row);
            });

            // === TAX BERUBAH ===
            $(document).on('input', '#tax_percent', calc_total);

            // === TAMBAH ROW ===
            $('#add_row').on('click', function() {
                const $tbody = $('#tab_logic tbody');
                const $newRow = $tbody.find('tr:first').clone();

                $newRow.attr('id', 'addr' + $tbody.find('tr').length);
                $newRow.find('td:first').text($tbody.find('tr').length + 1);
                $newRow.find('input').val('');
                $newRow.find('.freight').val('0');
                $newRow.find('.total').val('0.00');
                $newRow.find('.total_display').val('');
                $newRow.find('.select2').remove();
                $newRow.find('select').removeClass('select2-hidden-accessible').val('');

                $tbody.append($newRow);
                initSelect2($newRow.find('.select-product'));
            });

            // === HAPUS ROW ===
            $(document).on('click', '.delete-row', function() {
                if ($('#tab_logic tbody tr').length > 1) {
                    $(this).closest('tr').remove();
                    calc_total();
                }
            });

            // === SEBELUM SUBMIT, HAPUS KOMA ===
            $('#purchaseForm').on('submit', function() {
                $('.qty, .price, .freight, .total').each(function() {
                    $(this).val($(this).val().replace(/,/g, ''));
                });
            });

            // === AUTO DUE DATE ===
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
                form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

                const purchaseNumber = document.getElementById('purchase_number');
                if (!purchaseNumber.value.trim()) {
                    isValid = false;
                    showError(purchaseNumber, 'Invoice number wajib diisi');
                }

                const supplierSelect = $('#suppliers');
                if (!supplierSelect.val()) {
                    isValid = false;
                    showError(supplierSelect[0], 'Supplier wajib dipilih');
                }

                const editNote = document.getElementById('edit_note');
                if (!editNote.value.trim()) {
                    isValid = false;
                    showError(editNote, 'Catatan edit wajib diisi');
                }

                const rows = form.querySelectorAll('#tab_logic tbody tr');
                rows.forEach((row, i) => {
                    const product = row.querySelector('select[name="product[]"]');
                    const qty = row.querySelector('input[name="qty[]"]');
                    if (!product.value) {
                        isValid = false;
                        showError(product, `Produk baris ${i + 1} wajib dipilih`);
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
        });

        $(document).on('select2:open', () => {
            setTimeout(() => {
                document.querySelector('.select2-container--open .select2-search__field')?.focus();
            }, 50);
        });
    </script>
@endpush
