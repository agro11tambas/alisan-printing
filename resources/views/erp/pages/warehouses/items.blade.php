@extends('erp.layouts.main')

@push('styles')
<style>
    #warehouseTable {
        width: 100% !important;
        min-width: 0;
    }
</style>
@endpush

@section('breadcrumb')
<div class="page-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">Warehouse</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
            <li class="breadcrumb-item">Warehouse</li>
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
                <a href="/erp/warehouses/create-item" class="btn btn-primary">
                    <i class="feather-plus me-2"></i>
                    <span>Create Item</span>
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
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="start_date" class="fw-semibold fs-12">Start Date</label>
                                    <input type="date" id="start_date" name="start_date" class="form-control" style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                </div>
                                <div class="col-md-6">
                                    <label for="end_date" class="fw-semibold fs-12">Due Date</label>
                                    <input type="date" id="end_date" name="end_date" class="form-control" style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="row g-3 justify-content-end">
                                <div class="col-lg-6">
                                    <label for="product_name" class="fw-semibold fs-12">Item Name</label>
                                    <input type="text" id="product_name" name="product_name" class="form-control" style="padding: 0.5rem 1rem; font-size: 0.875rem;" placeholder="Search Item...">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover" id="warehouseTable">
                            <thead>
                                <tr>
                                    <th class="wd-30">No</th>
                                    <th>Item Name</th>
                                    <th>Beginning Stock</th>
                                    <th>Stock In</th>
                                    <th>Stock Out</th>
                                    <th>Final Stock</th>
                                    <th>Date</th>
                                    <th>Note</th>
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
<div class="modal fade" id="modalDeleteItem" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="formDeleteItem">
            @csrf
            @method('DELETE')
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title text-white" id="deleteModalLabel">Hapus Item</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menghapus Item <strong id="itemName"></strong>?</p>
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
        const table = $('#warehouseTable').DataTable({
            processing: true,
            serverSide: true,
            searching: false,
            lengthChange: false,
            ajax: {
                url: "{{ url('/erp/warehouses/items/data') }}",
                data: function(d) {
                    d.start_date = $('#start_date').val();
                    d.end_date = $('#end_date').val();
                    d.product_name = $('#product_name').val();
                }
            },
            columns: [{
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'product',
                    name: 'product'
                },
                {
                    data: 'stok_awal',
                    name: 'stok_awal'
                },
                {
                    data: 'barang_masuk',
                    name: 'barang_masuk'
                },
                {
                    data: 'barang_keluar',
                    name: 'barang_keluar'
                },
                {
                    data: 'stok_akhir',
                    name: 'stok_akhir'
                },
                {
                    data: 'date_change',
                    name: 'date_change'
                },
                {
                    data: 'note',
                    name: 'note'
                },
                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false
                }
            ]
        });

        $('#start_date, #end_date, #product_name').on('change keyup', function() {
            table.ajax.reload();
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('modalDeleteItem');
        const form = document.getElementById('formDeleteItem');
        const nameHolder = document.getElementById('ItemName');

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
