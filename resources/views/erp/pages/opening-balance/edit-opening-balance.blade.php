@extends('erp.layouts.main')

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Opening Balance</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Opening Balance</li>
                <li class="breadcrumb-item">Create Opening Balance</li>
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
                    <a href="/erp/accounts/opening-balance" class="btn btn-light-brand">
                        <i class="feather-arrow-left me-2"></i>
                        <span>Back</span>
                    </a>
                    <button type="submit" class="btn btn-primary" form="openingBalanceForm">
                        <i class="feather-plus me-2"></i>
                        <span>Add Opening Balance</span>
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
                    <form action="/erp/accounts/opening-balance/update" method="POST" id="openingBalanceForm">
                        @csrf
                        @method('PUT')
                        <div class="card-body px-0 py-4">
                            <div class="row px-4 mb-3">
                                <div class="col-lg-12">
                                    <h4>Opening Balance</h4>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table" id="openingBalanceList">
                                    <thead>
                                        <tr>
                                            <th>Particular</th>
                                            <th>Debit</th>
                                            <th>Credit</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="3" class="fw-bold bg-secondary text-white">Bank</td>
                                        </tr>
                                        @foreach ($bankAccounts as $account)
                                            <tr>
                                                <td>
                                                    {{ $account->type }}
                                                    <input type="hidden" name="accounts[{{ $account->id }}][account]"
                                                        value="{{ $account->id }}">
                                                </td>
                                                <td>
                                                    <input type="text" inputmode="numeric" class="form-control"
                                                        name="accounts[{{ $account->id }}][debit]"
                                                        value="{{ $account->opening_debit }}">
                                                </td>
                                                <td>
                                                    <input type="text" inputmode="numeric" class="form-control"
                                                        name="accounts[{{ $account->id }}][credit]"
                                                        value="{{ $account->opening_credit }}">
                                                </td>
                                            </tr>
                                        @endforeach
                                        <tr>
                                            <td colspan="3" class="fw-bold bg-secondary text-white">Cash</td>
                                        </tr>
                                        @foreach ($cashAccounts as $account)
                                            <tr>
                                                <td>
                                                    {{ $account->type }}
                                                    <input type="hidden" name="accounts[{{ $account->id }}][account]"
                                                        value="{{ $account->id }}">
                                                </td>
                                                <td>
                                                    <input type="text" inputmode="numeric" class="form-control"
                                                        name="accounts[{{ $account->id }}][debit]"
                                                        value="{{ $account->opening_debit }}">
                                                </td>
                                                <td>
                                                    <input type="text" inputmode="numeric" class="form-control"
                                                        name="accounts[{{ $account->id }}][credit]"
                                                        value="{{ $account->opening_credit }}">
                                                </td>
                                            </tr>
                                        @endforeach
                                        <tr>
                                            <td colspan="3" class="fw-bold bg-secondary text-white">Purchase</td>
                                        </tr>
                                        @foreach ($purchaseAccounts as $account)
                                            <tr>
                                                <td>
                                                    {{ $account->type }}
                                                    <input type="hidden" name="accounts[{{ $account->id }}][account]"
                                                        value="{{ $account->id }}">
                                                </td>
                                                <td>
                                                    <input type="text" inputmode="numeric" class="form-control"
                                                        name="accounts[{{ $account->id }}][debit]"
                                                        value="{{ $account->opening_debit }}">
                                                </td>
                                                <td>
                                                    <input type="text" inputmode="numeric" class="form-control"
                                                        name="accounts[{{ $account->id }}][credit]"
                                                        value="{{ $account->opening_credit }}">
                                                </td>
                                            </tr>
                                        @endforeach
                                        <tr>
                                            <td colspan="3" class="fw-bold bg-secondary text-white">Sales</td>
                                        </tr>
                                        @foreach ($saleAccounts as $account)
                                            <tr>
                                                <td>
                                                    {{ $account->type }}
                                                    <input type="hidden" name="accounts[{{ $account->id }}][account]"
                                                        value="{{ $account->id }}">
                                                </td>
                                                <td>
                                                    <input type="text" inputmode="numeric" class="form-control"
                                                        name="accounts[{{ $account->id }}][debit]"
                                                        value="{{ $account->opening_debit }}">
                                                </td>
                                                <td>
                                                    <input type="text" inputmode="numeric" class="form-control"
                                                        name="accounts[{{ $account->id }}][credit]"
                                                        value="{{ $account->opening_credit }}">
                                                </td>
                                            </tr>
                                        @endforeach
                                        <tr>
                                            <td colspan="3" class="fw-bold bg-secondary text-white">Expense</td>
                                        </tr>
                                        @foreach ($expenseAccounts as $account)
                                            <tr>
                                                <td>
                                                    {{ $account->type }}
                                                    <input type="hidden" name="accounts[{{ $account->id }}][account]"
                                                        value="{{ $account->id }}">
                                                </td>
                                                <td>
                                                    <input type="text" inputmode="numeric" class="form-control"
                                                        name="accounts[{{ $account->id }}][debit]"
                                                        value="{{ $account->opening_debit }}">
                                                </td>
                                                <td>
                                                    <input type="text" inputmode="numeric" class="form-control"
                                                        name="accounts[{{ $account->id }}][credit]"
                                                        value="{{ $account->opening_credit }}">
                                                </td>
                                            </tr>
                                        @endforeach
                                        <tr>
                                            <td colspan="3" class="fw-bold bg-secondary text-white">Capital</td>
                                        </tr>
                                        @foreach ($capitalAccounts as $account)
                                            <tr>
                                                <td>
                                                    {{ $account->type }}
                                                    <input type="hidden" name="accounts[{{ $account->id }}][account]"
                                                        value="{{ $account->id }}">
                                                </td>
                                                <td>
                                                    <input type="text" inputmode="numeric" class="form-control"
                                                        name="accounts[{{ $account->id }}][debit]"
                                                        value="{{ $account->opening_debit }}">
                                                </td>
                                                <td>
                                                    <input type="text" inputmode="numeric" class="form-control"
                                                        name="accounts[{{ $account->id }}][credit]"
                                                        value="{{ $account->opening_credit }}">
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
    </div>
