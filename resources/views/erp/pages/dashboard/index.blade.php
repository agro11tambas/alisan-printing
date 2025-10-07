@extends('erp.layouts.main')

@section('breadcrumb')
<div class="page-header sticky-top">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">Dashboard</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="/erp/dashboard">Home</a></li>
            <li class="breadcrumb-item">Dashboard</li>
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
                <div class="col-auto">
                    <select id="filter" class="form-control" style="padding: 0.5rem 1rem; font-size: 0.875rem; width: 200px !important;">
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
                    <input type="date" id="start_date" class="form-control" style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                </div>
                <div class="col-auto custom-range d-none">
                    <input type="date" id="end_date" class="form-control" style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                </div>
                <div class="col-auto custom-range d-none">
                    <button id="apply-filter" class="btn btn-primary">Apply</button>
                </div>
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
@if(session('error'))
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
        <div class="col-xxl-3 col-lg-4 col-md-6">
            <div class="card stretch stretch-full">
                <div class="card-body">
                    <div class="hstack justify-content-between lh-base mb-3">
                        <span class="fs-12 fw-medium text-muted">Sale List</span>
                        <div class="hstack gap-2 fs-11 text-success">
                            <i class="feather-circle fs-14"></i>
                        </div>
                    </div>
                    <div class="hstack justify-content-between lh-base">
                        <h4 class="fw-bolder mb-3"><span class="counter" id="SaleOrderDisplay">0</span></h4>
                    </div>
                    <p class="fs-12 text-muted mb-0">Total Sale List : <span class="fw-semibold text-dark" id="SaleOrderListDisplay">0</span></p>
                    <hr>
                    <div class="d-flex flex-wrap fs-12 text-muted">
                        <span>Paid: <span class="fw-semibold text-dark" id="SalePaid">0</span></span>
                        <span class="mx-2">|</span>
                        <span>Unpaid: <span class="fw-semibold text-dark" id="SaleUnpaid">0</span></span>
                        <span class="mx-2">|</span>
                        <span>Overdue: <span class="fw-semibold text-dark" id="SaleOverdue">0</span></span>
                        <span class="mx-2">|</span>
                        <span>Partially Paid: <span class="fw-semibold text-dark" id="SalePartiallyPaid">0</span></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-lg-4 col-md-6">
            <div class="card stretch stretch-full">
                <div class="card-body">
                    <div class="hstack justify-content-between lh-base mb-3">
                        <span class="fs-12 fw-medium text-muted">Purchase List</span>
                        <div class="hstack gap-2 fs-11 text-success">
                            <i class="feather-circle fs-14"></i>
                        </div>
                    </div>
                    <div class="hstack justify-content-between lh-base">
                        <h4 class="fw-bolder mb-3"><span class="counter" id="PurchaseOrderDisplay">0</span></h4>
                    </div>
                    <p class="fs-12 text-muted mb-0">Total Purchase List : <span class="fw-semibold text-dark" id="PurchaseOrderListDisplay">0</span></p>
                    <hr>
                    <div class="d-flex flex-wrap fs-12 text-muted">
                        <span>Paid: <span class="fw-semibold text-dark" id="PurchasePaid">0</span></span>
                        <span class="mx-2">|</span>
                        <span>Unpaid: <span class="fw-semibold text-dark" id="PurchaseUnpaid">0</span></span>
                        <span class="mx-2">|</span>
                        <span>Overdue: <span class="fw-semibold text-dark" id="PurchaseOverdue">0</span></span>
                        <span class="mx-2">|</span>
                        <span>Partially Paid: <span class="fw-semibold text-dark" id="PurchasePartiallyPaid">0</span></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-lg-4 col-md-6">
            <div class="card stretch stretch-full">
                <div class="card-body">
                    <div class="hstack justify-content-between lh-base mb-3">
                        <span class="fs-12 fw-medium text-muted">Payments</span>
                        <div class="hstack gap-2 fs-11 text-success">
                            <i class="feather-circle fs-14"></i>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-12">
                            <p class="fs-12 text-muted mb-0">Received from Customer</p>
                            <div class="hstack justify-content-between lh-base">
                                <h5 class="fw-bolder mt-2">
                                    <span class="counter" id="ReceivedFromCustomer">0</span>
                                </h5>
                            </div>
                        </div>
                        <div class="col-12">
                            <p class="fs-12 text-muted mb-0">Paid to Supplier</p>
                            <div class="hstack justify-content-between lh-base">
                                <h5 class="fw-bolder mt-2">
                                    <span class="counter" id="PaidToSupplier">0</span>
                                </h5>
                            </div>
                        </div>
                    </div>
                    <!-- <hr> -->
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-lg-4 col-md-6">
            <div class="card stretch stretch-full">
                <div class="card-body">
                    <div class="hstack justify-content-between lh-base mb-3">
                        <span class="fs-12 fw-medium text-muted">Receivable</span>
                        <div class="hstack gap-2 fs-11 text-success">
                            <i class="feather-circle fs-14"></i>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-12">
                            <p class="fs-12 text-muted mb-0">Receivable from Customer</p>
                            <div class="hstack justify-content-between lh-base">
                                <h5 class="fw-bolder mt-2">
                                    <span class="counter" id="ReceivableFromCustomer">0</span>
                                </h5>
                            </div>
                        </div>
                        <div class="col-12">
                            <p class="fs-12 text-muted mb-0">Payable to Supplier</p>
                            <div class="hstack justify-content-between lh-base">
                                <h5 class="fw-bolder mt-2">
                                    <span class="counter" id="PayableToSupplier">0</span>
                                </h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-lg-4 col-md-6">
            <div class="card stretch stretch-full">
                <div class="card-body">
                    <div class="hstack justify-content-between lh-base mb-3">
                        <span class="fs-12 fw-medium text-muted">Expense</span>
                    </div>
                    <div class="hstack justify-content-between lh-base">
                        <h4 class="fw-bolder mb-3"><span class="counter" id="ExpenseTotalDisplay">0</span></h4>
                    </div>
                    <div id="ExpenseBreakdown"></div>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-lg-4 col-md-6">
            <div class="card stretch stretch-full">
                <div class="card-body">
                    <div class="hstack justify-content-between lh-base mb-3">
                        <span class="fs-12 fw-medium text-muted">Bank</span>
                    </div>
                    <div class="hstack justify-content-between lh-base">
                        <h4 class="fw-bolder mb-3">
                            <span class="counter" id="BankTotalDisplay">0</span>
                        </h4>
                    </div>
                    <div id="BankBreakdown"></div>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-lg-4 col-md-6">
            <div class="card stretch stretch-full">
                <div class="card-body">
                    <div class="hstack justify-content-between lh-base mb-3">
                        <span class="fs-12 fw-medium text-muted">Cash</span>
                    </div>
                    <div class="hstack justify-content-between lh-base">
                        <h4 class="fw-bolder mb-3"><span class="counter" id="CashTotalDisplay">0</span></h4>
                    </div>
                    <div id="CashBreakdown"></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function formatRupiah(angka) {
        return new Intl.NumberFormat('id-ID').format(angka);
    }

    function fetchAccount(filter, start = '', end = '') {
        $.ajax({
            url: '{{ url("/erp/dashboard/summary") }}',
            method: 'GET',
            data: {
                filter: filter,
                start_date: start,
                end_date: end
            },
            success: function(response) {
                $('#SaleAccountDisplay').text('Rp ' + formatRupiah(response.totalSaleAccount));
                $('#SaleOrderDisplay').text('Rp ' + formatRupiah(response.totalSaleOrder));
                $('#SaleListDisplay').text(response.totalSaleList);
                $('#SaleOrderListDisplay').text(response.totalSaleOrderList);

                // Breakdown Sale List by payment_status
                $('#SalePaid').text(response.saleListStatus.paid);
                $('#SaleUnpaid').text(response.saleListStatus.unpaid);
                $('#SaleOverdue').text(response.saleListStatus.overdue);
                $('#SalePartiallyPaid').text(response.saleListStatus.partially_paid);

                $('#PurchaseAccountDisplay').text('Rp ' + formatRupiah(response.totalPurchaseAccount));
                $('#PurchaseOrderDisplay').text('Rp ' + formatRupiah(response.totalPurchaseOrder));
                $('#PurchaseListDisplay').text(response.totalPurchaseList);
                $('#PurchaseOrderListDisplay').text(response.totalPurchaseOrderList);

                // Breakdown Purchase List by payment_status
                $('#PurchasePaid').text(response.purchaseListStatus.paid);
                $('#PurchaseUnpaid').text(response.purchaseListStatus.unpaid);
                $('#PurchaseOverdue').text(response.purchaseListStatus.overdue);
                $('#PurchasePartiallyPaid').text(response.purchaseListStatus.partially_paid);

                $('#ReceivedFromCustomer').text('Rp ' + formatRupiah(response.receivedFromCustomer));
                $('#PaidToSupplier').text('Rp ' + formatRupiah(response.paidToSupplier));

                $('#ReceivableFromCustomer').text('Rp ' + formatRupiah(response.receivableFromCustomer));
                $('#PayableToSupplier').text('Rp ' + formatRupiah(response.payableToSupplier));

                $('#ExpenseAccountDisplay').text('Rp ' + formatRupiah(response.totalExpenseAccount));
                $('#BankAccountDisplay').text('Rp ' + formatRupiah(response.totalBankAccount));
                $('#CashAccountDisplay').text('Rp ' + formatRupiah(response.totalCashAccount));

                $('#ExpenseTotalDisplay').text('Rp ' + formatRupiah(response.totalByName['Expense'] ?? 0));
                $('#ExpenseBreakdown').empty();
                if (response.breakdownByName && response.breakdownByName['Expense']) {
                    $.each(response.breakdownByName['Expense'], function(type, total) {
                        $('#ExpenseBreakdown').append(
                            `<p class="fs-12 text-muted mb-0">${type} :
                                <span class="fw-semibold text-dark">Rp ${formatRupiah(total)}</span>
                            </p>`
                        );
                    });
                }

                // Bank total
                $('#BankTotalDisplay').text('Rp ' + formatRupiah(response.totalByName['Bank'] ?? 0));

                // Breakdown Bank
                $('#BankBreakdown').empty();
                if (response.breakdownByName && response.breakdownByName['Bank']) {
                    $.each(response.breakdownByName['Bank'], function(type, total) {
                        $('#BankBreakdown').append(
                            `<p class="fs-12 text-muted mb-0">${type} : 
                                <span class="fw-semibold text-dark">Rp ${formatRupiah(total)}</span>
                            </p>`
                        );
                    });
                }

                $('#CashTotalDisplay').text('Rp ' + formatRupiah(response.totalByName['Cash'] ?? 0));
                $('#CashBreakdown').empty();
                if (response.breakdownByName && response.breakdownByName['Cash']) {
                    $.each(response.breakdownByName['Cash'], function(type, total) {
                        $('#CashBreakdown').append(
                            `<p class="fs-12 text-muted mb-0">${type} :
                                <span class="fw-semibold text-dark">Rp ${formatRupiah(total)}</span>
                            </p>`
                        );
                    });
                }
            },
            error: function(err) {
                console.error(err);
            }
        });
    }

    $(document).ready(function() {
        fetchAccount('all'); // load awal

        $('#filter').change(function() {
            const filter = $(this).val();

            if (filter === 'custom') {
                $('.custom-range').removeClass('d-none');
            } else {
                $('.custom-range').addClass('d-none');
                fetchAccount(filter);
            }
        });

        $('#apply-filter').click(function() {
            const start = $('#start_date').val();
            const end = $('#end_date').val();
            fetchAccount('custom', start, end);
        });
    });
</script>
@endpush