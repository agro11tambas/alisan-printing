@extends('erp.layouts.main')

@section('breadcrumb')
    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Ecommerce Information</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/dashboard">Home</a></li>
                <li class="breadcrumb-item">Ecommerce Information</li>
            </ul>
        </div>
    </div>
@endsection

@section('content')
    <div class="main-content">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row">
            <div class="col-lg-12">
                <div class="card stretch-stretch">
                    <div class="card-header border-bottom">
                        <h5 class="card-title">General Information</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('erp.ecommerce-information.update') }}" method="POST">
                            @csrf
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="phone_number" class="form-label">Phone Number <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="phone_number" name="phone_number" value="{{ old('phone_number', $information?->phone_number) }}" placeholder="e.g. 08123456789">
                                        @error('phone_number')
                                            <div class="text-danger mt-1">{{ $message }}</div>
                                        @enderror
                                        <small class="text-muted mt-1 d-block">Nomor HP ini akan ditampilkan pada halaman website.</small>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">Save Information</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
