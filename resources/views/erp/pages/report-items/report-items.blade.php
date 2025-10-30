@extends('erp.layouts.main')

@push('styles')
    <style>
        @media (max-width: 768px) {

            #combinedReportTable td.desktop-only,
            #combinedReportTable th.desktop-only {
                display: none !important;
            }
        }

        #combinedReportTable {
            width: 100% !important;
            min-width: 0;
        }

        #combinedReportTable_wrapper .dataTables_scrollBody {
            background-image: none !important;
        }
    </style>
@endpush

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Report Items</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Reports</li>
                <li class="breadcrumb-item active">Production & Warehouse</li>
            </ul>
        </div>
    </div>
@endsection

@section('content')
    <div class="main-content">
        <div class="row">
            <div class="col-lg-12">
                <div class="card stretch stretch-full">
                    <div class="card-body p-0">
                        <div class="row g-3 p-4 justify-content-end">
                            <div class="col-lg-4">
                                <label for="product_name" class="fw-semibold fs-12">Item Name</label>
                                <input type="text" id="product_name" name="product_name" class="form-control"
                                    style="padding: 0.5rem 1rem; font-size: 0.875rem;" placeholder="Search Item...">
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover bg-transparent" id="combinedReportTable">
                                <thead>
                                    <tr>
                                        <th class="wd-30">No</th>
                                        <th>Item Name</th>
                                        <th>Stock After Sales</th>
                                        <th>Warehouse Stock</th>
                                        <th>Production Stock</th>
                                        <th>Finished Products</th>
                                        {{-- <th>Pending Waiting List</th> --}}
                                        <th>Incoming Stock (Warehouse)</th>
                                        <th>Incoming Stock (Production)</th>
                                        <th>Avg. Cost</th>
                                        <th>Fixed Cost</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
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
            let table = $('#combinedReportTable').DataTable({
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
                    url: "{{ url('/erp/report-items/data') }}",
                    data: function(d) {
                        d.product_name = $('#product_name').val();
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'stock_after_sales',
                        name: 'stock_after_sales'
                    },
                    {
                        data: 'inventory_stock',
                        name: 'inventory_stock'
                    },

                    {
                        data: 'production_available',
                        name: 'production_available'
                    },
                    {
                        data: 'finished_product_stock',
                        name: 'finished_product_stock'
                    },
                    // {
                    //     data: 'order_progress_remaining',
                    //     name: 'order_progress_remaining'
                    // },
                    {
                        data: 'incoming_stock',
                        name: 'incoming_stock'
                    },
                    {
                        data: 'incoming_stock_production',
                        name: 'incoming_stock_production'
                    },
                    {
                        data: 'avg_cost',
                        name: 'avg_cost'
                    },
                    {
                        data: 'fixed_cost',
                        name: 'fixed_cost'
                    }
                ]
            });

            $('#product_name').on('keyup change', function() {
                table.ajax.reload();
            });
        });
    </script>
@endpush
