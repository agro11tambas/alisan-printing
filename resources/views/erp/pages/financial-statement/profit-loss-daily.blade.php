@extends('erp.layouts.main')

@push('styles')
    <style>
        @media (max-width: 768px) {

            #dailyPLTable td.desktop-only,
            #dailyPLTable th.desktop-only {
                display: none !important;
            }
        }

        #dailyPLTable {
            width: 100% !important;
            min-width: 0;
        }

        #dailyPLTable_wrapper .dataTables_scrollBody {
            background-image: none !important;
        }
    </style>
@endpush

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Profit & Loss Daily Breakdown</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Reports</li>
                <li class="breadcrumb-item active">Profit & Loss Daily</li>
            </ul>
        </div>
    </div>
@endsection

@section('content')
    <div class="main-content">
        <div class="row">
            <div class="col-lg-12">
                <div class="card stretch stretch-full">
                    <div class="card-body p-0">
                        <div class="row g-3 p-4 justify-content-between">
                            <div class="col-lg-auto me-2">
                                <div class="row g-3 align-items-start">
                                    <div class="col-lg-auto">
                                        <label for="filter" class="fw-semibold fs-12">Filter Date</label>
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
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover" id="dailyPLTable">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Revenue</th>
                                        <th>COGS</th>
                                        <th>Gross Profit</th>
                                        <th>Expenses</th>
                                        <th>Net Profit</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                                <tfoot>
                                    <tr>
                                        <th>Total</th>
                                        <th id="totalRevenue"></th>
                                        <th id="totalCogs"></th>
                                        <th id="totalGrossProfit"></th>
                                        <th id="totalExpenses"></th>
                                        <th id="totalNetProfit"></th>
                                    </tr>
                                </tfoot>
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
        $(document).ready(function() {
            if ($.fn.DataTable.isDataTable('#dailyPLTable')) {
                $('#dailyPLTable').DataTable().clear().destroy();
            }

            const table = $('#dailyPLTable').DataTable({
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
                    url: "{{ url('/erp/financial-report/profit-loss/daily/data') }}",
                    data: function(d) {
                        d.filter = $('#filter').val();
                        d.start_date = $('#start_date').val();
                        d.end_date = $('#end_date').val();
                    },
                    dataSrc: function(json) {
                        // Update footer total
                        $('#totalRevenue').text('Rp ' + new Intl.NumberFormat('id-ID').format(json
                            .summary.total_revenue));
                        $('#totalCogs').text('Rp ' + new Intl.NumberFormat('id-ID').format(json.summary
                            .total_cogs));
                        $('#totalGrossProfit').text('Rp ' + new Intl.NumberFormat('id-ID').format(json
                            .summary.total_gross));
                        $('#totalExpenses').text('Rp ' + new Intl.NumberFormat('id-ID').format(json
                            .summary.total_expense));
                        $('#totalNetProfit').html('<strong>Rp ' + new Intl.NumberFormat('id-ID').format(
                            json.summary.total_net) + '</strong>');
                        return json.data;
                    }
                },
                columns: [{
                        data: 'date',
                        name: 'date'
                    },
                    {
                        data: 'revenue',
                        render: d => 'Rp ' + new Intl.NumberFormat('id-ID').format(d)
                    },
                    {
                        data: 'cogs',
                        render: d => 'Rp ' + new Intl.NumberFormat('id-ID').format(d)
                    },
                    {
                        data: 'grossProfit',
                        render: d => 'Rp ' + new Intl.NumberFormat('id-ID').format(d)
                    },
                    {
                        data: 'expenses',
                        render: d => 'Rp ' + new Intl.NumberFormat('id-ID').format(d)
                    },
                    {
                        data: 'netProfit',
                        render: d => '<strong>Rp ' + new Intl.NumberFormat('id-ID').format(d) +
                            '</strong>'
                    },
                ]
            });

            // 🔹 Filter dropdown
            $('#filter').change(function() {
                if ($(this).val() === 'custom') {
                    $('.custom-range').removeClass('d-none');
                } else {
                    $('.custom-range').addClass('d-none');
                    $('#start_date').val('');
                    $('#end_date').val('');
                    table.ajax.reload();
                }
            });

            // 🔹 Apply custom range
            $('#apply-filter').click(function() {
                table.ajax.reload();
            });
        });
    </script>
@endpush
