@extends('erp.layouts.main')

@push('styles')
    <style>
        @media (max-width: 768px) {

            #deliveryListTable td.desktop-only,
            #deliveryListTable th.desktop-only {
                display: none !important;
            }
        }

        #deliveryListTable {
            width: 100% !important;
            min-width: 0;
        }

        #deliveryListTable_wrapper .dataTables_scrollBody {
            /* background: transparent !important; */
            background-image: none !important;
        }
    </style>
@endpush

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Delivery Order</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Deliveries</li>
                <li class="breadcrumb-item active">Delivery Order</li>
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

    <div class="main-content">
        <div class="row">
            <div class="col-lg-12">
                <div class="card stretch stretch-full">
                    <div class="card-body p-0">
                        {{-- Filter --}}
                        <div class="row g-3 p-4 justify-content-between">
                            <div class="col-lg-4 me-2">
                                <label class="fw-semibold fs-12">Date</label>
                                <div class="d-flex align-items-center gap-2 page-header-right-items-wrapper">
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

                            <div class="col-lg-6">
                                <div class="row justify-content-end">
                                    <div class="col-lg-3">
                                        <label for="status" class="fw-semibold fs-12">Status</label>
                                        <select id="status" class="form-control">
                                            <option value="">All</option>
                                            <option value="Ongoing">Ongoing</option>
                                            <option value="Completed">Completed</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-6">
                                        <label for="search_type" class="fw-semibold fs-12">Filter By</label>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <select id="search_type" class="form-control">
                                                    <option value="shipment_number">Shipment Number</option>
                                                    <option value="driver">Driver</option>
                                                    <option value="vehicle">Vehicle</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <input type="text" id="search_keyword" class="form-control"
                                                    placeholder="Search..." />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Table --}}
                        <div class="table-responsive">
                            <table class="table table-hover bg-transparent" id="deliveryListTable">
                                <thead>
                                    <tr>
                                        <!-- <th>No</th> -->
                                        <th>Shipment Number</th>
                                        <th>Customer</th>
                                        <th>Address</th>
                                        <th>Driver</th>
                                        <th>Status</th>
                                        <th>Items</th>
                                        <th>Waybill Proof</th>
                                        <th>Delivery Proof</th>
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

@push('styles')
    {{-- Modal Upload Delivery Proof --}}
    <div class="modal fade" id="modalUploadDelivery" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" enctype="multipart/form-data" id="formUploadDelivery">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Upload Bukti Pengantaran</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="file" name="proof_delivery" class="form-control" required>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-md" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-md">Upload</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Upload Waybill Proof --}}
    <div class="modal fade" id="modalUploadWaybill" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" enctype="multipart/form-data" id="formUploadWaybill">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Upload Bukti Waybill / Surat Jalan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="file" name="proof_waybill" class="form-control" required>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-md" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-md">Upload</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="modalChangeStatus" tabindex="-1" aria-labelledby="changeStatusModal"
        aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" id="formChangeStatus">
                @csrf
                @method('PUT')
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title text-white" id="changeStatusModal">Verifikasi</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Apakah Anda yakin ingin memverifikasi Delivery List <strong id="DeliveryListName"></strong>?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-md" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-md">Verifikasi</button>
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
                deferRender: true,
                scrollY: 600,
                scroller: true,
                paging: true,
                searching: false,
                lengthChange: false,
                info: false,
                pagingType: "simple",
                ajax: {
                    url: "{{ url('/erp/deliveries/delivery-list/data') }}",
                    data: function(d) {
                        d.filter = $('#filter').val();
                        d.start_date = $('#start_date').val();
                        d.end_date = $('#end_date').val();
                        d.search_type = $('#search_type').val();
                        d.search_keyword = $('#search_keyword').val();
                        d.status = $('#status').val();
                    },
                    error: function(xhr) {
                        console.error('Error response:', xhr.responseJSON);
                        alert(xhr.responseJSON.message);
                    }
                },
                columns: [
                    // {
                    //     data: 'DT_RowIndex',
                    //     orderable: false,
                    //     searchable: false
                    // },
                    {
                        data: 'shipment_number',
                        name: 'shipment_number'
                    },
                    {
                        data: 'customer',
                        name: 'customer'
                    },
                    {
                        data: 'address',
                        name: 'address'
                    },
                    {
                        data: 'driver',
                        name: 'driver'
                    },
                    {
                        data: 'status',
                        name: 'status'
                    },
                    {
                        data: 'items',
                        name: 'items'
                    },
                    {
                        data: 'waybill_proof',
                        name: 'waybill_proof'
                    },
                    {
                        data: 'delivery_proof',
                        name: 'delivery_proof'
                    },
                ],
            });

            $('#status').on('change', function() {
                dataTable.ajax.reload();
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
            $('#search_type').on('change', function() {
                dataTable.ajax.reload();
            });
            $('#search_keyword').on('keyup', function() {
                dataTable.ajax.reload();
            });

            $('#deliveryListTable tbody').on('click', 'tr', function(e) {
                if ($(e.target).closest('td.dt-control').length) return;

                let $tr = $(this);
                let row = dataTable.row($tr);

                $('#deliveryListTable tbody tr').removeClass('action-shown').next('.action-row').remove();

                if ($tr.hasClass('action-shown')) {
                    $tr.removeClass('action-shown');
                } else {
                    let actionHtml = row.data().action;
                    let colCount = $tr.find('td').length;
                    let $actionRow = $(`
            <tr class="action-row">
                <td colspan="${colCount}">
                    <div class="d-flex justify-content-start">
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
                if ($(e.target).closest('#deliveryListTable').length) return;
                $('#deliveryListTable tbody tr').removeClass('action-shown').next('.action-row').remove();
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            // Modal Upload Delivery Proof
            $(document).on('click', '.btn-upload-delivery', function() {
                const url = $(this).data('url');
                $('#formUploadDelivery').attr('action', url);
                $('#modalUploadDelivery').modal('show');
            });

            // Modal Upload Waybill Proof
            $(document).on('click', '.btn-upload-waybill', function() {
                const url = $(this).data('url');
                $('#formUploadWaybill').attr('action', url);
                $('#modalUploadWaybill').modal('show');
            });
        });

        $(document).on('click', '.btn-verify', function() {
            let id = $(this).data('id');
            let name = $(this).data('name');
            let url = $(this).data('url');

            $('#DeliveryListName').text(name);
            $('#formChangeStatus').attr('action', url);
        });
    </script>
@endpush
