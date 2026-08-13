@extends('erp.layouts.main')

@push('styles')
    <style>
        .bypass-check {
            transform: scale(1.6);
            /* BESARKAN */
            margin-top: 6px;
            /* BIAR SEJARIS */
            cursor: pointer;
        }

        /* Optional: Besarkan label juga */
        .bypass-check+label {
            font-size: 14px;
            margin-left: 4px;
            cursor: pointer;
        }
    </style>
@endpush

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Add Assign Batch</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Production</li>
                <li class="breadcrumb-item">Add Assign Batch</li>
            </ul>
        </div>
        <div class="page-header-right ms-auto">
            <div class="page-header-right-items">
                <div class="d-flex align-items-center gap-2">
                    <a href="/erp/productions/waiting-list" class="btn btn-light-brand">
                        <i class="feather-arrow-left me-2"></i><span>Back</span>
                    </a>
                    <button type="submit" class="btn btn-primary" form="assignForm" id="btnSubmitForm">
                        <i class="feather-user-plus me-2"></i><span>Edit Assign Batch</span>
                    </button>
                </div>
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

    <div class="main-content m-0 m-md-2 m-lg-2 p-0 p-md-0 p-lg-0 pt-1 pt-md-0">
        <div class="row">
            <div class="col-12">
                <form action="/erp/productions/assign-list/assign/update/{{ $batch->id }}" method="POST"
                    id="assignForm">
                    @csrf
                    @method('PUT')
                    <div class="row">
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Sale Info</h4>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-2">
                                        <div class="col-lg-5 fw-semibold">Invoice Number:</div>
                                        <div class="col-lg-7">{{ $progress->order->order_number ?? '-' }}</div>
                                    </div>

                                    <div class="row mb-2">
                                        <div class="col-lg-5 fw-semibold">Customer:</div>
                                        <div class="col-lg-7">{{ $progress->order->customer->name ?? '-' }}</div>
                                    </div>

                                    <div class="row mb-2">
                                        <div class="col-lg-5 fw-semibold text-primary">Order Note:</div>
                                        <div class="col-lg-7 fw-semibold text-primary">
                                            {{ $progress->order->notes ?? '-' }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ===================== --}}
                        {{--   PREVIEW IMAGE CARD   --}}
                        {{-- ===================== --}}
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Preview Image</h4>
                                </div>
                                <div class="card-body">

                                    {{-- Design Preview Section --}}
                                    @if ($progress->items->pluck('designItem')->filter()->isNotEmpty())
                                        <div class="row mb-2">
                                            <div class="col-lg-2">
                                                <label class="fw-semibold">Design Preview:</label>
                                            </div>
                                            <div class="col-lg-10">
                                                <div class="d-flex flex-wrap gap-3">

                                                    @foreach ($progress->items as $item)
                                                        @php
                                                            $images = json_decode(
                                                                optional($item->designItem)->preview_image ?? '[]',
                                                                true,
                                                            );
                                                        @endphp

                                                        @foreach ($images as $img)
                                                            <div class="text-center">
                                                                <a href="#" class="img-viewer"
                                                                    data-src="{{ asset($img['file']) }}"
                                                                    data-note="{{ $img['note'] ?? '' }}">

                                                                    <img src="{{ asset($img['file']) }}" width="120"
                                                                        height="90" loading="lazy"
                                                                        style="border-radius:8px;object-fit:cover;object-position:center;border:1px solid #ddd;">
                                                                </a>

                                                                <p class="small text-muted mt-1">{{ $img['note'] ?? '-' }}
                                                                </p>
                                                            </div>
                                                        @endforeach
                                                    @endforeach

                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Auto Assign Date --}}
                                    <input type="hidden" id="assign_date" name="assign_date"
                                        value="{{ now()->format('Y-m-d') }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- === TABEL ASSIGN PER PRODUK === --}}
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Assign Product</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Product</th>
                                            {{-- <th>Progress</th> --}}
                                            <th>Assigning</th>
                                            {{-- <th>Available</th> --}}
                                            <th>Assign Now</th>
                                            <th style="width: 20%;">Mesin</th>
                                            <th>Note</th>
                                            <th>Bypass</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <tbody>
                                        @foreach ($batch->assigns as $index => $assign)
                                            @php
                                                $item = $assign->progressItem;
                                            @endphp
                                            @php
                                                $assign = $batch->assigns->firstWhere(
                                                    'order_progress_item_id',
                                                    $item->id,
                                                );
                                                $assignedQty = $assign->assigned_quantity ?? 0;
                                                $note = $assign->note ?? '';
                                                // 🔧 mesin per produk, data lama fallback ke mesin batch
                                                $selectedMachineId = $assign->machine_id ?? $batch->machine_id;
                                            @endphp
                                            <tr>
                                                <input type="hidden" name="items[{{ $index }}][id]"
                                                    value="{{ $assign->id ?? '' }}">
                                                {{-- <td>{{ $item->product->name }}</td> --}}
                                                <td>
                                                    <div class="fw-semibold" style="font-size: 14px;">
                                                        {{ $item->product->name }}
                                                    </div>
                                                    <div class="text-muted small mt-1">
                                                        Progress:
                                                        {{ number_format($item->completed_quantity, 0, ',', '.') }}
                                                        /
                                                        {{ number_format($item->quantity, 0, ',', '.') }}
                                                        {{ $item->unit_name }}
                                                    </div>
                                                </td>
                                                {{-- <td class="text-start">
                                                    {{ number_format($item->completed_quantity, 0, ',', '.') }} /
                                                    {{ number_format($item->quantity, 0, ',', '.') }}
                                                </td> --}}
                                                <td class="text-start">
                                                    <div class="fw-semibold text-primary mt-1">
                                                        Available:
                                                        {{ number_format($item->available_quantity, 0, ',', '.') }}
                                                        {{ $item->unit_name }}
                                                    </div>
                                                    <div class="fw-semibold text-danger">
                                                        Assigning: {{ number_format($item->active_assign, 0, ',', '.') }}
                                                    </div>
                                                </td>
                                                {{-- <td class="text-danger fw-semibold">
                                                    {{ number_format($item->active_assign, 0, ',', '.') }}
                                                </td>
                                                <td class="text-primary fw-semibold">
                                                    {{ number_format($item->available_quantity, 0, ',', '.') }}
                                                </td> --}}
                                                <td class="text-start">
                                                    <input type="hidden"
                                                        name="items[{{ $index }}][order_progress_item_id]"
                                                        value="{{ $item->id }}">
                                                    <input type="text" inputmode="numeric"
                                                        name="items[{{ $index }}][assigned_quantity]"
                                                        class="form-control text-start"
                                                        value="{{ number_format($assignedQty, 0, ',', '.') }}"
                                                        min="0" max="{{ $item->remaining_quantity }}"
                                                        placeholder="Qty">

                                                    <small class="text-muted d-block mt-1">
                                                        Current Stock Production:
                                                        {{ number_format($item->production_stock, 0, ',', '.') }}
                                                    </small>
                                                </td>
                                                {{-- 🔧 Mesin dipilih per produk --}}
                                                <td style="min-width: 180px;">
                                                    <select name="items[{{ $index }}][machine_id]"
                                                        class="form-select machine-field" data-select2-selector="tag">
                                                        <option value="">-- Choose Mesin --</option>
                                                        @foreach ($machines as $machine)
                                                            <option value="{{ $machine->id }}"
                                                                {{ old("items.$index.machine_id", $selectedMachineId) == $machine->id ? 'selected' : '' }}>
                                                                {{ $machine->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <small class="text-danger error-machine d-none">Mesin wajib
                                                        dipilih</small>
                                                </td>
                                                <td>
                                                    <input type="text" name="items[{{ $index }}][note]"
                                                        class="form-control" value="{{ $note }}"
                                                        placeholder="Catatan singkat">
                                                </td>
                                                <td class="text-center">
                                                    <div class="form-check">
                                                        <input type="hidden" name="items[{{ $index }}][bypass]"
                                                            value="0">
                                                        <input type="checkbox" class="form-check-input bypass-check"
                                                            name="items[{{ $index }}][bypass]" value="1"
                                                            id="bypass_{{ $index }}"
                                                            {{ $assign && (int) $assign->assigned_quantity <= 0 ? 'checked' : '' }}>
                                                        <label for="bypass_{{ $index }}"
                                                            class="form-check-label small">Bypass</label>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
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
            function formatNumber(n) {
                return n.replace(/\D/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            }

            $(document).on('focus', 'input[name^="items"][name$="[assigned_quantity]"]', function() {
                if ($(this).val() === '0') $(this).val('');
            });

            $(document).on('blur', 'input[name^="items"][name$="[assigned_quantity]"]', function() {
                if ($(this).val().trim() === '') $(this).val('0');
            });

            $(document).on('input', 'input[name^="items"][name$="[assigned_quantity]"]', function(e) {
                const input = $(this);
                const raw = input.val().replace(/\./g, '');
                if (raw === '') return;

                let value = parseInt(raw);
                const max = parseInt(input.attr('max')) || 0;

                if (value > max) {
                    value = max;
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'warning',
                        title: 'Jumlah melebihi batas maksimum',
                        showConfirmButton: false,
                        timer: 1500
                    });
                }

                const formatted = formatNumber(value.toString());
                input.val(formatted);
            });

            $('#assignForm').on('submit', function() {
                $('input[name^="items"][name$="[assigned_quantity]"]').each(function() {
                    this.value = this.value.replace(/\./g, '');
                });
            });

            $(document).on('change', '.bypass-check', function() {
                const row = $(this).closest('tr');
                const isBypass = $(this).is(':checked');
                const qtyInput = row.find('input[name$="[assigned_quantity]"]');

                if (isBypass) {
                    qtyInput.val('0').prop('readonly', true);
                    row.addClass('table-secondary');
                } else {
                    qtyInput.prop('readonly', false);
                    row.removeClass('table-secondary');
                }
            });

            $('#btnSubmitForm').on('click', function(e) {
                e.preventDefault();
                let valid = true;
                $('.error-machine').addClass('d-none');

                // 🔹 Hitung semua row & row yang bypass
                let totalRows = $('.bypass-check').length;
                let bypassCount = 0;

                $('.bypass-check').each(function() {
                    if ($(this).is(':checked')) bypassCount++;
                });

                // ❌ Kalau semua bypass → stop
                if (bypassCount === totalRows) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Tidak Valid!',
                        text: 'Minimal satu produk harus diproduksi (tidak boleh semua bypass).',
                    });
                    return;
                }

                // 🔥 VALIDASI ASSIGNED QTY WAJIB JIKA TIDAK BYPASS
                $('tbody tr').has('input[name$="[assigned_quantity]"]').each(function() {
                    const row = $(this);
                    const isBypass = row.find('.bypass-check').is(':checked');
                    const qtyInput = row.find('input[name$="[assigned_quantity]"]');

                    let qty = qtyInput.val().replace(/\./g, '');
                    qty = qty === '' ? 0 : parseInt(qty);

                    if (!isBypass && qty === 0) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Tidak Valid!',
                            text: 'Assigned quantity wajib diisi jika tidak bypass.',
                        });
                        valid = false;
                        return false; // stop loop
                    }
                });

                // 🔹 Validasi mesin per produk (kecuali row yang dibypass)
                $('tbody tr').has('.machine-field').each(function() {
                    const row = $(this);
                    const isBypass = row.find('.bypass-check').is(':checked');
                    const machineSelect = row.find('.machine-field');

                    if (isBypass) return;

                    if (!machineSelect.val()) {
                        row.find('.error-machine').removeClass('d-none');
                        Swal.fire({
                            icon: 'error',
                            title: 'Tidak Valid!',
                            text: 'Mesin wajib dipilih untuk setiap produk yang diassign.',
                        });
                        valid = false;
                        return false; // stop loop
                    }
                });

                if (valid) $('#assignForm').submit();
            });
        });

        $(document).on('select2:open', () => {
            setTimeout(() => {
                document.querySelector('.select2-container--open .select2-search__field')?.focus();
            }, 50);
        });
    </script>
@endpush
