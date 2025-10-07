@extends('erp.layouts.main')

@section('breadcrumb')
<div class="page-header sticky-top">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">Edit Stock Opname</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
            <li class="breadcrumb-item">Production</li>
            <li class="breadcrumb-item">Stock Opname</li>
        </ul>
    </div>
    <div class="page-header-right ms-auto">
        <div class="d-flex align-items-center gap-2 page-header-right-items-wrapper">
            <a href="/erp/productions/stock-opname" class="btn btn-light-brand">
                <i class="feather-arrow-left me-2"></i>
                <span>Back</span>
            </a>
            <button type="submit" class="btn btn-primary" form="stockOpnameForm">
                <i class="feather-save me-2"></i>
                <span>Update Stock Opname</span>
            </button>
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="main-content">
    <div class="row">
        <div class="col-lg-12">
            <div class="card stretch stretch-full">
                <form action="/erp/productions/stock-opname/update/{{ $stockOpname->id }}" method="POST" id="stockOpnameForm">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <input type="hidden" name="production_warehouse_id" value="{{ $stockOpname->production_warehouse_id ?? 2 }}">

                        {{-- Product --}}
                        <div class="row mb-3 align-items-center">
                            <div class="col-lg-2">
                                <label for="product" class="fw-semibold">Product:</label>
                            </div>
                            <div class="col-lg-10 mb-0">
                                <select class="form-control" id="product" name="product">
                                    <option value="" disabled hidden>Pilih produk</option>
                                    @foreach ($products as $product)
                                    <option value="{{ $product->id }}"
                                        {{ $stockOpname->product_id == $product->id ? 'selected' : '' }}>
                                        [{{ $product->sku }}] {{ $product->name }}
                                    </option>
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
                                    <input type="date" class="form-control" id="date" name="date"
                                        value="{{ old('date', $stockOpname->date ? $stockOpname->date->format('Y-m-d') : date('Y-m-d')) }}">
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
                                    <option value="" disabled hidden>Pilih jenis perubahan</option>
                                    <option value="available_quantity" {{ $stockOpname->change == 'available_quantity' ? 'selected' : '' }}>Available Quantity</option>
                                    <option value="finished_product" {{ $stockOpname->change == 'finished_product' ? 'selected' : '' }}>Finished Product</option>
                                </select>
                            </div>
                        </div>

                        {{-- Available Quantity --}}
                        <div class="row mb-3 align-items-center change-field {{ $stockOpname->change == 'available_quantity' ? '' : 'd-none' }}" id="field-available_quantity">
                            <div class="col-lg-2">
                                <label for="available_quantity" class="fw-semibold">Available Quantity:</label>
                            </div>
                            <div class="col-lg-10 mb-0">
                                <div class="input-group">
                                    <div class="input-group-text"><i class="feather-box"></i></div>
                                    <input type="number" class="form-control" id="available_quantity" name="available_quantity"
                                        value="{{ old('available_quantity', $stockOpname->available_quantity) }}" placeholder="Available Quantity">
                                </div>
                            </div>
                        </div>

                        {{-- Finished Product --}}
                        <div class="row mb-3 align-items-center change-field {{ $stockOpname->change == 'finished_product' ? '' : 'd-none' }}" id="field-finished_product">
                            <div class="col-lg-2">
                                <label for="finished_product" class="fw-semibold">Finished Product:</label>
                            </div>
                            <div class="col-lg-10 mb-0">
                                <div class="input-group">
                                    <div class="input-group-text"><i class="feather-box"></i></div>
                                    <input type="number" class="form-control" id="finished_product" name="finished_product"
                                        value="{{ old('finished_product', $stockOpname->finished_product) }}" placeholder="Finished Product">
                                </div>
                            </div>
                        </div>

                        {{-- Status --}}
                        <div class="row mb-3 align-items-center">
                            <div class="col-lg-2">
                                <label for="status" class="fw-semibold">Status:</label>
                            </div>
                            <div class="col-lg-10 mb-0">
                                <select class="form-control" id="status" name="status">
                                    <option value="Gain" {{ $stockOpname->status == 'Gain' ? 'selected' : '' }}>Gain</option>
                                    <option value="Loss" {{ $stockOpname->status == 'Loss' ? 'selected' : '' }}>Loss</option>
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
                                    <input type="text" class="form-control" id="notes" name="notes"
                                        value="{{ old('notes', $stockOpname->notes) }}" placeholder="Notes">
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
</script>
@endpush