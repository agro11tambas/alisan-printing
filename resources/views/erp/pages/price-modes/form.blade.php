@extends('erp.layouts.main')

@php($editing = $priceMode->exists)

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title"><h5 class="m-b-10">{{ $editing ? 'Edit' : 'Create' }} Mode</h5></div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item"><a href="/erp/products/price-modes">Mode</a></li>
                <li class="breadcrumb-item">{{ $editing ? 'Edit' : 'Create' }}</li>
            </ul>
        </div>
        <div class="page-header-right ms-auto d-flex gap-2">
            <a href="/erp/products/price-modes" class="btn btn-light-brand">
                <i class="feather-arrow-left me-2"></i>Back
            </a>
            <button type="submit" form="priceModeForm" class="btn btn-primary">
                <i class="feather-save me-2"></i>Save Mode
            </button>
        </div>
    </div>
@endsection

@section('content')
    <div class="main-content m-0 m-md-2 p-0 pt-1">
        <div class="card stretch stretch-full">
            <form id="priceModeForm" method="POST"
                action="{{ $editing ? '/erp/products/price-modes/' . $priceMode->id : '/erp/products/price-modes' }}">
                @csrf
                @if ($editing) @method('PUT') @endif
                <div class="card-body">
                    <div class="row mb-3">
                        <label class="col-lg-2 fw-semibold">Name</label>
                        <div class="col-lg-10">
                            <input name="name" class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name', $priceMode->name) }}" placeholder="Contoh: Embroidery" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label class="col-lg-2 fw-semibold">Code</label>
                        <div class="col-lg-10">
                            <input name="slug" class="form-control @error('slug') is-invalid @enderror"
                                value="{{ old('slug', $priceMode->slug) }}" placeholder="Otomatis dari Name jika kosong">
                            <small class="text-muted">Kode stabil yang disimpan sebagai snapshot transaksi.</small>
                            @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label class="col-lg-2 fw-semibold">Order</label>
                        <div class="col-lg-10">
                            <input type="number" min="0" name="sort_order" class="form-control"
                                value="{{ old('sort_order', $priceMode->sort_order ?? 0) }}" required>
                        </div>
                    </div>

                    <div class="row">
                        <label class="col-lg-2 fw-semibold">Status</label>
                        <div class="col-lg-10">
                            <input type="hidden" name="is_active" value="0">
                            <div class="form-check form-switch">
                                <input type="checkbox" class="form-check-input" name="is_active" value="1"
                                    @checked((bool) old('is_active', $priceMode->exists ? $priceMode->is_active : true))>
                                <label class="form-check-label">Active</label>
                            </div>
                        </div>
                    </div>
                </div>

            </form>
        </div>
    </div>
@endsection
