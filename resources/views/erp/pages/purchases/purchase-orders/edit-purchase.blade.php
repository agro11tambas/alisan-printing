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
    </style>
@endpush

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Purchase Order</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Purchase Order</li>
                <li class="breadcrumb-item">Edit Purchase Order</li>
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
                        <span>Update Purchase Order</span>
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
                <form action="/erp/purchases/purchase-orders/update/{{ $purchase->id }}" method="POST" id="purchaseForm">
                    @csrf
                    @method('PUT')
                    <div class="card stretch stretch-full">
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-lg-2">
                                    <label for="purchase_date" class="fw-semibold">Purchase Date:</label>
                                </div>
                                <div class="col-lg-10">
                                    <input type="datetime-local" class="form-control" id="purchase_date" name="purchase_date"
                                        value="{{ old('purchase_date', \Carbon\Carbon::parse($purchase->purchase_date)->format('Y-m-d\TH:i')) }}">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-lg-2">
                                    <label for="suppliers" class="fw-semibold">Supplier:</label>
                                </div>
                                <div class="col-lg-10">
                                    <select class="form-select form-control max-select" id="suppliers" name="suppliers">
                                        <option disabled selected hidden>Choose supplier</option>
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
                    </div>
                    <div class="card stretch stretch-full">
                        <div class="card-body">
                            <div class="mb-4">
                                <h5 class="fw-bold">Products:</h5>
                            </div>
                            <div class="table-responsive">
                                <input type="hidden" name="inventory_warehouse_id" value="1">
                                <table class="table table-bordered" id="tab_logic">
                                    <thead>
                                        <tr class="text-center">
                                            <th class="wd-50">#</th>
                                            <th class="wd-450">Product</th>
                                            <th class="wd-150">Qty</th>
                                            <th class="wd-100">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($purchase->purchaseItems as $index => $item)
                                            <tr id="addr{{ $index }}">
                                                <td>{{ $index + 1 }}</td>
                                                <input type="hidden" name="purchase_item_ids[]"
                                                    value="{{ $item->id }}">
                                                <td>
                                                    <select class="form-control select-product" name="product[]">
                                                        <option value="" disabled
                                                            {{ !$item->product_id ? 'selected hidden' : '' }}>Pilih produk
                                                        </option>
                                                        @foreach ($products as $product)
                                                            <option value="{{ $product->id }}"
                                                                {{ $item->product_id == $product->id ? 'selected' : '' }}>
                                                                [{{ $product->sku }}] {{ $product->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="text" inputmode="numeric" name="qty[]"
                                                        class="form-control qty"
                                                        value="{{ number_format($item->quantity ?? 0, 0, ',', '.') }}">
                                                </td>
                                                <td class="text-center">
                                                    <div class="d-flex justify-content-center">
                                                        <button type="button" class="btn btn-danger delete-row">
                                                            <i class="feather-trash-2"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr id="addr0">
                                                <td>1</td>
                                                <input type="hidden" name="purchase_item_ids[]" value="">
                                                <td>
                                                    <select class="form-control select-product" name="product[]">
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
                                                        class="form-control qty" placeholder="Qty">
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
                                <button type="button" id="add_row" class="btn btn-md btn-primary">Add Items</button>
                            </div>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script type="text/template" id="row-template-po">
<tr>
    <td>__INDEX__</td>
    <td>
        <select class="form-control select-product"
            name="product[]">
            <option value="" disabled selected hidden>Pilih produk</option>
            @foreach ($products as $product)
                <option value="{{ $product->id }}">
                    [{{ $product->sku }}] {{ $product->name }}
                </option>
            @endforeach
        </select>
    </td>
    <td>
        <input type="text" inputmode="numeric" name="qty[]" class="form-control qty" value="0">
    </td>
    <td class="text-center">
        <div class="d-flex justify-content-center">
            <button type="button" class="btn btn-danger delete-row">
                <i class="feather-trash-2"></i>
            </button>
        </div>
    </td>
</tr>
</script>
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

        $(document).on('select2:open', () => {
            setTimeout(() => {
                document.querySelector('.select2-container--open .select2-search__field')?.focus();
            }, 50);
        });

        $(document).ready(function() {
            initSelect2('.select-product');
            initSelect2('#suppliers');

            // Format angka qty
            $(document).on('input', '.qty', function() {
                const val = $(this).val().replace(/\D/g, '');
                $(this).val(formatRibuan(val));
            });

            $('#add_row').on('click', function() {
                const $tbody = $('#tab_logic tbody');
                const newIndex = $tbody.find('tr').length + 1;

                // Ambil template yang BENAR
                let template = $('#row-template-po').html();

                // Replace index
                template = template.replace('__INDEX__', newIndex);

                // Convert template ke element
                const $newRow = $(template);

                // Append ke tabel
                $tbody.append($newRow);

                // Init select2 di baris baru
                initSelect2($newRow.find('.select-product'));
            });

            // Delete row
            $(document).on('click', '.delete-row', function() {
                if ($('#tab_logic tbody tr').length > 1) {
                    $(this).closest('tr').remove();
                }
            });

            // Form validation
            $('#purchaseForm').on('submit', function(e) {
                let isValid = true;
                $(this).find('.is-invalid').removeClass('is-invalid');
                $(this).find('.invalid-feedback').remove();

                const date = $('#purchase_date');
                const supplier = $('#suppliers');

                if (!date.val().trim()) {
                    isValid = false;
                    date.addClass('is-invalid');
                }

                if (!supplier.val()) {
                    isValid = false;
                    supplier.addClass('is-invalid');
                }

                $('#tab_logic tbody tr').each(function() {
                    const product = $(this).find('select[name="product[]"]');
                    const qty = $(this).find('input[name="qty[]"]');
                    const qtyValue = unformatRibuan(qty.val());

                    if (!product.val()) {
                        isValid = false;
                        product.addClass('is-invalid');
                    }
                    if (!qty.val().trim() || qtyValue <= 0) {
                        isValid = false;
                        qty.addClass('is-invalid');
                    }
                });

                $('.qty').each(function() {
                    $(this).val(unformatRibuan($(this).val()));
                });

                if (!isValid) e.preventDefault();
            });
        });

        $(document).on('select2:open', () => {
            setTimeout(() => {
                document.querySelector('.select2-container--open .select2-search__field')?.focus();
            }, 50);
        });
    </script>
@endpush
