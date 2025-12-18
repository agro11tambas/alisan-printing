@extends('erp.layouts.main')

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Delivery Order</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Deliveries</li>
                <li class="breadcrumb-item active">Progress & Info</li>
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
                text: "{{ session('success') }}"
            });
        </script>
    @endif
    @if (session('error'))
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: "{{ session('error') }}"
            });
        </script>
    @endif

    <div class="main-content m-0 m-md-2 m-lg-2 p-0 p-md-0 p-lg-0 pt-2 pt-md-0">
        <div class="row align-items-baseline">
            <div class="col-xxl-8 col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Products in Delivery</h5>
                    </div>
                    <div class="card-body px-0">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Ready Qty</th>
                                        <th>Shipped Qty</th>
                                        <th>Remaining</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($delivery->items as $item)
                                        <tr>
                                            <td>{{ $item->product->name ?? '-' }}</td>
                                            <td><span
                                                    class="fw-bold text-primary">{{ number_format($item->ready_qty ?? 0) }}</span>
                                            </td>
                                            <td><span
                                                    class="fw-bold text-success">{{ number_format($item->shipped_qty ?? 0) }}</span>
                                            </td>
                                            <td>
                                                <span class="fw-bold text-danger">
                                                    {{ number_format(($item->ready_qty ?? 0) - ($item->shipped_qty ?? 0)) }}
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
                        <h5 class="card-title">Delivery Information</h5>
                    </div>
                    <div class="card-body task-info">
                        <div class="task-info-list">
                            <div class="row align-items-center mb-3">
                                <div class="col-6"><i class="feather-user me-2"></i> <span
                                        class="fw-semibold">Customer:</span></div>
                                <div class="col-6"><span
                                        class="border-bottom border-bottom-dashed">{{ $delivery->customer }}</span></div>
                            </div>
                            <div class="row align-items-center mb-3">
                                <div class="col-6"><i class="feather-map-pin me-2"></i> <span
                                        class="fw-semibold">Address:</span></div>
                                <div class="col-6"><span
                                        class="border-bottom border-bottom-dashed">{{ $delivery->shipping_address }}</span>
                                </div>
                            </div>
                            <div class="row align-items-center mb-3">
                                <div class="col-6"><i class="feather-link me-2"></i> <span class="fw-semibold">Google
                                        Map:</span></div>
                                <div class="col-6"><a href="{{ $delivery->google_map_link }}" target="_blank">Open Link</a>
                                </div>
                            </div>
                            <div class="row align-items-center mb-3">
                                <div class="col-6"><i class="feather-calendar me-2"></i> <span class="fw-semibold">Delivery
                                        Date:</span></div>
                                <div class="col-6"><span
                                        class="border-bottom border-bottom-dashed">{{ date('d M Y', strtotime($delivery->delivery_date)) }}</span>
                                </div>
                            </div>
                            <div class="row align-items-center mb-3">
                                <div class="col-6"><i class="feather-truck me-2"></i> <span
                                        class="fw-semibold">Status:</span></div>
                                <div class="col-6"><span
                                        class="border-bottom border-bottom-dashed">{{ $delivery->status }}</span></div>
                            </div>
                            <div class="row align-items-center mb-3">
                                <div class="col-6"><i class="feather-user-check me-2"></i> <span
                                        class="fw-semibold">Created By:</span></div>
                                <div class="col-6"><span
                                        class="border-bottom border-bottom-dashed">{{ $delivery->user->name ?? '-' }}</span>
                                </div>
                            </div>
                            @if ($delivery->note)
                                <div class="row align-items-center mb-3">
                                    <div class="col-6"><i class="feather-file-text me-2"></i> <span
                                            class="fw-semibold">Note:</span></div>
                                    <div class="col-6"><span
                                            class="border-bottom border-bottom-dashed">{{ $delivery->note }}</span></div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xxl-12 col-lg-6">
                <div class="card stretch stretch-full">
                    <div class="card-header">
                        <h5 class="card-title">Delivery History</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="p-4">
                            <div class="col-lg-4 me-2">
                                <label for="" class="fw-semibold fs-12">Date Filter</label>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="col-auto">
                                        <select id="filter" class="form-control" style="width: 200px !important;">
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
                                        <input type="date" id="start_date" class="form-control">
                                    </div>
                                    <div class="col-auto custom-range d-none">
                                        <input type="date" id="end_date" class="form-control">
                                    </div>
                                    <div class="col-auto custom-range d-none">
                                        <button id="apply-filter" class="btn btn-primary">Apply</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table id="delivery-history-table" class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Shipment No</th>
                                        <th>Shipment Date</th>
                                        <th>Driver</th>
                                        <th>Status</th>
                                        <th>Products</th>
                                        <th>Note</th>
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
    <div class="modal fade" id="editHistoryModal" tabindex="-1" aria-labelledby="editHistoryModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="editHistoryForm" method="POST">
                    @csrf
                    @method('POST')
                    <div class="modal-header">
                        <h5 class="modal-title" id="editHistoryModalLabel">Edit Shipment Item</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <input type="hidden" id="history_id" name="id">

                        <div class="mb-3">
                            <label class="form-label">Product</label>
                            <input type="text" id="product_name" class="form-control" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Shipped Quantity</label>
                            <input type="number" id="shipped_quantity" name="shipped_quantity" class="form-control"
                                min="0">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Note</label>
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
            const dataTable = $('#delivery-history-table').DataTable({
                processing: true,
                serverSide: true,
                searching: false,
                lengthChange: false,
                ajax: {
                    url: "{{ url('/erp/deliveries/delivery-orders/history-delivery-order/' . $delivery->id . '/data') }}",
                    data: function(d) {
                        d.filter = $('#filter').val();
                        d.start_date = $('#start_date').val();
                        d.end_date = $('#end_date').val();
                    },
                },
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'shipment_number',
                        name: 'shipment_number'
                    },
                    {
                        data: 'shipment_date',
                        name: 'shipment_date'
                    },
                    {
                        data: 'driver_name',
                        name: 'driver_name'
                    },
                    {
                        data: 'status',
                        name: 'status'
                    },
                    {
                        data: 'products',
                        name: 'products'
                    },
                    {
                        data: 'note',
                        name: 'note'
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

            $(document).on('click', '.btn-edit-history', function() {
                const id = $(this).data('id');
                $('#history_id').val(id);
                $('#product_name').val($(this).data('product'));
                $('#shipped_quantity').val($(this).data('quantity'));
                $('#note').val($(this).data('note'));
                $('#editHistoryModal').modal('show');
            });

            $('#editHistoryForm').on('submit', function(e) {
                e.preventDefault();
                const id = $('#history_id').val();

                $.ajax({
                    url: `/erp/deliveries/delivery-orders/update-history/${id}`,
                    method: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Updated!',
                            text: response.message || 'Shipment item has been updated.',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        $('#editHistoryModal').modal('hide');
                        $('#delivery-history-table').DataTable().ajax.reload(null, false);
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: xhr.responseJSON?.message ?? 'Failed to update data.'
                        });
                    }
                });
            });
        });
    </script>
@endpush
