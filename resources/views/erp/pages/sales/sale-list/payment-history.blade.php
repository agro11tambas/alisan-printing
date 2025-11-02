@extends('erp.layouts.main')

@section('breadcrumb')
<div class="page-header sticky-top">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">Order</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
            <li class="breadcrumb-item">Order</li>
            <li class="breadcrumb-item">Payment History</li>
        </ul>
    </div>
</div>
@endsection

@section('content')
@if(session('error'))
<script>
    Swal.fire({
        icon: 'error',
        title: 'Gagal!',
        text: "{{ session('error') }}",
    });
</script>
@endif

<div class="main-content">
    <div class="row align-items-baseline">
        <div class="col-xxl-12 col-xl-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Payment History - Order #{{ $order->order_number }}</h5>
                </div>
                <div class="card-body">
                    @forelse($transactions as $groupId => $trxGroup)
                    @php
                    $debitGroup = $trxGroup->where('debit', '>', 0);
                    @endphp

                    @if($debitGroup->isNotEmpty())
                    <div class="mb-4 border rounded">
                        <div class="d-flex justify-content-between align-items-center bg-light p-2">
                            <span><strong>Tanggal:</strong> {{ \Carbon\Carbon::parse($debitGroup->first()->transaction_date)->format('d-m-Y') }}</span>
                            <button type="button"
                                class="btn btn-sm btn-primary btn-edit-payment"
                                data-bs-toggle="modal"
                                data-bs-target="#modalEditPayment"
                                data-group="{{ $groupId }}"
                                data-date="{{ \Carbon\Carbon::parse($debitGroup->first()->transaction_date)->format('Y-m-d') }}"
                                data-amount="{{ $debitGroup->sum('debit') }}"
                                data-account="{{ optional($debitGroup->first())->account_id }}"
                                data-note="{{ $debitGroup->first()->note }}">
                                <i class="feather feather-edit-3 me-2"></i>Edit
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered m-0">
                                <thead>
                                    <tr>
                                        <th>Akun</th>
                                        <th>Debit</th>
                                        <th>Keterangan</th>
                                        <th>Particular</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($debitGroup as $trx)
                                    <tr>
                                        <td>{{ $trx->account->name ?? '-' }} ({{ $trx->account->type ?? '' }})</td>
                                        <td>{{ number_format($trx->debit, 0, ',', '.') }}</td>
                                        <td>{{ $trx->note }}</td>
                                        <td>{{ $trx->particular }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif
                    @empty
                    <p class="text-muted">Belum ada pembayaran untuk order ini.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('modals')
<div class="modal fade-scale" id="modalEditPayment" tabindex="-1" aria-labelledby="modalEditPaymentLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editPaymentForm">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <input type="hidden" name="transaction_group_id" id="transaction_group_id">

                    <div class="mb-3">
                        <label>Tanggal</label>
                        <input type="date" name="transaction_date" id="edit_transaction_date" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label>Paid Amount</label>
                        <input type="text" name="paid_amount" id="edit_paid_amount" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label>Cash/Bank Account</label>
                        <select name="cash_bank_account_id" id="edit_cash_bank_account_id" class="form-control">
                            <option value="">-- Pilih Akun --</option>
                            @foreach($cashAccounts as $cash)
                            <option value="{{ $cash->id }}">Cash - {{ $cash->type }}</option>
                            @endforeach
                            @foreach($bankAccounts as $bank)
                            <option value="{{ $bank->id }}">Bank - {{ $bank->type }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label>Note</label>
                        <input type="text" name="note" id="edit_note" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Update Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const editButtons = document.querySelectorAll('.btn-edit-payment');
        const form = document.getElementById('editPaymentForm');

        editButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const groupId = this.dataset.group || '';
                const date = this.dataset.date || '';
                const amount = this.dataset.amount || 0;
                const account = this.dataset.account || '';
                const note = this.dataset.note || '';

                document.getElementById('transaction_group_id').value = groupId;
                document.getElementById('edit_transaction_date').value = date;
                document.getElementById('edit_paid_amount').value = amount;
                document.getElementById('edit_cash_bank_account_id').value = account;
                document.getElementById('edit_note').value = note;

                form.action = `/erp/sales/sale-list/update-payment/${groupId}`;
            });
        });
    });

    const paidInput = document.getElementById("edit_paid_amount");

    paidInput.addEventListener("input", function() {
        let angka = this.value.replace(/\D/g, "") || "0";
        this.value = new Intl.NumberFormat('id-ID').format(angka);
    });

    document.querySelector("form").addEventListener("submit", function() {
        paidInput.value = paidInput.value.replace(/\./g, "");
    });
</script>

@endpush
