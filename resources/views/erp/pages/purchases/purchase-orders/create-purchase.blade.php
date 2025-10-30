@extends('erp.layouts.main')

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Purchase Order</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Purchase Order</li>
                <li class="breadcrumb-item">Create Purchase Order</li>
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
                        <span>Add Purchase Order</span>
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
                <form action="/erp/purchases/purchase-orders/store" method="POST" id="purchaseForm"
                    enctype="multipart/form-data">
                    @csrf
                    @method('POST')
                    <div class="card stretch stretch-full">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-lg-12">
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
                                                    <th class="text-center wd-100">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr id="addr0">
                                                    <td>1</td>
                                                    <td>
                                                        <select class="form-control select-product"
                                                            data-select2-selector="status" name="product[]" id="product_0">
                                                            <option value="" disabled selected hidden>Pilih produk
                                                            </option>
                                                            @foreach ($products as $product)
                                                                <option value="{{ $product->id }}">
                                                                    [{{ $product->sku }}] {{ $product->name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <input type="text" inputmode="numeric" name="qty[]"
                                                            class="form-control qty" id="qty_0" placeholder="Qty"
                                                            min="1">
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
@endsection

@push('scripts')
    <script>
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

        function formatRibuan(num) {
            if (num === null || num === undefined || num === '') return '';
            num = num.toString().replace(/\D/g, '');
            return num.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        function unformatRibuan(str) {
            if (!str) return 0;
            return parseFloat(str.toString().replace(/\./g, '')) || 0;
        }

        function showError(el, message) {
            const $el = $(el);
            if ($el.hasClass('select2-hidden-accessible')) {
                const $container = $el.siblings('.select2');
                $container.next('.invalid-feedback').remove();

                const feedback = $('<div class="invalid-feedback d-block"></div>').text(message);
                $container.after(feedback);
            } else {
                $el.addClass('is-invalid');
                let $container = $el.closest('.input-group');
                if ($container.length === 0) $container = $el.parent();
                $container.find('.invalid-feedback').remove();

                const feedback = $('<div class="invalid-feedback d-block"></div>').text(message);
                $container.append(feedback);
            }
        }

        $(document).on("change input",
            "#purchase_date, #suppliers, select[name='product[]'], input[name='qty[]']",
            function() {
                if ($(this).hasClass("select2-hidden-accessible")) {
                    $(this).siblings('.select2').next('.invalid-feedback').remove();
                } else {
                    this.classList.remove("is-invalid");
                    $(this).siblings(".invalid-feedback").remove();
                }
            }
        );

        $(document).on('select2:open', () => {
            setTimeout(() => {
                document.querySelector('.select2-container--open .select2-search__field')?.focus();
            }, 50);
        });

        $(document).ready(function() {
            initSelect2('.select-product');
            initSelect2('#suppliers');

            $(document).on('input', '.qty', function() {
                const val = $(this).val().replace(/\D/g, '');
                $(this).val(formatRibuan(val));
            });

            $('#add_row').on('click', function() {
                const $tbody = $('#tab_logic tbody');
                const $newRow = $tbody.find('tr:first').clone();
                const newIndex = $tbody.find('tr').length;

                $newRow.attr('id', 'addr' + newIndex);
                $newRow.find('td:first').text(newIndex + 1);
                $newRow.find('input').val('');
                $newRow.find('.select2').remove();
                $newRow.find('select').removeClass('select2-hidden-accessible').val('');

                $tbody.append($newRow);
                initSelect2($newRow.find('.select-product'));
            });

            $(document).on('click', '.delete-row', function() {
                if ($('#tab_logic tbody tr').length > 1) {
                    $(this).closest('tr').remove();
                }
            });

            $('#purchaseForm').on('submit', function(e) {
                let isValid = true;

                $(this).find('.is-invalid').removeClass('is-invalid');
                $(this).find('.invalid-feedback').remove();

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

                $('#tab_logic tbody tr').each(function() {
                    const product = $(this).find('select[name="product[]"]');
                    const qty = $(this).find('input[name="qty[]"]');
                    const qtyValue = unformatRibuan(qty.val());

                    if (!product.val()) {
                        isValid = false;
                        showError(product[0], 'Produk wajib dipilih');
                    }
                    if (!qty.val().trim() || qtyValue <= 0) {
                        isValid = false;
                        showError(qty[0], 'Qty wajib diisi');
                    }
                });

                $('.qty').each(function() {
                    $(this).val(unformatRibuan($(this).val()));
                });

                if (!isValid) {
                    e.preventDefault();
                }
            });
        });
    </script>
@endpush
