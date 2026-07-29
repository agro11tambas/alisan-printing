@extends('erp.layouts.main')

@push('styles')
    <style>
        .stockin-history-mobile {
            display: none;
        }

        @media (max-width: 991px) {

            #stockInHistoryTable_wrapper {
                display: none !important;
            }

            .stockin-history-mobile {
                display: block;
            }

            .history-mobile-card {
                background: #fff;
                border-radius: 12px;
                padding: 16px;
                margin: 0 12px 14px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, .06);
            }

            .history-mobile-title {
                font-weight: 600;
                font-size: 14px;
                margin-bottom: 6px;
            }

            .history-mobile-row {
                font-size: 12px;
                margin-bottom: 6px;
                color: #6b7280;
            }

            .history-mobile-label {
                font-weight: 600;
                color: #111;
            }

        }

        /* Mobile History Stock In: flat-card pattern */
        .stockin-history-mobile { display: none; }

        @media (max-width: 767.98px) {
            #stockInHistoryTable_wrapper { display: none !important; }
            .stockin-history-mobile { display: block !important; }
            .history-mobile-card {
                background: transparent;
                border-radius: 0;
                padding: 8px 14px;
                margin: 0 0 5px;
                box-shadow: none;
                border-bottom: 1px solid #e5e9ef;
            }
            .history-mobile-header {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                gap: 12px;
            }
            .history-mobile-title {
                min-width: 0;
                color: #4b5563;
                font-size: 12px;
                font-weight: 600;
                overflow-wrap: anywhere;
            }
            .history-mobile-date {
                flex: 0 0 auto;
                color: #ef4444;
                font-size: 11px;
                text-align: right;
            }
            .history-mobile-meta {
                display: flex;
                flex-wrap: wrap;
                gap: 4px 12px;
                margin-top: 4px;
                color: #6b7280;
                font-size: 11px;
            }
            .history-mobile-waybill {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 10px;
                margin-top: 5px;
            }
            .history-mobile-waybill-number {
                color: #4b5563;
                font-size: 11px;
                overflow-wrap: anywhere;
            }
            .history-mobile-waybill-image img,
            .history-mobile-waybill-image div {
                width: 72px !important;
                max-width: 72px !important;
                border-radius: 5px !important;
            }
            .history-mobile-items { margin-top: 7px; }
            .history-mobile-items .table-responsive { overflow: visible; }
            .history-mobile-items table {
                width: 100%;
                margin: 0 !important;
                border: 0 !important;
            }
            .history-mobile-items thead { display: none; }
            .history-mobile-items tbody,
            .history-mobile-items tr {
                display: block;
                width: 100%;
            }
            .history-mobile-items tr {
                display: grid;
                grid-template-columns: minmax(0, 1fr) auto;
                gap: 3px 8px;
                padding: 6px 0;
                border: 0;
                border-top: 1px dashed #d8dde5;
            }
            .history-mobile-items td {
                display: block;
                padding: 0 !important;
                border: 0 !important;
                color: #4b5563;
                font-size: 11px;
                min-width: 0;
                overflow-wrap: anywhere;
            }
            .history-mobile-items td:first-child { font-weight: 600; }
            .history-mobile-items td:nth-child(2) {
                color: #16a34a;
                font-weight: 600;
                text-align: right;
            }
            .history-mobile-items td:nth-child(3) {
                grid-column: 1;
                color: #9ca3af;
            }
            .history-mobile-items td:nth-child(4) {
                grid-column: 2;
                grid-row: 2;
                text-align: right;
            }
            .history-mobile-items .btn {
                padding: 2px 8px;
                font-size: 10px;
            }
        }

        @media (min-width: 768px) {
            #stockInHistoryTable_wrapper { display: block !important; }
            .stockin-history-mobile { display: none !important; }
        }
    </style>
