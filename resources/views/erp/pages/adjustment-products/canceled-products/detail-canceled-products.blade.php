@extends('erp.layouts.main')

@push('styles')
    <style>
        @media (max-width: 768px) {

            #canceledDetailTable td.desktop-only,
            #canceledDetailTable th.desktop-only {
                display: none !important;
            }
        }

        #canceledDetailTable {
            width: 100% !important;
            min-width: 0;
        }

        #canceledDetailTable_wrapper .dataTables_scrollBody {
            /* background: #fff !important; */
            background-image: none !important;
        }
    </style>
@endpush

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Canceled Product Detail</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Production</li>
                <li class="breadcrumb-item"><a href="/erp/productions/canceled-products">Canceled Products</a></li>
                <li class="breadcrumb-item">Detail</li>
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
                    <a href="/erp/productions/canceled-products" class="btn btn-light-brand">
                        <i class="feather-arrow-left me-2"></i>
                        <span>Back</span>
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
                        <div class="row g-0 p-4 justify-content-between">
                            <div class="col-lg-6">
                                <h5 class="fw-bold mb-3">Product: {{ $product->name }}</h5>
                            </div>
                            <div class="col-lg-6">
                                <div class="row justify-content-end">
                                    <div class="col-lg-3">
                                        <label for="progress_status" class="fw-semibold fs-12">Progress Status</label>
                                        <select id="progress_status" class="form-control"
                                            style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                            <option value="pending">Pending</option>
                                            <option value="completed">Completed</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover bg-transparent" id="canceledDetailTable">
                                <thead>
                                    <tr>
                                        <th class="wd-30">No</th>
                                        <th>Date</th>
                                        <th>Quantity</th>
                                        <th>Type</th>
                                        <th>Note</th>
                                        <th>Status</th>
                                        <th>Created By</th>
                                        <!-- <th>Action</th> -->
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
    <div class="modal fade-scale" id="modalChangeStatus" tabindex="-1" aria-labelledby="modalChangeStatus"
        aria-hidden="true" data-bs-dismiss="ou">
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
                <form method="POST" id="markAsSaleForm">
                    @csrf
                    <input type="hidden" id="canceled_product_id" name="canceled_product_id">

                    <div class="modal-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="date" class="fw-semibold">Date:</label>
                                <div class="input-group">
                                    <input type="date" id="date" name="date" class="form-control"
                                        value="{{ date('Y-m-d') }}" readonly>
                                </div>
                                <small class="text-danger d-none" id="error_date"></small>
                            </div>
                            <div class="col-md-6">
                                <label for="canceled_product" class="fw-semibold">Return to Warehouse:</label>
                                <div class="input-group">
                                    <input type="text" id="canceled_product" name="canceled_product" class="form-control"
                                        value="0">
                                </div>
                                <small class="text-danger d-none" id="error_canceled_product"></small>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer d-flex justify-content-between">
                        <div>
                            <div class="col-md-6">
                                <p class="m-0">Total:</p>
                                <h5 class="fw-semibold text-danger" id="total_canceled_product">0</h5>
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
            let dataTable = $('#canceledDetailTable').DataTable({
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
                    url: "{{ url('/erp/adjustment-products/canceled-products/detail/' . $product->id . '/data') }}",
                    data: function(d) {
                        d.status = $('#progress_status').val(); // 🔥 kirim filter status ke controller
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'date',
                        name: 'date'
                    },
                    {
                        data: 'quantity',
                        name: 'quantity'
                    },
                    {
                        data: 'type',
                        name: 'type'
                    },
                    {
                        data: 'note',
                        name: 'note'
                    },
                    {
                        data: 'status',
                        name: 'status'
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

            $('#canceledDetailTable tbody').on('click', 'tr', function(e) {
                if ($(e.target).closest('td.dt-control').length) return;

                let $tr = $(this);
                let row = dataTable.row($tr);

                $('#canceledDetailTable tbody tr').removeClass('action-shown').next('.action-row').remove();

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
                if ($(e.target).closest('#canceledDetailTable').length) return;
                $('#canceledDetailTable tbody tr').removeClass('action-shown').next('.action-row').remove();
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('modalChangeStatus');
            const form = document.getElementById('markAsSaleForm');
            const inputId = document.getElementById('canceled_product_id');
            const inputQty = document.getElementById('canceled_product');
            const totalHolder = document.getElementById('total_canceled_product');

            modal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-id');
                const url = button.getAttribute('data-url');
                const total = button.getAttribute('data-total');

                form.action = url;
                inputId.value = id;
                inputQty.value = 0;

                totalHolder.textContent = total;
                inputQty.setAttribute('max', total);
            });

            inputQty.addEventListener('input', function() {
                let max = parseInt(this.getAttribute('max')) || 0;
                let val = parseInt(this.value) || 0;

                if (val > max) {
                    this.value = max;
                }
            });
        });
    </script>
@endpush
