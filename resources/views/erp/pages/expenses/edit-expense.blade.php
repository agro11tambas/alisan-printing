@extends('erp.layouts.main')

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Expense</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Expense</li>
                <li class="breadcrumb-item">Create Expense</li>
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
                    <a href="/erp/expenses" class="btn btn-light-brand">
                        <i class="feather-arrow-left me-2"></i>
                        <span>Back</span>
                    </a>
                    <button type="submit" class="btn btn-primary" form="expenseForm">
                        <i class="feather-plus me-2"></i>
                        <span>Edit Expense</span>
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
            <div class="col-lg-12">
                <div class="card stretch stretch-full">
                    <form action="/erp/expenses/update/{{ $expense->transaction_group_id }}" method="POST"
                        id="expenseForm">
                        @csrf
                        @method('PUT')
                        <div class="card-body">
                            <div class="row mb-3 align-items-center">
                                <div class="col-lg-2">
                                    <label for="transaction_date" class="fw-semibold">Transaction Date:</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <div class="input-group">
                                        <input type="date" class="form-control" id="transaction_date"
                                            name="transaction_date"
                                            value="{{ old('transaction_date', $expense->transaction_date ? \Carbon\Carbon::parse($expense->transaction_date)->format('Y-m-d') : '') }}">
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-3 align-items-center">
                                <div class="col-lg-2">
                                    <label for="transaction_type" class="fw-semibold">Expense Type:</label>
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
                                        <select class="form-select form-control max-select" data-select2-selector="tag"
                                            id="transaction_type" name="transaction_type">
                                            <option disabled selected hidden>Choose Transaction Type</option>
                                            @foreach ($transactionTypes as $index => $transactionType)
                                                @php
                                                    $bg = $bgColors[$loop->index % count($bgColors)];
                                                @endphp
                                                <option value="{{ $transactionType->id }}" data-bg="{{ $bg }}"
                                                    {{ $transactionType->id == $expense->account_id ? 'selected' : '' }}>
                                                    {{ $transactionType->type }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-3 align-items-center">
                                <div class="col-lg-2">
                                    <label for="cash_bank_account_id" class="fw-semibold">Cash/Bank Account:</label>
                                </div>
                                <div class="col-lg-10">
                                    <select class="form-control" name="cash_bank_account_id">
                                        <option disabled hidden>Pilih Bank atau Cash Account</option>
                                        @foreach ($cashAccounts as $cash)
                                            <option value="{{ $cash->id }}"
                                                {{ $cash->id == $creditTransaction->account_id ? 'selected' : '' }}>
                                                Cash - {{ $cash->type }}
                                            </option>
                                        @endforeach
                                        @foreach ($bankAccounts as $bank)
                                            <option value="{{ $bank->id }}"
                                                {{ $bank->id == $creditTransaction->account_id ? 'selected' : '' }}>
                                                Bank - {{ $bank->type }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-3 align-items-center">
                                <div class="col-lg-2">
                                    <label for="debit" class="fw-semibold">Debit:</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <div class="input-group">
                                        <div class="input-group-text"><i class="feather-dollar-sign"></i></div>
                                        <input type="text" class="form-control" id="debit" name="debit"
                                            value="{{ old('debit', $expense->debit) }}" placeholder="amount">
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-3 align-items-center">
                                <div class="col-lg-2">
                                    <label for="note" class="fw-semibold">Note:</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <div class="input-group">
                                        <div class="input-group-text"><i class="feather-type"></i></div>
                                        <textarea name="note" id="note" cols="30" rows="5" placeholder="note" class="form-control"
                                            value="{{ old('note') }}">{{ strip_tags(old('note', $expense->note)) }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const debitField = document.getElementById('debit');

            function formatNumber(n) {
                n = n.toString().replace(/[^0-9.]/g, '');
                n = n.split('.')[0];

                if (n === '') return '0';

                return n.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            }

            if (debitField && debitField.value) {
                if (!debitField.value.includes(',')) {
                    debitField.value = formatNumber(debitField.value);
                }
            }

            debitField.addEventListener('focus', function() {
                if (this.value === '0') this.value = '';
            });

            debitField.addEventListener('blur', function() {
                if (this.value.trim() === '') this.value = '0';
            });

            debitField.addEventListener('input', function() {
                const pos = this.selectionStart;
                const raw = this.value.replace(/[^\d]/g, '');
                this.value = formatNumber(raw);
                const diff = this.value.length - raw.length;
                this.setSelectionRange(pos + diff, pos + diff);
            });

            const form = document.getElementById('expenseForm');
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

                const required = [{
                        selector: '#transaction_date',
                        msg: 'Tanggal Transaksi wajib diisi'
                    },
                    {
                        selector: '#debit',
                        msg: 'Amount wajib diisi'
                    },
                    {
                        selector: '#transaction_type',
                        msg: 'Expense Type wajib dipilih'
                    },
                    {
                        selector: '[name="cash_bank_account_id"]',
                        msg: 'Cash/Bank Account wajib dipilih'
                    }
                ];

                let ok = true;
                required.forEach(r => {
                    const el = form.querySelector(r.selector);
                    if (!el || el.value.trim() === '') {
                        showError(el, r.msg);
                        ok = false;
                    }
                });

                if (!ok) return;

                debitField.value = debitField.value.replace(/\./g, '');

                form.submit();
            });

            function showError(el, msg) {
                if (!el) return;
                el.classList.add('is-invalid');
                const fb = document.createElement('div');
                fb.className = 'invalid-feedback';
                fb.textContent = msg;
                (el.closest('.input-group') || el.parentNode).appendChild(fb);
            }
        });
    </script>
@endpush
