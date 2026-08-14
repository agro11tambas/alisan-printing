@extends('erp.layouts.main')

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Add Progress — {{ $machineName }}</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Production</li>
                <li class="breadcrumb-item"><a href="/erp/productions/waiting-list/assign-list">Assign List</a></li>
                <li class="breadcrumb-item">{{ $machineName }}</li>
            </ul>
        </div>
        <div class="page-header-right ms-auto">
            <div class="page-header-right-items">
                <div class="d-flex align-items-center gap-2">
                    <a href="/erp/productions/waiting-list/assign-list" class="btn btn-light-brand">
                        <i class="feather-arrow-left me-2"></i><span>Back</span>
                    </a>
                    @if ($groups->isNotEmpty())
                        <button type="submit" class="btn btn-primary" form="progressForm">
                            <i class="feather-plus me-2"></i><span>Add Progress</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    @if (session('error'))
        <script>
            window.addEventListener('load', function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: @json(session('error')),
                });
            });
        </script>
    @endif

    <div class="main-content m-0 m-md-2 m-lg-2 p-0 p-md-0 p-lg-0 pt-1 pt-md-0">
        <div class="row">
            <div class="col-12">
                <form action="/erp/productions/assign-list/machine/{{ $machineKey }}/add-progress" method="POST"
                    id="progressForm">
                    @csrf
                    @method('POST')

                    <input type="hidden" name="progress_date" id="progress_date" value="{{ now()->format('Y-m-d') }}">

                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Assign Details — {{ $machineName }}</h4>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width: 18%;">Customer</th>
                                            <th style="width: 20%;">Product</th>
                                            <th style="width: 8%;">Assigned Qty</th>
                                            <th style="width: 9%;">Completed</th>
                                            <th style="width: 9%;">Reject</th>
                                            <th style="width: 9%;">Defect</th>
                                            <th style="width: 15%;">Operator</th>
                                            <th style="width: 12%;">Note</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $index = 0; @endphp

                                        @forelse ($groups as $group)
                                            @php
                                                $batch = $group['batch'];
                                                $groupAssigns = $group['assigns'];
                                                $order = $batch?->orderProgress?->order;
                                                $businessName = $order?->customerAddress?->business_name;
                                                $customerName = $order?->customer?->name;
                                            @endphp

                                            @foreach ($groupAssigns as $i => $assign)
                                                <tr>
                                                    @if ($i === 0)
                                                        <td rowspan="{{ $groupAssigns->count() }}" class="align-top">
                                                            <div class="fw-bold text-dark">
                                                                {{ $businessName ?? ($customerName ?? '-') }}
                                                            </div>
                                                            <div class="text-muted small">
                                                                {{ $order?->order_number ?? '-' }}
                                                            </div>
                                                            @if ($order?->notes)
                                                                <div class="text-primary small mt-1"
                                                                    style="white-space: normal; word-break: break-word;">
                                                                    {{ $order->notes }}
                                                                </div>
                                                            @endif
                                                        </td>
                                                    @endif

                                                    <td>{{ $assign->progressItem?->product?->name ?? '-' }}</td>

                                                    <td data-assigned="{{ (int) $assign->assigned_quantity }}">
                                                        {{ number_format($assign->assigned_quantity, 0, ',', '.') }}
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

                                                    <td>
                                                        <select name="items[{{ $index }}][operator_id]"
                                                            class="form-select operator-field"
                                                            data-select2-selector="tag">
                                                            <option value="">-- Choose Operator --</option>
                                                            @foreach ($operators as $op)
                                                                <option value="{{ $op->id }}"
                                                                    {{ old("items.$index.operator_id") == $op->id ? 'selected' : '' }}>
                                                                    {{ $op->name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        <small class="text-danger error-operator d-none">Operator
                                                            wajib dipilih</small>
                                                    </td>

                                                    <td>
                                                        <input type="text" name="items[{{ $index }}][note]"
                                                            class="form-control" placeholder="Catatan singkat">
                                                    </td>
                                                </tr>
                                                @php $index++; @endphp
                                            @endforeach
                                        @empty
                                            <tr>
                                                <td colspan="8" class="text-center text-muted py-3">
                                                    Tidak ada assign yang bisa diinput progress untuk mesin ini.
                                                </td>
                                            </tr>
                                        @endforelse
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
                const assigned = parseInt(row.find('td[data-assigned]').data('assigned')) || 0;
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
                function() {
                    const input = $(this);
                    const row = input.closest('tr');
                    const raw = input.val().replace(/\./g, '');

                    if (raw === '') {
                        syncOperator(row);
                        return;
                    }

                    input.val(formatNumber(raw));
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

        $(document).on('select2:open', () => {
            setTimeout(() => {
                document.querySelector('.select2-container--open .select2-search__field')?.focus();
            }, 50);
        });
    </script>
@endpush
