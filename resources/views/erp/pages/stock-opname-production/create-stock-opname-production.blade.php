@extends('erp.layouts.main')

@section('breadcrumb')
<div class="page-header sticky-top">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">Stock Opname</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
            <li class="breadcrumb-item">Production</li>
            <li class="breadcrumb-item">Stock Opname</li>
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
                <a href="/erp/shop-manager/owners" class="btn btn-light-brand">
                    <i class="feather-arrow-left me-2"></i>
                    <span>Back</span>
                </a>
                <button type="submit" class="btn btn-primary" form="stockOpnameForm">
                    <i class="feather-plus me-2"></i>
                    <span>Create Stock Opname</span>
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
                <form action="/erp/productions/stock-opname/store" method="POST" id="stockOpnameForm">
                    @csrf
                    @method('POST')
                    <div class="card-body">
                        <input type="hidden" name="production_warehouse_id" id="production_warehouse_id" value="2">

                        {{-- Product --}}
                        <div class="row mb-3 align-items-center">
                            <div class="col-lg-2">
                                <label for="product" class="fw-semibold">Product:</label>
                            </div>
                            <div class="col-lg-10 mb-0">
                                <select class="form-control" id="product" name="product" data-select2-selector="status">
                                    <option value="" disabled selected hidden>Pilih produk</option>
                                    @foreach ($products as $product)
                                    <option value="{{ $product->id }}">[{{ $product->sku }}] {{ $product->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Date --}}
                        <div class="row mb-3 align-items-center">
                            <div class="col-lg-2">
                                <label for="date" class="fw-semibold">Date:</label>
                            </div>
                            <div class="col-lg-10 mb-0">
                                <div class="input-group">
                                    <div class="input-group-text"><i class="feather-calendar"></i></div>
                                    <input type="date" class="form-control" id="date" name="date" value="{{ date('Y-m-d') }}">
                                </div>
                            </div>
                        </div>

                        {{-- Change --}}
                        <div class="row mb-3 align-items-center">
                            <div class="col-lg-2">
                                <label for="change" class="fw-semibold">Change:</label>
                            </div>
                            <div class="col-lg-10 mb-0">
                                <select class="form-control" id="change" name="change">
                                    <option value="" selected disabled hidden>Pilih jenis perubahan</option>
                                    <option value="available_quantity">Available Quantity</option>
                                    <option value="finished_product">Finished Product</option>
                                </select>
                            </div>
                        </div>

                        {{-- Available Quantity --}}
                        <div class="row mb-3 align-items-center change-field d-none" id="field-available_quantity">
                            <div class="col-lg-2">
                                <label for="available_quantity" class="fw-semibold">Available Quantity:</label>
                            </div>
                            <div class="col-lg-10 mb-0">
                                <div class="input-group">
                                    <div class="input-group-text"><i class="feather-box"></i></div>
                                    <input type="number" class="form-control" id="available_quantity" name="available_quantity"
                                        value="{{ old('available_quantity') }}" placeholder="Available Quantity">
                                </div>
                            </div>
                        </div>

                        {{-- Finished Product --}}
                        <div class="row mb-3 align-items-center change-field d-none" id="field-finished_product">
                            <div class="col-lg-2">
                                <label for="finished_product" class="fw-semibold">Finished Product:</label>
                            </div>
                            <div class="col-lg-10 mb-0">
                                <div class="input-group">
                                    <div class="input-group-text"><i class="feather-box"></i></div>
                                    <input type="number" class="form-control" id="finished_product" name="finished_product"
                                        value="{{ old('finished_product') }}" placeholder="Finished Product">
                                </div>
                            </div>
                        </div>

                        {{-- Status --}}
                        <div class="row mb-3 align-items-center">
                            <div class="col-lg-2">
                                <label for="status" class="fw-semibold">Status:</label>
                            </div>
                            <div class="col-lg-10 mb-0">
                                <select class="form-control" id="status" name="status" data-select2-selector="status">
                                    <option value="Gain">Gain</option>
                                    <option value="Loss">Loss</option>
                                </select>
                            </div>
                        </div>

                        {{-- Notes --}}
                        <div class="row mb-3 align-items-center">
                            <div class="col-lg-2">
                                <label for="notes" class="fw-semibold">Notes:</label>
                            </div>
                            <div class="col-lg-10 mb-0">
                                <div class="input-group">
                                    <div class="input-group-text"><i class="feather-box"></i></div>
                                    <input type="text" class="form-control" id="notes" name="notes" value="{{ old('notes') }}" placeholder="Notes">
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
    const changeSelect = document.getElementById('change');
    const fields = document.querySelectorAll('.change-field');

    changeSelect.addEventListener('change', function() {
        fields.forEach(field => field.classList.add('d-none'));
        const selected = this.value;
        if (selected) {
            document.getElementById('field-' + selected).classList.remove('d-none');
        }
    });

    document.getElementById('stockOpnameForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const form = this;
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

        let isValid = true;

        // validate product
        const product = document.getElementById('product');
        if (!product.value) {
            showError(product, 'Product wajib dipilih');
            isValid = false;
        }

        // validate date
        const date = document.getElementById('date');
        if (!date.value) {
            showError(date, 'Tanggal wajib diisi');
            isValid = false;
        }

        // validate change
        const changeVal = changeSelect.value;
        if (!changeVal) {
            showError(changeSelect, 'Change wajib dipilih');
            isValid = false;
        } else {
            const input = document.querySelector(`#field-${changeVal} input`);
            if (!input.value.trim()) {
                const label = changeVal.replace('_', ' ');
                showError(input, `${label} wajib diisi`);
                isValid = false;
            }
        }

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