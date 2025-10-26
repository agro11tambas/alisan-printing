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
                <li class="breadcrumb-item">Create Capital Transaction</li>
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
    <div class="main-content">
        <div class="row">
            <div class="col-lg-12">
                <div class="card stretch stretch-full">
                    <form action="/erp/capital-transactions/store" method="POST" id="capitalTransactionsForm">
                        @csrf
                        @method('POST')
                        <div class="card-body">
                            <div class="row mb-3 align-items-center">
                                <div class="col-lg-2">
                                    <label for="transaction_date" class="fw-semibold">Transaction Date:</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <div class="input-group">
                                        <input type="date" class="form-control" id="transaction_date"
                                            name="transaction_date" value="{{ date('Y-m-d') }}">
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-3 align-items-center">
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
                                                <option value="{{ $transactionType->id }}" data-bg="{{ $bg }}">
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
                                            name="cash_bank_account_id" id="cash_bank_account_id">
                                            <option value="" disabled selected hidden>Pilih Bank atau Cash Account
                                            </option>
                                            @foreach ($cashAccounts as $cash)
                                                @php
                                                    $bg = $bgColors[$loop->index % count($bgColors)];
                                                @endphp
                                                <option value="{{ $cash->id }}" data-bg="{{ $bg }}">Cash -
                                                    {{ $cash->type }}</option>
                                            @endforeach
                                            @foreach ($bankAccounts as $bank)
                                                @php
                                                    $bg = $bgColors[$loop->index % count($bgColors)];
                                                @endphp
                                                <option value="{{ $bank->id }}" data-bg="{{ $bg }}">Bank -
                                                    {{ $bank->type }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-3 align-items-center">
                                <div class="col-lg-2">
                                    <label for="credit" class="fw-semibold">Credit:</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <div class="input-group">
                                        <div class="input-group-text"><i class="feather-dollar-sign"></i></div>
                                        <input type="text" class="form-control" id="credit" name="credit"
                                            value="{{ old('credit') }}" placeholder="amount">
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
                                            value="{{ old('note') }}"></textarea>
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

            // === Fungsi format angka tanpa desimal ===
            function formatNumber(n) {
                n = n.toString().replace(/[^0-9.]/g, ''); // sisakan angka & titik
                n = n.split('.')[0]; // ambil angka sebelum titik aja
                if (n === '') return '0';
                return n.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            }

            // === Normalisasi nilai awal (hindari double-format) ===
            if (creditField && creditField.value) {
                // kalau dari server udah ada titik, jangan format lagi
                if (!creditField.value.includes('.')) {
                    creditField.value = formatNumber(creditField.value);
                }
            }

            // === Hapus nol otomatis saat fokus ===
            creditField.addEventListener('focus', function() {
                if (this.value === '0') this.value = '';
            });

            // === Balik ke nol kalau kosong ===
            creditField.addEventListener('blur', function() {
                if (this.value.trim() === '') this.value = '0';
            });

            // === Format ribuan realtime ===
            creditField.addEventListener('input', function() {
                const cursorPos = this.selectionStart;
                const raw = this.value.replace(/[^\d]/g, '');
                this.value = formatNumber(raw);
                const diff = this.value.length - raw.length;
                this.setSelectionRange(cursorPos + diff, cursorPos + diff);
            });

            // === VALIDASI DAN HAPUS KOMA SAAT SUBMIT ===
            const form = document.getElementById('capitalTransactionsForm');
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                // Hapus error lama
                form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

                // Validasi wajib isi
                const rules = [{
                        selector: 'input[name="transaction_date"]',
                        message: 'Tanggal Transaksi wajib diisi'
                    },
                    {
                        selector: 'input[name="credit"]',
                        message: 'Amount wajib diisi'
                    },
                    {
                        selector: 'select[name="transaction_type"]',
                        message: 'Account Type wajib dipilih'
                    },
                    {
                        selector: 'select[name="cash_bank_account_id"]',
                        message: 'Cash/Bank Account wajib dipilih'
                    }
                ];

                let isValid = true;
                rules.forEach(rule => {
                    const el = form.querySelector(rule.selector);
                    const val = el?.value ?? '';
                    if (!val.trim()) {
                        showError(el, rule.message);
                        isValid = false;
                    }
                });

                if (!isValid) return;

                // === Hapus titik sebelum submit ke server ===
                creditField.value = creditField.value.replace(/\./g, '');

                form.submit();
            });

            function showError(input, message) {
                if (!input) return;
                input.classList.add('is-invalid');
                const parent = input.closest('.input-group') || input.closest('div');
                if (!parent) return;
                const feedback = document.createElement('div');
                feedback.className = 'invalid-feedback';
                feedback.textContent = message;
                parent.appendChild(feedback);
            }

        });
    </script>
@endpush
