@extends('erp.layouts.main')

@push('styles')
<style>
    @media (max-width: 768px) {

        #stockOpnameTable td.desktop-only,
        #stockOpnameTable th.desktop-only {
            display: none !important;
        }
    }

    #stockOpnameTable {
        width: 100% !important;
        min-width: 0;
    }

    #stockOpnameTable_wrapper .dataTables_scrollBody {
        /* background: #fff !important; */
        background-image: none !important;
    }
</style>
@endpush

@section('breadcrumb')
<div class="page-header sticky-top">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">Stock Opname</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
            <li class="breadcrumb-item">Production</li>
            <li class="breadcrumb-item">Stock Opname</li>
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
                <a href="/erp/productions/stock-opname/create-stock-opname" class="btn btn-primary">
                    <i class="feather-plus me-2"></i>
                    <span>Create Stock Opname</span>
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
                        <div class="col-lg-4">
                            <div class="row g-3 justify-content-end">
                                <div class="col-md-6">
                                    <label for="statusFilter" class="fw-semibold fs-12">Filter By</label>
                                </div>
                            </div>
                            <div class="row g-3 justify-content-end">
                                <div class="col-md-6">
                                    <select id="statusFilter" class="form-control" style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                        <option value="">All</option>
                                        <option value="Gain">Gain</option>
                                        <option value="Loss">Loss</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover bg-transparent" id="stockOpnameTable">
                            <thead>
                                <tr>
                                    <th class="wd-30">No</th>
                                    <th>Product</th>
                                    <th>Date</th>
                                    <th>Quantity</th>
                                    <th>Finished Product</th>
                                    <th>Status</th>
                                    <th>Notes</th>
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
<div class="modal fade" id="modalDeleteStockOpname" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="formDeleteStockOpname">
            @csrf
            @method('DELETE')
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title text-white" id="deleteModalLabel">Hapus StockOpname</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menghapus StockOpname <strong id="StockOpnameName"></strong>?</p>
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
@endpush

@push('scripts')
<script>
    $(document).ready(function() {
        const dataTable = $('#stockOpnameTable').DataTable({
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
                url: "{{ url('/erp/productions/stock-opname/data') }}",
                data: function(d) {
                    d.filter = $('#filter').val();
                    d.start_date = $('#start_date').val();
                    d.end_date = $('#end_date').val();
                    d.status = $('#statusFilter').val();
                }
            },
            columns: [{
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'product_name',
                    name: 'product_name'
                },
                {
                    data: 'date',
                    name: 'date'
                },
                {
                    data: 'available_quantity',
                    name: 'available_quantity'
                },
                {
                    data: 'finished_product',
                    name: 'finished_product'
                },
                {
                    data: 'status',
                    name: 'status'
                },
                {
                    data: 'notes',
                    name: 'notes'
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

        $('#statusFilter').change(function() {
            dataTable.ajax.reload();
        });

        $('#stockOpnameTable tbody').on('click', 'tr', function(e) {
            if ($(e.target).closest('td.dt-control').length) return;

            let $tr = $(this);
            let row = dataTable.row($tr);

            $('#stockOpnameTable tbody tr').removeClass('action-shown').next('.action-row').remove();

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
            if ($(e.target).closest('#stockOpnameTable').length) return;

            $('#stockOpnameTable tbody tr').removeClass('action-shown').next('.action-row').remove();
        });

        $(document).on('click', function(e) {
            if (!$(e.target).closest('#stockOpnameTable tbody tr, #stockOpnameTableMobile tbody tr').length) {
                $('#stockOpnameTable tbody tr.shown, #stockOpnameTableMobile tbody tr.shown').each(function() {
                    var tr = $(this);
                    var table = tr.closest('table').attr('id') === 'stockOpnameTable' ? dataTable : dataTableMobile;
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
        const modal = document.getElementById('modalDeleteStockOpname');
        const form = document.getElementById('formDeleteStockOpname');
        const nameHolder = document.getElementById('StockOpnameName');

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