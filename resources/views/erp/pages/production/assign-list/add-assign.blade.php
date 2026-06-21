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

        #assignTable_wrapper .dataTables_scrollBody {
            height: 55vh !important;
            overflow-y: auto !important;
            background-image: none !important;
        }

        .dataTables_scrollBody {
            scroll-behavior: smooth;
        }

        #assignTable tbody tr {
            animation: fadeIn 0.3s ease-in;
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
                        <i class="feather-user-plus me-2"></i><span>Add Assign Batch</span>
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

    <div class="main-content m-0 m-md-2 m-lg-2 p-0 p-md-0 p-lg-0 pt-2 pt-md-0">
        <div class="row">
            <div class="col-12">
                <form action="/erp/productions/waiting-list/assign/{{ $progress->id }}" method="POST" id="assignForm">
                    @csrf
                    @method('POST')
                    <div class="row">
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Sale Info</h4>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-lg-5 fw-semibold">Invoice Number:</div>
                                        <div class="col-lg-7">{{ $progress->order->order_number ?? '-' }}</div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-lg-5 fw-semibold">Customer:</div>
                                        <div class="col-lg-7">{{ $progress->order->customer->name ?? '-' }}</div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-lg-5 fw-semibold">
                                            <span class="text-primary">Order Note:</span>
                                        </div>
                                        <div class="col-lg-7 fw-semibold">
                                            <span class="text-primary">{{ $progress->order->notes ?? '-' }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Preview Image</h4>
                                </div>
                                <div class="card-body">
                                    {{-- <div class="row mb-3">
                                <div class="col-lg-2">
                                    <label for="assign_code" class="fw-semibold">Assign Code:</label>
                                </div>
                                <div class="col-lg-10">
                                    <input type="text" class="form-control" id="assign_code" name="assign_code"
                                        value="{{ $assignCode }}" readonly>
                                </div>
                            </div> --}}

                                    {{-- Design Preview Section --}}
                                    @if ($progress->items->pluck('designItem')->filter()->isNotEmpty())
                                        <div class="row mb-4">
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

                                    <input type="hidden" class="form-control" id="assign_date" name="assign_date"
                                        value="{{ now()->format('Y-m-d') }}">

                                    {{-- <div class="row mb-3">
                                <div class="col-lg-2">
                                    <label for="assign_date" class="fw-semibold">Assign Date:</label>
                                </div>
                                <div class="col-lg-10">
                                    <input type="date" class="form-control" id="assign_date" name="assign_date"
                                        value="{{ now()->format('Y-m-d') }}" required>
                                </div>
                            </div> --}}

                                    {{-- <div class="row mb-3">
                                <div class="col-lg-2">
                                    <label for="note" class="fw-semibold">Note:</label>
                                </div>
                                <div class="col-lg-10">
                                    <textarea class="form-control" id="note" name="note" placeholder="Catatan tambahan untuk batch ini"></textarea>
                                </div>
                            </div> --}}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Assign Operator per Product</h4>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive p-0">
                                <table class="table table-hover bg-transparent" id="assignTable">
                                    <thead>
                                        <tr>
                                            <th style="width: 10%;">Product</th>
                                            {{-- <th style="width: 5%;">Progress</th> --}}
                                            <th style="width: 5%;">Assigning</th>
                                            {{-- <th>Available</th> --}}
                                            <th style="width: 5%;">Assign Now</th>
                                            <th style="width: 25%;">Operator</th>
                                            <th style="width: 20%;">Note</th>
                                            <th style="width: 8%;">Delete</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($progress->items as $index => $item)
                                            {{-- @if ($item->available_quantity <= 0)
                                                @continue
                                            @endif --}}
                                            <tr>
                                                {{-- <td>{{ $item->product->name }}</td> --}}
                                                <td>
                                                    <div class="fw-semibold">
                                                        {{ $item->product->name }}
                                                    </div>
                                                    <div class="text-muted small">
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
                                                <td class="text-start" style="width: 100px; min-width: 100px;">
                                                    <input type="hidden"
                                                        name="items[{{ $index }}][order_progress_item_id]"
                                                        value="{{ $item->id }}">
                                                    <input type="text" inputmode="numeric"
                                                        name="items[{{ $index }}][assigned_quantity]"
                                                        class="form-control text-start assigned-input" value="0"
                                                        min="0" max="{{ $item->remaining_quantity }}"
                                                        placeholder="Qty">
                                                    <small class="text-muted d-block mt-1">
                                                        Current Stock:
                                                        {{ number_format($item->production_stock, 0, ',', '.') }}
                                                    </small>
                                                </td>
                                                <td>
                                                    <select name="items[{{ $index }}][operator_id]"
                                                        class="form-select operator-field" data-select2-selector="tag">
                                                        <option value="">-- Choose Operator --</option>
                                                        @foreach ($operators as $op)
                                                            <option value="{{ $op->id }}">{{ $op->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <small class="text-danger error-operator d-none">Operator wajib
                                                        dipilih</small>
                                                </td>
                                                <td>
                                                    <input type="text" name="items[{{ $index }}][note]"
                                                        class="form-control" placeholder="Catatan singkat">
                                                </td>
                                                <td class="text-center">
                                                    <div class="form-check">
                                                        <input type="checkbox" class="form-check-input bypass-check"
                                                            name="items[{{ $index }}][bypass]" value="1"
                                                            id="bypass_{{ $index }}"
                                                            {{ $item->available_quantity <= 0 ? 'checked' : '' }}>
                                                        <label for="bypass_{{ $index }}"
                                                            class="form-check-label small">Delete</label>
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

@push('modals')
    <div class="modal fade" id="modalPreviewDesign" tabindex="-1" aria-labelledby="modalPreviewDesignLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title" id="modalPreviewDesignLabel">Design Preview</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="previewImageFull" src="" alt="Design Preview" class="img-fluid rounded mb-3"
                        style="max-height:70vh;object-fit:contain;">
                    <p id="previewImageNote" class="text-muted fs-6 mb-0"></p>
                </div>
            </div>
        </div>
    </div>
@endpush

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

            $('#btnSubmitForm').on('click', function(e) {
                e.preventDefault();
                let valid = true;
                $('.error-operator').addClass('d-none');

                // 🔹 Hitung semua row dan yang bypass
                let totalRows = $('.bypass-check').length;
                let bypassCount = 0;

                $('.bypass-check').each(function() {
                    if ($(this).is(':checked')) bypassCount++;
                });

                // ❌ Jika semua bypass → error
                if (bypassCount === totalRows) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Tidak Valid!',
                        text: 'Minimal satu produk harus diproduksi (tidak boleh semua bypass).',
                    });
                    return; // stop submit
                }

                // VALIDASI ASSIGNED QTY WAJIB DIISI JIKA TIDAK BYPASS
                $('tbody tr').has('.assigned-input').each(function() {
                    const row = $(this);
                    const isBypass = row.find('.bypass-check').is(':checked');
                    const qtyInput = row.find('.assigned-input');

                    let qty = qtyInput.val().replace(/\./g, '');
                    qty = qty === '' ? 0 : parseInt(qty);

                    if (!isBypass && qty === 0) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Tidak Valid!',
                            text: 'Assigned quantity wajib diisi jika tidak delete.',
                        });
                        valid = false;
                        return false; // break dari loop
                    }
                });

                // VALIDASI OPERATOR WAJIB JIKA TIDAK BYPASS
                $('.operator-field').each(function() {
                    const row = $(this).closest('tr');
                    const isBypass = row.find('.bypass-check').is(':checked');

                    if (!isBypass && $(this).val() === '') {
                        row.find('.error-operator').removeClass('d-none');
                        valid = false;
                    }
                });

                if (valid) $('#assignForm').submit();
            });

            // AUTO DISABLE row yang bypass dari awal
            $('.bypass-check:checked').each(function() {
                const row = $(this).closest('tr');
                const qtyInput = row.find('.assigned-input');
                const operatorSelect = row.find('.operator-field');

                qtyInput.val('0').prop('disabled', true);
                operatorSelect.val('').trigger('change').prop('disabled', true);
                row.find('.error-operator').addClass('d-none');
            });

            $(document).on('change', '.bypass-check', function() {
                const row = $(this).closest('tr');
                const isBypass = $(this).is(':checked');
                const qtyInput = row.find('.assigned-input');
                const operatorSelect = row.find('.operator-field');

                if (isBypass) {
                    qtyInput.val('0').prop('disabled', true);
                    operatorSelect.val('').trigger('change').prop('disabled', true);
                    row.find('.error-operator').addClass('d-none');
                } else {
                    qtyInput.prop('disabled', false);
                    operatorSelect.prop('disabled', false);
                }
            });
        });

        $(document).on('select2:open', () => {
            setTimeout(() => {
                document.querySelector('.select2-container--open .select2-search__field')?.focus();
            }, 50);
        });

        $(document).on('click', '.img-viewer', function(e) {
            e.preventDefault();
            const imgSrc = $(this).data('src');
            const note = $(this).data('note') || '-';
            $('#previewImageFull').attr('src', imgSrc);
            $('#previewImageNote').text(note);
            $('#modalPreviewDesign').modal('show');
        });

        $(document).ready(function() {

            const dt = $('#assignTable').DataTable({
                processing: false,
                serverSide: false,
                scrollCollapse: true,
                paging: false,
                searching: false,
                info: false,
                lengthChange: false,
                ordering: false,
            });

        });
    </script>
@endpush
