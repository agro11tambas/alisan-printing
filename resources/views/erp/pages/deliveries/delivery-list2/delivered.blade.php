@extends('erp.layouts.main')

@section('breadcrumb')
<div class="page-header sticky-top">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">Order</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
            <li class="breadcrumb-item">Order</li>
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
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="order_number" class="fw-semibold fs-12">Order Number</label>
                                    <input type="text" id="order_number" name="order_number" class="form-control" style="padding: 0.5rem 1rem; font-size: 0.875rem;" placeholder="Search Order Number...">
                                </div>
                                <div class="col-md-6">
                                    <label for="customer_name" class="fw-semibold fs-12">Customer</label>
                                    <input type="text" id="customer_name" name="customer_name" class="form-control" style="padding: 0.5rem 1rem; font-size: 0.875rem;" placeholder="Search Customer...">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover" id="deliveryListTable">
                            <thead>
                                <tr>
                                    <th class="wd-30">No</th>
                                    <th>Order Number</th>
                                    <th>Order Date</th>
                                    <th>Customer</th>
                                    <th>Address</th>
                                    <th>Google Map</th>
                                    <th>Status</th>
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
<div class="modal fade" id="modalMarkAsCompletedOrder" tabindex="-1" aria-labelledby="markAsCompletedModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="formMarkAsCompletedOrder">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title text-white" id="markAsCompletedModalLabel">Completed Order</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Selesaikan Order <strong id="OrderName"></strong>?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-md" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-md">Completed</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endpush

@push('scripts')
<script>
    $(document).ready(function() {
        const dataTable = $('#deliveryListTable').DataTable({
            processing: true,
            serverSide: true,
            searching: false,
            lengthChange: false,
            ajax: {
                url: "{{ url('/erp/delivered/data') }}",
                data: function(d) {
                    d.start_date = $('#start_date').val();
                    d.end_date = $('#end_date').val();
                    d.order_number = $('#order_number').val();
                    d.customer_name = $('#customer_name').val();
                }
            },
            columns: [{
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'order_number',
                    name: 'order_number'
                },
                {
                    data: 'order_date',
                    name: 'order_date'
                },
                {
                    data: 'customer',
                    name: 'customer'
                },
                {
                    data: 'shipping_address',
                    name: 'shipping_address'
                },
                {
                    data: 'google_maps',
                    name: 'google_maps'
                },
                {
                    data: 'status',
                    name: 'status'
                },
                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false
                },
            ],

        });

        $('#start_date, #end_date, #order_number, #customer_name').on('change keyup', function() {
            dataTable.ajax.reload();
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('modalMarkAsCompletedOrder');
        const form = document.getElementById('formMarkAsCompletedOrder');
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
