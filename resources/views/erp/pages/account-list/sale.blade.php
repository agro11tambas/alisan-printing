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
            background-image: none !important;
            height: 60vh !important;
            overflow-y: auto !important;
        }

        .dataTables_scrollBody {
            scroll-behavior: smooth;
        }

        #accountList tbody tr {
            animation: fadeIn 0.3s ease-in;
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
                        <div class="col-lg-4">
                            <div class="row g-3 justify-content-end">
                                <div class="col-md-6">
                                    <label for="search_account_type" class="fw-semibold fs-12">Search Account Type</label>
                                    <!-- <input type="text" id="search_account_type" name="search_account_type" class="form-control" style="padding: 0.5rem 1rem; font-size: 0.875rem;" placeholder="Search Account Type..."> -->
                                    <select id="search_account_type" name="search_account_type" class="form-control"
                                        style="padding: 0.5rem 1rem; font-size: 0.875rem; width: 200px !important;"
                                        data-select2-selector="status">
                                        <option value="">All</option>
                                        @foreach ($accountTypes as $type)
                                            <option value="{{ $type }}">{{ $type }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-6">
                                    <label for="search_particular" class="fw-semibold fs-12">Search Particular</label>
                                    <input type="text" id="search_particular" name="search_particular"
                                        class="form-control" style="padding: 0.5rem 1rem; font-size: 0.875rem;"
                                        placeholder="Search Particular...">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table bg-transparent table-hover table-vcenter text-nowrap mb-0" id="accountList">
                        <thead>
                            <tr>
                                {{-- <th>No</th> --}}
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

            let allData = [];
            let currentPage = 0;
            let isLoading = false;
            let hasMoreData = true;

            const table = $('#accountList').DataTable({
                processing: false,
                serverSide: false,
                deferRender: true,
                scrollY: '60vh',
                scrollCollapse: true,
                paging: false,
                searching: false,
                lengthChange: false,
                info: false,
                order: [
                    [0, 'desc']
                ],
                data: [],
                columns: [
                    // {
                    //     data: 'DT_RowIndex',
                    //     orderable: false,
                    //     searchable: false
                    // },
                    {
                        data: 'transaction_date'
                    },
                    {
                        data: 'account_type'
                    },
                    {
                        data: 'particular'
                    },
                    {
                        data: 'debit'
                    },
                    {
                        data: 'credit'
                    },
                ]
            });

            function loadMoreData() {
                if (isLoading || !hasMoreData) return;
                isLoading = true;

                $.ajax({
                    url: "{{ url('/erp/accounts/sale/data') }}",
                    type: 'GET',
                    data: {
                        start: currentPage * 20,
                        length: 20,
                        filter: $('#filter').val(),
                        start_date: $('#start_date').val(),
                        end_date: $('#end_date').val(),
                        search_particular: $('#search_particular').val(),
                        search_account_type: $('#search_account_type').val(),
                    },
                    success: function(response) {
                        if (response && response.data && response.data.length > 0) {
                            allData = allData.concat(response.data);
                            table.clear();
                            table.rows.add(allData).draw(false);
                            currentPage++;
                        } else {
                            hasMoreData = false;
                        }
                        isLoading = false;
                    },
                    error: function(xhr) {
                        console.error('❌ Error response:', xhr.responseJSON);
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
                table.clear().draw();
                loadMoreData();
            }

            $('#filter').on('change', function() {
                if ($(this).val() === 'custom') {
                    $('.custom-range').removeClass('d-none');
                } else {
                    $('.custom-range').addClass('d-none');
                    $('#start_date').val('');
                    $('#end_date').val('');
                    resetAndReload();
                }
            });

            $('#apply-filter').on('click', function() {
                resetAndReload();
            });

            $('#search_particular, #search_account_type').on('keyup change', function() {
                resetAndReload();
            });
        });
    </script>
@endpush
