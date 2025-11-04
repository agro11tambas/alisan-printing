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
                                                                        data-price="{{ number_format($item->price ?? 0, 5, '.', '') }}"
                                                                        {{ isset($item->product_id) && $product->id == $item->product_id ? 'selected' : '' }}>
                                                                        [{{ $product->sku }}] {{ $product->name }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </td>
                                                        <td><input type="text" inputmode="numeric" name="qty[]"
                                                                class="form-control qty" min="1"
                                                                value="{{ number_format($item->quantity ?? 0, 0, ',', '.') }}">
                                                        </td>
                                                        <td><input type="text" inputmode="numeric" name="price[]"
                                                                class="form-control price"
                                                                value="{{ number_format($item->price ?? 0, 5, ',', '.') }}">
                                                        </td>
                                                        <td><input type="text" inputmode="numeric" name="freight[]"
                                                                class="form-control freight"
                                                                value="{{ number_format($item->freight ?? 0, 5, ',', '.') }}">
                                                        </td>
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
                                                                class="form-control price" value=""></td>
                                                        <td><input type="number" name="freight[]"
                                                                class="form-control freight" value=""></td>
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
                                                                <input type="hidden" name="sub_total" id="sub_total">
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

        // 4️⃣ Update fungsi calc_total untuk handle freight = 0
        function calc_total() {
            let subtotalProduct = 0,
                subtotalFreight = 0;

            $('#tab_logic tbody tr').each(function() {
                const row = $(this);
                const qty = parseFloat(unformatRibuan(row.find('.qty').val())) || 0;
                const price = parseFloat(unformatRibuan(row.find('.price').val())) || 0;
                const freight = parseFloat(unformatRibuan(row.find('.freight').val())) || 0;

                subtotalProduct += qty * price;
                subtotalFreight += qty * freight;

                // Kosongkan jika 0 untuk tampilan (opsional, hapus jika mau tetap tampil 0)
                if (price === 0) row.find('.price').val('0');
                // ✅ Freight tetap tampilkan 0 jika memang 0
                if (freight === 0) row.find('.freight').val('0');

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

            $("#total_amount_product_display").val(formatRibuan(totalProduct.toFixed(2)));
            $("#total_amount_freight_display").val(formatRibuan(subtotalFreight.toFixed(2)));
            $("#sub_total_display").val(formatRibuan(subTotal.toFixed(2)));
            $("#tax_amount_display").val(formatRibuan(taxAmount.toFixed(2)));
            $("#total_amount_display").val(formatRibuan(grandTotal.toFixed(2)));
        }

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

            $('#add_row').on('click', function() {
                const $tbody = $('#tab_logic tbody');
                const $newRow = $tbody.find('tr:first').clone();
                const newIndex = $tbody.find('tr').length;

                $newRow.attr('id', 'addr' + newIndex);
                $newRow.find('td:first').text(newIndex + 1);
                $newRow.find('input').val('');
                $newRow.find('.freight').val('');
                $newRow.find('.price').val('');
                $newRow.find('.total').val('');
                $newRow.find('.select2').remove();
                $newRow.find('select').removeClass('select2-hidden-accessible').val('');

                $tbody.append($newRow);
                initSelect2($newRow.find('.select-product'));
            });

            $(document).on('click', '.delete-row', function() {
                if ($('#tab_logic tbody tr').length > 1) {
                    $(this).closest('tr').remove();
                    calc_total();
                }
            });

            $(document).on('change', '.select-product', function() {
                const row = $(this).closest('tr');
                const selectedOption = $(this).find('option:selected');

                // 🔹 Ambil last price dari data attribute
                const lastPrice = parseFloat(selectedOption.data('price')) || 0;

                // 🔹 Isi otomatis kolom price dan reset freight
                row.find('.price').val(formatRibuan(lastPrice.toFixed(2)));
                row.find('.freight').val('');
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

                updateRowTotal($(this).closest('tr'));
            });

            $(document).on('input', '.price', function() {
                let val = $(this).val().replace(/[^\d,]/g, '');
                const parts = val.split(',');
                let integerPart = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                // ✅ maksimal 5 angka di belakang koma
                val = parts.length > 1 ? `${integerPart},${parts[1].slice(0, 5)}` : integerPart;
                $(this).val(val);
                updateRowTotal($(this).closest('tr'));
            });

            // ✅ Handler untuk FREIGHT sudah ada di bawah (baris 367 & 395)
            // Jadi tidak perlu ditambahkan lagi di sini

            $(document).on('input', '#tax_percent', calc_total);

            // $('#purchaseForm').on('submit', function() {
            //     $('.qty, .price, .freight, .total').each(function() {
            //         const cleanVal = unformatRibuan($(this).val());
            //         $(this).val(cleanVal);
            //     });
            // });

            // $('#purchaseForm').on('submit', function(e) {
            //     // Hapus clone sebelumnya biar gak dobel
            //     $(this).find('input[type="hidden"].submit-clone').remove();

            //     $(this).find('.qty, .price, .freight, .total').each(function() {
            //         const val = $(this).val();
            //         const clean = val.toString().replace(/\./g, '').replace(',', '.');
            //         const num = parseFloat(clean);
            //         const safeVal = isNaN(num) ? 0 : num;
            //         const name = $(this).attr('name');

            //         // Tambahkan hidden clone
            //         $(this).after(
            //             `<input type="hidden" class="submit-clone" name="${name}" value="${safeVal}">`
            //             );
            //     });
            // });


            // ✅ AUTO CLEAR ANGKA 0 SAAT FOCUS (untuk semua field angka)

            // 🎯 Handler untuk FREIGHT - Clear 0 saat focus
            $(document).on('focus', '.freight', function() {
                const val = $(this).val().trim();
                const num = unformatRibuan(val);

                // Jika nilai 0, kosongkan field
                if (num === 0) {
                    $(this).val('');
                }
            });

            // 🎯 Handler untuk PRICE - Clear 0 saat focus (opsional)
            $(document).on('focus', '.price', function() {
                const val = $(this).val().trim();
                const num = unformatRibuan(val);

                // Jika nilai 0, kosongkan field
                if (num === 0) {
                    $(this).val('');
                }
            });

            // 🎯 Handler untuk QTY - Clear 0 saat focus (opsional)
            $(document).on('focus', '.qty', function() {
                const val = $(this).val().replace(/\./g, ''); // Hapus titik pemisah ribuan
                const num = parseInt(val);

                // Jika nilai 0, kosongkan field
                if (num === 0) {
                    $(this).val('');
                }
            });

            // 🎯 Handler untuk TAX PERCENT - Clear 0 saat focus (opsional)
            $(document).on('focus', '#tax_percent', function() {
                const val = $(this).val().trim();
                const num = parseFloat(val);

                // Jika nilai 0, kosongkan field
                if (num === 0) {
                    $(this).val('');
                }
            });
        });

        $(document).on('change', '.select-product', function() {
            const row = $(this).closest('tr');
            const productId = $(this).val();
            const selectedOption = $(this).find('option:selected');

            // 🔹 Ambil last price dari data attribute dulu
            const lastPrice = parseFloat(selectedOption.data('price')) || 0;
            row.find('.price').val(formatRibuan(lastPrice.toFixed(2)));
            row.find('.freight').val('');
            updateRowTotal(row);

            // 🔹 Kalau mau override dengan data dari server (jika ada endpoint)
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

        // 🔧 VALIDASI FORM SUBMIT - AUTO-FILL FREIGHT SEBELUM VALIDASI

        // ✅ VALIDASI SUBMIT - TAMPILKAN ERROR FREIGHT LALU AUTO-FILL 0

        // $('#purchaseForm').on('submit', function(e) {
        //     let isValid = true;

        //     $(this).find('.is-invalid').removeClass('is-invalid');
        //     $(this).find('.invalid-feedback').remove();

        //     // ✅ STEP 1: VALIDASI FIELD UTAMA
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

        //     // ✅ STEP 2: VALIDASI SETIAP BARIS PRODUK
        //     $('#tab_logic tbody tr').each(function() {
        //         const product = $(this).find('select[name="product[]"]');
        //         const qty = $(this).find('input[name="qty[]"]');
        //         const price = $(this).find('input[name="price[]"]');
        //         const freight = $(this).find('input[name="freight[]"]');

        //         // Validasi Product
        //         if (!product.val()) {
        //             isValid = false;
        //             showError(product[0], 'Produk wajib dipilih');
        //         }

        //         // Validasi Qty (harus > 0)
        //         if (!qty.val() || parseFloat(unformatRibuan(qty.val())) <= 0) {
        //             isValid = false;
        //             showError(qty[0], 'Qty wajib diisi dan harus lebih dari 0');
        //         }

        //         // Validasi Price (harus > 0)
        //         const priceVal = unformatRibuan(price.val());
        //         if (!price.val() || priceVal <= 0) {
        //             isValid = false;
        //             showError(price[0], 'Harga wajib diisi dan harus lebih dari 0');
        //         }

        //         // ✅ Validasi Freight
        //         const freightVal = freight.val().trim();

        //         if (freightVal === '' || freightVal === null) {
        //             // 🔴 Tampilkan error bahwa freight harus diisi
        //             isValid = false;
        //             showError(freight[0], 'Freight harus diisi (minimal 0)');

        //             // ✅ Setelah error, auto-fill dengan 0
        //             freight.val('0');

        //         } else {
        //             // Jika sudah diisi, cek apakah angka valid
        //             const freightNum = unformatRibuan(freightVal);
        //             if (isNaN(freightNum) || freightNum < 0) {
        //                 isValid = false;
        //                 showError(freight[0], 'Freight harus berupa angka valid (minimal 0)');
        //             }
        //         }
        //     });

        //     // ✅ STEP 3: Jika tidak valid, cegah submit dan scroll ke error
        //     if (!isValid) {
        //         e.preventDefault();

        //         const firstError = $(this).find('.is-invalid, .select2 + .invalid-feedback').first();
        //         if (firstError.length) {
        //             $('html, body').animate({
        //                 scrollTop: firstError.offset().top - 100
        //             }, 300);
        //         }
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

            const editNote = $('#edit_note');
            if (!editNote.val().trim()) {
                isValid = false;
                showError(editNote[0], 'Catatan edit wajib diisi');
            }

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

        $(document).on('input', '.freight', function() {
            let val = $(this).val().replace(/[^\d,]/g, '');

            if (val === '0' || val === '0,00') {
                $(this).val('0');
                updateRowTotal($(this).closest('tr'));
                return;
            }

            const parts = val.split(',');
            let integerPart = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            // ✅ maksimal 5 angka di belakang koma
            val = parts.length > 1 ? `${integerPart},${parts[1].slice(0, 5)}` : integerPart;
            $(this).val(val);
            updateRowTotal($(this).closest('tr'));
        });

        $(document).on('blur', '.price, .freight, .qty', function() {
            let val = $(this).val().trim();
            if (val === '' || val === null) {
                $(this).val('0');
            } else {
                const num = unformatRibuan(val);
                $(this).val(formatRibuan(num));
            }
            updateRowTotal($(this).closest('tr'));
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
    </script>
@endpush
