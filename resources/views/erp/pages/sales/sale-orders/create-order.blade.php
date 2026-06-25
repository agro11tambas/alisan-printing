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

        /* 🔹 Perbesar font untuk kolom Qty, Price, dan Total */
        .product-grid input.qty,
        .product-grid input.price_before_discount_display,
        .product-grid input.total_before_discount_display {
            font-size: 16px !important;
            font-weight: 600;
            height: 44px !important;
        }

        /* 🔹 Untuk tampilan readonly total biar lebih kontras */
        #tab_logic input.total_before_discount_display[readonly] {
            background-color: #f8f9fa !important;
            color: #198754 !important;
        }

        /* 🔹 Perbesar font Grand Total biar seragam */
        #tab_logic_total input.form-control {
            font-size: 16px !important;
            font-weight: 600 !important;
            height: 44px !important;
        }

        #notes {
            font-size: 16px;
        }

        #notes::placeholder {
            font-size: 16px;
        }

        .product-item {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 14px;
            margin-bottom: 12px;
            /* background: #fff; */
        }

        .product-item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .product-number {
            font-weight: 600;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
        }

        .product-col-span-2 {
            grid-column: span 2;
        }

        /* MOBILE */
        @media (max-width: 768px) {

            /* CARD super tipis */
            .card-body {
                padding: 6px !important;
            }

            .select2-container--default .select2-selection--single {
                padding: 1px !important;
                height: auto !important;
            }

            .select2-container--default .select2-selection--single .select2-selection__arrow {
                display: none !important;
            }

            .select2-container--default .select2-selection--single .select2-selection__rendered {
                padding-left: 4px;
                padding-right: 4px;
                line-height: 1.5;
            }

            .product-item {
                padding: 4px !important;
                /* 👈 ini yang kamu mau */
                border-radius: 8px;
                margin-bottom: 8px;
            }

            .product-item-header {
                margin-bottom: 6px;
            }

            .product-number {
                font-size: 13px;
            }

            .delete-row {
                padding: 6px 6px;
            }

            /* GRID MOBILE: custom layout */
            .product-grid {
                display: grid;
                grid-template-columns: 1fr 1fr 1fr;
                /* ✅ 3 kolom */
                gap: 6px;
            }

            /* Product full width */
            .product-col-span-2 {
                grid-column: span 3;
                /* ✅ full */
            }

            /* Qty & Price satu baris */
            .product-grid .form-group:nth-of-type(2),
            .product-grid .form-group:nth-of-type(3),
            .product-grid .form-group:nth-of-type(4) {
                grid-column: span 1;
                /* ✅ sejajar */
            }

            /* Label diperkecil */
            .product-grid label {
                font-size: 12px;
                margin-bottom: 2px;
            }

            /* Input lebih compact */
            .product-grid .form-control {
                font-size: 14px !important;
                height: 36px !important;
                padding: 4px 8px !important;
            }

            /* Select2 compact */
            .select2-container--default .select2-selection--single {
                height: 36px !important;
                line-height: 36px !important;
            }

            .select2-selection__rendered {
                font-size: 14px !important;
                line-height: 36px !important;
            }

            .select2-selection__arrow {
                height: 36px !important;
            }

            /* Total readonly lebih jelas */
            .total_before_discount_display {
                font-weight: 600;
                color: #198754;
            }

            .product-grid .form-group:nth-of-type(2) label,
            .product-grid .form-group:nth-of-type(3) label,
            .product-grid .form-group:nth-of-type(4) label {
                display: none !important;
            }
        }
    </style>