@endsection

@push('scripts')
    <script>
        document.getElementById('openingBalanceForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const form = this;
            form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

            const rules = [];
            let isValid = true;

            rules.forEach(rule => {
                const el = form.querySelector(rule.selector);
                const val = el?.value ?? '';
                const valid = rule.validate ? rule.validate(val) : val.trim() !== '';
                if (!valid) {
                    showError(el, rule.message);
                    isValid = false;
                }
            });

            if (isValid) form.submit();
        });

        function showError(input, message) {
            if (!input) return;
            input.classList.add('is-invalid');
            const parent = input.closest('div');
            if (!parent) return;
            const feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            feedback.textContent = message;
            parent.appendChild(feedback);
        }

        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('openingBalanceForm');

            function formatNumber(n) {
                if (n === null || n === undefined) return '0';
                n = n.toString().split('.')[0];
                n = n.replace(/[^0-9]/g, '');
                if (n === '') return '0';
                return n.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            }

            document.querySelectorAll('input[name*="[debit]"], input[name*="[credit]"]').forEach(input => {
                const raw = input.value ? input.value.toString().split('.')[0] : '0';
                input.value = formatNumber(raw);
            });

            document.addEventListener('focusin', function(e) {
                if (e.target.matches('input[name*="[debit]"], input[name*="[credit]"]')) {
                    if (e.target.value === '0') e.target.value = '';
                }
            });

            document.addEventListener('focusout', function(e) {
                if (e.target.matches('input[name*="[debit]"], input[name*="[credit]"]')) {
                    if (e.target.value.trim() === '') e.target.value = '0';
                }
            });

            document.addEventListener('input', function(e) {
                if (e.target.matches('input[name*="[debit]"], input[name*="[credit]"]')) {
                    const pos = e.target.selectionStart;
                    const raw = e.target.value.replace(/[^\d]/g, '');
                    e.target.value = formatNumber(raw);
                    const diff = e.target.value.length - raw.length;
                    e.target.setSelectionRange(pos + diff, pos + diff);
                }
            });

            form.addEventListener('submit', function(e) {
                document.querySelectorAll('input[name*="[debit]"], input[name*="[credit]"]').forEach(
                    input => {
                        input.value = input.value.replace(/\./g, '');
                    });
            });
        });
    </script>
@endpush
