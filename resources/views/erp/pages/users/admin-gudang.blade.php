@extends('erp.layouts.main')

@push('styles')
<style>
    @media (max-width: 768px) {

        #ShopManagerList td.desktop-only,
        #ShopManagerList th.desktop-only {
            display: none !important;
        }
    }

    #ShopManagerList {
        width: 100% !important;
        min-width: 0;
    }
</style>
@endpush

@section('breadcrumb')
<div class="page-header sticky-top">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">Shop Manager</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
            <li class="breadcrumb-item">Shop Manager</li>
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
                <a href="/erp/shop-manager/create-user" class="btn btn-primary">
                    <i class="feather-plus me-2"></i>
                    <span>Create Shop Manager</span>
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
                                    <label for="name" class="fw-semibold fs-12">Name</label>
                                    <input type="text" id="name" name="name" class="form-control" style="padding: 0.5rem 1rem; font-size: 0.875rem;" placeholder="Search Name...">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover" id="ShopManagerList">
                            <thead>
                                <tr>
                                    <th class="wd-30">No</th>
                                    <th>Name</th>
                                    <th>Username</th>
                                    <th>Role</th>
                                    <th class="text-end">Actions</th>
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
<div class="modal fade" id="modalDeleteShopManager" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="formDeleteShopManager">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title text-white" id="deleteModalLabel">Hapus Shop Manager</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menghapus Shop Manager <strong id="ShopManagerName"></strong>?</p>
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
        if ($.fn.DataTable.isDataTable('#ShopManagerList')) {
            $('#ShopManagerList').DataTable().clear().destroy();
        }

        const dataTable = $('#ShopManagerList').DataTable({
            processing: true,
            serverSide: true,
            searching: false,
            lengthChange: false,
            ajax: {
                url: "{{ url('/erp/shop-manager/admin-gudang/data') }}",
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
                    data: 'username',
                    name: 'username',
                },
                {
                    data: 'role',
                    name: 'role',
                },
                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false,
                    visible: false,
                }
            ]
        });

        $('#name').on('input', function() {
            dataTable.ajax.reload();
        });

        $('#ShopManagerList tbody, #ShopManagerListMobile tbody').on('click', 'tr', function() {
            var tr = $(this);
            var table = tr.closest('table').attr('id') === 'ShopManagerList' ? dataTable : dataTableMobile;
            var row = table.row(tr);

            if (row.child.isShown()) {
                row.child.hide();
                tr.removeClass('shown');
            } else {
                table.rows().every(function() {
                    if (this.child.isShown()) {
                        this.child.hide();
                        $(this.node()).removeClass('shown');
                    }
                });

                var actionMenu = row.data().action;
                row.child('<div class="dropdown">' + actionMenu + '</div>').show();
                tr.addClass('shown');
            }
        });

        $(document).on('click', function(e) {
            if (!$(e.target).closest('#ShopManagerList tbody tr, #ShopManagerListMobile tbody tr').length) {
                $('#ShopManagerList tbody tr.shown, #ShopManagerListMobile tbody tr.shown').each(function() {
                    var tr = $(this);
                    var table = tr.closest('table').attr('id') === 'ShopManagerList' ? dataTable : dataTableMobile;
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
        const modal = document.getElementById('modalDeleteShopManager');
        const form = document.getElementById('formDeleteShopManager');
        const nameHolder = document.getElementById('shopManagerName');

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
