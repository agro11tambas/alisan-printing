@extends('erp.layouts.main')

@push('styles')
    <style>
        @media (max-width: 768px) {

            #purchaseListTable td.desktop-only,
            #purchaseListTable th.desktop-only {
                display: none !important;
            }
        }

        #purchaseListTable {
            width: 100% !important;
            min-width: 0;
        }

        #purchaseListTable_wrapper .dataTables_scrollBody {
            background-image: none !important;
            height: 60vh !important;
            overflow-y: auto !important;
        }

        #deletedPurchaseListTable_wrapper .dataTables_scrollBody {
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

        #purchaseListTable tbody tr {
            animation: fadeIn 0.3s ease-in;
        }

        .static-action-menu {
            padding: 6px;
            min-width: 700px;
        }

        .action-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
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
        @include('erp.pages.partials.transaction-list-mobile-header-styles')
    </style>
@endpush

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Purchase List</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item"><a href="/erp/purchases/purchase-orders">Purchase Order</a></li>
                @if ($purchaseOrder)
                    <li class="breadcrumb-item">{{ $purchaseOrder->purchase_number }}</li>
                @else
                    <li class="breadcrumb-item">Purchase List</li>
                @endif
            </ul>
        </div>
        <div class="page-header-right ms-auto">
            <div class="page-header-right-items transaction-list-desktop-actions">
                <div class="d-flex d-md-none">
                    <a href="javascript:void(0)" class="page-header-right-close-toggle">
                        <i class="feather-arrow-left me-2"></i><span>Back</span>
                    </a>
                </div>
                <div class="d-flex align-items-center gap-2 page-header-right-items-wrapper">
                    <a href="/erp/purchases/purchase-orders" class="btn btn-light-brand">
                        <i class="feather-arrow-left me-2"></i>
                        <span>Back to Purchase Order</span>
                    </a>
                </div>
            </div>
            <div class="d-md-none d-flex align-items-center">
                <a href="/erp/purchases/purchase-orders"
                    class="btn btn-light-brand transaction-list-mobile-action">
                    <i class="feather-arrow-left"></i>
                    <span>Purchase Orders</span>
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
                            <div class="col-lg-4">
                                <label for="search_type" class="fw-semibold fs-12">Filter By</label>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <select id="search_type" class="form-control"
                                            style="padding: 0.25rem 0.5rem; font-size: 0.875rem;">
                                            <option value="supplier">Supplier</option>
                                            <option value="purchase_number">Invoice</option>
                                            <option value="payment_status">Payment Status</option>
                                            <option value="due_date">Due Date</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="text" id="search_keyword" name="search_keyword"
                                            class="form-control search-input"
                                            style="padding: 0.25rem 0.5rem; font-size: 0.875rem;" placeholder="Search..." />
                                        <select id="search_payment_status" class="form-control search-input d-none"
                                            style="padding: 0.25rem 0.5rem; font-size: 0.875rem;">
                                            <option value="">All</option>
                                            <option value="Paid">Paid</option>
                                            <option value="Unpaid">Unpaid</option>
                                            <option value="Partially Paid">Partially Paid</option>
                                        </select>
                                        <select id="due_date_order" class="form-control d-none"
                                            style="padding: 0.25rem 0.5rem;">
                                            <option value="asc">Ascending</option>
                                            <option value="desc">Descending</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <ul class="nav nav-tabs mb-2" id="purchaseListTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="purchase-list-tab" data-bs-toggle="tab" href="#purchase-list"
                                    role="tab">Purchase List</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="deleted-purchase-list-tab" data-bs-toggle="tab"
                                    href="#deleted-purchase-list" role="tab">Deleted Purchase List</a>
                            </li>
                        </ul>
                        <div class="table-responsive">
                            <div class="tab-content">
                                <div class="tab-pane fade show active" id="purchase-list" role="tabpanel">
                                    <table class="table table-hover bg-transparent" id="purchaseListTable">
                                        <thead>
                                            <tr>
                                                <th></th>
                                                <th data-column="purchase_number">Purchase Number</th>
                                                <th data-column="supplier">Supplier</th>
                                                <th data-column="total_amount_product">Product Total</th>
                                                <th data-column="paid_amount_product">Product Paid</th>
                                                <th data-column="total_amount_freight">Freight Total</th>
                                                <th data-column="paid_amount_freight">Freight Paid</th>
                                                <th data-column="payment_status">Status</th>
                                                <th>User</th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                                <div class="tab-pane fade show" id="deleted-purchase-list" role="tabpanel">
                                    <table class="table table-hover bg-transparent" id="deletedPurchaseListTable">
                                        <thead>
                                            <tr>
                                                <th></th>
                                                <th>Purchase Number</th>
                                                <th>Supplier</th>
                                                <th>Total Amount</th>
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
    <div class="modal fade" id="modalDeletePurchase" tabindex="-1" aria-labelledby="deleteModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" id="formDeletePurchase">
                @csrf
                @method('DELETE')
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title text-white" id="deleteModalLabel">Hapus Purchase</h5>
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

    <div class="modal fade-scale" id="modalChangeStatusProduct" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="fw-bold mb-0">Mark as Paid (Product)</h5>
                    <a href="javascript:void(0)" class="avatar-text avatar-md bg-soft-danger close-icon"
                        data-bs-dismiss="modal">
                        <i class="feather-x text-danger"></i>
                    </a>
                </div>
                <form method="POST" id="markAsPurchaseFormProduct" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="purchase_id_product" name="purchase_id">
                    <div class="modal-body">
                        <div class="row g-3 mb-2">
                            <div class="col-md-6">
                                <label for="transaction_type_product" class="fw-semibold">Purchase Account:</label>
                                <div class="input-group">
                                    <select class="form-select" id="transaction_type_product" data-select2-selector="tag"
                                        name="transaction_type">
                                        <option value="12" data-bg="bg-danger">Purchase Account</option>
                                    </select>
                                </div>
                                <small class="text-danger d-none" id="error_transaction_type_product"></small>
                            </div>
                            <div class="col-md-6">
                                <label for="transaction_date_product" class="fw-semibold">Transaction Date:</label>
                                <div class="input-group">
                                    <input type="date" id="transaction_date_product" name="transaction_date"
                                        class="form-control" value="{{ date('Y-m-d') }}">
                                </div>
                                <small class="text-danger d-none" id="error_transaction_date_product"></small>
                            </div>
                        </div>
                        <div class="row g-3 mb-2">
                            <div class="col-md-6">
                                <label for="cash_bank_account_id_product" class="fw-semibold">Cash/Bank Account:</label>
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
                                    {{-- <select class="form-select" id="cash_bank_account_id_product"
                                        data-select2-selector="tag" name="cash_bank_account_id">
                                        <option value="" disabled selected hidden>Pilih Cash/Bank Account</option>
                                        @foreach ($cashAccounts as $cash)
                                            @php
                                                $bg = $bgColors[$loop->index % count($bgColors)];
                                            @endphp
                                            <option value="{{ $cash->id }}" data-bg="{{ $bg }}">Cash -
                                                {{ $cash->type }}</option>
                                        @endforeach
                                        @foreach ($bankAccounts as $bank)
                                            @php
                                                $bg = $bgColors[$loop->index % count($bgColors)];
                                            @endphp
                                            <option value="{{ $bank->id }}" data-bg="{{ $bg }}">Bank -
                                                {{ $bank->type }}</option>
                                        @endforeach
                                    </select> --}}
                                    <select class="form-select" id="cash_bank_account_id_product"
                                        data-select2-selector="tag" name="cash_bank_account_id">
                                        <option value="" disabled {{ !$defaultAccount ? 'selected' : '' }} hidden>
                                            Pilih Cash/Bank Account</option>

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
                                <small class="text-danger d-none" id="error_cash_bank_account_id_product"></small>
                            </div>
                            <div class="col-md-6">
                                <label for="paid_amount_product" class="fw-semibold">Paid Amount:</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="paid_amount_product"
                                        name="paid_amount" value="0">
                                </div>
                                <small class="text-danger d-none" id="error_paid_amount_product"></small>
                                <span class="fw-semibold fs-12" id="paid_amount_display_product">Paid: Rp. 0</span>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <label class="fw-semibold">Upload / Paste Proof (optional):</label>

                            <div id="pasteProofAreaProduct" class="border rounded p-2 text-center"
                                style="min-height: 120px; cursor: pointer;">
                                <p class="text-muted small mb-1">
                                    Klik di sini lalu tekan <strong>Ctrl + V</strong> untuk paste screenshot bukti transfer
                                </p>

                                <div id="proofPreviewContainerProduct" class="preview-list"></div>
                            </div>

                            <small class="text-danger d-none" id="error_purchase_payment_proof"></small>
                        </div>

                    </div>
                    <div class="modal-footer d-flex justify-content-between">
                        <div>
                            <p class="m-0">Balance:</p>
                            <h5 class="fw-semibold text-danger" id="total_amount_display_product">0</h5>
                        </div>
                        <button type="submit" class="btn btn-primary">Mark as Paid</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade-scale" id="modalChangeStatusFreight" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="fw-bold mb-0">Mark as Paid (Freight)</h5>
                    <a href="javascript:void(0)" class="avatar-text avatar-md bg-soft-danger close-icon"
                        data-bs-dismiss="modal">
                        <i class="feather-x text-danger"></i>
                    </a>
                </div>
                <form method="POST" id="markAsPurchaseFormFreight" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="purchase_id_freight" name="purchase_id">
                    <div class="modal-body">
                        <div class="row g-3 mb-2">
                            <div class="col-md-6">
                                <label for="transaction_type_freight" class="fw-semibold">Purchase Account:</label>
                                <div class="input-group">
                                    <select class="form-select" id="transaction_type_freight" data-select2-selector="tag"
                                        name="transaction_type">
                                        <option value="12" data-bg="bg-danger">Purchase Account</option>
                                    </select>
                                </div>
                                <small class="text-danger d-none" id="error_transaction_type_freight"></small>
                            </div>
                            <div class="col-md-6">
                                <label for="transaction_date_freight" class="fw-semibold">Transaction Date:</label>
                                <div class="input-group">
                                    <input type="date" id="transaction_date_freight" name="transaction_date"
                                        class="form-control" value="{{ date('Y-m-d') }}">
                                </div>
                                <small class="text-danger d-none" id="error_transaction_date_freight"></small>
                            </div>
                        </div>
                        <div class="row g-3 mb-2">
                            <div class="col-md-6">
                                <label for="cash_bank_account_id_product" class="fw-semibold">Cash/Bank Account:</label>
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
                                    {{-- <select class="form-select" id="cash_bank_account_id_freight"
                                        data-select2-selector="tag" name="cash_bank_account_id">
                                        <option value="" disabled selected hidden>Pilih Cash/Bank Account</option>
                                        @foreach ($cashAccounts as $cash)
                                            @php
                                                $bg = $bgColors[$loop->index % count($bgColors)];
                                            @endphp
                                            <option value="{{ $cash->id }}" data-bg="{{ $bg }}">Cash -
                                                {{ $cash->type }}</option>
                                        @endforeach
                                        @foreach ($bankAccounts as $bank)
                                            @php
                                                $bg = $bgColors[$loop->index % count($bgColors)];
                                            @endphp
                                            <option value="{{ $bank->id }}" data-bg="{{ $bg }}">Bank -
                                                {{ $bank->type }}</option>
                                        @endforeach
                                    </select> --}}
                                    <select class="form-select" id="cash_bank_account_id_freight"
                                        data-select2-selector="tag" name="cash_bank_account_id">
                                        <option value="" disabled {{ !$defaultAccount ? 'selected' : '' }} hidden>
                                            Pilih Cash/Bank Account</option>

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
                            </div>
                            <div class="col-md-6">
                                <label for="paid_amount_freight" class="fw-semibold">Paid Amount:</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="paid_amount_freight"
                                        name="paid_amount" value="0">
                                </div>
                                <small class="text-danger d-none" id="error_paid_amount_freight"></small>
                                <span class="fw-semibold fs-12" id="paid_amount_display_freight">Paid: Rp. 0</span>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <label class="fw-semibold">Upload / Paste Proof (optional):</label>

                            <div id="pasteProofAreaFreight" class="border rounded p-2 text-center"
                                style="min-height: 120px; cursor: pointer;">
                                <p class="text-muted small mb-1">
                                    Klik di sini lalu tekan <strong>Ctrl + V</strong> untuk paste screenshot bukti transfer
                                </p>

                                <div id="proofPreviewContainerFreight" class="preview-list"></div>
                            </div>

                            <small class="text-danger d-none" id="error_purchase_payment_proof"></small>
                        </div>

                    </div>
                    <div class="modal-footer d-flex justify-content-between">
                        <div>
                            <p class="m-0">Balance:</p>
                            <h5 class="fw-semibold text-danger" id="total_amount_display_freight">0</h5>
                        </div>
                        <button type="submit" class="btn btn-primary">Mark as Paid</button>
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

    <div class="modal fade" id="modalForceDeleteOwnerPurchase" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" id="forceDeleteOwnerPurchaseForm">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Force Delete Purchase (Owner)</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-1">
                            Anda akan menghapus Purchase List anak
                            <strong id="fd-purchase-number"></strong> secara permanen.
                        </p>
                        <div class="alert alert-warning py-2">
                            Stock In akan dibalik sesuai tujuan stok, seluruh history stock-in dan inventory akan
                            dihapus, serta transaksi pembayaran akan dibalik dari saldo akun.
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Delete Notes <span class="text-danger">*</span></label>
                            <textarea name="delete_notes" class="form-control" rows="3" required placeholder="Alasan penghapusan..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="feather feather-zap-off me-2"></i>Force Delete
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
                                    <th class="text-end">Price + Tax</th>
                                    <th class="text-end">Freight</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-end">Stock In</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                products.forEach(p => {
                    html += `
                                <tr>
                                    <td>${p.name}</td>
                                    <td>${p.sku}</td>
                                    <td>${p.qty}</td>
                                    <td class="text-end">${p.price}</td>
                                    <td class="text-end">${p.freight}</td>
                                    <td class="text-end">${p.total_price}</td>
                                    <td class="text-end">${p.stock_in} / ${p.qty}</td>
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
                const activeTab = $('#purchaseListTabs .nav-link.active').attr('href');

                if (activeTab === '#deleted-purchase-list') {
                    resetAndReloadDeleted();
                } else {
                    resetAndReload();
                }
            }

            let allData = [];
            let currentPage = 0;
            let isLoading = false;
            let hasMoreData = true;

            const dataTable = $('#purchaseListTable').DataTable({
                processing: false,
                serverSide: false,
                scrollY: '60vh',
                scrollCollapse: true,
                paging: false,
                searching: false,
                info: false,
                lengthChange: false,
                // order: [
                //     [7, 'desc']
                // ],
                ordering: false,
                data: [],
                columns: [{
                        className: 'dt-control text-center',
                        orderable: false,
                        data: null,
                        defaultContent: '',
                        width: "20px"
                    },
                    {
                        data: 'purchase_number'
                    },
                    {
                        data: 'supplier'
                    },
                    {
                        data: 'total_amount_product'
                    },
                    {
                        data: 'paid_amount_product',
                        name: 'paid_amount_product'
                    },
                    {
                        data: 'total_amount_freight'
                    },
                    {
                        data: 'paid_amount_freight',
                        name: 'paid_amount_freight'
                    },
                    {
                        data: 'payment_status',
                        name: 'payment_status'
                    },
                    {
                        data: 'user'
                    },
                    {
                        data: 'purchase_date',
                        visible: false,
                        searchable: false
                    }
                ]
            });

            let searchTimer = null;
            let currentRequest = null;

            function loadMoreData() {
                if (isLoading || !hasMoreData) return;
                isLoading = true;

                // 🚫 Batalkan request lama jika masih jalan
                if (currentRequest) {
                    currentRequest.abort();
                }

                currentRequest = $.ajax({
                    url: "{{ url('/erp/purchases/purchase-list/data') }}",
                    type: 'GET',
                    data: {
                        start: currentPage * 50,
                        length: 50,
                        purchase_order_id: @json($purchaseOrder?->id),
                        filter: $('#filter').val(),
                        start_date: $('#start_date').val(),
                        end_date: $('#end_date').val(),
                        search_type: $('#search_type').val(),
                        search_keyword: $('#search_keyword').val(),
                        payment_status: $('#search_payment_status').val(),
                        due_date_order: $('#due_date_order').val(),
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

            // Load pertama kali
            loadMoreData();

            // Lazy load saat scroll

            $('.dataTables_scrollBody').on('scroll', function() {
                const scrollTop = $(this).scrollTop();
                const scrollHeight = $(this)[0].scrollHeight;
                const clientHeight = $(this).height();

                // Load earlier (70%) without delay
                if (scrollTop + clientHeight >= scrollHeight * 0.70) {
                    loadMoreData();
                }
            });

            // Reset dan reload saat filter berubah
            function resetAndReload() {
                allData = [];
                currentPage = 0;
                hasMoreData = true;
                dataTable.clear().draw();
                loadMoreData();
            }

            // Apply filter (RESET)
            $('#apply-filter').on('click', function() {
                reloadActiveTab(); // reset table
            });

            // Search keyword (RESET)
            // $('#search_keyword').on('keyup', function() {
            //     clearTimeout(searchTimer);
            //     searchTimer = setTimeout(() => {
            //         reloadActiveTab();
            //     }, 250);
            // });

            // Search type (RESET)
            $('#search_type').on('change', function() {
                reloadActiveTab();
            });

            // Payment status (RESET)
            $('#search_payment_status').on('change', function() {
                reloadActiveTab();
            });

            // SHOW / HIDE custom date range
            $('#filter').on('change', function() {
                if ($(this).val() === 'custom') {
                    $('.custom-range').removeClass('d-none');
                } else {
                    $('.custom-range').addClass('d-none');
                    $('#start_date').val('');
                    $('#end_date').val('');
                }

                reloadActiveTab(); // FIX TODAY & CUSTOM
            });

            // Apply filter button
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

                reloadActiveTab();
            });

            $('#due_date_order').on('change', function() {
                if ($('#search_type').val() === 'due_date') {
                    resetAndReload();
                }
            });

            $('#search_keyword').on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    reloadActiveTab();
                }
            });

            // kosong → auto load
            $('#search_keyword').on('input', function() {
                if ($(this).val().trim() === '') {
                    reloadActiveTab();
                }
            });


            $('#search_payment_status').on('change', function() {
                reloadActiveTab();
            });

            // Expand/collapse products detail
            $('#purchaseListTable tbody').on('click', 'td.dt-control', function() {
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
            $('#purchaseListTable tbody').on('click', 'tr', function(e) {
                if ($(e.target).closest('td.dt-control').length) return;

                let $tr = $(this);
                let row = dataTable.row($tr);

                $('#purchaseListTable tbody tr').removeClass('action-shown').next('.action-row').remove();

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

            // Close action dropdown saat klik di luar
            $(document).on('click', function(e) {
                if ($(e.target).closest('#purchaseListTable').length) return;
                $('#purchaseListTable tbody tr').removeClass('action-shown').next('.action-row').remove();
            });

            // ========== DELETED PURCHASE LIST TABLE (CSR dengan Lazy Load) ==========
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
                        data: 'purchase_number'
                    },
                    {
                        data: 'supplier'
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

                deletedTable = $('#deletedPurchaseListTable').DataTable({
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

                // Lazy load saat scroll untuk deleted table
                $('#deletedPurchaseListTable')
                    .closest('.dataTables_wrapper')
                    .find('.dataTables_scrollBody')
                    .on('scroll', function() {

                        clearTimeout(scrollTimeout);

                        const scrollTop = $(this).scrollTop();
                        const scrollHeight = this.scrollHeight;
                        const clientHeight = $(this).height();

                        scrollTimeout = setTimeout(() => {
                            if (scrollTop + clientHeight >= scrollHeight * 0.85) {
                                loadMoreDeletedData();
                            }
                        }, 200);
                    });

                // Expand products di deleted table
                $('#deletedPurchaseListTable tbody').on('click', 'td.dt-control', function() {
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
                    url: "{{ url('/erp/purchases/purchase-list/data-deleted') }}",
                    type: 'GET',
                    data: {
                        start: deletedCurrentPage * 50,
                        length: 50,
                        purchase_order_id: @json($purchaseOrder?->id),
                        filter: $('#filter').val(),
                        start_date: $('#start_date').val(),
                        end_date: $('#end_date').val(),
                        search_type: $('#search_type').val(),
                        search_keyword: $('#search_keyword').val(),
                        payment_status: $('#search_payment_status').val(),
                        due_date_order: $('#due_date_order').val(),
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

                if (target === '#purchase-list') {
                    reloadActiveTab();
                }

                if (target === '#deleted-purchase-list') {
                    if (!deletedTableInitialized) initDeletedTable();
                    reloadActiveTab();
                }
            });


            // Filter change untuk deleted table
            $('#filter, #apply-filter, #search_type, #due_date_order, #search_payment_status').on('change click',
                function() {
                    if ($('#purchaseListTabs .nav-link.active').attr('href') === '#deleted-purchase-list') {
                        reloadActiveTab();
                    }
                });

            $('#search_keyword').on('keyup', function() {
                if ($('#purchaseListTabs .nav-link.active').attr('href') === '#deleted-purchase-list') {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        reloadActiveTab();
                    }, 400);
                }
            });

            let pastedProofProductBlobs = [];
            const pasteAreaProduct = document.getElementById('pasteProofAreaProduct');
            const previewContainerProduct = document.getElementById('proofPreviewContainerProduct');

            if (pasteAreaProduct) {
                pasteAreaProduct.setAttribute('tabindex', '0'); // bisa fokus

                pasteAreaProduct.addEventListener('click', (e) => {
                    if (e.target === pasteAreaProduct) {
                        pasteAreaProduct.focus();
                    }
                });

                pasteAreaProduct.addEventListener('paste', (e) => {

                    // 🔥 Jika paste di input note → IZINKAN paste text normal
                    if (e.target.classList.contains('note-input')) {
                        return;
                    }

                    // 📌 Jika paste bukan di input note → intercept screenshot
                    e.preventDefault();
                    const items = e.clipboardData.items;

                    for (const item of items) {
                        if (item.type.indexOf("image") === 0) {
                            const blob = item.getAsFile();
                            pastedProofProductBlobs.push(blob);

                            const reader = new FileReader();
                            reader.onload = function(event) {
                                const wrapper = document.createElement('div');
                                wrapper.classList.add('preview-item', 'mb-1');

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

                                const removeBtn = document.createElement('button');
                                removeBtn.type = 'button';
                                removeBtn.className = 'btn btn-sm btn-danger mt-1';
                                removeBtn.innerHTML = '<i class="feather-x"></i> Hapus';
                                removeBtn.onclick = function() {
                                    const index = Array.from(previewContainerProduct.children)
                                        .indexOf(wrapper);
                                    pastedProofProductBlobs.splice(index, 1);
                                    wrapper.remove();
                                };

                                wrapper.appendChild(img);
                                wrapper.appendChild(noteInput);
                                wrapper.appendChild(removeBtn);
                                previewContainerProduct.appendChild(wrapper);
                            };
                            reader.readAsDataURL(blob);
                        }
                    }
                });

            }

            // ===== FREIGHT PAYMENT =====
            let pastedProofFreightBlobs = [];
            const pasteAreaFreight = document.getElementById('pasteProofAreaFreight');
            const previewContainerFreight = document.getElementById('proofPreviewContainerFreight');

            if (pasteAreaFreight) {
                pasteAreaFreight.setAttribute('tabindex', '0');

                pasteAreaFreight.addEventListener('click', (e) => {
                    if (e.target === pasteAreaFreight) {
                        pasteAreaFreight.focus();
                    }
                });

                pasteAreaFreight.addEventListener('paste', (e) => {

                    // 🔥 Jika paste terjadi di input note → IZINKAN normal
                    if (e.target.classList.contains('note-input')) {
                        return;
                    }

                    // 📌 Kalau bukan input note → intercept image
                    e.preventDefault();
                    const items = e.clipboardData.items;

                    for (const item of items) {
                        if (item.type.indexOf("image") === 0) {
                            const blob = item.getAsFile();
                            pastedProofFreightBlobs.push(blob);

                            const reader = new FileReader();
                            reader.onload = function(event) {
                                const wrapper = document.createElement('div');
                                wrapper.classList.add('preview-item', 'mb-1');

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

                                const removeBtn = document.createElement('button');
                                removeBtn.type = 'button';
                                removeBtn.className = 'btn btn-sm btn-danger mt-1';
                                removeBtn.innerHTML = '<i class="feather-x"></i> Hapus';
                                removeBtn.onclick = function() {
                                    const index = Array.from(previewContainerFreight.children)
                                        .indexOf(wrapper);
                                    pastedProofFreightBlobs.splice(index, 1);
                                    wrapper.remove();
                                };

                                wrapper.appendChild(img);
                                wrapper.appendChild(noteInput);
                                wrapper.appendChild(removeBtn);
                                previewContainerFreight.appendChild(wrapper);
                            };
                            reader.readAsDataURL(blob);
                        }
                    }
                });
            }

            // Update satu baris tabel langsung dari respons mark as paid, tanpa
            // reload halaman. Kolom dicari lewat nama kolom DataTables supaya
            // tidak patah kalau urutan/visibilitas kolom berubah.
            function updatePurchaseRow(purchaseId, payload) {
                if (!payload) return false;

                const row = dataTable.row(function(_, data) {
                    return String(data.id) === String(purchaseId);
                });

                if (!row.node()) return false;

                const rowNode = $(row.node());

                const setCell = (columnName, html) => {
                    if (html === undefined || html === null) return;

                    const visibleIndex = dataTable.column(columnName + ':name').index('visible');
                    if (visibleIndex === undefined || visibleIndex === null) return;

                    rowNode.find('td').eq(visibleIndex).html(html);
                };

                setCell('paid_amount_product', payload.paid_amount_product_html);
                setCell('paid_amount_freight', payload.paid_amount_freight_html);
                setCell('payment_status', payload.payment_status_html);

                // Data baris ikut diperbarui supaya menu aksi yang dibuka
                // setelah ini memakai tombol terbaru (mis. "Mark as Paid"
                // hilang begitu lunas).
                const d = row.data();
                d.paid_amount_product = payload.paid_amount_product_html ?? d.paid_amount_product;
                d.paid_amount_freight = payload.paid_amount_freight_html ?? d.paid_amount_freight;
                d.payment_status = payload.payment_status_html ?? d.payment_status;
                if (payload.action_html) d.action = payload.action_html;
                row.data(d);

                // Kalau baris aksi baris ini sedang terbuka, isinya ikut diganti.
                const $openActionRow = rowNode.next('.action-row');
                if ($openActionRow.length && payload.action_html) {
                    $openActionRow.find('td > div').html(payload.action_html);
                }

                rowNode.addClass('bg-success-subtle');
                setTimeout(() => rowNode.removeClass('bg-success-subtle'), 1500);

                return true;
            }

            $('#markAsPurchaseFormProduct').on('submit', function(e) {
                e.preventDefault();

                const form = this;
                const url = form.action;
                const formData = new FormData(form);
                formData.set('paid_amount', parseFloat($('#paid_amount_product').val().replace(/\./g, '')
                    .replace(',', '.')));

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(res) {
                        Swal.close();
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: res.message ?? 'Pembayaran produk berhasil disimpan!',
                            timer: 1800,
                            showConfirmButton: false
                        });

                        $('#modalChangeStatusProduct').modal('hide');
                        form.reset();

                        // Kalau baris gagal di-update (mis. barisnya belum
                        // ter-load), tabel di-refresh supaya status tetap
                        // benar tanpa perlu reload halaman.
                        if (!updatePurchaseRow($('#purchase_id_product').val(), res.purchase)) {
                            resetAndReload();
                        }
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: xhr.responseJSON?.message ??
                                'Terjadi kesalahan saat menyimpan'
                        });
                    }
                });
            });

            $('#markAsPurchaseFormFreight').on('submit', function(e) {
                e.preventDefault();

                const form = this;
                const url = form.action;
                const formData = new FormData(form);
                formData.set('paid_amount', parseFloat($('#paid_amount_freight').val().replace(/\./g, '')
                    .replace(',', '.')));

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(res) {
                        Swal.close();
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: res.message ??
                                'Pembayaran freight berhasil disimpan!',
                            timer: 1800,
                            showConfirmButton: false
                        });

                        $('#modalChangeStatusFreight').modal('hide');
                        form.reset();

                        if (!updatePurchaseRow($('#purchase_id_freight').val(), res.purchase)) {
                            resetAndReload();
                        }
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: xhr.responseJSON?.message ??
                                'Terjadi kesalahan saat menyimpan'
                        });
                    }
                });
            });

            // ========== DELETE PURCHASE (SOFT DELETE) ==========
            $('#formDeletePurchase').on('submit', function(e) {
                e.preventDefault();

                const form = this;
                const url = form.action;

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: $(form).serialize(),
                    success: function(res) {
                        Swal.close();
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: res.message ?? 'Purchase berhasil dihapus!',
                            timer: 1800,
                            showConfirmButton: false
                        });

                        $('#modalDeletePurchase').modal('hide');

                        // 🔁 Hapus row dari DataTable utama tanpa reload
                        dataTable.clear();
                        allData = [];
                        currentPage = 0;
                        hasMoreData = true;
                        loadMoreData();

                        // 🔁 Reload deleted table kalau tab sedang aktif
                        if ($('#purchaseListTabs .nav-link.active').attr('href') ===
                            '#deleted-purchase-list') {
                            reloadActiveTab();
                        }
                    },
                    error: function(xhr) {
                        Swal.close();
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: xhr.responseJSON?.message ??
                                'Terjadi kesalahan saat menghapus data'
                        });
                    }
                });
            });

            // ========== FORCE DELETE PURCHASE ==========
            $('#formForceDeleteOrder').on('submit', function(e) {
                e.preventDefault();

                const form = this;
                const url = form.action;

                $.ajax({
                    url: url,
                    type: 'POST', // tetap POST + _method spoof
                    data: $(form).serialize() + '&_method=DELETE',
                    success: function(res) {
                        Swal.close();
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: res.message ?? 'Purchase dihapus permanen!',
                            timer: 1800,
                            showConfirmButton: false
                        });

                        $('#modalForceDeleteOrder').modal('hide');

                        // 🔁 Hapus dari deletedTable tanpa reload halaman
                        if (deletedTable) {
                            deletedTable.clear().draw(false); // 🔹 paksa refresh tbody kosong
                            deletedAllData = [];
                            deletedCurrentPage = 0;
                            deletedHasMoreData = true;
                            loadMoreDeletedData();
                        }
                    },
                    error: function(xhr) {
                        Swal.close();
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: xhr.responseJSON?.message ??
                                'Terjadi kesalahan saat menghapus permanen'
                        });
                    }
                });
            });

            // ========== RESTORE PURCHASE ==========
            $('#formRestoreOrder').on('submit', function(e) {
                e.preventDefault();

                const form = this;
                const url = form.action;

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: $(form).serialize(),
                    success: function(res) {
                        Swal.close();
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: res.message ?? 'Purchase berhasil dikembalikan!',
                            timer: 1800,
                            showConfirmButton: false
                        });

                        $('#modalRestoreOrder').modal('hide');

                        // 🔁 Refresh kedua tabel tanpa reload halaman
                        dataTable.clear();
                        allData = [];
                        currentPage = 0;
                        hasMoreData = true;
                        loadMoreData();

                        if (deletedTable) {
                            deletedTable.clear().draw(false);
                            deletedAllData = [];
                            deletedCurrentPage = 0;
                            deletedHasMoreData = true;
                            loadMoreDeletedData();
                        }
                    },
                    error: function(xhr) {
                        Swal.close();
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: xhr.responseJSON?.message ??
                                'Terjadi kesalahan saat mengembalikan data'
                        });
                    }
                });
            });
        });

        // ========== Modal Handlers ==========
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('modalDeletePurchase');
            const form = document.getElementById('formDeletePurchase');
            const nameHolder = document.getElementById('purchaseName');

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

        document.addEventListener('click', function(e) {
            if (e.target.closest('.btn-mark-paid-product')) {
                const btn = e.target.closest('.btn-mark-paid-product');

                const purchaseId = btn.dataset.id;
                const url = btn.dataset.url;
                const totalAmount = parseFloat(btn.dataset.totalAmountProduct) || 0;
                const paidAmount = parseFloat(btn.dataset.paidAmountProduct) || 0;
                const remaining = Math.max(totalAmount - paidAmount, 0);

                const fmt = new Intl.NumberFormat('id-ID');

                document.getElementById('purchase_id_product').value = purchaseId;
                document.getElementById('markAsPurchaseFormProduct').setAttribute('action', url);

                document.getElementById('total_amount_display_product').innerText = fmt.format(remaining);
                document.getElementById('paid_amount_product').value = fmt.format(remaining);

                const paidDisplay = document.getElementById('paid_amount_display_product');
                if (paidDisplay) paidDisplay.innerText = 'Paid: Rp. ' + fmt.format(paidAmount);
            }

            if (e.target.closest('.btn-mark-paid-freight')) {
                const btn = e.target.closest('.btn-mark-paid-freight');

                const purchaseId = btn.dataset.id;
                const url = btn.dataset.url;
                const totalAmount = parseFloat(btn.dataset.totalAmountFreight) || 0;
                const paidAmount = parseFloat(btn.dataset.paidAmountFreight) || 0;
                const remaining = Math.max(totalAmount - paidAmount, 0);

                const fmt = new Intl.NumberFormat('id-ID');

                document.getElementById('purchase_id_freight').value = purchaseId;
                document.getElementById('markAsPurchaseFormFreight').setAttribute('action', url);

                document.getElementById('total_amount_display_freight').innerText = fmt.format(remaining);
                document.getElementById('paid_amount_freight').value = fmt.format(remaining);

                const paidDisplay = document.getElementById('paid_amount_display_freight');
                if (paidDisplay) paidDisplay.innerText = 'Paid: Rp. ' + fmt.format(paidAmount);
            }
        });

        // ✅ BARU - Format angka yang benar
        ['product', 'freight'].forEach(type => {
            const input = document.getElementById(`paid_amount_${type}`);
            const display = document.getElementById(`paid_amount_display_${type}`);
            input.addEventListener('input', function() {
                let angka = this.value.replace(/\D/g, "") || "0";
                this.value = new Intl.NumberFormat('id-ID').format(angka);
                if (display) display.innerText = 'Paid: Rp. ' + this.value;
            });
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
                form.dataset.id = id;
                nameHolder.textContent = name;
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

        $('#modalChangeStatusProduct, #modalChangeStatusFreight').on('shown.bs.modal', function() {
            if ($.fn.select2) {
                $('#cash_bank_account_id_product, #cash_bank_account_id_freight').trigger('change.select2');
            }
        });

        $(document).on('click', '.btn-force-delete-owner', function() {
            const id = $(this).data('id');
            const name = $(this).data('name');
            const url = $(this).data('url');

            // Set judul & nomor purchase di modal
            $('#fd-purchase-number').text(name || `Purchase #${id}`);

            // Set action form ke URL force delete
            $('#forceDeleteOwnerPurchaseForm').attr('action', url);
        });
    </script>
@endpush
