@extends('erp.layouts.main')

@push('styles')
    <style>
        @media (max-width: 768px) {

            #rejectDetailTable td.desktop-only,
            #rejectDetailTable th.desktop-only {
                display: none !important;
            }
        }

        #rejectDetailTable {
            width: 100% !important;
            min-width: 0;
        }

        #rejectDetailTable_wrapper .dataTables_scrollBody {
            background-image: none !important;
        }
    </style>
@endpush

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Reject Product Detail</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Adjustment</li>
                <li class="breadcrumb-item"><a href="/erp/adjustment-products/reject-products">Reject Products</a></li>
                <li class="breadcrumb-item">Detail</li>
            </ul>
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

    <div class="main-content m-0 m-md-2 m-lg-2 p-0 p-md-0 p-lg-0 pt-1 pt-md-0">
        <div class="row">
            <div class="col-lg-12">
                <div class="card stretch stretch-full">
                    <div class="card-body p-0">
                        <div class="row g-0 p-2 justify-content-between">
                            <div class="col-lg-6">
                                <h5 class="fw-bold mb-2">Product: {{ $product->name }}</h5>
                            </div>
                            <div class="col-lg-6">
                                <div class="row justify-content-end">
                                    <div class="col-lg-3">
                                        <label for="progress_status" class="fw-semibold fs-12">Progress Status</label>
                                        <select id="progress_status" class="form-control"
                                            style="padding: 0.25rem 0.5rem; font-size: 0.875rem;">
                                            <option value="pending">Pending</option>
                                            <option value="completed">Completed</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover bg-transparent" id="rejectDetailTable">
                                <thead>
                                    <tr>
                                        <th class="wd-30">No</th>
                                        <th>Date</th>
                                        <th>Order Progress</th>
                                        <th>Quantity</th>
                                        <th>Status</th>
                                        <th>Note</th>
                                        <th>Created By</th>
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

@push('modals')
    <div class="modal fade-scale" id="modalProcessReject" tabindex="-1" aria-labelledby="modalProcessReject"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="d-flex flex-column mb-0">
                        <span class="fs-18 fw-bold mb-1">Return to Warehouse</span>
                    </h2>
                    <a href="javascript:void(0)" class="avatar-text avatar-md bg-soft-danger close-icon"
                        data-bs-dismiss="modal">
                        <i class="feather-x text-danger"></i>
                    </a>
                </div>
                <form method="POST" id="rejectReturnForm">
                    @csrf
                    <input type="hidden" id="reject_product_id" name="reject_product_id">

                    <div class="modal-body">
                        <div class="row g-3 mb-2">
                            <div class="col-md-6">
                                <label for="date" class="fw-semibold">Date:</label>
                                <div class="input-group">
                                    <input type="date" id="date" name="date" class="form-control"
                                        value="{{ date('Y-m-d') }}" readonly>
                                </div>
                                <small class="text-danger d-none" id="error_date"></small>
                            </div>

                            <div class="col-md-6">
                                <label for="quantity" class="fw-semibold">Return Quantity:</label>
                                <div class="input-group">
                                    <input type="number" id="quantity" name="quantity" class="form-control" value="0"
                                        min="1">
                                </div>
                                <small class="text-danger d-none" id="error_quantity"></small>
                            </div>
                        </div>
                        <input type="hidden" id="inventory_warehouse_id" name="inventory_warehouse_id" value="1">

                        {{-- <div class="row g-3 mb-2">
                            <div class="col-md-12">
                                <label for="inventory_warehouse_id" class="fw-semibold">Warehouse:</label>
                                <select id="inventory_warehouse_id" name="inventory_warehouse_id" class="form-select">
                                    <option value="">-- Select Warehouse --</option>
                                    @foreach (\App\Models\InventoryWarehouse::all() as $w)
                                        <option value="{{ $w->id }}">{{ $w->name }}</option>
                                    @endforeach
                                </select>
                                <small class="text-danger d-none" id="error_inventory_warehouse_id"></small>
                            </div>
                        </div> --}}
                    </div>
                    <div class="modal-footer d-flex justify-content-between">
                        <div>
                            <div class="col-md-6">
                                <p class="m-0">Total:</p>
                                <h5 class="fw-semibold text-danger" id="total_quantity">0</h5>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Return to Warehouse</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {
            let dataTable = $('#rejectDetailTable').DataTable({
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
                    url: "{{ url('/erp/adjustment-products/reject-products/detail-reject-products/data/' . $product->id) }}",
                    data: function(d) {
                        d.status = $('#progress_status').val();
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'reject_date',
                        name: 'reject_date'
                    },
                    {
                        data: 'order_progress',
                        name: 'order_progress'
                    },
                    {
                        data: 'quantity',
                        name: 'quantity'
                    },
                    {
                        data: 'status',
                        name: 'status'
                    },
                    {
                        data: 'note',
                        name: 'note'
                    },
                    {
                        data: 'user',
                        name: 'user'
                    },
                ]
            });

            $('#progress_status').on('change', function() {
                dataTable.ajax.reload();
            });

            $('#rejectDetailTable tbody').on('click', 'tr', function(e) {
                if ($(e.target).closest('.dropdown-item, .btn, button').length) return;
                if ($(e.target).closest('td.dt-control').length) return;

                let $tr = $(this);
                let row = dataTable.row($tr);

                $('#rejectDetailTable tbody tr').removeClass('action-shown').next('.action-row').remove();

                if ($tr.hasClass('action-shown')) {
                    $tr.removeClass('action-shown');
                } else {
                    let actionHtml = row.data().action || '';
                    let colCount = $tr.find('td').length;
                    let $actionRow = $(`
                        <tr class="action-row">
                            <td colspan="${colCount}">
                                <div class="d-flex justify-content-center">${actionHtml}</div>
                            </td>
                        </tr>
                    `);
                    $tr.after($actionRow);
                    $tr.addClass('action-shown');
                }
            });


            $(document).on('click', function(e) {
                if ($(e.target).closest('#rejectDetailTable').length) return;
                $('#rejectDetailTable tbody tr').removeClass('action-shown').next('.action-row').remove();
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('modalProcessReject');
            const form = document.getElementById('rejectReturnForm');
            const inputId = document.getElementById('reject_product_id');
            const inputQty = document.getElementById('quantity');
            const totalHolder = document.getElementById('total_quantity');

            modal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                if (!button) return;

                const id = button.getAttribute('data-id');
                const url = button.getAttribute('data-url');
                const total = button.getAttribute('data-total');

                form.action = url;
                inputId.value = id;
                inputQty.value = 0;
                inputQty.setAttribute('max', total);

                totalHolder.textContent = total;
            });

            inputQty.addEventListener('input', function() {
                const max = parseInt(this.getAttribute('max')) || 0;
                const val = parseInt(this.value) || 0;
                if (val > max) this.value = max;
            });
        });
    </script>
@endpush
