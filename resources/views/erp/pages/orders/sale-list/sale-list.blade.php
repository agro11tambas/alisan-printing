@extends('erp.layouts.main')

@section('breadcrumb')
<div class="page-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">Order</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
            <li class="breadcrumb-item">Order</li>
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
                <a href="/erp/orders/create-order" class="btn btn-primary">
                    <i class="feather-plus me-2"></i>
                    <span>Create Order</span>
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
                        <table class="table table-hover" id="saleListTable">
                            <thead>
                                <tr>
                                    <th class="wd-30">No</th>
                                    <th>Order Number</th>
                                    <th>Order Date</th>
                                    <th>Customer</th>
                                    <th>Total Amount</th>
                                    <th>Discount</th>
                                    <th>Grand Total</th>
                                    <th>Paid Amount</th>
                                    <th>Remaining Amount</th>
                                    <th>Payment Status</th>
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

<!-- <div class="modal fade" id="modalChangeStatus" tabindex="-1" aria-labelledby="changeStatusModal" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" id="formChangeStatus">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title text-white" id="changeStatusModal">Ubah Status</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin Mengubah Status Order <strong id="OrderName"></strong>?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-md" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger btn-md">Change</button>
                </div>
            </div>
        </form>
    </div>
</div> -->

<div class="modal fade-scale" id="modalChangeStatus" tabindex="-1" aria-labelledby="modalChangeStatus" aria-hidden="true" data-bs-dismiss="ou">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <!--! BEGIN: [modal-header] !-->
            <div class="modal-header">
                <h2 class="d-flex flex-column mb-0">
                    <span class="fs-18 fw-bold mb-1">Mark As Paid</span>
                </h2>
                <a href="javascript:void(0)" class="avatar-text avatar-md bg-soft-danger close-icon" data-bs-dismiss="modal">
                    <i class="feather-x text-danger"></i>
                </a>
            </div>
            <!--! BEGIN: [modal-body] !-->
            <form method="POST" id="markAsSaleForm">
                @csrf
                <input type="hidden" id="order_id" name="order_id">

                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="transaction_type" class="fw-semibold">Order:</label>
                            <div class="input-group">
                                <select class="form-select form-control max-select" data-select2-selector="tag" id="transaction_type" name="transaction_type">
                                    <option value="11" data-bg="bg-danger">Order Account</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="transaction_date" class="fw-semibold">Transaction Date:</label>
                            <input type="date" id="transaction_date" name="transaction_date" class="form-control" value="{{ date('Y-m-d') }}">
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="cash_bank_account_id" class="fw-semibold">Cash/Bank Account:</label>
                            <div class="input-group">
                                @php
                                $bgColors = ['bg-danger', 'bg-warning', 'bg-primary', 'bg-indigo', 'bg-success'];
                                @endphp
                                <select class="form-select form-control max-select" data-select2-selector="tag" name="cash_bank_account_id" id="cash_bank_account_id">
                                    <option value="" disabled selected hidden>Pilih Bank atau Cash Account</option>
                                    @foreach ($cashAccounts as $cash)
                                    @php
                                    $bg = $bgColors[$loop->index % count($bgColors)];
                                    @endphp
                                    <option value="{{ $cash->id }}" data-bg="{{ $bg }}">Cash - {{ $cash->type }}</option>
                                    @endforeach
                                    @foreach ($bankAccounts as $bank)
                                    @php
                                    $bg = $bgColors[$loop->index % count($bgColors)];
                                    @endphp
                                    <option value="{{ $bank->id }}" data-bg="{{ $bg }}">Bank - {{ $bank->type }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="paid_amount" class="fw-semibold">Paid Amount:</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="paid_amount" name="paid_amount" value="0">
                            </div>
                            <span class="fw-semibold fs-12" id="paid_amount_display">Paid: Rp. 0</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <div>
                        <div class="col-md-6">
                            <p class="m-0">Balance:</p>
                            <h5 class="fw-semibold text-danger" id="total_amount_display">0</h5>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Mark As Paid</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endpush

@push('scripts')
<script>
    $(document).ready(function() {
        const dataTable = $('#saleListTable').DataTable({
            processing: true,
            serverSide: true,
            searching: false,
            lengthChange: false,
            ajax: {
                url: "{{ url('/erp/orders/sale-list/data') }}",
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
                    data: 'total_amount',
                    name: 'total_amount'
                },
                {
                    data: 'discount',
                    name: 'discount'
                },
                {
                    data: 'grand_total',
                    name: 'grand_total'
                },
                {
                    data: 'paid_amount',
                    name: 'paid_amount'
                },
                {
                    data: 'remaining_amount',
                    name: 'remaining_amount'
                },
                {
                    data: 'payment_status',
                    name: 'payment_status'
                },
                {
                    data: 'status',
                    name: 'status'
                },
                // {
                //     data: 'notes',
                //     name: 'notes'
                // },
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

    document.addEventListener('click', function(e) {
        if (e.target.closest('.btn-mark-paid')) {
            const button = e.target.closest('.btn-mark-paid');
            const orderId = button.getAttribute('data-id');
            const url = button.getAttribute('data-url');
            const paid = parseFloat(button.getAttribute('data-paid')) || 0;
            const totalAmount = parseFloat(button.getAttribute('data-total-amount')) || 0;
            const paidAmount = parseFloat(button.getAttribute('data-paid-amount')) || 0;
            const remainingAmount = totalAmount - paidAmount;

            document.getElementById('order_id').value = orderId;
            document.getElementById('markAsSaleForm').setAttribute('action', url);
            document.getElementById('total_amount_display').innerText = new Intl.NumberFormat('id-ID').format(remainingAmount);

            const paidDisplay = document.getElementById('paid_amount_display');
            if (paidDisplay) {
                paidDisplay.innerText = 'Paid: Rp. ' + paid.toLocaleString('id-ID', {
                    minimumFractionDigits: 2
                });
            }
        }
    });
</script>
@endpush