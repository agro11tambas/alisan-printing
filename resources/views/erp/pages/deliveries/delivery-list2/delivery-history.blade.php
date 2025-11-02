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
            <li class="breadcrumb-item">Delivery History</li>
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
                <a href="/orders/create-order" class="btn btn-primary">
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
                                    <th>Printed</th>
                                    <th>Delivered</th>
                                    <th>To be delivered</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($order->orderItems as $index => $item)
                                <tr>
                                    <td>{{ $item->product->name ?? '-' }}</td>
                                    <td><span class="fw-bold text-primary">{{ $item->completed_quantity }}</span></td>
                                    <td><span class="fw-bold text-success">{{ $item->completed_delivery }}</span></td>
                                    <td>
                                        <span class="fw-bold text-danger">
                                            {{ $item->completed_quantity - $item->completed_delivery }}
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
                                <span class="fw-semibold">Customer Name:</span>
                            </div>
                            <div class="col-6 d-flex">
                                <span class="border-bottom border-bottom-dashed border-gray-5">{{ $order->customer->name }}</span>
                            </div>
                        </div>
                        <div class="row align-items-center mb-3 task-list-row">
                            <div class="col-6">
                                <i class="feather-phone me-2"></i>
                                <span class="fw-semibold">Whatsapp:</span>
                            </div>
                            <div class="col-6 d-flex">
                                <span class="border-bottom border-bottom-dashed border-gray-5">{{ $order->customer->phone }}</span>
                            </div>
                        </div>
                        <div class="row align-items-center mb-3 task-list-row">
                            <div class="col-6">
                                <i class="feather-airplay me-2"></i>
                                <span class="fw-semibold">Address:</span>
                            </div>
                            <div class="col-6 d-flex">
                                <span class="border-bottom border-bottom-dashed border-gray-5">{{ $order->shipping_address }}</span>
                            </div>
                        </div>
                        <div class="row align-items-center mb-3 task-list-row">
                            <div class="col-6">
                                <i class="feather-airplay me-2"></i>
                                <span class="fw-semibold">Google Map:</span>
                            </div>
                            <div class="col-6 d-flex">
                                <span class="border-bottom border-bottom-dashed border-gray-5"><a href="{{ $order->google_maps }}" target="_blank">Link Google Map</a></span>
                            </div>
                        </div>
                        <div class="row align-items-center mb-3 task-list-row">
                            <div class="col-6">
                                <i class="feather-calendar me-2"></i>
                                <span class="fw-semibold">Order Date:</span>
                            </div>
                            <div class="col-6 d-flex">
                                <span class="border-bottom border-bottom-dashed border-gray-5">{{ date('d M Y', strtotime($order->created_at)) }}</span>
                            </div>
                        </div>
                        <div class="row align-items-center mb-3 task-list-row">
                            <div class="col-6">
                                <i class="feather-clock me-2"></i>
                                <span class="fw-semibold">Status:</span>
                            </div>
                            <div class="col-6 d-flex">
                                <span class="border-bottom border-bottom-dashed border-gray-5">{{ $order->status }}</span>
                            </div>
                        </div>
                        <div class="row align-items-center mb-3 task-list-row">
                            <div class="col-6">
                                <i class="feather-clipboard me-2"></i>
                                <span class="fw-semibold">Payment Method:</span>
                            </div>
                            <div class="col-6 d-flex">
                                <span class="border-bottom border-bottom-dashed border-gray-5">{{ $order->payment_method }}</span>
                            </div>
                        </div>
                        <div class="row align-items-center task-list-row">
                            <div class="col-6">
                                <i class="feather-clipboard me-2"></i>
                                <span class="fw-semibold">Payment Status:</span>
                            </div>
                            <div class="col-6 d-flex">
                                <span class="border-bottom border-bottom-dashed border-gray-5">{{ $order->payment_status }}</span>
                            </div>
                        </div>
                        <!-- <div class="row align-items-center mb-3 task-list-row">
                            <div class="col-6">
                                <i class="feather-dollar-sign me-2"></i>
                                <span class="fw-semibold">Total Amount:</span>
                            </div>
                            <div class="col-6 d-flex">
                                <span class="border-bottom border-bottom-dashed border-gray-5">Rp. {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                            </div>
                        </div> -->
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-12 col-lg-6">
            <div class="card stretch stretch-full">
                <div class="card-header">
                    <h5 class="card-title">Delivery History</h5>
                </div>
                <div class="card-body p-0">
                    <div class="p-4">
                        <div class="col-lg-4 me-2">
                            <label for="" class="fw-semibold fs-12">Date</label>
                            <div class="d-flex align-items-center gap-2 page-header-right-items-wrapper">
                                <div class="col-auto">
                                    <select id="filter" class="form-control" style="padding: 0.5rem 1rem; font-size: 0.875rem; width: 200px !important;">
                                        <option value="all">All Time</option>
                                        <option value="yearly">Yearly</option>
                                        <option value="year_to_date">Year to Date</option>
                                        <option value="last_30_days">Last 30 Days</option>
                                        <option value="this_month">This Month</option>
                                        <option value="last_7_days">Last 7 Days</option>
                                        <option value="today">Today</option>
                                        <option value="custom">Custom Range</option>
                                    </select>
                                </div>
                                <div class="col-auto custom-range d-none">
                                    <input type="date" id="start_date" class="form-control" style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                </div>
                                <div class="col-auto custom-range d-none">
                                    <input type="date" id="end_date" class="form-control" style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                </div>
                                <div class="col-auto custom-range d-none">
                                    <button id="apply-filter" class="btn btn-primary">Apply</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table id="delivery-history-table" class="table table-hover">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Invoice</th>
                                    <th>Delivery Date</th>
                                    <th>Delivered By</th>
                                    <th>Products</th>
                                    <th>Proof</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
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
        const dataTable = $('#delivery-history-table').DataTable({
            processing: true,
            serverSide: true,
            searching: false,
            lengthChange: false,
            ajax: {
                url: "{{ url('/erp/deliveries/history/' . $order->id . '/data') }}",
                data: function(d) {
                    d.filter = $('#filter').val();
                    d.start_date = $('#start_date').val();
                    d.end_date = $('#end_date').val();
                }
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
                    data: 'delivered_at',
                    name: 'delivered_at'
                },
                {
                    data: 'user_name',
                    name: 'user_name'
                },
                {
                    data: 'products',
                    name: 'products'
                },
                {
                    data: 'delivery_proof',
                    name: 'delivery_proof',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'notes',
                    name: 'notes'
                }
            ]
        });

        $('#filter').on('change', function() {
            if ($(this).val() === 'custom') {
                $('.custom-range').removeClass('d-none');
            } else {
                $('.custom-range').addClass('d-none');
                dataTable.ajax.reload();
                // dataTableMobile.ajax.reload();
            }
        });

        // Apply custom date range
        $('#apply-filter').on('click', function() {
            dataTable.ajax.reload();
            // dataTableMobile.ajax.reload();
        });
    });
</script>
@endpush
