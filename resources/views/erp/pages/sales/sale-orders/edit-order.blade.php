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

        /* Kolom Qty, Price, Total */
        #tab_logic input.form-control.qty,
        #tab_logic input.form-control.price_before_discount_display,
        #tab_logic input.form-control.total_before_discount_display {
            font-size: 18px !important;
            font-weight: 600 !important;
            height: 44px !important;
            padding: 6px 10px !important;
        }

        /* Total readonly: latar abu muda + teks hijau */
        #tab_logic input.total_before_discount_display[readonly] {
            background-color: #f8f9fa !important;
            color: #198754 !important;
        }

        /* Header tabel produk */
        #tab_logic th {
            font-size: 15px !important;
            font-weight: 700 !important;
            vertical-align: middle !important;
            text-transform: uppercase;
        }

        /* Grand Total: biar seragam dan jelas */
        #tab_logic_total input.form-control {
            font-size: 18px !important;
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
        @media (max-width: 768px) {
            .product-grid {
                grid-template-columns: 1fr;
            }

            .product-col-span-2 {
                grid-column: span 1;
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
                <li class="breadcrumb-item">Edit Sale Orders</li>
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
                        <span>Edit Sale Order</span>
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
                <form action="/erp/sales/sale-orders/update/{{ $order->id }}" method="POST" id="orderForm">
                    @csrf
                    @method('PUT')
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
                                                    name="order_date"
                                                    value="{{ old('order_date', isset($order->order_date) ? \Carbon\Carbon::parse($order->order_date)->format('Y-m-d\TH:i') : date('Y-m-d\TH:i')) }}">
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
                                                        <option value="{{ $customer->id }}" data-bg="{{ $bg }}"
                                                            {{ $customer->id == $order->customer_id ? 'selected' : '' }}>
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
                                                    <option disabled hidden>Pilih alamat</option>
                                                    @if ($order->customer)
                                                        @foreach ($order->customer->addresses as $index => $address)
                                                            <option value="{{ $address->id }}"
                                                                data-map="{{ $address->google_maps }}"
                                                                {{ $order->address_id == $address->id ? 'selected' : '' }}>
                                                                Alamat ke-{{ $index + 1 }} - {{ $address->address }}
                                                            </option>
                                                        @endforeach
                                                    @endif
                                                </select>
                                            </div>
                                            <div id="google-maps-link" class="mt-2">
                                                @if ($order->customerAddress && $order->customerAddress->google_maps)
                                                    <a href="{{ $order->customerAddress->google_maps }}" target="_blank"
                                                        class="btn btn-sm btn-outline-primary mt-2">
                                                        Lihat di Google Maps
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    {{-- <div class="row mb-3 align-items-center">
                                        <div class="col-lg-2">
                                            <label for="mode" class="fw-semibold">Mode:</label>
                                        </div>
                                        <div class="col-lg-10 mb-0">
                                            <div class="input-group">
                                                <select class="form-select" id="mode" name="mode" required
                                                    data-select2-selector="tag">
                                                    <option disabled {{ !isset($order) ? 'selected' : '' }} hidden>Pilih
                                                        mode</option>
                                                    <option value="printing"
                                                        {{ isset($order) && $order->mode === 'printing' ? 'selected' : '' }}>
                                                        Printing
                                                    </option>
                                                    <option value="polosan"
                                                        {{ isset($order) && $order->mode === 'polosan' ? 'selected' : '' }}>
                                                        Polosan
                                                    </option>
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
                                                <textarea name="notes" id="notes" class="form-control" rows="2" placeholder="Tambahkan catatan (opsional)">{{ $order->notes }}</textarea>
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
                                                    name="discount_active" {{ $order->discount_active ? 'checked' : '' }}>
                                                <label class="form-check-label" for="toggleDiscount">
                                                    {{ $order->discount_active ? 'Aktifkan Diskon' : 'Diskon Nonaktif' }}
                                                </label>
                                            </div>
                                            <input type="hidden" id="discount_active_hidden"
                                                name="discount_active_hidden"
                                                value="{{ $order->discount_active ? 1 : 0 }}">
                                        </div>
                                    </div> --}}
                                    <div class="row mb-3 align-items-center">
                                        <div class="col-lg-2">
                                            <label class="fw-semibold">Mode & Diskon:</label>
                                        </div>
                                        <div class="col-lg-10 mb-0">
                                            <div class="row align-items-center">
                                                <!-- 🔹 Kolom Diskon -->
                                                <div class="col-md-6 mb-3 mb-md-0">
                                                    <div class="d-flex flex-column">
                                                        <label class="fw-semibold mb-1">Diskon:</label>
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input" type="checkbox"
                                                                id="toggleDiscount" name="discount_active"
                                                                {{ $order->discount_active ? 'checked' : '' }}>
                                                            <label class="form-check-label fw-semibold"
                                                                for="toggleDiscount">
                                                                {{ $order->discount_active ? 'Aktifkan Diskon' : 'Diskon Nonaktif' }}
                                                            </label>
                                                        </div>
                                                        <input type="hidden" id="discount_active_hidden"
                                                            name="discount_active_hidden"
                                                            value="{{ $order->discount_active ? 1 : 0 }}">
                                                    </div>
                                                </div>

                                                <!-- 🔹 Kolom Mode -->
                                                <div class="col-md-6">
                                                    <div class="d-flex flex-column">
                                                        <label class="fw-semibold mb-1">Mode:</label>
                                                        @php
                                                            $isPrinting =
                                                                !isset($order) ||
                                                                (isset($order) && $order->mode === 'printing');
                                                        @endphp
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input" type="checkbox"
                                                                id="toggleMode" name="mode_toggle"
                                                                {{ $isPrinting ? 'checked' : '' }}>
                                                            <label class="form-check-label fw-semibold" for="toggleMode"
                                                                id="modeLabel">
                                                                {{ $isPrinting ? 'Printing' : 'Polosan' }}
                                                            </label>
                                                        </div>
                                                        <input type="hidden" id="mode" name="mode"
                                                            value="{{ $isPrinting ? 'printing' : 'polosan' }}">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- <div class="card stretch stretch-full">
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
                                                    <th class="text-center wd-50">#</th>
                                                    <th class="text-center wd-450">Product</th>
                                                    <!-- <th class="text-center wd-200">Product Type</th> -->
                                                    <th class="text-center wd-100">Qty</th>
                                                    <th class="text-center wd-150">Price</th>
                                                    <th class="text-center wd-150">Total</th>
                                                    <th class="text-center wd-100">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tab_logic">
                                                @foreach ($order->orderItems as $index => $item)
                                                    <tr id="addr{{ $index }}">
                                                        <td>{{ $index + 1 }}</td>
                                                        <td>
                                                            <select class="form-control select-product" name="product[]"
                                                                id="product_{{ $index }}"
                                                                data-select2-selector="status"
                                                                data-selected-id="{{ $item->satuan == 'satuan' ? $item->product_id : $item->product_bundle_id }}"
                                                                data-selected-type="{{ $item->satuan }}">
                                                                <option value="" disabled hidden>Pilih produk
                                                                </option>
                                                                @foreach ($products as $prod)
                                                                    <option value="satuan_{{ $prod->id }}"
                                                                        {{ $item->satuan == 'satuan' && $item->product_id == $prod->id ? 'selected' : '' }}
                                                                        data-price="{{ $prod->price }}"
                                                                        data-discounts='@json($prod->discounts ?? [])'
                                                                        data-categories='@json($prod->categories ?? [])'
                                                                        data-type="satuan">
                                                                        {{ $prod->name }}
                                                                    </option>
                                                                @endforeach
                                                                @foreach ($productBundles as $bundle)
                                                                    <option value="bundle_{{ $bundle->id }}"
                                                                        {{ $item->satuan == 'bundle' && $item->product_bundle_id == $bundle->id ? 'selected' : '' }}
                                                                        data-price="{{ $bundle->price }}"
                                                                        data-discounts='@json($bundle->discounts ?? [])'
                                                                        data-categories='@json($bundle->categories ?? [])'
                                                                        data-type="bundle">
                                                                        {{ $bundle->name }} (Bundle)
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </td>
                                                        <input type="hidden" class="form-control product-type"
                                                            name="product_type[]" id="product_type_{{ $index }}"
                                                            value="{{ $item->satuan }}" readonly>
                                                        <td>
                                                            <input type="text" inputmode="numeric" name="qty[]"
                                                                class="form-control qty" id="qty_{{ $index }}"
                                                                value="{{ number_format($item->quantity, 0, ',', '.') }}">
                                                        </td>

                                                        </td>
                                                        <td>
                                                            @php
                                                                $isOwner = Auth::user()->role === 'Owner';
                                                            @endphp
                                                            <input type="text"
                                                                class="form-control price_before_discount_display"
                                                                value="{{ number_format($item->price, 2, ',', '.') }}">
                                                            <input type="hidden" name="price_before_discount[]"
                                                                class="price_before_discount"
                                                                id="price_before_discount_{{ $index }}"
                                                                value="{{ number_format($item->price, 2, '.', '') }}">
                                                        </td>
                                                        <td>
                                                            <input type="text"
                                                                class="form-control total_before_discount_display" readonly
                                                                value="{{ number_format($item->price * $item->quantity, 2, ',', '.') }}">
                                                            <input type="hidden" name="total_before_discount[]"
                                                                class="total_before_discount"
                                                                id="total_before_discount_{{ $index }}"
                                                                value="{{ number_format($item->price * $item->quantity, 2, '.', '') }}">
                                                        </td>

                                                        <td class="text-center">
                                                            <div class="d-flex justify-content-center">
                                                                <button type="button" class="btn btn-danger delete-row">
                                                                    <i class="feather-trash"></i>
                                                                </button>
                                                            </div>
                                                        </td>

                                                        <input type="hidden" name="price_after_discount[]"
                                                            class="form-control price_after_discount"
                                                            id="price_after_discount_{{ $index }}"
                                                            value="{{ $item->price_after_discount }}">
                                                        <input type="hidden" name="total_after_discount[]"
                                                            class="form-control total_after_discount"
                                                            id="total_after_discount_{{ $index }}"
                                                            value="{{ $item->total_after_discount }}">
                                                    </tr>
                                                @endforeach
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
                    </div> --}}

                    <div class="card stretch stretch-full">
                        <div class="card-body">

                            <div class="mb-4">
                                <h5 class="fw-bold">Add Products:</h5>
                            </div>

                            <!-- PRODUCT LIST -->
                            <div id="product_list">
                                @foreach ($order->orderItems as $index => $item)
                                    <div class="product-item" data-index="{{ $index }}">
                                        <div class="product-item-header">
                                            <span class="product-number">#{{ $index + 1 }}</span>
                                            <button type="button" class="btn btn-sm btn-danger delete-row">
                                                <i class="feather-trash"></i>
                                            </button>
                                        </div>

                                        <div class="product-grid">

                                            <div class="form-group product-col-span-2">
                                                <label>Product</label>
                                                <select class="form-select select-product" name="product[]"
                                                    data-select2-selector="status">
                                                    <option value="" disabled hidden>Pilih produk</option>

                                                    @foreach ($products as $prod)
                                                        <option value="satuan_{{ $prod->id }}"
                                                            {{ $item->satuan === 'satuan' && $item->product_id == $prod->id ? 'selected' : '' }}
                                                            data-price="{{ $prod->price }}"
                                                            data-discounts='@json($prod->discounts ?? [])'
                                                            data-categories='@json($prod->categories ?? [])'
                                                            data-type="satuan">
                                                            {{ $prod->name }}
                                                        </option>
                                                    @endforeach

                                                    @foreach ($productBundles as $bundle)
                                                        <option value="bundle_{{ $bundle->id }}"
                                                            {{ $item->satuan === 'bundle' && $item->product_bundle_id == $bundle->id ? 'selected' : '' }}
                                                            data-price="{{ $bundle->price }}"
                                                            data-discounts='@json($bundle->discounts ?? [])'
                                                            data-categories='@json($bundle->categories ?? [])'
                                                            data-type="bundle">
                                                            {{ $bundle->name }} (Bundle)
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <input type="hidden" name="product_type[]" class="product-type"
                                                value="{{ $item->satuan }}">

                                            <div class="form-group">
                                                <label>Qty</label>
                                                <input type="text" name="qty[]" class="form-control qty"
                                                    inputmode="numeric"
                                                    value="{{ number_format($item->quantity, 0, ',', '.') }}">
                                            </div>

                                            <div class="form-group">
                                                <label>Price</label>
                                                <input type="text" class="form-control price_before_discount_display"
                                                    value="{{ number_format($item->price, 0, ',', '.') }}">
                                                <input type="hidden" name="price_before_discount[]"
                                                    class="price_before_discount"
                                                    value="{{ number_format($item->price, 2, '.', '') }}">
                                            </div>

                                            <div class="form-group">
                                                <label>Total</label>
                                                <input type="text" class="form-control total_before_discount_display"
                                                    readonly
                                                    value="{{ number_format($item->price * $item->quantity, 0, ',', '.') }}">
                                                <input type="hidden" name="total_before_discount[]"
                                                    class="total_before_discount"
                                                    value="{{ number_format($item->price * $item->quantity, 2, '.', '') }}">
                                            </div>

                                            <input type="hidden" name="price_after_discount[]"
                                                class="price_after_discount" value="{{ $item->price_after_discount }}">

                                            <input type="hidden" name="total_after_discount[]"
                                                class="total_after_discount" value="{{ $item->total_after_discount }}">
                                        </div>
                                    </div>
                                @endforeach
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
                                            <select class="form-select select-product" name="product[]"
                                                data-select2-selector="status">
                                                <option value="" disabled selected hidden>Pilih produk</option>
                                            </select>
                                        </div>

                                        <input type="hidden" name="product_type[]" class="product-type">

                                        <div class="form-group">
                                            <label>Qty</label>
                                            <input type="text" name="qty[]" class="form-control qty"
                                                inputmode="numeric" value="1">
                                        </div>

                                        <div class="form-group">
                                            <label>Price</label>
                                            <input type="text" class="form-control price_before_discount_display"
                                                value="0">
                                            <input type="hidden" name="price_before_discount[]"
                                                class="price_before_discount" value="0">
                                        </div>

                                        <div class="form-group">
                                            <label>Total</label>
                                            <input type="text" class="form-control total_before_discount_display"
                                                readonly value="0">
                                            <input type="hidden" name="total_before_discount[]"
                                                class="total_before_discount" value="0">
                                        </div>

                                        <input type="hidden" name="price_after_discount[]" class="price_after_discount"
                                            value="0">
                                        <input type="hidden" name="total_after_discount[]" class="total_after_discount"
                                            value="0">
                                    </div>
                                </div>
                            </template>

                            <!-- ADD -->
                            <div class="d-flex justify-content-end mt-3">
                                <button type="button" id="add_row" class="btn btn-primary">
                                    Add Item
                                </button>
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
                                                                class="form-control" readonly
                                                                value="{{ number_format($order->sub_total ?? 0, 2, ',', '.') }}">
                                                            <input type="hidden" name="sub_total" id="sub_total"
                                                                value="{{ number_format($order->sub_total ?? 0, 2, '.', '') }}">
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th class="fs-10 text-dark text-uppercase">Total Discount</th>
                                                        <td>
                                                            <input type="text" id="total_discount_display"
                                                                class="form-control text-danger" readonly
                                                                value="{{ number_format($order->total_discount ?? 0, 2, ',', '.') }}">
                                                            <input type="hidden" name="total_discount"
                                                                id="total_discount"
                                                                value="{{ number_format($order->total_discount ?? 0, 2, '.', '') }}">
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th class="fs-10 text-dark text-uppercase bg-gray-100">Grand
                                                            Total</th>
                                                        <td>
                                                            <input type="text" id="total_amount_display"
                                                                class="form-control bg-gray-100 fw-700 text-success"
                                                                readonly
                                                                value="{{ number_format($order->total_amount ?? 0, 2, ',', '.') }}">
                                                            <input type="hidden" name="total_amount" id="total_amount"
                                                                value="{{ number_format($order->total_amount ?? 0, 2, '.', '') }}">
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
                    <h5 class="modal-title fw-semibold text-dark">Nonaktifkan Diskon</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-dark">
                    Apakah kamu yakin ingin menonaktifkan semua diskon?
                    Semua harga akan dihitung ulang tanpa potongan harga.
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
                    <h5 class="modal-title fw-semibold">Konfirmasi Tanggung Jawab</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body text-dark">
                    Apakah Anda bersedia <strong>bertanggung jawab</strong> atas keputusan menonaktifkan semua diskon ini?
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
                        <strong id="nextModeText">Polosan</strong>?
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

        let discountEnabled = {{ $order->discount_active ? 'true' : 'false' }};
        let pendingToggleOff = false;

        // 🔥 Inisialisasi label dan hidden input sesuai kondisi awal
        $(document).ready(function() {
            const label = $('#toggleDiscount').next('label');
            if (discountEnabled) {
                $('#toggleDiscount').prop('checked', true);
                $('#discount_active_hidden').val(1);
                label.text('Diskon Aktif').removeClass('text-danger').addClass('text-success');
            } else {
                $('#toggleDiscount').prop('checked', false);
                $('#discount_active_hidden').val(0);
                label.text('Diskon Nonaktif').removeClass('text-success').addClass('text-danger');
            }

            $('.product-item').each(function() {
                const select = $(this).find('.select-product');

                const selectedVal = select.val();
                let selectedType = null,
                    selectedId = null;

                if (selectedVal) {
                    [selectedType, selectedId] = selectedVal.split('_');
                }

                populateProducts(select[0], selectedId, selectedType);

                select.select2({
                    placeholder: 'Pilih produk',
                    width: '100%'
                });
            });


            recalcAllRows(); // ✅ hitung ulang setelah semuanya siap
        });

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

        // Populate dropdown produk
        function populateProducts(selectEl, selectedId = null, selectedType = null) {
            $(selectEl).empty().append('<option value="" disabled selected hidden>Pilih produk</option>');
            allProducts.forEach(item => {
                const option = $('<option>', {
                        value: item.type + '_' + item.id,
                        text: `[${item.sku || '-'}] ${item.name}` + (item.type === 'bundle' ? '' : '')
                    })
                    .data('price', item.price)
                    .data('discounts', item.discounts || [])
                    .data('categories', item.categories || [])
                    .data('type', item.type);

                if (selectedId && selectedType === item.type && selectedId == item.id) {
                    option.prop('selected', true);
                }

                $(selectEl).append(option);
            });
        }

        function formatNumber(num) {
            return new Intl.NumberFormat('id-ID', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(num);
        }

        $(document).on('change', '#toggleDiscount', function() {
            const isChecked = $(this).is(':checked');
            const label = $(this).next('label');

            if (!isChecked) {
                pendingToggleOff = true;
                $('#confirmDisableDiscountModal').modal('show');
                $(this).prop('checked', true);
            } else {
                discountEnabled = true;
                $('#discount_active_hidden').val(1);
                label.text('Diskon Aktif').removeClass('text-danger').addClass('text-success');
                recalcAllRows();
            }
        });

        $('#confirmDisableDiscountBtn').on('click', function() {
            $('#confirmDisableDiscountModal').modal('hide');
            if (pendingToggleOff) $('#confirmResponsibilityModal').modal('show');
        });

        $('#confirmResponsibilityBtn').on('click', function() {
            $('#confirmResponsibilityModal').modal('hide');
            discountEnabled = false;
            $('#toggleDiscount').prop('checked', false);
            $('#discount_active_hidden').val(0);
            const label = $('#toggleDiscount').next('label');
            label.text('Diskon Nonaktif').removeClass('text-success').addClass('text-danger');
            recalcAllRows();
            pendingToggleOff = false;
        });

        function calculateRow(row) {
            const selectedOption = row.find('select[name="product[]"] option:selected');

            // 🔥 ambil nilai input manual dari HIDDEN
            // let manualPrice = row.find('input.price_before_discount').val();
            // manualPrice = manualPrice === "" ? null : parseFloat(manualPrice);

            // // 🔥 base price logic BARU:
            // // - kalau user sudah input (termasuk 0), pakai input user
            // // - kalau belum pernah input, pakai harga product
            // let basePrice = (manualPrice !== null && !isNaN(manualPrice)) ?
            //     manualPrice :
            //     (parseFloat(selectedOption.data('price')) || 0);

            let manualPriceRaw = row.find('input.price_before_discount').val();

            // 🔥 anggap "0" sebagai BELUM DISET
            let manualPrice = (manualPriceRaw === '' || manualPriceRaw === '0') ?
                null :
                parseFloat(manualPriceRaw);

            let basePrice = manualPrice !== null ?
                manualPrice :
                (parseFloat(selectedOption.data('price')) || 0);


            const discounts = selectedOption.data('discounts') || [];
            const categories = selectedOption.data('categories') || [];

            const qty = parseFloat(row.find('input[name="qty[]"]').val().replace(/\./g, '')) || 0;

            const priceBeforeDiscount = basePrice;
            const totalBeforeDiscount = basePrice * qty;

            let finalPrice = priceBeforeDiscount;
            let allDiscounts = discountEnabled ? [...discounts] : [];

            // 🔥 hanya hitung diskon kalau diskon aktif DAN harga tidak 0
            if (discountEnabled && priceBeforeDiscount > 0) {

                categories.forEach(cat => {
                    if (cat.discounts) {
                        allDiscounts = allDiscounts.concat(cat.discounts);
                    }
                });

                allDiscounts.forEach(discount => {
                    let eligible = false;

                    if (discount.apply_on === 'Product') {
                        if (discount.minimum_based_on === 'Quantity of Items' &&
                            qty >= discount.minimum_qty_or_amount) {
                            eligible = true;
                        } else if (discount.minimum_based_on === 'Purchase Amount' &&
                            totalBeforeDiscount >= discount.minimum_qty_or_amount) {
                            eligible = true;
                        }
                    } else if (discount.apply_on === 'Category') {
                        let totalQtyCategory = 0;
                        let totalAmountCategory = 0;

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

                        if (discount.minimum_based_on === 'Quantity of Items' &&
                            totalQtyCategory >= discount.minimum_qty_or_amount) {
                            eligible = true;
                        } else if (discount.minimum_based_on === 'Purchase Amount' &&
                            totalAmountCategory >= discount.minimum_qty_or_amount) {
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

            // simpan hidden
            row.find('input.price_before_discount').val(priceBeforeDiscount.toFixed(2));
            row.find('input.total_before_discount').val(totalBeforeDiscount.toFixed(2));
            row.find('input.price_after_discount').val(finalPrice.toFixed(2));
            row.find('input.total_after_discount').val(totalAfterDiscount.toFixed(2));

            // update tampilan (display)
            if (!row.find('.price_before_discount_display').is(':focus')) {
                row.find('.price_before_discount_display').val(formatNumber(priceBeforeDiscount));
            }
            row.find('.total_before_discount_display').val(formatNumber(totalBeforeDiscount));
        }

        // function recalcAllRows() {
        //     $('tr[id^="addr"]').each(function() {
        //         calculateRow($(this));
        //     });
        //     calcTotalSummary();
        // }

        function recalcAllRows() {
            $('.product-item').each(function() {
                calculateRow($(this));
            });
            calcTotalSummary();
        }

        function calcTotalSummary() {
            let subTotal = 0;
            let totalAfterDiscount = 0;

            $(".total_before_discount").each(function() {
                subTotal += parseFloat($(this).val()) || 0;
            });

            $(".total_after_discount").each(function() {
                totalAfterDiscount += parseFloat($(this).val()) || 0;
            });

            const totalDiscount = subTotal - totalAfterDiscount;

            $("#sub_total").val(subTotal.toFixed(2));
            $("#total_discount").val(totalDiscount.toFixed(2));
            $("#total_amount").val(totalAfterDiscount.toFixed(2));

            $("#sub_total_display").val(formatNumber(subTotal));
            $("#total_discount_display").val(formatNumber(totalDiscount));
            $("#total_amount_display").val(formatNumber(totalAfterDiscount));
        }

        function updateRowTypeAndPrice(row) {
            const selectedOption = row.find('select[name="product[]"] option:selected');
            if (!selectedOption.length) return;
            const type = selectedOption.data('type') || '';
            row.find('.product-type').val(type);
            calculateRow(row);
        }

        // function initSelect2() {
        //     $('[data-select2-selector="status"]').select2({
        //         placeholder: 'Pilih produk',
        //         width: '100%'
        //     }).each(function() {
        //         if ($(this).hasClass('select-product')) {
        //             const selectedVal = $(this).val();
        //             const selectedType = $(this).closest('tr').find('.product-type').val();
        //             const selectedId = selectedVal ? selectedVal.split('_')[1] : null;
        //             populateProducts(this, selectedId, selectedType);
        //         }
        //     });
        // }

        document.addEventListener('DOMContentLoaded', function() {
            // initSelect2();
            recalcAllRows();

            let rowCount = document.querySelectorAll('#tab_logic tbody tr').length;

            // $('#add_row').on('click', function() {
            //     const tableBody = $('#tab_logic tbody');
            //     const newRow = $(`
        //         <tr id="addr${rowCount}">
        //             <td>${rowCount + 1}</td>

        //             <td>
        //                 <select class="form-control select-product" 
        //                     name="product[]" 
        //                     id="product_${rowCount}"
        //                     data-select2-selector="status">
        //                     <option value="" disabled selected hidden>Pilih produk</option>
        //                 </select>
        //             </td>

        //             <input type="hidden" 
        //                 name="product_type[]" 
        //                 id="product_type_${rowCount}"
        //                 class="form-control product-type" 
        //                 readonly>

        //             <td>
        //                 <input type="text" 
        //                     name="qty[]" 
        //                     class="form-control qty"
        //                     id="qty_${rowCount}"
        //                     value="1"
        //                     inputmode="numeric">
        //             </td>

        //             <td>
        //                 <input type="text"
        //                     class="form-control price_before_discount_display"
        //                     value="0">

        //                 <input type="hidden"
        //                     name="price_before_discount[]"
        //                     class="price_before_discount"
        //                     id="price_before_discount_${rowCount}"
        //                     value="0">
        //             </td>

        //             <td>
        //                 <input type="text"
        //                     class="form-control total_before_discount_display"
        //                     readonly
        //                     value="0">

        //                 <input type="hidden"
        //                     name="total_before_discount[]"
        //                     class="total_before_discount"
        //                     id="total_before_discount_${rowCount}"
        //                     value="0">
        //             </td>

        //             <td class="text-center">
        //                 <div class="d-flex justify-content-center">
        //                     <button type="button" class="btn btn-danger delete-row">
        //                         <i class="feather-trash"></i>
        //                     </button>
        //                 </div>          
        //             </td>

        //             <input type="hidden" 
        //                 name="price_after_discount[]" 
        //                 class="price_after_discount"
        //                 id="price_after_discount_${rowCount}"
        //                 value="0">

        //             <input type="hidden" 
        //                 name="total_after_discount[]" 
        //                 class="total_after_discount"
        //                 id="total_after_discount_${rowCount}"
        //                 value="0">
        //         </tr>
        //     `);

            //     tableBody.append(newRow);
            //     initSelect2(newRow.find('.select-product'));
            //     rowCount++;
            // });

            $('#add_row').on('click', function() {
                const index = $('.product-item').length;

                const html = document.getElementById('product_item_template')
                    .innerHTML
                    .replace(/__index__/g, index)
                    .replace(/__number__/g, index + 1);

                const wrapper = document.createElement('div');
                wrapper.innerHTML = html;

                const item = wrapper.firstElementChild;
                $('#product_list').append(item);

                populateProducts(item.querySelector('.select-product'));
                $(item).find('.select-product').select2({
                    width: '100%'
                });

                recalcAllRows();
            });

            // $(document).on('click', '.delete-row', function() {
            //     $(this).closest('tr').remove();

            //     $('#tab_logic tbody tr').each(function(i, el) {
            //         $(el).find('td:first').text(i + 1);
            //     });

            //     rowCount = $('#tab_logic tbody tr').length;
            //     recalcAllRows();
            // });

            $(document).on('click', '.delete-row', function() {
                $(this).closest('.product-item').remove();

                $('.product-item').each(function(i) {
                    $(this).attr('data-index', i);
                    $(this).find('.product-number').text('#' + (i + 1));
                });

                recalcAllRows();
            });

            // $(document).on('change', 'select[name="product[]"]', function() {
            //     const row = $(this).closest('tr');
            //     updateRowTypeAndPrice(row);
            //     recalcAllRows();
            // });

            // $(document).on('input', 'input[name="qty[]"]', recalcAllRows);

            // $(document).on('change', '.select-product', function() {
            //     const row = $(this).closest('.product-item');
            //     const type = $(this).find(':selected').data('type') || '';
            //     row.find('.product-type').val(type);
            //     recalcAllRows();
            // });

            $(document).on('change', '.select-product', function() {
                const row = $(this).closest('.product-item');
                const selected = $(this).find(':selected');

                const type = selected.data('type') || '';
                const price = parseFloat(selected.data('price')) || 0;

                // 🔥 SET HARGA DB KE HIDDEN
                row.find('.product-type').val(type);
                row.find('.price_before_discount').val(price.toFixed(2));

                recalcAllRows();
            });


            $(document).on('input', '.qty, .price_before_discount_display', recalcAllRows);
        });

        $(document).ready(function() {
            const initialCustomerId = $('#customers').val();
            if (initialCustomerId) {
                updateAddresses(initialCustomerId);
            }

            $('#customers').on('change', function() {
                const customerId = $(this).val();
                updateAddresses(customerId);
            });

            $('#addresses').on('change', function() {
                updateGoogleMapsLink();
            });

            function updateAddresses(customerId) {
                const addresses = customerAddresses[customerId] || [];
                const $addressSelect = $('#addresses');
                const selectedAddressId = "{{ $order->customer_address_id ?? '' }}";

                $addressSelect.empty().append('<option disabled hidden>Pilih alamat</option>');

                addresses.forEach(function(address, index) {
                    const isSelected = address.id == selectedAddressId;
                    $addressSelect.append(
                        `<option value="${address.id}" data-map="${address.google_maps}" ${isSelected ? 'selected' : ''}>
                            ${address.business_name ?? 'None'} - ${address.address}
                        </option>`
                    );
                });

                updateGoogleMapsLink();
            }

            function updateGoogleMapsLink() {
                const selectedOption = $('#addresses').find('option:selected');
                const mapUrl = selectedOption.data('map');

                if (mapUrl) {
                    $('#google-maps-link').html(`
                    <a href="${mapUrl}" target="_blank" class="btn btn-sm btn-outline-primary mt-2">
                        Lihat di Google Maps
                    </a>
                `);
                } else {
                    $('#google-maps-link').empty();
                }
            }
        });

        function showError(element, message) {
            $(element).next(".invalid-feedback").remove();

            $(element).after(`<div class="invalid-feedback">${message}</div>`);

            $(element).addClass("is-invalid");
        }

        $("#customers, #addresses").on("change", function() {
            $(this).removeClass("is-invalid");
            $(this).next(".invalid-feedback").remove();
        });

        $("form").on("submit", function(e) {
            let valid = true;

            if (!$("#customers").val()) {
                showError($("#customers"), "Customer wajib dipilih");
                valid = false;
            }

            if (!$("#addresses").val()) {
                showError($("#addresses"), "Alamat wajib dipilih");
                valid = false;
            }

            if (!valid) {
                e.preventDefault();
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            const optionEl = document.getElementById('due_date_option');
            const dateInput = document.getElementById('custom_due_date');

            const savedDate = dateInput.value ? new Date(dateInput.value) : null;
            const today = new Date();
            let defaultOption = 'custom';

            if (savedDate) {
                const diffDays = Math.floor((savedDate - today) / (1000 * 60 * 60 * 24));

                if (diffDays === 0) {
                    defaultOption = 'today';
                } else if (diffDays === 7) {
                    defaultOption = '1_week';
                } else if (diffDays === 30) {
                    defaultOption = '1_month';
                } else if (diffDays === 90) {
                    defaultOption = '3_months';
                } else {
                    defaultOption = 'custom';
                }
            } else {
                defaultOption = 'none';
            }

            optionEl.value = defaultOption;

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

            updateDueDate();
        });

        $(document).on('select2:open', () => {
            setTimeout(() => {
                document.querySelector('.select2-container--open .select2-search__field')?.focus();
            }, 50);
        });

        // === Harga bisa diubah hanya oleh Owner ===
        let priceInputTimeout;
        $(document).on('input', '.price_before_discount_display', function() {
            // if (!isOwner) return; // hanya Owner yang bisa ubah harga

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

        $(document).on('input', 'input[name="qty[]"]', function(e) {
            let rawValue = $(this).val().replace(/\D/g, '');

            let formatted = new Intl.NumberFormat('id-ID').format(rawValue);

            $(this).val(formatted);
        });

        $('#orderForm').on('submit', function() {
            // $('input[name="qty[]"]').each(function() {
            //     const raw = $(this).val().replace(/\./g, '');
            //     $(this).val(raw);
            // });

            $('input[name="qty[]"]').each(function() {
                $(this).val($(this).val().replace(/\./g, ''));
            });

            // pastikan price hidden valid
            $('input.price_before_discount').each(function() {
                if ($(this).val() === '' || isNaN($(this).val())) {
                    $(this).val(0);
                }
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const toggle = document.getElementById('toggleMode');
            const label = document.getElementById('modeLabel');
            const hidden = document.getElementById('mode');
            const nextModeText = document.getElementById('nextModeText');
            const confirmChangeBtn = document.getElementById('confirmChangeModeBtn');
            const confirmResponsibilityBtn = document.getElementById('confirmModeResponsibilityBtn');

            // 🔥 FIX: Ambil nilai dari hidden input yang udah di-set Blade
            const initialMode = hidden.value; // Ambil dari Blade, bukan hardcode!

            // Set label sesuai nilai awal
            label.textContent = initialMode === 'printing' ? 'Printing' : 'Polosan';
            toggle.checked = initialMode === 'printing';

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

                    // 🔥 Tambahkan log untuk debug
                    console.log('Mode changed to:', pendingMode);

                    pendingMode = null;
                }
            });

            // 🔥 Debug log saat page load
            console.log('Initial mode from Blade:', initialMode);
            console.log('Hidden input value:', hidden.value);
        });
    </script>
@endpush