@endpush

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Sale Orders</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Sale Orders</li>
                <li class="breadcrumb-item">Create Sale Orders</li>
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
                    <a href="/erp/sales/sale-orders" class="btn btn-light-brand">
                        <i class="feather-arrow-left me-2"></i>
                        <span>Back</span>
                    </a>
                    <button type="submit" class="btn btn-primary" form="orderForm">
                        <i class="feather-plus me-2"></i>
                        <span>Add Sale Order</span>
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
                <form action="/erp/sales/sale-orders/store" method="POST" id="orderForm">
                    @csrf
                    @method('POST')
                    <div class="card stretch stretch-full">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="row mb-3 align-items-center">
                                        <div class="col-lg-2">
                                            <label for="order_date" class="fw-semibold">Order Date:</label>
                                        </div>
                                        <div class="col-lg-10 mb-0">
                                            <div class="input-group">
                                                <input type="datetime-local" class="form-control" id="order_date"
                                                    name="order_date" value="{{ date('Y-m-d\TH:i') }}">
                                            </div>
                                        </div>
                                    </div>
                                    <!--  -->
                                    <div class="row mb-3 align-items-center">
                                        <div class="col-lg-2">
                                            <label for="customers" class="fw-semibold">Customer:</label>
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
                                                    data-select2-selector="tag" id="customers" name="customer_id">
                                                    <option disabled selected hidden>Choose Customer</option>
                                                    @foreach ($customers as $index => $customer)
                                                        @php
                                                            $bg = $bgColors[$loop->index % count($bgColors)];
                                                        @endphp
                                                        <option value="{{ $customer->id }}" data-bg="{{ $bg }}">
                                                            {{ $customer->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mb-3 align-items-center">
                                        <div class="col-lg-2">
                                            <label for="addresses" class="fw-semibold">Address:</label>
                                        </div>
                                        <div class="col-lg-10 mb-0">
                                            <div class="input-group">
                                                <select class="form-select form-control max-select"
                                                    data-select2-selector="tag" id="addresses" name="customer_address_id">
                                                    <option disabled selected hidden>Pilih alamat</option>
                                                </select>
                                            </div>
                                            <div id="google-maps-link" class="mt-2"></div>
                                        </div>
                                    </div>
                                    {{-- <div class="row mb-3 align-items-center">
                                        <div class="col-lg-2">
                                            <label for="mode" class="fw-semibold">Mode:</label>
                                        </div>
                                        <div class="col-lg-10 mb-0">
                                            <div class="input-group">
                                                <select class="form-select" id="mode" name="mode"
                                                    data-select2-selector="tag" required>
                                                    <option disabled selected hidden>Pilih mode</option>
                                                    <option value="printing">Printing</option>
                                                    <option value="polosan">Polosan</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div> --}}
                                    <div class="row mb-3 align-items-center">
                                        <div class="col-lg-2">
                                            <label for="notes" class="fw-semibold">Note:</label>
                                        </div>
                                        <div class="col-lg-10 mb-0">
                                            <div class="input-group">
                                                <textarea name="notes" id="notes" class="form-control" rows="2" placeholder="Tambahkan catatan (opsional)"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    {{-- <div class="row mb-3 align-items-center">
                                        <div class="col-lg-2">
                                            <label class="fw-semibold">Diskon:</label>
                                        </div>
                                        <div class="col-lg-10 mb-0">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="toggleDiscount"
                                                    name="discount_active" checked>
                                                <label class="form-check-label" for="toggleDiscount">Aktifkan
                                                    Diskon</label>
                                            </div>
                                            <input type="hidden" id="discount_active_hidden"
                                                name="discount_active_hidden" value="1">
                                        </div>
                                    </div> --}}
                                    <div class="row mb-3 align-items-center">
                                        <div class="col-lg-2">
                                            <label class="fw-semibold">Diskon:</label>
                                        </div>
                                        <div class="col-lg-10 mb-0">
                                            <div class="row align-items-center">
                                                <!-- 🔹 Kolom Diskon -->
                                                <div class="col-md-6">
                                                    <div class="d-flex flex-column">
                                                        <label class="fw-semibold mb-1">Diskon:</label>
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input" type="checkbox"
                                                                id="toggleDiscount" name="discount_active" checked>
                                                            <label class="form-check-label fw-semibold"
                                                                for="toggleDiscount">
                                                                Aktifkan Diskon
                                                            </label>
                                                        </div>
                                                        <input type="hidden" id="discount_active_hidden"
                                                            name="discount_active_hidden" value="1">
                                                    </div>
                                                </div>

                                                <!-- 🔹 Kolom Mode -->
                                                {{-- <div class="col-md-6 mb-3 mb-md-0">
                                                    <div class="d-flex flex-column">
                                                        <label class="fw-semibold mb-1">Mode:</label>
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input" type="checkbox"
                                                                id="toggleMode" name="mode_toggle" checked>
                                                            <label class="form-check-label fw-semibold" for="toggleMode"
                                                                id="modeLabel">
                                                                Printing
                                                            </label>
                                                        </div>
                                                        <input type="hidden" id="mode" name="mode"
                                                            value="printing">
                                                    </div>
                                                </div> --}}
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card stretch stretch-full">
                        <div class="card-body">

                            <div class="mb-4">
                                <h5 class="fw-bold">Add Products:</h5>
                            </div>

                            <!-- PRODUCT LIST -->
                            <div id="product_list">

                                <div class="product-item" data-index="0">
                                    <div class="product-item-header">
                                        <span class="product-number">#1</span>
                                        <button type="button" class="btn btn-danger delete-row">
                                            <i class="feather-trash"></i>
                                        </button>
                                    </div>

                                    <div class="product-grid">

                                        <div class="form-group product-col-span-2">
                                            <label>Product</label>
                                            <select class="form-control select-product" data-select2-selector="tag"
                                                name="product[]" id="product_0">
                                                <option value="" disabled selected hidden>Pilih produk</option>
                                            </select>
                                        </div>

                                        <input type="hidden" name="product_type[]" class="product-type"
                                            id="product_type_0">

                                        <div class="form-group product-unit-wrapper">
                                            <label>Unit</label>
                                            <select name="product_unit_id[]" class="form-control product-unit">
                                                <option value="">Pilih unit</option>
                                            </select>

                                            <input type="hidden" name="unit_conversion_value[]"
                                                class="unit-conversion-value" value="1">
                                            <input type="hidden" name="unit_name[]" class="unit-name" value="">
                                        </div>

                                        <div class="form-group">
                                            <label>Qty</label>
                                            <input type="text" inputmode="numeric" name="qty[]"
                                                class="form-control qty">
                                        </div>

                                        <div class="form-group">
                                            <label>Price</label>
                                            <input type="text" inputmode="numeric"
                                                class="form-control price_before_discount_display">
                                            <input type="hidden" name="price_before_discount[]"
                                                class="price_before_discount">
                                        </div>

                                        <div class="form-group">
                                            <label>Mode</label>
                                            <select name="mode[]" class="form-control item-mode">
                                                <option value="printing" selected>Printing</option>
                                                <option value="polosan">Polosan</option>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label>Total</label>
                                            <input type="text" class="form-control total_before_discount_display"
                                                readonly>
                                            <input type="hidden" name="total_before_discount[]"
                                                class="total_before_discount">
                                        </div>

                                        <input type="hidden" name="price_after_discount[]" class="price_after_discount">
                                        <input type="hidden" name="total_after_discount[]" class="total_after_discount">

                                    </div>
                                </div>

                            </div>

                            <template id="product_item_template">
                                <div class="product-item" data-index="__index__">
                                    <div class="product-item-header">
                                        <span class="product-number">#__number__</span>
                                        <button type="button" class="btn btn-sm btn-danger delete-row">
                                            <i class="feather-trash"></i>
                                        </button>
                                    </div>

                                    <div class="product-grid">

                                        <div class="form-group product-col-span-2">
                                            <label>Product</label>
                                            <select class="form-control select-product" data-select2-selector="tag"
                                                name="product[]" id="product___index__">
                                                <option value="" disabled selected hidden>Pilih produk</option>
                                            </select>
                                        </div>

                                        <input type="hidden" name="product_type[]" class="product-type"
                                            id="product_type___index__">

                                        <div class="form-group product-unit-wrapper">
                                            <label>Unit</label>
                                            <select name="product_unit_id[]" class="form-control product-unit">
                                                <option value="">Pilih unit</option>
                                            </select>

                                            <input type="hidden" name="unit_conversion_value[]"
                                                class="unit-conversion-value" value="1">
                                            <input type="hidden" name="unit_name[]" class="unit-name" value="">
                                        </div>

                                        <div class="form-group">
                                            <label>Qty</label>
                                            <input type="text" inputmode="numeric" name="qty[]"
                                                class="form-control qty">
                                        </div>

                                        <div class="form-group">
                                            <label>Price</label>
                                            <input type="text" inputmode="numeric"
                                                class="form-control price_before_discount_display">
                                            <input type="hidden" name="price_before_discount[]"
                                                class="price_before_discount">
                                        </div>

                                        <div class="form-group">
                                            <label>Mode</label>
                                            <select name="mode[]" class="form-control item-mode">
                                                <option value="printing" selected>Printing</option>
                                                <option value="polosan">Polosan</option>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label>Total</label>
                                            <input type="text" class="form-control total_before_discount_display"
                                                readonly>
                                            <input type="hidden" name="total_before_discount[]"
                                                class="total_before_discount">
                                        </div>

                                        <input type="hidden" name="price_after_discount[]" class="price_after_discount">
                                        <input type="hidden" name="total_after_discount[]" class="total_after_discount">

                                    </div>
                                </div>
                            </template>

                            <!-- ADD BUTTON -->
                            <div class="d-flex justify-content-end mt-3">
                                <button type="button" id="add_row" class="btn btn-primary">
                                    Add Item
                                </button>
                            </div>

                            <!-- GRAND TOTAL -->
                            <div class="row justify-content-end mt-4">
                                <div class="col-lg-4">
                                    <h5 class="fw-bold mb-3">Grand Total:</h5>

                                    <table class="table table-bordered" id="tab_logic_total">
                                        <tbody>
                                            <tr>
                                                <th>Sub Total</th>
                                                <td>
                                                    <input type="text" id="sub_total_display" class="form-control"
                                                        readonly>
                                                    <input type="hidden" name="sub_total" id="sub_total">
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Total Discount</th>
                                                <td>
                                                    <input type="text" id="total_discount_display"
                                                        class="form-control text-danger" readonly>
                                                    <input type="hidden" name="total_discount" id="total_discount">
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="bg-gray-100">Grand Total</th>
                                                <td>
                                                    <input type="text" id="total_amount_display"
                                                        class="form-control fw-bold text-success bg-gray-100" readonly>
                                                    <input type="hidden" name="total_amount" id="total_amount">
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('modals')
    <!-- Modal Konfirmasi Matikan Diskon -->
    <div class="modal fade" id="confirmDisableDiscountModal" tabindex="-1"
        aria-labelledby="confirmDisableDiscountLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title fw-semibold text-white" id="confirmDisableDiscountLabel">
                        Nonaktifkan Diskon
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0 text-dark">
                        Apakah kamu yakin ingin menonaktifkan semua diskon?
                        Semua harga akan dihitung ulang tanpa potongan harga.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-warning text-dark" id="confirmDisableDiscountBtn">Matikan
                        Diskon</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Kedua: Konfirmasi Tanggung Jawab -->
    <div class="modal fade" id="confirmResponsibilityModal" tabindex="-1" aria-labelledby="confirmResponsibilityLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-semibold text-white" id="confirmResponsibilityLabel">
                        Konfirmasi Tanggung Jawab
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0 text-dark">
                        Apakah Anda bersedia untuk <strong>bertanggung jawab</strong> atas keputusan menonaktifkan semua
                        diskon ini?
                        Perubahan ini dapat mempengaruhi total harga penjualan.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger text-white" id="confirmResponsibilityBtn">
                        Ya, Saya Bertanggung Jawab
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi Ganti Mode -->
    <div class="modal fade" id="confirmChangeModeModal" tabindex="-1" aria-labelledby="confirmChangeModeLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title fw-semibold text-white" id="confirmChangeModeLabel">
                        Ganti Mode Produksi
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0 text-dark">
                        Apakah kamu yakin ingin mengganti mode produksi menjadi
                        <strong id="nextModeText">Polosan</strong>?<br>
                        Perubahan ini akan mempengaruhi alur produksi dan stok barang.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-info text-white" id="confirmChangeModeBtn">
                        Ya, Ganti Mode
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi Tanggung Jawab Ganti Mode -->
    <div class="modal fade" id="confirmModeResponsibilityModal" tabindex="-1"
        aria-labelledby="confirmModeResponsibilityLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-semibold text-white" id="confirmModeResponsibilityLabel">
                        Konfirmasi Tanggung Jawab
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0 text-dark">
                        Apakah kamu <strong>bertanggung jawab penuh</strong> atas keputusan mengganti mode ini?<br>
                        Perubahan mode dapat mempengaruhi sistem produksi dan penghitungan stok.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger text-white" id="confirmModeResponsibilityBtn">
                        Ya, Saya Bertanggung Jawab
                    </button>
                </div>
            </div>
        </div>
    </div>
@endpush

@push('scripts')
    <script>
        const isOwner = {{ Auth::user()->role === 'Owner' ? 'true' : 'false' }};

        let discountEnabled = true;

        const customerAddresses = <?php echo json_encode(
            $customers->mapWithKeys(function ($customer) {
                return [
                    $customer->id => $customer->addresses->map(function ($address) {
                        return [
                            'id' => $address->id,
                            'address' => $address->address,
                            'google_maps' => $address->google_maps,
                            'business_name' => $address->business_name,
                        ];
                    }),
                ];
            }),
        ); ?>;

        const products = @json($productsJson);
        const bundles = @json($productBundlesJson);

        const allProducts = [
            ...products.map(p => ({
                ...p,
                type: 'satuan'
            })),
            ...bundles.map(b => ({
                ...b,
                type: 'bundle'
            })),
        ];

        function formatNumber(num) {
            return new Intl.NumberFormat('id-ID', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(num);
        }

        function populateProducts(selectEl) {
            $(selectEl).empty().append('<option value="" disabled selected hidden>Pilih produk</option>');

            allProducts.forEach(item => {
                $('<option>', {
                        value: item.type + '_' + item.id,
                        text: `[${item.sku || '-'}] ${item.name}` + (item.type === 'bundle' ? ' (Bundle)' : ''),
                    })
                    .data('price', item.price)
                    .data('discounts', item.discounts || [])
                    .data('categories', item.categories || [])
                    .data('type', item.type)
                    .data('sku', item.sku || '')
                    .data('units', item.units || [])
                    .appendTo(selectEl);
            });
        }

        // ✅ Format otomatis titik ribuan + update hidden input untuk perhitungan
        let priceInputTimeout;
        $(document).on('input', '.price_before_discount_display', function() {
            // if (!isOwner) return; // cuma Owner bisa edit harga

            // const row = $(this).closest('tr');
            const row = $(this).closest('.product-item');

            let rawValue = $(this).val().replace(/\D/g, '');
            if (rawValue.length > 12) rawValue = rawValue.substring(0, 12);

            const formatted = new Intl.NumberFormat('id-ID').format(rawValue);
            $(this).val(formatted);

            clearTimeout(priceInputTimeout);
            priceInputTimeout = setTimeout(() => {
                const parsed = parseFloat(rawValue) || 0;
                row.find('input.price_before_discount').val(parsed.toFixed(2));
                recalcAllRows();
            }, 200);
        });

        $(document).on('blur', '.price_before_discount_display', function() {
            let val = $(this).val().replace(/\D/g, '');
            $(this).val(new Intl.NumberFormat('id-ID').format(val));
        });

        $(document).ready(function() {
            let pendingToggleOff = false;

            $('#toggleDiscount').on('change', function() {
                const isChecked = this.checked;
                const label = $(this).next('label');

                if (!isChecked) {
                    pendingToggleOff = true;
                    $(this).prop('checked', true);
                    $('#confirmDisableDiscountModal').modal('show');
                } else {
                    discountEnabled = true;
                    $('#discount_active_hidden').val(1);
                    label.text('Diskon Aktif')
                        .removeClass('text-danger')
                        .addClass('text-success');
                    recalcAllRows();
                }
            });

            /* ⬇️ PENTING: OFF dulu event lama */
            $('#confirmDisableDiscountBtn')
                .off('click')
                .on('click', function() {
                    $('#confirmDisableDiscountModal').modal('hide');

                    $('#confirmDisableDiscountModal').one('hidden.bs.modal', function() {
                        $('#confirmResponsibilityModal').modal('show');
                    });
                });

            $('#confirmResponsibilityBtn')
                .off('click')
                .on('click', function() {
                    $('#confirmResponsibilityModal').modal('hide');

                    discountEnabled = false;
                    $('#toggleDiscount').prop('checked', false);
                    $('#discount_active_hidden').val(0);

                    const label = $('#toggleDiscount').next('label');
                    label.text('Diskon Nonaktif')
                        .removeClass('text-success')
                        .addClass('text-danger');

                    pendingToggleOff = false;
                    recalcAllRows();
                });
        })

        function calculateRow(row) {
            const selectedOption = row.find('select[name="product[]"] option:selected');

            // 🔹 Ambil harga dari hidden input (hasil edit user)
            let basePrice = row.find('input.price_before_discount').val();
            basePrice = basePrice === '' ? NaN : parseFloat(basePrice);

            // 🔹 Kalau belum ada harga (NaN), baru pakai harga product
            if (isNaN(basePrice)) {
                basePrice = parseFloat(selectedOption.data('price')) || 0;

                // sync ke hidden + display
                row.find('input.price_before_discount').val(basePrice.toFixed(2));
                if (!row.find('.price_before_discount_display').is(':focus')) {
                    row.find('input.price_before_discount_display').val(formatNumber(basePrice));
                }
            }

            const discounts = selectedOption.data('discounts') || [];
            const categories = selectedOption.data('categories') || [];
            const qty = parseFloat(row.find('input[name="qty[]"]').val().replace(/\./g, '')) || 0;

            const priceBeforeDiscount = basePrice;
            const totalBeforeDiscount = basePrice * qty;

            let finalPrice = priceBeforeDiscount;
            let allDiscounts = discountEnabled ? [...discounts] : [];

            // 🔥 Diskon cuma jalan kalau discountEnabled = true
            if (discountEnabled && priceBeforeDiscount > 0) {
                categories.forEach(cat => {
                    if (cat.discounts) {
                        allDiscounts = allDiscounts.concat(cat.discounts);
                    }
                });

                allDiscounts.forEach(discount => {
                    let eligible = false;

                    if (discount.apply_on === 'Product') {
                        if (discount.minimum_based_on === 'Quantity of Items' && qty >= discount
                            .minimum_qty_or_amount) {
                            eligible = true;
                        } else if (discount.minimum_based_on === 'Purchase Amount' && totalBeforeDiscount >=
                            discount.minimum_qty_or_amount) {
                            eligible = true;
                        }
                    } else if (discount.apply_on === 'Category') {
                        let totalQtyCategory = 0,
                            totalAmountCategory = 0;

                        $('select[name="product[]"]').each(function(i, el) {
                            const opt = $(el).find('option:selected');
                            const cats = opt.data('categories') || [];
                            const price = parseFloat(opt.data('price')) || 0;
                            const qtyVal = parseFloat($('input[name="qty[]"]').eq(i).val().replace(/\./g,
                                '')) || 0;

                            if (cats.some(c => c.id === discount.category_id)) {
                                totalQtyCategory += qtyVal;
                                totalAmountCategory += price * qtyVal;
                            }
                        });

                        if (discount.minimum_based_on === 'Quantity of Items' && totalQtyCategory >= discount
                            .minimum_qty_or_amount) {
                            eligible = true;
                        } else if (discount.minimum_based_on === 'Purchase Amount' && totalAmountCategory >=
                            discount.minimum_qty_or_amount) {
                            eligible = true;
                        }
                    }

                    if (eligible) {
                        if (discount.type === 'Percentage') {
                            finalPrice = priceBeforeDiscount - (priceBeforeDiscount * (discount.amount / 100));
                        } else {
                            finalPrice = Math.max(0, priceBeforeDiscount - discount.amount);
                        }
                    }
                });
            }

            const totalAfterDiscount = finalPrice * qty;

            // 🔹 simpan ke hidden
            row.find('input.price_before_discount').val(priceBeforeDiscount.toFixed(2));
            row.find('input.total_before_discount').val(totalBeforeDiscount.toFixed(2));
            row.find('input.price_after_discount').val(finalPrice.toFixed(2));
            row.find('input.total_after_discount').val(totalAfterDiscount.toFixed(2));

            // 🔹 update display (jangan ganggu kalau lagi fokus input price)
            if (!row.find('.price_before_discount_display').is(':focus')) {
                row.find('input.price_before_discount_display').val(formatNumber(basePrice));
            }
            row.find('input.total_before_discount_display').val(formatNumber(totalBeforeDiscount));
        }

        function recalcAllRows() {
            $('.product-item').each(function() {
                calculateRow($(this));
            });
            calcTotalSummary();
        }

        function calcTotalSummary() {
            let subTotal = 0,
                totalAfterDiscount = 0;

            $(".total_before_discount").each(function() {
                subTotal += parseFloat($(this).val()) || 0;
            });
            $(".total_after_discount").each(function() {
                totalAfterDiscount += parseFloat($(this).val()) || 0;
            });

            $("#sub_total").val(subTotal.toFixed(0));
            $("#total_discount").val((subTotal - totalAfterDiscount).toFixed(0));
            $("#total_amount").val(totalAfterDiscount.toFixed(0));

            $("#sub_total_display").val(formatNumber(subTotal));
            $("#total_discount_display").val(formatNumber(subTotal - totalAfterDiscount));
            $("#total_amount_display").val(formatNumber(totalAfterDiscount));
        }

        $(document).on('change', 'select[name="product[]"]', function() {
            const row = $(this).closest('.product-item');
            const selectedOption = $(this).find('option:selected');

            const type = selectedOption.data('type') || '';
            let units = selectedOption.data('units') || [];
            const price = parseFloat(selectedOption.data('price') || 0);

            const selectedValue = selectedOption.val();
            const selectedId = String(selectedValue).replace(type + '_', '');

            const selectedItem = allProducts.find(item =>
                item.type === type && String(item.id) === selectedId
            );

            // if (selectedItem?.base_unit_id) {
            //     units = units.filter(unit =>
            //         String(unit.unit_id) === String(selectedItem.base_unit_id)
            //     );
            // }

            row.find('.product-type').val(type);

            fillProductUnits(row, units, price, false, selectedItem?.base_unit_id);

            recalcAllRows();
        });

        // $(document).on('input', 'input[name="qty[]"]', recalcAllRows);
        $(document).on('input', 'input[name="qty[]"]', function() {
            recalcAllRows();
        });

        // function initSelect2(el) {
        //     $(el).select2({
        //         placeholder: 'Pilih produk',
        //         width: '100%',
        //         matcher: (params, data) => {
        //             if ($.trim(params.term) === '') return data;
        //             return data.text.toLowerCase().includes(params.term.toLowerCase()) ? data : null;
        //         }
        //     });
        //     if ($(el).children('option').length === 1) populateProducts(el);
        // }

        function isMobile() {
            return window.matchMedia('(max-width: 768px)').matches;
        }

        function initSelect2(el) {
            $(el).select2({
                placeholder: 'Pilih produk',
                width: '100%',

                /* 🔍 SEARCH: pakai nama + SKU */
                matcher: function(params, data) {
                    if ($.trim(params.term) === '') return data;
                    if (!data.element) return null;

                    const term = params.term.toLowerCase();
                    const text = data.text.toLowerCase();
                    const sku = $(data.element).data('sku')?.toLowerCase() || '';

                    if (text.includes(term) || sku.includes(term)) {
                        return data;
                    }

                    return null;
                },

                /* 📱 Tampilan list dropdown */
                templateResult: function(data) {
                    if (!data.element) return data.text;

                    const name = data.text.replace(/^\[.*?\]\s*/, '');
                    const sku = $(data.element).data('sku');

                    if (isMobile()) {
                        return name; // MOBILE → tanpa SKU
                    }

                    return data.text; // DESKTOP → pakai SKU
                },

                /* 📱 Tampilan selected value */
                templateSelection: function(data) {
                    if (!data.element) return data.text;

                    const name = data.text.replace(/^\[.*?\]\s*/, '');

                    if (isMobile()) {
                        return name;
                    }

                    return data.text;
                }
            });

            if ($(el).children('option').length === 1) populateProducts(el);
        }


        document.addEventListener('DOMContentLoaded', function() {
            let rowCount = 1;

            document.querySelectorAll('select.select-product').forEach(el => initSelect2(el));

            document.getElementById('add_row').addEventListener('click', function() {

                const list = document.getElementById('product_list');
                const index = list.querySelectorAll('.product-item').length;

                const html = document
                    .getElementById('product_item_template')
                    .innerHTML
                    .replace(/__index__/g, index)
                    .replace(/__number__/g, index + 1);

                const wrapper = document.createElement('div');
                wrapper.innerHTML = html;

                const item = wrapper.firstElementChild;
                list.appendChild(item);

                // init select2 SETELAH masuk DOM
                initSelect2(item.querySelector('.select-product'));
            });

            document.addEventListener('click', function(e) {
                if (e.target.closest('.delete-row')) {
                    e.target.closest('.product-item').remove();

                    document.querySelectorAll('.product-item').forEach((el, i) => {
                        el.dataset.index = i;
                        el.querySelector('.product-number').textContent = '#' + (i + 1);
                    });

                    recalcAllRows();
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
            "#customers, #addresses, select[name='product[]'], select[name='mode[]'], input[name='qty[]'], input[name='order_date']",
            function() {
                if ($(this).hasClass("select2-hidden-accessible")) {
                    $(this).next('.select2').next('.invalid-feedback').remove();
                } else {
                    this.classList.remove("is-invalid");
                    $(this).siblings(".invalid-feedback").remove();
                }
            });

        // document.getElementById('orderForm').addEventListener('submit', function(e) {
        //     e.preventDefault();

        //     let isValid = true;

        //     this.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        //     this.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

        //     const orderDate = this.querySelector('input[name="order_date"]');
        //     if (!orderDate.value.trim()) {
        //         isValid = false;
        //         showError(orderDate, "Tanggal order wajib diisi");
        //     }

        //     const customerSelect = $('#customers');
        //     if (!customerSelect.val() || customerSelect.val().length === 0) {
        //         isValid = false;
        //         showError(customerSelect[0], "Customer wajib dipilih");
        //     }

        //     const addressSelect = $('#addresses');
        //     if (!addressSelect.val() || addressSelect.val().length === 0) {
        //         isValid = false;
        //         showError(addressSelect[0], "Alamat wajib dipilih");
        //     }

        //     // const modeSelect = $('#mode');
        //     // if (!modeSelect.val() || modeSelect.val().length === 0) {
        //     //     isValid = false;
        //     //     showError(modeSelect[0], "Mode wajib dipilih");
        //     // }

        //     // const rows = this.querySelectorAll('#tab_logic tbody tr');
        //     // rows.forEach(row => {
        //     //     const product = row.querySelector('select[name="product[]"]');
        //     //     const qty = row.querySelector('input[name="qty[]"]');
        //     //     if (!product.value) {
        //     //         isValid = false;
        //     //         showError(product, "Produk wajib dipilih");
        //     //     }
        //     //     if (!qty.value || parseInt(qty.value.replace(/[.,]/g, '')) < 1) {
        //     //         isValid = false;
        //     //         showError(qty, "Qty minimal 1");
        //     //     }
        //     // });

        //     $('.product-item').each(function() {
        //         const product = $(this).find('select[name="product[]"]');
        //         const qty = $(this).find('input[name="qty[]"]');
        //         const mode = $(this).find('select[name="mode[]"]');

        //         const cleanQty = qty.val().replace(/[^\d]/g, '');

        //         if (!product.val()) {
        //             isValid = false;
        //             showError(product[0], 'Produk wajib dipilih');
        //         }

        //         if (!mode.val()) {
        //             isValid = false;
        //             showError(mode[0], 'Mode wajib dipilih');
        //         }

        //         if (!cleanQty || parseInt(cleanQty) < 1) {
        //             isValid = false;
        //             showError(qty[0], 'Qty minimal 1');
        //         }
        //     });

        //     $('input[name="qty[]"], #sub_total, #total_discount, #total_amount').each(function() {
        //         $(this).val($(this).val().replace(/[.,]/g, ''));
        //     });

        //     if (isValid) this.submit();
        // });

        $(document).ready(function() {
            $('#customers').on('change', function() {
                const customerId = $(this).val();
                const addresses = customerAddresses[customerId] || [];
                $('#addresses').empty().append('<option disabled selected hidden>Pilih alamat</option>');
                addresses.forEach((address, i) => {
                    $('#addresses').append(
                        `<option value="${address.id}" data-map="${address.google_maps}">
                            ${address.business_name ?? 'None'} - ${address.address}
                        </option>`
                    );
                });
            });
            $('#addresses').on('change', function() {
                const mapUrl = $(this).find('option:selected').data('map');
                if (mapUrl) {
                    $('#google-maps-link').html(
                        `<a href="${mapUrl}" target="_blank" class="btn btn-sm btn-outline-primary mt-2">Lihat di Google Maps</a>`
                    );
                } else {
                    $('#google-maps-link').empty();
                }
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const optionEl = document.getElementById('due_date_option');
            const dateInput = document.getElementById('custom_due_date');

            function updateDueDate() {
                const val = optionEl.value;
                let newDate = null;

                if (val === 'today') {
                    newDate = new Date();
                    dateInput.readOnly = true;
                } else if (val === '1_week') {
                    newDate = new Date();
                    newDate.setDate(newDate.getDate() + 7);
                    dateInput.readOnly = true;
                } else if (val === '1_month') {
                    newDate = new Date();
                    newDate.setMonth(newDate.getMonth() + 1);
                    dateInput.readOnly = true;
                } else if (val === '3_months') {
                    newDate = new Date();
                    newDate.setMonth(newDate.getMonth() + 3);
                    dateInput.readOnly = true;
                } else if (val === 'custom') {
                    newDate = null;
                    dateInput.readOnly = false;
                    dateInput.value = "";
                } else {
                    newDate = null;
                    dateInput.readOnly = true;
                    dateInput.value = "";
                }

                if (newDate) {
                    const yyyy = newDate.getFullYear();
                    const mm = String(newDate.getMonth() + 1).padStart(2, '0');
                    const dd = String(newDate.getDate()).padStart(2, '0');
                    dateInput.value = `${yyyy}-${mm}-${dd}`;
                }
            }

            optionEl.addEventListener('change', updateDueDate);

            // ✅ Set default value "1_week" & apply kalkulasinya
            optionEl.value = '1_week';
            updateDueDate();
        });

        $(document).on('select2:open', () => {
            setTimeout(() => {
                document.querySelector('.select2-container--open .select2-search__field')?.focus();
            }, 50);
        });

        $(document).on('input', 'input[name="qty[]"]', function() {
            let val = $(this).val().replace(/[^\d]/g, '');
            if (!val) {
                $(this).val('');
                return;
            }

            $(this).val(val.replace(/\B(?=(\d{3})+(?!\d))/g, '.'));
        });

        $('#orderForm').on('submit', function(e) {
            e.preventDefault();

            let isValid = true;
            const form = this;

            form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

            const orderDate = form.querySelector('input[name="order_date"]');
            if (!orderDate.value.trim()) {
                isValid = false;
                showError(orderDate, 'Tanggal order wajib diisi');
            }

            const customerSelect = $('#customers');
            if (!customerSelect.val()) {
                isValid = false;
                showError(customerSelect[0], 'Customer wajib dipilih');
            }

            const addressSelect = $('#addresses');
            if (!addressSelect.val()) {
                isValid = false;
                showError(addressSelect[0], 'Alamat wajib dipilih');
            }

            // $('.product-item').each(function() {
            //     const product = $(this).find('select[name="product[]"]');
            //     const qty = $(this).find('input[name="qty[]"]');
            //     const mode = $(this).find('select[name="mode[]"]');

            //     const cleanQty = qty.val().replace(/[^\d]/g, '');

            //     if (!product.val()) {
            //         isValid = false;
            //         showError(product[0], 'Produk wajib dipilih');
            //     }

            //     if (!mode.val()) {
            //         isValid = false;
            //         showError(mode[0], 'Mode wajib dipilih');
            //     }

            //     if (!cleanQty || parseInt(cleanQty) < 1) {
            //         isValid = false;
            //         showError(qty[0], 'Qty minimal 1');
            //     }
            // });

            $('.product-item').each(function() {
                const product = $(this).find('select[name="product[]"]');
                const unit = $(this).find('select[name="product_unit_id[]"]');
                const qty = $(this).find('input[name="qty[]"]');
                const mode = $(this).find('select[name="mode[]"]');

                const cleanQty = qty.val().replace(/[^\d]/g, '');

                if (!product.val()) {
                    isValid = false;
                    showError(product[0], 'Produk wajib dipilih');
                }

                if (!unit.val()) {
                    isValid = false;
                    showError(unit[0], 'Unit wajib dipilih');
                }

                if (!mode.val()) {
                    isValid = false;
                    showError(mode[0], 'Mode wajib dipilih');
                }

                if (!cleanQty || parseInt(cleanQty) < 1) {
                    isValid = false;
                    showError(qty[0], 'Qty minimal 1');
                }
            });

            if (!isValid) return;

            $('input[name="qty[]"], #sub_total, #total_discount, #total_amount').each(function() {
                $(this).val($(this).val().replace(/[.,]/g, ''));
            });

            $('input[name="unit_conversion_value[]"]').each(function() {
                $(this).val($(this).val().replace(',', '.'));
            });

            form.submit();
        });

        function formatRupiahNumber(number) {
            const value = parseFloat(number || 0);

            return value.toLocaleString('id-ID', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            });
        }

        function parseNumber(value) {
            if (!value) return 0;

            return parseFloat(
                value.toString()
                .replace(/\./g, '')
                .replace(',', '.')
            ) || 0;
        }

        function fillProductUnits(row, units, defaultPrice = 0, forceDefaultPcs = false, baseUnitId = null) {
            const unitSelect = row.find('.product-unit');

            unitSelect.empty();
            unitSelect.append('<option value="">Pilih unit</option>');

            row.find('.unit-conversion-value').val('1');
            row.find('.unit-name').val('Pcs');

            // Khusus bundle: Pcs default harus selalu ada
            if (forceDefaultPcs) {
                unitSelect.append(`
            <option value="default_pcs"
                data-unit-id=""
                data-unit-name="Pcs"
                data-conversion-value="1"
                data-sale-price="${defaultPrice}">
                Pcs
            </option>
        `);
            }

            if (Array.isArray(units) && units.length > 0) {
                units.forEach(function(unit) {
                    const unitName = unit.unit_name || 'Pcs';

                    // Kalau DB bundle juga punya Pcs, jangan dobel
                    if (forceDefaultPcs && unitName.toLowerCase() === 'pcs') {
                        return;
                    }

                    unitSelect.append(`
                <option value="${unit.id}"
                    data-unit-id="${unit.unit_id}"
                    data-unit-name="${unitName}"
                    data-conversion-value="${unit.conversion_value || 1}"
                    data-sale-price="${unit.sale_price || defaultPrice}">
                    ${unitName}
                </option>
            `);
                });
            }

            const baseOption = unitSelect.find(`option[data-unit-id="${baseUnitId}"]`).val();
            const firstUnitValue = unitSelect.find('option:eq(1)').val();

            if (forceDefaultPcs) {
                unitSelect.val(baseOption || 'default_pcs').trigger('change');
            } else if (baseOption || firstUnitValue) {
                unitSelect.val(baseOption || firstUnitValue).trigger('change');
            }
        }

        function updatePriceFromSelectedUnit(row) {
            const selectedUnit = row.find('.product-unit option:selected');

            if (!selectedUnit.val()) {
                return;
            }

            const unitName = selectedUnit.data('unit-name') || 'Pcs';
            const conversionValue = selectedUnit.data('conversion-value') || 1;
            const salePrice = parseFloat(selectedUnit.data('sale-price') || 0);

            row.find('.unit-name').val(unitName);
            row.find('.unit-conversion-value').val(conversionValue);

            row.find('.price_before_discount_display').val(formatNumber(salePrice));
            row.find('.price_before_discount').val(salePrice.toFixed(2));

            recalcAllRows();
        }

        // function calculateRowTotal($item) {
        //     const qty = parseNumber($item.find('.qty').val());
        //     const price = parseNumber($item.find('.price_before_discount').val());

        //     const total = qty * price;

        //     $item.find('.total_before_discount_display').val(formatRupiahNumber(total));
        //     $item.find('.total_before_discount').val(total);

        //     $item.find('.price_after_discount').val(price);
        //     $item.find('.total_after_discount').val(total);
        // }

        // function calculateGrandTotal() {
        //     let subTotal = 0;

        //     $('.total_before_discount').each(function() {
        //         subTotal += parseNumber($(this).val());
        //     });

        //     $('#sub_total').val(subTotal);
        //     $('#sub_total_display').val(formatRupiahNumber(subTotal));

        //     const totalDiscount = parseNumber($('#total_discount').val());

        //     const grandTotal = subTotal - totalDiscount;

        //     $('#total_amount').val(grandTotal);
        //     $('#total_amount_display').val(formatRupiahNumber(grandTotal));
        // }

        $(document).on('change', '.product-unit', function() {
            const $item = $(this).closest('.product-item');

            updatePriceFromSelectedUnit($item);
        });

        // $(document).on('input', '.qty', function() {
        //     const $item = $(this).closest('.product-item');

        //     calculateRowTotal($item);
        //     calculateGrandTotal();
        // });
    </script>
@endpush
