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
                <form action="/erp/purchases/purchase-orders/update/{{ $purchase->id }}" method="POST" id="purchaseForm"
                    enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="card stretch stretch-full">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-lg-12">
                                    <!-- <div class="row mb-3 align-items-center">
                                                                                                    <div class="col-lg-2">
                                                                                                        <label for="purchase_number" class="fw-semibold">Paid Amount:</label>
                                                                                                    </div>
                                                                                                    <div class="col-lg-10 mb-0">
                                                                                                        <div class="input-group">
                                                                                                            <input type="text" class="form-control" id="purchase_number" name="purchase_number" value="{{ old('purchase_number', $purchase->purchase_number) }}">
                                                                                                        </div>
                                                                                                    </div>
                                                                                                </div> -->
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
                                    <!--  -->
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
                                                        <td><input type="text" inputmode="numeric" pattern="[0-9.,]*"
                                                                name="qty[]" class="form-control qty"
                                                                value="{{ number_format($item->quantity ?? 0, 2, ',', '.') }}">
                                                        </td>

                                                        <td><input type="text" inputmode="numeric" pattern="[0-9.,]*"
                                                                name="price[]" class="form-control price"
                                                                value="{{ number_format($item->price ?? 0, 2, ',', '.') }}">
                                                        </td>

                                                        <td><input type="text" inputmode="numeric" pattern="[0-9.,]*"
                                                                name="freight[]" class="form-control freight"
                                                                value="{{ number_format($item->freight ?? 0, 2, ',', '.') }}">
                                                        </td>

                                                        <td><input type="text" inputmode="numeric" pattern="[0-9.,]*"
                                                                name="total[]" class="form-control total" readonly
                                                                value="{{ number_format($item->quantity * ($item->price + $item->freight), 2, ',', '.') }}">
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
                                                        <td>
                                                            <input type="text" inputmode="numeric" name="qty[]"
                                                                class="form-control qty"
                                                                value="{{ number_format($item->quantity ?? 0, 2, ',', '.') }}">
                                                        </td>
                                                        <td>
                                                            <input type="text" inputmode="numeric" name="price[]"
                                                                class="form-control price"
                                                                value="{{ number_format($item->price ?? 0, 2, ',', '.') }}">
                                                        </td>
                                                        <td>
                                                            <input type="text" inputmode="numeric" name="freight[]"
                                                                class="form-control freight"
                                                                value="{{ number_format($item->freight ?? 0, 2, ',', '.') }}">
                                                        </td>
                                                        <td>
                                                            <input type="text" inputmode="numeric" name="total[]"
                                                                class="form-control total" readonly
                                                                value="{{ number_format($item->quantity * ($item->price + $item->freight), 2, ',', '.') }}">
                                                        </td>

                                                        <td><input type="number" name="total[]"
                                                                class="form-control total" readonly></td>
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
                                                            <th>Total Produk</th>
                                                            <td>
                                                                <input type="hidden" name="total_amount_product"
                                                                    id="total_amount_product">
                                                                <input type="text" id="total_amount_product_display"
                                                                    class="form-control border-0 bg-transparent p-0"
                                                                    readonly>
                                                            </td>
                                                        </tr>
                                                        <tr class="single-item">
                                                            <th>Total Freight</th>
                                                            <td>
                                                                <input type="hidden" name="total_amount_freight"
                                                                    id="total_amount_freight">
                                                                <input type="text" id="total_amount_freight_display"
                                                                    class="form-control border-0 bg-transparent p-0"
                                                                    readonly>
                                                            </td>
                                                        </tr>
                                                        <tr class="single-item">
                                                            <th>Sub Total</th>
                                                            <td>
                                                                <input type="hidden" name="sub_total" id="sub_total">
                                                                <input type="text" id="sub_total_display"
                                                                    class="form-control border-0 bg-transparent p-0"
                                                                    readonly>
                                                            </td>
                                                        </tr>
                                                        <tr class="single-item">
                                                            <th>Tax (%)</th>
                                                            <td>
                                                                <div class="input-group mb-2 mb-sm-0">
                                                                    <input type="number" name="tax_percent"
                                                                        id="tax_percent"
                                                                        class="form-control border-0 bg-transparent p-0"
                                                                        value="{{ $purchase->tax_percent ?? 0 }}"
                                                                        min="0" max="100" step="0.01">
                                                                    <span class="input-group-text">%</span>
                                                                </div>
                                                            </td>
                                                        </tr>

                                                        <tr class="single-item">
                                                            <th>Tax Amount</th>
                                                            <td>
                                                                <input type="hidden" name="tax_amount" id="tax_amount"
                                                                    value="{{ $purchase->tax_amount ?? 0 }}">
                                                                <input type="text" id="tax_amount_display"
                                                                    class="form-control border-0 bg-transparent p-0"
                                                                    value="{{ number_format($purchase->tax_amount ?? 0, 2, ',', '.') }}"
                                                                    readonly>
                                                            </td>
                                                        </tr>
                                                        <tr class="single-item">
                                                            <th class="bg-gray-100">Grand Total</th>
                                                            <td class="bg-gray-100">
                                                                <input type="hidden" name="total_amount"
                                                                    id="total_amount">
                                                                <input type="text" id="total_amount_display"
                                                                    class="form-control border-0 bg-transparent p-0 fw-bold text-dark"
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
        // === FORMAT ANGKA RIBUAN (INDONESIA STYLE) ===
        function formatRibuan(angka) {
            if (angka === null || angka === undefined || isNaN(angka)) return '0';
            const parts = parseFloat(angka).toFixed(2).split('.');
            const ribuan = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            return ribuan + ',' + parts[1];
        }

        // === UNFORMAT RIBUAN KE ANGKA MURNI ===
        function unformatRibuan(angka) {
            if (!angka) return 0;
            return parseFloat(angka.toString().replace(/\./g, '').replace(',', '.')) || 0;
        }

        // === PERHITUNGAN TIAP BARIS ===
        function updateRowTotal(row) {
            let qty = unformatRibuan(row.find(".qty").val());
            let price = unformatRibuan(row.find(".price").val());
            let freight = unformatRibuan(row.find(".freight").val());

            // Pastikan bukan NaN
            qty = isNaN(qty) ? 0 : qty;
            price = isNaN(price) ? 0 : price;
            freight = isNaN(freight) ? 0 : freight;

            const total = qty * (price + freight);

            row.find(".total").val(formatRibuan(total || 0));
            calc_total();
        }


        // === PERHITUNGAN TOTAL AKHIR ===
        function calc_total() {
            let subtotalProduct = 0,
                subtotalFreight = 0;

            $('#tab_logic tbody tr').each(function() {
                const qty = unformatRibuan($(this).find('.qty').val());
                const price = unformatRibuan($(this).find('.price').val());
                const freight = unformatRibuan($(this).find('.freight').val());
                subtotalProduct += qty * price;
                subtotalFreight += qty * freight;
            });

            const taxPercent = unformatRibuan($("#tax_percent").val());
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

            // Tambah row
            $('#add_row').on('click', function() {
                const $tbody = $('#tab_logic tbody');
                const $newRow = $tbody.find('tr:first').clone();
                const newIndex = $tbody.find('tr').length;

                $newRow.attr('id', 'addr' + newIndex);
                $newRow.find('td:first').text(newIndex + 1);
                $newRow.find('input').val('');
                $newRow.find('.freight').val('0');
                $newRow.find('.total').val('0.00');
                $newRow.find('.select2').remove();
                $newRow.find('select').removeClass('select2-hidden-accessible').val('');

                $tbody.append($newRow);
                initSelect2($newRow.find('.select-product'));
            });

            // Hapus row
            $(document).on('click', '.delete-row', function() {
                if ($('#tab_logic tbody tr').length > 1) {
                    $(this).closest('tr').remove();
                    calc_total();
                }
            });

            // Produk berubah → isi harga otomatis
            $(document).on('change', '.select-product', function() {
                const row = $(this).closest('tr');
                const price = parseFloat($(this).find('option:selected').data('price')) || 0;
                row.find('.price').val(formatRibuan(price.toFixed(2)));
                updateRowTotal(row);
            });

            // Qty / Price / Freight berubah
            // Saat mengetik — hanya hitung total, jangan ubah tampilan
            $(document).on('input', '.qty, .price, .freight', function() {
                updateRowTotal($(this).closest('tr'));
            });

            // Saat keluar dari input (blur) — baru format ribuan
            $(document).on('blur', '.qty, .price, .freight', function() {
                let val = unformatRibuan($(this).val());
                $(this).val(formatRibuan(val));
                updateRowTotal($(this).closest('tr'));
            });

            // Tax berubah
            $(document).on('input', '#tax_percent', calc_total);

            // Sebelum submit, hapus format koma
            $('#purchaseForm').on('submit', function(e) {
                // Pastikan tidak error JS
                try {
                    $('.qty, .price, .freight, .total').each(function() {
                        let val = $(this).val() || '0';
                        $(this).val(val.toString().replace(/\./g, '').replace(',',
                        '.')); // ubah ke format angka murni
                    });
                } catch (err) {
                    console.error('Error sebelum submit:', err);
                }
                // IZINKAN form tetap submit
                return true;
            });

        });
    </script>
@endpush
