@extends('erp.layouts.main')

@section('breadcrumb')
<div class="page-header sticky-top">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">Account</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
            <li class="breadcrumb-item">Account</li>
            <li class="breadcrumb-item">Create Account</li>
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
                <a href="/erp/accounts" class="btn btn-light-brand">
                    <i class="feather-arrow-left me-2"></i>
                    <span>Back</span>
                </a>
                <button type="submit" class="btn btn-primary" form="accountForm">
                    <i class="feather-plus me-2"></i>
                    <span>Add Account</span>
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
    <div class="row">
        <div class="col-lg-12">
            <div class="card stretch stretch-full">
                <form action="/erp/accounts/update/{{ $account->id }}" method="POST" id="accountForm">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="row mb-3 align-items-center">
                            <div class="col-lg-2">
                                <label for="name" class="fw-semibold">Account Name:</label>
                            </div>
                            <div class="col-lg-10 mb-0">
                                <div class="input-group">
                                    <select class="form-select form-control max-select" data-select2-selector="tag" id="name" name="name">
                                        <option disabled selected hidden>Choose Transaction Type</option>
                                        <option value="Bank" data-bg="bg-success" {{ $account->name == 'Bank' ? 'selected' : '' }}>Bank</option>
                                        <option value="Cash" data-bg="bg-primary" {{ $account->name == 'Cash' ? 'selected' : '' }}>Cash</option>
                                        <option value="Sale" data-bg="bg-dark" {{ $account->name == 'Sale' ? 'selected' : '' }}>Sale</option>
                                        <option value="Purchase" data-bg="bg-teal" {{ $account->name == 'Purchase' ? 'selected' : '' }}>Purchase</option>
                                        <option value="Expense" data-bg="bg-warning" {{ $account->name == 'Expense' ? 'selected' : '' }}>Expense</option>
                                        <option value="Capital" data-bg="bg-danger" {{ $account->name == 'Capital' ? 'selected' : '' }}>Capital</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3 align-items-center">
                            <div class="col-lg-2">
                                <label for="type" class="fw-semibold">Account Type:</label>
                            </div>
                            <div class="col-lg-10 mb-0">
                                <div class="input-group">
                                    <input type="text" class="form-control" id="type" name="type" value="{{ old('type', $account->type) }}">
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
    document.getElementById('accountForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const form = this;
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

        const rules = [{
                selector: 'input[name="name"]',
                message: 'Account Name wajib diisi'
            },
            {
                selector: 'input[name="type"]',
                message: 'Account Type wajib diisi'
            },
        ];

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
</script>
@endpush
