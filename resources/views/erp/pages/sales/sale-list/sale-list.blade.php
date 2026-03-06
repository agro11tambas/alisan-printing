@extends('erp.layouts.main')

@push('styles')
    <style>
        @media (max-width: 768px) {

            #saleListTable td.desktop-only,
            #saleListTable th.desktop-only,
            #editedSaleListTable td.desktop-only,
            #editedSaleListTable th.desktop-only {
                display: none !important;
            }
        }

        #saleListTable,
        #editedSaleListTable {
            width: 100% !important;
            min-width: 0;
        }

        #saleListTable td.action-cell,
        #editedSaleListTable td.action-cell {
            display: none;
        }

        #saleListTable_wrapper .dataTables_scrollBody,
        #editedSaleListTable_wrapper .dataTables_scrollBody {
            background-image: none !important;
            height: 60vh !important;
            overflow-y: auto !important;
        }

        #deletedSaleListTable_wrapper .dataTables_scrollBody {
            background-image: none !important;
            height: 60vh !important;
            overflow-y: auto !important;
        }

        .dataTables_scrollBody {
            scroll-behavior: smooth;
        }

        #saleListTable tbody tr,
        #editedSaleListTable tbody tr {
            animation: fadeIn 0.3s ease-in;
        }

        table.dataTable td.customer-cell {
            white-space: normal !important;
            word-wrap: break-word !important;
            max-width: 200px;
        }

        .static-action-menu {
            padding: 12px;
            min-width: 850px;
        }

        .action-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
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

        /* Hide desktop table on mobile */
        @media (max-width: 767px) {

            #saleListTable_wrapper,
            #editedSaleListTable_wrapper,
            #deletedSaleListTable_wrapper {
                display: none !important;
            }

            .sale-tabs {
                display: none !important;
            }
        }

        .sale-mobile-card {
            border-radius: 0px;
            padding: 12px 14px;
            margin-bottom: 5px;
            overflow: visible !important;
            position: relative;
            border-bottom: 1px solid #e5e9ef;
        }

        .sale-mobile-card.active {
            background-color: #e5e9ef;
            /* abu-abu halus */
        }

        .sale-mobile-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .sale-date {
            font-size: 12px;
            color: #9ca3af;
        }

        .sale-status {
            font-size: 12px;
            padding: 2px 8px;
            border-radius: 6px;
            font-weight: 500;
        }

        .status-Unpaid {
            background: #374151;
        }

        .status-Paid {
            background: #065f46;
        }

        .status-Overdue {
            background: #7f1d1d;
        }

        .sale-customer {
            font-weight: 600;
            margin-top: 6px;
        }

        .sale-invoice {
            font-size: 12px;

        }

        .sale-amount {
            display: flex;
            justify-content: space-between;
            margin-top: 4px;
            font-size: 13px;
        }

        .sale-amount span {
            color: #9ca3af;
        }

        /* .sale-tabs {
                                                                                                                                                                                                                                                                                                                                                        display: none !important;
                                                                                                                                                                                                                                                                                                                                                    } */

        .sale-mobile-action {
            display: none;
            margin-top: 10px;
        }

        .sale-mobile-card.active .sale-mobile-action {
            display: block;
        }

        .sale-mobile-card.active .dropdown-menu {
            display: block !important;
        }

        .mobile-action-menu {
            position: absolute !important;
            top: 0;
            left: 0;
            width: 100%;
            min-width: unset !important;
            transform: none !important;
        }
    </style>