@endpush

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
    <div class="main-content m-0 m-md-2 m-lg-2 p-0 p-md-0 p-lg-0 pt-1 pt-md-0">
        <div class="row align-items-baseline">
            <div class="col-xxl-8 col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5>{{ $supplier->name }} — {{ isset($purchaseOrderId) ? 'PO ' . $purchaseOrderNumber : (isset($individualInventoryId) ? 'PL ' . $invoiceNumbers : $monthLabel) }}</h5>
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
            {{-- HAPUS/GANTI bagian ini --}}
            <div class="col-xxl-4 col-xl-6">
                <div class="card stretch">
                    <div class="card-header">
                        <h5 class="card-title">Order Information</h5>
                    </div>
                    <div class="card-body task-info">
                        <div class="task-info-list">
                            <div class="row align-items-center mb-2 task-list-row">
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
                            <div class="row align-items-center mb-2 task-list-row">
                                <div class="col-6">
                                    <i class="feather-calendar me-2"></i>
                                    <span class="fw-semibold">Period:</span>
                                </div>
                                <div class="col-6 d-flex">
                                    <span class="border-bottom border-bottom-dashed border-gray-5">
                                        {{ $monthLabel }}
                                    </span>
                                </div>
                            </div>
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
                        <div class="p-2">
                            <div class="col-lg-4 me-2">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="start_date" class="fw-semibold fs-12">Start Date</label>
                                        <input type="date" id="start_date" name="start_date" class="form-control"
                                            style="padding: 0.25rem 0.5rem; font-size: 0.875rem;">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="end_date" class="fw-semibold fs-12">Due Date</label>
                                        <input type="date" id="end_date" name="end_date" class="form-control"
                                            style="padding: 0.25rem 0.5rem; font-size: 0.875rem;">
                                    </div>
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
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                            <div id="stockInHistoryMobile" class="stockin-history-mobile"></div>

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

                        <div class="mb-2">
                            <label class="form-label">Product</label>
                            <input type="text" id="product_name" class="form-control" readonly>
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Quantity (pcs)</label>
                            <input type="number" id="edit_quantity" name="quantity" class="form-control" min="0"
                                required>
                        </div>

                        <div class="mb-2">
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
                    url: "{{ isset($purchaseOrderId)
                        ? url('/erp/inventory/stock-in/by-po/' . $purchaseOrderId . '/history/data')
                        : (isset($individualInventoryId)
                            ? url('/erp/inventory/stock-in/by-pl/' . $individualInventoryId . '/history/data')
                            : url('/erp/inventory/stock-in/history/' . $supplierId . '/' . $year . '/' . $month . '/data')) }}",
                    data: function(d) {
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
                ]
            });

            dataTable.on('xhr', function(e, settings, json) {
                if (window.innerWidth >= 768) return;
                if (json && json.data) {
                    renderStockInHistoryMobile(json.data);
                }
            });

            $('#start_date, #end_date').on('change', function() {
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
                    url: `/erp/inventory/stock-in/history/item/${id}/update`,
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
        });

        function renderStockInHistoryMobile(data) {
            if (window.innerWidth >= 768) return;

            const container = $('#stockInHistoryMobile');
            container.html('');

            if (!data || !data.length) {
                container.html('<div class="text-center text-muted py-3">No history data</div>');
                return;
            }

            data.forEach((row) => {
                container.append(`
                    <div class="history-mobile-card">
                        <div class="history-mobile-header">
                            <div class="history-mobile-title">${row.invoice_number ?? '-'}</div>
                            <div class="history-mobile-date">${row.change_date ?? '-'}</div>
                        </div>
                        <div class="history-mobile-meta">
                            <span><strong>Updated by:</strong> ${row.user_name ?? '-'}</span>
                        </div>
                        <div class="history-mobile-waybill">
                            <div class="history-mobile-waybill-number">
                                <strong>Waybill:</strong> ${row.waybill_number || '-'}
                            </div>
                            <div class="history-mobile-waybill-image">${row.waybill_image ?? '-'}</div>
                        </div>
                        <div class="history-mobile-items">${row.stock_in ?? '-'}</div>
                    </div>
                `);
            });
        }
    </script>
@endpush
