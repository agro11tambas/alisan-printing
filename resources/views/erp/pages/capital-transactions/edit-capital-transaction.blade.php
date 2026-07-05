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
                    <button type="submit" class="btn btn-primary" form="capitalTransactionsForm">
                        <i class="feather-plus me-2"></i>
                        <span>Add Expense</span>
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
    <div class="main-content m-0 m-md-2 m-lg-2 p-0 p-md-0 p-lg-0 pt-1 pt-md-0">
        <div class="row">
            <div class="col-lg-12">
                <div class="card stretch stretch-full">
                    <form action="/erp/capital-transactions/update/{{ $capitalTransaction->transaction_group_id }}"
                        method="POST" id="capitalTransactionsForm">
                        @csrf
                        @method('PUT')
                        <div class="card-body">
                            <div class="row mb-2 align-items-center">
                                <div class="col-lg-2">
                                    <label for="transaction_date" class="fw-semibold">Transaction Date:</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <div class="input-group">
                                        <input type="date" class="form-control" id="transaction_date"
                                            name="transaction_date"
                                            value="{{ old('transaction_date', $capitalTransaction->transaction_date ? \Carbon\Carbon::parse($capitalTransaction->transaction_date)->format('Y-m-d') : '') }}">
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-2 align-items-center">
                                <div class="col-lg-2">
                                    <label for="transaction_type" class="fw-semibold">Account Type:</label>
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
                                                    {{ $transactionType->id == $capitalTransaction->account_id ? 'selected' : '' }}>
                                                    {{ $transactionType->type }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-2 align-items-center">
                                <div class="col-lg-2">
                                    <label for="cash_bank_account_id" class="fw-semibold">Cash/Bank Account:</label>
                                </div>
                                <div class="col-lg-10">
                                    <select class="form-control" name="cash_bank_account_id">
                                        <option disabled hidden>Pilih Bank atau Cash Account</option>
                                        @foreach ($cashAccounts as $cash)
                                            <option value="{{ $cash->id }}"
                                                {{ $cash->id == $debitTransaction->account_id ? 'selected' : '' }}>
                                                Cash - {{ $cash->type }}
                                            </option>
                                        @endforeach
                                        @foreach ($bankAccounts as $bank)
                                            <option value="{{ $bank->id }}"
                                                {{ $bank->id == $debitTransaction->account_id ? 'selected' : '' }}>
                                                Bank - {{ $bank->type }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-2 align-items-center">
                                <div class="col-lg-2">
                                    <label for="credit" class="fw-semibold">Credit:</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <div class="input-group">
                                        <div class="input-group-text"><i class="feather-dollar-sign"></i></div>
                                        <input type="text" class="form-control" id="credit" name="credit"
                                            value="{{ old('credit', $capitalTransaction->credit) }}" placeholder="amount">
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-2 align-items-center">
                                <div class="col-lg-2">
                                    <label for="note" class="fw-semibold">Note:</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <div class="input-group">
                                        <div class="input-group-text"><i class="feather-type"></i></div>
                                        <textarea name="note" id="note" cols="30" rows="5" placeholder="note" class="form-control"
                                            value="{{ old('note') }}">{{ strip_tags(old('note', $capitalTransaction->note)) }}</textarea>
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

            const creditField = document.getElementById('credit');

            function formatNumber(n) {
                if (n === null || n === undefined || n === '') return '0';

                n = n.toString().split('.')[0].replace(/[^0-9]/g, '');

                if (n === '') return '0';

                return n.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            }

            if (creditField && creditField.value) {
                creditField.value = formatNumber(creditField.value);
            } else {
                creditField.value = '0';
            }

            creditField.addEventListener('focus', function() {
                if (this.value === '0') this.value = '';
            });

            creditField.addEventListener('blur', function() {
                if (this.value.trim() === '') this.value = '0';
            });

            creditField.addEventListener('input', function() {
                const pos = this.selectionStart;
                const raw = this.value.replace(/[^\d]/g, '');
                this.value = formatNumber(raw);
                const diff = this.value.length - raw.length;
                this.setSelectionRange(pos + diff, pos + diff);
            });

            const form = document.getElementById('capitalTransactionsForm');
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

                const required = [{
                        selector: '#transaction_date',
                        msg: 'Tanggal Transaksi wajib diisi'
                    },
                    {
                        selector: '#credit',
                        msg: 'Amount wajib diisi'
                    },
                    {
                        selector: '#transaction_type',
                        msg: 'Account Type wajib dipilih'
                    },
                    {
                        selector: '[name="cash_bank_account_id"]',
                        msg: 'Cash/Bank Account wajib dipilih'
                    },
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

                creditField.value = creditField.value.replace(/\./g, '');

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
