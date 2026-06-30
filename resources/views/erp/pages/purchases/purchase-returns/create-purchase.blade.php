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
        .select2-container--default .select2-selection--single {
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
            padding: 8px 12px !important;
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
            grid-template-columns: minmax(380px, 4fr) 130px 130px 130px 130px;
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

        .product-grid .form-group>label {
            display: none !important;
        }

        .product-grid .form-control,
        .product-grid .select2-container--default .select2-selection--single {
            height: 44px !important;
        }
    </style>
@endpush

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
                        <span>Add Purchase Returns</span>
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
    <div class="main-content m-0 m-md-2 m-lg-2 p-0 p-md-0 p-lg-0 pt-2 pt-md-0">
        <div class="row">
            <div class="col-12">
                <form action="/erp/purchases/purchase-returns/store" method="POST" id="purchaseForm"
                    enctype="multipart/form-data">
                    @csrf
                    @method('POST')
                    <div class="card stretch stretch-full">
                        <div class="card-body">
                            <div class="row">
                                <input type="hidden" name="purchase_id" value="{{ $purchase->id }}">
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
                                                    value="{{ old('purchase_number', $purchase->purchase_number) }}">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mb-3 align-items-center">
                                        <div class="col-lg-2">
                                            <label for="return_date" class="fw-semibold">Purchase Date:</label>
                                        </div>
                                        <div class="col-lg-10 mb-0">
                                            <div class="input-group">
                                                <input type="datetime-local" class="form-control" id="return_date"
                                                    name="return_date"
                                                    value="{{ old('return_date', isset($purchase->return_date) ? \Carbon\Carbon::parse($purchase->return_date)->format('Y-m-d\TH:i') : date('Y-m-d\TH:i')) }}">
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
                                            <label for="note" class="fw-semibold">Note:</label>
                                        </div>
                                        <div class="col-lg-10 mb-0">
                                            <div class="input-group">
                                                <textarea name="note" id="note" class="form-control" rows="2" placeholder="Tambahkan catatan (opsional)"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <input type="hidden" value="14" name="transaction_type" id="transaction_type">
                                    {{-- <div class="row mb-3 align-items-center">
                                        <div class="col-lg-2">
                                            <label for="transaction_type" class="fw-semibold">Purchase:</label>
                                        </div>
                                        <div class="col-lg-10 mb-0">
                                            <div class="input-group">
                                                <select class="form-select form-control max-select"
                                                    data-select2-selector="tag" id="transaction_type"
                                                    name="transaction_type">
                                                    <option value="14" data-bg="bg-success">Purchase Return</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div> --}}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="mb-4">
                                    <h5 class="fw-bold">Add Products:</h5>
                                </div>
                                <input type="hidden" name="inventory_warehouse_id" id="inventory_warehouse_id"
                                    value="1">

                                <div class="product-grid product-grid-header mb-2">
                                    <div class="product-col-span-2">Product</div>
                                    <div>Qty</div>
                                    <div>Price</div>
                                    <div>Freight</div>
                                    <div>Total</div>
                                </div>

                                <div id="product_list">
                                    @foreach ($purchase->purchaseItems as $index => $item)
                                        <div class="product-item" data-index="{{ $index }}">
                                            <div class="product-grid">
                                                <input type="hidden" name="purchase_item_ids[]"
                                                    value="{{ $item->id }}">

                                                <div class="form-group product-col-span-2">
                                                    <label>Product</label>
                                                    <select class="form-control select-product" name="product[]"
                                                        id="product_{{ $index }}">
                                                        <option value="" disabled>Pilih produk</option>
                                                        @foreach ($products as $product)
                                                            <option value="{{ $product->id }}"
                                                                data-price="{{ $product->price }}"
                                                                {{ $product->id == $item->product_id ? 'selected' : '' }}>
                                                                [{{ $product->sku }}] {{ $product->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div class="form-group">
                                                    <label>Qty</label>
                                                    <input type="text" inputmode="numeric" name="qty[]"
                                                        class="form-control qty" id="qty_{{ $index }}"
                                                        min="1" max="{{ $item->remaining_qty }}">

                                                    <small class="text-muted">
                                                        Sisa max: {{ number_format($item->remaining_qty) }}
                                                    </small>
                                                </div>

                                                <div class="form-group">
                                                    <label>Price</label>
                                                    <input type="text" name="price[]" class="form-control price"
                                                        value="{{ number_format($item->price ?? 0, 2, ',', '.') }}">
                                                </div>

                                                <div class="form-group">
                                                    <label>Freight</label>
                                                    <input type="text" name="freight[]" class="form-control freight"
                                                        value="{{ number_format($item->freight ?? 0, 2, ',', '.') }}">
                                                </div>

                                                <div class="form-group">
                                                    <label>Total</label>
                                                    <input type="text" name="total[]" class="form-control total"
                                                        readonly
                                                        value="{{ $item->total ?? $item->quantity * $item->price + ($item->freight ?? 0) }}">
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="col-lg-12 mt-4">
                                <div class="row justify-content-end">
                                    <div class="col-lg-4 mt-3">
                                        <div class="mb-4">
                                            <h5 class="fw-bold">Grand Total:</h5>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-bordered" id="tab_logic_total">
                                                <tbody>
                                                    <tr class="single-item">
                                                        <th>Total Produk</th>
                                                        <td>
                                                            <input type="hidden" name="total_amount_product"
                                                                id="total_amount_product">
                                                            <input type="text" id="total_amount_product_display"
                                                                class="form-control border-0 bg-transparent p-0" readonly>
                                                        </td>
                                                    </tr>
                                                    <tr class="single-item">
                                                        <th>Total Freight</th>
                                                        <td>
                                                            <input type="hidden" name="total_amount_freight"
                                                                id="total_amount_freight">
                                                            <input type="text" id="total_amount_freight_display"
                                                                class="form-control border-0 bg-transparent p-0" readonly>
                                                        </td>
                                                    </tr>
                                                    <tr class="single-item">
                                                        <th>Sub Total</th>
                                                        <td>
                                                            <input type="hidden" name="sub_total" id="sub_total">
                                                            <input type="text" id="sub_total_display"
                                                                class="form-control border-0 bg-transparent p-0" readonly>
                                                        </td>
                                                    </tr>
                                                    <tr class="single-item">
                                                        <th class="fs-10 text-dark text-uppercase bg-gray-100">Grand
                                                            Total</th>
                                                        <td class="bg-gray-100">
                                                            <input type="hidden" name="total_amount" id="total_amount">
                                                            <input type="text" id="total_amount_display"
                                                                class="form-control border-0 bg-transparent p-0 fw-700 text-dark"
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
        /* ===================== FORMAT / PARSING ===================== */
        function formatRibuan(value) {
            if (value === null || value === undefined || value === '') return '';
            const num = parseFloat(value);
            if (isNaN(num)) return '';
            let [intPart, decPart] = num.toFixed(5).split('.');
            const formattedInt = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            decPart = decPart.replace(/0+$/, '');
            return decPart ? `${formattedInt},${decPart}` : formattedInt;
        }

        function unformatRibuan(value) {
            if (!value) return 0;
            value = value.toString().replace(/[^0-9,.-]/g, '');
            if (value.includes(',')) {
                value = value.replace(/\./g, '').replace(',', '.');
            } else {
                value = value.replace(/\./g, '');
            }
            const num = parseFloat(value);
            return isNaN(num) ? 0 : num;
        }

        /* ===================== HITUNG TOTAL ===================== */
        function updateRowTotal(row) {
            const qty = parseFloat(unformatRibuan(row.find(".qty").val())) || 0;
            const price = parseFloat(unformatRibuan(row.find(".price").val())) || 0;
            const freight = parseFloat(unformatRibuan(row.find(".freight").val())) || 0;
            const total = qty * (price + freight);

            row.find(".total").val(total > 0 ? formatRibuan(total.toFixed(2)) : '');
            calc_total();
        }

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
            });

            const subTotal = subtotalProduct + subtotalFreight;
            const grandTotal = subTotal;

            $("#total_amount_product").val(subtotalProduct.toFixed(2));
            $("#total_amount_freight").val(subtotalFreight.toFixed(2));
            $("#sub_total").val(subTotal.toFixed(2));
            $("#total_amount").val(grandTotal.toFixed(2));

            $("#total_amount_product_display").val(formatRibuan(subtotalProduct.toFixed(2)));
            $("#total_amount_freight_display").val(formatRibuan(subtotalFreight.toFixed(2)));
            $("#sub_total_display").val(formatRibuan(subTotal.toFixed(2)));
            $("#total_amount_display").val(formatRibuan(grandTotal.toFixed(2)));
        }

        /* ===================== INIT ===================== */
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

            $('.price, .freight').each(function() {
                const num = unformatRibuan($(this).val());
                $(this).val(formatRibuan(num));
            });

            $('.product-item').each(function() {
                updateRowTotal($(this));
            });
            calc_total();
        });

        /* ===================== HANDLER INPUT QTY (LIMIT + TOAST) ===================== */
        $(document).on('input', '.qty', function() {
            const input = $(this);
            const row = input.closest('.product-item');
            const max = parseInt(input.attr('max')) || 0;

            // ambil angka mentah tanpa titik
            const raw = input.val().replace(/\./g, '');
            if (raw === '') return;

            let value = parseInt(raw) || 0;

            // kalau melebihi sisa max
            if (value > max) {
                value = max;

                // tampilkan toast kecil di pojok kanan atas
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'warning',
                    title: 'Qty tidak boleh melebihi sisa maksimum (' + max.toLocaleString('id-ID') + ')',
                    showConfirmButton: false,
                    timer: 1500,
                });
            }

            // format angka dengan titik ribuan
            input.val(value.toLocaleString('id-ID'));

            updateRowTotal(row);
        });


        $(document).on('input', '.price, .freight', function() {
            let val = $(this).val().replace(/[^\d,]/g, '');
            const parts = val.split(',');
            const integerPart = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
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

        /* ===================== AUTO CLEAR 0 SAAT FOCUS ===================== */
        $(document).on('focus', '.price, .freight, .qty', function() {
            const num = unformatRibuan($(this).val());
            if (num === 0) $(this).val('');
        });

        /* ===================== VALIDASI FORM ===================== */
        function showError(el, message) {
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

        $('#purchaseForm').on('submit', function(e) {
            let isValid = true;
            $(this).find('.is-invalid').removeClass('is-invalid');
            $(this).find('.invalid-feedback').remove();

            const purchaseNumber = $('#purchase_number');
            if (!purchaseNumber.val().trim()) {
                isValid = false;
                showError(purchaseNumber[0], 'Nomor invoice wajib diisi');
            }

            const returnDate = $('#return_date');
            if (!returnDate.val().trim()) {
                isValid = false;
                showError(returnDate[0], 'Tanggal retur wajib diisi');
            }

            const supplier = $('#suppliers');
            if (!supplier.val()) {
                isValid = false;
                showError(supplier[0], 'Supplier wajib dipilih');
            }

            $('.product-item').each(function() {
                const product = $(this).find('select[name="product[]"]');
                const qty = $(this).find('input[name="qty[]"]');
                const price = $(this).find('input[name="price[]"]');
                const freight = $(this).find('input[name="freight[]"]');

                if (!product.val()) {
                    isValid = false;
                    showError(product[0], 'Produk wajib dipilih');
                }

                if (!qty.val() || parseFloat(unformatRibuan(qty.val())) <= 0) {
                    isValid = false;
                    showError(qty[0], 'Qty harus lebih dari 0');
                }

                const priceVal = unformatRibuan(price.val());
                if (!price.val() || priceVal <= 0) {
                    isValid = false;
                    showError(price[0], 'Harga wajib diisi dan harus lebih dari 0');
                }

                const freightVal = freight.val().trim();
                if (freightVal === '' || freightVal === null) {
                    isValid = false;
                    showError(freight[0], 'Freight wajib diisi (minimal 0)');
                    freight.val('0');
                }
            });

            if (!isValid) {
                e.preventDefault();
                const firstError = $(this).find('.is-invalid').first();
                if (firstError.length) {
                    $('html, body').animate({
                        scrollTop: firstError.offset().top - 100
                    }, 300);
                }
            } else {
                // Sebelum submit, bersihkan format angka
                $('.qty, .price, .freight, .total').each(function() {
                    const cleanVal = unformatRibuan($(this).val());
                    $(this).val(cleanVal);
                });
            }
        });
    </script>
@endpush
