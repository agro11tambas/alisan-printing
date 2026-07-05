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
            <div class="page-header-right-items">
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
                                        {{-- <th>Type</th> --}}
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
                        <div>
                            <div class="col-md-6">
                                <p class="m-0">Balance:</p>
                                <h5 class="fw-semibold text-danger" id="total_amount_display">0</h5>
                            </div>
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
                    // {
                    //     data: 'mode'
                    // },
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
            $('#saleOrderTable tbody').on('click', 'tr', function(e) {
                if ($(e.target).closest('td.dt-control').length) return;

                let $tr = $(this);
                let row = dataTable.row($tr);

                $('#saleOrderTable tbody tr').removeClass('action-shown').next('.action-row').remove();

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
                if ($(e.target).closest('#saleOrderTable').length) return;
                $('#saleOrderTable tbody tr').removeClass('action-shown').next('.action-row').remove();
            });

            $(document).on('submit', '#formDeleteOrder', function(e) {
                e.preventDefault();
                const form = $(this);
                const url = form.attr('action');
                const orderName = $('#OrderName').text();

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: form.serialize(),
                    success: function() {
                        Swal.close();
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: `Order ${orderName} berhasil dihapus.`
                        });

                        $('#modalDeleteOrder').modal('hide');
                        $('#formDeleteOrder')[0].reset();

                        // ✅ reload data baru biar aman (hapus data lama dari array)
                        allData = [];
                        currentPage = 0;
                        hasMoreData = true;
                        dataTable.clear().draw();
                        loadMoreData();
                    },
                    error: function() {
                        Swal.close();
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: 'Terjadi kesalahan saat menghapus order.'
                        });
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
                const remainingAmount = totalAmount - paidAmount;

                const orderDate = document.getElementById('order_date').value;

                const response = await fetch(`/erp/sales/generate-invoice-number?date=${orderDate}`);
                const data = await response.json();

                document.getElementById('modal_order_number_display').value = data.invoice_number;
                document.getElementById('modal_order_number').value = button.getAttribute('data-order-number');
                document.getElementById('order_id').value = orderId;
                document.getElementById('markAsSaleForm').setAttribute('action', url);
                document.getElementById('total_amount_display').innerText =
                    new Intl.NumberFormat('id-ID').format(remainingAmount);
            }
        });

        $(document).on('click', '.btn-share-invoice', function() {
            const url = $(this).data('url');
            window.open(url, '_blank');
        });

        const paidInput = document.getElementById("paid_amount");

        paidInput.addEventListener("input", function() {
            let angka = this.value.replace(/\D/g, "") || "0";
            this.value = new Intl.NumberFormat('id-ID').format(angka);
        });

        document.querySelector("form").addEventListener("submit", function() {
            paidInput.value = paidInput.value.replace(/\./g, "");
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
