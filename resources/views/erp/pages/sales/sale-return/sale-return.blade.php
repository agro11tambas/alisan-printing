@extends('erp.layouts.main')

@push('styles')
    <style>
        @media (max-width: 768px) {

            #saleReturnTable td.desktop-only,
            #saleReturnTable th.desktop-only {
                display: none !important;
            }
        }

        #saleReturnTable {
            width: 100% !important;
            min-width: 0;
        }

        #saleReturnTable_wrapper .dataTables_scrollBody {
            background-image: none !important;
            height: 60vh !important;
            overflow-y: auto !important;
        }

        #deletedsaleReturnTable_wrapper .dataTables_scrollBody {
            background-image: none !important;
            height: 60vh !important;
            overflow-y: auto !important;
        }

        .dataTables_scrollBody {
            scroll-behavior: smooth;
            height: calc(100vh - 260px) !important;
            min-height: calc(100vh - 260px) !important;
            max-height: calc(100vh - 260px) !important;
        }

        #saleReturnTable tbody tr {
            animation: fadeIn 0.3s ease-in;
        }

        .static-action-menu {
            padding: 6px;
            min-width: 500px;
        }

        .action-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px 20px;
        }

        .action-col {
            display: flex;
            flex-direction: column;
        }

        .action-title {
            font-weight: 600;
            font-size: 13px;
            color: #6c757d;
            border-bottom: 1px solid #e9ecef;
            margin-bottom: 7px;
            padding-bottom: 4px;
        }

        .dropdown-item {
            font-size: 13px;
            padding: 6px 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .preview-list {
            display: block;
        }

        .preview-item {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            margin-bottom: 1rem;
            width: 100%;
        }

        .preview-item img {
            width: 100%;
            height: auto;
            border-radius: 6px;
            margin-bottom: 6px;
            object-fit: cover;
        }

        .preview-item input.note-input {
            width: 100%;
        }
    </style>
@endpush

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Sale Returns</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Sale Returns</li>
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
                        <div class="row g-3 p-2 justify-content-between">
                            <div class="col-lg-4 me-2">
                                <label for="" class="fw-semibold fs-12">Date</label>
                                <div class="d-flex align-items-center gap-2 page-header-right-items-wrapper">
                                    <div class="col-auto">
                                        <select id="filter" class="form-control"
                                            style="padding: 0.25rem 0.5rem; font-size: 0.875rem; width: 200px !important;">
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
                                            style="padding: 0.25rem 0.5rem; font-size: 0.875rem;">
                                    </div>
                                    <div class="col-auto custom-range d-none">
                                        <input type="date" id="end_date" class="form-control"
                                            style="padding: 0.25rem 0.5rem; font-size: 0.875rem;">
                                    </div>
                                    <div class="col-auto custom-range d-none">
                                        <button id="apply-filter" class="btn btn-primary">Apply</button>
                                    </div>
                                </div>
                            </div>
                            {{-- <div class="col-lg-3">

                            </div> --}}
                            <div class="d-flex gap-2 col-lg-4">
                                <div class="col-lg-4">
                                    <label class="fw-semibold fs-12">Payment Status</label>
                                    <select id="payment_status" class="form-control"
                                        style="padding: 0.25rem 0.5rem; font-size: 0.875rem;">
                                        <option value="Progress">Progress</option>
                                        <option value="Complete">Complete</option>
                                        {{-- <option value="Unpaid">Unpaid</option>
                                        <option value="Partially Paid">Partially Paid</option>
                                        <option value="Refunded">Refunded</option>
                                        <option value="Customer Deposit">Customer Deposit</option> --}}
                                    </select>
                                </div>
                                <div class="col-lg-8">
                                    <label for="search_type" class="fw-semibold fs-12">Filter By</label>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <select id="search_type" class="form-control"
                                                style="padding: 0.25rem 0.5rem; font-size: 0.875rem;">
                                                <option value="customer">Customer</option>
                                                <option value="order_number">Order Number</option>
                                                <option value="payment_status">Payment Status</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <input type="text" id="search_keyword" name="search_keyword"
                                                class="form-control search-input"
                                                style="padding: 0.25rem 0.5rem; font-size: 0.875rem;"
                                                placeholder="Search..." />
                                            <select id="search_payment_status" class="form-control search-input d-none"
                                                style="padding: 0.25rem 0.5rem; font-size: 0.875rem;">
                                                <option value="-">-</option>
                                                <option value="Refunded">Refunded</option>
                                                <option value="Unpaid">Unpaid</option>
                                                <option value="Customer Deposit">Customer Deposit</option>
                                                <option value="Partially Paid">Partially Paid</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <ul class="nav nav-tabs mb-2" id="saleReturnTabs" role="tabreturn">
                            <li class="nav-item">
                                <a class="nav-link active" id="sale-return-tab" data-bs-toggle="tab" href="#sale-return"
                                    role="tab">Sale Return</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="deleted-sale-return-tab" data-bs-toggle="tab"
                                    href="#deleted-sale-return" role="tab">Deleted Sale Return</a>
                            </li>
                        </ul>
                        <div class="table-responsive">
                            <div class="tab-content">
                                <div class="tab-pane fade show active" id="sale-return" role="tabpanel">
                                    <table class="table table-hover bg-transparent" id="saleReturnTable">
                                        <thead>
                                            <tr>
                                                <th></th>
                                                <th>Invoice Number</th>
                                                <th>Customer</th>
                                                <th>Total Amount</th>
                                                <th>Refund Amount</th>
                                                {{-- <th>Remaining Amount</th> --}}
                                                <th>Payment Status (Sale)</th>
                                                <th>Return Status</th>
                                                <th>Note</th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                                <div class="tab-pane fade show" id="deleted-sale-return" role="tabpanel">
                                    <table class="table table-hover bg-transparent" id="deletedSaleReturnTable">
                                        <thead>
                                            <tr>
                                                <th></th>
                                                <th>Invoice Number</th>
                                                <th>Customer</th>
                                                <th>Grand Total</th>
                                                <th>Deleted At</th>
                                                <th>Deleted By</th>
                                                <th>Delete Notes</th>
                                                @if (auth()->user()->role === 'Owner')
                                                    <th>Action</th>
                                                @endif
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                            </div>
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
                        <h5 class="modal-title text-white" id="deleteModalLabel">Hapus Sale Return</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p style="font-size: 16px;">1. Apakah anda yakin ingin menghapus data ?</p>
                        <p style="font-size: 16px;">2. Jika ada kesalahan saat audit. Anda siap untuk Bertanggung Jawab ?
                            Kolom Keterangan (Wajib diisi)</p>
                        <div class="form-group mt-2">
                            <label for="delete_notes" class="fw-semibold">Keterangan <span
                                    class="text-danger">*</span></label>
                            <textarea class="form-control" id="delete_notes" name="delete_notes" rows="3" required
                                placeholder="Tuliskan alasan penghapusan..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-md" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger btn-md">Hapus</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade-scale" id="modalChangeStatus" tabindex="-1" aria-labelledby="modalChangeStatus"
        aria-hidden="true" data-bs-dismiss="ou">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="d-flex flex-column mb-0">
                        <span class="fs-18 fw-bold mb-1">Mark As Refund</span>
                    </h2>
                    <a href="javascript:void(0)" class="avatar-text avatar-md bg-soft-danger close-icon"
                        data-bs-dismiss="modal">
                        <i class="feather-x text-danger"></i>
                    </a>
                </div>
                <form method="POST" id="markAsSaleForm" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="sale_return_id" name="sale_return_id">

                    <div class="modal-body">
                        <div class="row g-3 mb-2">
                            <div class="col-md-6">
                                <label for="transaction_type" class="fw-semibold">Account:</label>
                                <div class="input-group">
                                    <select class="form-select form-control max-select" data-select2-selector="tag"
                                        id="transaction_type" name="transaction_type">
                                        <option value="13" data-bg="bg-danger">Sale Return</option>
                                    </select>
                                </div>
                                <small class="text-danger d-none" id="error_transaction_type"></small>
                            </div>
                            <div class="col-md-6">
                                <label for="transaction_date" class="fw-semibold">Transaction Date:</label>
                                <div class="input-group">
                                    <input type="date" id="transaction_date" name="transaction_date"
                                        class="form-control" value="{{ date('Y-m-d') }}">
                                </div>
                                <small class="text-danger d-none" id="error_transaction_date"></small>
                            </div>
                        </div>
                        <div class="row g-3 mb-2">
                            <div class="col-md-6">
                                <label for="cash_bank_account_id" class="fw-semibold">Cash/Bank Account:</label>
                                <div class="input-group">
                                    @php
                                        $bgColors = [
                                            'bg-danger',
                                            'bg-warning',
                                            'bg-primary',
                                            'bg-indigo',
                                            'bg-success',
                                        ];
                                    @endphp
                                    <select class="form-select form-control max-select" data-select2-selector="tag"
                                        name="cash_bank_account_id" id="cash_bank_account_id">
                                        <option value="" disabled {{ !$defaultAccount ? 'selected' : '' }} hidden>
                                            Pilih Bank atau Cash Account
                                        </option>

                                        @foreach ($cashAccounts as $cash)
                                            @php $bg = $bgColors[$loop->index % count($bgColors)]; @endphp
                                            <option value="{{ $cash->id }}" data-bg="{{ $bg }}"
                                                {{ isset($defaultAccount) && $defaultAccount->id == $cash->id ? 'selected' : '' }}>
                                                Cash - {{ $cash->type }}
                                            </option>
                                        @endforeach

                                        @foreach ($bankAccounts as $bank)
                                            @php $bg = $bgColors[$loop->index % count($bgColors)]; @endphp
                                            <option value="{{ $bank->id }}" data-bg="{{ $bg }}"
                                                {{ isset($defaultAccount) && $defaultAccount->id == $bank->id ? 'selected' : '' }}>
                                                Bank - {{ $bank->type }}
                                            </option>
                                        @endforeach
                                    </select>

                                </div>
                                <small class="text-danger d-none" id="error_cash_bank_account_id"></small>
                            </div>
                            <div class="col-md-6">
                                <label for="refund_amount" class="fw-semibold">Paid Amount:</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="refund_amount" name="refund_amount"
                                        value="0">
                                </div>
                                <small class="text-danger d-none" id="error_refund_amount"></small>
                                <span class="fw-semibold fs-12" id="paid_amount_display">Paid: Rp. 0</span>
                            </div>
                        </div>
                        {{-- <div class="row g-3 mb-2">
                            <div class="col-md-12">
                                <label for="payment_proof" class="fw-semibold">Upload Proof (optional):</label>
                                <div class="input-group">
                                    <input type="file" class="form-control" id="payment_proof" name="payment_proof"
                                        accept="image/jpg,image/jpeg,image/png,image/webp,application/pdf">
                                </div>
                                <small class="text-muted">Upload foto bukti transfer (Gambar)</small>
                                <small class="text-danger d-none" id="error_payment_proof"></small>
                                <div class="mt-1 d-none" id="proof_preview_wrapper">
                                    <p class="fw-semibold mb-1">Preview:</p>
                                    <img id="proof_preview" src="#" alt="Proof Preview" class="img-thumbnail"
                                        style="max-height: 200px;">
                                </div>
                            </div>
                        </div> --}}

                        <div class="col-md-12">
                            <label class="fw-semibold">Upload / Paste Proof (optional):</label>

                            <div id="pasteProofArea" class="border rounded p-2 text-center"
                                style="min-height: 120px; cursor: pointer;">
                                <p class="text-muted small mb-1">
                                    Klik di sini lalu tekan <strong>Ctrl + V</strong> untuk paste screenshot bukti transfer
                                </p>

                                <!-- 🔹 ubah layout preview -->
                                <div id="proofPreviewContainer" class="preview-list"></div>
                            </div>

                            {{-- <input type="file" class="form-control mt-1" id="payment_proof" name="payment_proof[]"
                                accept="image/jpg,image/jpeg,image/png,image/webp,application/pdf" multiple> --}}

                            <small class="text-danger d-none" id="error_payment_proof"></small>
                        </div>

                    </div>
                    <div class="modal-footer d-flex justify-content-between">
                        <div>
                            <div class="col-md-6">
                                <p class="m-0">Balance:</p>
                                <h5 class="fw-semibold text-danger" id="total_amount_display">0</h5>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Mark As Refund</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <div class="modal fade" id="modalForceDeleteOrder" tabindex="-1" aria-labelledby="forceDeleteModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" id="formForceDeleteOrder">
                @csrf
                @method('DELETE')
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title text-white" id="forceDeleteModalLabel">Hapus Permanen Order</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Apakah Anda yakin ingin <strong>menghapus permanen</strong> Order <strong
                                id="ForceOrderName"></strong>?</p>
                        <p class="text-muted">⚠️ Data yang dihapus permanen <b>tidak bisa dikembalikan</b>.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-md" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger btn-md">Hapus Permanen</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="modalRestoreOrder" tabindex="-1" aria-labelledby="restoreModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" id="formRestoreOrder">
                @csrf
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title text-white" id="restoreModalLabel">Restore Order</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Apakah Anda yakin ingin mengembalikan Order <strong id="RestoreOrderName"></strong>?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-md" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success btn-md">Restore</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="modalForceDeleteOwner" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" id="forceDeleteOwnerForm">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title text-danger">Force Delete Sale Return</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Yakin ingin menghapus permanen Sale Return <b id="fd-order-number"></b>?</p>
                        <p class="text-danger fw-bold mb-0">Tindakan ini tidak dapat dibatalkan.</p>
                        <div class="mt-2">
                            <label for="fd-delete-notes" class="form-label">Alasan Penghapusan</label>
                            <textarea class="form-control" name="delete_notes" id="fd-delete-notes" rows="2" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">Hapus Permanen</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade-scale" id="modalMarkAsCustomerDeposit" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="fw-bold mb-0">Mark as Customer Deposit</h5>
                    <a href="javascript:void(0)" class="avatar-text avatar-md bg-soft-danger" data-bs-dismiss="modal">
                        <i class="feather-x text-danger"></i>
                    </a>
                </div>

                <form method="POST" id="markAsCustomerDepositForm">
                    @csrf
                    <input type="hidden" name="sale_return_id" id="deposit_sale_return_id">

                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="fw-semibold">Deposit Amount</label>
                            <input type="text" class="form-control" id="deposit_amount" name="deposit_amount"
                                value="0">
                        </div>

                        <div class="alert alert-warning d-flex justify-content-between align-items-center">
                            <span>Balance</span>
                            <strong id="deposit_balance_display">0</strong>
                        </div>

                        <div class="mb-2">
                            <label class="fw-semibold">Transaction Date</label>
                            <input type="date" class="form-control" name="transaction_date"
                                value="{{ date('Y-m-d') }}">
                        </div>

                        <div class="mb-2">
                            <label class="fw-semibold">Note (optional)</label>
                            <textarea class="form-control" name="note" rows="2"></textarea>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">
                            Mark as Customer Deposit
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade-scale" id="modalReturnToWarehouse" tabindex="-1"
        aria-labelledby="modalReturnToWarehouseLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="d-flex flex-column mb-0">
                        <span class="fs-18 fw-bold mb-1">Return to Warehouse</span>
                        <small class="text-muted">Sale Return: <span id="modal-order-number">-</span></small>
                    </h2>
                    <a href="javascript:void(0)" class="avatar-text avatar-md bg-soft-danger close-icon"
                        data-bs-dismiss="modal">
                        <i class="feather-x text-danger"></i>
                    </a>
                </div>
                <form method="POST" id="formReturnToWarehouse">
                    @csrf
                    <input type="hidden" name="sale_return_id" id="sale_return_id">

                    <div class="modal-body">
                        <div class="row g-3 mb-2">
                            <div class="col-md-6">
                                <label for="return_date" class="fw-semibold">Date:</label>
                                <input type="date" id="return_date" name="return_date" class="form-control"
                                    value="{{ date('Y-m-d') }}">
                                <small class="text-danger d-none" id="error_return_date"></small>
                            </div>

                            {{-- 🆕 Pilihan Destination --}}
                            <div class="col-md-6">
                                <label for="stock_destination" class="fw-semibold">Stock Destination:</label>
                                <select id="stock_destination" name="stock_destination" class="form-control">
                                    <option value="warehouse">Warehouse</option>
                                    <option value="production">Production</option>
                                </select>
                                <small class="text-danger d-none" id="error_stock_destination"></small>
                            </div>
                        </div>

                        {{-- 🆕 Warehouse Selection (show/hide based on destination) --}}
                        <div class="row g-3 mb-2" id="warehouse_selection">
                            <div class="col-md-12">
                                <label for="inventory_warehouse_id" class="fw-semibold">Warehouse:</label>
                                <select id="inventory_warehouse_id" name="inventory_warehouse_id" class="form-control">
                                    @foreach (\App\Models\InventoryWarehouse::all() as $warehouse)
                                        <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- 🆕 Production Warehouse Selection (hidden by default) --}}
                        <div class="row g-3 mb-2 d-none" id="production_selection">
                            <div class="col-md-6">
                                <label for="production_warehouse_id" class="fw-semibold">Production Warehouse:</label>
                                <select id="production_warehouse_id" name="production_warehouse_id" class="form-control">
                                    @foreach (\App\Models\ProductionWarehouse::all() as $prodWarehouse)
                                        <option value="{{ $prodWarehouse->id }}">{{ $prodWarehouse->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" id="tableReturnProducts">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Product Name</th>
                                        <th>SKU</th>
                                        <th width="15%" class="text-center">Available Qty</th>
                                        <th width="20%">Return Qty</th>
                                    </tr>
                                </thead>
                                <tbody id="productListBody">
                                    <tr>
                                        <td colspan="4" class="text-center">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="modal-footer d-flex justify-content-between">
                        <div>
                            <p class="m-0">Total Return Qty:</p>
                            <h5 class="fw-semibold text-primary" id="total_return_qty">0</h5>
                        </div>
                        <button type="submit" class="btn btn-primary" id="btnSubmitReturn">
                            <i class="feather-package me-2"></i>Return to Warehouse
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalMarkAsRetur" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" id="formMarkAsRetur" action="">
                @csrf

                <div class="modal-content">
                    <div class="modal-header bg-warning text-white">
                        <h5 class="modal-title text-white">Mark as Retur</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <input type="hidden" id="retur_sale_return_id" name="sale_return_id">

                        <p class="mb-1">
                            Yakin ingin mengubah payment status sale return ini menjadi <strong>Retur</strong>?
                        </p>

                        <div class="alert alert-warning mb-0">
                            <div class="fw-semibold">Sale Return:</div>
                            <div id="retur_order_number">-</div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-md" data-bs-dismiss="modal">
                            Batal
                        </button>
                        <button type="submit" class="btn btn-warning btn-md text-white">
                            Ya, Mark as Retur
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {

            // ===== Helper: format produk di detail row =====
            function formatProducts(products) {
                if (!products || products.length === 0) {
                    return '<div class="p-1 text-muted">No products</div>';
                }

                let html = `
                    <div class="table-responsive p-1">
                        <table class="table bg-transparent table-sm table-bordered mb-0 w-auto">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>SKU</th>
                                    <th>Qty</th>
                                    <th class="text-end">Price</th>
                                </tr>
                            </thead>
                            <tbody>
                    `;

                products.forEach(p => {
                    html += `
                                <tr>
                                    <td style="white-space: normal; word-break: break-word; max-width: 280px;">${p.name}</td>
                                    <td>${p.sku}</td>
                                    <td>${p.qty}</td>
                                    <td class="text-end">${p.price}</td>
                                </tr>`;
                });

                html += `</tbody></table></div>`;
                return html;
            }

            function reloadActiveTab() {
                const activeTab = $('#saleReturnTabs .nav-link.active').attr('href');

                if (activeTab === '#deleted-sale-return') {
                    resetAndReloadDeleted();
                } else {
                    resetAndReload();
                }
            }

            // ========== SALE RETURN TABLE (CSR dengan Lazy Load) ==========
            let allData = [];
            let currentPage = 0;
            let isLoading = false;
            let hasMoreData = true;

            const dataTable = $('#saleReturnTable').DataTable({
                processing: false,
                serverSide: false,
                scrollY: '60vh',
                scrollCollapse: true,
                paging: false,
                searching: false,
                info: false,
                lengthChange: false,
                ordering: false,
                order: [
                    [7, 'desc']
                ],
                data: [],
                columns: [{
                        className: 'dt-control text-center',
                        orderable: false,
                        data: null,
                        defaultContent: '',
                        width: "20px"
                    },
                    {
                        data: 'order_number'
                    },
                    {
                        data: 'customer'
                    },
                    {
                        data: 'total_amount'
                    },
                    {
                        data: 'refund_amount'
                    },
                    // {
                    //     data: 'remaining_amount'
                    // },
                    {
                        data: 'order_payment_status'
                    },
                    {
                        data: 'return_payment_status'
                    },
                    {
                        data: 'note'
                    },
                    {
                        data: 'return_date', // tambahkan kolom ini
                        visible: false, // disembunyikan dari tampilan
                        searchable: false // tidak perlu di-search
                    }
                ]
            });

            let searchTimer = null;
            let currentRequest = null;

            function loadMoreData() {
                if (isLoading || !hasMoreData) return;
                isLoading = true;

                // 🚫 Batalkan request sebelumnya jika masih berjalan
                if (currentRequest) {
                    currentRequest.abort();
                }

                currentRequest = $.ajax({
                    url: "{{ url('/erp/sales/sale-returns/data') }}",
                    type: 'GET',
                    data: {
                        start: currentPage * 50,
                        length: 50,
                        filter: $('#filter').val(),
                        start_date: $('#start_date').val(),
                        end_date: $('#end_date').val(),
                        search_type: $('#search_type').val(),
                        search_keyword: $('#search_keyword').val(),
                        payment_status: $('#payment_status').val(),
                    },
                    success: function(response) {
                        if (response && response.data && response.data.length > 0) {
                            allData = allData.concat(response.data);
                            if (dataTable.rows().count() === 0) {
                                dataTable.rows.add(response.data).draw(false);
                            } else {
                                let newNodes = dataTable.rows.add(response.data).nodes();
                                $(dataTable.table().body()).append(newNodes);
                            }
                            currentPage++;
                        } else {
                            hasMoreData = false;
                        }
                    },
                    complete: function() {
                        isLoading = false;
                        currentRequest = null;
                    },
                    error: function(xhr) {
                        if (xhr.statusText !== "abort") {
                            console.error("AJAX error:", xhr);
                        }
                        isLoading = false;
                    }
                });
            }


            loadMoreData();


            $('.dataTables_scrollBody').on('scroll', function() {
                const scrollTop = $(this).scrollTop();
                const scrollHeight = $(this)[0].scrollHeight;
                const clientHeight = $(this).height();

                // Load earlier (70%) without delay
                if (scrollTop + clientHeight >= scrollHeight * 0.70) {
                    loadMoreData();
                }
            });

            function resetAndReload() {
                allData = [];
                currentPage = 0;
                hasMoreData = true;
                dataTable.clear().draw();
                loadMoreData();
            }

            $('#filter, #apply-filter, #search_type, #search_keyword, #payment_status, #start_date, #end_date')
                .on('change keyup', function() {
                    clearTimeout(searchTimer);
                    searchTimer = setTimeout(() => {
                        reloadActiveTab();
                    }, 150);
                });

            $('#apply-filter').on('click', function() {
                resetAndReload();
            });

            $('#search_type').on('change', function() {
                const selected = $(this).val();
                if (selected === 'payment_status') {
                    $('#search_keyword').addClass('d-none').val('');
                    // $('#search_payment_status').removeClass('d-none');
                } else {
                    $('#search_keyword').removeClass('d-none');
                    // $('#search_payment_status').addClass('d-none').val('');
                }
                resetAndReload();
            });

            // Debounce untuk search keyword
            // 
            // $('#search_keyword').on('keyup', function() {
            //     if ($('#search_type').val() !== 'payment_status') {
            //         clearTimeout(searchTimeout);
            //         searchTimeout = setTimeout(() => resetAndReload(), 500);
            //     }
            // });

            // $('#search_payment_status').on('change', function() {
            //     if ($('#search_type').val() === 'payment_status') resetAndReload();
            // });

            $('#payment_status').on('change', function() {
                reloadActiveTab();
            });


            // Expand/collapse products detail
            $('#saleReturnTable tbody').on('click', 'td.dt-control', function() {
                let tr = $(this).closest('tr');
                let row = dataTable.row(tr);
                let icon = $(this).find('i');

                if (row.child.isShown()) {
                    row.child.hide();
                    tr.removeClass('shown');
                    icon.removeClass('feather-minus').addClass('feather-plus');
                } else {
                    row.child(formatProducts(row.data().products)).show();
                    tr.addClass('shown');
                    icon.removeClass('feather-plus').addClass('feather-minus');
                }
            });

            // Action button dropdown
            $('#saleReturnTable tbody').on('click', 'tr', function(e) {
                if ($(e.target).closest('td.dt-control').length) return;

                let $tr = $(this);
                let row = dataTable.row($tr);

                $('#saleReturnTable tbody tr').removeClass('action-shown').next('.action-row').remove();

                if ($tr.hasClass('action-shown')) {
                    $tr.removeClass('action-shown');
                } else {
                    let actionHtml = row.data().action || '';
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
                if ($(e.target).closest('#saleReturnTable').length) return;
                $('#saleReturnTable tbody tr').removeClass('action-shown').next('.action-row').remove();
            });

            let deletedAllData = [];
            let deletedCurrentPage = 0;
            let deletedIsLoading = false;
            let deletedHasMoreData = true;
            let deletedTable = null;
            let deletedTableInitialized = false;

            function initDeletedTable() {
                if (deletedTableInitialized) return;

                const deletedColumns = [{
                        className: 'dt-control text-center',
                        orderable: false,
                        data: null,
                        defaultContent: '',
                        width: "20px"
                    },
                    {
                        data: 'order_number'
                    },
                    {
                        data: 'customer'
                    },
                    {
                        data: 'total_amount'
                    },
                    {
                        data: 'deleted_at'
                    },
                    {
                        data: 'deleted_by'
                    },
                    {
                        data: 'delete_notes'
                    }
                ];

                @if (auth()->user()->role === 'Owner')
                    deletedColumns.push({
                        data: 'action',
                        orderable: false,
                        searchable: false
                    });
                @endif

                deletedTable = $('#deletedSaleReturnTable').DataTable({
                    processing: false,
                    serverSide: false,
                    scrollY: '60vh',
                    scrollCollapse: true,
                    paging: false,
                    searching: false,
                    info: false,
                    lengthChange: false,
                    ordering: false,
                    order: [
                        [4, 'asc']
                    ],
                    data: [],
                    columns: deletedColumns
                });

                deletedTableInitialized = true;

                $('#deletedSaleReturnTable').closest('.dataTables_scrollBody').on('scroll', function() {
                    const scrollTop = $(this).scrollTop();
                    const scrollHeight = $(this)[0].scrollHeight;
                    const clientHeight = $(this).height();

                    // Load earlier (70%) without delay
                    if (scrollTop + clientHeight >= scrollHeight * 0.70) {
                        loadMoreDeletedData();
                    }
                });

                $('#deletedSaleReturnTable tbody').on('click', 'td.dt-control', function() {
                    let tr = $(this).closest('tr');
                    let row = deletedTable.row(tr);
                    let icon = $(this).find('i');

                    if (row.child.isShown()) {
                        row.child.hide();
                        tr.removeClass('shown');
                        icon.removeClass('feather-minus').addClass('feather-plus');
                    } else {
                        row.child(formatProducts(row.data().products)).show();
                        tr.addClass('shown');
                        icon.removeClass('feather-plus').addClass('feather-minus');
                    }
                });
            }

            function loadMoreDeletedData() {
                if (deletedIsLoading || !deletedHasMoreData) return;

                deletedIsLoading = true;

                $.ajax({
                    url: "{{ url('/erp/sales/sale-returns/data-deleted') }}",
                    type: 'GET',
                    data: {
                        start: deletedCurrentPage * 50,
                        length: 50,
                        filter: $('#filter').val(),
                        start_date: $('#start_date').val(),
                        end_date: $('#end_date').val(),
                        search_type: $('#search_type').val(),
                        search_keyword: $('#search_keyword').val(),
                        payment_status: $('#payment_status').val(),
                    },
                    success: function(response) {

                        if (response && response.data && response.data.length > 0) {
                            deletedAllData = deletedAllData.concat(response.data);
                            if (deletedTable.rows().count() === 0) {
                                deletedTable.rows.add(response.data).draw(false);
                            } else {
                                let newNodes = deletedTable.rows.add(response.data).nodes();
                                $(deletedTable.table().body()).append(newNodes);
                            }
                            deletedCurrentPage++;
                        } else {
                            deletedHasMoreData = false;
                        }

                        deletedIsLoading = false;
                    },
                    error: function(xhr, status, error) {
                        deletedIsLoading = false;
                    }
                });
            }

            function resetAndReloadDeleted() {
                deletedAllData = [];
                deletedCurrentPage = 0;
                deletedHasMoreData = true;
                if (deletedTable) {
                    deletedTable.clear().draw();
                }
                loadMoreDeletedData();
            }

            $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
                const target = $(e.target).attr('href');

                if (target === '#sale-return') {
                    resetAndReload();
                }

                if (target === '#deleted-sale-return') {
                    if (!deletedTableInitialized) initDeletedTable();
                    resetAndReloadDeleted();
                }
            });

            // $('#search_keyword').on('keyup', function() {
            //     clearTimeout(searchTimeout);
            //     searchTimeout = setTimeout(() => {
            //         reloadActiveTab();
            //     }, 300);
            // });

            // === ENTER ONLY SEARCH ===
            $('#search_keyword').on('keypress', function(e) {
                if (e.which === 13) { // ENTER
                    e.preventDefault();
                    reloadActiveTab();
                }
            });

            // === Auto reload ketika input dikosongkan ===
            $('#search_keyword').on('input', function() {
                if ($(this).val().trim() === '') {
                    reloadActiveTab();
                }
            });

            // Paste proof functionality
            let pastedProofBlobs = [];

            const pasteArea = document.getElementById('pasteProofArea');
            const previewContainer = document.getElementById('proofPreviewContainer');

            if (pasteArea) {
                pasteArea.setAttribute('tabindex', '0'); // Make focusable

                pasteArea.addEventListener('click', () => {
                    if (e.target.tagName !== 'INPUT' && e.target.tagName !== 'BUTTON') {
                        pasteArea.focus();
                    }
                });

                pasteArea.addEventListener('paste', (e) => {

                    // 🔥 Jika user paste di input note → IZINKAN paste normal
                    if (e.target.classList.contains('note-input')) {
                        return;
                    }

                    // 📌 Kalau bukan input note → intercept image untuk preview
                    e.preventDefault();

                    const items = e.clipboardData.items;

                    for (const item of items) {
                        if (item.type.indexOf("image") === 0) {
                            const blob = item.getAsFile();
                            pastedProofBlobs.push(blob);

                            const reader = new FileReader();
                            reader.onload = function(event) {
                                const wrapper = document.createElement('div');
                                wrapper.classList.add('preview-item');

                                const img = document.createElement('img');
                                img.src = event.target.result;
                                img.classList.add('img-thumbnail');
                                img.style.maxHeight = '150px';
                                img.style.marginBottom = '5px';

                                const noteInput = document.createElement('input');
                                noteInput.type = 'text';
                                noteInput.classList.add('form-control', 'form-control-sm',
                                    'note-input');
                                noteInput.placeholder = 'Tambahkan catatan...';
                                noteInput.style.width = '100%';

                                // Add remove button
                                const removeBtn = document.createElement('button');
                                removeBtn.type = 'button';
                                removeBtn.className = 'btn btn-sm btn-danger mt-1';
                                removeBtn.innerHTML = '<i class="feather-x"></i> Hapus';
                                removeBtn.onclick = function() {
                                    const index = Array.from(previewContainer.children).indexOf(
                                        wrapper);
                                    pastedProofBlobs.splice(index, 1);
                                    wrapper.remove();
                                };

                                wrapper.appendChild(img);
                                wrapper.appendChild(noteInput);
                                wrapper.appendChild(removeBtn);
                                previewContainer.appendChild(wrapper);
                            };
                            reader.readAsDataURL(blob);
                        }
                    }
                });
            }

            // ========= MARK AS REFUND (di dalam $(document).ready) =========
            $('#markAsSaleForm').on('submit', function(e) {
                e.preventDefault();

                const form = $(this);
                const url = form.attr('action');
                const formData = new FormData(this);

                // Reset error messages
                form.find('small.text-danger').addClass('d-none').text('');

                let valid = true;

                const transactionType = $('#transaction_type').val()?.trim();
                const transactionDate = $('#transaction_date').val()?.trim();
                const cashBankAccount = $('#cash_bank_account_id').val()?.trim();

                let refundAmountRaw = $('#refund_amount').val()?.trim() || '0';
                const refundAmount = refundAmountRaw.replace(/\./g, '');
                const remainingRaw = $('#total_amount_display').text().trim().replace(/[^\d]/g,
                    ''); // Hapus semua non-digit
                const remainingAmount = parseInt(remainingRaw) || 0;

                // ====== VALIDASI ======
                if (!transactionType) {
                    $('#error_transaction_type').text('Account wajib dipilih').removeClass('d-none');
                    valid = false;
                }

                if (!transactionDate) {
                    $('#error_transaction_date').text('Tanggal transaksi wajib diisi').removeClass(
                        'd-none');
                    valid = false;
                }

                if (!cashBankAccount) {
                    $('#error_cash_bank_account_id').text('Pilih cash atau bank account').removeClass(
                        'd-none');
                    valid = false;
                }

                if (!refundAmount || isNaN(refundAmount) || parseInt(refundAmount) <= 0) {
                    $('#error_refund_amount').text('Refund amount harus diisi dan lebih dari 0')
                        .removeClass('d-none');
                    valid = false;
                } else if (parseInt(refundAmount) > remainingAmount) {
                    $('#error_refund_amount').text('Refund amount tidak boleh melebihi Balance')
                        .removeClass('d-none');
                    valid = false;
                }

                if (!valid) return;

                // Tampilkan loading
                Swal.fire({
                    title: 'Processing...',
                    text: 'Mohon tunggu',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Format angka
                $('#refund_amount').val(refundAmount);

                const notes = [];
                $('#proofPreviewContainer .note-input').each(function() {
                    notes.push($(this).val());
                });

                // 🔹 Tambahkan hasil paste screenshot dan note ke FormData
                if (typeof pastedProofBlobs !== 'undefined' && pastedProofBlobs.length > 0) {
                    pastedProofBlobs.forEach((blob, index) => {
                        formData.append('payment_proof[]', blob, `proof_${index + 1}.png`);
                        formData.append('note_per_image[]', notes[index] || '');
                    });
                }

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function() {
                        Swal.close();
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: 'Sale Return berhasil ditandai sebagai Refund.'
                        });

                        $('#modalChangeStatus').modal('hide');
                        form[0].reset();

                        // Reset pasted proof
                        if (typeof pastedProofBlobs !== 'undefined') {
                            pastedProofBlobs = [];
                        }
                        const previewContainer = document.getElementById(
                            'proofPreviewContainer');
                        if (previewContainer) previewContainer.innerHTML = '';

                        // 🔁 Refresh tabel tanpa reload halaman
                        allData = [];
                        currentPage = 0;
                        hasMoreData = true;
                        dataTable.clear().draw();
                        loadMoreData();
                    },
                    error: function(xhr) {
                        Swal.close();
                        let msg = 'Gagal menandai Refund.';
                        if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON
                            .message;
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: msg
                        });
                    }
                });
            });

            $('#formDeleteOrder').on('submit', function(e) {
                e.preventDefault();
                const form = this;
                const url = form.action;
                const formData = new FormData(form);

                $.ajax({
                    url: url,
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(res) {
                        Swal.close();
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: res.message ?? 'Sale Return berhasil dihapus!',
                            timer: 1800,
                            showConfirmButton: false
                        });

                        $('#modalDeleteOrder').modal('hide');
                        form.reset();

                        // 🔥 ambil id dari modal
                        const id = form.dataset.id;

                        // 🔥 cari baris di DataTable sesuai id, langsung hapus DOM-nya tanpa reload
                        const table = $('#saleReturnTable').DataTable();
                        const rowNode = table.rows().nodes().to$().filter(function() {
                            const rowData = table.row(this).data();
                            return rowData && rowData.id == id;
                        });

                        if (rowNode.length) {
                            rowNode.fadeOut(300, function() {
                                table.row(rowNode).remove().draw(false);
                            });
                        }

                        // 🔥 hapus dari array JS juga
                        const index = allData.findIndex(r => r.id == id);
                        if (index !== -1) allData.splice(index, 1);
                    },
                    error: function(xhr) {
                        Swal.close();
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: xhr.responseJSON?.message ??
                                'Gagal menghapus sale return',
                        });
                    }
                });
            });

            $('#formRestoreOrder').on('submit', function(e) {
                e.preventDefault();
                const form = this;
                const url = form.action;
                const formData = new FormData(form);

                $.ajax({
                    url: url,
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(res) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: res.message ?? 'Sale Return berhasil direstore!',
                            timer: 1800,
                            showConfirmButton: false
                        });

                        $('#modalRestoreOrder').modal('hide');
                        form.reset();

                        // 🔥 ambil id dari form
                        const id = form.dataset.id;
                        const table = $('#deletedSaleReturnTable').DataTable();

                        // 🔥 cari baris di Deleted Table, langsung hapus DOM-nya
                        const rowNode = table.rows().nodes().to$().filter(function() {
                            const rowData = table.row(this).data();
                            return rowData && rowData.id == id;
                        });

                        if (rowNode.length) {
                            rowNode.fadeOut(300, function() {
                                table.row(rowNode).remove().draw(false);
                            });
                        }

                        // 🔥 hapus dari array JS deleted
                        const index = deletedAllData.findIndex(r => r.id == id);
                        if (index !== -1) deletedAllData.splice(index, 1);

                        // 🔥 langsung refresh tabel aktif (tanpa reload halaman)
                        allData = [];
                        currentPage = 0;
                        hasMoreData = true;
                        dataTable.clear().draw();
                        loadMoreData();
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: xhr.responseJSON?.message ??
                                'Gagal merestore Sale Return'
                        });
                    }
                });
            });

            $('#formForceDeleteOrder, #forceDeleteOwnerForm').on('submit', function(e) {
                e.preventDefault();
                const form = this;
                const url = form.action;
                const formData = new FormData(form);

                $.ajax({
                    url: url,
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(res) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: res.message ??
                                'Sale Return berhasil dihapus permanen!',
                            timer: 1800,
                            showConfirmButton: false
                        });

                        $('.modal').modal('hide');
                        form.reset();

                        // 🔥 ambil id dari modal
                        const id = form.dataset.id;
                        const table = $('#deletedSaleReturnTable').DataTable();

                        // 🔥 cari baris sesuai id dan langsung remove DOM-nya
                        const rowNode = table.rows().nodes().to$().filter(function() {
                            const rowData = table.row(this).data();
                            return rowData && rowData.id == id;
                        });

                        if (rowNode.length) {
                            rowNode.fadeOut(300, function() {
                                table.row(rowNode).remove().draw(false);
                            });
                        }

                        // 🔥 hapus dari array JS biar data bersih
                        const index = deletedAllData.findIndex(r => r.id == id);
                        if (index !== -1) deletedAllData.splice(index, 1);
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: xhr.responseJSON?.message ??
                                'Gagal menghapus permanen Sale Return'
                        });
                    }
                });
            });

            console.log('🔥 Script Return to Warehouse loaded!'); // CEK INI MUNCUL GA

            $(document).on('click', '.btn-return-warehouse', function(e) {
                e.preventDefault();
                console.log('🚀 Button clicked!'); // CEK INI MUNCUL GA

                const button = $(this);
                const saleReturnId = button.data('id');
                const url = button.data('url');

                console.log('Sale Return ID:', saleReturnId);
                console.log('URL:', url);

                // Set sale return id
                $('#sale_return_id').val(saleReturnId);
                $('#formReturnToWarehouse').data('sale-return-id', saleReturnId);

                // Load products via AJAX
                loadCanceledProducts(url, saleReturnId);
            });

            function loadCanceledProducts(url, saleReturnId) {
                console.log('📦 Loading products...', url);

                const tbody = $('#productListBody');
                tbody.html(`
            <tr>
                <td colspan="4" class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </td>
            </tr>
        `);

                $.ajax({
                    url: url,
                    type: 'GET',
                    success: function(response) {
                        console.log('✅ Response:', response);

                        tbody.empty();

                        if (!response.data || response.data.length === 0) {
                            tbody.html(`
                        <tr>
                            <td colspan="4" class="text-center text-muted">No canceled products available</td>
                        </tr>
                    `);
                            $('#btnSubmitReturn').prop('disabled', true);
                            return;
                        }

                        $('#btnSubmitReturn').prop('disabled', false);
                        $('#modal-order-number').text(response.order_number || '-');

                        response.data.forEach(function(item) {
                            const maxQty = item.remaining_quantity;

                            const row = `
                        <tr data-id="${item.id}">
                            <td>${item.product_name}</td>
                            <td>${item.sku}</td>
                            <td class="text-center">
                                <span class="badge bg-soft-warning text-warning">${maxQty}</span>
                            </td>
                            <td>
                                <input type="number" 
                                    class="form-control return-qty-input" 
                                    name="products[${item.id}][quantity]"
                                    data-id="${item.id}"
                                    min="0" 
                                    max="${maxQty}" 
                                    value="0"
                                    placeholder="Enter qty">
                                <input type="hidden" name="products[${item.id}][canceled_product_id]" value="${item.id}">
                            </td>
                        </tr>
                    `;
                            tbody.append(row);
                        });

                        updateTotalReturn();
                    },
                    error: function(xhr) {
                        console.error('❌ Error:', xhr);
                        tbody.html(`
                    <tr>
                        <td colspan="4" class="text-center text-danger">
                            Failed to load products: ${xhr.responseJSON?.message || 'Unknown error'}
                        </td>
                    </tr>
                `);
                        $('#btnSubmitReturn').prop('disabled', true);
                    }
                });
            }

            // ========== Toggle Warehouse/Production Selection ==========
            $('#stock_destination').on('change', function() {
                const destination = $(this).val();

                if (destination === 'warehouse') {
                    $('#warehouse_selection').removeClass('d-none');
                    $('#production_selection').addClass('d-none');
                    $('#inventory_warehouse_id').prop('required', true);
                    $('#production_warehouse_id').prop('required', false);
                } else {
                    $('#warehouse_selection').addClass('d-none');
                    $('#production_selection').removeClass('d-none');
                    $('#inventory_warehouse_id').prop('required', false);
                    $('#production_warehouse_id').prop('required', true);
                }
            });

            // Reset modal on close
            $('#modalReturnToWarehouse').on('hidden.bs.modal', function() {
                $('#formReturnToWarehouse')[0].reset();
                $('#productListBody').empty();
                $('#total_return_qty').text('0');
                $('#modal-order-number').text('-');

                // Reset ke warehouse by default
                $('#stock_destination').val('warehouse').trigger('change');
            });

            // Update total when quantity input changed
            $(document).on('input', '.return-qty-input', function() {
                const input = $(this);
                const max = parseInt(input.attr('max')) || 0;
                let val = parseInt(input.val()) || 0;

                if (val > max) {
                    input.val(max);
                }

                if (val < 0) {
                    input.val(0);
                }

                updateTotalReturn();
            });

            function updateTotalReturn() {
                let total = 0;
                $('.return-qty-input').each(function() {
                    total += parseInt($(this).val()) || 0;
                });
                $('#total_return_qty').text(total);
            }

            // Submit form
            $('#formReturnToWarehouse').on('submit', function(e) {
                e.preventDefault();

                const form = $(this);
                const formData = new FormData(this);
                const saleReturnId = form.data('sale-return-id');

                // Validation
                let totalQty = 0;
                $('.return-qty-input').each(function() {
                    totalQty += parseInt($(this).val()) || 0;
                });

                if (totalQty === 0) {
                    Swal.fire('Warning', 'Please enter at least one product quantity to return', 'warning');
                    return;
                }

                Swal.fire({
                    title: 'Processing...',
                    text: 'Returning products to warehouse',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                $.ajax({
                    url: `/erp/sales/sale-returns/process-return-to-warehouse/${saleReturnId}`,
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(response) {
                        Swal.close();

                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message ||
                                'Products returned to warehouse successfully',
                            timer: 2000,
                            showConfirmButton: false
                        });

                        $('#modalReturnToWarehouse').modal('hide');
                        form[0].reset();

                        // Refresh table
                        reloadActiveTab();
                    },
                    error: function(xhr) {
                        Swal.close();

                        Swal.fire({
                            icon: 'error',
                            title: 'Failed!',
                            text: xhr.responseJSON?.message ||
                                'Failed to return products to warehouse'
                        });
                    }
                });
            });

            // Reset modal on close
            $('#modalReturnToWarehouse').on('hidden.bs.modal', function() {
                $('#formReturnToWarehouse')[0].reset();
                $('#productListBody').empty();
                $('#total_return_qty').text('0');
                $('#modal-order-number').text('-');
            });

            // ========= MARK AS RETUR =========
            $(document).on('click', '.btn-mark-retur', function(e) {
                e.preventDefault();

                const button = $(this);
                const saleReturnId = button.data('id');
                const orderNumber = button.data('order-number') || '-';
                const url = button.data('url');

                $('#retur_sale_return_id').val(saleReturnId);
                $('#retur_order_number').text(orderNumber);
                $('#formMarkAsRetur').attr('action', url);

                $('#modalMarkAsRetur').modal('show');
            });

            $('#formMarkAsRetur').on('submit', function(e) {
                e.preventDefault();

                const form = $(this);
                const url = form.attr('action');
                const formData = new FormData(this);

                Swal.fire({
                    title: 'Processing...',
                    text: 'Mengubah payment status menjadi Retur',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(response) {
                        Swal.close();

                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: response.message ??
                                'Payment status berhasil diubah menjadi Retur.',
                            timer: 1200,
                            showConfirmButton: false
                        });

                        $('#modalMarkAsRetur').modal('hide');
                        form[0].reset();

                        reloadActiveTab();
                    },
                    error: function(xhr) {
                        Swal.close();

                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: xhr.responseJSON?.message ??
                                'Gagal mengubah payment status menjadi Retur.'
                        });
                    }
                });
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
                form.dataset.id = id;
                nameHolder.textContent = name;
            });
        });

        // Event handler untuk button Mark As Paid
        $(document).on('click', '.btn-mark-paid', function(e) {
            e.preventDefault();

            const button = $(this);
            const saleReturnId = button.data('id');
            const url = button.data('url');
            const totalAmount = parseFloat(button.data('total-amount')) || 0;
            const paidAmount = parseFloat(button.data('paid-amount')) || 0;
            const remainingAmount = totalAmount - paidAmount;

            // Set form values
            $('#sale_return_id').val(saleReturnId);
            $('#markAsSaleForm').attr('action', url);

            // Set remaining amount display
            $('#total_amount_display').text(new Intl.NumberFormat('id-ID').format(remainingAmount));

            // Set refund amount input
            const formatted = new Intl.NumberFormat('id-ID').format(remainingAmount);
            $('#refund_amount').val(formatted);

            // Set paid amount display
            const paidDisplay = $('#paid_amount_display');
            if (paidDisplay.length) {
                paidDisplay.text('Paid: Rp. ' + formatted);
            }

            // Show modal
            $('#modalChangeStatus').modal('show');
        });

        const refundInput = document.getElementById("refund_amount");

        refundInput.addEventListener("input", function() {
            let angka = this.value.replace(/\D/g, "") || "0";
            this.value = new Intl.NumberFormat("id-ID").format(angka);
        });

        // ========= MARK AS CUSTOMER DEPOSIT =========
        $(document).on('click', '.btn-mark-deposit', function(e) {
            e.preventDefault();

            const button = $(this);
            const saleReturnId = button.data('id');
            const remaining = parseInt(button.data('remaining')) || 0;
            const url = button.data('url');

            // set form
            $('#deposit_sale_return_id').val(saleReturnId);
            $('#markAsCustomerDepositForm').attr('action', url);

            // set balance display
            $('#deposit_balance_display').text(
                new Intl.NumberFormat('id-ID').format(remaining)
            );

            // auto fill deposit amount = sisa
            $('#deposit_amount').val(
                new Intl.NumberFormat('id-ID').format(remaining)
            );

            $('#modalMarkAsCustomerDeposit').modal('show');
        });

        // format number input
        $('#deposit_amount').on('input', function() {
            let angka = this.value.replace(/\D/g, '') || '0';
            this.value = new Intl.NumberFormat('id-ID').format(angka);
        });

        // submit handler
        $('#markAsCustomerDepositForm').on('submit', function(e) {
            e.preventDefault();

            const form = $(this);
            const url = form.attr('action');
            const formData = new FormData(this);

            let depositRaw = $('#deposit_amount').val().replace(/\./g, '');
            let balanceRaw = $('#deposit_balance_display').text().replace(/\D/g, '');

            if (!depositRaw || parseInt(depositRaw) <= 0) {
                Swal.fire('Error', 'Deposit amount harus lebih dari 0', 'error');
                return;
            }

            if (parseInt(depositRaw) > parseInt(balanceRaw)) {
                Swal.fire('Error', 'Deposit amount tidak boleh melebihi Balance', 'error');
                return;
            }

            // normalisasi angka
            $('#deposit_amount').val(depositRaw);

            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function() {
                    Swal.close();

                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: 'Berhasil dijadikan Customer Deposit',
                        timer: 800,
                        showConfirmButton: false
                    });

                    setTimeout(() => {
                        window.location.reload();
                    }, 100);
                },

                error: function(xhr) {
                    Swal.close();
                    Swal.fire(
                        'Gagal!',
                        xhr.responseJSON?.message ?? 'Gagal memproses Customer Deposit',
                        'error'
                    );
                }
            });
        });

        document.querySelector("form").addEventListener("submit", function() {
            refundInput.value = refundInput.value.replace(/\./g, "");
        });

        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('modalForceDeleteOrder');
            const form = document.getElementById('formForceDeleteOrder');
            const nameHolder = document.getElementById('ForceOrderName');

            modal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-id');
                const name = button.getAttribute('data-name');
                const url = button.getAttribute('data-url');

                form.action = url;
                nameHolder.textContent = name;
                form.dataset.id = id;
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('modalRestoreOrder');
            const form = document.getElementById('formRestoreOrder');
            const nameHolder = document.getElementById('RestoreOrderName');

            modal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-id');
                const name = button.getAttribute('data-name');
                const url = button.getAttribute('data-url');

                form.action = url;
                form.dataset.id = id;
                nameHolder.textContent = name;
            });
        });

        $('#modalChangeStatus').on('shown.bs.modal', function() {
            if ($.fn.select2) {
                $('#cash_bank_account_id').trigger('change.select2');
            }
        });

        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.btn-force-delete-owner');
            if (!btn) return;

            const form = document.getElementById('forceDeleteOwnerForm');
            form.action = btn.dataset.url;

            const nameEl = document.getElementById('fd-order-number');
            if (nameEl) nameEl.textContent = btn.dataset.name || '';
        });

        document.getElementById('payment_proof').addEventListener('change', function(event) {
            const file = event.target.files[0];
            const previewWrapper = document.getElementById('proof_preview_wrapper');
            const preview = document.getElementById('proof_preview');

            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    previewWrapper.classList.remove('d-none');
                }
                reader.readAsDataURL(file);
            } else {
                previewWrapper.classList.add('d-none');
                preview.src = '#';
            }
        });

        $('#modalChangeStatus').on('shown.bs.modal', function() {
            $('#cash_bank_account_id').trigger('change.select2');
        });

        document.getElementById('payment_proof').addEventListener('change', function(event) {
            const file = event.target.files[0];
            const previewWrapper = document.getElementById('proof_preview_wrapper');
            const preview = document.getElementById('proof_preview');

            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    previewWrapper.classList.remove('d-none');
                }
                reader.readAsDataURL(file);
            } else {
                previewWrapper.classList.add('d-none');
                preview.src = '#';
            }
        });
    </script>
@endpush
