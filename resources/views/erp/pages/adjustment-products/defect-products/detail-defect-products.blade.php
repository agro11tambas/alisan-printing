@extends('erp.layouts.main')

@push('styles')
    <style>
        @media (max-width: 768px) {

            #defectDetailTable td.desktop-only,
            #defectDetailTable th.desktop-only {
                display: none !important;
            }
        }

        #defectDetailTable {
            width: 100% !important;
            min-width: 0;
        }

        #defectDetailTable_wrapper .dataTables_scrollBody {
            background-image: none !important;
        }
    </style>
@endpush

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Defect Product Detail</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Adjustment</li>
                <li class="breadcrumb-item"><a href="/erp/adjustment-products/defect-products">Defect Products</a></li>
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
                    <a href="/erp/adjustment-products/defect-products" class="btn btn-light-brand">
                        <i class="feather-arrow-left me-2"></i>
                        <span>Back</span>
                    </a>
                </div>
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
                            <table class="table table-hover bg-transparent" id="defectDetailTable">
                                <thead>
                                    <tr>
                                        <th class="wd-30">No</th>
                                        <th>Date</th>
                                        <th>Supplier</th>
                                        <th>Quantity</th>
                                        <th>Type</th>
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
    <div class="modal fade-scale" id="modalChangeStatus" tabindex="-1" aria-labelledby="modalChangeStatus"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="d-flex flex-column mb-0">
                        <span class="fs-18 fw-bold mb-1" id="modalTitle">Process Defect Product</span>
                    </h2>
                    <a href="javascript:void(0)" class="avatar-text avatar-md bg-soft-danger close-icon"
                        data-bs-dismiss="modal">
                        <i class="feather-x text-danger"></i>
                    </a>
                </div>

                <form method="POST" id="defectProcessForm">
                    @csrf
                    <input type="hidden" id="defect_id" name="defect_id">
                    <input type="hidden" id="action_type" name="action_type">

                    <div class="modal-body">

                        <div class="mb-2" id="supplierField" style="display: none;">
                            <label for="supplier_name" class="fw-semibold">Supplier:</label>
                            <input type="text" id="supplier_name" name="supplier_name" class="form-control" readonly>
                        </div>

                        <div class="row g-3 mb-2">
                            <div class="col-md-6">
                                <label for="date" class="fw-semibold">Date:</label>
                                <input type="date" id="date" name="date" class="form-control"
                                    value="{{ date('Y-m-d') }}" readonly>
                            </div>

                            <div class="col-md-6">
                                <label for="process_quantity" class="fw-semibold">Quantity:</label>
                                <input type="number" id="process_quantity" name="quantity" class="form-control"
                                    value="0" min="1">
                            </div>
                        </div>

                        <p class="text-muted">
                            Available Quantity:
                            <strong id="total_defect" class="text-danger">0</strong>
                        </p>
                    </div>

                    <div class="modal-footer d-flex justify-content-between">
                        <div>
                            <p class="m-0">Total:</p>
                            <h5 class="fw-semibold text-danger" id="total_display">0</h5>
                        </div>
                        <button type="submit" class="btn btn-primary" id="btnSubmitAction">Confirm</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {
            let dataTable = $('#defectDetailTable').DataTable({
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
                    url: "{{ url('/erp/adjustment-products/defect-products/detail-defect-products/data/' . $product->id) }}",
                    data: function(d) {
                        d.status = $('#progress_status').val();
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'defect_date',
                        name: 'defect_date'
                    },
                    {
                        data: 'supplier',
                        name: 'supplier'
                    },
                    {
                        data: 'quantity',
                        name: 'quantity'
                    },
                    {
                        data: 'defect_type',
                        name: 'defect_type'
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

            $('#defectDetailTable tbody').on('click', 'tr', function(e) {
                if ($(e.target).closest('td.dt-control').length) return;
                let $tr = $(this);
                let row = dataTable.row($tr);

                $('#defectDetailTable tbody tr').removeClass('action-shown').next('.action-row').remove();

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
                if ($(e.target).closest('#defectDetailTable').length) return;
                $('#defectDetailTable tbody tr').removeClass('action-shown').next('.action-row').remove();
            });

            const modal = document.getElementById('modalChangeStatus');
            const form = document.getElementById('defectProcessForm');
            const inputId = document.getElementById('defect_id');
            const inputQty = document.getElementById('process_quantity');
            const totalHolder = document.getElementById('total_defect');
            const totalDisplay = document.getElementById('total_display');
            const modalTitle = document.getElementById('modalTitle');
            const btnSubmit = document.getElementById('btnSubmitAction');
            const supplierField = document.getElementById('supplierField');
            const supplierName = document.getElementById('supplier_name');

            modal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-id');
                const url = button.getAttribute('data-url');
                const total = button.getAttribute('data-total');
                const type = button.getAttribute('data-action-type');
                const supplier = button.getAttribute('data-supplier') || '-';

                form.action = url;
                inputId.value = id;
                inputQty.value = 0;
                totalHolder.textContent = total;
                totalDisplay.textContent = total;
                inputQty.setAttribute('max', total);

                if (type === 'return') {
                    modalTitle.textContent = 'Return Defect to Supplier';
                    btnSubmit.textContent = 'Return';
                    supplierField.style.display = 'block';
                    supplierName.value = supplier;
                } else {
                    modalTitle.textContent = 'Eliminate Defect Product';
                    btnSubmit.textContent = 'Eliminate';
                    supplierField.style.display = 'none';
                }
            });

            inputQty.addEventListener('input', function() {
                let max = parseInt(this.getAttribute('max')) || 0;
                let val = parseInt(this.value) || 0;
                if (val > max) this.value = max;
            });
        });
    </script>
@endpush
