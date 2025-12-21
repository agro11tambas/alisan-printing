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
            grid-template-columns: repeat(5, 1fr);
            gap: 10px;
        }

        .product-col-span-2 {
            grid-column: span 2;
        }

        /* MOBILE */
        /* @media (max-width: 768px) {
                                .product-grid {
                                    grid-template-columns: 1fr;
                                }

                                .product-col-span-2 {
                                    grid-column: span 1;
                                }
                            } */

        /* MOBILE */
        @media (max-width: 768px) {

            /* CARD super tipis */
            .card-body {
                padding: 6px !important;
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
                                            <label class="fw-semibold">Mode & Diskon:</label>
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
                                                <div class="col-md-6 mb-3 mb-md-0">
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
                                                </div>
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
                                        <button type="button" class="btn btn-sm btn-danger delete-row">
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

        function initSelect2(el) {
            $(el).select2({
                placeholder: 'Pilih produk',
                width: '100%',

                // dropdown FULL
                templateResult: function(data) {
                    return data.text;
                },

                // selected DIPOTONG
                templateSelection: function(data) {
                    // mobile only
                    if (window.innerWidth <= 576) {
                        return truncateText(data.text, 45);
                    }

                    // desktop / tablet: FULL TEXT
                    return data.text;
                },

                matcher: (params, data) => {
                    if ($.trim(params.term) === '') return data;
                    return data.text.toLowerCase().includes(params.term.toLowerCase()) ?
                        data :
                        null;
                }
            });

            if ($(el).children('option').length === 1) {
                populateProducts(el);
            }
        }

        function truncateText(text, max = 45) {
            if (!text) return '';
            return text.length > max ? text.slice(0, max) + '...' : text;
        }

        function populateProducts(selectEl) {
            $(selectEl).empty().append('<option value="" disabled selected hidden>Pilih produk</option>');
            allProducts.forEach(item => {
                const fullText = `[${item.sku || '-'}] ${item.name}`;
                $('<option>', {
                        value: item.id,
                        text: fullText,
                    })
                    .data('price', item.price)
                    .data('discounts', item.discounts || [])
                    .data('categories', item.categories || [])
                    .data('type', item.type)
                    .data('sku', item.sku || '')
                    .appendTo(selectEl);
            });
        }

        function formatNumber(num) {
            return new Intl.NumberFormat('id-ID', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(num);
        }

        let priceInputTimeout;
        $(document).on('input', '.price_before_discount_display', function() {
            // if (!isOwner) return;

            // const row = $(this).closest('tr');
            const row = $(this).closest('.product-item');

            let rawValue = $(this).val().replace(/\D/g, ''); // hanya angka
            if (rawValue.length > 12) rawValue = rawValue.substring(0, 12);

            // tampilkan format ribuan di input user
            const formatted = new Intl.NumberFormat('id-ID').format(rawValue);
            $(this).val(formatted);

            // simpan ke hidden input tanpa format
            clearTimeout(priceInputTimeout);
            priceInputTimeout = setTimeout(() => {
                const parsed = parseFloat(rawValue) || 0;
                row.find('input.price_before_discount').val(parsed.toFixed(2));
                recalcAllRows();
            }, 200); // sedikit delay supaya halus waktu ngetik cepat
        });

        $(document).on('blur', '.price_before_discount_display', function() {
            let val = $(this).val().replace(/\D/g, '');
            $(this).val(new Intl.NumberFormat('id-ID').format(val));
        });

        function calculateRow(row) {
            const selectedOption = row.find('select[name="product[]"] option:selected');
            let manualPrice = row.find('input.price_before_discount').val();
            manualPrice = manualPrice === "" ? null : parseFloat(manualPrice);

            // Jika user sudah input angka (termasuk 0), pakai input itu
            // Kalau belum pernah input harga → pakai harga product
            let basePrice = (manualPrice !== null && !isNaN(manualPrice)) ?
                manualPrice :
                (parseFloat(selectedOption.data('price')) || 0);

            const discounts = selectedOption.data('discounts') || [];
            const categories = selectedOption.data('categories') || [];
            const qty = parseFloat(row.find('input[name="qty[]"]').val().replace(/\./g, '')) || 0;

            const priceBeforeDiscount = basePrice;
            const totalBeforeDiscount = basePrice * qty;

            // let finalPrice = priceBeforeDiscount;
            // let allDiscounts = [...discounts];

            let finalPrice = priceBeforeDiscount;
            let allDiscounts = discountEnabled ? [...discounts] : [];

            // 🔥 Tambahan logika baru: hanya jalankan perhitungan diskon kalau discountEnabled = true
            if (discountEnabled) {
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

            row.find('input.price_before_discount').val(priceBeforeDiscount.toFixed(2));
            row.find('input.total_before_discount').val(totalBeforeDiscount.toFixed(2));
            row.find('input.price_after_discount').val(finalPrice.toFixed(2));
            row.find('input.total_after_discount').val(totalAfterDiscount.toFixed(2));

            // row.find('input.price_before_discount_display').val(formatNumber(priceBeforeDiscount));
            // row.find('input.total_before_discount_display').val(formatNumber(totalBeforeDiscount));

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

            const totalDiscount = subTotal - totalAfterDiscount;

            $("#sub_total").val(subTotal.toFixed(2));
            $("#total_discount").val((subTotal - totalAfterDiscount).toFixed(2));
            $("#total_amount").val(totalAfterDiscount.toFixed(2));

            $("#sub_total_display").val(formatNumber(subTotal));
            $("#total_discount_display").val(formatNumber(totalDiscount));
            $("#total_amount_display").val(formatNumber(totalAfterDiscount));
        }

        $(document).on('change', 'select[name="product[]"]', function() {
            const row = $(this).closest('.product-item');

            const type = $(this).find('option:selected').data('type') || '';
            row.find('.product-type').val(type);

            recalcAllRows();
        });

        // $(document).on('input', 'input[name="qty[]"]', recalcAllRows);

        $(document).on('input', 'input[name="qty[]"]', function() {
            recalcAllRows();
        });

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
            "#customers, #addresses, select[name='product[]'], input[name='qty[]'], input[name='order_date']",
            function() {
                if ($(this).hasClass("select2-hidden-accessible")) {
                    $(this).next('.select2').next('.invalid-feedback').remove();
                } else {
                    this.classList.remove("is-invalid");
                    $(this).siblings(".invalid-feedback").remove();
                }
            });

        document.getElementById('orderForm').addEventListener('submit', function(e) {
            let isValid = true;

            this.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            this.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

            const orderDate = this.querySelector('input[name="order_date"]');
            if (!orderDate.value.trim()) {
                isValid = false;
                showError(orderDate, "Tanggal order wajib diisi");
            }

            const customerSelect = $('#customers');
            if (!customerSelect.val() || customerSelect.val().length === 0) {
                isValid = false;
                showError(customerSelect[0], "Customer wajib dipilih");
            }

            const addressSelect = $('#addresses');
            if (!addressSelect.val() || addressSelect.val().length === 0) {
                isValid = false;
                showError(addressSelect[0], "Alamat wajib dipilih");
            }

            const modeSelect = $('#mode');
            if (!modeSelect.val() || modeSelect.val().length === 0) {
                isValid = false;
                showError(modeSelect[0], "Mode wajib dipilih");
            }

            const rows = this.querySelectorAll('#tab_logic tbody tr');
            rows.forEach(row => {
                const product = row.querySelector('select[name="product[]"]');
                const qty = row.querySelector('input[name="qty[]"]');
                if (!product.value) {
                    isValid = false;
                    showError(product, "Produk wajib dipilih");
                }
                if (!qty.value || parseInt(qty.value) < 1) {
                    isValid = false;
                    showError(qty, "Qty minimal 1");
                }
            });

            if (!isValid) e.preventDefault();
        });

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

        $(document).on('select2:open', () => {
            setTimeout(() => {
                document.querySelector('.select2-container--open .select2-search__field')?.focus();
            }, 50);
        });

        $(document).on('input', 'input[name="qty[]"]', function(e) {
            let rawValue = $(this).val().replace(/\D/g, '');
            if (rawValue.length > 12) rawValue = rawValue.substring(0, 12);
            let formatted = new Intl.NumberFormat('id-ID').format(rawValue);
            $(this).val(formatted);
        });

        $('#orderForm').on('submit', function() {
            $('input[name="qty[]"]').each(function() {
                const raw = $(this).val().replace(/\./g, '');
                $(this).val(raw);
            });
        });

        $(document).ready(function() {

            let pendingMode = null;

            // default state
            $('#modeLabel').text('Printing');
            $('#mode').val('printing');
            $('#toggleMode').prop('checked', true);

            $('#toggleMode').on('change', function() {
                const nextMode = this.checked ? 'printing' : 'polosan';
                const currentMode = $('#mode').val();

                if (nextMode !== currentMode) {
                    pendingMode = nextMode;

                    $('#nextModeText').text(
                        nextMode === 'printing' ? 'Printing' : 'Polosan'
                    );

                    // rollback toggle
                    $(this).prop('checked', currentMode === 'printing');

                    $('#confirmChangeModeModal').modal('show');
                }
            });

            $('#confirmChangeModeBtn')
                .off('click')
                .on('click', function() {
                    $('#confirmChangeModeModal').modal('hide');

                    $('#confirmChangeModeModal').one('hidden.bs.modal', function() {
                        $('#confirmModeResponsibilityModal').modal('show');
                    });
                });

            $('#confirmModeResponsibilityBtn')
                .off('click')
                .on('click', function() {
                    $('#confirmModeResponsibilityModal').modal('hide');

                    if (pendingMode) {
                        $('#mode').val(pendingMode);
                        $('#modeLabel').text(
                            pendingMode === 'printing' ? 'Printing' : 'Polosan'
                        );
                        $('#toggleMode').prop(
                            'checked',
                            pendingMode === 'printing'
                        );
                        pendingMode = null;
                    }
                });

        });
    </script>
@endpush
