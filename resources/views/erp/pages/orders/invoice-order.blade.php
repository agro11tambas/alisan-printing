@extends('erp.layouts.main')

@section('breadcrumb')
<div class="page-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">Order</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
            <li class="breadcrumb-item">Order</li>
            <li class="breadcrumb-item">Invoice</li>
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
<div class="main-content container-lg">
    <div class="row">
        <div class="col-lg-12">
            <div class="card invoice-container" id="invoiceContent">
                <div class="card-body p-0">
                    <div class="px-2 pt-2">
                        <div class="d-sm-flex justify-content-between">
                            <div>
                                <div class="fs-24 fw-bolder font-montserrat-alt text-uppercase text-primary">Alisan</div>
                                <address class="text-muted">
                                    Jl. abcde asdkasdas
                                </address>
                            </div>
                            <div class="lh-lg pt-2 pt-sm-0">
                                <h2 class="fs-4 fw-bold text-primary">Invoice</h2>
                                <div>
                                    <span class="fw-bold text-dark">Invoice:</span>
                                    <span class="fw-bold text-primary">{{ $order->order_number }}</span>
                                </div>
                                <div>
                                    <span class="fw-bold text-dark">Date:</span>
                                    <span class="text-muted">{{ date('d M Y', strtotime($order->created_at)) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr class="border-dashed">
                    <div class="px-2 py-sm-4">
                        <div class="d-sm-flex gap-4 justify-content-center">
                            <div class="text-sm-end">
                                <h2 class="fs-16 fw-bold text-dark mb-2">Invoiced To:</h2>
                                <address class="text-muted lh-lg">
                                    {{ $order->customer->name }}<br>
                                    {{ $order->customer->phone }}<br>
                                    {{ $order->shipping_address }}
                                </address>
                            </div>
                            <div class="border-end border-end-dashed border-gray-500 d-none d-sm-block"></div>
                            <div class="mt-2 mt-sm-0">
                                <h2 class="fs-16 fw-bold text-dark mb-2">Payment Details:</h2>
                                <div class="text-muted lh-lg">
                                    <div>
                                        <span class="text-muted">Total Due:</span>
                                        <span class="fw-bold text-dark">Rp. {{ number_format($order->grand_total, 0, ',', '.') }}</span>
                                    </div>
                                    <div>
                                        <span class="text-muted">Payout Status:</span>
                                        <span class="fw-bold text-success">{{ $order->payment_status }}</span>
                                    </div>
                                    <div>
                                        <span class="text-muted">Payment Method:</span>
                                        <span class="fw-bold text-dark">{{ $order->payment_method }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr class="border-dashed mb-0">
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
                                    <td>{{ $item->product->name }}</td>
                                    <td>Rp. {{ number_format($item->product->price, 0, ',', '.') }}</td>
                                    <td>{{ $item->quantity }}</td>
                                    <td class="text-end">Rp. {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                                </tr>
                                @endforeach
                                <tr>
                                    <td colspan="2" class="border-0"></td>
                                    <td class="fw-semibold text-dark text-lg-end border-0 text-end">Sub Total</td>
                                    <td class="fw-bold text-dark border-0 text-end">Rp. {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td colspan="2" class="border-0"></td>
                                    <td class="fw-semibold text-dark text-lg-end border-0 text-end">Discount</td>
                                    <td class="fw-bold text-danger border-0 text-end">- Rp. {{ number_format($order->discount, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td colspan="2" class="border-0"></td>
                                    <td class="fw-semibold text-dark text-lg-end border-0 text-end">Grand Total</td>
                                    <td class="fw-bold text-success border-0 text-end">Rp. {{ number_format($order->grand_total, 0, ',', '.') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <hr class="border-dashed mt-0">
                    <div class="px-2 pt-2 d-sm-flex align-items-center justify-content-between">
                        <div class="d-flex mb-3 mb-sm-0">
                            <!-- <a href="/orders/invoice-pdf/{{ $order->id }}" class="d-flex me-1 printBTN">
                                <div class="avatar-text avatar-md" data-bs-toggle="tooltip" data-bs-trigger="hover" title="Print Invoice"><i class="feather feather-printer"></i></div>
                            </a> -->
                            <a href="/erp/orders/invoice-png/{{ $order->id }}" target="_blank" class="d-flex me-1">
                                <div class="avatar-text avatar-md" data-bs-toggle="tooltip" data-bs-trigger="hover" title="Download Invoice">
                                    <i class="feather feather-download"></i>
                                </div>
                            </a>
                        </div>
                        <div class="text-end">
                            <h6 class="fs-13 fw-bold mt-1">Owner</h6>
                            <p class="fs-11 fw-semibold text-muted">{{ date('d M Y, H:i', strtotime($order->created_at)) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@endpush
