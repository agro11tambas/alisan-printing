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
                <li class="breadcrumb-item">History</li>
            </ul>
        </div>
        <!-- <div class="page-header-right ms-auto">
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
                            </div> -->
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
        <div class="row align-items-baseline">
            <div class="col-xxl-8 col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Products</h5>
                    </div>
                    <div class="card-body px-0">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>QTY</th>
                                        <th>Printed</th>
                                        <th>To be printed</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($progress->items as $item)
                                        <tr>
                                            <td>{{ $item->product->name ?? '-' }}</td>
                                            <td><span
                                                    class="fw-bold text-primary">{{ number_format($item->quantity) }}</span>
                                            </td>
                                            <td><span
                                                    class="fw-bold text-success">{{ number_format($item->completed_quantity) }}</span>
                                            </td>
                                            <td>
                                                <span class="fw-bold text-danger">
                                                    {{ number_format($item->quantity - $item->completed_quantity) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xxl-4 col-xl-6">
                <div class="card stretch">
                    <div class="card-header">
                        <h5 class="card-title">Order Information</h5>
                    </div>
                    <div class="card-body task-info">
                        <div class="task-info-list">
                            <div class="row align-items-center mb-3 task-list-row">
                                <div class="col-6">
                                    <i class="feather-star me-2"></i>
                                    <span class="fw-semibold">Customer Name:</span>
                                </div>
                                <div class="col-6 d-flex">
                                    <span
                                        class="border-bottom border-bottom-dashed border-gray-5">{{ $progress->order->customer->name }}</span>
                                </div>
                            </div>
                            <div class="row align-items-center mb-3 task-list-row">
                                <div class="col-6">
                                    <i class="feather-phone me-2"></i>
                                    <span class="fw-semibold">Whatsapp:</span>
                                </div>
                                <div class="col-6 d-flex">
                                    <span
                                        class="border-bottom border-bottom-dashed border-gray-5">{{ $progress->order->customer->phone }}</span>
                                </div>
                            </div>
                            <div class="row align-items-center mb-3 task-list-row">
                                <div class="col-6">
                                    <i class="feather-airplay me-2"></i>
                                    <span class="fw-semibold">Address:</span>
                                </div>
                                <div class="col-6 d-flex">
                                    <span
                                        class="border-bottom border-bottom-dashed border-gray-5">{{ $progress->order->shipping_address }}</span>
                                </div>
                            </div>
                            <div class="row align-items-center mb-3 task-list-row">
                                <div class="col-6">
                                    <i class="feather-airplay me-2"></i>
                                    <span class="fw-semibold">Google Map:</span>
                                </div>
                                <div class="col-6 d-flex">
                                    <span class="border-bottom border-bottom-dashed border-gray-5"><a
                                            href="{{ $progress->order->google_maps }}" target="_blank">Link Google
                                            Map</a></span>
                                </div>
                            </div>
                            <div class="row align-items-center mb-3 task-list-row">
                                <div class="col-6">
                                    <i class="feather-calendar me-2"></i>
                                    <span class="fw-semibold">Order Date:</span>
                                </div>
                                <div class="col-6 d-flex">
                                    <span
                                        class="border-bottom border-bottom-dashed border-gray-5">{{ date('d M Y', strtotime($progress->order->created_at)) }}</span>
                                </div>
                            </div>
                            <div class="row align-items-center mb-3 task-list-row">
                                <div class="col-6">
                                    <i class="feather-clock me-2"></i>
                                    <span class="fw-semibold">Status:</span>
                                </div>
                                <div class="col-6 d-flex">
                                    <span
                                        class="border-bottom border-bottom-dashed border-gray-5">{{ $progress->order->status }}</span>
                                </div>
                            </div>
                            <div class="row align-items-center mb-3 task-list-row">
                                <div class="col-6">
                                    <i class="feather-clipboard me-2"></i>
                                    <span class="fw-semibold">Payment Method:</span>
                                </div>
                                <div class="col-6 d-flex">
                                    <span
                                        class="border-bottom border-bottom-dashed border-gray-5">{{ $progress->order->payment_method }}</span>
                                </div>
                            </div>
                            <div class="row align-items-center task-list-row">
                                <div class="col-6">
                                    <i class="feather-clipboard me-2"></i>
                                    <span class="fw-semibold">Payment Status:</span>
                                </div>
                                <div class="col-6 d-flex">
                                    <span
                                        class="border-bottom border-bottom-dashed border-gray-5">{{ $progress->order->payment_status }}</span>
                                </div>
                            </div>
                            <!-- <div class="row align-items-center mb-3 task-list-row">
                                                    <div class="col-6">
                                                        <i class="feather-dollar-sign me-2"></i>
                                                        <span class="fw-semibold">Total Amount:</span>
                                                    </div>
                                                    <div class="col-6 d-flex">
                                                        <span class="border-bottom border-bottom-dashed border-gray-5">Rp. {{ number_format($progress->total_amount, 0, ',', '.') }}</span>
                                                    </div>
                                                </div> -->
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xxl-12 col-lg-6">
                <div class="card stretch stretch-full">
                    <div class="card-header">
                        <h5 class="card-title">History</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="p-4">
                            <div class="col-lg-4 me-2">
                                <label for="" class="fw-semibold fs-12">Date</label>
                                <div class="d-flex align-items-center gap-2 page-header-right-items-wrapper">
                                    <div class="col-auto">
                                        <select id="filter" class="form-control"
                                            style="padding: 0.5rem 1rem; font-size: 0.875rem; width: 200px !important;">
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
                                        <input type="date" id="start_date" class="form-control"
                                            style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                    </div>
                                    <div class="col-auto custom-range d-none">
                                        <input type="date" id="end_date" class="form-control"
                                            style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                    </div>
                                    <div class="col-auto custom-range d-none">
                                        <button id="apply-filter" class="btn btn-primary">Apply</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table id="progress-history-table" class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Invoice</th>
                                        <th>Change Date</th>
                                        <th>Updated By</th>
                                        <th>Histories</th>
                                        <th>Note</th>
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
    <div class="modal fade" id="editHistoryModal" tabindex="-1" aria-labelledby="editHistoryModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="editHistoryForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title" id="editHistoryModalLabel">Edit Product Progress</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <input type="hidden" id="history_id" name="id">
                        <div class="mb-3">
                            <label class="form-label">Product</label>
                            <input type="text" id="product_name" class="form-control" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Completed Quantity</label>
                            <input type="number" id="completed_quantity" name="completed_quantity"
                                class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Defect</label>
                            <input type="number" id="defect_quantity" name="defect_quantity" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Reject</label>
                            <input type="number" id="reject_quantity" name="reject_quantity" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Operator</label>
                            <input type="text" id="operator_name" name="operator_name" class="form-control" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea id="note" name="note" class="form-control" rows="3"></textarea>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {
            const dataTable = $('#progress-history-table').DataTable({
                processing: true,
                serverSide: true,
                searching: false,
                lengthChange: false,
                ajax: {
                    url: "{{ url('/erp/productions/waiting-list/history-order/' . $progress->id . '/data') }}",
                    data: function(d) {
                        d.filter = $('#filter').val();
                        d.start_date = $('#start_date').val();
                        d.end_date = $('#end_date').val();
                    },
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
                    {
                        data: 'date',
                        name: 'date'
                    },
                    {
                        data: 'user_name',
                        name: 'user_name'
                    },
                    {
                        data: 'products',
                        name: 'products'
                    },
                    {
                        data: 'notes',
                        name: 'notes'
                    },
                ]
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
        });

        $(document).on('click', '.btn-edit-history', function() {
            const id = $(this).data('id');
            $('#history_id').val(id);
            $('#product_name').val($(this).data('product'));
            $('#completed_quantity').val($(this).data('quantity'));
            $('#defect_quantity').val($(this).data('defect'));
            $('#reject_quantity').val($(this).data('reject'));
            $('#operator_name').val($(this).data('operator'));
            $('#notes').val($(this).data('note'));
            $('#editHistoryModal').modal('show');
        });

        $('#editHistoryForm').on('submit', function(e) {
            e.preventDefault();
            const id = $('#history_id').val();

            $.ajax({
                url: `/erp/productions/waiting-list/history-order/update-history/${id}`,
                method: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Updated!',
                        text: 'Product progress has been updated.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    $('#editHistoryModal').modal('hide');
                    $('#progress-history-table').DataTable().ajax.reload(null, false);
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: xhr.responseJSON?.message ?? 'Failed to update data'
                    });
                }
            });
        });
    </script>
@endpush
