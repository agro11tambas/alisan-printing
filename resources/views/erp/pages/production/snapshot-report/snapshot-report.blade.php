@extends('erp.layouts.main')

@push('styles')
    <style>
        #snapshotTable {
            width: 100% !important;
            min-width: 0;
        }

        #snapshotTable_wrapper .dataTables_scrollBody {
            background-image: none !important;
            height: 60vh !important;
            overflow-y: auto !important;
        }

        @media (max-width: 991px) {
            #snapshotTable_wrapper {
                display: none !important;
            }

            #snapshotMobile {
                display: block !important;
            }
        }

        @media (min-width: 992px) {
            #snapshotMobile {
                display: none !important;
            }
        }

        .report-mobile-card {
            padding: 14px 16px;
            margin: 0 12px 12px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, .06);
        }

        .report-mobile-card .product-name {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .report-mobile-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 5px 0;
            border-bottom: 1px dashed #e5e9ef;
            font-size: 13px;
        }

        .report-mobile-row:last-child {
            border-bottom: none;
        }

        .report-mobile-row .label {
            color: #6b7280;
        }

        .report-mobile-row .value {
            font-weight: 500;
        }
    </style>
@endpush

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Snapshot Report</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Production</li>
                <li class="breadcrumb-item active">Snapshot Report</li>
            </ul>
        </div>
        <div class="page-header-right ms-auto">
            <div class="page-header-right-items">
                <div class="d-flex d-md-none">
                    <a href="javascript:void(0)" class="page-header-right-close-toggle">
                        <i class="feather-arrow-left me-2"></i><span>Back</span>
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
    <div class="main-content m-0 m-md-2 m-lg-2 p-0 p-md-0 p-lg-0 pt-2 pt-md-0">
        <div class="row">
            <div class="col-lg-12">
                <div class="card stretch stretch-full">
                    <div class="card-body p-0">
                        <div class="row g-3 p-4 justify-content-between">
                            {{-- Filter Kiri --}}
                            <div class="col-lg-8">
                                <div class="row g-3">
                                    <div class="col-lg-4">
                                        <label for="product_name" class="fw-semibold fs-12">Item Name</label>
                                        <input type="text" id="product_name" name="product_name" class="form-control"
                                            style="padding: 0.5rem 1rem; font-size: 0.875rem;" placeholder="Search Item...">
                                    </div>
                                    <div class="col-lg-4">
                                        <label for="snapshot_date" class="fw-semibold fs-12">Date</label>
                                        <input type="date" id="snapshot_date" class="form-control"
                                            style="padding: 0.5rem 1rem; font-size: 0.875rem;" value="{{ date('Y-m-d') }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Desktop Table --}}
                        <div class="table-responsive">
                            <table class="table table-hover bg-transparent" id="snapshotTable">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        {{-- <th>Date</th> --}}
                                        <th>Item Name</th>
                                        {{-- <th>Current Stock</th> --}}
                                        <th>Opening Stock</th>
                                        <th>Stock In</th>
                                        <th>Assign Today</th>
                                        <th>Closing Stock</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>

                        {{-- Mobile Cards --}}
                        <div id="snapshotMobile" class="px-0 pb-4"></div>
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
            let currentRequest = null;

            let table = $('#snapshotTable').DataTable({
                processing: false,
                serverSide: false,
                deferRender: true,
                scrollY: '60vh',
                scrollCollapse: true,
                paging: false,
                searching: false,
                lengthChange: false,
                info: false,
                data: [],
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    // {
                    //     data: 'snapshot_date'
                    // },
                    {
                        data: 'product_name'
                    },
                    // {
                    //     data: 'available_quantity'
                    // },

                    {
                        data: 'opening_stock'
                    },
                    {
                        data: 'stock_in_today'
                    },
                    {
                        data: 'assign_today'
                    },
                    {
                        data: 'closing_stock'
                    },
                ],
            });

            // ── Date filter ────────────────────────────────────────
            $("#snapshot_date").on("change", function() {
                loadData(true);
            });

            // ── Load data ──────────────────────────────────────────
            function loadData(reset = false) {
                if (isLoading) return;
                if (!hasMoreData && !reset) return;
                isLoading = true;

                if (reset) {
                    allData = [];
                    currentPage = 0;
                    hasMoreData = true;
                    table.clear().draw();
                }

                if (currentRequest) currentRequest.abort();

                currentRequest = $.ajax({
                    url: "{{ url('/erp/productions/snapshot-report/data') }}",
                    type: "GET",
                    data: {
                        start: currentPage * 200,
                        length: 200,
                        product_name: $("#product_name").val(),
                        snapshot_date: $("#snapshot_date").val(),
                    },
                    success: function(res) {
                        const rows = res.data ?? [];

                        if (rows.length > 0) {
                            if (reset) allData = [];
                            allData = allData.concat(rows);
                            table.clear();
                            table.rows.add(allData).draw(false);
                            currentPage++;
                            hasMoreData = true;
                            renderMobileCards(allData);
                        } else {
                            hasMoreData = false;
                        }
                    },
                    complete: function() {
                        isLoading = false;
                        currentRequest = null;
                    },
                    error: function(xhr) {
                        if (xhr.statusText !== "abort") console.error("AJAX error", xhr);
                        isLoading = false;
                    },
                });
            }

            // ── Mobile cards ───────────────────────────────────────
            function renderMobileCards(data) {
                const $c = $("#snapshotMobile");
                $c.empty();
                data.forEach(function(row) {
                    $c.append(`
                        <div class="report-mobile-card">
                            <div class="product-name">${row.product_name}</div>                            
                            <div class="report-mobile-row"><span class="label">Opening Stock</span><span class="value">${row.opening_stock}</span></div>
                            <div class="report-mobile-row"><span class="label">Stock In</span><span class="value">${row.stock_in_today}</span></div>
                            <div class="report-mobile-row"><span class="label">Assign Today</span><span class="value">${row.assign_today}</span></div>
                            <div class="report-mobile-row"><span class="label">Closing Stock</span><span class="value">${row.closing_stock}</span></div>
                        </div>
                    `);
                });
            }

            // ── Search on Enter ────────────────────────────────────
            $("#product_name").on("keydown", function(e) {
                if (e.key === "Enter") {
                    e.preventDefault();
                    loadData(true);
                }
            });

            // ── Infinite scroll ────────────────────────────────────
            let scrollTimeout = null;
            $(".dataTables_scrollBody").on("scroll", function() {
                clearTimeout(scrollTimeout);
                const scrollTop = $(this).scrollTop();
                const scrollHeight = $(this)[0].scrollHeight;
                const clientHeight = $(this).height();

                scrollTimeout = setTimeout(() => {
                    if (scrollTop + clientHeight >= scrollHeight * 0.85) {
                        loadData();
                    }
                }, 200);
            });

            // ── Initial load (default: this_month) ────────────────
            loadData();
        });
    </script>
@endpush
