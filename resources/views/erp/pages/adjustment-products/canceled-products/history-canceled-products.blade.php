@extends('erp.layouts.main')

@push('styles')
<style>
    #historyTable {
        width: 100% !important;
    }

    #historyTable_wrapper .dataTables_scrollBody {
        background: #fff !important;
    }
</style>
@endpush

@section('breadcrumb')
<div class="page-header sticky-top">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">Canceled Product History</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
            <li class="breadcrumb-item">Production</li>
            <li class="breadcrumb-item"><a href="/erp/productions/canceled-products">Canceled Products</a></li>
            <li class="breadcrumb-item active">History</li>
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
                <a href="/erp/productions/canceled-products" class="btn btn-light-brand">
                    <i class="feather-arrow-left me-2"></i>
                    <span>Back</span>
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
<div class="main-content">
    <div class="row">
        <div class="col-lg-12">
            <div class="card stretch stretch-full">
                <div class="card-header">
                    <h5 class="mb-0">
                        History for: <span class="fw-bold text-primary">{{ $productionStock->product->name }}</span>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="row g-3 p-4 justify-content-between">
                        <div class="col-lg-4 me-2">
                            <label for="filter" class="fw-semibold fs-12">Date</label>
                            <div class="d-flex align-items-center gap-2">
                                <div class="col-auto">
                                    <select id="filter" class="form-control" style="width: 200px;">
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
                                    <input type="date" id="start_date" class="form-control">
                                </div>
                                <div class="col-auto custom-range d-none">
                                    <input type="date" id="end_date" class="form-control">
                                </div>
                                <div class="col-auto custom-range d-none">
                                    <button id="apply-filter" class="btn btn-primary">Apply</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover" id="historyTable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Date</th>
                                    <th>Quantity</th>
                                    <!-- <th>Warehouse</th> -->
                                    <th>Note</th>
                                    <th>User</th>
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
        const table = $('#historyTable').DataTable({
            processing: true,
            serverSide: true,
            deferRender: true,
            scrollY: 600,
            scroller: true,
            paging: true,
            searching: false,
            lengthChange: false,
            info: false,
            pagingType: "simple",
            ajax: {
                url: "{{ url('/erp/adjustment-products/canceled-products/history/'.$productionStock->id.'/data') }}",
                data: function(d) {
                    d.filter = $('#filter').val();
                    d.start_date = $('#start_date').val();
                    d.end_date = $('#end_date').val();
                }
            },
            columns: [{
                    data: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'date',
                    name: 'date'
                },
                {
                    data: 'quantity',
                    name: 'quantity'
                },
                // {
                //     data: 'warehouse',
                //     name: 'warehouse'
                // },
                {
                    data: 'note',
                    name: 'note'
                },
                {
                    data: 'user',
                    name: 'user'
                },
            ]
        });

        $('#filter').on('change', function() {
            if ($(this).val() === 'custom') {
                $('.custom-range').removeClass('d-none');
            } else {
                $('.custom-range').addClass('d-none');
                table.ajax.reload();
            }
        });

        $('#apply-filter').on('click', function() {
            table.ajax.reload();
        });
    });
</script>

@endpush
