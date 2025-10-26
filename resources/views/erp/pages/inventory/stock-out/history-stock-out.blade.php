@extends('erp.layouts.main')

@section('breadcrumb')
<div class="page-header sticky-top">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">Stock Out History</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
            <li class="breadcrumb-item">Warehouse</li>
            <li class="breadcrumb-item">Stock Out History</li>
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
    <div class="row align-items-baseline">
        <div class="col-xxl-8 col-xl-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Products</h5>
                </div>
                <div class="card-body px-0">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>QTY</th>
                                    <th>Printed</th>
                                    <th>To be printed</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($stockOut->items as $item)
                                <tr>
                                    <td>{{ $item->product->name ?? '-' }}</td>
                                    <td><span class="fw-bold text-primary">{{ number_format($item->quantity, 0, ',', '.') }}</span></td>
                                    <td><span class="fw-bold text-success">{{ number_format($item->stock_out, 0, ',', '.') }}</span></td>
                                    <td>
                                        <span class="fw-bold text-danger">
                                            {{ number_format($item->quantity - $item->stock_out, 0, ',', '.') }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-4 col-xl-6">
            <div class="card stretch">
                <div class="card-header">
                    <h5 class="card-title">Order Information</h5>
                </div>
                <div class="card-body task-info">
                    <div class="task-info-list">
                        <div class="row align-items-center mb-3 task-list-row">
                            <div class="col-6">
                                <i class="feather-star me-2"></i>
                                <span class="fw-semibold">
                                    @if($stockOut->note === 'Purchase Returns')
                                    Supplier Name:
                                    @elseif($stockOut->note === 'Sale Account')
                                    Customer Name:
                                    @else
                                    -
                                    @endif
                                </span>
                            </div>
                            <div class="col-6 d-flex">
                                <span class="border-bottom border-bottom-dashed border-gray-5">
                                    @if($stockOut->note === 'Purchase Returns')
                                    {{ $stockOut->purchaseReturn->supplier->name ?? '-' }}
                                    @elseif($stockOut->note === 'Sale Account')
                                    {{ $stockOut->order->customer->name ?? '-' }}
                                    @else
                                    -
                                    @endif
                                </span>
                            </div>
                        </div>
                        <div class="row align-items-center mb-3 task-list-row">
                            <div class="col-6">
                                <i class="feather-calendar me-2"></i>
                                <span class="fw-semibold">Date:</span>
                            </div>
                            <div class="col-6 d-flex">
                                <span class="border-bottom border-bottom-dashed border-gray-5">{{ date('d M Y', strtotime($stockOut->date)) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-12 col-lg-6">
            <div class="card stretch stretch-full">
                <div class="card-header">
                    <h5 class="card-title">History</h5>
                </div>
                <div class="card-body p-0">
                    <div class="p-4">
                        <div class="col-lg-4 me-2">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="start_date" class="fw-semibold fs-12">Start Date</label>
                                    <input type="date" id="start_date" name="start_date" class="form-control" style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                </div>
                                <div class="col-md-6">
                                    <label for="end_date" class="fw-semibold fs-12">Due Date</label>
                                    <input type="date" id="end_date" name="end_date" class="form-control" style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table id="stockOutHistoryTable" class="table table-hover">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Invoice</th>
                                    <th>Stock Out Date</th>
                                    <th>Updated By</th>
                                    <th>Waybill Number</th>
                                    <th>Waybill Image</th>
                                    <th>Histories</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        const dataTable = $('#stockOutHistoryTable').DataTable({
            processing: true,
            serverSide: true,
            searching: false,
            lengthChange: false,
            ajax: {
                url: "{{ url('/erp/inventory/stock-out/history/' . $stockOut->id . '/data') }}",
                data: function(d) {
                    d.start_date = $('#start_date').val();
                    d.end_date = $('#end_date').val();
                },
            },
            columns: [{
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'invoice_number',
                    name: 'invoice_number'
                },
                {
                    data: 'change_date',
                    name: 'change_date'
                },
                {
                    data: 'user_name',
                    name: 'user_name'
                },
                {
                    data: 'waybill_number',
                    name: 'waybill_number'
                },
                {
                    data: 'waybill_image',
                    name: 'waybill_image'
                },
                {
                    data: 'stock_out',
                    name: 'stock_out'
                },
            ]
        });

        $('#start_date, #end_date').on('change', function() {
            dataTable.ajax.reload();
        });
    });
</script>
@endpush