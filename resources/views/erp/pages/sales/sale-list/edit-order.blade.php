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
        .select2-container--bootstrap-5 .select2-selection--single {
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
            padding: 4px 8px !important;
        }

        /* 🔹 Perbesar ikon dropdown */
        .select2-selection__arrow {
            height: 42px !important;
            right: 10px !important;
        }

        /* Kolom Qty, Price, Total */
        .product-grid input.qty,
        .product-grid input.price_before_discount_display,
        .product-grid input.total_before_discount_display {
            font-size: 16px !important;
            font-weight: 600;
            height: 44px !important;
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
            /* border: 1px solid #e5e7eb; */
            border-radius: 12px;
            /* padding: 14px; */
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
            grid-template-columns:
                minmax(380px, 4fr) 100px 130px 130px 130px 130px 52px;
            gap: 10px;
            align-items: start;
        }

        .product-bundle-inline {
            display: grid;
            grid-template-columns: 1fr;
            gap: 8px;
        }

        .product-bundle-inline:has(.bundle-wrapper:not(.d-none)) {
            grid-template-columns: 1fr 1fr;
        }

        .product-delete-col {
            display: flex;
            flex-direction: column;
        }

        .product-delete-col label {
            height: 32px;
            margin-bottom: 6px;
        }

        .product-delete-col .delete-row {
            height: 44px;
            width: 44px;
            padding: 0;
        }

        .product-grid .form-group {
            display: flex;
            flex-direction: column;
        }

        .product-grid .form-group>label,
        .product-label-row {
            height: 32px;
            display: flex;
            align-items: center;
            margin-bottom: 6px;
        }

        .product-label-row {
            justify-content: space-between;
        }

        .product-grid .form-control,
        .product-grid .select2-container--bootstrap-5 .select2-selection--single {
            height: 44px !important;
        }

        .product-col-span-2 {
            grid-column: span 1;
        }

        .bundle-toggle {
            margin: 0;
        }

        .bundle-toggle input {
            display: none;
        }

        .bundle-toggle span {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            height: 32px;
            padding: 0 12px;
            border-radius: 8px;
            background: #0d6efd;
            color: #fff;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }

        .bundle-toggle input:checked+span {
            background: #198754;
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
            .select2-container--bootstrap-5 .select2-selection--single {
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

        .product-bundle-inline {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 8px;
            align-items: center;
        }

        .product-bundle-inline:has(.bundle-wrapper:not(.d-none)) {
            grid-template-columns: auto 1fr 1fr;
        }

        .product-grid-header {
            font-size: 14px;
            color: #64748b;
            font-weight: 500;
        }

        .product-item .form-group>label,
        .product-delete-col>label {
            display: none !important;
        }

        .product-option-name,
        .product-option-selected .product-option-name {
            font-weight: 500;
            line-height: 1.2;
        }

        .product-option-sku {
            font-size: 11px;
            color: #6c757d;
            line-height: 1.1;
        }


        .item-mode+.select2 .select2-selection__rendered {
            font-size: 14px !important;
            font-weight: 500 !important;
        }

        .item-mode+.select2 .select2-results__option {
            font-size: 14px !important;
        }

        .product-unit.unit-selected {
            color: #0d6efd !important;
            font-weight: 600 !important;
        }
        @include('erp.pages.partials.transaction-form-mobile-styles')
    </style>
@endpush

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Sale List</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Sale List</li>
                <li class="breadcrumb-item">Edit Sale List</li>
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
                        <span>Edit Order</span>
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
    <div class="main-content transaction-form-page m-0 m-md-2 m-lg-2 p-0 p-md-0 p-lg-0 pt-1 pt-md-0">
        <div class="row">
            <div class="col-12">
                <form action="/erp/sales/sale-list/update/{{ $order->id }}" method="POST" id="orderForm">
                    @csrf
                    @method('PUT')
                    <div class="card stretch stretch-full">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="row mb-2 align-items-center">
                                        <div class="col-lg-2">
                                            <label for="order_number" class="fw-semibold">Order Number:</label>
                                        </div>
                                        <div class="col-lg-10 mb-0">
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="order_number"
                                                    name="order_number"
                                                    value="{{ old('order_number', $order->order_number) }}" readonly>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mb-2 align-items-center">
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
                                    <div class="row mb-2 align-items-center">
                                        <div class="col-lg-2">
                                            <label for="due_date_option" class="fw-semibold">Due Date:</label>
                                        </div>
                                        <div class="col-lg-10 mb-0">
                                            <div class="input-group">
                                                <select class="form-select" id="due_date_option" name="due_date_option"
                                                    style="font-size: 14px;" required>
                                                    <option value="none">Tidak ada due date</option>
                                                    <option value="today">Hari ini</option>
                                                    <option value="1_week">1 Minggu</option>
                                                    <option value="1_month">1 Bulan</option>
                                                    <option value="3_months">3 Bulan</option>
                                                    <option value="custom">Custom</option>
                                                </select>
                                            </div>
                                            <div id="custom_due_date_wrapper" class="mt-1">
                                                <input type="date" class="form-control" id="custom_due_date"
                                                    name="custom_due_date"
                                                    value="{{ $order->due_date ? \Carbon\Carbon::parse($order->due_date)->format('Y-m-d') : '' }}">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mb-2 align-items-center">
                                        <div class="col-lg-2">
                                            <label for="customers" class="fw-semibold">Business:</label>
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
                                                    <option disabled selected hidden>Choose Business</option>
                                                    @foreach ($customers as $index => $customer)
                                                        @php
                                                            $bg = $bgColors[$loop->index % count($bgColors)];
                                                        @endphp
                                                        <option value="{{ $customer->id }}" data-bg="{{ $bg }}"
                                                            {{ $order->customer_id == $customer->id ? 'selected' : '' }}>
                                                            {{ $customer->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mb-2 align-items-center">
                                        <div class="col-lg-2">
                                            <label for="customer_accounts" class="fw-semibold">Contact:</label>
                                        </div>
                                        <div class="col-lg-10 mb-0">
                                            <div class="input-group">
                                                <select class="form-select form-control max-select"
                                                    data-select2-selector="tag" id="customer_accounts"
                                                    name="customer_account_id">
                                                    <option disabled hidden>Choose Contact</option>

                                                    @if ($order->customer)
                                                        @foreach ($order->customer->accounts as $account)
                                                            <option value="{{ $account->id }}"
                                                                {{ $order->customer_account_id == $account->id ? 'selected' : '' }}>
                                                                {{ $account->name ?? '-' }} -
                                                                {{ $account->whatsapp_number ?? ($account->email ?? '-') }}
                                                            </option>
                                                        @endforeach
                                                    @endif
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mb-2 align-items-center">
                                        <div class="col-lg-2">
                                            <label for="addresses" class="fw-semibold">Address:</label>
                                        </div>
                                        <div class="col-lg-10 mb-0">
                                            <div class="input-group">
                                                <select class="form-select form-control max-select"
                                                    data-select2-selector="tag" id="addresses"
                                                    name="customer_address_id">
                                                    <option disabled hidden>Choose Address</option>
                                                    @if ($order->customer)
                                                        @foreach ($order->customer->addresses as $index => $address)
                                                            <option value="{{ $address->id }}"
                                                                data-map="{{ $address->google_maps }}"
                                                                {{ $order->customer_address_id == $address->id ? 'selected' : '' }}>
                                                                Alamat ke-{{ $index + 1 }} - {{ $address->address }}
                                                            </option>
                                                        @endforeach
                                                    @endif
                                                </select>
                                            </div>
                                            <div id="google-maps-link" class="mt-1">
                                                @if ($order->customerAddress && $order->customerAddress->google_maps)
                                                    <a href="{{ $order->customerAddress->google_maps }}" target="_blank"
                                                        class="btn btn-sm btn-outline-primary mt-1">
                                                        Lihat di Google Maps
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <input type="hidden" value="6" name="transaction_type" id="transaction_type">
                                    <div class="row mb-2 align-items-center">
                                        <div class="col-lg-2">
                                            <label for="notes" class="fw-semibold">Note:</label>
                                        </div>
                                        <div class="col-lg-10 mb-0">
                                            <div class="input-group">
                                                <textarea name="notes" id="notes" class="form-control" rows="2"
                                                    placeholder="Tambahkan catatan (opsional)">{{ $order->notes }}</textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mb-2 align-items-center">
                                        <div class="col-lg-2">
                                            <label class="fw-semibold">Diskon:</label>
                                        </div>
                                        <div class="col-lg-10 mb-0">
                                            <div class="row align-items-center">
                                                <!-- 🔹 Kolom Diskon -->
                                                <div class="col-md-6 mb-2 mb-md-0">
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
                                                {{-- <div class="col-md-6">
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
                                                </div> --}}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mb-2 align-items-center">
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
                    <div class="mt-2">

                        <div class="mb-2">
                            <h5 class="fw-bold">Add Products:</h5>
                        </div>

                        <div class="product-grid product-grid-header mb-1">
                            <div class="product-col-span-2">Product</div>
                            <div>Unit</div>
                            <div>Qty</div>
                            <div>Price</div>
                            <div>Mode</div>
                            <div>Total</div>
                            <div></div>
                        </div>

                        <!-- PRODUCT LIST -->
                        <div id="product_list">
                            @foreach ($order->orderItems as $index => $item)
                                @php
                                    $isBundle = $item->satuan === 'bundle';

                                    $primaryProductId = null;
                                    $secondaryProductId = null;

                                    if ($isBundle && $item->productBundle) {
                                        $primaryProductId = optional($item->productBundle->primaryItem)->product_id;
                                        $secondaryProductId = optional($item->productBundle->secondaryItems->first())
                                            ->product_id;
                                    }

                                    // FK unit conversion bisa jadi NULL (nullOnDelete) kalau unit conversion
                                    // produk/bundle pernah di-resync ulang. Fallback ke unit_name yang tersimpan.
                                    $selectedConversionId = $isBundle
                                        ? $item->product_bundle_unit_conversion_id
                                        : $item->product_unit_conversion_id;
                                    $fallbackUnitName = $item->unit_name;
                                @endphp
                                <div class="product-item" data-index="{{ $index }}">
                                    <input type="hidden" name="order_item_id[]" value="{{ $item->id }}">

                                    <div class="product-grid">
                                        <div class="form-group product-col-span-2">
                                            {{-- <div class="product-label-row">
                                                <label>Product</label>

                                                <label class="bundle-toggle">
                                                    <input type="checkbox" class="add-bundle-check" value="1"
                                                        {{ $isBundle ? 'checked' : '' }}>
                                                    <span>
                                                        <i class="feather-package"></i>
                                                        Bundle
                                                    </span>
                                                </label>
                                            </div> --}}

                                            <div class="product-bundle-inline">
                                                <label class="bundle-toggle">
                                                    <input type="checkbox" class="add-bundle-check" value="1"
                                                        {{ $isBundle ? 'checked' : '' }}>
                                                    <span>
                                                        <i class="feather-package"></i>
                                                        Bundle
                                                    </span>
                                                </label>

                                                <select class="form-control select-product" data-select2-selector="tag"
                                                    name="product[]">
                                                    <option value="" disabled hidden>Pilih produk</option>

                                                    {{-- Daftar produk diisi oleh JS populateProducts() dari `products`
                                                         JSON saat document ready. Render seluruh katalog di sini
                                                         percuma (langsung di-empty() JS) dan biayanya produk x baris
                                                         item, jadi cukup opsi yang sedang terpilih supaya
                                                         select.val() masih terbaca saat inisialisasi. --}}
                                                    @if (!$isBundle && $item->product_id)
                                                        <option value="satuan_{{ $item->product_id }}" selected>
                                                            {{ optional($item->product)->name ?? '-' }}
                                                        </option>
                                                    @endif
                                                </select>

                                                <div class="bundle-wrapper {{ $isBundle ? '' : 'd-none' }}">
                                                    <select
                                                        class="form-control bundle-secondary-product select-secondary-product"
                                                        data-select2-selector="tag" name="bundle_secondary_product_id[]"
                                                        data-selected-bundle-id="{{ $item->product_bundle_id }}"
                                                        data-selected-secondary-id="{{ $secondaryProductId }}">
                                                        <option value="">Pilih product bundle</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <input type="hidden" name="product_type[]" class="product-type"
                                            value="{{ $item->satuan }}">


                                        <div class="form-group product-unit-wrapper">
                                            <label>Unit</label>

                                            <select name="product_unit_id[]" class="form-control product-unit">
                                                <option value="">Pilih unit</option>

                                                @if ($item->satuan === 'satuan' && $item->product)
                                                    @foreach ($item->product->unitConversions as $conversion)
                                                        @php
                                                            $conversionUnitName = optional($conversion->unit)->name;
                                                            $isSelectedUnit = $selectedConversionId
                                                                ? (int) $selectedConversionId === (int) $conversion->id
                                                                : ($fallbackUnitName &&
                                                                    strcasecmp($fallbackUnitName, (string) $conversionUnitName) === 0);
                                                        @endphp
                                                        <option value="{{ $conversion->id }}"
                                                            data-unit-id="{{ $conversion->unit_id }}"
                                                            data-unit-name="{{ $conversionUnitName }}"
                                                            data-conversion-value="{{ $conversion->conversion_value }}"
                                                            data-sale-price="{{ $conversion->sale_price }}"
                                                            data-prices="{{ rawurlencode(json_encode($conversion->prices ?? [])) }}"
                                                            @selected($isSelectedUnit)>
                                                            {{ $conversionUnitName }}
                                                        </option>
                                                    @endforeach
                                                @elseif ($item->satuan === 'bundle' && $item->productBundle)
                                                    @foreach ($item->productBundle->unitConversions as $conversion)
                                                        @php
                                                            $conversionUnitName = optional($conversion->unit)->name;
                                                            $isSelectedUnit = $selectedConversionId
                                                                ? (int) $selectedConversionId === (int) $conversion->id
                                                                : ($fallbackUnitName &&
                                                                    strcasecmp($fallbackUnitName, (string) $conversionUnitName) === 0);
                                                        @endphp
                                                        <option value="{{ $conversion->id }}"
                                                            data-unit-id="{{ $conversion->unit_id }}"
                                                            data-unit-name="{{ $conversionUnitName }}"
                                                            data-conversion-value="{{ $conversion->conversion_value }}"
                                                            data-sale-price="{{ $conversion->sale_price }}"
                                                            data-prices="{{ rawurlencode(json_encode($conversion->prices ?? [])) }}"
                                                            @selected($isSelectedUnit)>
                                                            {{ $conversionUnitName }}
                                                        </option>
                                                    @endforeach
                                                @endif
                                            </select>

                                            <input type="hidden" name="unit_conversion_value[]"
                                                class="unit-conversion-value"
                                                value="{{ $item->unit_conversion_value ?? 1 }}">

                                            <input type="hidden" name="unit_name[]" class="unit-name"
                                                value="{{ $item->unit_name ?? 'Pcs' }}">
                                        </div>

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
                                            <label>Mode</label>
                                            <select name="mode[]" class="form-control item-mode">
                                                @foreach ($priceModes as $priceMode)
                                                    <option value="{{ $priceMode->slug }}"
                                                        @selected(($item->mode ?? 'printing') === $priceMode->slug)
                                                        @disabled(!$priceMode->is_active && ($item->mode ?? '') !== $priceMode->slug)>
                                                        {{ $priceMode->name }}{{ $priceMode->is_active ? '' : ' (Inactive)' }}
                                                    </option>
                                                @endforeach
                                            </select>
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

                                        <div class="product-delete-col">
                                            <label>&nbsp;</label>
                                            <button type="button" class="btn btn-danger delete-row">
                                                <i class="feather-trash"></i>
                                            </button>
                                        </div>

                                        <input type="hidden" name="price_after_discount[]" class="price_after_discount"
                                            value="{{ $item->price_after_discount }}">

                                        <input type="hidden" name="total_after_discount[]" class="total_after_discount"
                                            value="{{ $item->total_after_discount }}">
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <template id="product_item_template">
                            <div class="product-item" data-index="__index__">
                                <input type="hidden" name="order_item_id[]" value="">
                                <div class="product-grid">

                                    <div class="form-group product-col-span-2">
                                        {{-- <div class="product-label-row">
                                            <label>Product</label>

                                            <label class="bundle-toggle">
                                                <input type="checkbox" class="add-bundle-check" value="1">
                                                <span>
                                                    <i class="feather-package"></i>
                                                    Bundle
                                                </span>
                                            </label>
                                        </div> --}}

                                        <div class="product-bundle-inline">
                                            <label class="bundle-toggle">
                                                <input type="checkbox" class="add-bundle-check" value="1">
                                                <span>
                                                    <i class="feather-package"></i>
                                                    Bundle
                                                </span>
                                            </label>
                                            <select class="form-control select-product" data-select2-selector="tag"
                                                name="product[]">
                                                <option value="" disabled selected hidden>Pilih produk</option>
                                            </select>

                                            <div class="bundle-wrapper d-none">
                                                <select class="form-control bundle-secondary-product"
                                                    data-select2-selector="tag" name="bundle_secondary_product_id[]">
                                                    <option value="">Pilih product bundle</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <input type="hidden" name="product_type[]" class="product-type">

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
                                        <label>Mode</label>
                                        <select name="mode[]" class="form-control item-mode">
                                            @foreach ($priceModes as $priceMode)
                                                <option value="{{ $priceMode->slug }}"
                                                    @selected(($item->mode ?? 'printing') === $priceMode->slug)
                                                    @disabled(!$priceMode->is_active && ($item->mode ?? '') !== $priceMode->slug)>
                                                    {{ $priceMode->name }}{{ $priceMode->is_active ? '' : ' (Inactive)' }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Total</label>
                                        <input type="text" class="form-control total_before_discount_display" readonly
                                            value="0">
                                        <input type="hidden" name="total_before_discount[]"
                                            class="total_before_discount" value="0">
                                    </div>

                                    <div class="product-delete-col">
                                        <label>&nbsp;</label>
                                        <button type="button" class="btn btn-danger delete-row">
                                            <i class="feather-trash"></i>
                                        </button>
                                    </div>

                                    <input type="hidden" name="price_after_discount[]" class="price_after_discount"
                                        value="0">
                                    <input type="hidden" name="total_after_discount[]" class="total_after_discount"
                                        value="0">
                                </div>
                            </div>
                        </template>

                        <!-- ADD -->
                        <div class="d-flex justify-content-end mt-2">
                            <button type="button" id="add_row" class="btn btn-primary">
                                Add Item
                            </button>
                        </div>

                        <div class="col-lg-12">
                            <div class="row justify-content-end">
                                <div class="col-lg-4 mt-2">
                                    <div class="mb-2">
                                        <h5 class="fw-bold">Grand Total:</h5>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-bordered" id="tab_logic_total">
                                            <tbody>
                                                <tr>
                                                    <th class="fs-10 text-dark text-uppercase">Sub Total (Before
                                                        Discount)</th>
                                                    <td>
                                                        <input type="text" id="sub_total_display" class="form-control"
                                                            readonly
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
                                                        <input type="hidden" name="total_discount" id="total_discount"
                                                            value="{{ number_format($order->total_discount ?? 0, 2, '.', '') }}">
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th class="fs-10 text-dark text-uppercase bg-gray-100">Grand
                                                        Total</th>
                                                    <td>
                                                        <input type="text" id="total_amount_display"
                                                            class="form-control bg-gray-100 fw-700 text-success" readonly
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
                    {{-- <div class="card stretch stretch-full">
                    </div> --}}

                    @include('erp.pages.partials.transaction-mobile-actions', [
                        'backUrl' => '/erp/sales/sale-list',
                        'formId' => 'orderForm',
                        'submitLabel' => 'Update Sale List',
                    ])
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

@php
    $customerAddressesData = $customers
        ->mapWithKeys(function ($customer) {
            return [
                $customer->id => $customer->addresses
                    ->map(function ($address) {
                        return [
                            'id' => $address->id,
                            'address' => $address->address,
                            'google_maps' => $address->google_maps,
                            'business_name' => $address->business_name,
                        ];
                    })
                    ->values(),
            ];
        })
        ->toArray();

    $customerAccountsData = $customers
        ->mapWithKeys(function ($customer) {
            return [
                $customer->id => $customer->accounts
                    ->map(function ($account) {
                        return [
                            'id' => $account->id,
                            'name' => $account->name,
                            'email' => $account->email,
                            'whatsapp_number' => $account->whatsapp_number,
                        ];
                    })
                    ->values(),
            ];
        })
        ->toArray();
@endphp

@push('scripts')
    @include('erp.pages.sales.partials.discount-engine')
    <script>
        const isOwner = {{ Auth::user()->role === 'Owner' ? 'true' : 'false' }};

        let discountEnabled = {{ $order->discount_active ? 'true' : 'false' }};
        let pendingToggleOff = false;

        const customerAddresses = @json($customerAddressesData);
        const customerAccounts = @json($customerAccountsData);

        {{-- Sudah berupa JSON string dari ErpCatalogPayload (di-cache), jadi dicetak apa adanya --}}
        const products = {!! $productsJson !!};
        const bundles = {!! $productBundlesJson !!};
        // const allProducts = [
        //     ...products.map(p => ({
        //         ...p,
        //         type: 'satuan'
        //     })),
        //     ...bundles.map(b => ({po
        //         ...b,
        //         type: 'bundle'
        //     })),
        // ];

        // const allProducts = products.map(p => ({
        //     ...p,
        //     type: 'satuan'
        // }));

        const allProducts = products.map(p => ({
            ...p,
            type: 'satuan',
            units: p.units || p.product_units || p.unit_conversions || []
        }));

        function truncateText(text, max = 45) {
            if (!text) return '';
            return text.length > max ? text.slice(0, max) + '...' : text;
        }

        function select2ProductConfig() {
            return {
                placeholder: 'Pilih produk',
                width: '100%',

                matcher: function(params, data) {
                    if ($.trim(params.term) === '') return data;
                    if (!data.element) return null;

                    const term = params.term.toLowerCase();
                    const text = data.text.toLowerCase();
                    const sku = $(data.element).data('sku')?.toLowerCase() || '';

                    return text.includes(term) || sku.includes(term) ? data : null;
                },

                templateResult: function(data) {
                    if (!data.element) return data.text;

                    const name = data.text;
                    const sku = $(data.element).data('sku') || '-';

                    return $(`
                <div class="product-option">
                    <div class="product-option-name">${name}</div>
                    <small class="product-option-sku">[${sku}]</small>
                </div>
            `);
                },

                templateSelection: function(data) {
                    if (!data.element) return data.text;

                    return $(`
                        <span>${data.text}</span>
                    `);
                }
            };
        }

        function findSelectedProductData(selectedValue) {
            if (!selectedValue) {
                return null;
            }

            const [type, id] = selectedValue.split('_');

            return allProducts.find(item => {
                return item.type === type && String(item.id) === String(id);
            }) || null;
        }

        function formatNumber(num) {
            return new Intl.NumberFormat('id-ID', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(num || 0);
        }

        function populateProducts(selectEl, selectedId = null, selectedType = null) {
            const select = $(selectEl);

            select.empty().append('<option value="" disabled selected hidden>Pilih produk</option>');

            allProducts.forEach(item => {
                const option = $('<option>', {
                        value: item.type + '_' + item.id,
                        // text: `[${item.sku || '-'}] ${item.name}` + (item.type === 'bundle' ? ' (Bundle)' : '')
                        text: item.name
                    })
                    .data('real-id', item.id)
                    .data('sku', item.sku || '')
                    .data('price', item.price)
                    .data('discounts', item.discounts || [])
                    .data('categories', item.categories || [])
                    .data('type', item.type)
                    .data('units', item.units || [])
                    .data('base-unit-id', item.base_unit_id);

                if (selectedId && selectedType === item.type && String(selectedId) === String(item.id)) {
                    option.prop('selected', true);
                }

                select.append(option);
            });
        }

        // function populateSecondaryBundleProducts(row, primaryProductId, selectedBundleId = null) {
        //     const select = row.find('.bundle-secondary-product');

        //     select.empty().append('<option value="">Pilih product secondary</option>');

        //     bundles.forEach(bundle => {
        //         const primaryItem = bundle.primary_item;

        //         if (!primaryItem) return;

        //         if (String(primaryItem.product_id) !== String(primaryProductId)) {
        //             return;
        //         }

        //         const secondaryItems = bundle.secondary_items || [];

        //         secondaryItems.forEach(item => {
        //             const product = item.product;
        //             if (!product) return;

        //             const option = $(`
    //                 <option value="${product.id}"
    //                     data-bundle-id="${bundle.id}"
    //                     data-sku="${product.sku || '-'}">
    //                     ${product.name}
    //                 </option>
    //             `);

        //             if (selectedBundleId && String(selectedBundleId) === String(bundle.id)) {
        //                 option.prop('selected', true);
        //             }

        //             select.append(option);

        //             if (select.hasClass('select2-hidden-accessible')) {
        //                 select.select2('destroy');
        //             }

        //             select.select2(select2ProductConfig());
        //         });
        //     });
        // }    

        function hasSecondaryBundleProduct(primaryProductId) {
            return bundles.some(bundle => {
                const primaryItem = bundle.primary_item || bundle.items?.find(item => item.role === 'primary');
                if (!primaryItem || String(primaryItem.product_id) !== String(primaryProductId)) return false;

                const secondaryItems = bundle.secondary_items || bundle.items?.filter(item => item.role === 'secondary') || [];
                return secondaryItems.some(item => item.product);
            });
        }

        function populateSecondaryBundleProducts(row, primaryProductId, selectedBundleId = null) {
            const select = row.find('.bundle-secondary-product');

            if (select.hasClass('select2-hidden-accessible')) {
                select.select2('destroy');
            }

            select.empty().append('<option value="">Pilih product secondary</option>');

            bundles.forEach(bundle => {
                const primaryItem =
                    bundle.primary_item ||
                    bundle.items?.find(i => i.role === 'primary');

                if (!primaryItem) return;

                if (String(primaryItem.product_id) !== String(primaryProductId)) {
                    return;
                }

                const secondaryItems =
                    bundle.secondary_items ||
                    bundle.items?.filter(i => i.role === 'secondary') || [];

                secondaryItems.forEach(item => {
                    const product = item.product;
                    if (!product) return;

                    const option = $(`
                <option value="${product.id}"
                    data-bundle-id="${bundle.id}"
                    data-sku="${product.sku || '-'}">
                    ${product.name}
                </option>
            `);

                    if (selectedBundleId && String(selectedBundleId) === String(bundle.id)) {
                        option.prop('selected', true);
                    }

                    select.append(option);
                });
            });

            select.select2(select2ProductConfig());
        }

        function getUnitColor(unitName) {
            const name = (unitName || '').toLowerCase();

            if (name.includes('pcs')) return '#230cf2';
            if (name.includes('dus')) return '#fd7e14';
            if (name.includes('pack')) return '#6f42c1';

            return '#0d6efd';
        }

        function fillProductUnits(row, units, baseUnitId = null) {
            const unitSelect = row.find('.product-unit');

            unitSelect.empty();
            unitSelect.append('<option value="">Pilih unit</option>');

            row.find('.unit-conversion-value').val('');
            row.find('.unit-name').val('');

            if (!Array.isArray(units) || units.length === 0) {
                return;
            }

            units.forEach(function(unit) {
                const id = unit.id || unit.product_unit_conversion_id || unit.product_bundle_unit_conversion_id;
                const unitId = unit.unit_id || unit.product_unit_id || '';
                const unitName =
                    unit.unit_name ||
                    unit.name ||
                    unit.unit?.name ||
                    unit.product_unit?.name ||
                    unit.productUnit?.name ||
                    'PCS';

                const conversionValue =
                    unit.conversion_value ||
                    unit.value ||
                    unit.conversion ||
                    1;

                const salePrice =
                    unit.sale_price ||
                    unit.price ||
                    unit.selling_price ||
                    0;

                const unitColor = getUnitColor(unitName);

                const dynamicPrices = encodeURIComponent(JSON.stringify(unit.prices || []));
                unitSelect.append(`
                    <option value="${id}"
                        data-unit-id="${unitId}"
                        data-unit-name="${unitName}"
                        data-conversion-value="${conversionValue}"
                        data-sale-price="${salePrice}"
                        data-prices="${dynamicPrices}"
                        data-color="${unitColor}">
                        ${unitName}
                    </option>
                `);
            });

            const baseOption = unitSelect.find(`option[data-unit-id="${baseUnitId}"]`).val();
            const firstOption = unitSelect.find('option:eq(1)').val();

            unitSelect.val(baseOption || firstOption).trigger('change');

            // let baseOption = null;

            // unitSelect.find('option').each(function() {
            //     if (String($(this).data('unit-id')) === String(baseUnitId)) {
            //         baseOption = $(this).val();
            //         return false;
            //     }
            // });

            // const firstOption = unitSelect.find('option:eq(1)').val();

            // unitSelect.val(baseOption || firstOption).trigger('change');
        }

        function getModePrice(row, selectedUnit) {
            const fallback = parseFloat(selectedUnit.data('sale-price') || 0);
            const encoded = selectedUnit.attr('data-prices') || '';
            if (!encoded) return fallback;

            try {
                const prices = JSON.parse(decodeURIComponent(encoded));
                const mode = row.find('.item-mode').val();
                const selected = prices.find(price => price.mode === mode);
                return selected ? parseFloat(selected.sale_price || 0) : fallback;
            } catch (error) {
                console.warn('Dynamic price tidak dapat dibaca', error);
                return fallback;
            }
        }

        function updatePriceFromSelectedUnit(row, replacePrice = true) {
            const selectedUnit = row.find('.product-unit option:selected');

            if (!selectedUnit.val()) {
                return;
            }

            const unitName = selectedUnit.data('unit-name') || 'Pcs';
            const conversionValue = selectedUnit.data('conversion-value') || 1;
            const salePrice = getModePrice(row, selectedUnit);

            const unitColor = selectedUnit.data('color') || getUnitColor(unitName);

            row.find('.unit-name').val(unitName);
            row.find('.unit-conversion-value').val(conversionValue);

            if (replacePrice) {
                row.find('.price_before_discount').val(salePrice.toFixed(2));
                row.find('.price_before_discount_display').val(formatNumber(salePrice));
            }

            row.find('.product-unit').css({
                color: unitColor,
                fontWeight: '600'
            });

            recalcAllRows();
        }

        function calculateRow(row) {
            const selectedOption = row.find('select[name="product[]"] option:selected');

            let manualPriceRaw = row.find('input.price_before_discount').val();

            let manualPrice = (manualPriceRaw === '' || manualPriceRaw === '0') ?
                null :
                parseFloat(manualPriceRaw);

            let basePrice = manualPrice !== null ?
                manualPrice :
                (parseFloat(selectedOption.data('price')) || 0);

            const qty = parseFloat(row.find('input[name="qty[]"]').val().replace(/\./g, '')) || 0;

            const priceBeforeDiscount = basePrice;
            const totalBeforeDiscount = basePrice * qty;

            const finalPrice = (discountEnabled && priceBeforeDiscount > 0) ?
                DiscountEngine.priceAfterDiscount(row, priceBeforeDiscount) :
                priceBeforeDiscount;

            const totalAfterDiscount = finalPrice * qty;

            row.find('input.price_before_discount').val(priceBeforeDiscount.toFixed(2));
            row.find('input.total_before_discount').val(totalBeforeDiscount.toFixed(2));
            row.find('input.price_after_discount').val(finalPrice.toFixed(2));
            row.find('input.total_after_discount').val(totalAfterDiscount.toFixed(2));

            if (!row.find('.price_before_discount_display').is(':focus')) {
                row.find('.price_before_discount_display').val(formatNumber(priceBeforeDiscount));
            }

            row.find('.total_before_discount_display').val(formatNumber(totalBeforeDiscount));
        }

        function recalcAllRows() {
            $('.product-item').each(function() {
                calculateRow($(this));
            });

            calcTotalSummary();
        }

        function calcTotalSummary() {
            let subTotal = 0;
            let totalAfterDiscount = 0;

            $('.total_before_discount').each(function() {
                subTotal += parseFloat($(this).val()) || 0;
            });

            $('.total_after_discount').each(function() {
                totalAfterDiscount += parseFloat($(this).val()) || 0;
            });

            const totalDiscount = subTotal - totalAfterDiscount;

            $('#sub_total').val(subTotal.toFixed(2));
            $('#total_discount').val(totalDiscount.toFixed(2));
            $('#total_amount').val(totalAfterDiscount.toFixed(2));

            $('#sub_total_display').val(formatNumber(subTotal));
            $('#total_discount_display').val(formatNumber(totalDiscount));
            $('#total_amount_display').val(formatNumber(totalAfterDiscount));
        }

        function showError(element, message) {
            $(element).next('.invalid-feedback').remove();
            $(element).after(`<div class="invalid-feedback">${message}</div>`);
            $(element).addClass('is-invalid');
        }

        function updateAddresses(customerId) {
            const addresses = customerAddresses[customerId] || [];
            const addressSelect = $('#addresses');
            const selectedAddressId = "{{ $order->customer_address_id ?? '' }}";

            addressSelect.empty().append('<option disabled hidden>Choose Address</option>');

            addresses.forEach(function(address) {
                const isSelected = String(address.id) === String(selectedAddressId);

                addressSelect.append(`
                <option value="${address.id}"
                    data-map="${address.google_maps || ''}"
                    ${isSelected ? 'selected' : ''}>
                    ${address.business_name ?? 'None'} - ${address.address}
                </option>
            `);
            });

            updateGoogleMapsLink();
        }

        function updateCustomerAccounts(customerId) {
            const accounts = customerAccounts[customerId] || [];
            const accountSelect = $('#customer_accounts');
            const selectedAccountId = "{{ $order->customer_account_id ?? '' }}";

            accountSelect.empty().append('<option disabled hidden>Choose Contact</option>');

            accounts.forEach(function(account) {
                const isSelected = String(account.id) === String(selectedAccountId);

                accountSelect.append(`
            <option value="${account.id}" ${isSelected ? 'selected' : ''}>
                ${account.name ?? '-'} - ${account.whatsapp_number ?? account.email ?? '-'}
            </option>
        `);
            });
        }

        function updateGoogleMapsLink() {
            const selectedOption = $('#addresses').find('option:selected');
            const mapUrl = selectedOption.data('map');

            if (mapUrl) {
                $('#google-maps-link').html(`
                <a href="${mapUrl}" target="_blank" class="btn btn-sm btn-outline-primary mt-1">
                    Lihat di Google Maps
                </a>
            `);
            } else {
                $('#google-maps-link').empty();
            }
        }

        function initDueDate() {
            const optionEl = document.getElementById('due_date_option');
            const dateInput = document.getElementById('custom_due_date');

            if (!optionEl || !dateInput) {
                return;
            }

            const savedDate = dateInput.value ? new Date(dateInput.value) : null;
            const today = new Date();
            let defaultOption = 'none';

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
                    dateInput.readOnly = false;
                    return;
                } else {
                    dateInput.readOnly = true;
                    dateInput.value = '';
                    return;
                }

                const yyyy = newDate.getFullYear();
                const mm = String(newDate.getMonth() + 1).padStart(2, '0');
                const dd = String(newDate.getDate()).padStart(2, '0');

                dateInput.value = `${yyyy}-${mm}-${dd}`;
            }

            optionEl.addEventListener('change', updateDueDate);

            updateDueDate();
        }

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
                const row = $(this);
                const select = row.find('.select-product');

                const selectedVal = select.val();
                let selectedType = null;
                let selectedId = null;

                if (selectedVal) {
                    [selectedType, selectedId] = selectedVal.split('_');
                }

                const selectedUnitId = row.find('.product-unit').val();

                populateProducts(select[0], selectedId, selectedType);

                select.select2(select2ProductConfig());

                const isBundleChecked = row.find('.add-bundle-check').is(':checked');

                if (isBundleChecked) {
                    const selectedBundleId = row.find('.bundle-secondary-product').data(
                        'selected-bundle-id');
                    const bundle = bundles.find(b => String(b.id) === String(selectedBundleId));

                    if (bundle && bundle.primary_item) {
                        const primaryProductId = bundle.primary_item.product_id;

                        populateProducts(select[0], primaryProductId, 'satuan');

                        select.val('satuan_' + primaryProductId).trigger('change.select2');

                        row.find('.bundle-wrapper').removeClass('d-none');
                        populateSecondaryBundleProducts(row, primaryProductId, selectedBundleId);

                        row.find('.product-type').val('bundle');

                        // fillProductUnits(
                        //     row,
                        //     bundle.units || [],
                        //     selectedUnitId,
                        //     parseFloat(bundle.price || 0)
                        // );
                        updatePriceFromSelectedUnit(row, false);
                    }
                } else {
                    const selectedData = findSelectedProductData(select.val());

                    const type = selectedData ? selectedData.type : selectedType;
                    const units = selectedData ? (selectedData.units || []) : [];
                    const defaultPrice = selectedData ? parseFloat(selectedData.price || 0) : 0;

                    row.find('.product-type').val(type);

                    if (type === 'satuan') {
                        // fillProductUnits(row, units, selectedUnitId, defaultPrice, false);
                        updatePriceFromSelectedUnit(row, false);
                    } else {
                        row.find('.product-unit-wrapper').hide();
                        row.find('.product-unit').val('');
                        row.find('.unit-conversion-value').val('1');
                        row.find('.unit-name').val('');
                    }
                }
            });

            const initialCustomerId = $('#customers').val();

            if (initialCustomerId) {
                updateAddresses(initialCustomerId);
                updateCustomerAccounts(initialCustomerId);
            }

            initDueDate();
            recalcAllRows();
        });

        // $(document).on('change', '.select-product', function() {
        //     const row = $(this).closest('.product-item');

        //     if (row.find('.add-bundle-check').is(':checked')) {
        //         const selectedVal = $(this).val();

        //         if (selectedVal) {
        //             const primaryProductId = selectedVal.split('_')[1];

        //             row.find('.bundle-wrapper').removeClass('d-none');
        //             populateSecondaryBundleProducts(row, primaryProductId);
        //             row.find('.product-type').val('bundle');
        //         }

        //         return;
        //     }

        //     const selectedData = findSelectedProductData($(this).val());

        //     const type = selectedData ? selectedData.type : '';
        //     const price = selectedData ? parseFloat(selectedData.price || 0) : 0;
        //     const units = selectedData ? (selectedData.units || []) : [];

        //     row.find('.product-type').val(type);

        //     if (type === 'satuan') {
        //         fillProductUnits(row, units, null, price, false);
        //     } else {
        //         row.find('.product-unit-wrapper').hide();
        //         row.find('.product-unit').val('');
        //         row.find('.unit-conversion-value').val('1');
        //         row.find('.unit-name').val('');

        //         row.find('.price_before_discount').val(price.toFixed(2));
        //         row.find('.price_before_discount_display').val(formatNumber(price));

        //         recalcAllRows();
        //     }
        // });

        // $(document).on('change', '.select-product', function() {
        //     const row = $(this).closest('.product-item');

        //     const selectedData = findSelectedProductData($(this).val());
        //     if (!selectedData) return;

        //     row.find('.product-type').val(
        //         row.find('.add-bundle-check').is(':checked') ? 'bundle' : 'satuan'
        //     );

        //     fillProductUnits(
        //         row,
        //         selectedData.units || [],
        //         selectedData.base_unit_id
        //     );

        //     if (row.find('.add-bundle-check').is(':checked')) {
        //         const primaryProductId = $(this).val().split('_')[1];
        //         row.find('.bundle-wrapper').removeClass('d-none');
        //         populateSecondaryBundleProducts(row, primaryProductId);
        //     } else {
        //         row.find('.bundle-wrapper').addClass('d-none');
        //         row.find('.bundle-secondary-product').val('');
        //     }

        //     recalcAllRows();
        // });

        $(document).on('change select2:select', 'select[name="product[]"]', function() {
            const row = $(this).closest('.product-item');
            const selectedOption = $(this).find('option:selected');

            const type = selectedOption.data('type') || '';
            const selectedId = String(selectedOption.data('real-id') || '');

            row.find('.product-type').val(type);

            const selectedItem = allProducts.find(item => {
                return item.type === type && String(item.id) === selectedId;
            });

            let units = selectedItem?.units || [];

            const defaultSaleUnitId = selectedItem?.sale_unit_id || selectedItem?.base_unit_id;
            fillProductUnits(row, units, defaultSaleUnitId);

            row.find('.add-bundle-check').prop('checked', false);
            row.find('.bundle-wrapper').addClass('d-none');
            row.find('.bundle-secondary-product').empty().append('<option value="">Pilih product bundle</option>');

            recalcAllRows();
        });

        $(document).on('change', '.product-unit', function() {
            const row = $(this).closest('.product-item');
            updatePriceFromSelectedUnit(row);
        });

        $(document).on('input', '.qty', function() {
            let rawValue = $(this).val().replace(/\D/g, '');
            $(this).val(new Intl.NumberFormat('id-ID').format(rawValue));
            recalcAllRows();
        });

        let priceInputTimeout;

        $(document).on('input', '.price_before_discount_display', function() {
            const row = $(this).closest('.product-item');

            let rawValue = $(this).val().replace(/\D/g, '');

            if (rawValue.length > 12) {
                rawValue = rawValue.substring(0, 12);
            }

            $(this).val(new Intl.NumberFormat('id-ID').format(rawValue));

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

        $(document).on('click', '#add_row', function() {
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
            $(item).find('.select-product').select2(select2ProductConfig());

            recalcAllRows();
        });

        $(document).on('click', '.delete-row', function() {
            $(this).closest('.product-item').remove();

            $('.product-item').each(function(i) {
                $(this).attr('data-index', i);
                $(this).find('.product-number').text('#' + (i + 1));
            });

            recalcAllRows();
        });

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

            if (pendingToggleOff) {
                $('#confirmResponsibilityModal').modal('show');
            }
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

        $('#customers').on('change', function() {
            const customerId = $(this).val();

            updateAddresses(customerId);
            updateCustomerAccounts(customerId);
        });

        $('#addresses').on('change', function() {
            updateGoogleMapsLink();
        });

        $(document).on('change input',
            "#customers, #customer_accounts, #addresses, #edit_note, select[name='mode[]'], select[name='product[]'], select[name='product_unit_id[]'], input[name='qty[]']",
            function() {
                $(this).removeClass('is-invalid');
                $(this).next('.invalid-feedback').remove();
            }
        );

        $(document).on('change', '.add-bundle-check', function() {
            const row = $(this).closest('.product-item');
            const productSelect = row.find('select[name="product[]"]');
            const selectedVal = productSelect.val();

            if (this.checked) {
                if (!selectedVal) {
                    $(this).prop('checked', false);
                    showError(productSelect, 'Pilih product dulu');
                    return;
                }

                const primaryProductId = selectedVal.split('_')[1];

                if (!hasSecondaryBundleProduct(primaryProductId)) {
                    const productName = productSelect.find('option:selected').text().trim();
                    $(this).prop('checked', false);
                    row.find('.product-type').val('satuan');
                    row.find('.bundle-wrapper').addClass('d-none');
                    row.find('.bundle-secondary-product').empty().append('<option value="">Pilih product bundle</option>');

                    Swal.fire({
                        icon: 'warning',
                        title: 'Bundle tidak tersedia',
                        text: `${productName} tidak memiliki bundle.`
                    });
                    return;
                }

                row.find('.product-type').val('bundle');
                row.find('.bundle-wrapper').removeClass('d-none');

                populateSecondaryBundleProducts(row, primaryProductId);

                const selectedData = findSelectedProductData(selectedVal);
                if (selectedData) {
                    const defaultSaleUnitId = selectedData.sale_unit_id || selectedData.base_unit_id;
                    fillProductUnits(row, selectedData.units || [], defaultSaleUnitId);
                }
            } else {
                row.find('.product-type').val('satuan');
                row.find('.bundle-wrapper').addClass('d-none');
                row.find('.bundle-secondary-product').val('');

                const selectedData = findSelectedProductData(selectedVal);
                if (selectedData) {
                    const defaultSaleUnitId = selectedData.sale_unit_id || selectedData.base_unit_id;
                    fillProductUnits(row, selectedData.units || [], defaultSaleUnitId);
                }
            }

            recalcAllRows();
        });

        // $(document).on('change', '.bundle-secondary-product', function() {
        //     const row = $(this).closest('.product-item');
        //     const bundleId = $(this).find('option:selected').data('bundle-id');

        //     const bundle = bundles.find(b => String(b.id) === String(bundleId));
        //     if (!bundle) return;

        //     row.find('.product-type').val('bundle');

        //     fillProductUnits(row, bundle.units || [], null, parseFloat(bundle.price || 0));

        //     recalcAllRows();
        // });

        $(document).on('change', '.bundle-secondary-product', function() {
            const row = $(this).closest('.product-item');
            const bundleId = $(this).find('option:selected').data('bundle-id');

            const bundle = bundles.find(b => String(b.id) === String(bundleId));
            if (!bundle) return;

            row.find('.product-type').val('bundle');

            fillProductUnits(row, bundle.units || [], bundle.base_unit_id);

            recalcAllRows();
        });

        $('#orderForm').on('submit', function(e) {
            let valid = true;

            if (!$('#customers').val()) {
                showError($('#customers'), 'Business wajib dipilih');
                valid = false;
            }

            if (!$('#customer_accounts').val()) {
                showError($('#customer_accounts'), 'Contact wajib dipilih');
                valid = false;
            }

            if (!$('#addresses').val()) {
                showError($('#addresses'), 'Address wajib dipilih');
                valid = false;
            }

            if (!$('#edit_note').val()) {
                showError($('#edit_note'), 'Catatan edit wajib diisi');
                valid = false;
            }

            $('.product-item').each(function() {
                const product = $(this).find('select[name="product[]"]');
                const mode = $(this).find('select[name="mode[]"]');
                const qty = $(this).find('input[name="qty[]"]');
                const type = $(this).find('.product-type').val();
                const unit = $(this).find('select[name="product_unit_id[]"]');

                const cleanQty = qty.val().replace(/[^\d]/g, '');

                if (!product.val()) {
                    showError(product, 'Produk wajib dipilih');
                    valid = false;
                }

                if ((type === 'satuan' || type === 'bundle') && !unit.val()) {
                    showError(unit, 'Unit wajib dipilih');
                    valid = false;
                }

                if (!mode.val()) {
                    showError(mode, 'Mode wajib dipilih');
                    valid = false;
                }

                if (!cleanQty || parseInt(cleanQty) < 1) {
                    showError(qty, 'Qty minimal 1');
                    valid = false;
                }
            });

            if (!valid) {
                e.preventDefault();
                return false;
            }

            $('input[name="qty[]"]').each(function() {
                $(this).val($(this).val().replace(/\./g, ''));
            });

            $('input[name="unit_conversion_value[]"]').each(function() {
                const raw = $(this).val().toString().replace(',', '.');
                $(this).val(raw || '1');
            });

            $('input.price_before_discount').each(function() {
                if ($(this).val() === '' || isNaN($(this).val())) {
                    $(this).val(0);
                }
            });

            $('select[name="product[]"]').each(function() {
                const row = $(this).closest('.product-item');
                const isBundle = row.find('.add-bundle-check').is(':checked');

                let finalProductId = null;

                if (isBundle) {
                    finalProductId = row.find('.bundle-secondary-product option:selected').data(
                        'bundle-id');
                    row.find('.product-type').val('bundle');
                } else {
                    finalProductId = $(this).val()?.split('_')[1];
                    row.find('.product-type').val('satuan');
                }

                if (finalProductId) {
                    const finalType = row.find('.product-type').val();

                    $('<input>')
                        .attr('type', 'hidden')
                        .attr('name', 'product[]')
                        .val(finalType + '_' + finalProductId)
                        .appendTo('#orderForm');

                    $(this).prop('disabled', true);
                }
            });

            return true;
        });

        $(document).on('select2:open', () => {
            setTimeout(() => {
                document.querySelector('.select2-container--open .select2-search__field')?.focus();
            }, 50);
        });

        $(document).on('change', '.item-mode', function() {
            $(this).css({
                color: this.value === 'printing' ? '#198754' : '#dc3545',
                fontWeight: '600'
            });

            const row = $(this).closest('.product-item');
            if ($(this).data('mode-price-ready')) {
                updatePriceFromSelectedUnit(row);
            } else {
                $(this).data('mode-price-ready', true);
                recalcAllRows();
            }
        });

        $('.item-mode').trigger('change');
    </script>
@endpush
