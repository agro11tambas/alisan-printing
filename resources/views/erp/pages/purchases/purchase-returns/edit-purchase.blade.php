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
                    <a href="/erp/purchases/purchase-returns" class="btn btn-light-brand">
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
                <form action="/erp/purchases/purchase-returns/update/{{ $purchaseReturn->id }}" method="POST"
                    id="purchaseForm" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="card stretch stretch-full">
                        <div class="card-body">
                            <div class="row">
                                <input type="hidden" name="status" value="Purchase Returns">
                                <div class="col-lg-12">
                                    <div class="row mb-3 align-items-center">
                                        <div class="col-lg-2">
                                            <label for="purchase_number" class="fw-semibold">Invoice Number:</label>
                                        </div>
                                        <div class="col-lg-10 mb-0">
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="purchase_number"
                                                    name="purchase_number"
                                                    value="{{ old('purchase_number', $purchaseReturn->purchase_number) }}">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mb-3 align-items-center">
                                        <div class="col-lg-2">
                                            <label for="return_date" class="fw-semibold">Purchase Date:</label>
                                        </div>
                                        <div class="col-lg-10 mb-0">
                                            <div class="input-group">
                                                <input type="date" class="form-control" id="return_date"
                                                    name="return_date"
                                                    value="{{ old('return_date', isset($purchaseReturn->return_date) ? \Carbon\Carbon::parse($purchaseReturn->return_date)->format('Y-m-d') : date('Y-m-d')) }}">
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
                                                            {{ $supplier->id == $purchaseReturn->supplier_id ? 'selected' : '' }}>
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
                                                    <option value="14" data-bg="bg-success">Purchase Returns</option>
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
                                                <textarea class="form-control" id="edit_note" name="edit_note" rows="2" placeholder="Tambahkan catatan edit..."></textarea>
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
                                            <input type="hidden" name="inventory_warehouse_id"
                                                id="inventory_warehouse_id" value="1">
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
                                            <tbody id="tab_logic">
                                                @foreach ($remainingItems as $index => $item)
                                                    <tr id="addr{{ $index }}">
                                                        <td>{{ $index + 1 }}</td>
                                                        <input type="hidden" name="purchase_item_ids[]"
                                                            value="{{ $item->purchase_item_id }}">
                                                        <td>
                                                            <select class="form-control select-product"
                                                                data-select2-selector="status" name="product[]"
                                                                id="product_{{ $index }}">
                                                                <option value="" disabled
                                                                    {{ !isset($item->purchase_product_id) ? 'selected hidden' : '' }}>
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
                                                        <td>
                                                            <input type="text" inputmode="numeric" name="qty[]"
                                                                class="form-control qty" id="qty_{{ $index }}"
                                                                min="1" max="{{ $item->remaining_qty }}"
                                                                value="{{ $item->return_qty }}">
                                                            <small class="text-muted">Sisa max:
                                                                {{ number_format($item->remaining_qty) }}</small>
                                                        </td>
                                                        <td><input type="text" inputmode="numeric" name="price[]"
                                                                class="form-control price" id="price_{{ $index }}"
                                                                value="{{ $item->price ?? '' }}"></td>
                                                        <td>
                                                            <input type="text" inputmode="numeric" name="freight[]"
                                                                class="form-control freight"
                                                                id="freight_{{ $index }}"
                                                                value="{{ $item->freight ?? 0 }}">
                                                        </td>
                                                        <td><input type="text" inputmode="numeric" name="total[]"
                                                                class="form-control total" id="total_{{ $index }}"
                                                                readonly
                                                                value="{{ $item->total ?? $item->quantity * $item->price }}">
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="col-lg-12 mt-4">
                                    <div class="row justify-content-end">
                                        <div class="col-lg-4 mt-3">
                                            <div class="mb-4">
                                                <h5 class="fw-bold">Grand Total Breakdown:</h5>
                                            </div>
                                            <div class="table-responsive">
                                                <table class="table table-bordered" id="tab_logic_total">
                                                    <tbody>
                                                        <tr class="single-item">
                                                            <th>Total Product</th>
                                                            <td>
                                                                <input type="hidden" name="total_amount_product"
                                                                    id="total_amount_product"
                                                                    value="{{ $purchaseReturn->total_amount_product ?? 0 }}">
                                                                <input type="text" id="total_amount_product_display"
                                                                    class="form-control border-0 bg-transparent p-0"
                                                                    readonly
                                                                    value="{{ number_format($purchaseReturn->total_amount_product ?? 0, 2, ',', '.') }}">
                                                            </td>
                                                        </tr>
                                                        <tr class="single-item">
                                                            <th>Total Freight</th>
                                                            <td>
                                                                <input type="hidden" name="total_amount_freight"
                                                                    id="total_amount_freight"
                                                                    value="{{ $purchaseReturn->total_amount_freight ?? 0 }}">
                                                                <input type="text" id="total_amount_freight_display"
                                                                    class="form-control border-0 bg-transparent p-0"
                                                                    readonly
                                                                    value="{{ number_format($purchaseReturn->total_amount_freight ?? 0, 2, ',', '.') }}">
                                                            </td>
                                                        </tr>
                                                        <tr class="single-item bg-gray-100">
                                                            <th>Grand Total</th>
                                                            <td>
                                                                <input type="hidden" name="total_amount"
                                                                    id="total_amount"
                                                                    value="{{ $purchaseReturn->total_amount ?? 0 }}">
                                                                <input type="text" id="total_amount_display"
                                                                    class="form-control border-0 bg-transparent p-0 fw-700 text-dark"
                                                                    readonly
                                                                    value="{{ number_format($purchaseReturn->total_amount ?? 0, 2, ',', '.') }}">
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
        function formatRibuanID(angka, withDecimal = true) {
            if (angka === null || angka === undefined || angka === '') return '';
            const num = parseFloat(angka.toString().replace(/[^0-9,-]/g, '').replace(',', '.')) || 0;

            if (!withDecimal) {
                const ribuan = Math.floor(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                return ribuan;
            }

            const parts = num.toFixed(2).split('.');
            const ribuan = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            return `${ribuan},${parts[1]}`;
        }

        function unformatRibuanID(angka) {
            if (!angka) return 0;
            return parseFloat(angka.toString().replace(/\./g, '').replace(',', '.')) || 0;
        }

        function updateRowTotal(row) {
            const qty = unformatRibuanID(row.find('.qty').val());
            const price = unformatRibuanID(row.find('.price').val());
            const freight = unformatRibuanID(row.find('.freight').val());
            const total = qty * (price + freight);
            row.find('.total').val(formatRibuanID(total, true));
            calc_total();
        }

        function calc_total() {
            let subtotalProduct = 0,
                subtotalFreight = 0;
            $('#tab_logic tbody tr').each(function() {
                const qty = unformatRibuanID($(this).find('.qty').val());
                const price = unformatRibuanID($(this).find('.price').val());
                const freight = unformatRibuanID($(this).find('.freight').val());
                subtotalProduct += qty * price;
                subtotalFreight += qty * freight;
            });

            const subTotal = subtotalProduct + subtotalFreight;
            const grandTotal = subTotal;

            $('#total_amount_product').val(subtotalProduct.toFixed(2));
            $('#total_amount_freight').val(subtotalFreight.toFixed(2));
            $('#sub_total').val(subTotal.toFixed(2));
            $('#total_amount').val(grandTotal.toFixed(2));

            $('#total_amount_product_display').val(formatRibuanID(subtotalProduct, true));
            $('#total_amount_freight_display').val(formatRibuanID(subtotalFreight, true));
            $('#sub_total_display').val(formatRibuanID(subTotal, true));
            $('#total_amount_display').val(formatRibuanID(grandTotal, true));
        }

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
            initSelect2('#transaction_type');

            $('.qty').each(function() {
                const val = $(this).val();
                if (val && !isNaN(val)) $(this).val(formatRibuanID(parseFloat(val), false));
            });
            $('.price, .freight, .total').each(function() {
                const val = $(this).val();
                if (val && !isNaN(val)) $(this).val(formatRibuanID(parseFloat(val), true));
            });

            $(document).on('change', '.select-product', function() {
                const row = $(this).closest('tr');
                const price = parseFloat($(this).find('option:selected').data('price')) || 0;
                row.find('.price').val(formatRibuanID(price, true));
                updateRowTotal(row);
            });

            $(document).on('input', '.qty', function() {
                const input = $(this);
                let raw = input.val().replace(/\./g, '').replace(/\D/g, '');
                if (raw === '') return;

                let value = parseInt(raw) || 0;
                const max = parseInt(input.attr('max')) || 0;

                if (value > max) {
                    value = max;
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'warning',
                        title: 'Qty tidak boleh melebihi sisa maksimum (' + max.toLocaleString(
                            'id-ID') + ')',
                        showConfirmButton: false,
                        timer: 1500
                    });
                }

                input.val(formatRibuanID(value.toString(), false));

                updateRowTotal(input.closest('tr'));
            });

            $(document).on('paste', '.qty', function() {
                setTimeout(() => $(this).trigger('input'), 10);
            });

            $(document).on('input', '.price, .freight', function() {
                const raw = $(this).val().replace(/\./g, '').replace(/[^0-9,]/g, '');
                $(this).val(formatRibuanID(raw, true));
                updateRowTotal($(this).closest('tr'));
            });

            $('#tab_logic tbody tr').each(function() {
                updateRowTotal($(this));
            });
            calc_total();

            $('#purchaseForm').on('submit', function() {
                $('.qty, .price, .freight, .total').each(function() {
                    $(this).val(unformatRibuanID($(this).val()));
                });
            });
        });

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
