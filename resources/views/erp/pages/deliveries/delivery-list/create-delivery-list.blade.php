@extends('erp.layouts.main')

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Delivery List</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Deliveries</li>
                <li class="breadcrumb-item">Delivery List</li>
                <li class="breadcrumb-item active">Create Delivery List</li>
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
                    <a href="/erp/deliveries/delivery-order" class="btn btn-light-brand">
                        <i class="feather-arrow-left me-2"></i>
                        <span>Back</span>
                    </a>
                    <button type="submit" class="btn btn-primary" form="deliveryListForm">
                        <i class="feather-plus me-2"></i>
                        <span>Add Delivery List</span>
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
    @if (session('error'))
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
                <form action="/erp/deliveries/delivery-list/store/{{ $deliveryOrder->id }}" method="POST"
                    id="deliveryListForm">
                    @csrf
                    <div class="card stretch stretch-full">
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="fw-semibold">Shipment Number</label>
                                    <input type="text" class="form-control" name="shipment_number"
                                        value="{{ $shipmentNumber }}" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="fw-semibold">Shipment Date</label>
                                    <input type="date" class="form-control" name="shipment_date"
                                        value="{{ date('Y-m-d') }}" required>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="fw-semibold">Driver</label>
                                    <select name="driver_id" class="form-control" data-select2-selector="tag" required>
                                        <option value="">-- Pilih Driver --</option>
                                        @foreach ($drivers as $driver)
                                            <option value="{{ $driver->id }}">{{ $driver->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="fw-semibold">Vehicle</label>
                                    <input type="text" class="form-control" name="vehicle"
                                        placeholder="Vehicle Plate No">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-12">
                                    <label class="fw-semibold">Note</label>
                                    <textarea class="form-control" name="note" rows="2" placeholder="Optional note..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card stretch stretch-full">
                        <div class="card-body">
                            <h5 class="fw-bold mb-3">Delivery Items</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Total Qty</th>
                                            <th>Ready Qty</th>
                                            <th>Already Shipped</th>
                                            <th>Shipped Qty (Now)</th>
                                            <th>Note</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($deliveryOrder->items as $item)
                                            @php
                                                $alreadyShipped = $item->deliveryListItems()->sum('shipped_quantity');
                                                $remaining = $item->ready_qty - $alreadyShipped;
                                            @endphp
                                            <tr>
                                                <td>
                                                    {{ $item->product->name }}
                                                    <input type="hidden"
                                                        name="items[{{ $item->id }}][delivery_order_item_id]"
                                                        value="{{ $item->id }}">
                                                    <input type="hidden" name="items[{{ $item->id }}][product_id]"
                                                        value="{{ $item->product_id }}">
                                                </td>
                                                <td><span class="text-primary">{{ $item->progress_qty }}</span></td>
                                                <td><span class="text-danger">{{ $item->ready_qty }}</span></td>
                                                <td><span class="text-success">{{ $alreadyShipped }}</span></td>
                                                <td>
                                                    <input type="number" class="form-control"
                                                        name="items[{{ $item->id }}][shipped_quantity]" min="0"
                                                        max="{{ $item->ready_qty }}" value="0">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control"
                                                        name="items[{{ $item->id }}][note]" placeholder="Note">
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
