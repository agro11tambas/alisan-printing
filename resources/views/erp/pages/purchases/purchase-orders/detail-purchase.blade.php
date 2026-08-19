@extends('erp.layouts.main')

@push('styles')
    <style>
        @include('erp.pages.partials.transaction-table-mobile-styles')
    </style>
@endpush

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Purchase</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Purchase</li>
                <li class="breadcrumb-item">Detail Purchase</li>
            </ul>
        </div>
        <!-- <div class="page-header-right ms-auto">
            <div class="page-header-right-items">
                <div class="d-flex d-md-none">
                    <a href="javascript:void(0)" class="page-header-right-close-toggle">
                        <i class="feather-arrow-left me-2"></i><span>Back</span>
                    </a>
                </div>
                <div class="d-flex align-items-center gap-2 page-header-right-items-wrapper">
                    <a href="/erp/orders/create-order" class="btn btn-primary">
                        <i class="feather-plus me-2"></i>
                        <span>Create Order</span>
                    </a>
                </div>
            </div>
            <div class="d-md-none d-flex align-items-center">
                <a href="javascript:void(0)" class="page-header-right-open-toggle">
                    <i class="feather-align-right fs-20"></i>
                </a>
            </div>
        </div> -->
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
        <div class="tab-content">
            <div class="tab-pane fade active show" id="proposalTab">
                <div class="row g-3">
                    <div class="col-xxl-8 col-xl-6">
                        <div class="card stretch">
                            <div class="card-header">
                                <h5 class="card-title">Products</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-mobile-cards">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>PO Qty</th>
                                                <th>Verify ke PL</th>
                                                <th>Sisa</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($purchase->purchaseItems as $item)
                                                <tr>
                                                    <td class="mobile-card-title">{{ $item->purchaseProduct?->name ?? $item->product_name ?? '-' }}</td>
                                                    @php $approved = $item->purchaseListItems->sum('quantity'); @endphp
                                                    <td data-label="PO Qty">{{ number_format($item->quantity, 0, ',', '.') }} {{ $item->unit_name }}</td>
                                                    <td data-label="Verify ke PL">{{ number_format($approved, 0, ',', '.') }} {{ $item->unit_name }}</td>
                                                    <td class="fw-bold text-primary" data-label="Sisa">{{ number_format(max(0, $item->quantity - $approved), 0, ',', '.') }} {{ $item->unit_name }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="card stretch">
                            <div class="card-header">
                                <h5 class="card-title">Purchase Lists</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table mb-0 table-mobile-cards">
                                        <thead>
                                            <tr>
                                                <th>PL Number</th>
                                                <th>Date</th>
                                                <th>Products</th>
                                                <th>Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($purchase->purchaseLists as $purchaseList)
                                                <tr>
                                                    <td class="mobile-card-title"><a href="/erp/purchases/purchase-list/detail-purchase/{{ $purchaseList->id }}">{{ $purchaseList->purchase_number }}</a></td>
                                                    <td data-label="Date">{{ $purchaseList->purchase_date?->format('d M Y') }}</td>
                                                    <td data-label="Products">{{ number_format($purchaseList->purchaseItems->sum('quantity'), 0, ',', '.') }}</td>
                                                    <td data-label="Total">Rp. {{ number_format($purchaseList->total_amount, 0, ',', '.') }}</td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="4" class="text-center text-muted">Belum ada Purchase List.</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xxl-4 col-xl-6">
                        <div class="card stretch">
                            <div class="card-body task-info">
                                <div class="mb-2">
                                    <h5 class="card-title mb-1">Purchase Information</h5>
                                    <span
                                        class="fs-12 fw-normal text-muted d-block">{{ date('d M Y', strtotime($purchase->created_at)) }}</span>
                                </div>
                                <div class="task-info-list">
                                    <div class="row align-items-center mb-2 task-list-row">
                                        <div class="col-6">
                                            <i class="feather-star me-2"></i>
                                            <span class="fw-semibold">Supplier Name:</span>
                                        </div>
                                        <div class="col-6 d-flex">
                                            <span
                                                class="border-bottom border-bottom-dashed border-gray-5">{{ $purchase->supplier->name }}</span>
                                        </div>
                                    </div>
                                    <div class="row align-items-center mb-2 task-list-row">
                                        <div class="col-6">
                                            <i class="feather-phone me-2"></i>
                                            <span class="fw-semibold">Whatsapp:</span>
                                        </div>
                                        <div class="col-6 d-flex">
                                            <span
                                                class="border-bottom border-bottom-dashed border-gray-5">{{ $purchase->supplier->phone }}</span>
                                        </div>
                                    </div>
                                    <div class="row align-items-center mb-2 task-list-row">
                                        <div class="col-6">
                                            <i class="feather-calendar me-2"></i>
                                            <span class="fw-semibold">Purchase Date:</span>
                                        </div>
                                        <div class="col-6 d-flex">
                                            <span
                                                class="border-bottom border-bottom-dashed border-gray-5">{{ date('d M Y', strtotime($purchase->created_at)) }}</span>
                                        </div>
                                    </div>
                                    <div class="row align-items-center mb-2 task-list-row">
                                        <div class="col-6">
                                            <i class="feather-clock me-2"></i>
                                            <span class="fw-semibold">Status:</span>
                                        </div>
                                        <div class="col-6 d-flex">
                                            <span
                                                class="border-bottom border-bottom-dashed border-gray-5">{{ $purchase->approval_status_label }}</span>
                                        </div>
                                    </div>
                                    <div class="row align-items-center mb-2 task-list-row">
                                        <div class="col-6">
                                            <i class="feather-clipboard me-2"></i>
                                            <span class="fw-semibold">Payment Method:</span>
                                        </div>
                                        <div class="col-6 d-flex">
                                            <span
                                                class="border-bottom border-bottom-dashed border-gray-5">{{ $purchase->payment_method }}</span>
                                        </div>
                                    </div>
                                    <div class="row align-items-center mb-2 task-list-row">
                                        <div class="col-6">
                                            <i class="feather-clipboard me-2"></i>
                                            <span class="fw-semibold">Payment Status:</span>
                                        </div>
                                        <div class="col-6 d-flex">
                                            <span
                                                class="border-bottom border-bottom-dashed border-gray-5">{{ $purchase->payment_status }}</span>
                                        </div>
                                    </div>
                                    <div class="row align-items-center mb-2 task-list-row">
                                        <div class="col-6">
                                            <i class="feather-dollar-sign me-2"></i>
                                            <span class="fw-semibold">Total Amount:</span>
                                        </div>
                                        <div class="col-6 d-flex">
                                            <span class="border-bottom border-bottom-dashed border-gray-5">Rp.
                                                {{ number_format($purchase->total_amount, 0, ',', '.') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
