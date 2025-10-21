@extends('erp.layouts.main')

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Profit & Loss Report</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Reports</li>
                <li class="breadcrumb-item active">Profit & Loss</li>
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
            <div class="d-md-none d-flex align-items-center">
                <a href="javascript:void(0)" class="page-header-right-open-toggle">
                    <i class="feather-align-right fs-20"></i>
                </a>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="main-content">
        <div class="row">
            <div class="col-lg-12">
                <div class="card stretch stretch-full">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <tbody>
                                    <tr>
                                        <td><strong>Net Revenue</strong></td>
                                        <td class="text-end" id="RevenueDisplay">Rp {{ number_format($netRevenue, 0) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Less: COGS</td>
                                        <td class="text-end" id="COGSDisplay">(Rp {{ number_format($cogs, 0) }})</td>
                                    </tr>
                                    <tr>
                                        <td>Less: Sale Return</td>
                                        <td class="text-end" id="SaleReturnDisplay">(Rp
                                            {{ number_format($saleReturn ?? 0, 0) }})</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Gross Profit</strong></td>
                                        <td class="text-end" id="GrossProfitDisplay">Rp {{ number_format($grossProfit, 0) }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Less: Expenses</td>
                                        <td class="text-end" id="ExpenseDisplay">(Rp {{ number_format($expenses, 0) }})</td>
                                    </tr>
                                    <tr class="table-success">
                                        <td><strong>Net Profit</strong></td>
                                        <td class="text-end" id="NetProfitDisplay"><strong>Rp
                                                {{ number_format($netProfit, 0) }}</strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-12">
                <div class="card stretch stretch-full">
                    <div class="card-header text-white">
                        <h5 class="mb-0">Profit & Loss (Fixed Cost)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <tbody>
                                    <tr>
                                        <td><strong>Net Revenue</strong></td>
                                        <td class="text-end" id="RevenueFixedDisplay">Rp {{ number_format($netRevenue, 0) }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Less: COGS (Fixed)</td>
                                        <td class="text-end" id="COGSFixedDisplay">(Rp
                                            {{ number_format($cogsFixed ?? 0, 0) }})</td>
                                    </tr>
                                    <tr>
                                        <td>Less: Sale Return</td>
                                        <td class="text-end" id="SaleReturnFixedDisplay">(Rp
                                            {{ number_format($saleReturn ?? 0, 0) }})</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Gross Profit (Fixed)</strong></td>
                                        <td class="text-end" id="GrossProfitFixedDisplay">Rp
                                            {{ number_format($grossProfitFixed ?? 0, 0) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Less: Expenses</td>
                                        <td class="text-end" id="ExpenseFixedDisplay">(Rp
                                            {{ number_format($expenses ?? 0, 0) }})</td>
                                    </tr>
                                    <tr class="table-info">
                                        <td><strong>Net Profit (Fixed)</strong></td>
                                        <td class="text-end" id="NetProfitFixedDisplay"><strong>Rp
                                                {{ number_format($netProfitFixed ?? 0, 0) }}</strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function formatRupiah(angka) {
            return parseFloat(angka).toLocaleString('en-US'); // pakai koma sebagai pemisah ribuan
        }

        function fetchProfitLoss(filter, start = '', end = '') {
            $.ajax({
                url: '{{ url('/erp/financial-report/profit-loss/summary') }}',
                method: 'GET',
                data: {
                    filter,
                    start_date: start,
                    end_date: end
                },
                success: function(res) {
                    $('#RevenueDisplay').text('Rp ' + formatRupiah(res.netRevenue));
                    $('#COGSDisplay').text('(Rp ' + formatRupiah(res.cogs) + ')');
                    $('#SaleReturnDisplay').text('(Rp ' + formatRupiah(res.saleReturn) + ')');
                    $('#GrossProfitDisplay').text('Rp ' + formatRupiah(res.grossProfit));
                    $('#ExpenseDisplay').text('(Rp ' + formatRupiah(res.expenses) + ')');
                    $('#NetProfitDisplay').html('<strong>Rp ' + formatRupiah(res.netProfit) + '</strong>');
                }
            });
        }

        $(document).ready(function() {
            fetchProfitLoss('all'); // load awal

            $('#filter').change(function() {
                const filter = $(this).val();
                if (filter === 'custom') {
                    $('.custom-range').removeClass('d-none');
                } else {
                    $('.custom-range').addClass('d-none');
                    fetchProfitLoss(filter);
                }
            });

            $('#apply-filter').click(function() {
                const start = $('#start_date').val();
                const end = $('#end_date').val();
                fetchProfitLoss('custom', start, end);
            });
        });
    </script>
@endpush