@endpush

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Sale List</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Sale List</li>
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
                    <a href="/erp/sales/sale-list/create-order" class="btn btn-primary">
                        <i class="feather-plus me-2"></i>
                        <span>Create Sale List</span>
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
    <div class="main-content m-0 m-md-2 m-lg-2 p-0 p-md-0 p-lg-0 pt-2 pt-md-0">
        <div class="row">
            <div class="col-lg-12">
                <div class="card stretch stretch-full">
                    <div class="card-body p-0">
                        <div class="row g-3 p-4 justify-content-between">
                            <div class="col-lg-4">
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
                            <div class="col-lg-4">
                                <label for="search_type" class="fw-semibold fs-12">Filter By</label>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <select id="search_type" class="form-control"
                                            style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                            <option value="customer">Customer</option>
                                            <option value="order_number">Order Number</option>
                                            <option value="payment_status">Payment Status</option>
                                            <option value="due_date">Due Date</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="text" id="search_keyword" name="search_keyword"
                                            class="form-control search-input"
                                            style="padding: 0.5rem 1rem; font-size: 0.875rem;" placeholder="Search..." />
                                        <select id="search_payment_status" class="form-control search-input d-none"
                                            style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                            {{-- <option value="">All</option> --}}
                                            <option value="Paid">Paid</option>
                                            <option value="Unpaid">Unpaid</option>
                                            {{-- <option value="Partially Paid">Partially Paid</option>
                                            <option value="Overdue">Overdue</option> --}}
                                        </select>
                                        <select id="due_date_order" class="form-control d-none"
                                            style="padding: 0.5rem 1rem;">
                                            <option value="asc">Ascending</option>
                                            <option value="desc">Descending</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <ul class="nav nav-tabs mb-3 sale-tabs" id="saleListTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="sale-list-tab" data-bs-toggle="tab" href="#sale-list"
                                    role="tab">Sale List</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="deleted-sale-list-tab" data-bs-toggle="tab"
                                    href="#deleted-sale-list" role="tab">Deleted Sale List</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="edited-sale-list-tab" data-bs-toggle="tab"
                                    href="#edited-sale-list" role="tab">Edited Sale List</a>
                            </li>
                        </ul>
                        <div class="table-responsive ">
                            <div class="tab-content">
                                <div class="tab-pane fade show active" id="sale-list" role="tabpanel">
                                    <table class=" table table-hover bg-transparent" id="saleListTable">
                                        <thead>
                                            <tr>
                                                <th style="width:3%;"></th>
                                                <th style="width:12%;">Invoice Number</th>
                                                <th style="width:18%;">Customer</th>
                                                <th style="width:10%;">Grand Total</th>
                                                <th style="width:10%;">Paid Amount</th>
                                                <th style="width:12%;">Payment</th>
                                                <th style="width:8%;">User</th>
                                                <th style="width:6%;">Type</th>
                                                <th style="width:15%;">Note</th>
                                                <th style="width:6%;">Chat</th>
                                            </tr>
                                        </thead>
                                    </table>
                                    <div id="loadingIndicator" style="display:none;">
                                        <div class="shimmer-wrapper">
                                            <div class="shimmer"></div>
                                            <div class="shimmer"></div>
                                            <div class="shimmer"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane fade show" id="edited-sale-list" role="tabpanel">
                                    <table class="table table-hover bg-transparent" id="editedSaleListTable">
                                        <thead>
                                            <tr>
                                                <th></th>
                                                <th>Invoice Number</th>
                                                <th>Customer</th>
                                                <th>Grand Total</th>
                                                <th>Paid Amount</th>
                                                <th>Payment</th>
                                                <th>User</th>
                                                <th>Type</th>
                                                <th>Note</th>
                                            </tr>
                                        </thead>
                                    </table>

                                    <div id="loadingIndicatorEdited" style="display:none;">
                                        <div class="shimmer-wrapper">
                                            <div class="shimmer"></div>
                                            <div class="shimmer"></div>
                                            <div class="shimmer"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane fade show" id="deleted-sale-list" role="tabpanel">
                                    <table class="table table-hover bg-transparent" id="deletedSaleListTable">
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
                                    <div id="loadingIndicator" style="display:none;">
                                        <div class="shimmer-wrapper">
                                            <div class="shimmer"></div>
                                            <div class="shimmer"></div>
                                            <div class="shimmer"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        {{-- MOBILE SALE LIST --}}
                        <div id="saleListMobile" class="d-md-none px-0 pb-4">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <iframe id="invoiceIframe" style="display:none;"></iframe>
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
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p style="font-size: 16px;">1. Apakah anda yakin ingin menghapus data ?</p>
                        <p style="font-size: 16px;">2. Jika ada kesalahan saat audit. Anda siap untuk Bertanggung Jawab ?
                            Kolom Keterangan (Wajib diisi)</p>
                        <p style="font-size: 16px;">3. Anda akan menghapus order dari customer:</p>
                        <ul style="font-size: 15px;">
                            <li><strong>Nama Customer:</strong> <span id="delete_customer_name">-</span></li>
                            <li><strong>Business:</strong> <span id="delete_business_name">-</span></li>
                        </ul>
                        <div class="form-group mt-3">
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
                        <span class="fs-18 fw-bold mb-1">Mark As Paid</span>
                    </h2>
                    <a href="javascript:void(0)" class="avatar-text avatar-md bg-soft-danger close-icon"
                        data-bs-dismiss="modal">
                        <i class="feather-x text-danger"></i>
                    </a>
                </div>
                <form method="POST" id="markAsSaleForm" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="order_id" name="order_id">
                    <div class="modal-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="transaction_type" class="fw-semibold">Account:</label>
                                <div class="input-group">
                                    <select class="form-select form-control max-select" data-select2-selector="tag"
                                        id="transaction_type" name="transaction_type">
                                        <option value="11" data-bg="bg-danger">Sale Account</option>
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
                        <div class="row g-3 mb-3">
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
                                            Pilih Bank atau Cash Account</option>

                                        @foreach ($cashAccounts as $cash)
                                            @php
                                                $bg = $bgColors[$loop->index % count($bgColors)];
                                            @endphp
                                            <option value="{{ $cash->id }}" data-bg="{{ $bg }}"
                                                {{ isset($defaultAccount) && $defaultAccount->id == $cash->id ? 'selected' : '' }}>
                                                Cash - {{ $cash->type }}
                                            </option>
                                        @endforeach

                                        @foreach ($bankAccounts as $bank)
                                            @php
                                                $bg = $bgColors[$loop->index % count($bgColors)];
                                            @endphp
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
                                <label for="paid_amount" class="fw-semibold">Paid Amount:</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="paid_amount" name="paid_amount"
                                        value="0" required>
                                </div>
                                <small class="text-danger d-none" id="error_paid_amount"></small>
                                <span class="fw-semibold fs-12" id="paid_amount_display">Paid: Rp. 0</span>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="use_write_off"
                                        name="use_write_off">
                                    <label class="form-check-label fw-semibold" for="use_write_off">
                                        Use Customer Deposit
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- 🔥 FIELD CUSTOMER DEPOSIT (hidden by default) -->
                        <div class="row g-3 mb-3 d-none" id="write_off_container">
                            <div class="col-md-12">
                                <label for="customer_deposit_amount" class="fw-semibold">Customer Deposit Amount:</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="customer_deposit_amount"
                                        name="customer_deposit_amount" value="0">
                                </div>
                                <small class="text-muted">Max: <span id="max_deposit_display">Rp. 0</span> (Balance
                                    limit)</small><br>
                                <small class="text-info">Customer Total Deposit: <span id="customer_deposit_display">Rp.
                                        0</span></small>
                                <small class="text-danger d-none" id="error_customer_deposit_amount"></small>
                            </div>
                        </div>

                        <!-- 🔥 CHECKBOX WRITE OFF -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="use_write_off_only"
                                        name="use_write_off_only">
                                    <label class="form-check-label fw-semibold" for="use_write_off_only">
                                        Write Off (hapus sisa piutang)
                                    </label>
                                </div>
                                <small class="text-muted">Jika dicentang, sisa balance setelah pembayaran akan dianggap
                                    sebagai write-off.</small>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <label class="fw-semibold">Upload / Paste Proof (optional):</label>

                            <div id="pasteProofArea" class="border rounded p-3 text-center"
                                style="min-height: 120px; cursor: pointer;">
                                <p class="text-muted small mb-2">
                                    Klik di sini lalu tekan <strong>Ctrl + V</strong> untuk paste screenshot bukti transfer
                                </p>

                                <!-- 🔹 ubah layout preview -->
                                <div id="proofPreviewContainer" class="preview-list"></div>
                            </div>

                            {{-- <input type="file" class="form-control mt-2" id="payment_proof" name="payment_proof[]"
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
                        <button type="submit" class="btn btn-primary">Mark As Paid</button>
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

    <div class="modal fade-scale" id="modalReturnMoney" tabindex="-1" aria-labelledby="modalReturnMoneyLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="d-flex flex-column mb-0">
                        <span class="fs-18 fw-bold mb-1">Return Money</span>
                    </h2>
                    <a href="javascript:void(0)" class="avatar-text avatar-md bg-soft-danger close-icon"
                        data-bs-dismiss="modal">
                        <i class="feather-x text-danger"></i>
                    </a>
                </div>
                <form method="POST" id="formReturnMoney">
                    @csrf
                    <input type="hidden" id="return_order_id" name="order_id">
                    <div class="modal-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-12">
                                <label for="return_transaction_date" class="fw-semibold">Transaction Date:</label>
                                <div class="input-group">
                                    <input type="date" id="return_transaction_date" name="transaction_date"
                                        class="form-control" value="{{ date('Y-m-d') }}">
                                </div>
                                <small class="text-danger d-none" id="error_return_transaction_date"></small>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="return_cash_bank_account_id" class="fw-semibold">Cash/Bank Account:</label>
                                <div class="input-group">
                                    <select class="form-select form-control max-select" data-select2-selector="tag"
                                        name="cash_bank_account_id" id="return_cash_bank_account_id">
                                        <option value="" disabled selected hidden>Pilih Bank atau Cash Account
                                        </option>
                                        @php
                                            $bgColors = [
                                                'bg-danger',
                                                'bg-warning',
                                                'bg-primary',
                                                'bg-indigo',
                                                'bg-success',
                                            ];
                                        @endphp
                                        @foreach ($cashAccounts as $cash)
                                            @php $bg = $bgColors[$loop->index % count($bgColors)]; @endphp
                                            <option value="{{ $cash->id }}" data-bg="{{ $bg }}">Cash -
                                                {{ $cash->type }}</option>
                                        @endforeach
                                        @foreach ($bankAccounts as $bank)
                                            @php $bg = $bgColors[$loop->index % count($bgColors)]; @endphp
                                            <option value="{{ $bank->id }}" data-bg="{{ $bg }}">Bank -
                                                {{ $bank->type }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <small class="text-danger d-none" id="error_return_cash_bank_account_id"></small>
                            </div>
                            <div class="col-md-6">
                                <label for="return_amount" class="fw-semibold">Return Amount:</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="return_amount" name="return_amount"
                                        value="0" required>
                                </div>
                                <small class="text-danger d-none" id="error_return_amount"></small>
                                <span class="fw-semibold fs-12">Overpaid: <span id="overpaid_display">Rp. 0</span></span>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-12">
                                <label for="return_note" class="fw-semibold">Note:</label>
                                <textarea id="return_note" name="note" class="form-control" rows="2" placeholder="Optional..."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer d-flex justify-content-between">
                        <div>
                            <div class="col-md-6">
                                <p class="m-0">Balance:</p>
                                <h5 class="fw-semibold text-danger" id="return_balance_display">0</h5>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Process Return</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalForceDeleteOwner" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" id="forceDeleteOwnerForm">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Force Delete Order (Owner)</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-2">Anda akan menghapus <strong id="fd-order-number"></strong> secara permanen
                            beserta rollback stok produksi.</p>
                        <ul>
                            <li><strong>Nama Customer:</strong> <span id="fd-customer-name">-</span></li>
                            <li><strong>Business:</strong> <span id="fd-business-name">-</span></li>
                        </ul>
                        <div class="mb-3">
                            <label class="form-label">Delete Notes <span class="text-danger">*</span></label>
                            <textarea name="delete_notes" class="form-control" rows="3" required placeholder="Alasan penghapusan..."></textarea>
                        </div>
                        {{-- opsional: pilih gudang --}}
                        <input type="hidden" name="inventory_warehouse_id" value="1">
                        <input type="hidden" name="production_warehouse_id" value="2">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">Force Delete</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

@endpush

@push('scripts')
    <script>
        $(document).ready(function() {
            // Helper function untuk format products
            function formatProducts(products) {
                if (!products || products.length === 0) {
                    return '<div class="p-2 text-muted">No products</div>';
                }

                let html = `
                    <div class="table-responsive p-2">
                        <table class="table bg-transparent table-sm table-bordered mb-0 w-auto">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>SKU</th>
                                    <th>Qty</th>
                                    <th class="text-end">Price</th>
                                    <th class="text-end">Progress</th>
                                    <th class="text-end">Delivered</th>
                                    <th class="text-end">On Delivery</th>
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
                            <td class="text-end">${p.ready_qty} / ${p.qty}</td>
                            <td class="text-end">${p.delivered}</td>
                            <td class="text-end">${p.on_delivery}</td>
                        </tr>
                    `;
                });

                html += `
                            </tbody>
                        </table>
                    </div>
                `;
                return html;
            }

            function reloadActiveTab() {
                const activeTab = $('#saleListTabs .nav-link.active').attr('href');

                if (activeTab === '#deleted-sale-list') {
                    resetAndReloadDeleted();
                } else if (activeTab === '#edited-sale-list') {
                    resetAndReloadEdited();
                } else {
                    resetAndReload();
                }
            }

            // ========== SALE LIST TABLE (dengan Lazy Load) ==========
            let allData = [];
            let currentPage = 0;
            let isLoading = false;
            let hasMoreData = true;
            let lastKeyword = ''; // 🔹 simpan keyword terakhir
            let lastFilterState = ''; // 🔹 simpan kombinasi filter terakhir

            const dataTable = $('#saleListTable').DataTable({
                processing: false,
                serverSide: false,
                scrollY: '60vh',
                scrollCollapse: true,
                paging: false,
                searching: false,
                info: false,
                lengthChange: false,
                order: [
                    [10, 'desc']
                ],
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
                        data: 'grand_total'
                    },
                    {
                        data: 'paid_amount'
                    },
                    // {
                    //     data: 'remaining_amount'
                    // },
                    {
                        data: 'payment_status'
                    },
                    {
                        data: 'user'
                    },
                    {
                        data: 'mode'
                    },
                    {
                        data: 'notes'
                    },
                    {
                        data: 'whatsapp'
                    },
                    {
                        data: 'order_date_raw', // tambahkan kolom ini
                        visible: false, // disembunyikan dari tampilan
                        searchable: false // tidak perlu di-search
                    }
                ]
            });

            let searchTimer = null;
            let currentRequest = null;

            function loadMoreData(reset = false) {
                if (isLoading) return;
                if (!hasMoreData && !reset) return;
                isLoading = true;

                if (reset) {
                    allData = [];
                    currentPage = 0;
                    hasMoreData = true;
                    dataTable.clear().draw();
                }

                // 🚫 Batalkan request sebelumnya jika masih berjalan
                if (currentRequest) {
                    currentRequest.abort();
                }

                currentRequest = $.ajax({
                    url: "{{ url('/erp/sales/sale-list/data') }}",
                    type: 'GET',
                    data: {
                        start: currentPage * 50,
                        length: 50,
                        filter: $('#filter').val(),
                        start_date: $('#start_date').val(),
                        end_date: $('#end_date').val(),
                        search_type: $('#search_type').val(),
                        search_keyword: $('#search_keyword').val(),
                        payment_status: $('#search_payment_status').val(),
                        due_date_order: $('#due_date_order').val()
                    },
                    success: function(response) {
                        if (response && response.data && response.data.length > 0) {
                            if (reset) allData = []; // hapus data lama kalau reset
                            allData = allData.concat(response.data);
                            dataTable.clear();
                            dataTable.rows.add(allData);
                            dataTable.draw(false);

                            renderMobileFromAllData();
                            currentPage++;
                            hasMoreData = true;
                        } else {
                            if (reset) {
                                currentPage = 0;
                                hasMoreData = true;
                            } else {
                                hasMoreData = false;
                            }
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

            // Load data pertama kali
            loadMoreData();

            // Lazy load saat scroll
            let scrollTimeout = null;
            $('.dataTables_scrollBody').on('scroll', function() {
                if (window.innerWidth < 768) return;
                clearTimeout(scrollTimeout);

                const scrollTop = $(this).scrollTop();
                const scrollHeight = $(this)[0].scrollHeight;
                const clientHeight = $(this).height();

                scrollTimeout = setTimeout(() => {
                    if (scrollTop + clientHeight >= scrollHeight * 0.85) {
                        loadMoreData();
                    }
                }, 200);
            });

            // Reset dan reload saat filter berubah
            function resetAndReload() {
                allData = [];
                currentPage = 0;
                hasMoreData = true;
                mobilePage = 0;
                dataTable.clear().draw();
                $('#saleListMobile').html('');
                loadMoreData();
            }

            // Event handlers untuk filter SALE LIST
            $('#filter').on('change', function() {
                if ($(this).val() === 'custom') {
                    $('.custom-range').removeClass('d-none');
                } else {
                    $('.custom-range').addClass('d-none');
                    reloadActiveTab();
                }
            });

            $('#apply-filter').on('click', function() {
                reloadActiveTab();
            });

            $('#search_type').on('change', function() {
                const selected = $(this).val();

                if (selected === 'payment_status') {
                    $('#search_keyword').addClass('d-none').val('');
                    $('#search_payment_status').removeClass('d-none');
                    $('#due_date_order').addClass('d-none');
                } else if (selected === 'due_date') {
                    $('#search_keyword').addClass('d-none').val('');
                    $('#search_payment_status').addClass('d-none').val('');
                    $('#due_date_order').removeClass('d-none');
                } else {
                    $('#search_keyword').removeClass('d-none');
                    $('#search_payment_status').addClass('d-none').val('');
                    $('#due_date_order').addClass('d-none');
                }
            });

            $('#due_date_order').on('change', function() {
                if ($('#search_type').val() === 'due_date') {
                    reloadActiveTab();
                }
            });

            function detectFilterChange() {
                return (
                    $('#search_type').val() + '|' +
                    $('#search_keyword').val() + '|' +
                    $('#search_payment_status').val() + '|' +
                    $('#filter').val() + '|' +
                    $('#start_date').val() + '|' +
                    $('#end_date').val() + '|' +
                    $('#due_date_order').val()
                );
            }

            $('#search_payment_status, #due_date_order, #filter, #start_date, #end_date')
                .on('keyup change', function(e) {
                    clearTimeout(searchTimer);

                    searchTimer = setTimeout(() => {
                        const currentState = detectFilterChange();
                        if (currentState !== lastFilterState) {
                            lastFilterState = currentState;
                            reloadActiveTab();
                        }
                    }, 200);
                });

            $(document).on('keyup', '#search_keyword', function(e) {
                if (e.key === 'Enter') {
                    reloadActiveTab();
                }
            });

            $('#search_keyword').on('input', function() {
                if ($(this).val().trim() === '') {
                    reloadActiveTab();
                }
            });

            $('#search_payment_status').on('change', function() {
                if ($('#search_type').val() === 'payment_status') {
                    reloadActiveTab();
                }
            });

            // Expand/collapse products detail
            $('#saleListTable tbody').on('click', 'td.dt-control', function() {
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
            $('#saleListTable tbody').on('click', 'tr', function(e) {
                if ($(e.target).closest('td.dt-control').length) return;

                let $tr = $(this);
                let row = dataTable.row($tr);

                $('#saleListTable tbody tr').removeClass('action-shown').next('.action-row').remove();

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
                // Cek apakah klik di dalam salah satu tabel
                if ($(e.target).closest('#saleListTable, #editedSaleListTable, #deletedSaleListTable')
                    .length) return;

                // Tutup semua action row di semua tabel
                $('#saleListTable tbody tr').removeClass('action-shown').next('.action-row').remove();
                $('#editedSaleListTable tbody tr').removeClass('action-shown').next('.action-row').remove();
            });

            // ========== EDITED SALE LIST TABLE (100% SAMA dengan SALE LIST) ==========
            let editedData = [];
            let editedPage = 0;
            let editedIsLoading = false;
            let editedHasMoreData = true;
            let editedTable = null;
            let editedInitialized = false;
            let editedCurrentRequest = null;
            let editedScrollTimeout = null;

            function initEditedTable() {
                if (editedInitialized) return;

                editedTable = $('#editedSaleListTable').DataTable({
                    processing: false,
                    serverSide: false,
                    scrollY: '60vh',
                    scrollCollapse: true,
                    paging: false,
                    searching: false,
                    info: false,
                    lengthChange: false,
                    order: [
                        [9, 'desc']
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
                            data: 'grand_total'
                        },
                        {
                            data: 'paid_amount'
                        },
                        {
                            data: 'payment_status'
                        },
                        {
                            data: 'user'
                        },
                        {
                            data: 'mode'
                        },
                        {
                            data: 'notes'
                        },
                        {
                            data: 'order_date_raw',
                            visible: false,
                            searchable: false
                        }
                    ]
                });

                editedInitialized = true;

                // 🔥 Lazy load scroll untuk EDITED TABLE
                $('#editedSaleListTable').parents('.dataTables_scrollBody').on('scroll', function() {
                    clearTimeout(editedScrollTimeout);

                    const scrollTop = $(this).scrollTop();
                    const scrollHeight = $(this)[0].scrollHeight;
                    const clientHeight = $(this).height();

                    editedScrollTimeout = setTimeout(() => {
                        if (scrollTop + clientHeight >= scrollHeight * 0.85) {
                            loadMoreEdited();
                        }
                    }, 200);
                });

                // 🔥 Expand/collapse products - EDITED
                $('#editedSaleListTable tbody').on('click', 'td.dt-control', function() {
                    let tr = $(this).closest('tr');
                    let row = editedTable.row(tr);
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

                // 🔥 Action button dropdown - EDITED (SAMA PERSIS dengan sale list)
                $('#editedSaleListTable tbody').on('click', 'tr', function(e) {
                    if ($(e.target).closest('td.dt-control').length) return;

                    let $tr = $(this);
                    let row = editedTable.row($tr);

                    $('#editedSaleListTable tbody tr').removeClass('action-shown').next('.action-row')
                        .remove();

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
            }

            function loadMoreEdited(reset = false) {
                if (editedIsLoading) return;
                if (!editedHasMoreData && !reset) return;

                editedIsLoading = true;

                if (reset) {
                    editedData = [];
                    editedPage = 0;
                    editedHasMoreData = true;
                    editedTable.clear().draw();
                }

                if (editedCurrentRequest) {
                    editedCurrentRequest.abort();
                }

                editedCurrentRequest = $.ajax({
                    url: "{{ url('/erp/sales/sale-list/data') }}", // 🔥 ENDPOINT SAMA!
                    type: 'GET',
                    data: {
                        start: editedPage * 50,
                        length: 50,
                        show_edited: 1, // 🔥 INI PARAMETER PEMBEDA
                        filter: $('#filter').val(),
                        start_date: $('#start_date').val(),
                        end_date: $('#end_date').val(),
                        search_type: $('#search_type').val(),
                        search_keyword: $('#search_keyword').val(),
                        payment_status: $('#search_payment_status').val(),
                        due_date_order: $('#due_date_order').val()
                    },
                    success: function(response) {
                        if (response && response.data && response.data.length > 0) {
                            if (reset) editedData = [];
                            editedData = editedData.concat(response.data);
                            editedTable.clear();
                            editedTable.rows.add(editedData);
                            editedTable.draw(false);
                            editedPage++;
                            editedHasMoreData = true;
                        } else {
                            if (reset) {
                                editedPage = 0;
                                editedHasMoreData = true;
                            } else {
                                editedHasMoreData = false;
                            }
                        }
                    },
                    complete: function() {
                        editedIsLoading = false;
                        editedCurrentRequest = null;
                    },
                    error: function(xhr) {
                        if (xhr.statusText !== "abort") {
                            console.error("AJAX error:", xhr);
                        }
                        editedIsLoading = false;
                    }
                });
            }

            function resetAndReloadEdited() {
                editedData = [];
                editedPage = 0;
                editedHasMoreData = true;
                if (editedTable) {
                    editedTable.clear().draw();
                }
                loadMoreEdited();
            }

            // ========== DELETED SALE LIST TABLE (dengan Lazy Load CSR) ==========
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
                        data: 'customer',
                        className: 'customer-cell'
                    },
                    {
                        data: 'grand_total'
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

                deletedTable = $('#deletedSaleListTable').DataTable({
                    processing: false,
                    serverSide: false,
                    scrollY: '60vh',
                    scrollCollapse: true,
                    paging: false,
                    searching: false,
                    info: false,
                    lengthChange: false,
                    order: [
                        [4, 'asc']
                    ],
                    data: [],
                    columns: deletedColumns
                });

                deletedTableInitialized = true;

                let deletedScrollTimeout = null; // 🔥 BIKIN VARIABLE BARU!
                $('#deletedSaleListTable').parents('.dataTables_scrollBody').on('scroll', function() {
                    clearTimeout(deletedScrollTimeout); // 🔥 PAKAI INI!

                    const scrollTop = $(this).scrollTop();
                    const scrollHeight = $(this)[0].scrollHeight;
                    const clientHeight = $(this).height();

                    deletedScrollTimeout = setTimeout(() => { // 🔥 PAKAI INI!
                        if (scrollTop + clientHeight >= scrollHeight * 0.85) {
                            loadMoreDeletedData();
                        }
                    }, 200);
                });

                // Expand products di deleted table
                $('#deletedSaleListTable tbody').on('click', 'td.dt-control', function() {
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
                    url: "{{ url('/erp/sales/sale-list/data-deleted') }}",
                    type: 'GET',
                    data: {
                        start: deletedCurrentPage * 50,
                        length: 50,
                        filter: $('#filter').val(),
                        start_date: $('#start_date').val(),
                        end_date: $('#end_date').val(),
                        search_type: $('#search_type').val(),
                        search_keyword: $('#search_keyword').val(),
                        payment_status: $('#search_payment_status').val(),
                        due_date_order: $('#due_date_order').val()
                    },
                    success: function(response) {

                        if (response && response.data && response.data.length > 0) {
                            deletedAllData = deletedAllData.concat(response.data);
                            deletedTable.clear();
                            deletedTable.rows.add(deletedAllData);
                            deletedTable.draw(false);
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

                if (target === '#sale-list') {
                    resetAndReload(); // 🔥 INI YANG PERLU DITAMBAHKAN
                }

                if (target === '#edited-sale-list') {
                    if (!editedInitialized) initEditedTable();
                    resetAndReloadEdited();
                }

                if (target === '#deleted-sale-list') {
                    if (!deletedTableInitialized) initDeletedTable();
                    resetAndReloadDeleted();
                }
            });

            let pastedProofBlobs = [];

            const previewContainer = document.getElementById('proofPreviewContainer');

            document.addEventListener('paste', function(e) {
                const items = e.clipboardData.items;
                let hasImage = false;

                // 🔥 Cek apakah ada gambar di clipboard
                for (const item of items) {
                    if (item.type.indexOf("image") === 0) {
                        hasImage = true;
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
                            noteInput.classList.add('form-control', 'form-control-sm', 'note-input');
                            noteInput.placeholder = 'Tambahkan catatan...';
                            noteInput.style.width = '100%';

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

                // 🧠 kalau di input note => jangan block paste textnya
                if (e.target.classList.contains('note-input')) {
                    return;
                }

                // Kalau ada gambar dan bukan paste di input note => block default agar text sampah tidak ikut
                if (hasImage) {
                    e.preventDefault();
                }
            });

            document.getElementById('markAsSaleForm').addEventListener('submit', function(e) {
                e.preventDefault();

                const form = this;
                const url = form.getAttribute('action');
                const formData = new FormData(form);

                // 🔥 FIX: Convert checkbox ke boolean yang benar
                if ($('#use_write_off_only').is(':checked')) {
                    formData.set('use_write_off_only', '1'); // atau 'true'
                } else {
                    formData.delete('use_write_off_only'); // hapus dari FormData kalau tidak dicentang
                }

                // Hapus error sebelumnya
                document.querySelectorAll('#markAsSaleForm small.text-danger').forEach(el => {
                    el.classList.add('d-none');
                    el.innerText = '';
                });

                // Validasi manual
                let valid = true;
                const transactionType = form.transaction_type.value.trim();
                const transactionDate = form.transaction_date.value.trim();
                const cashBankAccount = form.cash_bank_account_id.value.trim();
                const paidAmountRaw = form.paid_amount.value.trim();
                const paidAmount = paidAmountRaw.replace(/\./g, "");

                if (!transactionType) {
                    showError('error_transaction_type', 'Account wajib dipilih');
                    valid = false;
                }
                if (!transactionDate) {
                    showError('error_transaction_date', 'Tanggal transaksi wajib diisi');
                    valid = false;
                }
                if (!cashBankAccount) {
                    showError('error_cash_bank_account_id', 'Pilih cash atau bank account');
                    valid = false;
                }
                // if (!paidAmount || isNaN(paidAmount) || parseInt(paidAmount) <= 0) {
                //     showError('error_paid_amount', 'Paid amount harus diisi dan lebih dari 0');
                //     valid = false;
                // }

                if (!valid) return;

                formData.set('paid_amount', paidAmount);

                const useWriteOff = $('#use_write_off').is(':checked');
                let depositUsed = 0;

                if (useWriteOff) {
                    const depositRaw = $('#customer_deposit_amount').val().trim();
                    depositUsed = parseInt(depositRaw.replace(/\./g, "")) || 0;

                    const maxDeposit = Math.min(currentBalance, customerTotalDeposit);

                    if (depositUsed > maxDeposit) {
                        showError('error_customer_deposit_amount',
                            `Deposit tidak boleh lebih dari Rp. ${new Intl.NumberFormat('id-ID').format(maxDeposit)}`
                        );
                        valid = false;
                    }
                }

                // 🔥 Cek validasi dulu sebelum lanjut
                if (!valid) return;

                // 🔥 Baru append kalau valid
                if (useWriteOff && depositUsed > 0) {
                    // formData.append('customer_deposit_used', depositUsed);
                    formData.append('deposit_used', depositUsed);
                }

                const notes = [];
                $('#proofPreviewContainer .note-input').each(function() {
                    notes.push($(this).val());
                });

                // Tambahkan hasil paste screenshot dan note ke FormData
                pastedProofBlobs.forEach((blob, index) => {
                    formData.append('payment_proof[]', blob, `proof_${index + 1}.png`);
                    formData.append('note_per_image[]', notes[index] || '');
                });

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
                            text: res.message ?? 'Pembayaran berhasil disimpan!',
                            timer: 1800,
                            showConfirmButton: false
                        });

                        $('#modalChangeStatus').modal('hide');
                        $('#markAsSaleForm')[0].reset();

                        pastedProofBlobs = [];
                        if (previewContainer) previewContainer.innerHTML = '';

                        // 🔥 update manual tampilan row di tabel TANPA reload & TANPA rubah HTML badge backend
                        const orderId = $('#order_id').val();

                        // ambil row target
                        const rowNode = dataTable.rows().nodes().to$().filter(function() {
                            const rowData = dataTable.row(this).data();
                            return rowData && String(rowData.id) === String(orderId);
                        });

                        if (rowNode.length) {
                            const row = dataTable.row(rowNode);
                            const d = row.data();

                            d.paid_amount = res.order.paid_amount;
                            d.payment_status = res.order.payment_status;
                            d.action = res.order.action;

                            row.data(d).invalidate(); // cukup ini saja

                            rowNode.addClass('bg-success-subtle');
                            setTimeout(() => rowNode.removeClass('bg-success-subtle'), 1500);
                        }


                    },
                    error: function(xhr) {
                        Swal.close();
                        let msg = xhr.responseJSON?.message ??
                            'Terjadi kesalahan saat menyimpan';
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: msg,
                        });
                    }
                });

                function showError(id, msg) {
                    const el = document.getElementById(id);
                    el.innerText = msg;
                    el.classList.remove('d-none');
                }
            });

            // ========== DELETE (SOFT DELETE) ==========
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
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: res.message ?? 'Order berhasil dihapus!',
                            timer: 1800,
                            showConfirmButton: false
                        });

                        $('#modalDeleteOrder').modal('hide');
                        form.reset();

                        // 🔥 ambil ID dari form
                        const id = form.dataset.id;
                        const table = $('#saleListTable').DataTable();

                        // 🔥 cari row di tabel dan hapus tanpa reload
                        const rowNode = table.rows().nodes().to$().filter(function() {
                            const rowData = table.row(this).data();
                            return rowData && rowData.id == id;
                        });

                        if (rowNode.length) {
                            rowNode.fadeOut(300, function() {
                                table.row(rowNode).remove().draw(false);
                            });
                        }

                        // 🔥 hapus dari array JS
                        const index = allData.findIndex(r => r.id == id);
                        if (index !== -1) allData.splice(index, 1);
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: xhr.responseJSON?.message ?? 'Gagal menghapus order',
                        });
                    }
                });
            });

            // ========== RESTORE ORDER ==========
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
                            text: res.message ?? 'Order berhasil direstore!',
                            timer: 1800,
                            showConfirmButton: false
                        });

                        $('#modalRestoreOrder').modal('hide');
                        form.reset();

                        const id = form.dataset.id;
                        const table = $('#deletedSaleListTable').DataTable();

                        // 🔥 hapus dari deleted table (langsung)
                        const rowNode = table.rows().nodes().to$().filter(function() {
                            const rowData = table.row(this).data();
                            return rowData && rowData.id == id;
                        });

                        if (rowNode.length) {
                            rowNode.fadeOut(300, function() {
                                table.row(rowNode).remove().draw(false);
                            });
                        }

                        // 🔥 hapus juga dari array JS deleted
                        const index = deletedAllData.findIndex(r => r.id == id);
                        if (index !== -1) deletedAllData.splice(index, 1);

                        // 🔁 langsung refresh tabel aktif (tanpa reload)
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
                            text: xhr.responseJSON?.message ?? 'Gagal merestore order',
                        });
                    }
                });
            });

            // ========== FORCE DELETE (OWNER) ==========
            $('#formForceDeleteOrder').on('submit', function(e) {
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
                            text: res.message ?? 'Order berhasil dihapus permanen!',
                            timer: 1800,
                            showConfirmButton: false
                        });

                        $('.modal').modal('hide');
                        form.reset();

                        // 🔥 ambil id dari form
                        const id = form.dataset.id;
                        const table = $('#deletedSaleListTable').DataTable();

                        // 🔥 hapus langsung dari tabel
                        const rowNode = table.rows().nodes().to$().filter(function() {
                            const rowData = table.row(this).data();
                            return rowData && rowData.id == id;
                        });

                        if (rowNode.length) {
                            rowNode.fadeOut(300, function() {
                                table.row(rowNode).remove().draw(false);
                            });
                        }

                        // 🔥 hapus dari array deleted
                        const index = deletedAllData.findIndex(r => r.id == id);
                        if (index !== -1) deletedAllData.splice(index, 1);
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: xhr.responseJSON?.message ??
                                'Gagal menghapus permanen order',
                        });
                    }
                });
            });

            $('#forceDeleteOwnerForm').on('submit', function(e) {
                e.preventDefault();
                const form = this;
                const url = form.action;
                const formData = new FormData(form);

                $.ajax({
                    url: url,
                    method: 'POST', // sama kayak formDeleteOrder
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(res) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: res.message ?? 'Order berhasil dihapus permanen!',
                            timer: 1800,
                            showConfirmButton: false
                        });

                        $('#modalForceDeleteOwner').modal('hide');
                        form.reset();

                        // 🔥 ambil id
                        const id = form.dataset.id;

                        // 🔥 hapus row langsung dari SALE LIST TABLE (karena tombolnya dari situ)
                        const table = $('#saleListTable').DataTable();
                        const rowNode = table.rows().nodes().to$().filter(function() {
                            const rowData = table.row(this).data();
                            return rowData && (rowData.id == id || rowData.order_id ==
                                id);
                        });

                        if (rowNode.length) {
                            rowNode.fadeOut(300, function() {
                                table.row(rowNode).remove().draw(false);
                            });
                        }

                        // 🔥 hapus juga dari array JS
                        const index = allData.findIndex(r => String(r.id) === String(id) ||
                            String(r.order_id) === String(id));
                        if (index !== -1) allData.splice(index, 1);

                        // 🔥 optional: refresh Deleted Table biar ikut update
                        if ($.fn.DataTable.isDataTable('#deletedSaleListTable')) {
                            resetAndReloadDeleted();
                        }
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: xhr.responseJSON?.message ??
                                'Gagal menghapus permanen order',
                        });
                    }

                });
            });

            // 🔥 GLOBAL VARIABLES untuk Write Off
            let originalPaidAmount = 0;
            let customerTotalDeposit = 0;
            let currentBalance = 0;

            // 🔥 Handler checkbox Write Off
            $('#use_write_off').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#write_off_container').removeClass('d-none');

                    // Set max deposit yang bisa dipakai = min(balance, customer_deposit)
                    const maxDeposit = Math.min(currentBalance, customerTotalDeposit);

                    // Auto-isi dengan max
                    const formatted = new Intl.NumberFormat('id-ID').format(maxDeposit);
                    $('#customer_deposit_amount').val(formatted);

                    // Update display
                    updatePaidAmountWithDeposit();
                } else {
                    $('#write_off_container').addClass('d-none');
                    $('#customer_deposit_amount').val('0');

                    // Kembalikan ke original
                    const formatted = new Intl.NumberFormat('id-ID').format(originalPaidAmount);
                    $('#paid_amount').val(formatted);
                    $('#paid_amount_display').text('Paid: Rp. ' + formatted);
                }
            });

            // 🔥 Format input customer deposit
            $('#customer_deposit_amount').on('input', function() {
                let angka = this.value.replace(/\D/g, "") || "0";

                // Cek jangan melebihi balance atau customer deposit
                const maxDeposit = Math.min(currentBalance, customerTotalDeposit);
                if (parseInt(angka) > maxDeposit) {
                    angka = maxDeposit.toString();
                }

                this.value = new Intl.NumberFormat('id-ID').format(angka);
                updatePaidAmountWithDeposit();
            });

            // 🔥 Fungsi update Paid Amount berdasarkan deposit
            function updatePaidAmountWithDeposit() {
                const depositUsed = parseInt($('#customer_deposit_amount').val().replace(/\D/g, "")) || 0;
                const newPaidAmount = originalPaidAmount - depositUsed;

                const formatted = new Intl.NumberFormat('id-ID').format(Math.max(0, newPaidAmount));
                $('#paid_amount').val(formatted);
                $('#paid_amount_display').text('Paid: Rp. ' + formatted);
            }

            // 🔥 Handler checkbox Write Off Only
            $('#use_write_off_only').on('change', function() {
                if ($(this).is(':checked')) {
                    // Matikan deposit kalau write off only aktif
                    $('#use_write_off').prop('checked', false);
                    $('#write_off_container').addClass('d-none');
                    $('#customer_deposit_amount').val('0');

                    // SET PAID AMOUNT KE 0
                    $('#paid_amount').val('0');
                    $('#paid_amount_display').text('Paid: Rp. 0');

                } else {
                    // KEMBALIKAN KE NILAI AWAL (originalPaidAmount atau remainingAmount)
                    const formatted = new Intl.NumberFormat('id-ID').format(originalPaidAmount);
                    $('#paid_amount').val(formatted);
                    $('#paid_amount_display').text('Paid: Rp. ' + formatted);
                }
            });

            // 🔥 Handler button Mark as Paid (FINAL FIX - GUARANTEED WORK!)
            $(document).on('click', '.btn-mark-paid', function(e) {
                e.preventDefault();

                const orderId = $(this).data('id');
                const url = $(this).data('url');
                const totalAmount = parseFloat($(this).data('total-amount')) || 0;
                const paidAmount = parseFloat($(this).data('paid-amount')) || 0;
                const customerDeposit = parseFloat($(this).data('deposit')) || 0;

                const remainingAmount = totalAmount - paidAmount;

                // 🔥 Simpan ke global variables
                originalPaidAmount = remainingAmount;
                customerTotalDeposit = customerDeposit;
                currentBalance = remainingAmount;

                // 🔥 Set form data
                $('#order_id').val(orderId);
                $('#customer_total_deposit').val(customerDeposit);
                $('#markAsSaleForm').attr('action', url);

                $('#total_amount_display').html('Rp.&nbsp;' + new Intl.NumberFormat('id-ID').format(
                    remainingAmount));

                // 🔥 Set Paid Amount LANGSUNG (INI YANG PENTING BANGET!)
                const formattedAmount = new Intl.NumberFormat('id-ID').format(remainingAmount);
                $('#paid_amount').val(formattedAmount);
                $('#paid_amount_display').text('Paid: Rp. ' + formattedAmount);

                // 🔥 Update deposit info
                $('#customer_deposit_display').text('Rp. ' + new Intl.NumberFormat('id-ID').format(
                    customerDeposit));

                const maxDeposit = Math.min(remainingAmount, customerDeposit);
                $('#max_deposit_display').text('Rp. ' + new Intl.NumberFormat('id-ID').format(maxDeposit));

                // 🔥 Reset write off state
                $('#use_write_off').prop('checked', false);
                $('#write_off_container').addClass('d-none');
                $('#customer_deposit_amount').val('0');
            });

            let mobilePage = 0;
            const MOBILE_LIMIT = 50;

            function renderMobileFromAllData() {
                if (window.innerWidth >= 768) return;

                const container = $('#saleListMobile');

                if (!allData.length) {
                    container.html('<div class="text-center text-muted py-4">No sale data</div>');
                    return;
                }

                const start = 0;
                const end = (mobilePage + 1) * MOBILE_LIMIT;
                const slicedData = allData.slice(start, end);

                container.html('');

                slicedData.forEach(row => {
                    container.append(`
            <div class="sale-mobile-card" data-id="${row.id}">
                <div class="sale-mobile-main">
                    <div class="sale-mobile-header">
                        <div class="sale-invoice">${row.order_number}</div>                                    
                        <span class="sale-status">${row.payment_status}</span>
                    </div>

                    <div class="sale-amount align-items-end">
                        <div>
                            <div class="sale-customer">${row.customer_mobile}</div>
                            ${row.grand_total}
                        </div>
                        <div class="text-end"> 
                            ${row.paid_amount}
                        </div>
                    </div>
                </div>

                <div class="sale-mobile-action">
                    ${row.action_mobile}
                </div>
            </div>
        `);
                });
            }

            $(window).on('scroll', function() {
                if (window.innerWidth >= 768) return;
                if (isLoading || !hasMoreData) return;

                const scrollTop = $(window).scrollTop();
                const windowHeight = $(window).height();
                const documentHeight = $(document).height();

                if (scrollTop + windowHeight >= documentHeight - 200) {
                    mobilePage++;
                    loadMoreData();
                }
            });


            $(document).on('click', '.sale-mobile-card', function(e) {
                if ($(e.target).closest('.sale-mobile-action, button, a').length) return;

                const card = $(this);

                // tutup card lain
                $('.sale-mobile-card').not(card).removeClass('active');

                // toggle card ini
                card.toggleClass('active');
            });

            $(document).on('click', function(e) {
                if ($(e.target).closest('.sale-mobile-card').length) return;

                $('.sale-mobile-card').removeClass('active');
            });

        });

        // Modal handlers
        // document.addEventListener('DOMContentLoaded', function() {
        //     const modal = document.getElementById('modalDeleteOrder');
        //     const form = document.getElementById('formDeleteOrder');
        //     const nameHolder = document.getElementById('OrderName');

        //     modal.addEventListener('show.bs.modal', function(event) {
        //         const button = event.relatedTarget;
        //         const id = button.getAttribute('data-id');
        //         const name = button.getAttribute('data-name');
        //         const url = button.getAttribute('data-url');

        //         form.action = url;
        //         form.dataset.id = id;
        //         nameHolder.textContent = name;
        //     });
        // });

        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.btn-delete');
            if (!btn) return;

            const id = btn.getAttribute('data-id');
            const url = btn.getAttribute('data-url');
            const customer = btn.getAttribute('data-customer');
            const business = btn.getAttribute('data-business');

            const form = document.getElementById('formDeleteOrder');
            form.action = url;
            form.dataset.id = id;

            document.getElementById('delete_customer_name').textContent = customer ?? '-';
            document.getElementById('delete_business_name').textContent = business ?? '-';
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
                form.dataset.id = id;
                nameHolder.textContent = name;
            });
        });

        // document.addEventListener('DOMContentLoaded', function() {
        //     const modal = document.getElementById('modalForceDeleteOwner');
        //     const form = document.getElementById('forceDeleteOwnerForm');
        //     const nameHolder = document.getElementById('fd-order-number'); // id di modal kamu

        //     modal.addEventListener('show.bs.modal', function(event) {
        //         const button = event.relatedTarget;
        //         const id = button.getAttribute('data-id');
        //         const name = button.getAttribute('data-name');
        //         const url = button.getAttribute('data-url');

        //         form.action = url;
        //         form.dataset.id = id;
        //         if (nameHolder) nameHolder.textContent = name; // isi nama ordernya
        //     });
        // });

        $(document).on('input', '#paid_amount', function() {
            // Jangan format kalau lagi di-set programmatically
            if ($(this).data('programmatic-update')) {
                return;
            }

            let angka = this.value.replace(/\D/g, "") || "0";
            this.value = new Intl.NumberFormat('id-ID').format(angka);
        });

        document.getElementById('formReturnMoney').addEventListener('submit', function(e) {
            e.preventDefault();

            document.querySelectorAll('#formReturnMoney small.text-danger').forEach(el => {
                el.classList.add('d-none');
                el.innerText = '';
            });

            let valid = true;

            let transactionDate = document.getElementById('return_transaction_date').value.trim();
            let cashBankAccount = document.getElementById('return_cash_bank_account_id').value.trim();

            let returnAmountRaw = document.getElementById('return_amount').value.trim();
            let returnAmount = returnAmountRaw.replace(/\./g, "");

            if (!transactionDate) {
                document.getElementById('error_return_transaction_date').innerText =
                    'Tanggal transaksi wajib diisi';
                document.getElementById('error_return_transaction_date').classList.remove('d-none');
                valid = false;
            }

            if (!cashBankAccount) {
                document.getElementById('error_return_cash_bank_account_id').innerText =
                    'Pilih cash atau bank account';
                document.getElementById('error_return_cash_bank_account_id').classList.remove('d-none');
                valid = false;
            }

            if (!returnAmount || isNaN(returnAmount) || parseInt(returnAmount) <= 0) {
                document.getElementById('error_return_amount').innerText =
                    'Return amount harus diisi dan lebih dari 0';
                document.getElementById('error_return_amount').classList.remove('d-none');
                valid = false;
            }

            if (!valid) return;

            document.getElementById('return_amount').value = returnAmount;

            this.submit();
        });

        $(document).on('click', '.btn-share-invoice-img', function() {
            const url = $(this).data('url');
            window.open(url, '_blank');
        });

        // document.addEventListener('DOMContentLoaded', function() {
        //     const modal = document.getElementById('modalForceDeleteOrder');
        //     const form = document.getElementById('formForceDeleteOrder');
        //     const nameHolder = document.getElementById('ForceOrderName');

        //     modal.addEventListener('show.bs.modal', function(event) {
        //         const button = event.relatedTarget;
        //         const id = button.getAttribute('data-id');
        //         const name = button.getAttribute('data-name');
        //         const url = button.getAttribute('data-url');

        //         form.action = url;
        //         form.dataset.id = id;
        //         nameHolder.textContent = name;
        //     });
        // });

        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.btn-force-delete-owner');
            if (!btn) return;

            const id = btn.getAttribute('data-id');
            const url = btn.getAttribute('data-url');
            const name = btn.getAttribute('data-name');
            const customer = btn.getAttribute('data-customer');
            const business = btn.getAttribute('data-business');

            const form = document.getElementById('forceDeleteOwnerForm');
            form.action = url;
            form.dataset.id = id;

            document.getElementById('fd-order-number').textContent = name ?? '-';
            document.getElementById('fd-customer-name').textContent = customer ?? '-';
            document.getElementById('fd-business-name').textContent = business ?? '-';
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

        $(document).on('click', '.btn-return-money', function() {
            const orderId = $(this).data('id');
            const url = $(this).data('url');
            const overpaidAmount = $(this).data('overpaid-amount');

            $('#return_order_id').val(orderId);
            $('#formReturnMoney').attr('action', url);

            const formatted = new Intl.NumberFormat('id-ID').format(overpaidAmount);
            $('#return_amount').val(formatted);
            $('#overpaid_display').text(formatted);

            $('#return_balance_display').text(formatted);
        });

        $('#return_amount').on('input', function() {
            let angka = this.value.replace(/\D/g, "") || "0";
            this.value = new Intl.NumberFormat('id-ID').format(angka);
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script>
        $(document).on('click', '.btn-share-invoice', async function() {
            console.log('SCRIPT SHARE LOADED');
            const btn = $(this);

            const orderId = btn.data('id');
            const invoiceUrl = btn.data('url');
            const business = btn.data('business');
            const invoiceNo = btn.data('invoice');
            const total = btn.data('total');

            const rawPhone = btn.attr('data-phone') ?? '';
            let phone = String(rawPhone).replace(/[^0-9]/g, '');
            if (phone.startsWith('0')) phone = '62' + phone.substring(1);

            try {
                // 🔹 ambil HTML invoice
                const html = await fetch(invoiceUrl).then(r => r.text());

                const temp = document.createElement('div');
                temp.style.position = 'fixed';
                temp.style.left = '-99999px';
                temp.innerHTML = html;
                document.body.appendChild(temp);

                const invoiceContent = temp.querySelector('#invoiceContent');
                if (!invoiceContent) throw new Error('invoiceContent tidak ditemukan');

                // 🔹 convert ke image
                const canvas = await html2canvas(invoiceContent, {
                    scale: 2,
                    backgroundColor: '#ffffff'
                });

                document.body.removeChild(temp);

                const imageData = canvas.toDataURL('image/jpeg', 0.95);

                // 🔹 upload ke server
                const response = await fetch('{{ route('invoice.convert') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        image: imageData,
                        order_id: orderId
                    })
                });

                const result = await response.json();
                if (!result.success) return;

                const message = [
                    `Berikut Invoice *${invoiceNo}*`,
                    result.url,
                    ``,
                    `1) Diwajibkan Melunasi Tagihan`,
                    `Terlebih dahulu sebelum proses produksi dimulai.`,
                    ``,
                    `2) Setelah pembayaran diterima`,
                    `Produksi akan berjalan sesuai estimasi yang disepakati.`,
                    ``,
                    `*REKENING BCA*`,
                    `Nomor: *0590712647*`,
                    `Nama: *STEFAN LEWIS*`,
                    ``,
                    `*WAJIB:*`,
                    `Mengirim bukti transfer setelah pembayaran`,
                ].join('\n');
                window.open(
                    `https://wa.me/${phone}?text=${encodeURIComponent(message)}`,
                    '_blank'
                );

            } catch (e) {
                console.error(e);
            }
        });

        $(document).on('click', '.btn-share-invoice-image', async function() {
            const url = $(this).data('url');
            const name = $(this).data('customer');

            const response = await fetch(url);
            const html = await response.text();

            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = html;
            tempDiv.style.position = 'absolute';
            tempDiv.style.top = '-9999px';
            tempDiv.style.left = '-9999px';
            document.body.appendChild(tempDiv);

            const invoiceElement = tempDiv.querySelector('#invoiceContent');

            const canvas = await html2canvas(invoiceElement, {
                scale: 2
            });
            document.body.removeChild(tempDiv);

            const dataUrl = canvas.toDataURL('image/png');
            const blob = await (await fetch(dataUrl)).blob();
            const file = new File([blob], 'invoice.png', {
                type: 'image/png'
            });

            const shareText = `Halo ${name}, berikut invoice pembelian Anda.`;

            if (navigator.canShare && navigator.canShare({
                    files: [file]
                })) {
                navigator.share({
                    files: [file],
                    title: 'Invoice',
                    text: shareText
                });
            } else {
                alert('Browser tidak mendukung share langsung. Gambar akan di-download.');
                const link = document.createElement('a');
                link.href = dataUrl;
                link.download = 'invoice.png';
                link.click();
            }
        });
    </script>
@endpush
