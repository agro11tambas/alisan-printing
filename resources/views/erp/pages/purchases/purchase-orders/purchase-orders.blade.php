@extends('erp.layouts.main')

@push('styles')
    <style>
        @media (max-width: 768px) {

            #purchaseOrderTable td.desktop-only,
            #purchaseOrderTable th.desktop-only {
                display: none !important;
            }
        }

        #purchaseOrderTable {
            width: 100% !important;
            min-width: 0;
        }

        #purchaseOrderTable_wrapper .dataTables_scrollBody {
            background-image: none !important;
            height: 60vh !important;
            overflow-y: auto !important;
        }

        .dataTables_scrollBody {
            scroll-behavior: smooth;
        }

        #purchaseOrderTable tbody tr {
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
                    <a href="/erp/purchases/purchase-orders/create-purchase" class="btn btn-primary">
                        <i class="feather-plus me-2"></i>
                        <span>Create Purchase Order</span>
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
                        <div class="table-responsive">
                            <table class="table table-hover bg-transparent" id="purchaseOrderTable">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th>Purchase Number</th>
                                        <th>Supplier</th>
                                        <th>Total Amount</th>
                                    </tr>
                                </thead>
                                <tbody>

                                </tbody>
                            </table>
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
                        <h5 class="modal-title text-white" id="deleteModalLabel">Hapus Purchase</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Apakah Anda yakin ingin menghapus Purchase <strong id="purchaseName"></strong>?</p>
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

    {{-- <div class="modal fade-scale" id="modalChangeStatus" tabindex="-1" aria-labelledby="modalChangeStatus"
        aria-hidden="true" data-bs-dismiss="ou">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="d-flex flex-column mb-0">
                        <span class="fs-18 fw-bold mb-1">Mark As Purchase List</span>
                    </h2>
                    <a href="javascript:void(0)" class="avatar-text avatar-md bg-soft-danger close-icon"
                        data-bs-dismiss="modal">
                        <i class="feather-x text-danger"></i>
                    </a>
                </div>
                <form method="POST" id="markAsPurchaseForm">
                    @csrf
                    <input type="hidden" id="purchase_id" name="purchase_id">
                    <div class="modal-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="purchase_number" class="fw-semibold fs-12">Invoice Number</label>
                                <input type="text" id="modal_purchase_number" name="purchase_number"
                                    class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label for="purchase_date" class="fw-semibold fs-12" id="modal_purchase_date">Purchase
                                    Date</label>
                                <input type="date" id="purchase_date" name="purchase_date" class="form-control"
                                    value="{{ date('Y-m-d') }}">
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-12">
                                <label for="paid_amount" class="fw-semibold">Paid Amount:</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="paid_amount" name="paid_amount"
                                        value="0">
                                </div>
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="transaction_type" class="fw-semibold">Purchase:</label>
                                <div class="input-group">
                                    <select class="form-select form-control max-select" data-select2-selector="tag"
                                        id="transaction_type" name="transaction_type">
                                        <option value="12" data-bg="bg-danger">Purchase Account</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="transaction_date" class="fw-semibold">Transaction Date:</label>
                                <input type="date" id="transaction_date" name="transaction_date" class="form-control"
                                    value="{{ date('Y-m-d') }}">
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
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
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-12">
                                <label for="notes" class="fw-semibold">Note:</label>
                                <textarea class="form-control" name="notes" id="notes" rows="3"></textarea>
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
                        <button type="submit" class="btn btn-primary">Mark As Purchase List</button>
                    </div>
                </form>
            </div>
        </div>
    </div> --}}

    <div class="modal fade-scale" id="modalChangeStatus" tabindex="-1" aria-labelledby="modalChangeStatus"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="d-flex flex-column mb-0">
                        <span class="fs-18 fw-bold mb-1">Mark As Purchase List</span>
                    </h2>
                    <a href="javascript:void(0)" class="avatar-text avatar-md bg-soft-danger close-icon"
                        data-bs-dismiss="modal">
                        <i class="feather-x text-danger"></i>
                    </a>
                </div>

                <form method="POST" id="markAsPurchaseForm">
                    @csrf
                    <input type="hidden" id="purchase_id" name="purchase_id">
                    <div class="modal-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="modal_purchase_number" class="fw-semibold fs-12">Invoice Number</label>
                                <input type="text" id="modal_purchase_number" name="purchase_number"
                                    class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label for="purchase_date" class="fw-semibold fs-12">Purchase Date</label>
                                <input type="date" id="purchase_date" name="purchase_date" class="form-control"
                                    value="{{ date('Y-m-d') }}">
                            </div>
                        </div>

                        <div class="border rounded p-3 mb-4">
                            <h6 class="fw-bold text-primary mb-3">Product Payment (to Supplier)</h6>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="fw-semibold">Remaining Amount (Product):</label>
                                    <h5 id="remaining_product_display" class="text-danger fw-bold">0</h5>
                                </div>
                                <div class="col-md-6">
                                    <label class="fw-semibold">Pay Amount (Product):</label>
                                    <input type="text" class="form-control format-number" id="paid_amount_product"
                                        name="paid_amount_product" value="0">
                                </div>
                            </div>
                        </div>

                        <div class="border rounded p-3 mb-4">
                            <h6 class="fw-bold text-primary mb-3">Freight Payment (to Expedition)</h6>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="fw-semibold">Remaining Amount (Freight):</label>
                                    <h5 id="remaining_freight_display" class="text-danger fw-bold">0</h5>
                                </div>
                                <div class="col-md-6">
                                    <label class="fw-semibold">Pay Amount (Freight):</label>
                                    <input type="text" class="form-control format-number" id="paid_amount_freight"
                                        name="paid_amount_freight" value="0">
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="transaction_type" class="fw-semibold">Transaction Type:</label>
                                <select class="form-select" id="transaction_type" name="transaction_type"
                                    data-select2-selector="tag">
                                    <option value="12" data-bg="bg-danger">Purchase Account</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="transaction_date" class="fw-semibold">Transaction Date:</label>
                                <input type="date" id="transaction_date" name="transaction_date" class="form-control"
                                    value="{{ date('Y-m-d') }}">
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-12">
                                <label for="cash_bank_account_id" class="fw-semibold">Cash/Bank Account:</label>
                                @php
                                    $bgColors = ['bg-danger', 'bg-warning', 'bg-primary', 'bg-indigo', 'bg-success'];
                                @endphp
                                <select class="form-select form-control max-select" data-select2-selector="tag"
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
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-12">
                                <label for="notes" class="fw-semibold">Note:</label>
                                <textarea class="form-control" name="notes" id="notes" rows="3"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">Mark As Paid</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endpush

