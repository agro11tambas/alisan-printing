@extends('erp.layouts.main')

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

    <div class="main-content">
        <div class="row">
            <div class="col-12">
                <form action="/erp/productions/waiting-list/assign/{{ $progress->id }}" method="POST" id="assignForm">
                    @csrf
                    @method('POST')
                    {{-- === INFORMASI UMUM === --}}
                    <div class="card mb-4">
                        <div class="card-header">
                            <h4 class="card-title">Sale Info</h4>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-lg-2 fw-semibold">Invoice Number:</div>
                                <div class="col-lg-10">{{ $progress->order->order_number ?? '-' }}</div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-lg-2 fw-semibold">Customer:</div>
                                <div class="col-lg-10">{{ $progress->order->customer->name ?? '-' }}</div>
                            </div>
                        </div>
                    </div>

                    {{-- === DETAIL BATCH === --}}
                    <div class="card mb-4">
                        <div class="card-header">
                            <h4 class="card-title">Assign Batch Details</h4>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-lg-2">
                                    <label for="assign_code" class="fw-semibold">Assign Code:</label>
                                </div>
                                <div class="col-lg-10">
                                    <input type="text" class="form-control" id="assign_code" name="assign_code"
                                        value="{{ $assignCode }}" readonly>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-lg-2">
                                    <label for="assign_date" class="fw-semibold">Assign Date:</label>
                                </div>
                                <div class="col-lg-10">
                                    <input type="date" class="form-control" id="assign_date" name="assign_date"
                                        value="{{ now()->format('Y-m-d') }}" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-lg-2">
                                    <label for="note" class="fw-semibold">Note:</label>
                                </div>
                                <div class="col-lg-10">
                                    <textarea class="form-control" id="note" name="note" placeholder="Catatan tambahan untuk batch ini"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- === TABEL ASSIGN PER PRODUK === --}}
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Assign Operator per Product</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Product</th>
                                            <th class="text-center">Total Qty</th>
                                            <th class="text-center">Already Assigned</th>
                                            <th class="text-center">Assign Now</th>
                                            <th>Operator</th>
                                            <th>Note</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($progress->items as $index => $item)
                                            <tr>
                                                <td>{{ $item->product->name }}</td>
                                                <td class="text-start">{{ number_format($item->quantity, 0, ',', '.') }}
                                                </td>
                                                <td class="text-start">
                                                    {{ number_format($item->assigns->sum('assigned_quantity'), 0, ',', '.') }}
                                                </td>
                                                <td class="text-start">
                                                    <input type="hidden"
                                                        name="items[{{ $index }}][order_progress_item_id]"
                                                        value="{{ $item->id }}">
                                                    <input type="text"
                                                        name="items[{{ $index }}][assigned_quantity]"
                                                        class="form-control text-start" value="0" min="0"
                                                        max="{{ $item->quantity - $item->completed_quantity }}"
                                                        placeholder="Qty">
                                                    <small class="text-muted d-block mt-1">
                                                        Remaining:
                                                        {{ number_format($item->quantity - $item->completed_quantity, 0, ',', '.') }}
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

            // === FORMAT ANGKA DENGAN TITIK (1.000) ===
            function formatNumber(n) {
                return n.replace(/\D/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            }

            // === HAPUS 0 SAAT FOKUS ===
            $(document).on('focus', 'input[name^="items"][name$="[assigned_quantity]"]', function() {
                if ($(this).val() === '0') $(this).val('');
            });

            // === KEMBALIKAN 0 JIKA KOSONG ===
            $(document).on('blur', 'input[name^="items"][name$="[assigned_quantity]"]', function() {
                if ($(this).val().trim() === '') $(this).val('0');
            });

            // === FORMAT ANGKA OTOMATIS + BATAS MAX ===
            $(document).on('input', 'input[name^="items"][name$="[assigned_quantity]"]', function(e) {
                const input = $(this);
                const raw = input.val().replace(/\./g, '');
                if (raw === '') return;

                let value = parseInt(raw);
                const max = parseInt(input.attr('max')) || 0;

                // 🔹 Batasi agar tidak lebih dari max
                if (value > max) {
                    value = max;
                    // opsional: beri notifikasi kecil
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

            // === BERSIHKAN TITIK SEBELUM SUBMIT ===
            $('#assignForm').on('submit', function() {
                $('input[name^="items"][name$="[assigned_quantity]"]').each(function() {
                    this.value = this.value.replace(/\./g, '');
                });
            });

            // === VALIDASI OPERATOR WAJIB ===
            $('#btnSubmitForm').on('click', function(e) {
                e.preventDefault();
                let valid = true;
                $('.error-operator').addClass('d-none');

                $('.operator-field').each(function() {
                    if ($(this).val() === '') {
                        $(this).closest('td').find('.error-operator').removeClass('d-none');
                        valid = false;
                    }
                });

                if (valid) $('#assignForm').submit();
            });
        });
    </script>
@endpush
