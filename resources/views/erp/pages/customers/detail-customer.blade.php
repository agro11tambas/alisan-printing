@extends('erp.layouts.main')

@section('breadcrumb')
<div class="page-header sticky-top">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">Customers</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
            <li class="breadcrumb-item">Customers</li>
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
                <a href="/erp/customers" class="btn btn-light-brand">
                    <i class="feather-arrow-left me-2"></i>
                    <span>Back</span>
                </a>
                <a href="/erp/customers/create-customer" class="btn btn-primary">
                    <i class="feather-plus me-2"></i>
                    <span>Create Customer</span>
                </a>
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
    <div class="tab-content">
        <div class="tab-pane fade show active" id="profileTab" role="tabpanel">
            <div class="card card-body lead-info">
                <div class="row mb-4">
                    <div class="col-lg-2 fw-medium">Name</div>
                    <div class="col-lg-10"><strong class="text-black">{{ $customer->name }}</strong></div>
                </div>
                <div class="row mb-4">
                    <div class="col-lg-2 fw-medium">Phone Number</div>
                    <div class="col-lg-10"><strong class="text-black">{{ $customer->phone }}</strong></div>
                </div>
                <div class="border-top pt-4">
                    @foreach ($customer->addresses as $index => $address)
                    <div class="row mb-3">
                        <div class="col-lg-2 fw-medium">Business {{ $index + 1 }}</div>
                        <div class="row col-lg-4">
                            <div class="col-lg-4">
                                <div class="d-flex flex-column">
                                    <span>Business Name</span>
                                    <strong class="text-black fs-6">{{ $address->business_name }}</strong>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="d-flex flex-column">
                                    <span>Address</span>
                                    <strong class="text-black fs-6">{{ $address->address }}</strong>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="d-flex flex-column">
                                    <span>Google Map</span>
                                    <strong class="text-black"><a class="btn btn-primary btn-sm" href="{{ $address->google_maps }}" target="_blank">Google Map</a></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            <hr>
        </div>
    </div>
</div>
@endsection
