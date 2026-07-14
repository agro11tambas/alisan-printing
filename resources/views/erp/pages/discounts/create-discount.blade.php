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
                <li class="breadcrumb-item">Create Discount</li>
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
                        <span>Add Discount</span>
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
                    <form action="/erp/discounts/store" method="POST" id="discountForm">
                        @csrf
                        @method('POST')
                        <div class="card-body">
                            <div class="row mb-2 align-items-center">
                                <div class="col-lg-2">
                                    <label for="name" class="fw-semibold">Discount Name:</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <div class="input-group">
                                        <div class="input-group-text"><i class="feather-dollar-sign"></i></div>
                                        <input type="text" class="form-control" id="name" name="name"
                                            value="{{ old('name') }}" placeholder="name">
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
                                            <option value="Percentage" data-bg="bg-success">Percentage</option>
                                            <option value="Fixed Amount" data-bg="bg-primary">Fixed Amount</option>
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
                                            value="{{ old('amount') }}" placeholder="amount">
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
                                            <option value="Quantity of Items" data-bg="bg-success">Quantity of Items
                                            </option>
                                            <option value="Purchase Amount" data-bg="bg-primary">Purchase Amount</option>
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
                                            name="minimum_qty_or_amount" value="{{ old('minimum_qty_or_amount') }}"
                                            placeholder="minimum_qty_or_amount">
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-2 align-items-center">
                                <div class="col-lg-2">
                                    <label for="apply_on" class="fw-semibold">Apply On:</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <select id="apply_on" name="apply_on" class="form-control">
                                        <option value="" disabled selected hidden>Choose Apply Type</option>
                                        <option value="Product">Product</option>
                                        <option value="Category">Product Category</option>
                                    </select>
                                </div>
                            </div>
                            <div id="product_group" class="row mb-2 align-items-center" style="display: none;">
                                <div class="col-lg-2">
                                    <label for="products" class="fw-semibold">Select Product(s):</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <select name="products[]" id="products" class="form-control"
                                        data-select2-selector="tag" multiple>
                                        @foreach ($products as $product)
                                            <option value="{{ $product->id }}">{{ $product->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div id="category_group" class="row mb-2 align-items-center" style="display: none;">
                                <div class="col-lg-2">
                                    <label for="categories" class="fw-semibold">Select Category(ies):</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <select name="categories[]" id="categories" class="form-control"
                                        data-select2-selector="tag" multiple>
                                        @foreach ($categories as $category)
                                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                                        @endforeach
                                    </select>
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
                                            <option disabled selected hidden>Choose Status</option>
                                            <option value="1" data-bg="bg-success">Active</option>
                                            <option value="0" data-bg="bg-primary">Inactive</option>
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
        document.addEventListener('DOMContentLoaded', function() {
            const applySelect = document.getElementById('apply_on');
            const productGroup = document.getElementById('product_group');
            const categoryGroup = document.getElementById('category_group');

            function toggleApplyOn() {
                productGroup.style.display = 'none';
                categoryGroup.style.display = 'none';

                if (applySelect.value === 'Product') {
                    productGroup.style.display = 'flex';
                } else if (applySelect.value === 'Category') {
                    categoryGroup.style.display = 'flex';
                }
            }

            applySelect.addEventListener('change', toggleApplyOn);
            
            toggleApplyOn();
        });

        document.getElementById('discountForm').addEventListener('submit', function(e) {
            e.preventDefault();

            let isValid = true;

            this.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            this.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

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
                    selector: 'select[name="apply_on"]',
                    message: 'Apply On wajib dipilih'
                },
                {
                    selector: 'select[name="status"]',
                    message: 'Status wajib dipilih'
                },
            ];

            rules.forEach(rule => {
                const el = this.querySelector(rule.selector);
                if (!el) return;

                let val = el.value;

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

            if (isValid) this.submit();
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
