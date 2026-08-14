@extends('erp.layouts.main')

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10 text-success">Add Progress</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Production</li>
                <li class="breadcrumb-item">Add Progress</li>
            </ul>
        </div>
        <div class="page-header-right ms-auto">
            <div class="page-header-right-items">
                <div class="d-flex align-items-center gap-2">
                    <a href="/erp/productions/assign-list" class="btn btn-light-brand">
                        <i class="feather-arrow-left me-2"></i><span>Back</span>
                    </a>
                    <button type="submit" class="btn btn-primary" form="progressForm">
                        <i class="feather-plus me-2"></i><span>Add Progress</span>
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
    @if (session('success'))
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: "{{ session('success') }}",
            });
        </script>
    @endif

    <div class="main-content m-0 m-md-2 m-lg-2 p-0 p-md-0 p-lg-0 pt-1 pt-md-0">
        <div class="row">
            <div class="col-12">
                <form action="/erp/productions/assign-list/add-progress/{{ $batch->id }}" method="POST"
                    id="progressForm">
                    @csrf
                    @method('POST')

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card mb-2">
                                <div class="card-header">
                                    <h4 class="card-title">Sale Info</h4>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-2">
                                        <div class="col-lg-2 fw-semibold">Invoice Number:</div>
                                        <div class="col-lg-10">{{ $batch->orderProgress->order->order_number ?? '-' }}</div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-lg-2 fw-semibold">Customer:</div>
                                        <div class="col-lg-10">{{ $batch->orderProgress->order->customer->name ?? '-' }}
                                        </div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-lg-2 fw-semibold">Mesin:</div>
                                        <div class="col-lg-10 fw-semibold">
                                            @php
                                                // 🔧 mesin per produk, data lama fallback ke mesin batch
                                                $batchMachineNames = $batch->assigns
                                                    ->map(fn($assign) => $assign->machine->name ?? null)
                                                    ->filter()
                                                    ->unique()
                                                    ->values();
                                            @endphp
                                            {{ $batchMachineNames->isNotEmpty() ? $batchMachineNames->implode(', ') : $batch->machine->name ?? '-' }}
                                        </div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-lg-2 fw-semibold">
                                            <span class="text-primary">Order Note:</span>
                                        </div>
                                        <div class="col-lg-10 fw-semibold">
                                            <span
                                                class="text-primary">{{ $batch->orderProgress->order->notes ?? '-' }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="progress_date" id="progress_date" value="{{ now()->format('Y-m-d') }}">
                        {{-- <div class="col-lg-8">
                            <div class="card mb-2">
                                <div class="card-header">
                                    <h4 class="card-title">Assign Info - {{ $batch->assign_code }}</h4>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-2">
                                        <div class="col-lg-2">
                                            <label for="progress_date" class="fw-semibold">Progress Date:</label>
                                        </div>
                                        <div class="col-lg-10">
                                            <input type="date" class="form-control" id="progress_date"
                                                name="progress_date" value="{{ now()->format('Y-m-d') }}" required>
                                        </div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-lg-2">
                                            <label for="note" class="fw-semibold">Note:</label>
                                        </div>
                                        <div class="col-lg-10">
                                            <textarea class="form-control" id="note" name="note" placeholder="Catatan tambahan"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div> --}}
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Assign Details</h4>
                        </div>
                        <div class="card-body">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Mesin</th>
                                        <th>Assigned Qty</th>
                                        <th>Completed</th>
                                        <th>Reject</th>
                                        <th>Defect</th>
                                        {{-- <th>Remaining</th> --}}
                                        <th style="width: 20%;">Operator</th>
                                        <th>Note</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($batch->assigns as $index => $assign)
                                        @php
                                            $remaining = $assign->assigned_quantity - $assign->completed_quantity;
                                        @endphp
                                        <tr>
                                            <td>{{ $assign->progressItem->product->name ?? '-' }}</td>
                                            <td>{{ $assign->machine->name ?? ($batch->machine->name ?? '-') }}</td>
                                            <td>{{ number_format($assign->assigned_quantity, 0, ',', '.') }}
                                            </td>
                                            <td>
                                                <input type="hidden" name="items[{{ $index }}][assign_id]"
                                                    value="{{ $assign->id }}">
                                                <input type="text" inputmode="numeric"
                                                    name="items[{{ $index }}][completed_quantity]"
                                                    class="form-control text-start" value="0">
                                            </td>
                                            <td>
                                                <input type="text" inputmode="numeric"
                                                    name="items[{{ $index }}][reject_quantity]"
                                                    class="form-control text-start" value="0">
                                            </td>
                                            <td>
                                                <input type="text" inputmode="numeric"
                                                    name="items[{{ $index }}][defect_quantity]"
                                                    class="form-control text-start" value="0">
                                            </td>
                                            {{-- <td><span
                                                    class="text-muted">{{ number_format($remaining, 0, ',', '.') }}</span>
                                            </td> --}}
                                            <td>
                                                <select name="items[{{ $index }}][operator_id]"
                                                    class="form-select operator-field" data-select2-selector="tag">
                                                    <option value="">-- Choose Operator --</option>
                                                    @foreach ($operators as $op)
                                                        <option value="{{ $op->id }}"
                                                            {{ old("items.$index.operator_id") == $op->id ? 'selected' : '' }}>
                                                            {{ $op->name }}
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
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
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
            let toastActive = false;

            function formatNumber(n) {
                return n.replace(/\D/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
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

            function checkLimit(row) {
                const assigned = parseInt(row.find('td:eq(1)').text().replace(/\./g, '')) || 0;
                const completed = parseInt(row.find('input[name$="[completed_quantity]"]').val().replace(/\./g,
                    '')) || 0;
                const reject = parseInt(row.find('input[name$="[reject_quantity]"]').val().replace(/\./g, '')) || 0;
                const defect = parseInt(row.find('input[name$="[defect_quantity]"]').val().replace(/\./g, '')) || 0;

                const total = completed + reject + defect;
                const wasOver = row.data('wasOver') || false;

                if (total > assigned) {
                    if (!wasOver) {
                        showToast('Total tidak boleh melebihi jumlah assigned (' + assigned.toLocaleString(
                            'id-ID') + ')');
                        row.data('wasOver', true);
                    }

                    const lastInput = row.find('input:focus');
                    if (lastInput.length) {
                        const othersTotal = total - parseInt(lastInput.val().replace(/\./g, ''));
                        const maxAllowed = Math.max(assigned - othersTotal, 0);
                        lastInput.val(formatNumber(maxAllowed.toString()));
                    }
                }

                if (total <= assigned && wasOver) {
                    row.data('wasOver', false);
                }
            }

            // 🔧 Total isian satu baris (completed + reject + defect)
            function rowTotal(row) {
                let total = 0;

                row.find(
                    'input[name$="[completed_quantity]"], input[name$="[reject_quantity]"], input[name$="[defect_quantity]"]'
                ).each(function() {
                    total += parseInt($(this).val().replace(/\./g, '')) || 0;
                });

                return total;
            }

            // 🔧 Baris yang totalnya 0 (bypass) → operatornya otomatis dikosongkan
            function syncOperator(row) {
                const operator = row.find('.operator-field');

                if (rowTotal(row) <= 0) {
                    if (operator.val()) {
                        operator.val('').trigger('change');
                    }
                    row.find('.error-operator').addClass('d-none');
                }
            }

            $(document).on('input',
                'input[name$="[completed_quantity]"], input[name$="[reject_quantity]"], input[name$="[defect_quantity]"]',
                function(e) {
                    const input = $(this);
                    const row = input.closest('tr');
                    const raw = input.val().replace(/\./g, '');

                    if (raw === '') {
                        syncOperator(row);
                        return;
                    }

                    const formatted = formatNumber(raw);
                    input.val(formatted);

                    checkLimit(row);
                    syncOperator(row);
                });

            $(document).on('focus',
                'input[name$="[completed_quantity]"], input[name$="[reject_quantity]"], input[name$="[defect_quantity]"]',
                function() {
                    if ($(this).val() === '0') $(this).val('');
                });

            $(document).on('blur',
                'input[name$="[completed_quantity]"], input[name$="[reject_quantity]"], input[name$="[defect_quantity]"]',
                function() {
                    if ($(this).val().trim() === '') $(this).val('0');
                });

            $('#progressForm').on('submit', function(e) {
                let valid = true;
                let anyFilled = false;

                $('.error-operator').addClass('d-none');

                // 🔧 Operator hanya wajib untuk baris yang diisi.
                //    Baris 0 (bypass) dilewati dan tidak disimpan ke database.
                $('tbody tr').has('.operator-field').each(function() {
                    const row = $(this);

                    if (rowTotal(row) <= 0) return;

                    anyFilled = true;

                    if (row.find('.operator-field').val() === '') {
                        row.find('.error-operator').removeClass('d-none');
                        valid = false;
                    }
                });

                if (!valid) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Tidak Valid!',
                        text: 'Operator wajib dipilih untuk produk yang diisi progressnya.',
                    });
                    return;
                }

                if (!anyFilled) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Tidak Valid!',
                        text: 'Minimal satu produk harus diisi progressnya.',
                    });
                    return;
                }

                $('input[name$="[completed_quantity]"], input[name$="[reject_quantity]"], input[name$="[defect_quantity]"]')
                    .each(function() {
                        this.value = this.value.replace(/\./g, '');
                    });
            });
        });
    </script>
@endpush
