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
                <li class="breadcrumb-item active">Edit Delivery List</li>
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
                    <a href="/erp/deliveries/delivery-list" class="btn btn-light-brand">
                        <i class="feather-arrow-left me-2"></i>
                        <span>Back</span>
                    </a>
                    <button type="submit" class="btn btn-primary" form="deliveryListForm">
                        <i class="feather-save me-2"></i>
                        <span>Update Delivery List</span>
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
                <form action="/erp/deliveries/delivery-list/update/{{ $deliveryList->id }}" method="POST"
                    id="deliveryListForm">
                    @csrf
                    @method('PUT')
                    <div class="card stretch stretch-full">
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="fw-semibold">Shipment Number</label>
                                    <input type="text" class="form-control" name="shipment_number"
                                        value="{{ $deliveryList->shipment_number }}" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="fw-semibold">Shipment Date</label>
                                    <input type="date" class="form-control" name="shipment_date"
                                        value="{{ $deliveryList->shipment_date }}" required>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="fw-semibold">Driver</label>
                                    <select name="driver_id" class="form-control" required>
                                        <option value="">-- Pilih Driver --</option>
                                        @foreach ($drivers as $driver)
                                            <option value="{{ $driver->id }}"
                                                {{ $deliveryList->driver_id == $driver->id ? 'selected' : '' }}>
                                                {{ $driver->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="fw-semibold">Vehicle</label>
                                    <input type="text" class="form-control" name="vehicle"
                                        value="{{ $deliveryList->vehicle }}">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-12">
                                    <label class="fw-semibold">Note</label>
                                    <textarea class="form-control" name="note" rows="2" placeholder="Optional note...">{{ $deliveryList->note }}</textarea>
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
                                            <th>Shipped Qty (This List)</th>
                                            <th>Note</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($deliveryList->items as $dlItem)
                                            @php
                                                $doItem = $dlItem->deliveryOrderItem;
                                                $alreadyShipped = $doItem
                                                    ->deliveryListItems()
                                                    ->where('id', '!=', $dlItem->id)
                                                    ->sum('shipped_quantity');
                                                $maxQty = $doItem->progress_qty - $alreadyShipped;
                                            @endphp
                                            <tr>
                                                <td>
                                                    {{ $doItem->product->name }}
                                                    <input type="hidden"
                                                        name="items[{{ $dlItem->id }}][delivery_list_item_id]"
                                                        value="{{ $dlItem->id }}">
                                                    <input type="hidden"
                                                        name="items[{{ $dlItem->id }}][delivery_order_item_id]"
                                                        value="{{ $doItem->id }}">
                                                    <input type="hidden" name="items[{{ $dlItem->id }}][product_id]"
                                                        value="{{ $doItem->product_id }}">
                                                </td>
                                                <td><span class="text-primary">{{ $doItem->progress_qty }}</span></td>
                                                <td><span class="text-danger">{{ $doItem->ready_qty }}</span></td>
                                                <td><span class="text-success">{{ $alreadyShipped }}</span></td>
                                                <td>
                                                    <input type="number" class="form-control"
                                                        name="items[{{ $dlItem->id }}][shipped_quantity]" min="0"
                                                        max="{{ $maxQty }}" value="{{ $dlItem->shipped_quantity }}">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control"
                                                        name="items[{{ $dlItem->id }}][note]"
                                                        value="{{ $dlItem->note }}">
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
