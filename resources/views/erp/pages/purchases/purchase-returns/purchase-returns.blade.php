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
            /* background: transparent !important; */
            background-image: none !important;
        }

        #deletedPurchaseReturnTable_wrapper .dataTables_scrollBody {
            /* background: transparent !important; */
            background-image: none !important;
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
    <div class="main-content">
        <div class="row">
            <div class="col-lg-12">
                <div class="card stretch stretch-full">
                    <div class="card-body p-0">
                        <div class="row g-3 p-4 justify-content-between">
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
                            <div class="col-lg-4">
                                <label for="search_type" class="fw-semibold fs-12">Filter By</label>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <select id="search_type" class="form-control"
                                            style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                            <option value="purchase_number">Invoice</option>
                                            <option value="supplier">Supplier</option>
                                            <option value="payment_status">Payment Status</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="text" id="search_keyword" name="search_keyword"
                                            class="form-control search-input"
                                            style="padding: 0.5rem 1rem; font-size: 0.875rem;" placeholder="Search..." />
                                        <select id="search_payment_status" class="form-control search-input d-none"
                                            style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                            <option value="">All</option>
                                            <option value="Paid">Paid</option>
                                            <option value="Unpaid">Unpaid</option>
                                            <option value="Partially Paid">Partially Paid</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <ul class="nav nav-tabs mb-3" id="purchaseListTabs" role="tablist">
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
                                    <table class="table table-hover bg-transparent" id="purchaseReturnTable">
                                        <thead>
                                            <tr>
                                                <th></th>
                                                <th>Purchase Number</th>
                                                <th>Supplier</th>
                                                <th>Total Amount</th>
                                                <th>Paid Amount</th>
                                                <th>Remaining Amount</th>
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
    <div class="modal fade" id="modalDeletePurchase" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
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

    {{-- <div class="modal fade-scale" id="modalChangeStatus" tabindex="-1" aria-labelledby="modalChangeStatus" aria-hidden="true" data-bs-dismiss="ou">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <!--! BEGIN: [modal-header] !-->
            <div class="modal-header">
                <h2 class="d-flex flex-column mb-0">
                    <span class="fs-18 fw-bold mb-1">Mark As Refund</span>
                </h2>
                <a href="javascript:void(0)" class="avatar-text avatar-md bg-soft-danger close-icon" data-bs-dismiss="modal">
                    <i class="feather-x text-danger"></i>
                </a>
            </div>
            <!--! BEGIN: [modal-body] !-->
            <form method="POST" id="markAsPurchaseForm">
                @csrf
                <input type="hidden" id="purchase_return_id" name="purchase_return_id">

                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="transaction_type" class="fw-semibold">Purchase:</label>
                            <div class="input-group">
                                <select class="form-select form-control max-select" data-select2-selector="tag" id="transaction_type" name="transaction_type">
                                    <option value="14" data-bg="bg-danger">Purchase Return</option>
                                </select>
                            </div>
                            <small class="text-danger d-none" id="error_transaction_type"></small>
                        </div>
                        <div class="col-md-6">
                            <label for="transaction_date" class="fw-semibold">Transaction Date:</label>
                            <div class="input-group">
                                <input type="date" id="transaction_date" name="transaction_date" class="form-control" value="{{ date('Y-m-d') }}">
                            </div>
                            <small class="text-danger d-none" id="error_transaction_date"></small>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="cash_bank_account_id" class="fw-semibold">Cash/Bank Account:</label>
                            <div class="input-group">
                                @php
                                $bgColors = ['bg-danger', 'bg-warning', 'bg-primary', 'bg-indigo', 'bg-success'];
                                @endphp
                                <select class="form-select form-control max-select" data-select2-selector="tag" name="cash_bank_account_id" id="cash_bank_account_id">
                                    <option value="" disabled selected hidden>Pilih Bank atau Cash Account</option>
                                    @foreach ($cashAccounts as $cash)
                                    @php
                                    $bg = $bgColors[$loop->index % count($bgColors)];
                                    @endphp
                                    <option value="{{ $cash->id }}" data-bg="{{ $bg }}">Cash - {{ $cash->type }}</option>
                                    @endforeach
                                    @foreach ($bankAccounts as $bank)
                                    @php
                                    $bg = $bgColors[$loop->index % count($bgColors)];
                                    @endphp
                                    <option value="{{ $bank->id }}" data-bg="{{ $bg }}">Bank - {{ $bank->type }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <small class="text-danger d-none" id="error_cash_bank_account_id"></small>
                        </div>
                        <div class="col-md-6">
                            <label for="paid_amount" class="fw-semibold">Paid Amount:</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="paid_amount" name="paid_amount" value="0">
                            </div>
                            <small class="text-danger d-none" id="error_paid_amount"></small>
                            <span class="fw-semibold fs-12" id="paid_amount_display">Paid: Rp. 0</span>
                        </div>
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
</div> --}}

    <div class="modal fade-scale" id="modalRefundProduct" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="fw-bold mb-0">Mark as Refund (Product)</h5>
                    <a href="javascript:void(0)" class="avatar-text avatar-md bg-soft-danger close-icon"
                        data-bs-dismiss="modal"><i class="feather-x text-danger"></i></a>
                </div>
                <form method="POST" id="refundFormProduct">
                    @csrf
                    <input type="hidden" id="purchase_return_id_product" name="purchase_return_id">
                    <div class="modal-body">
                        <div class="row g-3 mb-3">
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
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="cash_bank_account_id_product" class="fw-semibold">Cash/Bank Account:</label>
                                <div class="input-group">
                                    @php $bgColors = ['bg-danger','bg-warning','bg-primary','bg-indigo','bg-success']; @endphp
                                    <select class="form-select" id="cash_bank_account_id_product"
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
                <form method="POST" id="refundFormFreight">
                    @csrf
                    <input type="hidden" id="purchase_return_id_freight" name="purchase_return_id">
                    <div class="modal-body">
                        <div class="row g-3 mb-3">
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
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="cash_bank_account_id_freight" class="fw-semibold">Cash/Bank Account:</label>
                                <div class="input-group">
                                    @php $bgColors = ['bg-danger','bg-warning','bg-primary','bg-indigo','bg-success']; @endphp
                                    <select class="form-select" id="cash_bank_account_id_freight"
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
                        <td class="text-end">${Number(p.price) + Number(p.freight)}</td>
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

            const dataTable = $('#purchaseReturnTable').DataTable({
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
                    url: "{{ url('/erp/purchases/purchase-returns/data') }}",
                    data: function(d) {
                        d.filter = $('#filter').val();
                        d.start_date = $('#start_date').val();
                        d.end_date = $('#end_date').val();
                        d.search_type = $('#search_type').val();
                        d.search_keyword = $('#search_keyword').val();
                        d.payment_status = $('#search_payment_status').val();
                    }
                },
                columns: [{
                        className: 'dt-control text-center',
                        orderable: false,
                        data: null,
                        defaultContent: '',
                        width: "20px"
                    },
                    // {
                    //     data: 'DT_RowIndex',
                    //     orderable: false,
                    //     searchable: false
                    // },
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
                        data: 'refund_amount'
                    },
                    {
                        data: 'remaining_amount'
                    },
                    {
                        data: 'payment_status'
                    },
                    // {
                    //     data: 'action',
                    //     orderable: false,
                    //     searchable: false,
                    //     className: 'action-cell text-end'
                    // }
                ]
            });

            let deletedTable = null;

            $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
                if ($(e.target).attr('href') === '#deleted-purchase-list') {
                    if (!deletedTable) {
                        deletedTable = $('#deletedPurchaseReturnTable').DataTable({
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
                            ajax: "{{ url('/erp/purchases/purchase-returns/data-deleted') }}",
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
                                @if (auth()->user()->role === 'Owner')
                                    {
                                        data: 'action',
                                        orderable: false,
                                        searchable: false
                                    }
                                @endif
                            ]
                        });

                        $('#deletedPurchaseReturnTable tbody').on('click', 'td.dt-control', function() {
                            let tr = $(this).closest('tr');
                            let row = deletedTable.row(tr);

                            if (row.child.isShown()) {
                                row.child.hide();
                                tr.removeClass('shown');
                            } else {
                                row.child(formatProducts(row.data().products)).show();
                                tr.addClass('shown');
                            }
                        });
                    } else {
                        deletedTable.ajax.reload();
                    }
                }
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

            $(document).on('click', function(e) {
                if ($(e.target).closest('#purchaseReturnTable').length) return;

                $('#purchaseReturnTable tbody tr').removeClass('action-shown').next('.action-row').remove();
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
                const selected = $(this).val();
                if (selected === 'payment_status') {
                    $('#search_keyword').addClass('d-none').val('');
                    $('#search_payment_status').removeClass('d-none');
                } else {
                    $('#search_keyword').removeClass('d-none');
                    $('#search_payment_status').addClass('d-none').val('');
                }
                dataTable.ajax.reload();
            });

            $('#search_keyword').on('keyup', function() {
                if ($('#search_type').val() !== 'payment_status') {
                    dataTable.ajax.reload();
                }
            });

            $('#search_payment_status').on('change', function() {
                if ($('#search_type').val() === 'payment_status') {
                    dataTable.ajax.reload();
                }
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
            // if (e.target.closest('.btn-mark-paid')) {
            //     const button = e.target.closest('.btn-mark-paid');
            //     const purchaseReturnId = button.getAttribute('data-id');
            //     const url = button.getAttribute('data-url');
            //     const totalAmount = parseFloat(button.getAttribute('data-total-amount')) || 0;
            //     const paidAmount = parseFloat(button.getAttribute('data-paid-amount')) || 0;
            //     const remainingAmount = totalAmount - paidAmount;

            //     // isi hidden + form action
            //     document.getElementById('purchase_return_id').value = purchaseReturnId;
            //     document.getElementById('markAsPurchaseForm').setAttribute('action', url);

            //     // tampilkan balance
            //     document.getElementById('total_amount_display').innerText = new Intl.NumberFormat('id-ID').format(
            //         remainingAmount);

            //     // isi otomatis Paid Amount = balance
            //     const formatted = new Intl.NumberFormat('id-ID').format(remainingAmount);
            //     document.getElementById('paid_amount').value = formatted;

            //     // update label Paid: Rp ...
            //     const paidDisplay = document.getElementById('paid_amount_display');
            //     if (paidDisplay) {
            //         paidDisplay.innerText = 'Paid: Rp. ' + formatted;
            //     }
            // }

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

        // const paidInput = document.getElementById("paid_amount");
        // paidInput.addEventListener("input", function() {
        //     let angka = this.value.replace(/\D/g, "") || "0";
        //     this.value = new Intl.NumberFormat('id-ID').format(angka);
        // });

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

        // document.getElementById('markAsPurchaseForm').addEventListener('submit', function(e) {
        //     e.preventDefault();

        //     document.querySelectorAll('#markAsPurchaseForm small.text-danger').forEach(el => {
        //         el.classList.add('d-none');
        //         el.innerText = '';
        //     });

        //     let valid = true;

        //     let transactionType = document.getElementById('transaction_type').value.trim();
        //     let transactionDate = document.getElementById('transaction_date').value.trim();
        //     let cashBankAccount = document.getElementById('cash_bank_account_id').value.trim();

        //     let paidAmountRaw = document.getElementById('paid_amount').value.trim();
        //     let paidAmount = paidAmountRaw.replace(/\./g, "");

        //     // ambil balance (remaining) dari tampilan Balance:
        //     let remainingRaw = document.getElementById('total_amount_display').innerText.trim().replace(/\./g, "");
        //     let remainingAmount = parseInt(remainingRaw) || 0;

        //     if (!transactionType) {
        //         document.getElementById('error_transaction_type').innerText = 'Account wajib dipilih';
        //         document.getElementById('error_transaction_type').classList.remove('d-none');
        //         valid = false;
        //     }

        //     if (!transactionDate) {
        //         document.getElementById('error_transaction_date').innerText = 'Tanggal transaksi wajib diisi';
        //         document.getElementById('error_transaction_date').classList.remove('d-none');
        //         valid = false;
        //     }

        //     if (!cashBankAccount) {
        //         document.getElementById('error_cash_bank_account_id').innerText = 'Pilih cash atau bank account';
        //         document.getElementById('error_cash_bank_account_id').classList.remove('d-none');
        //         valid = false;
        //     }

        //     if (!paidAmount || isNaN(paidAmount) || parseInt(paidAmount) <= 0) {
        //         document.getElementById('error_paid_amount').innerText = 'Paid amount harus diisi dan lebih dari 0';
        //         document.getElementById('error_paid_amount').classList.remove('d-none');
        //         valid = false;
        //     } else if (parseInt(paidAmount) > remainingAmount) {
        //         document.getElementById('error_paid_amount').innerText = 'Paid amount tidak boleh melebihi Balance';
        //         document.getElementById('error_paid_amount').classList.remove('d-none');
        //         valid = false;
        //     }

        //     if (!valid) return;

        //     document.getElementById('paid_amount').value = paidAmount;

        //     this.submit();
        // });

        document.getElementById('refundFormProduct').addEventListener('submit', function(e) {
            e.preventDefault();

            document.querySelectorAll('#refundFormProduct small.text-danger').forEach(el => {
                el.classList.add('d-none');
                el.innerText = '';
            });

            let valid = true;

            const transactionType = document.getElementById('transaction_type_product').value.trim();
            const transactionDate = document.getElementById('transaction_date_product').value.trim();
            const cashBankAccount = document.getElementById('cash_bank_account_id_product').value.trim();
            const refundAmountRaw = document.getElementById('refund_amount_product').value.trim();
            const refundAmount = refundAmountRaw.replace(/\./g, '');
            const remainingRaw = document.getElementById('total_amount_display_product').innerText.trim().replace(
                /\./g, '');
            const remainingAmount = parseInt(remainingRaw) || 0;

            if (!transactionType) {
                const el = document.getElementById('error_transaction_type_product');
                el.innerText = 'Purchase Return Account wajib dipilih';
                el.classList.remove('d-none');
                valid = false;
            }

            if (!transactionDate) {
                const el = document.getElementById('error_transaction_date_product');
                el.innerText = 'Tanggal transaksi wajib diisi';
                el.classList.remove('d-none');
                valid = false;
            }

            if (!cashBankAccount) {
                const el = document.getElementById('error_cash_bank_account_id_product');
                el.innerText = 'Pilih Cash/Bank Account';
                el.classList.remove('d-none');
                valid = false;
            }

            if (!refundAmount || isNaN(refundAmount) || parseInt(refundAmount) <= 0) {
                const el = document.getElementById('error_refund_amount_product');
                if (el) {
                    el.innerText = 'Refund amount harus diisi dan lebih dari 0';
                    el.classList.remove('d-none');
                }
                valid = false;
            } else if (parseInt(refundAmount) > remainingAmount) {
                const el = document.getElementById('error_refund_amount_product');
                if (el) {
                    el.innerText = 'Refund amount tidak boleh melebihi Balance';
                    el.classList.remove('d-none');
                }
                valid = false;
            }

            if (!valid) return;

            document.getElementById('refund_amount_product').value = refundAmount;

            this.submit();
        });

        document.getElementById('refundFormFreight').addEventListener('submit', function(e) {
            e.preventDefault();

            document.querySelectorAll('#refundFormFreight small.text-danger').forEach(el => {
                el.classList.add('d-none');
                el.innerText = '';
            });

            let valid = true;

            const transactionType = document.getElementById('transaction_type_freight').value.trim();
            const transactionDate = document.getElementById('transaction_date_freight').value.trim();
            const cashBankAccount = document.getElementById('cash_bank_account_id_freight').value.trim();
            const refundAmountRaw = document.getElementById('refund_amount_freight').value.trim();
            const refundAmount = refundAmountRaw.replace(/\./g, '');
            const remainingRaw = document.getElementById('total_amount_display_freight').innerText.trim().replace(
                /\./g, '');
            const remainingAmount = parseInt(remainingRaw) || 0;

            if (!transactionType) {
                const el = document.getElementById('error_transaction_type_freight');
                el.innerText = 'Purchase Return Account wajib dipilih';
                el.classList.remove('d-none');
                valid = false;
            }

            if (!transactionDate) {
                const el = document.getElementById('error_transaction_date_freight');
                el.innerText = 'Tanggal transaksi wajib diisi';
                el.classList.remove('d-none');
                valid = false;
            }

            if (!cashBankAccount) {
                const el = document.getElementById('error_cash_bank_account_id_freight');
                el.innerText = 'Pilih Cash/Bank Account';
                el.classList.remove('d-none');
                valid = false;
            }

            if (!refundAmount || isNaN(refundAmount) || parseInt(refundAmount) <= 0) {
                const el = document.getElementById('error_refund_amount_freight');
                if (el) {
                    el.innerText = 'Refund amount harus diisi dan lebih dari 0';
                    el.classList.remove('d-none');
                }
                valid = false;
            } else if (parseInt(refundAmount) > remainingAmount) {
                const el = document.getElementById('error_refund_amount_freight');
                if (el) {
                    el.innerText = 'Refund amount tidak boleh melebihi Balance';
                    el.classList.remove('d-none');
                }
                valid = false;
            }

            if (!valid) return;

            document.getElementById('refund_amount_freight').value = refundAmount;
            this.submit();
        });

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

        // document.querySelector("form").addEventListener("submit", function() {
        //     paidInput.value = paidInput.value.replace(/\./g, "");
        // });

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
    </script>
@endpush
