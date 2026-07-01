@extends('erp.layouts.main')

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Delivery Order</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Deliveries</li>
                <li class="breadcrumb-item">Delivery Order</li>
                <li class="breadcrumb-item active">Edit Delivery Order</li>
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
                    <a href="/erp/deliveries/delivery-list" class="btn btn-light-brand">
                        <i class="feather-arrow-left me-2"></i>
                        <span>Back</span>
                    </a>
                    <button type="submit" class="btn btn-primary" form="deliveryListForm">
                        <i class="feather-save me-2"></i>
                        <span>Update Delivery List</span>
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
                <form action="/erp/deliveries/delivery-list/update/{{ $deliveryList->id }}" method="POST"
                    id="deliveryListForm">
                    @csrf
                    @method('PUT')
                    <div class="card stretch stretch-full">
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="fw-semibold">Shipment Number</label>
                                    <input type="text" class="form-control" name="shipment_number"
                                        value="{{ $deliveryList->shipment_number }}" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="fw-semibold">Shipment Date</label>
                                    <input type="date" class="form-control" name="shipment_date"
                                        value="{{ $deliveryList->shipment_date }}" required>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="fw-semibold">Driver</label>
                                    <select name="driver_id" id="driver_id" class="form-control">
                                        <option value="">-- Pilih Driver --</option>
                                        @foreach ($drivers as $driver)
                                            <option value="{{ $driver->id }}"
                                                {{ $deliveryList->driver_id == $driver->id ? 'selected' : '' }}>
                                                {{ $driver->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="fw-semibold">Vehicle</label>
                                    <input type="text" class="form-control" name="vehicle"
                                        value="{{ $deliveryList->vehicle }}">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-12">
                                    <label class="fw-semibold">Note</label>
                                    <textarea class="form-control" name="note" rows="2" placeholder="Optional note...">{{ $deliveryList->note }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card stretch stretch-full">
                        <div class="card-body">
                            <h5 class="fw-bold mb-3">Delivery Items</h5>
                            <div class="table-responsive">
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>Total Qty</th>
                                                <th>Delivered</th>
                                                <th>Shipping</th>
                                                <th>Available</th>
                                                <th>Shipped Qty (Now)</th>
                                                <th>Note</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($deliveryList->deliveryOrder->items as $item)
                                                @php
                                                    $unitConversionValue = (float) ($item->unit_conversion_value ?? 1);

                                                    if ($unitConversionValue <= 0) {
                                                        $unitConversionValue = 1;
                                                    }

                                                    $readyBase = $item->ready_qty ?? 0;

                                                    $deliveredBase = $item
                                                        ->deliveryListItems()
                                                        ->whereHas(
                                                            'shipment',
                                                            fn($q) => $q->where('status', 'Finished'),
                                                        )
                                                        ->sum('shipped_quantity');

                                                    $shippingBase = $item
                                                        ->deliveryListItems()
                                                        ->whereHas('shipment', fn($q) => $q->where('status', 'Ongoing'))
                                                        ->sum('shipped_quantity');

                                                    $dlItem = $deliveryList->items->firstWhere(
                                                        'delivery_order_item_id',
                                                        $item->id,
                                                    );

                                                    $currentBaseQty = $dlItem->shipped_quantity ?? 0;
                                                    $currentDisplayQty = $currentBaseQty / $unitConversionValue;

                                                    $note = $dlItem->note ?? '';

                                                    $availableBase = max(
                                                        $readyBase - ($deliveredBase + $shippingBase),
                                                        0,
                                                    );

                                                    $maxBase = $availableBase + $currentBaseQty;
                                                    $maxDisplay = $maxBase / $unitConversionValue;

                                                    $remainingBase = max($maxBase - $currentBaseQty, 0);
                                                    $remainingDisplay = $remainingBase / $unitConversionValue;

                                                    $readyDisplay = $readyBase / $unitConversionValue;
                                                    $deliveredDisplay = $deliveredBase / $unitConversionValue;
                                                    $shippingDisplay = $shippingBase / $unitConversionValue;
                                                    $availableDisplay = $availableBase / $unitConversionValue;
                                                @endphp

                                                <tr>
                                                    <td>
                                                        {{ $item->product->name }}
                                                        <input type="hidden"
                                                            name="items[{{ $item->id }}][delivery_list_item_id]"
                                                            value="{{ $dlItem->id ?? '' }}">
                                                        <input type="hidden"
                                                            name="items[{{ $item->id }}][delivery_order_item_id]"
                                                            value="{{ $item->id }}">
                                                        <input type="hidden" name="items[{{ $item->id }}][product_id]"
                                                            value="{{ $item->product_id }}">
                                                    </td>
                                                    <td>
                                                        <span
                                                            class="text-primary">{{ number_format($readyDisplay, 0, ',', '.') }}</span>
                                                        /
                                                        <span>{{ number_format($item->progress_qty, 0, ',', '.') }}</span>
                                                        {{ $item->unit_name }}
                                                        <div class="small text-muted">
                                                            Base: {{ number_format($readyBase, 0, ',', '.') }}
                                                        </div>
                                                    </td>
                                                    <td><span
                                                            class="text-success">{{ number_format($deliveredDisplay, 0, ',', '.') }}
                                                            {{ $item->unit_name }}</span>
                                                    </td>
                                                    <td><span
                                                            class="text-warning">{{ number_format($shippingDisplay, 0, ',', '.') }}
                                                            {{ $item->unit_name }}</span>
                                                    </td>
                                                    <td><span
                                                            class="text-danger">{{ number_format($availableDisplay, 0, ',', '.') }}
                                                            {{ $item->unit_name }}</span>
                                                        <div class="small text-muted">
                                                            Base: {{ number_format($availableBase, 0, ',', '.') }}
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <input type="text" inputmode="numeric"
                                                            class="form-control shipped-input"
                                                            name="items[{{ $item->id }}][shipped_quantity]"
                                                            max="{{ $maxDisplay }}" data-max="{{ $maxDisplay }}"
                                                            value="{{ number_format($currentDisplayQty, 0, ',', '.') }}">
                                                        <small class="text-muted remaining-info">
                                                            Remaining:
                                                            {{ number_format($maxDisplay, 0, ',', '.') }}
                                                            {{ $item->unit_name }}
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control"
                                                            name="items[{{ $item->id }}][note]"
                                                            value="{{ $note }}" placeholder="Note">
                                                    </td>
                                                </tr>
                                            @endforeach
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

@push('scripts')
    <script>
        $(document).ready(function() {

            function formatNumberID(value) {
                return value.replace(/\D/g, '')
                    .replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            }

            function showToast(msg) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'warning',
                    title: msg,
                    showConfirmButton: false,
                    timer: 1500
                });
            }

            $(document).on('input', 'input[name^="items"][name$="[shipped_quantity]"]', function() {
                const input = $(this);
                const raw = input.val().replace(/\./g, '');
                if (raw === '') return;

                let value = parseInt(raw);
                const max = parseInt(input.attr('max')) || 0;

                if (value > max) {
                    value = max;
                    showToast('Jumlah pengiriman tidak boleh melebihi Ready Qty (' + max.toLocaleString(
                        'id-ID') + ')');
                }

                input.val(formatNumberID(value.toString()));
            });

            function showError(el, message) {
                if ($(el).data('select2')) {
                    const select2Container = $(el).next('.select2');
                    select2Container.next('.invalid-feedback').remove();

                    const feedback = document.createElement('div');
                    feedback.className = 'invalid-feedback d-block';
                    feedback.textContent = message;

                    select2Container.after(feedback);
                    select2Container.find('.select2-selection').css('border-color', '#dc3545'); // merah bootstrap
                } else {
                    el.classList.add('is-invalid');
                    const container = el.closest('.input-group') || el.parentNode;
                    const existing = container.querySelector('.invalid-feedback');
                    if (existing) existing.remove();

                    const feedback = document.createElement('div');
                    feedback.className = 'invalid-feedback d-block';
                    feedback.textContent = message;
                    container.appendChild(feedback);
                }
            }


            $(document).on("change input", "#driver_id", function() {
                if ($(this).hasClass("select2-hidden-accessible")) {
                    $(this).next('.select2').next('.invalid-feedback').remove();
                } else {
                    this.classList.remove("is-invalid");
                    $(this).siblings(".invalid-feedback").remove();
                }
            });

            $('#deliveryListForm').on('submit', function(e) {
                e.preventDefault();

                let isValid = true;
                let hasQuantity = false; // ✅ Tambahan untuk cek minimal 1 qty > 0

                this.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                this.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

                const driverSelect = document.querySelector('#driver_id');
                if (!driverSelect.value) {
                    isValid = false;
                    showError(driverSelect, 'Driver wajib dipilih');
                }

                $('input[name^="items"][name$="[shipped_quantity]"]').each(function() {
                    const val = $(this).val().replace(/\./g, '');
                    const td = $(this).closest('td');
                    $(td).find('.invalid-feedback').remove();

                    // ✅ validasi input kosong
                    if (val === '') {
                        isValid = false;
                        $(this).addClass('is-invalid');
                        const feedback = document.createElement('div');
                        feedback.className = 'invalid-feedback d-block';
                        feedback.textContent = 'Harus diisi (boleh 0)';
                        td.appendChild(feedback);
                    } else {
                        $(this).removeClass('is-invalid');
                    }

                    // ✅ cek kalau ada minimal 1 qty > 0
                    if (parseInt(val) > 0) {
                        hasQuantity = true;
                    }
                });

                // ✅ kalau semua 0 → tampilkan swal error
                if (!hasQuantity) {
                    isValid = false;
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: 'Minimal 1 produk harus memiliki jumlah pengiriman lebih dari 0.',
                    });
                }

                // ✅ bersihkan titik sebelum submit
                $('input[name^="items"][name$="[shipped_quantity]"]').each(function() {
                    $(this).val($(this).val().replace(/\./g, ''));
                });

                if (isValid) this.submit();
            });


            $(document).on('focus', 'input[name^="items"][name$="[shipped_quantity]"]', function() {
                if ($(this).val() === '0') $(this).val('');
            });

            $(document).on('blur', 'input[name^="items"][name$="[shipped_quantity]"]', function() {
                if ($(this).val().trim() === '') $(this).val('0');
            });
        });
    </script>
@endpush
