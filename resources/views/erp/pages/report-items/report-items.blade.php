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
            height: 60vh !important;
            overflow-y: auto !important;
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

            let allData = [];
            let currentPage = 0;
            let isLoading = false;
            let hasMoreData = true;

            let table = $('#combinedReportTable').DataTable({
                processing: false,
                serverSide: false,
                deferRender: true,
                scrollY: '60vh',
                scrollCollapse: true,
                paging: false,
                searching: false,
                lengthChange: false,
                info: false,
                order: [
                    [1, 'asc']
                ],
                data: [],
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'name'
                    },
                    {
                        data: 'stock_after_sales'
                    },
                    {
                        data: 'inventory_stock'
                    },
                    {
                        data: 'production_available'
                    },
                    {
                        data: 'finished_product_stock'
                    },
                    // { data: 'order_progress_remaining' },
                    {
                        data: 'incoming_stock'
                    },
                    {
                        data: 'incoming_stock_production'
                    },
                    {
                        data: 'avg_cost'
                    },
                    {
                        data: 'fixed_cost'
                    }
                ]
            });

            function loadMoreData() {
                if (isLoading || !hasMoreData) return;
                isLoading = true;

                $.ajax({
                    url: "{{ url('/erp/report-items/data') }}",
                    type: 'GET',
                    data: {
                        start: currentPage * 15,
                        length: 15,
                        product_name: $('#product_name').val(),
                    },
                    success: function(res) {
                        if (res && res.data && res.data.length > 0) {
                            allData = allData.concat(res.data);
                            table.clear();
                            table.rows.add(allData).draw(false);
                            currentPage++;
                        } else {
                            hasMoreData = false;
                        }
                        isLoading = false;
                    },
                    error: function(xhr) {
                        console.error('❌ error response:', xhr.responseJSON);
                        isLoading = false;
                    }
                });
            }

            loadMoreData();

            let scrollTimeout = null;
            $('.dataTables_scrollBody').on('scroll', function() {
                clearTimeout(scrollTimeout);
                const scrollTop = $(this).scrollTop();
                const scrollHeight = $(this)[0].scrollHeight;
                const clientHeight = $(this).height();

                scrollTimeout = setTimeout(() => {
                    if (scrollTop + clientHeight >= scrollHeight * 0.85) {
                        loadMoreData();
                    }
                }, 200);
            });

            $('#product_name').on('keyup change', function() {
                allData = [];
                currentPage = 0;
                hasMoreData = true;
                table.clear().draw();
                loadMoreData();
            });
        });
    </script>
@endpush