@push('scripts')
    <script>
        $(document).ready(function() {

            let allData = [];
            let currentPage = 0;
            let isLoading = false;
            let hasMoreData = true;

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

            const dataTable = $('#purchaseOrderTable').DataTable({
                processing: false,
                serverSide: false,
                scrollY: '60vh',
                scrollCollapse: true,
                paging: false,
                searching: false,
                info: false,
                lengthChange: false,
                order: [
                    [4, 'desc']
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
                        data: 'purchase_date',
                        visible: false,
                        searchable: false
                    }
                ],
            });

            function loadMoreData() {
                if (isLoading || !hasMoreData) return;
                isLoading = true;

                $.ajax({
                    url: "{{ url('/erp/purchases/purchase-orders/data') }}",
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
                        console.error('❌ Error:', xhr.responseJSON);
                        alert(xhr.responseJSON?.message || 'Error loading data.');
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

            $('#purchaseOrderTable tbody').on('click', 'td.dt-control', function() {
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

            $('#purchaseOrderTable tbody').on('click', 'tr', function(e) {
                if ($(e.target).closest('td.dt-control').length) return;

                let $tr = $(this);
                let row = dataTable.row($tr);

                $('#purchaseOrderTable tbody tr').removeClass('action-shown').next('.action-row').remove();

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
                if ($(e.target).closest('#purchaseOrderTable').length) return;
                $('#purchaseOrderTable tbody tr').removeClass('action-shown').next('.action-row').remove();
            });

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
                } else {
                    $('#search_keyword').removeClass('d-none');
                    $('#search_payment_status').addClass('d-none').val('');
                }
                resetAndReload();
            });

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

        $(document).on('shown.bs.modal', '#modalChangeStatus', function() {
            $('#paid_amount').trigger('input');
        });

        // document.addEventListener('click', function(e) {
        //     if (e.target.closest('.btn-mark-purchase')) {
        //         const button = e.target.closest('.btn-mark-purchase');
        //         const purchaseNumber = button.getAttribute('data-purchase-number') || '';
        //         const purchaseId = button.getAttribute('data-id');
        //         const url = button.getAttribute('data-url');
        //         const totalAmount = parseFloat(button.getAttribute('data-total-amount')) || 0;
        //         const paidAmount = parseFloat(button.getAttribute('data-paid-amount')) || 0;
        //         const remainingAmount = totalAmount - paidAmount;

        //         document.getElementById('modal_purchase_number').value = purchaseNumber;
        //         document.getElementById('purchase_id').value = purchaseId;
        //         document.getElementById('markAsPurchaseForm').setAttribute('action', url);
        //         document.getElementById('total_amount_display').innerText = new Intl.NumberFormat('id-ID').format(
        //             remainingAmount);
        //     }
        // });

        document.addEventListener('click', function(e) {
            if (e.target.closest('.btn-mark-purchase')) {
                const button = e.target.closest('.btn-mark-purchase');
                const purchaseId = button.getAttribute('data-id');
                const purchaseNumber = button.getAttribute('data-purchase-number') || '';

                const totalProduct = parseFloat(button.getAttribute('data-total-amount-product')) || 0;
                const paidProduct = parseFloat(button.getAttribute('data-paid-amount-product')) || 0;
                const remainingProduct = totalProduct - paidProduct;

                const totalFreight = parseFloat(button.getAttribute('data-total-amount-freight')) || 0;
                const paidFreight = parseFloat(button.getAttribute('data-paid-amount-freight')) || 0;
                const remainingFreight = totalFreight - paidFreight;

                document.getElementById('modal_purchase_number').value = purchaseNumber;
                document.getElementById('purchase_id').value = purchaseId;

                document.getElementById('remaining_product_display').innerText = new Intl.NumberFormat('id-ID')
                    .format(remainingProduct);
                document.getElementById('remaining_freight_display').innerText = new Intl.NumberFormat('id-ID')
                    .format(remainingFreight);

                document.getElementById('markAsPurchaseForm').setAttribute('action', button.getAttribute(
                    'data-url'));
            }
        });

        const paidInput = document.getElementById("paid_amount");

        paidInput.addEventListener("input", function() {
            let angka = this.value.replace(/\D/g, "") || "0";
            this.value = new Intl.NumberFormat('id-ID').format(angka);
        });

        // document.querySelector("form").addEventListener("submit", function() {
        //     paidInput.value = paidInput.value.replace(/\./g, "");
        // });

        $(document).on('input', '.format-number', function() {
            let angka = this.value.replace(/\D/g, '') || '0';
            this.value = new Intl.NumberFormat('id-ID').format(angka);
        });

        $('#markAsPurchaseForm').on('submit', function() {
            $('.format-number').each(function() {
                this.value = this.value.replace(/\./g, '');
            });
        });
    </script>
@endpush
