@extends('erp.layouts.main')

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Ecommerce Product Category</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('erp.ecommerce-product-categories.index') }}">Ecommerce Product Category</a></li>
                <li class="breadcrumb-item">Create</li>
            </ul>
        </div>
        <div class="page-header-right ms-auto">
            <div class="page-header-right-items">
                <div class="d-flex align-items-center gap-2 page-header-right-items-wrapper">
                    <a href="{{ route('erp.ecommerce-product-categories.index') }}" class="btn btn-light-brand">
                        <i class="feather-arrow-left me-2"></i>
                        <span>Back</span>
                    </a>
                    <button type="submit" class="btn btn-primary" form="categoryForm">
                        <i class="feather-plus me-2"></i>
                        <span>Add Category</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    @include('erp.pages.ecommerce-product-categories.form')
@endsection
