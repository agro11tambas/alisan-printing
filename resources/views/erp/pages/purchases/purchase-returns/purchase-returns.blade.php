@extends('erp.layouts.main')

@push('styles')
    <style>
        @media (max-width: 768px) {

            #purchaseReturnTable td.desktop-only,
            #purchaseReturnTable th.desktop-only {
                display: none !important;
            }
        }

        #purchaseReturnTable {
            width: 100% !important;
            min-width: 0;
        }

        #purchaseReturnTable_wrapper .dataTables_scrollBody {
            background-image: none !important;
            height: 60vh !important;
            overflow-y: auto !important;
        }

        #deletedpurchaseReturnTable_wrapper .dataTables_scrollBody {
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

        #purchaseReturnTable tbody tr {
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
                <h5 class="m-b-10">Purchase</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Purchase</li>
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
                            <div class="col-lg-4">
                                <label for="search_type" class="fw-semibold fs-12">Filter By</label>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <select id="search_type" class="form-control"
                                            style="padding: 0.25rem 0.5rem; font-size: 0.875rem;">
                                            <option value="supplier">Supplier</option>
                                            <option value="purchase_number">Invoice</option>
                                            <option value="payment_status">Payment Status</option>
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
                                    href="#deleted-purchase-list" role="tab">Deleted Purchase Return</a>
                            </li>
                        </ul>
                        <div class="table-responsive">
                            <div class="tab-content">
                                <div class="tab-pane fade show active" id="purchase-list" role="tabpanel">
                                    <table class="table table-hover bg-transparent" id="purchaseReturnTable">
                                        <thead>
                                            <tr>
                                                <th></th>
                                                <th>Purchase Number</th>
                                                <th>Supplier</th>
                                                <th data-column="total_amount_product">Product Total</th>
                                                <th data-column="paid_amount_product">Product Paid</th>
                                                <th data-column="total_amount_freight">Freight Total</th>
                                                <th data-column="paid_amount_freight">Freight Paid</th>
                                                <th>Payment Status</th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                                <div class="tab-pane fade" id="deleted-purchase-list" role="tabpanel">
                                    <table class="table table-hover bg-transparent" id="deletedPurchaseReturnTable">
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
                        <h5 class="modal-title text-white" id="deleteModalLabel">Hapus Purchase Return</h5>
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

    <div class="modal fade-scale" id="modalRefundProduct" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="fw-bold mb-0">Mark as Refund (Product)</h5>
                    <a href="javascript:void(0)" class="avatar-text avatar-md bg-soft-danger close-icon"
                        data-bs-dismiss="modal"><i class="feather-x text-danger"></i></a>
                </div>
                <form method="POST" id="refundFormProduct" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="purchase_return_id_product" name="purchase_return_id">
                    <div class="modal-body">
                        <div class="row g-3 mb-2">
                            <div class="col-md-6">
                                <label for="transaction_type_product" class="fw-semibold">Purchase Return Account:</label>
                                <div class="input-group">
                                    <select class="form-select" id="transaction_type_product" data-select2-selector="tag"
                                        name="transaction_type">
                                        <option value="14" data-bg="bg-danger">Purchase Return</option>
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
                                    @php $bgColors = ['bg-danger','bg-warning','bg-primary','bg-indigo','bg-success']; @endphp
                                    {{-- <select class="form-select" id="cash_bank_account_id_product"
                                        data-select2-selector="tag" name="cash_bank_account_id">
                                        <option value="" disabled selected hidden>Pilih Cash/Bank Account</option>
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
                                <label for="refund_amount_product" class="fw-semibold">Refund Amount:</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="refund_amount_product"
                                        name="refund_amount" value="0">
                                </div>
                                <small class="text-danger d-none" id="error_refund_amount_product"></small>
                                <span class="fw-semibold fs-12" id="refund_amount_display_product">Refund: Rp. 0</span>
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
                        <button type="submit" class="btn btn-primary">Mark as Refund</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade-scale" id="modalRefundFreight" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="fw-bold mb-0">Mark as Refund (Freight)</h5>
                    <a href="javascript:void(0)" class="avatar-text avatar-md bg-soft-danger close-icon"
                        data-bs-dismiss="modal"><i class="feather-x text-danger"></i></a>
                </div>
                <form method="POST" id="refundFormFreight" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="purchase_return_id_freight" name="purchase_return_id">
                    <div class="modal-body">
                        <div class="row g-3 mb-2">
                            <div class="col-md-6">
                                <label for="transaction_type_freight" class="fw-semibold">Purchase Return Account:</label>
                                <div class="input-group">
                                    <select class="form-select" id="transaction_type_freight" data-select2-selector="tag"
                                        name="transaction_type">
                                        <option value="14" data-bg="bg-danger">Purchase Return</option>
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
                                <label for="cash_bank_account_id_freight" class="fw-semibold">Cash/Bank Account:</label>
                                <div class="input-group">
                                    @php $bgColors = ['bg-danger','bg-warning','bg-primary','bg-indigo','bg-success']; @endphp
                                    {{-- <select class="form-select" id="cash_bank_account_id_freight"
                                        data-select2-selector="tag" name="cash_bank_account_id">
                                        <option value="" disabled selected hidden>Pilih Cash/Bank Account</option>
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
                                <small class="text-danger d-none" id="error_cash_bank_account_id_freight"></small>
                            </div>
                            <div class="col-md-6">
                                <label for="refund_amount_freight" class="fw-semibold">Refund Amount:</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="refund_amount_freight"
                                        name="refund_amount" value="0">
                                </div>
                                <small class="text-danger d-none" id="error_refund_amount_freight"></small>
                                <span class="fw-semibold fs-12" id="refund_amount_display_freight">Refund: Rp. 0</span>
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
                        <button type="submit" class="btn btn-primary">Mark as Refund</button>
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
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {
            // ====================================================
            // 🔹 FORMAT PRODUK
            // ====================================================
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
                                    <th class="text-end">Freight</th>
                                    <th class="text-end">Total</th>
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

            const dataTable = $('#purchaseReturnTable').DataTable({
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
                    [8, 'desc']
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
                        data: 'purchase_number'
                    },
                    {
                        data: 'supplier'
                    },
                    {
                        data: 'total_amount_product'
                    },
                    {
                        data: 'refund_amount_product'
                    },
                    {
                        data: 'total_amount_freight'
                    },
                    {
                        data: 'refund_amount_freight'
                    },
                    {
                        data: 'payment_status'
                    },
                    {
                        data: 'return_date',
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

                // 🚫 Batalkan request sebelumnya jika masih berjalan
                if (currentRequest) {
                    currentRequest.abort();
                }

                currentRequest = $.ajax({
                    url: "{{ url('/erp/purchases/purchase-returns/data') }}",
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

            // SHOW / HIDE CUSTOM RANGE (non-custom langsung reload, custom nunggu Apply)
            $('#filter').on('change', function() {
                const val = $(this).val();

                if (val === 'custom') {
                    // cuma tampilkan range, BELUM reload
                    $('.custom-range').removeClass('d-none');
                } else {
                    $('.custom-range').addClass('d-none');
                    $('#start_date').val('');
                    $('#end_date').val('');
                    // preset (today, last_7_days, this_month, dll) langsung reload
                    reloadActiveTab();
                }
            });


            // APPLY
            $('#apply-filter').on('click', function() {
                reloadActiveTab();
            });

            // SEARCH KEYWORD
            // $('#search_keyword').on('keyup', function() {
            //     if ($('#purchaseListTabs .nav-link.active').attr('href') === '#deleted-purchase-list') {
            //         clearTimeout(searchTimer);
            //         searchTimer = setTimeout(() => {
            //             resetAndReloadDeleted();
            //         }, 250);
            //     }
            // });

            // 🔍 SEARCH KEYWORD → reload cuma saat ENTER
            $('#search_keyword').on('keypress', function(e) {
                if (e.which === 13) { // Enter
                    e.preventDefault();
                    reloadActiveTab(); // otomatis pilih tabel aktif (normal / deleted)
                }
            });

            // kalau dikosongkan → reload lagi semua data
            $('#search_keyword').on('input', function() {
                if ($(this).val().trim() === '') {
                    reloadActiveTab();
                }
            });

            // SEARCH TYPE
            $('#search_type').on('change', function() {
                const selected = $(this).val();

                if (selected === 'payment_status') {
                    $('#search_keyword').addClass('d-none').val('');
                    $('#search_payment_status').removeClass('d-none');
                } else {
                    $('#search_keyword').removeClass('d-none');
                    $('#search_payment_status').addClass('d-none').val('');
                }

                reloadActiveTab();
            });

            // PAYMENT STATUS
            $('#search_payment_status').on('change', function() {
                reloadActiveTab();
            });

            // CUSTOM DATE RANGE AUTO UPDATE
            // $('#start_date, #end_date').on('change', function() {
            //     if ($('#filter').val() === 'custom') reloadActiveTab();
            // });

            $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
                const target = $(e.target).attr('href');

                if (!deletedTableInitialized) initDeletedTable();
                reloadActiveTab();
            });


            $('#purchaseReturnTable tbody').on('click', 'td.dt-control', function() {
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

            $('#purchaseReturnTable tbody').on('click', 'tr', function(e) {
                if ($(e.target).closest('td.dt-control').length) return;

                let $tr = $(this);
                let row = dataTable.row($tr);

                $('#purchaseReturnTable tbody tr').removeClass('action-shown').next('.action-row').remove();

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
                if ($(e.target).closest('#purchaseReturnTable').length) return;
                $('#purchaseReturnTable tbody tr').removeClass('action-shown').next('.action-row').remove();
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
                    },
                ];

                @if (auth()->user()->role === 'Owner')
                    deletedColumns.push({
                        data: 'action',
                        orderable: false,
                        searchable: false
                    });
                @endif

                deletedTable = $('#deletedPurchaseReturnTable').DataTable({
                    processing: false,
                    serverSide: false,
                    scrollY: '60vh',
                    scrollCollapse: true,
                    paging: false,
                    searching: false,
                    info: false,
                    lengthChange: false,
                    data: [],
                    order: [
                        [4, 'asc']
                    ],
                    columns: deletedColumns
                });

                deletedTableInitialized = true;

                $('#deletedPurchaseReturnTable')
                    .closest('.dataTables_wrapper')
                    .find('.dataTables_scrollBody').on('scroll', function() {
                        const scrollTop = $(this).scrollTop();
                        const scrollHeight = $(this)[0].scrollHeight;
                        const clientHeight = $(this).height();

                        // Load earlier (70%) without delay
                        if (scrollTop + clientHeight >= scrollHeight * 0.70) {
                            loadMoreDeletedData();
                        }
                    });

                $('#deletedPurchaseReturnTable tbody').on('click', 'td.dt-control', function() {
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
                    url: "{{ url('/erp/purchases/purchase-returns/data-deleted') }}",
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
                        console.error("Error:", error);
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

                    // 🔥 Kalau paste di input note → jangan intercept
                    if (e.target.classList.contains('note-input')) {
                        return;
                    }

                    // 📌 Kalau bukan input note → intercept screenshot
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

                    // 🔥 Kalau paste DI INPUT NOTE → jangan intercept
                    if (e.target.classList.contains('note-input')) {
                        return;
                    }

                    // 📌 Intercept hanya kalau paste bukan text-note
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

            // ======================================================
            // 🔹 MARK AS REFUND PRODUCT (AJAX TANPA REFRESH HALAMAN)
            // ======================================================
            $('#refundFormProduct').on('submit', function(e) {
                e.preventDefault();

                const form = this;
                const url = form.action;
                const formData = new FormData(form);

                const notes = [];
                $('#proofPreviewContainerProduct .note-input').each(function() {
                    notes.push($(this).val());
                });

                pastedProofProductBlobs.forEach((blob, index) => {
                    formData.append('payment_proof[]', blob, `proof_${index + 1}.png`);
                    formData.append('note_per_image[]', notes[index] || '');
                });

                // Reset error
                $('#refundFormProduct small.text-danger').addClass('d-none').text('');

                let valid = true;
                const transactionType = $('#transaction_type_product').val()?.trim();
                const transactionDate = $('#transaction_date_product').val()?.trim();
                const cashBankAccount = $('#cash_bank_account_id_product').val()?.trim();
                const refundAmountRaw = $('#refund_amount_product').val()?.trim() || '0';
                const refundAmount = parseFloat(refundAmountRaw.replace(/\./g, '').replace(',', '.')) || 0;
                const remainingRaw = $('#total_amount_display_product').text().trim().replace(/\./g, '')
                    .replace(',', '.');
                const remainingAmount = parseFloat(remainingRaw) || 0;

                if (!transactionType) showError('error_transaction_type_product',
                    'Purchase Account wajib dipilih'), valid = false;
                if (!transactionDate) showError('error_transaction_date_product',
                    'Tanggal transaksi wajib diisi'), valid = false;
                if (!cashBankAccount) showError('error_cash_bank_account_id_product',
                    'Pilih Cash/Bank Account'), valid = false;
                if (!refundAmount || isNaN(refundAmount) || refundAmount <= 0) {
                    showError('error_refund_amount_product', 'Refund amount harus diisi dan lebih dari 0');
                    valid = false;
                } else if (refundAmount > remainingAmount) {
                    showError('error_refund_amount_product', 'Refund amount tidak boleh melebihi Balance');
                    valid = false;
                }

                if (!valid) return;

                // Bersihkan nilai angka sebelum kirim
                formData.set('refund_amount', refundAmount);

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
                            text: res.message ?? 'Refund produk berhasil disimpan!',
                            timer: 1800,
                            showConfirmButton: false
                        });

                        $('#modalRefundProduct').modal('hide');
                        form.reset();

                        // 🔁 reload DataTable tanpa refresh halaman
                        dataTable.clear();
                        allData = [];
                        currentPage = 0;
                        hasMoreData = true;
                        loadMoreData();
                    },
                    error: function(xhr) {
                        Swal.close();
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: xhr.responseJSON?.message ??
                                'Terjadi kesalahan saat menyimpan refund'
                        });
                    }
                });

                function showError(id, msg) {
                    const el = document.getElementById(id);
                    if (el) {
                        el.innerText = msg;
                        el.classList.remove('d-none');
                    }
                }
            });

            // ======================================================
            // 🔹 MARK AS REFUND FREIGHT (AJAX TANPA REFRESH HALAMAN)
            // ======================================================
            $('#refundFormFreight').on('submit', function(e) {
                e.preventDefault();

                const form = this;
                const url = form.action;
                const formData = new FormData(form);

                const notes = [];
                $('#proofPreviewContainerFreight .note-input').each(function() {
                    notes.push($(this).val());
                });

                pastedProofFreightBlobs.forEach((blob, index) => {
                    formData.append('payment_proof[]', blob, `proof_${index + 1}.png`);
                    formData.append('note_per_image[]', notes[index] || '');
                });

                $('#refundFormFreight small.text-danger').addClass('d-none').text('');

                let valid = true;
                const transactionType = $('#transaction_type_freight').val()?.trim();
                const transactionDate = $('#transaction_date_freight').val()?.trim();
                const cashBankAccount = $('#cash_bank_account_id_freight').val()?.trim();
                const refundAmountRaw = $('#refund_amount_freight').val()?.trim() || '0';
                const refundAmount = parseFloat(refundAmountRaw.replace(/\./g, '').replace(',', '.')) || 0;
                const remainingRaw = $('#total_amount_display_freight').text().trim().replace(/\./g, '')
                    .replace(',', '.');
                const remainingAmount = parseFloat(remainingRaw) || 0;

                if (!transactionType) showError('error_transaction_type_freight',
                    'Purchase Account wajib dipilih'), valid = false;
                if (!transactionDate) showError('error_transaction_date_freight',
                    'Tanggal transaksi wajib diisi'), valid = false;
                if (!cashBankAccount) showError('error_cash_bank_account_id_freight',
                    'Pilih Cash/Bank Account'), valid = false;
                if (!refundAmount || isNaN(refundAmount) || refundAmount <= 0) {
                    showError('error_refund_amount_freight', 'Refund amount harus diisi dan lebih dari 0');
                    valid = false;
                } else if (refundAmount > remainingAmount) {
                    showError('error_refund_amount_freight', 'Refund amount tidak boleh melebihi Balance');
                    valid = false;
                }

                if (!valid) return;

                // Bersihkan nilai angka sebelum kirim
                formData.set('refund_amount', refundAmount);

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
                            text: res.message ?? 'Refund freight berhasil disimpan!',
                            timer: 1800,
                            showConfirmButton: false
                        });

                        $('#modalRefundFreight').modal('hide');
                        form.reset();

                        // 🔁 reload DataTable tanpa refresh halaman
                        dataTable.clear();
                        allData = [];
                        currentPage = 0;
                        hasMoreData = true;
                        loadMoreData();
                    },
                    error: function(xhr) {
                        Swal.close();
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: xhr.responseJSON?.message ??
                                'Terjadi kesalahan saat menyimpan refund freight'
                        });
                    }
                });

                function showError(id, msg) {
                    const el = document.getElementById(id);
                    if (el) {
                        el.innerText = msg;
                        el.classList.remove('d-none');
                    }
                }
            });

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
                            text: res.message ?? 'Purchase Return berhasil dihapus!',
                            timer: 1800,
                            showConfirmButton: false
                        });

                        $('#modalDeletePurchase').modal('hide');

                        // 🔁 Reload tabel utama tanpa reload halaman
                        dataTable.clear();
                        allData = [];
                        currentPage = 0;
                        hasMoreData = true;
                        loadMoreData();

                        // 🔁 Kalau tab deleted aktif, reload juga
                        if ($('a[data-bs-toggle="tab"][href="#deleted-purchase-list"]').parent()
                            .hasClass('active')) {
                            resetAndReloadDeleted();
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



            // ========== FORCE DELETE PURCHASE RETURN ==========
            $('#formForceDeleteOrder').on('submit', function(e) {
                e.preventDefault();

                const form = this;
                const url = form.action;

                $.ajax({
                    url: url,
                    type: 'POST', // tetap POST + spoof method
                    data: $(form).serialize() + '&_method=DELETE',
                    success: function(res) {
                        Swal.close();
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: res.message ?? 'Purchase Return dihapus permanen!',
                            timer: 1800,
                            showConfirmButton: false
                        });

                        $('#modalForceDeleteOrder').modal('hide');

                        // 🔁 Refresh deleted table tanpa reload halaman
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
                                'Terjadi kesalahan saat menghapus permanen'
                        });
                    }
                });
            });

            // ========== RESTORE PURCHASE RETURN ==========
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
                            text: res.message ??
                                'Purchase Return berhasil dikembalikan!',
                            timer: 1800,
                            showConfirmButton: false
                        });

                        $('#modalRestoreOrder').modal('hide');

                        // 🔁 Refresh tabel utama & deleted tanpa reload halaman
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
                nameHolder.textContent = name;
            });
        });

        document.addEventListener('click', function(e) {
            if (e.target.closest('.btn-mark-refund-product')) {
                const btn = e.target.closest('.btn-mark-refund-product');
                const id = btn.dataset.id;
                const url = btn.dataset.url;
                const total = parseFloat(btn.dataset.totalAmount) || 0;
                const refunded = parseFloat(btn.dataset.paidAmount) || 0;
                const remaining = Math.max(total - refunded, 0);

                const fmt = new Intl.NumberFormat('id-ID');

                document.getElementById('purchase_return_id_product').value = id;
                document.getElementById('refundFormProduct').action = url;
                document.getElementById('total_amount_display_product').innerText = fmt.format(remaining);
                document.getElementById('refund_amount_product').value = fmt.format(remaining);

                const display = document.getElementById('refund_amount_display_product');
                if (display) display.innerText = 'Refunded: Rp. ' + fmt.format(refunded);
            }

            if (e.target.closest('.btn-mark-refund-freight')) {
                const btn = e.target.closest('.btn-mark-refund-freight');
                const id = btn.dataset.id;
                const url = btn.dataset.url;
                const total = parseFloat(btn.dataset.totalAmount) || 0;
                const refunded = parseFloat(btn.dataset.paidAmount) || 0;
                const remaining = Math.max(total - refunded, 0);

                const fmt = new Intl.NumberFormat('id-ID');

                document.getElementById('purchase_return_id_freight').value = id;
                document.getElementById('refundFormFreight').action = url;
                document.getElementById('total_amount_display_freight').innerText = fmt.format(remaining);
                document.getElementById('refund_amount_freight').value = fmt.format(remaining);

                const display = document.getElementById('refund_amount_display_freight');
                if (display) display.innerText = 'Refunded: Rp. ' + fmt.format(refunded);
            }
        });

        const refundInputProduct = document.getElementById("refund_amount_product");
        refundInputProduct.addEventListener("input", function() {
            let angka = this.value.replace(/\D/g, "") || "0";
            this.value = new Intl.NumberFormat('id-ID').format(angka);

            const display = document.getElementById('refund_amount_display_product');
            if (display) display.innerText = 'Refund: Rp. ' + this.value;
        });

        const refundInputFreight = document.getElementById("refund_amount_freight");
        refundInputFreight.addEventListener("input", function() {
            let angka = this.value.replace(/\D/g, "") || "0";
            this.value = new Intl.NumberFormat('id-ID').format(angka);

            const display = document.getElementById('refund_amount_display_freight');
            if (display) display.innerText = 'Refund: Rp. ' + this.value;
        });

        // document.getElementById('refundFormProduct').addEventListener('submit', function(e) {
        //     e.preventDefault();

        //     document.querySelectorAll('#refundFormProduct small.text-danger').forEach(el => {
        //         el.classList.add('d-none');
        //         el.innerText = '';
        //     });

        //     let valid = true;

        //     const transactionType = document.getElementById('transaction_type_product')?.value.trim() || '';
        //     const transactionDate = document.getElementById('transaction_date_product')?.value.trim() || '';
        //     const cashBankAccount = document.getElementById('cash_bank_account_id_product')?.value.trim() || '';

        //     // const paidAmountRaw = document.getElementById('paid_amount_product')?.value.trim() || '0';
        //     // const paidAmount = paidAmountRaw.replace(/\./g, "");

        //     // const remainingRaw = document.getElementById('total_amount_display_product')?.innerText.trim().replace(
        //     //     /\./g, "") || '0';
        //     // const remainingAmount = parseInt(remainingRaw) || 0;

        //     const refundAmountRaw = document.getElementById('refund_amount_product')?.value.trim() || '0';
        //     // Bersihkan format Indonesia: hapus titik (pemisah ribuan), ganti koma dengan titik (desimal)
        //     const refundAmount = parseFloat(refundAmountRaw.replace(/\./g, "").replace(",", ".")) || 0;

        //     const remainingRaw = document.getElementById('total_amount_display_product')?.innerText.trim()
        //         .replace(/\./g, "").replace(",", ".") || '0';
        //     const remainingAmount = parseFloat(remainingRaw) || 0; // ✅ parseFloat!

        //     if (!transactionType) {
        //         const el = document.getElementById('error_transaction_type_product');
        //         if (el) {
        //             el.innerText = 'Purchase Account wajib dipilih';
        //             el.classList.remove('d-none');
        //         }
        //         valid = false;
        //     }

        //     if (!transactionDate) {
        //         const el = document.getElementById('error_transaction_date_product');
        //         if (el) {
        //             el.innerText = 'Tanggal transaksi wajib diisi';
        //             el.classList.remove('d-none');
        //         }
        //         valid = false;
        //     }

        //     if (!cashBankAccount) {
        //         const el = document.getElementById('error_cash_bank_account_id_product');
        //         if (el) {
        //             el.innerText = 'Pilih Cash/Bank Account';
        //             el.classList.remove('d-none');
        //         }
        //         valid = false;
        //     }

        //     if (!refundAmount || isNaN(refundAmount) || refundAmount <= 0) {
        //         const el = document.getElementById('error_refund_amount_product');
        //         if (el) {
        //             el.innerText = 'refund amount harus diisi dan lebih dari 0';
        //             el.classList.remove('d-none');
        //         }
        //         valid = false;
        //     } else if (refundAmount > remainingAmount) { // ✅ Float vs Float
        //         const el = document.getElementById('error_refund_amount_product');
        //         if (el) {
        //             el.innerText = 'refund amount tidak boleh melebihi Balance';
        //             el.classList.remove('d-none');
        //         }
        //         valid = false;
        //     }

        //     // 💾 Validasi file
        //     const fileInput = document.getElementById('payment_proof');
        //     const file = fileInput.files[0];
        //     if (file) {
        //         const allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
        //         const ext = file.name.split('.').pop().toLowerCase();

        //         if (!allowedExt.includes(ext)) {
        //             Swal.fire({
        //                 icon: 'error',
        //                 title: 'Format Tidak Valid!',
        //                 text: 'File harus berupa JPG, JPEG, PNG, atau WEBP.',
        //             });
        //             return; // 🚫 stop total
        //         }

        //         if (file.size > 2 * 1024 * 1024) {
        //             Swal.fire({
        //                 icon: 'error',
        //                 title: 'File Terlalu Besar!',
        //                 text: 'Ukuran file maksimal 2MB.',
        //             });
        //             return; // 🚫 stop total
        //         }
        //     }

        //     if (!valid) {
        //         return false;
        //     }

        //     const cleanRefund = refundAmountRaw
        //         .replace(/\./g, '') // Hapus titik (pemisah ribuan)
        //         .replace(',', '.'); // Ganti koma dengan titik (desimal)

        //     // Validasi final sebelum submit
        //     const parsedRefund = parseFloat(cleanRefund);
        //     if (isNaN(parsedRefund) || parsedRefund <= 0) {
        //         const el = document.getElementById('error_refund_amount_product');
        //         el.innerText = 'refund amount tidak valid';
        //         el.classList.remove('d-none');
        //         return false;
        //     }

        //     // Set nilai yang sudah dibersihkan ke input
        //     document.getElementById('refund_amount_product').value = cleanRefund;
        //     this.submit();

        // });

        // document.getElementById('refundFormFreight').addEventListener('submit', function(e) {
        //     e.preventDefault();

        //     document.querySelectorAll('#refundFormFreight small.text-danger').forEach(el => {
        //         el.classList.add('d-none');
        //         el.innerText = '';
        //     });

        //     let valid = true;

        //     const transactionType = document.getElementById('transaction_type_freight')?.value.trim() || '';
        //     const transactionDate = document.getElementById('transaction_date_freight')?.value.trim() || '';
        //     const cashBankAccount = document.getElementById('cash_bank_account_id_freight')?.value.trim() || '';

        //     const refundAmountRaw = document.getElementById('refund_amount_freight')?.value.trim() || '0';
        //     const refundAmount = parseFloat(refundAmountRaw.replace(/\./g, "").replace(",", ".")) || 0;

        //     const remainingRaw = document.getElementById('total_amount_display_freight')?.innerText.trim()
        //         .replace(/\./g, "").replace(",", ".") || '0';
        //     const remainingAmount = parseFloat(remainingRaw) || 0;

        //     if (!transactionType) {
        //         const el = document.getElementById('error_transaction_type_freight');
        //         el.innerText = 'Purchase Account wajib dipilih';
        //         el.classList.remove('d-none');
        //         valid = false;
        //     }

        //     if (!transactionDate) {
        //         const el = document.getElementById('error_transaction_date_freight');
        //         el.innerText = 'Tanggal transaksi wajib diisi';
        //         el.classList.remove('d-none');
        //         valid = false;
        //     }

        //     if (!cashBankAccount) {
        //         const el = document.getElementById('error_cash_bank_account_id_freight');
        //         el.innerText = 'Pilih Cash/Bank Account';
        //         el.classList.remove('d-none');
        //         valid = false;
        //     }

        //     if (!refundAmount || isNaN(refundAmount) || refundAmount <= 0) {
        //         const el = document.getElementById('error_refund_amount_freight');
        //         el.innerText = 'refund amount harus diisi dan lebih dari 0';
        //         el.classList.remove('d-none');
        //         valid = false;
        //     } else if (refundAmount > remainingAmount) {
        //         const el = document.getElementById('error_refund_amount_freight');
        //         el.innerText = 'refund amount tidak boleh melebihi Balance';
        //         el.classList.remove('d-none');
        //         valid = false;
        //     }

        //     if (!valid) return false;

        //     // Bersihkan angka sebelum kirim
        //     const cleanRefund = refundAmountRaw.replace(/\./g, '').replace(',', '.');
        //     const parsedRefund = parseFloat(cleanRefund);
        //     if (isNaN(parsedRefund) || parsedRefund <= 0) {
        //         const el = document.getElementById('error_refund_amount_freight');
        //         el.innerText = 'refund amount tidak valid';
        //         el.classList.remove('d-none');
        //         return false;
        //     }

        //     document.getElementById('refund_amount_freight').value = cleanRefund;
        //     this.submit();
        // });

        ['product', 'freight'].forEach(type => {
            const input = document.getElementById(`refund_amount_${type}`);
            const display = document.getElementById(`refund_amount_display_${type}`);
            const form = document.getElementById(`refundForm${type.charAt(0).toUpperCase() + type.slice(1)}`);

            if (input) {
                input.addEventListener('input', function() {
                    let angka = this.value.replace(/\D/g, '') || '0';
                    this.value = new Intl.NumberFormat('id-ID').format(angka);
                    if (display) display.innerText = 'Refund: Rp. ' + this.value;
                });
            }

            if (form && input) {
                form.addEventListener('submit', function() {
                    input.value = input.value.replace(/\./g, '');
                });
            }
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
                nameHolder.textContent = name;
            });
        });

        $('#modalRefundProduct, #modalRefundFreight').on('shown.bs.modal', function() {
            if ($.fn.select2) {
                $('#cash_bank_account_id_product, #cash_bank_account_id_freight').trigger('change.select2');
            }
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
