@extends('erp.layouts.main')

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Discount</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Discount</li>
                <li class="breadcrumb-item">Edit Discount</li>
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
                    <a href="/erp/discounts" class="btn btn-light-brand">
                        <i class="feather-arrow-left me-2"></i>
                        <span>Back</span>
                    </a>
                    <button type="submit" class="btn btn-primary" form="discountForm">
                        <i class="feather-plus me-2"></i>
                        <span>Edit Discount</span>
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
                    <form action="/erp/discounts/update/{{ $discount->id }}" method="POST" id="discountForm">
                        @csrf
                        @method('PUT')
                        <div class="card-body">
                            <div class="row mb-2 align-items-center">
                                <div class="col-lg-2">
                                    <label for="name" class="fw-semibold">Discount Name:</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <div class="input-group">
                                        <div class="input-group-text"><i class="feather-dollar-sign"></i></div>
                                        <input type="text" class="form-control" id="name" name="name"
                                            value="{{ old('name', $discount->name) }}" placeholder="name">
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-2 align-items-center">
                                <div class="col-lg-2">
                                    <label for="type" class="fw-semibold">Discount Type:</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <div class="input-group">
                                        <select class="form-select form-control max-select" data-select2-selector="tag"
                                            id="type" name="type">
                                            <option disabled selected hidden>Choose Transaction Type</option>
                                            <option value="Percentage" data-bg="bg-success"
                                                {{ $discount->type == 'Percentage' ? 'selected' : '' }}>Percentage</option>
                                            <option value="Fixed Amount" data-bg="bg-primary"
                                                {{ $discount->type == 'Fixed Amount' ? 'selected' : '' }}>Fixed Amount
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-2 align-items-center">
                                <div class="col-lg-2">
                                    <label for="amount" class="fw-semibold">Amount:</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <div class="input-group">
                                        <div class="input-group-text"><i class="feather-dollar-sign"></i></div>
                                        <input type="text" class="form-control" id="amount" name="amount"
                                            value="{{ old('amount', $discount->amount) }}" placeholder="amount">
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-2 align-items-center">
                                <div class="col-lg-2">
                                    <label for="minimum_based_on" class="fw-semibold">Discount Type:</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <div class="input-group">
                                        <select class="form-select form-control max-select" data-select2-selector="tag"
                                            id="minimum_based_on" name="minimum_based_on">
                                            <option disabled selected hidden>Choose Minimum Amount Based On</option>
                                            <option value="Quantity of Items" data-bg="bg-success"
                                                {{ $discount->minimum_based_on == 'Quantity of Items' ? 'selected' : '' }}>
                                                Quantity of Items</option>
                                            <option value="Purchase Amount" data-bg="bg-primary"
                                                {{ $discount->minimum_based_on == 'Purchase Amount' ? 'selected' : '' }}>
                                                Purchase Amount</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-2 align-items-center">
                                <div class="col-lg-2">
                                    <label for="minimum_qty_or_amount" class="fw-semibold">Minimum Qty or Purchase
                                        Amount:</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <div class="input-group">
                                        <div class="input-group-text"><i class="feather-dollar-sign"></i></div>
                                        <input type="text" class="form-control" id="minimum_qty_or_amount"
                                            name="minimum_qty_or_amount"
                                            value="{{ old('minimum_qty_or_amount', $discount->minimum_qty_or_amount) }}"
                                            placeholder="minimum_qty_or_amount">
                                    </div>
                                </div>
                            </div>
                            @php($selectedCategories = old('categories', $discount->categories->pluck('id')->all()))
                            @php($selectedModes = old('price_modes', $discount->priceModes->pluck('id')->all()))
                            <div id="category_group" class="row mb-2 align-items-center">
                                <div class="col-lg-2">
                                    <label for="categories" class="fw-semibold">Select Category(ies):</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <select name="categories[]" id="categories" class="form-control"
                                        data-select2-selector="tag" multiple>
                                        @foreach ($categories as $category)
                                            <option value="{{ $category->id }}"
                                                {{ in_array($category->id, (array) $selectedCategories) ? 'selected' : '' }}>
                                                {{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div id="mode_group" class="row mb-2 align-items-center">
                                <div class="col-lg-2">
                                    <label for="price_modes" class="fw-semibold">Select Mode(s):</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <select name="price_modes[]" id="price_modes" class="form-control"
                                        data-select2-selector="tag" multiple>
                                        @foreach ($priceModes as $priceMode)
                                            <option value="{{ $priceMode->id }}"
                                                {{ in_array($priceMode->id, (array) $selectedModes) ? 'selected' : '' }}>
                                                {{ $priceMode->name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="fs-11 text-muted mt-1">
                                        Diskon berlaku untuk baris yang memenuhi <strong>kedua</strong> syarat:
                                        produknya masuk kategori terpilih <strong>dan</strong> modenya termasuk
                                        mode terpilih.
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-2 align-items-center">
                                <div class="col-lg-2">
                                    <label for="status" class="fw-semibold">Status:</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <div class="input-group">
                                        <select class="form-select form-control max-select" data-select2-selector="tag"
                                            id="status" name="status">
                                            <option disabled {{ !isset($discount->status) ? 'selected' : '' }}>Choose
                                                Status</option>
                                            <option value="1" data-bg="bg-success"
                                                {{ $discount->is_active == 1 ? 'selected' : '' }}>Active</option>
                                            <option value="0" data-bg="bg-primary"
                                                {{ $discount->is_active == 0 ? 'selected' : '' }}>Inactive</option>
                                        </select>
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
        document.getElementById('discountForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const form = this;
            form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

            const rules = [{
                    selector: 'input[name="name"]',
                    message: 'Discount Name wajib diisi'
                },
                {
                    selector: 'select[name="type"]',
                    message: 'Discount Type wajib dipilih'
                },
                {
                    selector: 'input[name="amount"]',
                    message: 'Amount wajib diisi'
                },
                {
                    selector: 'select[name="minimum_based_on"]',
                    message: 'Minimum Based On wajib dipilih'
                },
                {
                    selector: 'input[name="minimum_qty_or_amount"]',
                    message: 'Minimum Qty or Amount wajib diisi'
                },
                {
                    selector: 'select[name="status"]',
                    message: 'Status wajib dipilih'
                },
            ];

            let isValid = true;

            rules.forEach(rule => {
                const el = form.querySelector(rule.selector);
                if (!el) return;

                const val = el.value;

                if (el.tagName === 'SELECT') {
                    const selectedOption = el.options[el.selectedIndex];
                    const isDisabled = selectedOption?.disabled || selectedOption?.hidden;

                    if (isDisabled || val === '' || val === null) {
                        showError(el, rule.message);
                        isValid = false;
                    }
                } else {
                    if (val === null || val.trim() === '') {
                        showError(el, rule.message);
                        isValid = false;
                    }
                }
            });

            // Scope-nya baku: kategori dan mode dua-duanya wajib diisi.
            const targetRules = [
                ['select[name="categories[]"]', 'Product Category wajib dipilih minimal satu'],
                ['select[name="price_modes[]"]', 'Mode wajib dipilih minimal satu'],
            ];

            targetRules.forEach(([selector, message]) => {
                const el = form.querySelector(selector);
                if (el && el.selectedOptions.length === 0) {
                    showError(el, message);
                    isValid = false;
                }
            });

            if (isValid) form.submit();
        });

        function showError(input, message) {
            if (!input) return;
            input.classList.add('is-invalid');
            const parent = input.closest('.input-group') || input.parentNode;
            const feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            feedback.textContent = message;
            parent.appendChild(feedback);
        }
    </script>
@endpush
