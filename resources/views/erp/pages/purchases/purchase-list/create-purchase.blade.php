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
                                                                @php
                                                                    $lastPrice =
                                                                        $product->latestPurchaseItem->price ??
                                                                        ($product->price ?? 0);
                                                                @endphp
                                                                <option value="{{ $product->id }}"
                                                                    data-price="{{ $lastPrice }}">
                                                                    [{{ $product->sku }}] {{ $product->name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                    <td><input type="text" inputmode="numeric" name="qty[]"
                                                            class="form-control qty" id="qty_0" placeholder="Qty"
                                                            min="1"></td>
                                                    <td><input type="text" inputmode="numeric" name="price[]"
                                                            class="form-control price" id="price_0"></td>
                                                    <td><input type="text" inputmode="numeric" name="freight[]"
                                                            class="form-control freight" id="freight_0">
                                                    </td>
                                                    <td>
                                                        <input type="hidden" name="total[]" class="form-control total"
                                                            id="total_0">
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
        function formatRibuan(angka) {
            if (angka === null || angka === undefined || angka === '') return '';

            const num = parseFloat(angka.toString().replace(/[^0-9,.-]/g, '').replace(',', '.')) || 0;

            let [integer, decimal] = num.toFixed(2).split('.');
            integer = integer.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            return `${integer},${decimal}`;
        }

        function unformatRibuan(angka) {
            if (!angka) return 0;
            const str = angka.toString().trim();

            if (str.includes(',')) {
                return parseFloat(str.replace(/\./g, '').replace(',', '.')) || 0;
            }

            if (str.includes('.')) {
                return parseFloat(str.replace(/\./g, '')) || 0;
            }

            return parseFloat(str) || 0;
        }

        function updateRowTotal(row) {
            const qty = parseFloat(unformatRibuan(row.find(".qty").val())) || 0;
            const price = parseFloat(unformatRibuan(row.find(".price").val())) || 0;
            const freight = parseFloat(unformatRibuan(row.find(".freight").val())) || 0;

            const total = qty * (price + freight);
            if (total > 0) {
                row.find(".total").val(total.toFixed(2));
                row.find(".total_display").val(formatRibuan(total.toFixed(2)));
            } else {
                row.find(".total").val('');
                row.find(".total_display").val('');
            }

            calc_total();
        }

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

                if (price === 0) row.find('.price').val('');
                if (freight === 0) row.find('.freight').val('');
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
                const val = $(this).val();
                if (val && !isNaN(val)) {
                    $(this).val(formatRibuan(parseFloat(val)));
                }
            });

            $('#tab_logic tbody tr').each(function() {
                updateRowTotal($(this));
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
                const price = parseFloat($(this).find('option:selected').data('price')) || 0;
                row.find('.price').val(formatRibuan(price.toFixed(2)));
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

            $(document).on('blur', '.qty', function() {
                let val = $(this).val().replace(/\D/g, '');
                $(this).val(val ? val.replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '');
            });

            $(document).on('input', '.price, .freight', function() {
                let val = $(this).val().replace(/[^\d,]/g, '');
                const parts = val.split(',');

                // format bagian ribuan (integer)
                let integerPart = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                val = parts.length > 1 ? `${integerPart},${parts[1].slice(0, 2)}` : integerPart;

                $(this).val(val);
                updateRowTotal($(this).closest('tr'));
            });

            $(document).on('blur', '.price, .freight', function() {
                const raw = $(this).val();
                const formatted = formatRibuan(raw);
                $(this).val(formatted);
                updateRowTotal($(this).closest('tr'));
            });

            $(document).on('input', '#tax_percent', calc_total);

            $('#purchaseForm').on('submit', function() {
                $('.qty, .price, .freight, .total').each(function() {
                    const cleanVal = unformatRibuan($(this).val());
                    $(this).val(cleanVal);
                });
            });

        });

        $(document).on('select2:open', () => {
            setTimeout(() => {
                document.querySelector('.select2-container--open .select2-search__field')?.focus();
            }, 50);
        });

        $(document).on('change', '.select-product', function() {
            const productId = $(this).val();
            const row = $(this).closest('tr');

            if (!productId) {
                row.find('.price').val('');
                row.find('.freight').val('');
                updateRowTotal(row);
                return;
            }

            $.ajax({
                url: `/erp/purchases/get-latest-price/${productId}`,
                type: 'GET',
                success: function(response) {
                    const price = response.price ? parseFloat(response.price) : 0;
                    const freight = response.freight ? parseFloat(response.freight) : 0;

                    row.find('.price').val(formatNumberInput(price));
                    row.find('.freight').val(formatNumberInput(freight));

                    updateRowTotal(row);
                },
                error: function() {
                    row.find('.price').val('');
                    row.find('.freight').val('');
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

        $('#purchaseForm').on('submit', function(e) {
            let isValid = true;

            $(this).find('.is-invalid').removeClass('is-invalid');
            $(this).find('.invalid-feedback').remove();

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

            const editNote = document.getElementById('edit_note');
            if (editNote && !editNote.value.trim()) {
                isValid = false;
                showError(editNote, 'Catatan edit wajib diisi');
            }

            const transactionType = $('#transaction_type');
            if (!transactionType.val()) {
                isValid = false;
                showError(transactionType[0], 'Tipe transaksi wajib dipilih');
            }

            $('#tab_logic tbody tr').each(function() {
                const product = $(this).find('select[name="product[]"]');
                const qty = $(this).find('input[name="qty[]"]');
                const price = $(this).find('input[name="price[]"]');
                const freight = $(this).find('input[name="freight[]"]');

                if (!product.val()) {
                    isValid = false;
                    showError(product[0], 'Produk wajib dipilih');
                }
                if (!qty.val() || parseFloat(qty.val().replace(/\./g, '')) <= 0) {
                    isValid = false;
                    showError(qty[0], 'Qty wajib diisi');
                }
                if (!price.val() || parseFloat(price.val().replace(/\./g, '')) <= 0) {
                    isValid = false;
                    showError(price[0], 'Harga wajib diisi');
                }
                if (!freight.val() || isNaN(parseFloat(freight.val().replace(/\./g, '')))) {
                    isValid = false;
                    showError(freight[0], 'Freight wajib diisi (isi 0 jika tidak ada)');
                }
            });

            if (!isValid) {
                e.preventDefault();
            }
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
    </script>
@endpush
