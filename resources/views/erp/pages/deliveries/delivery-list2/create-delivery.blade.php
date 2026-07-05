@extends('erp.layouts.main')

@section('breadcrumb')
<div class="page-header sticky-top">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">Order</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
            <li class="breadcrumb-item">Order</li>
            <li class="breadcrumb-item">Add Delivery</li>
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
                <a href="/erp/orders/sale-orders" class="btn btn-light-brand">
                    <i class="feather-arrow-left me-2"></i>
                    <span>Back</span>
                </a>
                <button type="submit" class="btn btn-primary" form="deliveryForm">
                    <i class="feather-plus me-2"></i>
                    <span>Add Delivery</span>
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
        <div class="col-12">
            <form action="/erp/deliveries/store" method="POST" enctype="multipart/form-data" id="deliveryForm">
                @csrf
                @method('POST')
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Invoice Number : <span>{{ $order->order_number }}</span></h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12">
                                <div class="row mb-2 align-items-center">
                                    <div class="col-lg-2">
                                        <label for="invoice_number" class="fw-semibold">Invoice Number:</label>
                                    </div>
                                    <div class="col-lg-10 mb-0">
                                        <div class="input-group">
                                            <input type="hidden" name="order_id" value="{{ $order->id }}">
                                            <input type="text" class="form-control" id="invoice_number" name="invoice_number" value="{{ $order->order_number }}" readonly>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mb-2 align-items-center">
                                    <div class="col-lg-2">
                                        <label for="delivered_at" class="fw-semibold">Delivered At:</label>
                                    </div>
                                    <div class="col-lg-10 mb-0">
                                        <div class="input-group">
                                            <input type="date" class="form-control" id="delivered_at" name="delivered_at" value="{{ now()->format('Y-m-d') }}" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mb-2 align-items-center">
                                    <div class="col-lg-2">
                                        <label for="delivery_proof" class="fw-semibold">Delivery Proof:</label>
                                    </div>
                                    <div class="col-lg-10 mb-0">
                                        <div class="input-group">
                                            <input type="file" class="form-control" id="delivery_proof" name="delivery_proof" accept="image/*">
                                        </div>
                                    </div>
                                </div>
                                <div class="row mb-2 align-items-center">
                                    <div class="col-lg-2">
                                        <label for="notes" class="fw-semibold">Note:</label>
                                    </div>
                                    <div class="col-lg-10 mb-0">
                                        <div class="input-group">
                                            <textarea class="form-control" id="notes" name="notes" placeholder="Catatan"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Printed</th>
                                    <th>Delivered</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($order->orderItems as $index => $item)
                                <tr>
                                    <td>
                                        @if($item->product)
                                        {{ $item->product->name }}
                                        @endif

                                        @if($item->productBundle)
                                        {{ $item->productBundle->name }}
                                        @endif
                                    </td>
                                    <td>{{ $item->completed_quantity }}</td>
                                    <td>
                                        <input type="hidden" name="items[{{ $index }}][order_item_id]" value="{{ $item->id }}">
                                        <input type="number"
                                            name="items[{{ $index }}][delivered_quantity]"
                                            class="form-control"
                                            value="0"
                                            min="0"
                                            max="{{ $item->completed_quantity - $item->completed_delivery }}"
                                            placeholder="Jumlah dikirim">
                                        <small class="text-muted">Sisa: {{ $item->completed_quantity - $item->completed_delivery }}</small>
                                    </td>
                                    <td>
                                        <input type="text" name="items[{{ $index }}][kurir]" class="form-control" placeholder="Kurir">
                                    </td>
                                    <td>
                                        <input type="text" name="items[{{ $index }}][note]" class="form-control" placeholder="Note">
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
