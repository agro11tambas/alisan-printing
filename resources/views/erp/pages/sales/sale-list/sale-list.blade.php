@extends('erp.layouts.main')

@push('styles')
    <style>
        @media (max-width: 768px) {

            #saleListTable td.desktop-only,
            #saleListTable th.desktop-only {
                display: none !important;
            }
        }

        #saleListTable {
            width: 100% !important;
            min-width: 0;
        }

        #saleListTable td.action-cell {
            display: none;
        }

        #saleListTable_wrapper .dataTables_scrollBody {
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

        #saleListTable tbody tr {
            animation: fadeIn 0.3s ease-in;
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
    <div class="main-content">
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
                                            <option value="order_number">Order Number</option>
                                            <option value="customer">Customer</option>
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
                                            <option value="">All</option>
                                            <option value="Paid">Paid</option>
                                            <option value="Unpaid">Unpaid</option>
                                            <option value="Partially Paid">Partially Paid</option>
                                            <option value="Overdue">Overdue</option>
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
                        <ul class="nav nav-tabs mb-3" id="saleListTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="sale-list-tab" data-bs-toggle="tab" href="#sale-list"
                                    role="tab">Sale List</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="deleted-sale-list-tab" data-bs-toggle="tab"
                                    href="#deleted-sale-list" role="tab">Deleted Sale List</a>
                            </li>
                        </ul>
                        <div class="table-responsive">
                            <div class="tab-content">
                                <div class="tab-pane fade show active" id="sale-list" role="tabpanel">
                                    <table class="table table-hover bg-transparent" id="saleListTable">
                                        <thead>
                                            <tr>
                                                <th></th>
                                                <th>Invoice Number</th>
                                                <th>Customer</th>
                                                <th>Grand Total</th>
                                                <th>Paid Amount</th>
                                                <th>Remaining Amount</th>
                                                <th>Payment Status</th>
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
                                <div class="tab-pane fade" id="deleted-sale-list" role="tabpanel">
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
                        <h5 class="modal-title text-white" id="deleteModalLabel">Hapus Order</h5>
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
                <form method="POST" id="markAsSaleForm">
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
                                    {{-- <select class="form-select form-control max-select" data-select2-selector="tag"
                                        name="cash_bank_account_id" id="cash_bank_account_id">
                                        <option value="" disabled selected hidden>Pilih Bank atau Cash Account
                                        </option>
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
                            <th class="text-end">Shipped</th>
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
                    <td class="text-end">${p.progress_qty}</td>
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

            // ========== SALE LIST TABLE (dengan Lazy Load) ==========
            let allData = [];
            let currentPage = 0;
            let isLoading = false;
            let hasMoreData = true;

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
                    [7, 'desc']
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
                    {
                        data: 'remaining_amount'
                    },
                    {
                        data: 'payment_status'
                    },
                    {
                        data: 'order_date', // tambahkan kolom ini
                        visible: false, // disembunyikan dari tampilan
                        searchable: false // tidak perlu di-search
                    }
                ]
            });

            // Fungsi load data dari server
            function loadMoreData() {
                if (isLoading || !hasMoreData) return;

                isLoading = true;
                console.log('🔄 Loading page:', currentPage + 1);

                $.ajax({
                    url: "{{ url('/erp/sales/sale-list/data') }}",
                    type: 'GET',
                    data: {
                        start: currentPage * 15,
                        length: 15,
                        filter: $('#filter').val(),
                        start_date: $('#start_date').val(),
                        end_date: $('#end_date').val(),
                        search_type: $('#search_type').val(),
                        search_keyword: $('#search_keyword').val(),
                        payment_status: $('#search_payment_status').val(),
                        due_date_order: $('#due_date_order').val()
                    },
                    success: function(response) {
                        console.log('✅ Response received:', response);

                        if (response && response.data && response.data.length > 0) {
                            allData = allData.concat(response.data);
                            dataTable.clear();
                            dataTable.rows.add(allData);
                            dataTable.draw(false);
                            currentPage++;
                            console.log('📦 Total rows:', allData.length);
                        } else {
                            hasMoreData = false;
                            console.log('⚠️ No more data');
                        }

                        isLoading = false;
                    },
                    error: function(xhr, status, error) {
                        console.error('❌ Error:', error);
                        console.error('Response:', xhr.responseText);
                        isLoading = false;
                    }
                });
            }

            // Load data pertama kali
            loadMoreData();

            // Lazy load saat scroll
            let scrollTimeout = null;
            $('.dataTables_scrollBody').on('scroll', function() {
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
                dataTable.clear().draw();
                loadMoreData();
            }

            // Event handlers untuk filter SALE LIST
            $('#filter').on('change', function() {
                const isDeletedTab = $('a[data-bs-toggle="tab"][href="#deleted-sale-list"]').parent()
                    .hasClass('active');

                if ($(this).val() === 'custom') {
                    $('.custom-range').removeClass('d-none');
                } else {
                    $('.custom-range').addClass('d-none');
                    if (!isDeletedTab) {
                        resetAndReload();
                    } else {
                        resetAndReloadDeleted();
                    }
                }
            });

            $('#apply-filter').on('click', function() {
                const isDeletedTab = $('a[data-bs-toggle="tab"][href="#deleted-sale-list"]').parent()
                    .hasClass('active');

                if (!isDeletedTab) {
                    resetAndReload();
                } else {
                    resetAndReloadDeleted();
                }
            });

            $('#search_type').on('change', function() {
                const selected = $(this).val();
                const isDeletedTab = $('a[data-bs-toggle="tab"][href="#deleted-sale-list"]').parent()
                    .hasClass('active');

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

                if (!isDeletedTab) {
                    resetAndReload();
                } else {
                    resetAndReloadDeleted();
                }
            });

            $('#due_date_order').on('change', function() {
                const isDeletedTab = $('a[data-bs-toggle="tab"][href="#deleted-sale-list"]').parent()
                    .hasClass('active');

                if ($('#search_type').val() === 'due_date') {
                    if (!isDeletedTab) {
                        resetAndReload();
                    } else {
                        resetAndReloadDeleted();
                    }
                }
            });

            // Debounce untuk search keyword
            let searchTimeout = null;
            $('#search_keyword').on('keyup', function() {
                const isDeletedTab = $('a[data-bs-toggle="tab"][href="#deleted-sale-list"]').parent()
                    .hasClass('active');

                if ($('#search_type').val() !== 'payment_status') {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        if (!isDeletedTab) {
                            resetAndReload();
                        } else {
                            resetAndReloadDeleted();
                        }
                    }, 500);
                }
            });

            $('#search_payment_status').on('change', function() {
                const isDeletedTab = $('a[data-bs-toggle="tab"][href="#deleted-sale-list"]').parent()
                    .hasClass('active');

                if ($('#search_type').val() === 'payment_status') {
                    if (!isDeletedTab) {
                        resetAndReload();
                    } else {
                        resetAndReloadDeleted();
                    }
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

            // Close action dropdown saat klik di luar
            $(document).on('click', function(e) {
                if ($(e.target).closest('#saleListTable').length) return;
                $('#saleListTable tbody tr').removeClass('action-shown').next('.action-row').remove();
            });

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
                        data: 'customer'
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

                // Lazy load saat scroll untuk deleted table
                $('#deletedSaleListTable').closest('.dataTables_scrollBody').on('scroll', function() {
                    clearTimeout(scrollTimeout);

                    const scrollTop = $(this).scrollTop();
                    const scrollHeight = $(this)[0].scrollHeight;
                    const clientHeight = $(this).height();

                    scrollTimeout = setTimeout(() => {
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
                console.log('🔄 Loading deleted page:', deletedCurrentPage + 1);

                $.ajax({
                    url: "{{ url('/erp/sales/sale-list/data-deleted') }}",
                    type: 'GET',
                    data: {
                        start: deletedCurrentPage * 15,
                        length: 15,
                        filter: $('#filter').val(),
                        start_date: $('#start_date').val(),
                        end_date: $('#end_date').val(),
                        search_type: $('#search_type').val(),
                        search_keyword: $('#search_keyword').val(),
                        payment_status: $('#search_payment_status').val(),
                        due_date_order: $('#due_date_order').val()
                    },
                    success: function(response) {
                        console.log('✅ Deleted response received:', response);

                        if (response && response.data && response.data.length > 0) {
                            deletedAllData = deletedAllData.concat(response.data);
                            deletedTable.clear();
                            deletedTable.rows.add(deletedAllData);
                            deletedTable.draw(false);
                            deletedCurrentPage++;
                            console.log('📦 Total deleted rows:', deletedAllData.length);
                        } else {
                            deletedHasMoreData = false;
                            console.log('⚠️ No more deleted data');
                        }

                        deletedIsLoading = false;
                    },
                    error: function(xhr, status, error) {
                        console.error('❌ Error loading deleted:', error);
                        console.error('Response:', xhr.responseText);
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

            // Tab switch handler
            $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
                if ($(e.target).attr('href') === '#deleted-sale-list') {
                    if (!deletedTableInitialized) {
                        initDeletedTable();
                        loadMoreDeletedData();
                    } else {
                        resetAndReloadDeleted();
                    }
                }
            });
        });

        // Modal handlers
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
                nameHolder.textContent = name;
            });
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
                nameHolder.textContent = name;
            });
        });

        document.addEventListener('click', function(e) {
            if (e.target.closest('.btn-mark-paid')) {
                const button = e.target.closest('.btn-mark-paid');
                const orderId = button.getAttribute('data-id');
                const url = button.getAttribute('data-url');
                const totalAmount = parseFloat(button.getAttribute('data-total-amount')) || 0;
                const paidAmount = parseFloat(button.getAttribute('data-paid-amount')) || 0;
                const remainingAmount = totalAmount - paidAmount;

                document.getElementById('order_id').value = orderId;
                document.getElementById('markAsSaleForm').setAttribute('action', url);

                document.getElementById('total_amount_display').innerText = new Intl.NumberFormat('id-ID').format(
                    remainingAmount);

                const formatted = new Intl.NumberFormat('id-ID').format(remainingAmount);
                document.getElementById('paid_amount').value = formatted;

                document.getElementById('paid_amount_display').innerText = 'Paid: Rp. ' + formatted;
            }
        });

        const paidInput = document.getElementById("paid_amount");

        paidInput.addEventListener("input", function() {
            let angka = this.value.replace(/\D/g, "") || "0";
            this.value = new Intl.NumberFormat('id-ID').format(angka);
        });

        document.getElementById('markAsSaleForm').addEventListener('submit', function(e) {
            e.preventDefault();

            document.querySelectorAll('#markAsSaleForm small.text-danger').forEach(el => {
                el.classList.add('d-none');
                el.innerText = '';
            });

            let valid = true;

            let transactionType = document.getElementById('transaction_type').value.trim();
            let transactionDate = document.getElementById('transaction_date').value.trim();
            let cashBankAccount = document.getElementById('cash_bank_account_id').value.trim();

            let paidAmountRaw = document.getElementById('paid_amount').value.trim();
            let paidAmount = paidAmountRaw.replace(/\./g, "");

            if (!transactionType) {
                document.getElementById('error_transaction_type').innerText = 'Account wajib dipilih';
                document.getElementById('error_transaction_type').classList.remove('d-none');
                valid = false;
            }

            if (!transactionDate) {
                document.getElementById('error_transaction_date').innerText = 'Tanggal transaksi wajib diisi';
                document.getElementById('error_transaction_date').classList.remove('d-none');
                valid = false;
            }

            if (!cashBankAccount) {
                document.getElementById('error_cash_bank_account_id').innerText = 'Pilih cash atau bank account';
                document.getElementById('error_cash_bank_account_id').classList.remove('d-none');
                valid = false;
            }

            if (!paidAmount || isNaN(paidAmount) || parseInt(paidAmount) <= 0) {
                document.getElementById('error_paid_amount').innerText = 'Paid amount harus diisi dan lebih dari 0';
                document.getElementById('error_paid_amount').classList.remove('d-none');
                valid = false;
            }

            if (!valid) return;

            document.getElementById('paid_amount').value = paidAmount;

            this.submit();
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

        $(document).on('click', '.btn-share-invoice', function() {
            const url = $(this).data('url');
            window.open(url, '_blank');
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

        $('#modalChangeStatus').on('shown.bs.modal', function() {
            $('#cash_bank_account_id').trigger('change.select2');
        });
    </script>
@endpush
