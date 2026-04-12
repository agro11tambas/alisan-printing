@extends('erp.layouts.main')

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Stock In History</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Warehouse</li>
                <li class="breadcrumb-item">Stock In History</li>
            </ul>
        </div>
    </div>
@endsection

@section('content')
    @if (session('error'))
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: "{{ session('error') }}",
            });
        </script>
    @endif
    <div class="main-content m-0 m-md-2 m-lg-2 p-0 p-md-0 p-lg-0 pt-2 pt-md-0">
        <div class="row align-items-baseline">
            <div class="col-xxl-8 col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5>{{ $supplier->name }}</h5>
                        <br>
                        <h5 class="card-title">Products</h5>
                    </div>
                    <div class="card-body px-0">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>QTY</th>
                                        <th>Stock In</th>
                                        <th>Remaining Stock In</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {{-- @foreach ($stockIn->items as $item)
                                        <tr>
                                            <td>{{ $item->product->name ?? '-' }}</td>
                                            <td><span
                                                    class="fw-bold text-primary">{{ number_format($item->quantity, 0, ',', '.') }}</span>
                                            </td>
                                            <td><span
                                                    class="fw-bold text-success">{{ number_format($item->stock_in, 0, ',', '.') }}</span>
                                            </td>
                                            <td>
                                                <span class="fw-bold text-danger">
                                                    {{ number_format($item->quantity - $item->stock_in, 0, ',', '.') }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach --}}
                                    @foreach ($mergedItems as $item)
                                        <tr>
                                            <td>{{ $item->product->name ?? '-' }}</td>
                                            <td>{{ number_format($item->quantity, 0, ',', '.') }}</td>
                                            <td>{{ number_format($item->stock_in, 0, ',', '.') }}</td>
                                            <td>{{ number_format($item->quantity - $item->stock_in, 0, ',', '.') }}</td>
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
                            {{-- <div class="row align-items-center mb-3 task-list-row">
                                <div class="col-6">
                                    <i class="feather-star me-2"></i>
                                    <span class="fw-semibold">
                                        @if ($stockIn->note === 'Purchase Account')
                                            Supplier Name:
                                        @elseif($stockIn->note === 'Sale Returns')
                                            Customer Name:
                                        @else
                                            -
                                        @endif
                                    </span>
                                </div>
                                <div class="col-6 d-flex">
                                    <span class="border-bottom border-bottom-dashed border-gray-5">
                                        @if ($stockIn->note === 'Purchase Account')
                                            {{ $stockIn->purchase->supplier->name ?? '-' }}
                                        @elseif($stockIn->note === 'Sale Returns')
                                            {{ $stockIn->saleReturn->customer->name ?? '-' }}
                                        @else
                                            -
                                        @endif
                                    </span>
                                </div>
                            </div>
                            <div class="row align-items-center mb-3 task-list-row">
                                <div class="col-6">
                                    <i class="feather-calendar me-2"></i>
                                    <span class="fw-semibold">Date:</span>
                                </div>
                                <div class="col-6 d-flex">
                                    <span
                                        class="border-bottom border-bottom-dashed border-gray-5">{{ date('d M Y', strtotime($stockIn->date)) }}</span>
                                </div>
                            </div> --}}
                            <div class="row align-items-center mb-3 task-list-row">
                                <div class="col-6">
                                    <i class="feather-star me-2"></i>
                                    <span class="fw-semibold">Supplier Name:</span>
                                </div>
                                <div class="col-6 d-flex">
                                    <span class="border-bottom border-bottom-dashed border-gray-5">
                                        {{ $supplier->name ?? '-' }}
                                    </span>
                                </div>
                            </div>
                            {{-- <div class="row align-items-center mb-3 task-list-row">
                                <div class="col-6">
                                    <i class="feather-calendar me-2"></i>
                                    <span class="fw-semibold">Period:</span>
                                </div>
                                <div class="col-6 d-flex">
                                    <span class="border-bottom border-bottom-dashed border-gray-5">
                                        {{ $monthLabel }}
                                    </span>
                                </div>
                            </div> --}}
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
                        <div class="p-4 row g-3 justify-content-between">
                            <div class="col-lg-4 me-2">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="start_date" class="fw-semibold fs-12">Start Date</label>
                                        <input type="date" id="start_date" name="start_date" class="form-control"
                                            style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="end_date" class="fw-semibold fs-12">Due Date</label>
                                        <input type="date" id="end_date" name="end_date" class="form-control"
                                            style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4 ms-auto justify-content-end">
                                <div class="col-md-6 ms-auto">
                                    <label for="filter_verified" class="fw-semibold fs-12">Status</label>
                                    <select id="filter_verified" class="form-select"
                                        style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                        <option value="unverified">Unpaid</option>
                                        <option value="verified">Paid</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table id="stockInHistoryTable" class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Invoice</th>
                                        <th>Stock In Date</th>
                                        <th>Updated By</th>
                                        <th>Waybill Number</th>
                                        <th>Waybill Image</th>
                                        <th>Histories</th>
                                        <th>Mark As Paid</th>
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
    <div class="modal fade" id="editHistoryModal" tabindex="-1" aria-labelledby="editHistoryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="editHistoryForm" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="editHistoryModalLabel">Edit Stock In History</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <input type="hidden" id="edit_id" name="id">

                        <div class="mb-3">
                            <label class="form-label">Product</label>
                            <input type="text" id="product_name" class="form-control" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Quantity (pcs)</label>
                            <input type="number" id="edit_quantity" name="quantity" class="form-control" min="0"
                                required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea id="edit_notes" name="notes" class="form-control" rows="3"></textarea>
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

    <div class="modal fade" id="verifyModal" tabindex="-1" aria-labelledby="verifyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="verifyModalLabel">Mark As Paid</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <i class="ri-checkbox-circle-line fs-1 text-primary mb-2"></i>
                    <p class="mb-0">Yakin ingin menandai stock in ini sebagai dibayar?</p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tidak</button>
                    <button type="button" class="btn btn-primary" id="btnConfirmVerify">Ya, Mark As Paid</button>
                </div>
            </div>
        </div>
    </div>
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {
            const dataTable = $('#stockInHistoryTable').DataTable({
                processing: true,
                serverSide: true,
                searching: false,
                lengthChange: false,
                ajax: {
                    url: "{{ url('/erp/productions/stock-in/history/' . $supplierId . '/data') }}",
                    data: function(d) {
                        d.start_date = $('#start_date').val();
                        d.end_date = $('#end_date').val();
                        d.verified = $('#filter_verified').val();
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
                        data: 'change_date',
                        name: 'change_date'
                    },
                    {
                        data: 'user_name',
                        name: 'user_name'
                    },
                    {
                        data: 'waybill_number',
                        name: 'waybill_number'
                    },
                    {
                        data: 'waybill_image',
                        name: 'waybill_image'
                    },
                    {
                        data: 'stock_in',
                        name: 'stock_in'
                    },
                    {
                        data: 'verified',
                        name: 'verified',
                        orderable: false,
                        searchable: false
                    },
                ]
            });

            $('#start_date, #end_date, #filter_verified').on('change', function() {
                dataTable.ajax.reload();
            });

            // 🟡 Klik tombol edit
            $(document).on('click', '.edit-item', function() {
                const id = $(this).data('id');
                const qty = $(this).data('qty');
                const notes = $(this).data('notes');
                const product = $(this).data('product');

                // Isi modal
                $('#edit_id').val(id);
                $('#product_name').val(product);
                $('#edit_quantity').val(qty);
                $('#edit_notes').val(notes || '');

                // Tampilkan modal
                $('#editHistoryModal').modal('show');
            });

            // 🟢 Submit form
            $('#editHistoryForm').on('submit', function(e) {
                e.preventDefault();

                const id = $('#edit_id').val();
                const formData = new FormData(this);

                $.ajax({
                    url: `/erp/productions/stock-in/history/item/${id}/update`,
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(res) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: res.message || 'Data berhasil diperbarui.',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        $('#editHistoryModal').modal('hide');
                        setTimeout(() => location.reload(), 500);
                    },
                    error: function(err) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: err.responseJSON?.message ||
                                'Terjadi kesalahan saat menyimpan data.'
                        });
                    }
                });
            });

            // $(document).on('click', '.btn-verify', function() {
            //     const id = $(this).data('id');
            //     console.log('ID:', id);

            //     $.ajax({
            //         url: `/erp/productions/stock-in/verify/${id}`,
            //         method: 'POST',
            //         data: {
            //             _token: '{{ csrf_token() }}'
            //         },
            //         success: function(res) {
            //             console.log('SUCCESS', res);
            //         },
            //         error: function(xhr) {
            //             console.log('ERROR', xhr.status, xhr.responseText);
            //         }
            //     });
            // });

            let verifyId = null;

            $(document).on('click', '.btn-verify', function() {
                verifyId = $(this).data('id');
                $('#verifyModal').modal('show');
            });

            $('#btnConfirmVerify').on('click', function() {
                $.ajax({
                    url: `/erp/productions/stock-in/verify/${verifyId}`,
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(res) {
                        $('#verifyModal').modal('hide');
                        dataTable.ajax.reload(null, false);
                    },
                    error: function(xhr) {
                        console.log('ERROR', xhr.status, xhr.responseText);
                    }
                });
            });
        });
    </script>
@endpush
