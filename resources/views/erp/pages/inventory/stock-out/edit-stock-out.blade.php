@extends('erp.layouts.main')

@section('breadcrumb')
<div class="page-header sticky-top">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">Edit Stock Out</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
            <li class="breadcrumb-item">Warehouse</li>
            <li class="breadcrumb-item">Edit Stock Out</li>
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
                <a href="/erp/inventory/stock-out" class="btn btn-light-brand">
                    <i class="feather-arrow-left me-2"></i>
                    <span>Back</span>
                </a>
                <button type="submit" class="btn btn-primary" form="stockOutForm">
                    <i class="feather-plus me-2"></i>
                    <span>Edit Stock Out</span>
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
<div class="main-content">
    <div class="row">
        <div class="col-12">
            <form action="/erp/inventory/stock-out/update/{{ $inventory->id }}" method="POST" enctype="multipart/form-data" id="stockOutForm">
                @csrf
                @method('PUT')

                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Edit Inventory</h4>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-lg-2"><label class="fw-semibold">Change Date</label></div>
                            <div class="col-lg-10">
                                <input type="date" class="form-control" name="change_date" value="{{ now()->format('Y-m-d') }}">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-lg-2"><label class="fw-semibold">Note</label></div>
                            <div class="col-lg-10">
                                <textarea class="form-control" name="notes">{{ $inventory->notes }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Edit Items</h4>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Qty Ordered</th>
                                    <th>Stock Out</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($inventory->items as $index => $item)
                                <tr>
                                    <td>{{ $item->product->name }}</td>
                                    <td>{{ $item->quantity }}</td>
                                    <td>
                                        <input type="hidden" name="items[{{ $index }}][inventory_item_id]" value="{{ $item->id }}">
                                        <input type="number" class="form-control"
                                            name="items[{{ $index }}][stock_out]"
                                            value="{{ $item->stock_out }}"
                                            min="0"
                                            max="{{ $item->quantity }}">
                                        <small class="text-muted">Sisa: {{ $item->quantity - $item->stock_out }}</small>
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
