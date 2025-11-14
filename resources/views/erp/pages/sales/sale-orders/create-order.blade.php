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
        #tab_logic input.form-control.qty,
        #tab_logic input.form-control.price_before_discount_display,
        #tab_logic input.form-control.total_before_discount_display {
            font-size: 16px !important;
            font-weight: 600 !important;
            height: 44px !important;
            padding: 6px 10px !important;
        }

        /* 🔹 Untuk tampilan readonly total biar lebih kontras */
        #tab_logic input.total_before_discount_display[readonly] {
            background-color: #f8f9fa !important;
            color: #198754 !important;
        }

        /* 🔹 Perbesar juga font di header tabel produk (Qty, Price, Total) */
        /* #tab_logic th {
                        font-size: 15px !important;
                        font-weight: 700 !important;
                        vertical-align: middle !important;
                        text-transform: uppercase;
                    } */

        /* 🔹 Perbesar font Grand Total biar seragam */
        #tab_logic_total input.form-control {
            font-size: 16px !important;
            font-weight: 600 !important;
            height: 44px !important;
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
    <div class="main-content">
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
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="mb-4">
                                        <h5 class="fw-bold">Add Products:</h5>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-bordered overflow-hidden" id="tab_logic">
                                            <thead>
                                                <tr class="single-item">
                                                    <th class="text-center wd-20">#</th>
                                                    <th class="text-center wd-450">Product</th>
                                                    <!-- <th class="text-center wd-200">Product Type</th> -->
                                                    <th class="text-center wd-100">Qty</th>
                                                    <th class="text-center wd-100">Price</th>
                                                    <th class="text-center wd-100">Total</th>
                                                    <th class="text-center wd-50">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tab_logic_body">
                                                <tr id="addr0">
                                                    <td>1</td>

                                                    <td>
                                                        <select class="form-control select-product"
                                                            data-select2-selector="status" name="product[]"
                                                            id="product_0">
                                                            <option value="" disabled selected hidden>Pilih produk
                                                            </option>
                                                        </select>
                                                    </td>

                                                    <input type="hidden" name="product_type[]"
                                                        class="form-control product-type" id="product_type_0" readonly>

                                                    <td><input type="text" inputmode="numeric" name="qty[]"
                                                            class="form-control qty" id="qty_0" min="1"></td>

                                                    <!-- <td><input type="number" name="price_before_discount[]" class="form-control price_before_discount" id="price_before_discount_0" readonly></td>
                                                                                                                                                                                                        <td><input type="number" name="total_before_discount[]" class="form-control total_before_discount" id="total_before_discount_0" readonly></td> -->
                                                    <td>
                                                        @php
                                                            $isOwner = Auth::user()->role === 'Owner';
                                                        @endphp
                                                        <input type="text"
                                                            class="form-control price_before_discount_display">
                                                        <input type="hidden" name="price_before_discount[]"
                                                            class="price_before_discount">
                                                    </td>
                                                    <td>
                                                        <input type="text"
                                                            class="form-control total_before_discount_display" readonly>
                                                        <input type="hidden" name="total_before_discount[]"
                                                            class="total_before_discount">
                                                    </td>

                                                    <td class="text-center">
                                                        <div class="d-flex justify-content-center">
                                                            <button type="button" class="btn btn-danger delete-row">
                                                                <i class="feather-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>

                                                    <input type="hidden" name="price_after_discount[]"
                                                        class="price_after_discount">
                                                    <input type="hidden" name="total_after_discount[]"
                                                        class="total_after_discount">
                                                </tr>

                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="d-flex justify-content-end gap-2 mt-3">
                                        <!-- <button type="button" id="delete_row" class="btn btn-md bg-soft-danger text-danger">Delete</button> -->
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
                                                            <th class="fs-10 text-dark text-uppercase">Sub Total (Before
                                                                Discount)</th>
                                                            <td>
                                                                <input type="text" id="sub_total_display"
                                                                    class="form-control" readonly>
                                                                <input type="hidden" name="sub_total" id="sub_total">
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th class="fs-10 text-dark text-uppercase">Total Discount</th>
                                                            <td>
                                                                <input type="text" id="total_discount_display"
                                                                    class="form-control text-danger" readonly>
                                                                <input type="hidden" name="total_discount"
                                                                    id="total_discount">
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th class="fs-10 text-dark text-uppercase bg-gray-100">Grand
                                                                Total</th>
                                                            <td>
                                                                <input type="text" id="total_amount_display"
                                                                    class="form-control bg-gray-100 fw-700 text-success"
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

        let pendingToggleOff = false;

        $(document).on('change', '#toggleDiscount', function() {
            const isChecked = $(this).is(':checked');
            const label = $(this).next('label');

            if (!isChecked) {
                // simpan state kalau user mau OFF
                pendingToggleOff = true;
                // tampilkan modal konfirmasi
                $('#confirmDisableDiscountModal').modal('show');
                // kembalikan toggle ke posisi ON sementara
                $(this).prop('checked', true);
            } else {
                // aktifkan diskon
                discountEnabled = true;
                label.text('Diskon Aktif').removeClass('text-danger').addClass('text-success');
                recalcAllRows();
            }
        });

        // Ketika user konfirmasi matikan diskon dari modal pertama
        $('#confirmDisableDiscountBtn').on('click', function() {
            $('#confirmDisableDiscountModal').modal('hide');
            if (pendingToggleOff) {
                // Tampilkan modal kedua: tanggung jawab
                $('#confirmResponsibilityModal').modal('show');
            }
        });

        $('#confirmResponsibilityBtn').on('click', function() {
            $('#confirmResponsibilityModal').modal('hide');

            discountEnabled = false;
            $('#toggleDiscount').prop('checked', false);
            $('#discount_active_hidden').val(0); // ✅ ini akan tersimpan
            const label = $('#toggleDiscount').next('label');
            label.text('Diskon Nonaktif').removeClass('text-success').addClass('text-danger');
            recalcAllRows();
            pendingToggleOff = false;
        })

        function populateProducts(selectEl) {
            $(selectEl).empty().append('<option value="" disabled selected hidden>Pilih produk</option>');
            allProducts.forEach(item => {
                $('<option>', {
                        value: item.id,
                        text: `[${item.sku || '-'}] ${item.name}`,
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

            const row = $(this).closest('tr');
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
            $('tr[id^="addr"]').each(function() {
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
            const row = $(this).closest('tr');
            const type = $(this).find('option:selected').data('type') || '';
            row.find('.product-type').val(type);
            recalcAllRows();
        });

        $(document).on('input', 'input[name="qty[]"]', recalcAllRows);

        function initSelect2(el) {
            $(el).select2({
                placeholder: 'Pilih produk',
                width: '100%',
                matcher: (params, data) => {
                    if ($.trim(params.term) === '') return data;
                    return data.text.toLowerCase().includes(params.term.toLowerCase()) ? data : null;
                }
            });
            if ($(el).children('option').length === 1) populateProducts(el);
        }

        document.addEventListener('DOMContentLoaded', function() {
            let rowCount = 1;

            document.querySelectorAll('select.select-product').forEach(el => initSelect2(el));

            document.getElementById('add_row').addEventListener('click', function() {
                const tableBody = document.querySelector('#tab_logic_body');
                const rowCount = tableBody.querySelectorAll('tr').length;

                const newRow = document.createElement('tr');
                newRow.id = 'addr' + rowCount;

                newRow.innerHTML = `
                <td>${rowCount + 1}</td>
                <td>
                    <select class="form-control select-product" name="product[]" id="product_${rowCount}">
                        <option value="" disabled selected hidden>Pilih produk</option>
                    </select>
                </td>
                <input type="hidden" name="product_type[]" class="form-control product-type" readonly>
                <td><input type="text" inputmode="numeric" name="qty[]" class="form-control qty" min="1"></td>
                <td>
                    <input type="text" class="form-control price_before_discount_display">
                    <input type="hidden" name="price_before_discount[]" class="price_before_discount">
                </td>
                <td>
                    <input type="text"
                        class="form-control total_before_discount_display" readonly>
                    <input type="hidden" name="total_before_discount[]"
                        class="total_before_discount">
                </td>
                <td class="text-center">
                    <div class="d-flex justify-content-center">
                        <button type="button" class="btn btn-danger delete-row">
                            <i class="feather-trash"></i>
                        </button>
                    </div>
                </td>
                <input type="hidden" name="price_after_discount[]" class="form-control price_after_discount" readonly>
                <input type="hidden" name="total_after_discount[]" class="form-control total_after_discount" readonly>
            `;
                tableBody.appendChild(newRow);

                initSelect2(newRow.querySelector('.select-product'));
            });

            $(document).on('click', '.delete-row', function() {
                const row = $(this).closest('tr');
                row.remove();

                $('#tab_logic_body tr').each(function(i, el) {
                    $(el).find('td:first').text(i + 1);
                });

                recalcAllRows();
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

        document.addEventListener('DOMContentLoaded', function() {
            const toggle = document.getElementById('toggleMode');
            const label = document.getElementById('modeLabel');
            const hidden = document.getElementById('mode');
            const nextModeText = document.getElementById('nextModeText');
            const confirmChangeBtn = document.getElementById('confirmChangeModeBtn');
            const confirmResponsibilityBtn = document.getElementById('confirmModeResponsibilityBtn');

            // default
            label.textContent = 'Printing';
            hidden.value = 'printing';
            let pendingMode = null;

            toggle.addEventListener('change', function() {
                const nextMode = toggle.checked ? 'printing' : 'polosan';
                const currentMode = hidden.value;

                // Kalau beda, munculkan konfirmasi modal
                if (nextMode !== currentMode) {
                    pendingMode = nextMode;
                    nextModeText.textContent = nextMode === 'printing' ? 'Printing' : 'Polosan';

                    // balikin toggle sementara
                    toggle.checked = currentMode === 'printing';
                    $('#confirmChangeModeModal').modal('show');
                }
            });

            // Tombol konfirmasi pertama
            confirmChangeBtn.addEventListener('click', function() {
                $('#confirmChangeModeModal').modal('hide');
                $('#confirmModeResponsibilityModal').modal('show');
            });

            // Tombol tanggung jawab
            confirmResponsibilityBtn.addEventListener('click', function() {
                $('#confirmModeResponsibilityModal').modal('hide');

                // apply perubahan mode
                if (pendingMode) {
                    hidden.value = pendingMode;
                    label.textContent = pendingMode === 'printing' ? 'Printing' : 'Polosan';
                    toggle.checked = pendingMode === 'printing';
                    pendingMode = null;
                }
            });
        });
    </script>
@endpush
