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
        }

        #purchaseListTable tbody tr {
            animation: fadeIn 0.3s ease-in;
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
        <div class="page-header-right ms-auto">
            <div class="page-header-right-items">
                <div class="d-flex d-md-none">
                    <a href="javascript:void(0)" class="page-header-right-close-toggle">
                        <i class="feather-arrow-left me-2"></i><span>Back</span>
                    </a>
                </div>
                <div class="d-flex align-items-center gap-2 page-header-right-items-wrapper">
                    <a href="/erp/purchases/purchase-list/create-purchase" class="btn btn-primary">
                        <i class="feather-plus me-2"></i>
                        <span>Create Purchase List</span>
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
                                    <table class="table table-hover bg-transparent" id="purchaseListTable">
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
                <form method="POST" id="markAsPurchaseFormProduct">
                    @csrf
                    <input type="hidden" id="purchase_id_product" name="purchase_id">
                    <div class="modal-body">
                        <div class="row g-3 mb-3">
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
                        <div class="row g-3 mb-3">
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
                <form method="POST" id="markAsPurchaseFormFreight">
                    @csrf
                    <input type="hidden" id="purchase_id_freight" name="purchase_id">
                    <div class="modal-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="transaction_type_freight" class="fw-semibold">Purchase Account:</label>
                                <div class="input-group">
                                    <select class="form-select" id="transaction_type_freight" data-select2-selector="tag"
                                        name="transaction_type">
                                        <option value="15" data-bg="bg-danger">Purchase Account</option>
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
                            <th class="text-end">Price + Tax</th>
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
                        data: 'purchase_number'
                    },
                    {
                        data: 'supplier'
                    },
                    {
                        data: 'total_amount'
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
                        data: 'purchase_date',
                        visible: false,
                        searchable: false
                    }
                ]
            });

            // Fungsi load data dari server
            function loadMoreData() {
                if (isLoading || !hasMoreData) return;
                isLoading = true;

                $.ajax({
                    url: "{{ url('/erp/purchases/purchase-list/data') }}",
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
                        due_date_order: $('#due_date_order').val(),
                    },
                    success: function(response) {

                        if (response && response.data && response.data.length > 0) {
                            allData = allData.concat(response.data);
                            dataTable.clear();
                            dataTable.rows.add(allData).draw(false);
                            currentPage++;
                        } else {
                            hasMoreData = false;
                        }
                        isLoading = false;
                    },
                    error: function(xhr) {
                        isLoading = false;
                    }
                });
            }

            // Load pertama kali
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

            // Event handlers untuk filter
            $('#filter').on('change', function() {
                if ($(this).val() === 'custom') {
                    $('.custom-range').removeClass('d-none');
                } else {
                    $('.custom-range').addClass('d-none');
                    resetAndReload();
                }
            });

            $('#apply-filter').on('click', function() {
                resetAndReload();
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

                resetAndReload();
            });

            $('#due_date_order').on('change', function() {
                if ($('#search_type').val() === 'due_date') {
                    resetAndReload();
                }
            });

            // Debounce untuk search keyword
            let searchTimeout = null;
            $('#search_keyword').on('keyup', function() {
                if ($('#search_type').val() !== 'payment_status') {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => resetAndReload(), 400);
                }
            });

            $('#search_payment_status').on('change', function() {
                if ($('#search_type').val() === 'payment_status') {
                    resetAndReload();
                }
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
                    order: [
                        [4, 'asc']
                    ],
                    data: [],
                    columns: deletedColumns
                });

                deletedTableInitialized = true;

                // Lazy load saat scroll untuk deleted table
                $('#deletedPurchaseListTable').closest('.dataTables_scrollBody').on('scroll', function() {
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
                        start: deletedCurrentPage * 15,
                        length: 15,
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

            // Tab switch handler
            $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
                if ($(e.target).attr('href') === '#deleted-purchase-list') {
                    if (!deletedTableInitialized) {
                        initDeletedTable();
                        loadMoreDeletedData();
                    } else {
                        resetAndReloadDeleted();
                    }
                }
            });

            // Filter change untuk deleted table
            $('#filter, #apply-filter, #search_type, #due_date_order, #search_payment_status').on('change click',
                function() {
                    if ($('a[data-bs-toggle="tab"][href="#deleted-purchase-list"]').parent().hasClass(
                        'active')) {
                        resetAndReloadDeleted();
                    }
                });

            $('#search_keyword').on('keyup', function() {
                if ($('a[data-bs-toggle="tab"][href="#deleted-purchase-list"]').parent().hasClass(
                    'active')) {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        resetAndReloadDeleted();
                    }, 400);
                }
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

        const paidInputProduct = document.getElementById("paid_amount_product");
        paidInputProduct.addEventListener("input", function() {
            let angka = this.value.replace(/\D/g, "") || "0";
            this.value = new Intl.NumberFormat('id-ID').format(angka);
        });

        const paidInputFreight = document.getElementById("paid_amount_freight");
        paidInputFreight.addEventListener("input", function() {
            let angka = this.value.replace(/\D/g, "") || "0";
            this.value = new Intl.NumberFormat('id-ID').format(angka);
        });

        document.getElementById('markAsPurchaseFormProduct').addEventListener('submit', function(e) {
            e.preventDefault();

            document.querySelectorAll('#markAsPurchaseFormProduct small.text-danger').forEach(el => {
                el.classList.add('d-none');
                el.innerText = '';
            });

            let valid = true;

            const transactionType = document.getElementById('transaction_type_product')?.value.trim() || '';
            const transactionDate = document.getElementById('transaction_date_product')?.value.trim() || '';
            const cashBankAccount = document.getElementById('cash_bank_account_id_product')?.value.trim() || '';

            const paidAmountRaw = document.getElementById('paid_amount_product')?.value.trim() || '0';
            const paidAmount = paidAmountRaw.replace(/\./g, "");

            const remainingRaw = document.getElementById('total_amount_display_product')?.innerText.trim().replace(
                /\./g, "") || '0';
            const remainingAmount = parseInt(remainingRaw) || 0;

            if (!transactionType) {
                const el = document.getElementById('error_transaction_type_product');
                if (el) {
                    el.innerText = 'Purchase Account wajib dipilih';
                    el.classList.remove('d-none');
                }
                valid = false;
            }

            if (!transactionDate) {
                const el = document.getElementById('error_transaction_date_product');
                if (el) {
                    el.innerText = 'Tanggal transaksi wajib diisi';
                    el.classList.remove('d-none');
                }
                valid = false;
            }

            if (!cashBankAccount) {
                const el = document.getElementById('error_cash_bank_account_id_product');
                if (el) {
                    el.innerText = 'Pilih Cash/Bank Account';
                    el.classList.remove('d-none');
                }
                valid = false;
            }

            if (!paidAmount || isNaN(paidAmount) || parseInt(paidAmount) <= 0) {
                const el = document.getElementById('error_paid_amount_product');
                if (el) {
                    el.innerText = 'Paid amount harus diisi dan lebih dari 0';
                    el.classList.remove('d-none');
                }
                valid = false;
            } else if (parseInt(paidAmount) > remainingAmount) {
                const el = document.getElementById('error_paid_amount_product');
                if (el) {
                    el.innerText = 'Paid amount tidak boleh melebihi Balance';
                    el.classList.remove('d-none');
                }
                valid = false;
            }

            if (!valid) {
                return false;
            }

            document.getElementById('paid_amount_product').value = paidAmount;

            this.submit();
        });

        document.getElementById('markAsPurchaseFormFreight').addEventListener('submit', function(e) {
            e.preventDefault();

            document.querySelectorAll('#markAsPurchaseFormFreight small.text-danger').forEach(el => {
                el.classList.add('d-none');
                el.innerText = '';
            });

            let valid = true;

            const transactionType = document.getElementById('transaction_type_freight')?.value.trim() || '';
            const transactionDate = document.getElementById('transaction_date_freight')?.value.trim() || '';
            const cashBankAccount = document.getElementById('cash_bank_account_id_freight')?.value.trim() || '';
            const paidAmountRaw = document.getElementById('paid_amount_freight')?.value.trim() || '0';
            const paidAmount = paidAmountRaw.replace(/\./g, "");
            const remainingRaw = document.getElementById('total_amount_display_freight')?.innerText.trim().replace(
                /\./g, "") || '0';
            const remainingAmount = parseInt(remainingRaw) || 0;

            if (!transactionType) {
                const el = document.getElementById('error_transaction_type_freight');
                if (el) {
                    el.innerText = 'Purchase Account wajib dipilih';
                    el.classList.remove('d-none');
                }
                valid = false;
            }

            if (!transactionDate) {
                const el = document.getElementById('error_transaction_date_freight');
                if (el) {
                    el.innerText = 'Tanggal transaksi wajib diisi';
                    el.classList.remove('d-none');
                }
                valid = false;
            }

            if (!cashBankAccount) {
                const el = document.getElementById('error_cash_bank_account_id_freight');
                if (el) {
                    el.innerText = 'Pilih Cash/Bank Account';
                    el.classList.remove('d-none');
                }
                valid = false;
            }

            if (!paidAmount || isNaN(paidAmount) || parseInt(paidAmount) <= 0) {
                const el = document.getElementById('error_paid_amount_freight');
                if (el) {
                    el.innerText = 'Paid amount harus diisi dan lebih dari 0';
                    el.classList.remove('d-none');
                }
                valid = false;
            } else if (parseInt(paidAmount) > remainingAmount) {
                const el = document.getElementById('error_paid_amount_freight');
                if (el) {
                    el.innerText = 'Paid amount tidak boleh melebihi Balance';
                    el.classList.remove('d-none');
                }
                valid = false;
            }

            if (!valid) {
                return false;
            }

            document.getElementById('paid_amount_freight').value = paidAmount;
            this.submit();
        });

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

        $('#modalChangeStatusProduct, #modalChangeStatusFreight').on('shown.bs.modal', function() {
            if ($.fn.select2) {
                $('#cash_bank_account_id_product, #cash_bank_account_id_freight').trigger('change.select2');
            }
        });
    </script>
@endpush
