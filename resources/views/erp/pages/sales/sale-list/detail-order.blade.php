@extends('erp.layouts.main')

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Sale List</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Sale List</li>
                <li class="breadcrumb-item">Detail Sale List</li>
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
                    <a href="/erp/saless/create-order" class="btn btn-primary">
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
    <div class="main-content m-0 m-md-2 m-lg-2 p-0 p-md-0 p-lg-0 pt-2 pt-md-0">
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
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>Price</th>
                                                <th>QTY</th>
                                                <th class="text-end">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($order->orderItems as $item)
                                                <tr>
                                                    <td>
                                                        @if ($item->product)
                                                            {{ $item->product->name }}
                                                        @endif

                                                        @if ($item->productBundle)
                                                            {{ $item->productBundle->name }}
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if ($item->product)
                                                            Rp. {{ number_format($item->product->price, 0, ',', '.') }}
                                                        @endif

                                                        @if ($item->productBundle)
                                                            Rp.
                                                            {{ number_format($item->productBundle->price, 0, ',', '.') }}
                                                        @endif
                                                    </td>
                                                    <td>{{ $item->quantity }}</td>
                                                    <td class="text-end">Rp.
                                                        {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                                                </tr>
                                            @endforeach
                                            @php
                                                $totalAmount = $order->orderItems->sum('subtotal');
                                            @endphp
                                            <tr>
                                                <td colspan="2" class="border-0"></td>
                                                <td class="fw-semibold text-dark text-lg-end border-bottom text-end">Sub
                                                    Total</td>
                                                <td class="fw-bold text-dark border-bottom text-end">Rp.
                                                    {{ number_format($totalAmount, 0, ',', '.') }}</td>
                                            </tr>
                                            <tr>
                                                <td colspan="2" class="border-0"></td>
                                                <td class="fw-semibold text-dark text-lg-end border-bottom text-end">
                                                    Discount</td>
                                                <td class="fw-bold text-danger border-bottom text-end">- Rp.
                                                    {{ number_format($order->discount ?? 0, 0, ',', '.') }}</td>
                                            </tr>
                                            <tr>
                                                <td colspan="2" class="border-0"></td>
                                                <td class="fw-semibold text-dark text-lg-end border-bottom text-end">Grand
                                                    Total</td>
                                                <td class="fw-bold text-success border-bottom text-end">Rp.
                                                    {{ number_format($order->grand_total ?? $totalAmount, 0, ',', '.') }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="row justify-content-end">
                                    <div class="col-sm-3">
                                        <a href="/erp/sales/sale-list/invoice/{{ $order->id }}"
                                            class="btn btn-primary">Print Invoice</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xxl-4 col-xl-6">
                        <div class="card stretch">
                            <div class="card-body task-info">
                                <div class="mb-4">
                                    <h5 class="card-title mb-1">Order Information</h5>
                                    <span
                                        class="fs-12 fw-normal text-muted d-block">{{ date('d M Y', strtotime($order->created_at)) }}</span>
                                </div>
                                <div class="task-info-list">
                                    <div class="row align-items-center mb-3 task-list-row">
                                        <div class="col-6">
                                            <i class="feather-star me-2"></i>
                                            <span class="fw-semibold">Customer Name:</span>
                                        </div>
                                        <div class="col-6 d-flex">
                                            <span
                                                class="border-bottom border-bottom-dashed border-gray-5">{{ $order->customer->name }}</span>
                                        </div>
                                    </div>
                                    <div class="row align-items-center mb-3 task-list-row">
                                        <div class="col-6">
                                            <i class="feather-phone me-2"></i>
                                            <span class="fw-semibold">Whatsapp:</span>
                                        </div>
                                        <div class="col-6 d-flex">
                                            <span
                                                class="border-bottom border-bottom-dashed border-gray-5">{{ $order->customer->phone }}</span>
                                        </div>
                                    </div>
                                    <div class="row align-items-center mb-3 task-list-row">
                                        <div class="col-6">
                                            <i class="feather-airplay me-2"></i>
                                            <span class="fw-semibold">Address:</span>
                                        </div>
                                        <div class="col-6 d-flex">
                                            <span
                                                class="border-bottom border-bottom-dashed border-gray-5">{{ $order->shipping_address }}</span>
                                        </div>
                                    </div>
                                    <div class="row align-items-center mb-3 task-list-row">
                                        <div class="col-6">
                                            <i class="feather-airplay me-2"></i>
                                            <span class="fw-semibold">Google Map:</span>
                                        </div>
                                        <div class="col-6 d-flex">
                                            <span class="border-bottom border-bottom-dashed border-gray-5"><a
                                                    href="{{ $order->google_maps }}" target="_blank">Link Google
                                                    Map</a></span>
                                        </div>
                                    </div>
                                    <div class="row align-items-center mb-3 task-list-row">
                                        <div class="col-6">
                                            <i class="feather-calendar me-2"></i>
                                            <span class="fw-semibold">Order Date:</span>
                                        </div>
                                        <div class="col-6 d-flex">
                                            <span
                                                class="border-bottom border-bottom-dashed border-gray-5">{{ date('d M Y', strtotime($order->created_at)) }}</span>
                                        </div>
                                    </div>
                                    <div class="row align-items-center mb-3 task-list-row">
                                        <div class="col-6">
                                            <i class="feather-clock me-2"></i>
                                            <span class="fw-semibold">Status:</span>
                                        </div>
                                        <div class="col-6 d-flex">
                                            <span
                                                class="border-bottom border-bottom-dashed border-gray-5">{{ $order->status }}</span>
                                        </div>
                                    </div>
                                    <div class="row align-items-center mb-3 task-list-row">
                                        <div class="col-6">
                                            <i class="feather-clipboard me-2"></i>
                                            <span class="fw-semibold">Payment Method:</span>
                                        </div>
                                        <div class="col-6 d-flex">
                                            <span
                                                class="border-bottom border-bottom-dashed border-gray-5">{{ $order->payment_method }}</span>
                                        </div>
                                    </div>
                                    <div class="row align-items-center mb-3 task-list-row">
                                        <div class="col-6">
                                            <i class="feather-clipboard me-2"></i>
                                            <span class="fw-semibold">Payment Status:</span>
                                        </div>
                                        <div class="col-6 d-flex">
                                            <span
                                                class="border-bottom border-bottom-dashed border-gray-5">{{ $order->payment_status }}</span>
                                        </div>
                                    </div>
                                    <div class="row align-items-center mb-3 task-list-row">
                                        <div class="col-6">
                                            <i class="feather-dollar-sign me-2"></i>
                                            <span class="fw-semibold">Total Amount:</span>
                                        </div>
                                        <div class="col-6 d-flex">
                                            <span class="border-bottom border-bottom-dashed border-gray-5">Rp.
                                                {{ number_format($order->total_amount, 0, ',', '.') }}</span>
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
