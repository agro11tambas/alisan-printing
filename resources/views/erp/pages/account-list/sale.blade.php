@extends('erp.layouts.main')

@push('styles')
<style>
    @media (max-width: 768px) {

        #accountList td.desktop-only,
        #accountList th.desktop-only {
            display: none !important;
        }
    }

    #accountList {
        width: 100% !important;
        min-width: 0;
    }

    #accountList_wrapper .dataTables_scrollBody {
        /* background: #fff !important; */
        background-image: none !important;
    }
</style>
@endpush

@section('breadcrumb')
<div class="page-header sticky-top">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">Sale</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
            <li class="breadcrumb-item">Sale</li>
        </ul>
    </div>
    <div class="page-header-right ms-auto">

    </div>
</div>
@endsection

@section('content')
@if(session('success'))
<script>
    Swal.fire({
        icon: 'success',
        title: 'Berhasil!',
        text: "{{ session('success') }}",
    });
</script>
@endif
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
    <div class="col-lg-12">
        <div class="card stretch stretch-full">
            <div class="card-body p-0">
                <div class="row g-3 p-4 justify-content-between">
                    <div class="col-lg-auto me-2">
                        <div class="row g-3 align-items-start">
                            <div class="col-lg-auto">
                                <label for="filter" class="fw-semibold fs-12">Filter Date</label>
                                <div class="col-lg-4 me-2">
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
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="row g-3 justify-content-end">
                            <div class="col-md-6">
                                <label for="search_account_type" class="fw-semibold fs-12">Search Account Type</label>
                                <!-- <input type="text" id="search_account_type" name="search_account_type" class="form-control" style="padding: 0.5rem 1rem; font-size: 0.875rem;" placeholder="Search Account Type..."> -->
                                <select id="search_account_type" name="search_account_type" class="form-control" style="padding: 0.5rem 1rem; font-size: 0.875rem; width: 200px !important;" data-select2-selector="status">
                                    <option value="">All</option>
                                    @foreach ($accountTypes as $type)
                                    <option value="{{ $type }}">{{ $type }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-6">
                                <label for="search_particular" class="fw-semibold fs-12">Search Particular</label>
                                <input type="text" id="search_particular" name="search_particular" class="form-control" style="padding: 0.5rem 1rem; font-size: 0.875rem;" placeholder="Search Particular...">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table bg-transparent table-hover table-vcenter text-nowrap mb-0" id="accountList">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Date</th>
                            <th>Account Type</th>
                            <th>Particular</th>
                            <th>Debit</th>
                            <th>Credit</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // // Cegah reinitialisasi
        // if ($.fn.DataTable.isDataTable('#accountList')) {
        //     $('#accountList').DataTable().clear().destroy();
        // }

        const table = $('#accountList').DataTable({
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
                url: "{{ url('/erp/accounts/sale/data') }}",
                data: function(d) {
                    d.filter = $('#filter').val();
                    d.start_date = $('#start_date').val();
                    d.end_date = $('#end_date').val();
                    d.search_particular = $('#search_particular').val();
                    d.search_account_type = $('#search_account_type').val();
                }
            },
            columns: [{
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'transaction_date',
                    name: 'transaction_date'
                },
                {
                    data: 'account_type',
                    name: 'account_type'
                },
                {
                    data: 'particular',
                    name: 'particular'
                },
                {
                    data: 'debit',
                    name: 'debit'
                },
                {
                    data: 'credit',
                    name: 'credit'
                },
            ]
        });

        // $('#name, #type').on('change keyup', function() {
        //     table.ajax.reload();
        // });

        $('#filter').on('change', function() {
            if ($(this).val() === 'custom') {
                $('.custom-range').removeClass('d-none');
            } else {
                $('.custom-range').addClass('d-none');
                $('#start_date').val('');
                $('#end_date').val('');
                table.ajax.reload(); // reload langsung saat non-custom dipilih
            }
        });

        // Tombol apply untuk filter custom
        $('#apply-filter').on('click', function() {
            table.ajax.reload();
        });

        $('#search_particular, #search_account_type').on('keyup change', function() {
            table.ajax.reload();
        });
    });
</script>
@endpush