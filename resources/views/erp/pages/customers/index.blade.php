@extends('erp.layouts.main')

@push('styles')
<style>
    @media (max-width: 768px) {

        #customerList td.desktop-only,
        #customerList th.desktop-only {
            display: none !important;
        }
    }

    #customerList {
        width: 100% !important;
        min-width: 0;
    }

    #customerList_wrapper .dataTables_scrollBody {
        /* background: #fff !important; */
        background-image: none !important;
    }
</style>
@endpush

@section('breadcrumb')
<div class="page-header sticky-top">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">Customers</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
            <li class="breadcrumb-item">Customers</li>
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
                <a href="/erp/customers/create-customer" class="btn btn-primary">
                    <i class="feather-plus me-2"></i>
                    <span>Create Customer</span>
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

                        </div>
                        <div class="col-lg-4">
                            <div class="row g-3 justify-content-end">
                                <div class="col-lg-6">
                                    <label for="name" class="fw-semibold fs-12">Customer Name</label>
                                    <input type="text" id="name" name="name" class="form-control" style="padding: 0.5rem 1rem; font-size: 0.875rem;" placeholder="Search Customer Name...">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table bg-transparent table-hover" id="customerList">
                            <thead>
                                <tr>
                                    <th class="wd-30">No</th>
                                    <th>Nama</th>
                                    <th>Phone</th>
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
<div class="modal fade" id="modalDeleteCustomer" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="formDeleteCustomer">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title text-white" id="deleteModalLabel">Hapus Customer</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menghapus customer <strong id="customerName"></strong>?</p>
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
        // Cegah reinitialisasi
        if ($.fn.DataTable.isDataTable('#customerList')) {
            $('#customerList').DataTable().clear().destroy();
        }

        const dataTable = $('#customerList').DataTable({
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
                url: "{{ url('/erp/customers/data') }}",
                data: function(d) {
                    d.name = $('#name').val();
                }
            },
            columns: [{
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false,
                },
                {
                    data: 'name',
                    name: 'name',
                },
                {
                    data: 'phone',
                    name: 'phone',
                },
                // {
                //     data: 'action',
                //     name: 'action',
                //     orderable: false,
                //     searchable: false,
                //     visible: false,
                // }
            ]
        });

        $('#name').on('keyup', function() {
            dataTable.ajax.reload();
        });

        $('#customerList tbody').on('click', 'tr', function(e) {
            if ($(e.target).closest('td.dt-control').length) return; // skip tombol +

            let $tr = $(this);
            let row = dataTable.row($tr);

            // tutup semua dulu
            $('#customerList tbody tr').removeClass('action-shown').next('.action-row').remove();

            if ($tr.hasClass('action-shown')) {
                $tr.removeClass('action-shown');
            } else {
                let actionHtml = row.data().action;

                // bikin baris tambahan di bawahnya (full colspan)
                let colCount = $tr.find('td').length; // total kolom yg ada
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
            // kalau kliknya di dalam tabel, abaikan
            if ($(e.target).closest('#customerList').length) return;

            // tutup semua action-row
            $('#customerList tbody tr').removeClass('action-shown').next('.action-row').remove();
        });

        $(document).on('click', function(e) {
            if (!$(e.target).closest('#customerList tbody tr, #customerListMobile tbody tr').length) {
                $('#customerList tbody tr.shown, #customerListMobile tbody tr.shown').each(function() {
                    var tr = $(this);
                    var table = tr.closest('table').attr('id') === 'customerList' ? dataTable : dataTableMobile;
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
        const modal = document.getElementById('modalDeleteCustomer');
        const form = document.getElementById('formDeleteCustomer');
        const nameHolder = document.getElementById('customerName');

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