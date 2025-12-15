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

        #combinedReportTable_wrapper .dataTables_scrollBody {
            overflow-x: scroll !important;
        }

        #combinedReportTable_wrapper .dataTables_scrollHead th {
            position: sticky;
            top: 0;
            background: #f8f9fa;
            z-index: 50 !important;
            /* Menaikkan supaya selalu di atas */
        }

        #combinedReportTable_wrapper .dataTables_scrollBody td:first-child,
        #combinedReportTable_wrapper .dataTables_scrollHead th:first-child {
            position: sticky;
            left: 0;
            background: #fff;
            z-index: 60 !important;
            /* lebih tinggi dari header biasa */
        }

        #combinedReportTable_wrapper .dataTables_scrollBody td:nth-child(2),
        #combinedReportTable_wrapper .dataTables_scrollHead th:nth-child(2) {
            position: sticky;
            left: 70px;
            /* Sesuaikan lebar kolom No */
            background: #fff;
            z-index: 60 !important;
        }

        #combinedReportTable th,
        #combinedReportTable td {
            border-right: 1px solid #e5e5e5;
        }

        #combinedReportTable th.warehouse-col,
        #combinedReportTable_wrapper .dataTables_scrollHead th.warehouse-col {
            background: #e8f4ff !important;
            /* biru muda */
            color: #000;
        }

        /* Border biar rapi */
        #combinedReportTable th.warehouse-col {
            border-right: 1px solid #d0e8ff;
        }

        #combinedReportTable th.production-col,
        #combinedReportTable_wrapper .dataTables_scrollHead th.production-col {
            background: #eaffea !important;
            /* hijau muda */
            color: #000;
        }

        #combinedReportTable th.production-col {
            border-right: 1px solid #d3f5d3;
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
                        <div class="row g-3 p-4 justify-content-between">
                            <div class="col-lg-4">
                                <label for="filter" class="fw-semibold fs-12">Date</label>
                                <div class="d-flex align-items-center gap-2">
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

                                    <input type="date" id="start_date" class="form-control custom-range d-none">
                                    <input type="date" id="end_date" class="form-control custom-range d-none">
                                    <button id="apply-filter" class="btn btn-primary custom-range d-none">Apply</button>
                                </div>
                            </div>
                            <div class="col-lg-2">
                                <label for="product_name" class="fw-semibold fs-12">Product & SKU</label>
                                <input type="text" id="product_name" name="product_name" class="form-control"
                                    style="padding: 0.5rem 1rem; font-size: 0.875rem;" placeholder="Search Item...">
                            </div>
                            {{-- <div class="col-lg-4 me-2">
                                <div class="row g-3 justify-content-start">
                                </div>
                            </div> --}}
                        </div>
                        <div class="table-responsive" style="overflow-x: auto; white-space: nowrap;">
                            <button id="toggleCompleted" class="btn btn-primary btn-sm ms-3">
                                Show Completed Columns
                            </button>
                            <table class="table table-hover bg-transparent" id="combinedReportTable"
                                style="min-width: 1400px;">
                                <thead>
                                    <tr>
                                        <th class="wd-30">No</th>
                                        <th>Item Name</th>
                                        <th>SAS</th>
                                        <th class="warehouse-col">
                                            <span class="text-danger">
                                                <small>Utama</small> <br>
                                                Stock (W)
                                            </span>
                                        </th>
                                        <th class="warehouse-col">
                                            <span class="text-warning">
                                                <small>Opening</small> <br>
                                                Opening (W)
                                            </span>
                                        </th>
                                        <th class="warehouse-col">
                                            <small>Ongoing</small> <br>
                                            Incoming (W)
                                        </th>
                                        <th class="warehouse-col">
                                            <span class="text-primary">
                                                <small>Completed</small> <br>
                                                Incoming (W)
                                            </span>
                                        </th>
                                        <th class="warehouse-col">
                                            <small>Ongoing</small> <br>
                                            Outgoing (W)
                                        </th>
                                        <th class="warehouse-col ">
                                            <span class="text-primary">
                                                <small>Completed</small> <br>
                                                Outgoing (W)
                                            </span>
                                        </th>

                                        <th class="production-col">
                                            <span class="text-danger">
                                                <small>Utama</small> <br>
                                                Stock (P)
                                            </span>
                                        </th>
                                        <th class="production-col">
                                            <span class="text-warning">
                                                <small>Opening</small> <br>
                                                Opening (P)
                                            </span>
                                        </th>
                                        <th class="production-col">
                                            <small>Ongoing</small> <br>
                                            Incoming (P)
                                        </th>
                                        <th class="production-col ">
                                            <span class="text-primary">
                                                <small>Completed</small> <br>
                                                Incoming (P)
                                            </span>
                                        </th>
                                        <th class="production-col">
                                            <small>Ongoing</small> <br>
                                            Waiting List
                                        </th>
                                        <th class="production-col">
                                            <small>Ongoing</small> <br>
                                            Assigned
                                        </th>
                                        <th class="production-col">
                                            <small>Total</small> <br>
                                            Total Assigned
                                        </th>
                                        <th class="production-col">
                                            <small>Ongoing</small> <br>
                                            Finished
                                        </th>
                                        <th class="production-col">
                                            <small>Ongoing</small> <br>
                                            On Delivery
                                        </th>
                                        <th class="production-col">
                                            <span class="text-primary">
                                                <small>Completed</small> <br>
                                                Delivered
                                            </span>
                                        </th>
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
            let lastKeyword = ''; // 🔹 simpan keyword terakhir

            let table = $('#combinedReportTable').DataTable({
                processing: false,
                serverSide: false,
                deferRender: true,
                scrollY: '60vh',
                scrollX: true,
                scrollCollapse: true,
                paging: false,
                searching: false,
                lengthChange: false,
                info: false,
                order: [
                    [1, 'asc']
                ],
                columnDefs: [{
                    targets: [4, 6, 8, 10, 12, 18],
                    visible: false
                }],
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
                        data: 'opening_stock_warehouse'
                    },
                    {
                        data: 'incoming_stock'
                    },
                    {
                        data: 'incoming_stock_completed'
                    },
                    {
                        data: 'outgoing_stock'
                    },
                    {
                        data: 'outgoing_stock_completed'
                    },
                    {
                        data: 'production_available'
                    },
                    {
                        data: 'production_opening_stock'
                    },
                    {
                        data: 'incoming_stock_production'
                    },
                    {
                        data: 'incoming_stock_production_completed'
                    },
                    {
                        data: 'pending_waiting_list'
                    },
                    {
                        data: 'assigned_minus_completed'
                    },
                    {
                        data: 'assigned_total'
                    },
                    {
                        data: 'finished_product_stock'
                    },
                    {
                        data: 'on_delivery'
                    },
                    {
                        data: 'completed_delivery'
                    },
                    {
                        data: 'avg_cost'
                    },
                    {
                        data: 'fixed_cost'
                    }
                ]
            });

            // =========================
            // ✅ Fungsi utama load data
            // =========================
            function loadMoreData(reset = false) {
                if (isLoading) return;
                if (!hasMoreData && !reset) return;

                isLoading = true;

                const keyword = $('#product_name').val().trim();
                const page = reset ? 0 : currentPage;

                $.ajax({
                    url: "{{ url('/erp/report-items/data') }}",
                    type: 'GET',
                    data: {
                        start: page * 200,
                        length: 200,
                        product_name: keyword || null,
                        filter: $('#filter').val(),
                        start_date: $('#start_date').val(),
                        end_date: $('#end_date').val()
                    },
                    success: function(res) {
                        if (reset) {
                            // 🔹 kalau reset pencarian (misal keyword berubah)
                            allData = [];
                            currentPage = 0;
                            hasMoreData = true;
                            table.clear().draw();
                        }

                        if (res && res.data && res.data.length > 0) {
                            if (reset) allData = []; // hapus data lama sepenuhnya
                            allData = allData.concat(res.data);
                            table.clear();
                            table.rows.add(allData).draw(false);
                            currentPage++;
                            hasMoreData = true;
                        } else {
                            if (reset) {
                                table.clear().draw();
                                hasMoreData = true;
                                currentPage = 0;
                            } else {
                                hasMoreData = false;
                            }
                        }
                        isLoading = false;
                    },
                    error: function() {
                        isLoading = false;
                    }
                });
            }

            // 🚀 Load awal
            loadMoreData(true);

            // 🔁 Infinite scroll
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

            $('#product_name').on('input', function() {
                if ($(this).val().trim() === '') {
                    lastKeyword = '';
                    loadMoreData(true);
                }
            });

            // =========================
            // ✅ Event pencarian
            // =========================
            let searchTimeout;
            // $('#product_name').on('keyup change', function() {
            //     clearTimeout(searchTimeout);
            //     searchTimeout = setTimeout(() => {
            //         const keyword = $(this).val().trim();
            //         if (keyword !== lastKeyword) {
            //             lastKeyword = keyword;
            //             loadMoreData(true); // 🔹 reset & reload penuh
            //         }
            //     }, 200); // debounce biar gak spam
            // });

            $('#product_name').on('keypress', function(e) {
                if (e.which === 13) { // 13 = ENTER
                    e.preventDefault();

                    const keyword = $(this).val().trim();
                    if (keyword !== lastKeyword) {
                        lastKeyword = keyword;
                        loadMoreData(true); // 🔹 reset & reload data
                    }
                }
            });

            let completedIndexes = [4, 6, 8, 10, 12, 18];
            let expanded = false;

            $("#toggleCompleted").on("click", function() {
                expanded = !expanded;

                completedIndexes.forEach(i => {
                    table.column(i).visible(expanded);
                });

                $(this).text(expanded ? "Hide Completed Columns" : "Show Completed Columns");
            });

            $('#filter').on('change', function() {
                if ($(this).val() === 'custom') {
                    $('.custom-range').removeClass('d-none');
                } else {
                    $('.custom-range').addClass('d-none');
                    loadMoreData(true); // reload pakai preset date range
                }
            });

            $('#apply-filter').on('click', function() {
                loadMoreData(true);
            });

        });
    </script>
@endpush
