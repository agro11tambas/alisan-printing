@extends('erp.layouts.main')

@push('styles')
    <style>
        .bypass-check {
            transform: scale(1.6);
            margin-top: 6px;
            cursor: pointer;
        }

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
                <h5 class="m-b-10">Edit Assign — {{ $machineName }}</h5>
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
                        <button type="submit" class="btn btn-primary" form="assignForm" id="btnSubmitForm">
                            <i class="feather-save me-2"></i><span>Save Assign</span>
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
                <form action="/erp/productions/assign-list/machine/{{ $machineKey }}/edit" method="POST" id="assignForm">
                    @csrf
                    @method('PUT')

                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Assign Product — {{ $machineName }}</h4>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width: 20%;">Customer</th>
                                            <th style="width: 22%;">Product</th>
                                            <th style="width: 13%;">Assign Now</th>
                                            <th style="width: 18%;">Mesin</th>
                                            <th style="width: 19%;">Note</th>
                                            <th style="width: 8%;">Delete</th>
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
                                                @php
                                                    $item = $assign->progressItem;
                                                    $conversion = max(
                                                        (float) ($item?->unit_conversion_value ?? 1),
                                                        1,
                                                    );
                                                @endphp
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

                                                    <td>
                                                        <div class="fw-semibold">
                                                            {{ $item?->product?->name ?? '-' }}
                                                        </div>
                                                        <div class="text-muted small">
                                                            Progress:
                                                            {{ number_format($assign->change_quantity, 0, ',', '.') }}
                                                            /
                                                            {{ number_format($assign->assigned_quantity, 0, ',', '.') }}
                                                        </div>
                                                    </td>

                                                    <td>
                                                        <input type="hidden" name="items[{{ $index }}][id]"
                                                            value="{{ $assign->id }}">
                                                        <input type="hidden" name="items[{{ $index }}][bypass]"
                                                            value="0">

                                                        <input type="text" inputmode="numeric"
                                                            name="items[{{ $index }}][assigned_quantity]"
                                                            class="form-control text-start assigned-input"
                                                            value="{{ number_format($assign->assigned_quantity, 0, ',', '.') }}"
                                                            min="0" max="{{ (int) $assign->max_quantity }}">

                                                        <small class="text-muted d-block mt-1">
                                                            Max Base:
                                                            {{ number_format($assign->max_quantity, 0, ',', '.') }}
                                                        </small>
                                                    </td>

                                                    <td>
                                                        <select name="items[{{ $index }}][machine_id]"
                                                            class="form-select machine-field"
                                                            data-select2-selector="tag">
                                                            <option value="">-- Choose Mesin --</option>
                                                            @foreach ($machines as $machine)
                                                                <option value="{{ $machine->id }}"
                                                                    {{ old("items.$index.machine_id", $assign->machine_id) == $machine->id ? 'selected' : '' }}>
                                                                    {{ $machine->name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        <small class="text-danger error-machine d-none">Mesin wajib
                                                            dipilih</small>
                                                    </td>

                                                    <td>
                                                        <input type="text" name="items[{{ $index }}][note]"
                                                            class="form-control" value="{{ $assign->note }}"
                                                            placeholder="Catatan singkat">
                                                    </td>

                                                    <td class="text-center">
                                                        <div class="form-check">
                                                            <input type="checkbox"
                                                                class="form-check-input bypass-check"
                                                                name="items[{{ $index }}][bypass]" value="1"
                                                                id="bypass_{{ $index }}">
                                                            <label for="bypass_{{ $index }}"
                                                                class="form-check-label small">Delete</label>
                                                        </div>
                                                    </td>
                                                </tr>
                                                @php $index++; @endphp
                                            @endforeach
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center text-muted py-3">
                                                    Tidak ada assign yang bisa diedit untuk mesin ini.
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

            $(document).on('focus', '.assigned-input', function() {
                if ($(this).val() === '0') $(this).val('');
            });

            $(document).on('blur', '.assigned-input', function() {
                if ($(this).val().trim() === '') $(this).val('0');
            });

            $(document).on('input', '.assigned-input', function() {
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

                input.val(formatNumber(value.toString()));
            });

            // Row yang dicentang delete → input dikunci
            $(document).on('change', '.bypass-check', function() {
                const row = $(this).closest('tr');
                const isBypass = $(this).is(':checked');

                row.find('.assigned-input').prop('readonly', isBypass);
                row.toggleClass('table-secondary', isBypass);
            });

            $('#btnSubmitForm').on('click', function(e) {
                e.preventDefault();

                let valid = true;
                $('.error-machine').addClass('d-none');

                $('tbody tr').has('.assigned-input').each(function() {
                    const row = $(this);
                    const isBypass = row.find('.bypass-check').is(':checked');

                    if (isBypass) return;

                    let qty = row.find('.assigned-input').val().replace(/\./g, '');
                    qty = qty === '' ? 0 : parseInt(qty);

                    if (qty === 0) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Tidak Valid!',
                            text: 'Assigned quantity wajib diisi jika tidak dihapus.',
                        });
                        valid = false;
                        return false;
                    }

                    if (!row.find('.machine-field').val()) {
                        row.find('.error-machine').removeClass('d-none');
                        Swal.fire({
                            icon: 'error',
                            title: 'Tidak Valid!',
                            text: 'Mesin wajib dipilih untuk setiap produk.',
                        });
                        valid = false;
                        return false;
                    }
                });

                if (valid) {
                    $('.assigned-input').each(function() {
                        this.value = this.value.replace(/\./g, '');
                    });

                    $('#assignForm').submit();
                }
            });
        });

        $(document).on('select2:open', () => {
            setTimeout(() => {
                document.querySelector('.select2-container--open .select2-search__field')?.focus();
            }, 50);
        });
    </script>
@endpush
