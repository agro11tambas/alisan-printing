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
            /* background: #fff !important; */
            background-image: none !important;
        }

        #deletedRequestStockTable_wrapper .dataTables_scrollBody {
            /* background: #fff !important; */
            background-image: none !important;
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
                        </ul>
                        <div class="table-responsive">
                            <!-- <table class="table table-hover" id="requestStockTable">
                                <thead>
                                    <tr>
                                        <th class="wd-30">No</th>
                                        <th>Request By</th>
                                        <th>Date</th>
                                        <th>Items</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>

                                </tbody>
                            </table> -->
                            <div class="tab-content">
                                <div class="tab-pane fade show active" id="request-stock" role="tabpanel">
                                    <table class="table table-hover bg-transparent" id="requestStockTable">
                                        <thead>
                                            <tr>
                                                <th class="wd-30">No</th>
                                                <th>Request By</th>
                                                <th>Date</th>
                                                <th>Items</th>
                                                <th>Warehouse Status</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                                <div class="tab-pane fade" id="deleted-request-stock" role="tabpanel">
                                    <table class="table table-hover bg-transparent" id="deletedRequestStockTable">
                                        <thead>
                                            <tr>
                                                <th class="wd-30">No</th>
                                                <th>Request By</th>
                                                <th>Date</th>
                                                <th>Items</th>
                                                <th>Deleted At</th>
                                                <th>Action</th>
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
            const dataTable = $('#requestStockTable').DataTable({
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
                    url: "{{ url('/erp/productions/material-request/data') }}",
                    data: function(d) {
                        d.filter = $('#filter').val();
                        d.start_date = $('#start_date').val();
                        d.end_date = $('#end_date').val();
                        d.progress_status = $('#progress_status').val();
                    },
                },
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
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
                        data: 'status',
                        name: 'status'
                    },
                    // {
                    //     data: 'action',
                    //     name: 'action',
                    //     orderable: false,
                    //     searchable: false,
                    //     visible: false,
                    // },
                ],

            });

            let deletedTable = null;

            $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
                if ($(e.target).attr('href') === '#deleted-request-stock') {
                    if (!deletedTable) {
                        deletedTable = $('#deletedRequestStockTable').DataTable({
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
                            ajax: "{{ url('/erp/productions/stock-request/data-deleted') }}",
                            columns: [{
                                    data: 'DT_RowIndex',
                                    orderable: false,
                                    searchable: false
                                },
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
                                }
                            ]
                        });


                        $('#deletedRequestStockTable tbody').on('click', 'td.dt-control', function() {
                            let tr = $(this).closest('tr');
                            let row = deletedTable.row(tr);

                            if (row.child.isShown()) {
                                row.child.hide();
                                tr.removeClass('shown');
                            } else {
                                row.child(formatProducts(row.data().products)).show();
                                tr.addClass('shown');
                            }
                        });
                    } else {
                        deletedTable.ajax.reload();
                    }
                }
            });

            $('#progress_status').on('change', function() {
                dataTable.ajax.reload();
            })

            $('#filter').on('change', function() {
                if ($(this).val() === 'custom') {
                    $('.custom-range').removeClass('d-none');
                } else {
                    $('.custom-range').addClass('d-none');
                    dataTable.ajax.reload();
                }
            });

            $('#apply-filter').on('click', function() {
                dataTable.ajax.reload();
            });

            $('#search_type').on('change', function() {
                const selected = $(this).val();
                if (selected === 'payment_status') {
                    $('#search_keyword').addClass('d-none').val('');
                } else {
                    $('#search_keyword').removeClass('d-none');
                }
                dataTable.ajax.reload();
            });

            $('#search_keyword').on('keyup', function() {
                if ($('#search_type').val() !== 'payment_status') {
                    dataTable.ajax.reload();
                }
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
    </script>
@endpush
