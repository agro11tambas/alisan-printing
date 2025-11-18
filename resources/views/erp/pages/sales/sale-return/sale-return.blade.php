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
        }

        #saleReturnTable tbody tr {
            animation: fadeIn 0.3s ease-in;
        }

        .static-action-menu {
            padding: 12px;
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
                                            <option value="customer">Customer</option>
                                            <option value="order_number">Order Number</option>
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
                                            <option value="Refunded">Refunded</option>
                                            <option value="Unpaid">Unpaid</option>
                                            <option value="Partially Paid">Partially Paid</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <ul class="nav nav-tabs mb-3" id="saleReturnTabs" role="tabreturn">
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
                                                <th>Paid Amount</th>
                                                <th>Remaining Amount</th>
                                                <th>Payment Status</th>
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
                        <div class="row g-3 mb-3">
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
                        {{-- <div class="row g-3 mb-3">
                            <div class="col-md-12">
                                <label for="payment_proof" class="fw-semibold">Upload Proof (optional):</label>
                                <div class="input-group">
                                    <input type="file" class="form-control" id="payment_proof" name="payment_proof"
                                        accept="image/jpg,image/jpeg,image/png,image/webp,application/pdf">
                                </div>
                                <small class="text-muted">Upload foto bukti transfer (Gambar)</small>
                                <small class="text-danger d-none" id="error_payment_proof"></small>
                                <div class="mt-2 d-none" id="proof_preview_wrapper">
                                    <p class="fw-semibold mb-1">Preview:</p>
                                    <img id="proof_preview" src="#" alt="Proof Preview" class="img-thumbnail"
                                        style="max-height: 200px;">
                                </div>
                            </div>
                        </div> --}}

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
                        <div class="mt-3">
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
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {

            // ===== Helper: format produk di detail row =====
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
                    {
                        data: 'remaining_amount'
                    },
                    {
                        data: 'payment_status'
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
                        payment_status: $('#search_payment_status').val(),
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

            function resetAndReload() {
                allData = [];
                currentPage = 0;
                hasMoreData = true;
                dataTable.clear().draw();
                loadMoreData();
            }

            $('#filter, #apply-filter, #search_type, #search_keyword, #search_payment_status, #start_date, #end_date')
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
                    $('#search_payment_status').removeClass('d-none');
                } else {
                    $('#search_keyword').removeClass('d-none');
                    $('#search_payment_status').addClass('d-none').val('');
                }
                resetAndReload();
            });

            // Debounce untuk search keyword
            let searchTimeout = null;
            $('#search_keyword').on('keyup', function() {
                if ($('#search_type').val() !== 'payment_status') {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => resetAndReload(), 500);
                }
            });

            $('#search_payment_status').on('change', function() {
                if ($('#search_type').val() === 'payment_status') resetAndReload();
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
                    order: [
                        [4, 'asc']
                    ],
                    data: [],
                    columns: deletedColumns
                });

                deletedTableInitialized = true;

                $('#deletedSaleReturnTable').closest('.dataTables_scrollBody').on('scroll', function() {
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
                        payment_status: $('#search_payment_status').val(),
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

                if (target === '#sale-return') {
                    resetAndReload();
                }

                if (target === '#deleted-sale-return') {
                    if (!deletedTableInitialized) initDeletedTable();
                    resetAndReloadDeleted();
                }
            });

            $('#search_keyword').on('keyup', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    reloadActiveTab();
                }, 300);
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
