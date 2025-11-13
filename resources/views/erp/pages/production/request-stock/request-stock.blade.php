@extends('erp.layouts.main')

@push('styles')
    <style>
        @media (max-width: 768px) {

            #requestStockTable td.desktop-only,
            #requestStockTable th.desktop-only {
                display: none !important;
            }
        }

        #requestStockTable {
            width: 100% !important;
            min-width: 0;
        }

        #requestStockTable_wrapper .dataTables_scrollBody {
            background-image: none !important;
            height: 60vh !important;
            overflow-y: auto !important;
        }

        #deletedRequestStockTable_wrapper .dataTables_scrollBody {
            background-image: none !important;
            height: 60vh !important;
            overflow-y: auto !important;
        }

        #requestSummaryTable_wrapper .dataTables_scrollBody {
            background-image: none !important;
            height: 60vh !important;
            overflow-y: auto !important;
        }

        .dataTables_scrollBody {
            scroll-behavior: smooth;
        }

        #requestStockTable tbody tr {
            animation: fadeIn 0.3s ease-in;
        }
    </style>
@endpush

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Request Stocks</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Production</li>
                <li class="breadcrumb-item">Request Stocks</li>
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
                    <a href="/erp/productions/material-request/create" class="btn btn-primary">
                        <i class="feather-plus me-2"></i>
                        <span>Create Request Stock</span>
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
    @if (session('success'))
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: "{{ session('success') }}",
            });
        </script>
    @endif
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
            <div class="col-lg-12">
                <div class="card stretch stretch-full">
                    <div class="card-body p-0">
                        <div class="row g-3 p-4 justify-content-between">
                            <div class="col-lg-4 me-2">
                                <label for="" class="fw-semibold fs-12">Date</label>
                                <div class="d-flex align-items-center gap-2 page-header-right-items-wrapper">
                                    <div class="col-auto">
                                        <select id="filter" class="form-control"
                                            style="padding: 0.5rem 1rem; font-size: 0.875rem; width: 200px !important;">
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
                                        <input type="date" id="start_date" class="form-control"
                                            style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                    </div>
                                    <div class="col-auto custom-range d-none">
                                        <input type="date" id="end_date" class="form-control"
                                            style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                    </div>
                                    <div class="col-auto custom-range d-none">
                                        <button id="apply-filter" class="btn btn-primary">Apply</button>
                                    </div>
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
                                        <label for="search_product" class="fw-semibold fs-12">Search Product</label>
                                        <input type="text" id="search_product" class="form-control"
                                            placeholder="Product name...">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <ul class="nav nav-tabs mb-3" id="saleListTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="request-stock-tab" data-bs-toggle="tab" href="#request-stock"
                                    role="tab">Request Stock</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="deleted-request-stock-tab" data-bs-toggle="tab"
                                    href="#deleted-request-stock" role="tab">Deleted Request Stock</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="request-summary-tab" data-bs-toggle="tab" href="#request-summary"
                                    role="tab">Request Summary</a>
                            </li>
                        </ul>
                        <div class="table-responsive">
                            <div class="tab-content">
                                <div class="tab-pane fade show active" id="request-stock" role="tabpanel">
                                    <table class="table table-hover bg-transparent" id="requestStockTable">
                                        <thead>
                                            <tr>
                                                {{-- <th class="wd-30">No</th> --}}
                                                <th class="wd-150">Request By</th>
                                                <th class="wd-150">Date</th>
                                                <th class="wd-550">Items</th>
                                                <th class="wd-100">Warehouse Status</th>
                                                <th class="wd-150">Verified By</th>
                                                {{-- <th>Status</th> --}}
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                                <div class="tab-pane fade" id="deleted-request-stock" role="tabpanel">
                                    <table class="table table-hover bg-transparent" id="deletedRequestStockTable">
                                        <thead>
                                            <tr>
                                                {{-- <th class="wd-30">No</th> --}}
                                                <th class="wd-150">Request By</th>
                                                <th class="wd-150">Date</th>
                                                <th class="wd-550">Items</th>
                                                <th class="wd-150">Deleted At</th>
                                                <th class="wd-100">Action</th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                                <div class="tab-pane fade" id="request-summary" role="tabpanel">
                                    <table class="table table-hover bg-transparent" id="requestSummaryTable">
                                        <thead>
                                            <tr>
                                                <th class="wd-300">Product</th>
                                                <th class="wd-150">SKU</th>
                                                <th class="wd-150">Total Requested Qty</th>
                                                {{-- <th class="wd-100">Unit</th> --}}
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('modals')
    <div class="modal fade" id="modalDeleteRequestStock" tabindex="-1" aria-labelledby="deleteModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" id="formDeleteRequestStock">
                @csrf
                @method('DELETE')
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title text-white" id="deleteModalLabel">Hapus RequestStock</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Apakah Anda yakin ingin menghapus RequestStock <strong id="RequestStockName"></strong>?</p>
                        <p class="text-muted">Data yang dihapus tidak dapat dikembalikan.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-md" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger btn-md">Hapus</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="modalDeleteEmptyRequestStock" tabindex="-1" aria-labelledby="deleteEmptyModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" id="formDeleteEmptyRequestStock">
                @csrf
                @method('DELETE')
                <div class="modal-content">
                    <div class="modal-header bg-warning text-white">
                        <h5 class="modal-title text-white" id="deleteEmptyModalLabel">Hapus RequestStock (Empty)</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Apakah Anda yakin ingin menghapus Request Stock <strong id="EmptyRequestStockName"></strong>?
                        </p>
                        <p class="text-muted">⚠️ Request Stock ini belum di-issued maupun di-received.</p>
                        <p class="text-danger mb-0">Aksi ini hanya akan <strong>mengurangi incoming stock</strong> di
                            Production.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-md" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning btn-md">Hapus</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="modalChangeStatus" tabindex="-1" aria-labelledby="changeStatusModal"
        aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" id="formChangeStatus">
                @csrf
                @method('PUT')
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title text-white" id="changeStatusModal">Verifikasi</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Apakah Anda yakin ingin Mengubah Status RequestStock <strong id="RequestStockName"></strong>?
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-md" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-md">Change</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="modalResponsibilityRequest" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title text-white">Konfirmasi Tanggung Jawab</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Jika terjadi kesalahan permintaan bahan, apakah Anda siap bertanggung jawab atas hasil Request Stock
                        ini?</p>
                    <p class="text-muted mb-0">Pastikan data permintaan sudah sesuai sebelum melanjutkan.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-md" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-warning btn-md" id="btnConfirmResponsibilityRequest">
                        Ya, Saya Bertanggung Jawab
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalForceDeleteRequestStock" tabindex="-1" aria-labelledby="forceDeleteModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" id="formForceDeleteRequestStock">
                @csrf
                @method('DELETE')
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title text-white" id="forceDeleteModalLabel">Hapus Permanen RequestStock</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Apakah Anda yakin ingin <strong>menghapus permanen</strong> RequestStock <strong
                                id="ForceRequestStockName"></strong>?</p>
                        <p class="text-muted">⚠️ Data yang dihapus permanen <b>tidak bisa dikembalikan</b>.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-md" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger btn-md">Hapus Permanen</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="modalRestoreRequestStock" tabindex="-1" aria-labelledby="restoreModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" id="formRestoreRequestStock">
                @csrf
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title text-white" id="restoreModalLabel">Restore Order</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Apakah Anda yakin ingin mengembalikan Request Stock <strong
                                id="RestoreRequestStockName"></strong>?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-md" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success btn-md">Restore</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {

            let allData = [];
            let currentPage = 0;
            let isLoading = false;
            let hasMoreData = true;

            const dataTable = $('#requestStockTable').DataTable({
                processing: false,
                serverSide: false,
                scrollY: '60vh',
                scrollCollapse: true,
                paging: false,
                searching: false,
                info: false,
                lengthChange: false,
                order: [
                    [1, 'desc']
                ],
                data: [],
                columns: [
                    // {
                    //     data: 'DT_RowIndex',
                    //     orderable: false,
                    //     searchable: false
                    // },
                    {
                        data: 'requested_by',
                        name: 'requested_by'
                    },
                    {
                        data: 'requested_at',
                        name: 'requested_at'
                    },
                    {
                        data: 'items',
                        name: 'items'
                    },
                    {
                        data: 'warehouse_status',
                        name: 'warehouse_status'
                    },
                    {
                        data: 'verified_by',
                        name: 'verified_by'
                    },
                ],
            });

            let searchTimer = null;
            let currentRequest = null;
            let currentDeletedRequest = null;

            function loadMoreData() {
                if (isLoading || !hasMoreData) return;
                isLoading = true;

                // 🚫 Batalkan request sebelumnya kalau masih jalan
                if (currentRequest) {
                    currentRequest.abort();
                }

                currentRequest = $.ajax({
                    url: "{{ url('/erp/productions/material-request/data') }}",
                    type: 'GET',
                    data: {
                        start: currentPage * 50,
                        length: 50,
                        filter: $('#filter').val(),
                        start_date: $('#start_date').val(),
                        end_date: $('#end_date').val(),
                        progress_status: $('#progress_status').val(),
                        search_product: $('#search_product').val(),
                    },
                    success: function(response) {
                        if (response && response.data && response.data.length > 0) {
                            allData = allData.concat(response.data);
                            dataTable.clear();
                            dataTable.rows.add(allData).draw(false);
                            currentPage++;
                        } else {
                            hasMoreData = false;
                        }
                    },
                    complete: function() {
                        isLoading = false;
                        currentRequest = null;
                    },
                    error: function(xhr) {
                        if (xhr.statusText !== 'abort') {
                            console.error('Error:', xhr.responseJSON?.message);
                            alert(xhr.responseJSON?.message || 'Gagal memuat data.');
                        }
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

            function resetAndReload() {
                allData = [];
                currentPage = 0;
                hasMoreData = true;
                dataTable.clear().draw();
                loadMoreData();
            }

            // ============================================================
            // 🔹 FIXED FILTER HANDLER (same behavior as design/assign batch)
            // ============================================================

            // 🔸 Kalau filter tanggal berubah
            $('#filter').on('change', function() {
                const val = $(this).val();
                const isDeletedTab = $('a[data-bs-toggle="tab"][href="#deleted-request-stock"]').parent()
                    .hasClass('active');

                // Kalau custom range → tampilkan input tanggal tapi jangan reload
                if (val === 'custom') {
                    $('.custom-range').removeClass('d-none');
                    return;
                }

                // Selain custom → sembunyikan input dan reload
                $('.custom-range').addClass('d-none');
                if (isDeletedTab) {
                    resetAndReloadDeleted();
                } else {
                    resetAndReload();
                }
            });

            // 🔸 Tombol Apply Filter → baru reload kalau pakai custom range
            $('#apply-filter').on('click', function() {
                const isDeletedTab = $('a[data-bs-toggle="tab"][href="#deleted-request-stock"]').parent()
                    .hasClass('active');
                if (isDeletedTab) {
                    resetAndReloadDeleted();
                } else {
                    resetAndReload();
                }
            });

            // 🔸 Filter lain (progress, search, product, date input) tetap auto reload
            $('#progress_status, #search_type, #search_keyword, #search_product, #start_date, #end_date')
                .on('change keyup input paste', function() {
                    clearTimeout(searchTimer);
                    searchTimer = setTimeout(() => {
                        const isDeletedTab = $('a[data-bs-toggle="tab"][href="#deleted-request-stock"]')
                            .parent().hasClass('active');
                        if (isDeletedTab) {
                            resetAndReloadDeleted();
                        } else {
                            resetAndReload();
                        }
                    }, 100);
                });

            $('#requestStockTable tbody').on('click', 'tr', function(e) {
                if ($(e.target).closest('td.dt-control').length) return;

                let $tr = $(this);
                let row = dataTable.row($tr);

                $('#requestStockTable tbody tr').removeClass('action-shown').next('.action-row').remove();

                if ($tr.hasClass('action-shown')) {
                    $tr.removeClass('action-shown');
                } else {
                    let actionHtml = row.data().action;
                    let colCount = $tr.find('td').length;
                    let $actionRow = $(`
                <tr class="action-row">
                    <td colspan="${colCount}">
                        <div class="d-flex justify-content-center">
                        ${actionHtml}
                        </div>
                    </td>
                </tr>
            `);

                    $tr.after($actionRow);
                    $tr.addClass('action-shown');
                }
            });

            $(document).on('click', function(e) {
                if ($(e.target).closest('#requestStockTable').length) return;
                $('#requestStockTable tbody tr').removeClass('action-shown').next('.action-row').remove();
            });

            let deletedAllData = [];
            let deletedCurrentPage = 0;
            let deletedIsLoading = false;
            let deletedHasMoreData = true;
            let deletedTable = null;
            let deletedTableInitialized = false;

            function initDeletedTable() {
                if (deletedTableInitialized) return;

                deletedTable = $('#deletedRequestStockTable').DataTable({
                    processing: false,
                    serverSide: false,
                    scrollY: '60vh',
                    scrollCollapse: true,
                    paging: false,
                    searching: false,
                    info: false,
                    lengthChange: false,
                    data: [],
                    columns: [
                        // {
                        //     data: 'DT_RowIndex',
                        //     orderable: false,
                        //     searchable: false
                        // },
                        {
                            data: 'requested_by',
                            name: 'requested_by'
                        },
                        {
                            data: 'requested_at',
                            name: 'requested_at'
                        },
                        {
                            data: 'items',
                            name: 'items'
                        },
                        {
                            data: 'deleted_at',
                            name: 'deleted_at'
                        },
                        {
                            data: 'action',
                            orderable: false,
                            searchable: false
                        },
                    ]
                });

                deletedTableInitialized = true;

                $('#deletedRequestStockTable').closest('.dataTables_scrollBody').on('scroll', function() {
                    clearTimeout(scrollTimeout);

                    const scrollTop = $(this).scrollTop();
                    const scrollHeight = $(this)[0].scrollHeight;
                    const clientHeight = $(this).height();

                    scrollTimeout = setTimeout(() => {
                        if (scrollTop + clientHeight >= scrollHeight * 0.85) {
                            loadMoreDeletedData();
                        }
                    }, 200);
                });
            }

            function loadMoreDeletedData() {
                if (deletedIsLoading || !deletedHasMoreData) return;
                deletedIsLoading = true;

                if (currentDeletedRequest) {
                    currentDeletedRequest.abort();
                }

                currentDeletedRequest = $.ajax({
                    url: "{{ url('/erp/productions/stock-request/data-deleted') }}",
                    type: 'GET',
                    data: {
                        start: deletedCurrentPage * 50,
                        length: 50,
                        filter: $('#filter').val(),
                        start_date: $('#start_date').val(),
                        end_date: $('#end_date').val(),
                        progress_status: $('#progress_status').val(),
                    },
                    success: function(response) {
                        if (response && response.data && response.data.length > 0) {
                            deletedAllData = deletedAllData.concat(response.data);
                            deletedTable.clear();
                            deletedTable.rows.add(deletedAllData).draw(false);
                            deletedCurrentPage++;
                        } else {
                            deletedHasMoreData = false;
                        }
                    },
                    complete: function() {
                        deletedIsLoading = false;
                        currentDeletedRequest = null;
                    },
                    error: function(xhr) {
                        if (xhr.statusText !== 'abort') {
                            console.error('Error:', xhr.responseJSON?.message);
                        }
                        deletedIsLoading = false;
                    }
                });
            }


            function resetAndReloadDeleted() {
                deletedAllData = [];
                deletedCurrentPage = 0;
                deletedHasMoreData = true;
                if (deletedTable) {
                    deletedTable.clear().draw();
                }
                loadMoreDeletedData();
            }

            $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
                if ($(e.target).attr('href') === '#deleted-request-stock') {
                    if (!deletedTableInitialized) {
                        initDeletedTable();
                        loadMoreDeletedData();
                    } else {
                        resetAndReloadDeleted();
                    }
                }
            });

            $(document).on('click', function(e) {
                if (!$(e.target).closest('#requestStockTable tbody tr, #requestStockTableMobile tbody tr')
                    .length) {
                    $('#requestStockTable tbody tr.shown, #requestStockTableMobile tbody tr.shown').each(
                        function() {
                            var tr = $(this);
                            var table = tr.closest('table').attr('id') === 'requestStockTable' ?
                                dataTable : dataTableMobile;
                            var row = table.row(tr);
                            if (row.child.isShown()) {
                                row.child.hide();
                                tr.removeClass('shown');
                            }
                        });
                }
            });

            let summaryTableInitialized = false;
            let summaryTable = null;

            function initSummaryTable() {
                if (summaryTableInitialized) return;

                summaryTable = $('#requestSummaryTable').DataTable({
                    processing: true,
                    serverSide: false,
                    ajax: {
                        url: "{{ url('/erp/material-request/summary') }}",
                        data: function(d) {
                            d.filter = $('#filter').val();
                            d.start_date = $('#start_date').val();
                            d.end_date = $('#end_date').val();
                        },
                    },
                    columns: [{
                            data: 'product_name',
                            name: 'product_name'
                        },
                        {
                            data: 'sku',
                            name: 'sku'
                        },
                        {
                            data: 'total_requested_qty',
                            name: 'total_requested_qty'
                        },
                        // {
                        //     data: 'unit',
                        //     name: 'unit'
                        // },
                    ],
                    order: [
                        [2, 'desc']
                    ],
                    paging: false,
                    searching: false,
                    info: false,
                    scrollY: '60vh',
                    scrollCollapse: true,
                });

                summaryTableInitialized = true;
            }

            // Saat tab “Request Summary” dibuka
            $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
                if ($(e.target).attr('href') === '#request-summary') {
                    if (!summaryTableInitialized) {
                        initSummaryTable();
                    } else {
                        summaryTable.ajax.reload();
                    }
                }
            });

        });

        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('modalDeleteRequestStock');
            const form = document.getElementById('formDeleteRequestStock');
            const nameHolder = document.getElementById('RequestStockName');

            modal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-id');
                const name = button.getAttribute('data-name');
                const url = button.getAttribute('data-url');

                form.action = url;
                nameHolder.textContent = name;
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('modalChangeStatus');
            const form = document.getElementById('formChangeStatus');
            const nameHolder = document.getElementById('OrderName');

            modal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-id');
                const name = button.getAttribute('data-name');
                const url = button.getAttribute('data-url');

                form.action = url;
                nameHolder.textContent = name;
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('modalForceDeleteRequestStock');
            const form = document.getElementById('formForceDeleteRequestStock');
            const nameHolder = document.getElementById('ForceRequestStockName');

            modal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-id');
                const name = button.getAttribute('data-name');
                const url = button.getAttribute('data-url');

                form.action = url;
                nameHolder.textContent = name;
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('modalRestoreRequestStock');
            const form = document.getElementById('formRestoreRequestStock');
            const nameHolder = document.getElementById('RestoreRequestStockName');

            modal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-id');
                const name = button.getAttribute('data-name');
                const url = button.getAttribute('data-url');

                form.action = url;
                nameHolder.textContent = name;
            });
        });

        $(document).ready(function() {
            let pendingFormAction = null;

            // Tangkap form verifikasi
            const verifyForm = $('#formChangeStatus');

            // Saat klik tombol "Change" pertama
            verifyForm.on('submit', function(e) {
                e.preventDefault();

                pendingFormAction = $(this).attr('action');
                $('#modalChangeStatus').modal('hide');

                // Tampilkan modal konfirmasi tanggung jawab
                setTimeout(() => {
                    $('#modalResponsibilityRequest').modal('show');
                }, 300);
            });

            // Saat klik "Ya, Saya Bertanggung Jawab" di modal kedua
            $('#btnConfirmResponsibilityRequest').on('click', function() {
                $('#modalResponsibilityRequest').modal('hide');

                $.ajax({
                    url: pendingFormAction,
                    method: 'POST',
                    data: verifyForm.serialize(),
                    success: function(response) {
                        Swal.close();
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: 'Status Request Stock berhasil diperbarui.',
                        }).then(() => {
                            window.location.reload();
                        });
                    },
                    error: function(xhr) {
                        Swal.close();
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: xhr.responseJSON?.message ||
                                'Terjadi kesalahan saat memperbarui status.',
                        });
                    }
                });
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('modalDeleteEmptyRequestStock');
            const form = document.getElementById('formDeleteEmptyRequestStock');
            const nameHolder = document.getElementById('EmptyRequestStockName');

            modal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-id');
                const name = button.getAttribute('data-name');
                const url = button.getAttribute('data-url');

                form.action = url;
                nameHolder.textContent = name;
            });
        });
    </script>
@endpush
