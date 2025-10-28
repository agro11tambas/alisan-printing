@extends('erp.layouts.main')

@push('styles')
    <style>
        @media (max-width: 768px) {

            #assignBatchTable td.desktop-only,
            #assignBatchTable th.desktop-only {
                display: none !important;
            }
        }

        #assignBatchTable {
            width: 100% !important;
            min-width: 0;
        }

        #assignBatchTable_wrapper .dataTables_scrollBody {
            /* background: #fff !important; */
            background-image: none !important;
        }
    </style>
@endpush

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Progress & Info</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Production</li>
                <li class="breadcrumb-item">Progress & Info</li>
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
                        <div class="row g-3 p-4 pb-3 justify-content-between">
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
                            <div class="col-lg-6">
                                <div class="row justify-content-end">
                                    <div class="col-lg-3">
                                        <label for="progress_status" class="fw-semibold fs-12">Progress Status</label>
                                        <select id="progress_status" class="form-control"
                                            style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                            <option value="progress">Progress</option>
                                            <option value="completed">Completed</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-3">
                                        <label for="search_type" class="fw-semibold fs-12">Search Assign Code</label>
                                        <div class="row g-3">
                                            <div class="col-md-12">
                                                <input type="text" id="search_keyword" name="search_keyword"
                                                    class="form-control search-input"
                                                    style="padding: 0.5rem 1rem; font-size: 0.875rem;"
                                                    placeholder="Search..." />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover bg-transparent" id="assignBatchTable">
                                <thead>
                                    <tr>
                                        <th class="wd-30">No</th>
                                        <th>Assign Code</th>
                                        <th>Assign List</th>
                                        <th>Note</th>
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
            const batchTable = $('#assignBatchTable').DataTable({
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
                    url: `/erp/productions/waiting-list/assign-list/data`,
                    data: function(d) {
                        d.filter = $('#filter').val();
                        d.start_date = $('#start_date').val();
                        d.end_date = $('#end_date').val();
                        d.progress_status = $('#progress_status').val(); // 🔹 Filter status
                        d.search_keyword = $('#search_keyword').val(); // 🔹 Search assign code
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'assign_code'
                    },
                    {
                        data: 'assign_products'
                    },
                    {
                        data: 'note'
                    },
                    {
                        data: 'action',
                        visible: false,
                        orderable: false,
                        searchable: false
                    },
                ],
                order: [
                    [1, 'desc']
                ],
                language: {
                    processing: '<div class="spinner-border text-primary" role="status"></div>'
                }
            });

            $('#assignBatchTable tbody').on('click', 'tr', function(e) {
                if ($(e.target).closest('td.dt-control').length) return;
                let $tr = $(this);
                let row = batchTable.row($tr);
                $('#assignBatchTable tbody tr').removeClass('action-shown').next('.action-row').remove();

                if ($tr.hasClass('action-shown')) {
                    $tr.removeClass('action-shown');
                } else {
                    let actionHtml = row.data().action;
                    let colCount = $tr.find('td').length;
                    let $actionRow = $(`
                <tr class="action-row">
                    <td colspan="${colCount}">
                        <div class="d-flex justify-content-center py-2">${actionHtml}</div>
                    </td>
                </tr>
            `);
                    $tr.after($actionRow);
                    $tr.addClass('action-shown');
                }
            });

            $(document).on('click', function(e) {
                if ($(e.target).closest('#assignBatchTable').length) return;
                $('#assignBatchTable tbody tr').removeClass('action-shown').next('.action-row').remove();
            });

            $('#filter').on('change', function() {
                if ($(this).val() === 'custom') {
                    $('.custom-range').removeClass('d-none');
                } else {
                    $('.custom-range').addClass('d-none');
                    batchTable.ajax.reload();
                }
            });

            $('#apply-filter').on('click', function() {
                batchTable.ajax.reload();
            });

            $('#progress_status').on('change', function() {
                batchTable.ajax.reload();
            });

            let searchTimeout;
            $('#search_keyword').on('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => batchTable.ajax.reload(), 500);
            });
        });
    </script>
@endpush
