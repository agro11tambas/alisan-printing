@extends('erp.layouts.main')

@push('styles')
<style>
    @media (max-width: 768px) {

        #waitingListTable td.desktop-only,
        #waitingListTable th.desktop-only {
            display: none !important;
        }
    }

    #waitingListTable {
        width: 100% !important;
        min-width: 0;
    }

    #waitingListTable_wrapper .dataTables_scrollBody {
        /* background: #fff !important; */
        background-image: none !important;
    }
</style>
@endpush

@section('breadcrumb')
<div class="page-header sticky-top">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">Waiting List</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
            <li class="breadcrumb-item">Production</li>
            <li class="breadcrumb-item">Waiting List</li>
        </ul>
    </div>
</div>
@endsection

@section('content')
@if(session('success'))
<script>
    Swal.fire({
        icon: 'success',
        title: 'Berhasil!',
        text: "{{ session('success') }}",
    });
</script>
@endif
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
    <div class="row">
        <div class="col-lg-12">
            <div class="card stretch stretch-full">
                <div class="card-body p-0">
                    <div class="row g-3 p-4 justify-content-between">
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
                        <div class="col-lg-6">
                            <div class="row justify-content-end">
                                <div class="col-lg-3">
                                    <label for="progress_status" class="fw-semibold fs-12">Progress Status</label>
                                    <select id="progress_status" class="form-control" style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                        <option value="progress">Progress</option>
                                        <option value="completed">Completed</option>
                                    </select>
                                </div>
                                <div class="col-lg-6">
                                    <label for="search_type" class="fw-semibold fs-12">Filter By</label>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <select id="search_type" class="form-control" style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                                <option value="order_number">Order Number</option>
                                                <option value="customer">Customer</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <input type="text" id="search_keyword" name="search_keyword" class="form-control search-input"
                                                style="padding: 0.5rem 1rem; font-size: 0.875rem;" placeholder="Search..." />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover bg-transparent" id="waitingListTable">
                            <thead>
                                <tr>
                                    <th class="wd-30">No</th>
                                    <th>Order Number</th>
                                    {{-- <th>Order Date</th> --}}
                                    <th>Customer</th>
                                    <th>Progress</th>
                                    <!-- <th class="text-end">Actions</th> -->
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

@push('modals')
<div class="modal fade" id="modalDeleteOrder" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="formDeleteOrder">
            @csrf
            @method('DELETE')
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title text-white" id="deleteModalLabel">Hapus Order</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menghapus Order <strong id="OrderName"></strong>?</p>
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

<div class="modal fade" id="modalChangeStatus" tabindex="-1" aria-labelledby="changeStatusModal" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="formChangeStatus">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title text-white" id="changeStatusModal">Ubah Status</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin Mengubah Status Order <strong id="OrderName"></strong>?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-md" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-md">Change</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endpush

@push('scripts')
<script>
    $(document).ready(function() {
        const dataTable = $('#waitingListTable').DataTable({
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
                url: "{{ url('/erp/productions/waiting-list/data') }}",
                data: function(d) {
                    d.filter = $('#filter').val();
                    d.start_date = $('#start_date').val();
                    d.end_date = $('#end_date').val();
                    d.search_type = $('#search_type').val();
                    d.search_keyword = $('#search_keyword').val();
                    d.payment_status = $('#search_payment_status').val();
                    d.progress_status = $('#progress_status').val();
                },
                error: function(xhr) {
                    console.error('Error response:', xhr.responseJSON);
                    alert(xhr.responseJSON.message);
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
                // {
                //     data: 'date',
                //     name: 'date'
                // },
                {
                    data: 'customer',
                    name: 'customer'
                },
                {
                    data: 'progress',
                    name: 'progress'
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

        $('#waitingListTable tbody').on('click', 'tr', function(e) {
            if ($(e.target).closest('td.dt-control').length) return;

            let $tr = $(this);
            let row = dataTable.row($tr);

            $('#waitingListTable tbody tr').removeClass('action-shown').next('.action-row').remove();

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
            if ($(e.target).closest('#waitingListTable').length) return;

            $('#waitingListTable tbody tr').removeClass('action-shown').next('.action-row').remove();
        });

        $(document).on('click', function(e) {
            if (!$(e.target).closest('#waitingListTable tbody tr, #waitingListTableMobile tbody tr').length) {
                $('#waitingListTable tbody tr.shown, #waitingListTableMobile tbody tr.shown').each(function() {
                    var tr = $(this);
                    var table = tr.closest('table').attr('id') === 'waitingListTable' ? dataTable : dataTableMobile;
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
        const modal = document.getElementById('modalDeleteOrder');
        const form = document.getElementById('formDeleteOrder');
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
</script>
@endpush