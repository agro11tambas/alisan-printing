@extends('erp.layouts.main')

@push('styles')
    <style>
        @media (max-width: 768px) {

            #saleOrderTable td.desktop-only,
            #saleOrderTable th.desktop-only,
            #editedOrderTable td.desktop-only,
            #editedOrderTable th.desktop-only {
                display: none !important;
            }
        }

        #saleOrderTable,
        #editedOrderTable {
            width: 100% !important;
            min-width: 0;
        }

        #saleOrderTable_wrapper .dataTables_scrollHeadInner,
        #editedOrderTable_wrapper .dataTables_scrollHeadInner,
        #saleOrderTable_wrapper .dataTables_scrollHeadInner table,
        #editedOrderTable_wrapper .dataTables_scrollHeadInner table {
            width: 100% !important;
        }

        #saleOrderTable td.action-cell,
        #editedOrderTable td.action-cell {
            display: none;
        }

        #saleOrderTable_wrapper .dataTables_scrollBody,
        #editedOrderTable_wrapper .dataTables_scrollBody {
            background-image: none !important;
            height: 60vh !important;
            overflow-y: auto !important;
        }

        #deletedOrderTable_wrapper .dataTables_scrollBody {
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

        #saleOrderTable tbody tr,
        #editedOrderTable tbody tr {
            animation: fadeIn 0.3s ease-in;
        }

        table.dataTable td.customer-cell {
            white-space: normal !important;
            word-wrap: break-word !important;
            max-width: 200px;
        }

        .static-action-menu {
            padding: 6px;
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

            #saleOrderTable_wrapper,
            #editedOrderTable_wrapper,
            #deletedOrderTable_wrapper {
                display: none !important;
            }

            .sale-tabs {
                display: none !important;
            }
        }

        .sale-mobile-card {
            border-radius: 0px;
            padding: 6px 14px;
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

        .sale-mobile-info {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            width: 100%;
        }

        .sale-info-left {
            flex: 1;
            min-width: 0;
        }

        .sale-info-right {
            flex: 0 0 auto;
            text-align: right;
        }
        @include('erp.pages.partials.transaction-list-mobile-header-styles')
    </style>
@endpush

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Sale Orders</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Sale Orders</li>
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
                    <a href="/erp/sales/sale-orders/create-order" class="btn btn-primary">
                        <i class="feather-plus me-2"></i>
                        <span>Create Sale Order</span>
                    </a>
                </div>
            </div>
            <div class="d-md-none d-flex align-items-center">
                <a href="/erp/sales/sale-orders/create-order"
                    class="btn btn-primary transaction-list-mobile-action">
                    <i class="feather-plus"></i>
                    <span>Create Sale Order</span>
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
                                            <option value="customer">Customer</option>
                                            <option value="order_number">Order Number</option>
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
                        <div class="table-responsive">
                            <table class="table table-hover bg-transparent" id="saleOrderTable">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th>Invoice Number</th>
                                        <!-- <th class="d-none d-md-table-cell">Order Date</th> -->
                                        <th class="d-none d-md-table-cell">Customer</th>
                                        <th>Grand Total</th>
                                        <th>User</th>
                                        <th class="text-center">Mode</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>

                                </tbody>
                            </table>
                        </div>
                        {{-- MOBILE SALE LIST --}}
                        <div id="saleOrderMobile" class="d-md-none px-0 pb-2">
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
                        <p>Apakah Anda yakin ingin menghapus Order <strong id="OrderName"></strong>?</p>
                        <p class="text-muted">Data yang dihapus tidak dapat dikembalikan.</p>
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
                        <span class="fs-18 fw-bold mb-1">Mark As Sale List</span>
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
                        <div class="row g-3 mb-2">
                            <div class="col-md-12">
                                <label for="order_number" class="fw-semibold fs-12">Invoice Number</label>
                                <input type="text" id="modal_order_number_display" class="form-control" readonly>
                                <input type="hidden" id="modal_order_number" name="order_number">
                            </div>
                        </div>
                        <div class="row g-3 mb-2">
                            <div class="col-md-6">
                                <label for="order_date" class="fw-semibold fs-12" id="modal_order_date">Order
                                    Date</label>
                                <input type="datetime-local" id="order_date" name="order_date" class="form-control"
                                    value="{{ date('Y-m-d\TH:i') }}">
                            </div>
                            <div class="col-md-6">
                                <label for="due_date_option" class="fw-semibold fs-12">Due Date</label>
                                <select id="due_date_option" style="font-size: 14px;" name="due_date_option"
                                    class="form-select">
                                    <option value="none">Tidak ada due date</option>
                                    <option value="today">Hari ini</option>
                                    <option value="1_week" selected>1 Minggu</option>
                                    <option value="1_month">1 Bulan</option>
                                    <option value="3_months">3 Bulan</option>
                                    <option value="custom">Custom</option>
                                </select>
                            </div>
                            <div class="col-md-12 d-none" id="custom_due_date_wrapper">
                                <label for="custom_due_date" class="fw-semibold fs-12">Input Due Date</label>
                                <input type="date" id="custom_due_date" name="custom_due_date" class="form-control">
                            </div>
                        </div>

                        <div class="row g-3 mb-2">
                            <div class="col-md-12">
                                <label for="paid_amount" class="fw-semibold">Paid Amount:</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="paid_amount" name="paid_amount"
                                        value="0">
                                </div>
                                <div class="mt-1">
                                    <input type="checkbox" class="btn-check" id="pay_full" autocomplete="off">
                                    <label class="btn btn-sm btn-outline-primary fs-12" for="pay_full">
                                        Bayar Full
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="row g-3 mb-2">
                            <div class="col-md-6">
                            </div>
                        </div>

                        <div class="row g-3 mb-2">
                            <div class="col-md-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="use_customer_deposit"
                                        name="use_customer_deposit">
                                    <label class="form-check-label fw-semibold" for="use_customer_deposit">
                                        Use Customer Deposit
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-2 d-none" id="customer_deposit_container">
                            <div class="col-md-12">
                                <label for="deposit_used" class="fw-semibold">Customer Deposit Amount:</label>
                                <input type="text" class="form-control" id="deposit_used" name="deposit_used"
                                    value="0">
                                <small class="text-muted">Max: <span id="max_deposit_display">Rp. 0</span></small><br>
                                <small class="text-info">Customer Total Deposit:
                                    <span id="customer_deposit_display">Rp. 0</span>
                                </small>
                            </div>
                        </div>

                        <div class="row g-3 mb-2">
                            <div class="col-md-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="use_write_off_only"
                                        name="use_write_off_only" value="1">
                                    <label class="form-check-label fw-semibold" for="use_write_off_only">
                                        Write Off (hapus sisa piutang)
                                    </label>
                                </div>
                                <small class="text-muted">Sisa setelah pembayaran dan deposit akan dianggap write-off.</small>
                            </div>
                        </div>

                        <div class="row g-3 mb-2">
                            <div class="col-md-6">
                                <label for="transaction_type" class="fw-semibold">Sale:</label>
                                <div class="input-group">
                                    <select class="form-select form-control max-select" data-select2-selector="tag"
                                        id="transaction_type" name="transaction_type">
                                        <option value="11" data-bg="bg-danger">Sale Account</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="transaction_date" class="fw-semibold">Transaction Date:</label>
                                <input type="date" id="transaction_date" name="transaction_date" class="form-control"
                                    value="{{ date('Y-m-d') }}">
                            </div>
                        </div>
                        <div class="row g-3 mb-2">
                            <div class="col-md-12">
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
                                        <option value="" selected>Pilih Bank atau Cash Account</option>
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
                                    </select>
                                </div>
                                <div id="cash_bank_account_error" class="text-danger fs-12 mt-1 d-none">
                                    Cash/Bank Account wajib dipilih.
                                </div>
                            </div>
                        </div>
                        {{-- <div class="row g-3 mb-2">
                            <div class="col-md-12">
                                <label for="notes" class="fw-semibold">Note:</label>
                                <textarea class="form-control" name="notes" id="notes" rows="3"></textarea>
                            </div>
                        </div> --}}
                    </div>
                    <div class="modal-footer d-flex justify-content-between">
                        <div class="text-nowrap">
                            <p class="m-0">Remaining:</p>
                            <h5 class="fw-semibold text-danger mb-0 text-nowrap" id="total_amount_display">0</h5>
                        </div>
                        <button type="submit" class="btn btn-primary">Mark As Sale List</button>
                    </div>
                </form>
            </div>
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

            let allData = [];
            let currentPage = 0;
            let isLoading = false;
            let hasMoreData = true;

            const dataTable = $('#saleOrderTable').DataTable({
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
                    [6, 'desc']
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
                        data: 'user'
                    },
                    {
                        data: 'mode',
                        className: 'text-center align-middle'
                    },
                    {
                        data: 'notes'
                    },
                    {
                        data: 'created_at', // tambahkan kolom ini
                        visible: false, // disembunyikan dari tampilan
                        searchable: false // tidak perlu di-search
                    }
                ]
            });

            let searchTimer = null;
            let currentRequest = null;
            // ========== FUNGSI LOAD DATA ==========
            function loadMoreData() {
                if (isLoading || !hasMoreData) return;
                isLoading = true;

                // 🚫 Batalkan request sebelumnya jika masih jalan
                if (currentRequest) {
                    currentRequest.abort();
                }

                currentRequest = $.ajax({
                    url: "{{ url('/erp/sales/sale-orders/data') }}",
                    type: 'GET',
                    data: {
                        start: currentPage * 50,
                        length: 50,
                        filter: $('#filter').val(),
                        start_date: $('#start_date').val(),
                        end_date: $('#end_date').val(),
                        search_type: $('#search_type').val(),
                        search_keyword: $('#search_keyword').val(),
                        payment_status: $('#search_payment_status').val()
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
                            renderMobileFromAllData();
                            requestAnimationFrame(() => dataTable.columns.adjust());
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

            // ========== LAZY LOAD SCROLL ==========

            $('.dataTables_scrollBody').on('scroll', function() {
                const scrollTop = $(this).scrollTop();
                const scrollHeight = $(this)[0].scrollHeight;
                const clientHeight = $(this).height();

                // Load earlier (70%) without delay
                if (scrollTop + clientHeight >= scrollHeight * 0.70) {
                    loadMoreData();
                }
            });

            // ========== FILTER HANDLERS ==========
            function resetAndReload() {
                allData = [];
                currentPage = 0;
                hasMoreData = true;
                dataTable.clear().draw();
                $('#saleOrderMobile').html('');
                loadMoreData();
            }

            $('#search_payment_status, #filter, #start_date, #end_date')
                .on('input change', function() {
                    clearTimeout(searchTimer);
                    searchTimer = setTimeout(() => {
                        const keyword = $('#search_keyword').val().trim();
                        const paymentStatus = $('#search_payment_status').val();
                        const startDate = $('#start_date').val();
                        const endDate = $('#end_date').val();
                        const filter = $('#filter').val();

                        // tampilkan custom date range kalau pilih custom
                        if (filter === 'custom') {
                            $('.custom-range').removeClass('d-none');
                        } else {
                            $('.custom-range').addClass('d-none');
                        }

                        // ⛔ kalau semua input kosong → jangan reload
                        if (!keyword && !paymentStatus && !startDate && !endDate) return;

                        // ✅ kalau ada isi → reload ulang
                        allData = [];
                        currentPage = 0;
                        hasMoreData = true;
                        dataTable.clear().draw();
                        loadMoreData();
                    }, 200);
                });

            $('#apply-filter').on('click', function() {
                resetAndReload();
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
                // resetAndReload();
            });

            // 
            // $('#search_keyword').on('keyup', function() {
            //     if ($('#search_type').val() !== 'payment_status') {
            //         clearTimeout(searchTimeout);
            //         searchTimeout = setTimeout(() => resetAndReload(), 500);
            //     }
            // });

            // ENTER ONLY search
            $('#search_keyword').on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    resetAndReload();
                }
            });

            // Auto reload jika dikosongkan
            $('#search_keyword').on('input', function() {
                if ($(this).val().trim() === '') {
                    resetAndReload();
                }
            });


            $('#search_payment_status').on('change', function() {
                if ($('#search_type').val() === 'payment_status') resetAndReload();
            });

            // ========== EXPAND ROW DETAIL ==========
            $('#saleOrderTable tbody').on('click', 'td.dt-control', function() {
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

            // ========== ACTION ROW ==========
            // Handler-nya sudah disediakan global lewat initRowActionHandler('#saleOrderTable')
            // di layout. Jangan didobel di sini: handler kedua ikut menghapus baris menu saat
            // tombol di dalamnya diklik, sehingga tombol lepas dari DOM sebelum Bootstrap
            // sempat membuka modal-nya.

            // ========== DELETE ORDER ==========
            function closeSaleOrderActionMenu() {
                $('#saleOrderTable tbody tr')
                    .removeClass('action-shown action-active')
                    .next('.action-row').remove();
                $('.sale-mobile-card').removeClass('active');
            }

            function removeSaleOrderRow(id) {
                if (!id) return;

                id = String(id);

                // buang dari cache data
                allData = allData.filter(item => String(item.id) !== id);

                // buang dari DataTable
                const $row = $('#saleOrderTable tbody tr').filter(function() {
                    const rowData = dataTable.row(this).data();
                    return rowData && String(rowData.id) === id;
                });

                if ($row.length) {
                    dataTable.row($row).remove().draw(false);
                }

                // buang dari tampilan mobile
                $(`#saleOrderMobile .sale-mobile-card[data-id="${id}"]`).remove();

                if (!allData.length) {
                    $('#saleOrderMobile').html(
                        '<div class="text-center text-muted py-2">No sale data</div>');
                }
            }

            $(document).on('submit', '#formDeleteOrder', function(e) {
                e.preventDefault();

                const form = this;
                const $form = $(form);
                const $submitBtn = $form.find('button[type="submit"]');

                // cegah double submit
                if ($submitBtn.prop('disabled')) return;
                $submitBtn.prop('disabled', true);

                const url = $form.attr('action');
                // id diambil dari tombol yang diklik; fallback ke potongan terakhir URL
                const orderId = form.dataset.id || (url || '').split('/').pop();
                const orderName = $('#OrderName').text();

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: $form.serialize(),
                    success: function(res) {
                        // Tutup modal DULU, baru tampilkan notifikasi. Kalau dibalik,
                        // backdrop modal sering nyangkut dan halaman jadi tidak bisa diklik.
                        hideDeleteOrderModal(function() {
                            Swal.close();
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: (res && res.message) ?
                                    res.message :
                                    `Order ${orderName} berhasil dihapus.`,
                                timer: 1800,
                                showConfirmButton: false
                            });
                        });

                        closeSaleOrderActionMenu();
                        removeSaleOrderRow(orderId);
                    },
                    error: function(xhr) {
                        hideDeleteOrderModal(function() {
                            Swal.close();
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal!',
                                text: (xhr.responseJSON && xhr.responseJSON.message) ?
                                    xhr.responseJSON.message :
                                    'Terjadi kesalahan saat menghapus order.'
                            });
                        });
                    },
                    complete: function() {
                        $submitBtn.prop('disabled', false);
                    }
                });
            });

            function renderMobileFromAllData() {
                if (window.innerWidth >= 768) return;

                const container = $('#saleOrderMobile');
                container.html('');

                if (!allData.length) {
                    container.html('<div class="text-center text-muted py-2">No sale data</div>');
                    return;
                }

                allData.forEach(row => {
                    container.append(`
                        <div class="sale-mobile-card" data-id="${row.id}">
                            <div class="sale-mobile-main">
                                <div class="sale-mobile-header">
                                    <div class="sale-invoice">${row.order_number}</div>
                                    <span class="sale-status">${row.payment_status}</span>
                                </div>

                                <div class="sale-customer-mobile">
                                    ${row.customer_mobile}
                                </div>

                                <div class="sale-mobile-info">
                                    <div class="sale-info-left">        
                                        <div class="sale-grand">${row.grand_total}</div>
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


            $(document).on('click', '.sale-mobile-card', function(e) {
                // kalau klik button / link di dropdown → jangan toggle
                if ($(e.target).closest('.sale-mobile-action, button, a').length) return;

                const card = $(this);

                // tutup card lain
                $('.sale-mobile-card').not(card).removeClass('active');

                // toggle card ini
                card.toggleClass('active');
            });

            $(document).on('click', function(e) {
                // kalau klik di dalam card atau dropdown → abaikan
                if ($(e.target).closest('.sale-mobile-card').length) return;

                // kalau klik di mana saja di luar → tutup semua
                $('.sale-mobile-card').removeClass('active');
            });

        });

        // Tutup modal delete dengan aman: pakai instance Bootstrap, bersihkan sisa backdrop,
        // lalu jalankan callback setelah modal benar-benar tertutup.
        function hideDeleteOrderModal(callback) {
            const modalEl = document.getElementById('modalDeleteOrder');

            if (!modalEl) {
                if (callback) callback();
                return;
            }

            const done = function() {
                // sisa backdrop / body lock yang nyangkut bikin halaman tidak bisa diklik
                if (!document.querySelector('.modal.show')) {
                    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
                    document.body.classList.remove('modal-open');
                    document.body.style.removeProperty('overflow');
                    document.body.style.removeProperty('padding-right');
                }
                if (callback) callback();
            };

            const instance = (typeof bootstrap !== 'undefined' && bootstrap.Modal) ?
                bootstrap.Modal.getInstance(modalEl) :
                null;

            if (!modalEl.classList.contains('show')) {
                done();
                return;
            }

            if (!instance) {
                $(modalEl).modal('hide');
                setTimeout(done, 400);
                return;
            }

            modalEl.addEventListener('hidden.bs.modal', done, {
                once: true
            });

            // fallback kalau event hidden tidak pernah datang (transisi ketimpa SweetAlert dll)
            setTimeout(function() {
                if (!modalEl.classList.contains('show')) return;
                modalEl.classList.remove('show');
                modalEl.style.display = 'none';
                done();
            }, 600);

            instance.hide();
        }

        // Isi target form delete langsung dari tombol yang diklik (tidak cuma mengandalkan
        // relatedTarget), supaya URL tidak pernah ketinggalan dari order yang sudah dihapus.
        document.addEventListener('click', function(e) {
            const button = e.target.closest('.btn-delete[data-bs-target="#modalDeleteOrder"]');
            if (!button) return;

            const form = document.getElementById('formDeleteOrder');
            const nameHolder = document.getElementById('OrderName');
            if (!form) return;

            form.action = button.getAttribute('data-url') || '';
            form.dataset.id = button.getAttribute('data-id') || '';
            if (nameHolder) nameHolder.textContent = button.getAttribute('data-name') || '';

            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.disabled = false;
        });

        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('modalDeleteOrder');
            const form = document.getElementById('formDeleteOrder');
            const nameHolder = document.getElementById('OrderName');

            if (!modal || !form) return;

            modal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                if (!button) return;

                form.action = button.getAttribute('data-url') || form.action;
                form.dataset.id = button.getAttribute('data-id') || form.dataset.id;
                if (nameHolder) nameHolder.textContent = button.getAttribute('data-name') || '';
            });
        });

        $(document).on('shown.bs.modal', '#modalChangeStatus', function() {
            $('#payment_status').trigger('change');
        });

        document.addEventListener('click', async function(e) {
            if (e.target.closest('.btn-mark-sale')) {
                const button = e.target.closest('.btn-mark-sale');
                const orderId = button.getAttribute('data-id');
                const url = button.getAttribute('data-url');
                const totalAmount = parseFloat(button.getAttribute('data-total-amount')) || 0;
                const paidAmount = parseFloat(button.getAttribute('data-paid-amount')) || 0;
                const customerDeposit = parseFloat(button.getAttribute('data-deposit')) || 0;
                const remainingAmount = Math.max(0, totalAmount - paidAmount);

                saleOrderOutstanding = remainingAmount;
                saleOrderCustomerDeposit = customerDeposit;

                $('#use_customer_deposit').prop('checked', false);
                $('#customer_deposit_container').addClass('d-none');
                $('#deposit_used').val('0');
                $('#use_write_off_only').prop('checked', false);

                // 🔥 Paid amount default 0, diisi manual (kecuali centang "Bayar Full")
                $('#pay_full').prop('checked', false).prop('disabled', false);
                $('#paid_amount').prop('readonly', false).val('0');
                $('#cash_bank_account_id').val('').trigger('change');
                clearCashBankAccountError();
                $('#customer_deposit_display').text('Rp. ' + new Intl.NumberFormat('id-ID').format(customerDeposit));
                $('#max_deposit_display').text('Rp. ' + new Intl.NumberFormat('id-ID').format(
                    Math.min(remainingAmount, customerDeposit)
                ));

                const orderDate = document.getElementById('order_date').value;

                const response = await fetch(`/erp/sales/generate-invoice-number?date=${orderDate}`);
                const data = await response.json();

                document.getElementById('modal_order_number_display').value = data.invoice_number;
                document.getElementById('modal_order_number').value = button.getAttribute('data-order-number');
                document.getElementById('order_id').value = orderId;
                document.getElementById('markAsSaleForm').setAttribute('action', url);
                updateSaleOrderPaymentSummary();
            }
        });

        $(document).on('click', '.btn-view-invoice', function() {
            const url = $(this).data('url');
            window.open(url, '_blank');
        });

        $(document).on('click', '.btn-share-wa', async function() {
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
                // ???? ambil HTML invoice
                const html = await fetch(invoiceUrl).then(r => r.text());

                const temp = document.createElement('div');
                temp.style.position = 'fixed';
                temp.style.left = '-99999px';
                temp.innerHTML = html;
                document.body.appendChild(temp);

                const invoiceContent = temp.querySelector('#invoiceContent');
                if (!invoiceContent) throw new Error('invoiceContent tidak ditemukan');

                // ???? convert ke image
                const canvas = await html2canvas(invoiceContent, {
                    scale: 2,
                    backgroundColor: '#ffffff'
                });

                document.body.removeChild(temp);

                const imageData = canvas.toDataURL('image/jpeg', 0.95);

                // ???? upload ke server
                const response = await fetch('{{ route('invoice.convert') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        image: imageData,
                        order_id: orderId,
                        order_number: invoiceNo
                    })
                });

                const result = await response.json();
                if (!result.success) return;

                const message = [
                    `Kepada *${business}*`,
                    `Berikut Invoice *${invoiceNo}*`,
                    result.url.replace('https://', ''),
                    ``,
                    `*1) Diwajibkan Melunasi Tagihan*`,
                    `Terlebih dahulu sebelum proses produksi dimulai.`,
                    ``,
                    `*2) Setelah pembayaran diterima*`,
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

        let saleOrderOutstanding = 0;
        let saleOrderCustomerDeposit = 0;
        const paidInput = document.getElementById("paid_amount");

        function parseSalePaymentAmount(value) {
            return parseInt(String(value || '').replace(/\D/g, ''), 10) || 0;
        }

        function updateSaleOrderPaymentSummary() {
            const cashPaid = parseSalePaymentAmount($('#paid_amount').val());
            const depositUsed = $('#use_customer_deposit').is(':checked')
                ? parseSalePaymentAmount($('#deposit_used').val())
                : 0;
            const remaining = $('#use_write_off_only').is(':checked')
                ? 0
                : Math.max(0, saleOrderOutstanding - cashPaid - depositUsed);

            $('#total_amount_display').text('Rp. ' + new Intl.NumberFormat('id-ID').format(remaining));
        }

        // 🔥 Paid amount default 0 & diisi manual, kecuali checkbox "Bayar Full" dicentang
        function isSalePayFull() {
            return $('#pay_full').is(':checked');
        }

        function setSalePaidAmount(amount) {
            $('#paid_amount').val(new Intl.NumberFormat('id-ID').format(Math.max(0, amount)));
        }

        // Kalau "Bayar Full" aktif -> isi otomatis sisa tagihan (dikurangi deposit yang dipakai).
        // Kalau tidak aktif -> biarkan nilai manual user, atau reset ke 0 saat forceReset.
        function syncSalePaidAmount(forceReset = false) {
            const depositUsed = $('#use_customer_deposit').is(':checked') ?
                parseSalePaymentAmount($('#deposit_used').val()) :
                0;
            const maxPaid = Math.max(0, saleOrderOutstanding - depositUsed);

            if (isSalePayFull()) {
                setSalePaidAmount(maxPaid);
            } else if (forceReset) {
                setSalePaidAmount(0);
            } else {
                // biarkan nilai manual, cuma dijaga tidak melebihi sisa tagihan
                setSalePaidAmount(Math.min(parseSalePaymentAmount($('#paid_amount').val()), maxPaid));
            }

            $('#paid_amount').prop('readonly', isSalePayFull());
            updateSaleOrderPaymentSummary();
        }

        $('#pay_full').on('change', function() {
            syncSalePaidAmount(true);
        });

        paidInput.addEventListener("input", function() {
            let angka = this.value.replace(/\D/g, "") || "0";
            const depositUsed = $('#use_customer_deposit').is(':checked')
                ? parseSalePaymentAmount($('#deposit_used').val())
                : 0;
            angka = Math.min(parseInt(angka, 10) || 0, Math.max(0, saleOrderOutstanding - depositUsed)).toString();
            this.value = new Intl.NumberFormat('id-ID').format(angka);
            updateSaleOrderPaymentSummary();
        });

        $('#use_customer_deposit').on('change', function() {
            if ($(this).is(':checked')) {
                $('#use_write_off_only').prop('checked', false);
                $('#customer_deposit_container').removeClass('d-none');
                const maxDeposit = Math.min(saleOrderOutstanding, saleOrderCustomerDeposit);
                $('#deposit_used').val(new Intl.NumberFormat('id-ID').format(maxDeposit));
            } else {
                $('#customer_deposit_container').addClass('d-none');
                $('#deposit_used').val('0');
            }
            syncSalePaidAmount();
        });

        $('#deposit_used').on('input', function() {
            const maxDeposit = Math.min(saleOrderOutstanding, saleOrderCustomerDeposit);
            const depositUsed = Math.min(parseSalePaymentAmount(this.value), maxDeposit);
            this.value = new Intl.NumberFormat('id-ID').format(depositUsed);
            syncSalePaidAmount();
        });

        $('#use_write_off_only').on('change', function() {
            if ($(this).is(':checked')) {
                $('#use_customer_deposit').prop('checked', false);
                $('#customer_deposit_container').addClass('d-none');
                $('#deposit_used').val('0');
                $('#pay_full').prop('checked', false).prop('disabled', true);
            } else {
                $('#pay_full').prop('disabled', false);
            }
            syncSalePaidAmount(true);
        });

        paidInput.addEventListener("input", function() {
            let angka = this.value.replace(/\D/g, "") || "0";
            this.value = new Intl.NumberFormat('id-ID').format(angka);
        });

        function clearCashBankAccountError() {
            $('#cash_bank_account_id').removeClass('is-invalid');
            $('#cash_bank_account_id').next('.select2').find('.select2-selection').removeClass('is-invalid');
            $('#cash_bank_account_error').addClass('d-none');
        }

        $('#cash_bank_account_id').on('change', function() {
            if (this.value) {
                clearCashBankAccountError();
            }
        });

        document.getElementById("markAsSaleForm").addEventListener("submit", function(event) {
            const cashBankAccount = document.getElementById('cash_bank_account_id');
            const paidAmount = parseSalePaymentAmount(paidInput.value);

            if (paidAmount > 0 && !cashBankAccount.value) {
                event.preventDefault();
                cashBankAccount.classList.add('is-invalid');
                $('#cash_bank_account_id').next('.select2').find('.select2-selection').addClass('is-invalid');
                $('#cash_bank_account_error').removeClass('d-none');
                cashBankAccount.focus();
                return;
            }

            clearCashBankAccountError();
            paidInput.value = paidInput.value.replace(/\./g, "");
            document.getElementById("deposit_used").value =
                document.getElementById("deposit_used").value.replace(/\./g, "");
        });

        document.addEventListener("DOMContentLoaded", function() {
            const dueDateOption = document.getElementById("due_date_option");
            const customWrapper = document.getElementById("custom_due_date_wrapper");
            const orderDateInput = document.getElementById("order_date");
            const customDueDateInput = document.getElementById("custom_due_date");

            function updateDueDate() {
                const orderDate = new Date(orderDateInput.value);
                let dueDate = null;

                switch (dueDateOption.value) {
                    case "today":
                        dueDate = orderDate;
                        break;
                    case "1_week":
                        dueDate = new Date(orderDate);
                        dueDate.setDate(orderDate.getDate() + 7);
                        break;
                    case "1_month":
                        dueDate = new Date(orderDate);
                        dueDate.setMonth(orderDate.getMonth() + 1);
                        break;
                    case "3_months":
                        dueDate = new Date(orderDate);
                        dueDate.setMonth(orderDate.getMonth() + 3);
                        break;
                    case "custom":
                        customWrapper.classList.remove("d-none");
                        return;
                    default:
                        customWrapper.classList.add("d-none");
                        customDueDateInput.value = "";
                        return;
                }

                if (dueDate) {
                    const yyyy = dueDate.getFullYear();
                    const mm = String(dueDate.getMonth() + 1).padStart(2, "0");
                    const dd = String(dueDate.getDate()).padStart(2, "0");
                    customDueDateInput.value = `${yyyy}-${mm}-${dd}`;
                }

                customWrapper.classList.remove("d-none");
            }

            dueDateOption.addEventListener("change", updateDueDate);

            // ✅ Set default langsung 1 minggu dari tanggal order
            dueDateOption.value = "1_week";
            updateDueDate();

            // ✅ Jika tanggal order berubah, update juga due date
            orderDateInput.addEventListener("change", updateDueDate);
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
