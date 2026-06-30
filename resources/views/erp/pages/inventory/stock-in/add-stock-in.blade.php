@extends('erp.layouts.main')

@push('styles')
    <style>
        /* default mobile hidden */
        .stockin-mobile-wrapper {
            display: none;
        }

        @media (max-width: 991px) {

            .stockin-desktop-table {
                display: none;
            }

            .stockin-mobile-wrapper {
                display: block;
            }

            .stockin-mobile-card {
                border-radius: 12px;
                padding: 16px;
                margin-bottom: 14px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, .06);
            }

            .stockin-mobile-card h6 {
                font-size: 14px;
                font-weight: 600;
                margin-bottom: 8px;
            }

            .stockin-mobile-label {
                font-size: 12px;
                margin-bottom: 4px;
            }

            .stockin-mobile-value {
                font-size: 13px;
                margin-bottom: 10px;
            }

            .stockin-mobile-card .form-control-sm {
                padding: 4px 8px;
                font-size: 12px;
            }
        }
    </style>
@endpush

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Stock In</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Warehouse</li>
                <li class="breadcrumb-item">Add Stock In</li>
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
                    <a href="/erp/inventory/stock-in" class="btn btn-light-brand">
                        <i class="feather-arrow-left me-2"></i>
                        <span>Back</span>
                    </a>
                    <button type="submit" class="btn btn-primary" form="stockInForm">
                        <i class="feather-plus me-2"></i>
                        <span>Add Stock In</span>
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
                {{-- <form action="/erp/inventory/stock-in/store/{{ $stockIn->id }}" method="POST" id="stockInForm" --}}
                <form action="/erp/inventory/stock-in/store/{{ $supplierId }}/{{ $year }}/{{ $month }}"
                    method="POST" id="stockInForm" enctype="multipart/form-data">
                    @csrf
                    @method('POST')
                    <div class="card">
                        <div class="card-header">
                            {{-- <h4 class="card-title">
                                Invoice Number :
                                <span>
                                    @if ($stockIn->note === 'Sale Returns')
                                        {{ $stockIn->order_number }}
                                    @elseif($stockIn->note === 'Purchase Account')
                                        {{ $stockIn->purchase_number }}
                                    @else
                                        -
                                    @endif
                                </span>
                            </h4> --}}
                            <h4 class="card-title">
                                {{ $supplier->name }} — {{ $monthLabel }}
                            </h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-12">
                                    <div class="row mb-3 align-items-center">
                                        <div class="col-lg-2">
                                            <label for="invoice_number" class="fw-semibold">Invoice Number:</label>
                                        </div>
                                        <div class="col-lg-10 mb-0">
                                            <div class="input-group">
                                                {{-- <input type="hidden" name="inventory_id" value="{{ $stockIn->id }}">
                                                <input type="text" class="form-control" id="invoice_number"
                                                    name="invoice_number"
                                                    value="{{ $stockIn->note === 'Sale Returns' ? $stockIn->order_number : $stockIn->purchase_number }}"
                                                    readonly> --}}

                                                <input type="text" class="form-control" value="{{ $invoiceNumbers }}"
                                                    readonly>

                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mb-3 align-items-center">
                                        <div class="col-lg-2">
                                            <label for="change_date" class="fw-semibold">Change Date:</label>
                                        </div>
                                        <div class="col-lg-10 mb-0">
                                            <div class="input-group">
                                                <input type="date" class="form-control" id="change_date"
                                                    name="change_date" value="{{ now()->format('Y-m-d') }}" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mb-3 align-items-center">
                                        <div class="col-lg-2">
                                            <label for="waybill_number" class="fw-semibold">Waybill Number:</label>
                                        </div>
                                        <div class="col-lg-10 mb-0">
                                            <div class="input-group">
                                                {{-- <input type="text" class="form-control" id="waybill_number"
                                                    name="waybill_number" value="{{ $stockIn->waybill_number }}"> --}}
                                                <input type="text" class="form-control" id="waybill_number"
                                                    name="waybill_number" value="">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mb-3 align-items-center">
                                        <div class="col-lg-2">
                                            <label for="waybill_image" class="fw-semibold">Waybill Image</label>
                                        </div>
                                        <div class="col-lg-10 mb-0">
                                            <div class="input-group">
                                                <input type="file" class="form-control" id="waybill_image"
                                                    name="waybill_image" accept="image/*" capture="environment"
                                                    value="{{ old('waybill_image') }}">
                                            </div>
                                            <img id="preview-image" src="#" alt="Preview"
                                                style="display:none; max-width: 100px; margin-top: 10px; border-radius: 10px" />
                                        </div>
                                    </div>
                                    {{-- <div class="row mb-3 align-items-center">
                                        <div class="col-lg-2">
                                            <label for="notes" class="fw-semibold">Note:</label>
                                        </div>
                                        <div class="col-lg-10 mb-0">
                                            <div class="input-group">
                                                <textarea class="form-control" id="notes" name="notes" placeholder="Catatan"></textarea>
                                            </div>
                                        </div>
                                    </div> --}}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Add Stock In</h4>
                        </div>

                        {{-- DESKTOP --}}
                        <div class="stockin-desktop-table">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Total Qty</th>
                                        <th>Total Stock In</th>
                                        <th>Remaining</th>
                                        <th>Add Stock In</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($mergedItems as $index => $item)
                                        @php
                                            $conv = $item->unit_conversion_value ?? 1;
                                            $unit = $item->unit_name ?? 'Pcs';
                                        @endphp
                                        <tr>
                                            <td class="fw-semibold">{{ $item->product->name ?? '-' }}</td>
                                            <td>
                                                {{ number_format($item->quantity, 0, ',', '.') }} {{ $unit }}
                                                @if ($conv > 1)
                                                    <small
                                                        class="text-muted">({{ number_format($item->qty_base, 0, ',', '.') }}
                                                        Pcs)</small>
                                                @endif
                                            </td>
                                            <td class="text-success">
                                                {{ number_format($item->stock_in / $conv, 0, ',', '.') }}
                                                {{ $unit }}
                                                @if ($conv > 1)
                                                    <small
                                                        class="text-muted">({{ number_format($item->stock_in, 0, ',', '.') }}
                                                        Pcs)</small>
                                                @endif
                                            </td>
                                            <td class="text-danger">
                                                {{ number_format($item->remaining / $conv, 0, ',', '.') }}
                                                {{ $unit }}
                                                @if ($conv > 1)
                                                    <small
                                                        class="text-muted">({{ number_format($item->remaining, 0, ',', '.') }}
                                                        Pcs)</small>
                                                @endif
                                            </td>
                                            <td>
                                                @foreach ($item->item_ids as $itemId)
                                                    <input type="hidden"
                                                        name="items[{{ $index }}][inventory_item_ids][]"
                                                        value="{{ $itemId }}">
                                                @endforeach
                                                <input type="hidden" name="items[{{ $index }}][product_id]"
                                                    value="{{ $item->product_id }}">
                                                <input type="hidden"
                                                    name="items[{{ $index }}][unit_conversion_value]"
                                                    value="{{ $conv }}">
                                                <input type="text" inputmode="numeric"
                                                    name="items[{{ $index }}][stock_in]"
                                                    class="form-control stock-in-input" value="0"
                                                    data-max="{{ $item->remaining / $conv }}"
                                                    placeholder="Jumlah ({{ $unit }})">
                                                <small class="text-muted">
                                                    Sisa: {{ number_format($item->remaining / $conv, 0, ',', '.') }}
                                                    {{ $unit }}
                                                    @if ($conv > 1)
                                                        = {{ number_format($item->remaining, 0, ',', '.') }} Pcs
                                                    @endif
                                                </small>
                                            </td>
                                            <td>
                                                <input type="text" name="items[{{ $index }}][notes]"
                                                    class="form-control" placeholder="Catatan">
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- MOBILE --}}
                        <div class="stockin-mobile-wrapper">
                            @foreach ($mergedItems as $index => $item)
                                <div class="stockin-mobile-card">
                                    <h5>{{ $item->product->name ?? '-' }}</h5>

                                    <div class="stockin-mobile-label">Total Quantity</div>
                                    <div class="stockin-mobile-value">{{ number_format($item->quantity, 0, ',', '.') }}
                                    </div>

                                    <div class="stockin-mobile-label">Already Stock In</div>
                                    <div class="stockin-mobile-value text-success">
                                        {{ number_format($item->stock_in, 0, ',', '.') }}
                                    </div>

                                    <div class="stockin-mobile-label">Remaining</div>
                                    <div class="stockin-mobile-value text-danger">
                                        {{ number_format($item->remaining, 0, ',', '.') }}
                                    </div>

                                    <div class="stockin-mobile-label">Add Stock In</div>
                                    @foreach ($item->item_ids as $itemId)
                                        <input type="hidden" name="items[{{ $index }}][inventory_item_ids][]"
                                            value="{{ $itemId }}">
                                    @endforeach
                                    <input type="hidden" name="items[{{ $index }}][product_id]"
                                        value="{{ $item->product_id }}">
                                    <input type="text" inputmode="numeric"
                                        name="items[{{ $index }}][stock_in]"
                                        class="form-control form-control-sm mb-2 stock-in-input" value="0"
                                        data-max="{{ $item->remaining }}">
                                    <small class="text-muted d-block mb-2">
                                        Sisa: {{ number_format($item->remaining, 0, ',', '.') }}
                                    </small>

                                    <div class="stockin-mobile-label">Notes</div>
                                    <input type="text" name="items[{{ $index }}][notes]"
                                        class="form-control form-control-sm mb-2">
                                </div>
                            @endforeach
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {

            $('#waybill_image').on('change', function() {
                const input = this;
                const file = input.files[0];

                if (!file || !file.type.startsWith('image/')) return;

                const img = new Image();
                const reader = new FileReader();

                reader.onload = function(e) {
                    img.onload = function() {
                        const targetRatio = 16 / 9;
                        const imgRatio = img.width / img.height;

                        let cropWidth = img.width;
                        let cropHeight = img.height;
                        let cropX = 0;
                        let cropY = 0;

                        if (imgRatio > targetRatio) {
                            cropWidth = img.height * targetRatio;
                            cropX = (img.width - cropWidth) / 2;
                        } else {
                            cropHeight = img.width / targetRatio;
                            cropY = (img.height - cropHeight) / 2;
                        }

                        const canvas = document.createElement('canvas');
                        canvas.width = 1280;
                        canvas.height = 720;

                        const ctx = canvas.getContext('2d');

                        ctx.drawImage(
                            img,
                            cropX,
                            cropY,
                            cropWidth,
                            cropHeight,
                            0,
                            0,
                            canvas.width,
                            canvas.height
                        );

                        canvas.toBlob(function(blob) {
                            const newFile = new File([blob], file.name.replace(/\.[^/.]+$/,
                                '') + '.jpg', {
                                type: 'image/jpeg',
                                lastModified: Date.now()
                            });

                            const dataTransfer = new DataTransfer();
                            dataTransfer.items.add(newFile);
                            input.files = dataTransfer.files;

                            $('#preview-image')
                                .attr('src', URL.createObjectURL(newFile))
                                .show();

                        }, 'image/jpeg', 0.9);
                    };

                    img.src = e.target.result;
                };

                reader.readAsDataURL(file);
            });

            $(document).on('focus', 'input[name^="items"][name$="[stock_in]"]', function() {
                if ($(this).val() === '0') $(this).val('');
            });

            $(document).on('blur', 'input[name^="items"][name$="[stock_in]"]', function() {
                if ($(this).val().trim() === '') $(this).val('0');
            });

            $(document).on('input', 'input[name^="items"][name$="[stock_in]"]', function() {
                const input = $(this);
                const raw = input.val().replace(/\./g, '');
                if (raw === '') return;

                let value = parseInt(raw) || 0;
                const max = parseInt(input.data('max')) || 999999;

                if (value > max) {
                    value = max;
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'warning',
                        title: 'Jumlah tidak boleh melebihi total sisa (' + max.toLocaleString(
                            'id-ID') + ')',
                        showConfirmButton: false,
                        timer: 1500
                    });
                }

                input.val(value.toLocaleString('id-ID'));
            });

            // 🔥 INI YANG PENTING! PAKE submitHandler BUKAN on('submit')!
            let formSubmitting = false;

            $('#stockInForm').on('submit', function(e) {
                if (formSubmitting) return true;

                e.preventDefault();

                let isValid = true;

                $(this).find('.is-invalid').removeClass('is-invalid');
                $(this).find('.invalid-feedback').remove();

                function showError(element, message) {
                    $(element).addClass('is-invalid');
                    $(element).after(
                        '<div class="invalid-feedback d-block text-danger fw-semibold small">' +
                        message + '</div>');
                }

                // 🔥 DISABLE INPUT YANG HIDDEN (MOBILE ATAU DESKTOP)
                $('.stockin-desktop-table:hidden .stock-in-input').prop('disabled', true);
                $('.stockin-mobile-wrapper:hidden .stock-in-input').prop('disabled', true);

                // 🔥 HAPUS TITIK DARI INPUT YANG VISIBLE AJA
                $('.stock-in-input:not(:disabled)').each(function() {
                    let rawValue = $(this).val().replace(/\./g, '').trim();
                    if (rawValue === '') rawValue = '0';
                    $(this).val(rawValue);
                });

                // Validasi input yang visible aja
                $('.stock-in-input:not(:disabled)').each(function() {
                    const val = $(this).val().trim();
                    const num = parseInt(val) || 0;
                    if (val === '' || isNaN(num) || num < 0) {
                        isValid = false;
                        showError(this, 'Tidak boleh kosong atau negatif');
                    }
                });

                // const waybillNumber = $('#waybill_number');
                // if (!waybillNumber.val().trim()) {
                //     isValid = false;
                //     showError(waybillNumber[0], 'Waybill number wajib diisi');
                // }

                const waybillImage = $('#waybill_image');

                if (!waybillImage[0].files.length) {
                    isValid = false;
                    showError(waybillImage[0], 'Waybill image wajib diupload');
                }

                if (!isValid) {
                    // Enable balik sebelum scroll
                    $('.stock-in-input').prop('disabled', false);
                    $('html, body').animate({
                        scrollTop: $('.is-invalid:first').offset().top - 100
                    }, 500);
                } else {
                    formSubmitting = true;
                    $(this).submit();
                }
            });
        });
    </script>
@endpush
