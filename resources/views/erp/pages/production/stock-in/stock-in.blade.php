@extends('erp.layouts.main')

@push('styles')
    <style>
        @media (max-width: 768px) {

            #inventoryTable td.desktop-only,
            #inventoryTable th.desktop-only {
                display: none !important;
            }
        }

        #inventoryTable {
            width: 100% !important;
            min-width: 0;
        }

        #inventoryTable_wrapper .dataTables_scrollBody {
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

        #inventoryTable tbody tr {
            animation: fadeIn 0.3s ease-in;
        }
    </style>
@endpush

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Stock In</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Warehouse</li>
                <li class="breadcrumb-item">Stock In</li>
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
                            <div class="col-lg-6">
                                <div class="row justify-content-end">
                                    <div class="col-lg-3">
                                        <label for="progress_status" class="fw-semibold fs-12">Progress Status</label>
                                        <select id="progress_status" class="form-control"
                                            style="padding: 0.25rem 0.5rem; font-size: 0.875rem;">
                                            <option value="progress">Progress</option>
                                            <option value="completed">Completed</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-3">
                                        <label for="search_product" class="fw-semibold fs-12">Search Product</label>
                                        <input type="text" id="search_product" class="form-control"
                                            placeholder="Product name...">
                                    </div>
                                    <div class="col-lg-6">
                                        <label for="search_type" class="fw-semibold fs-12">Filter By</label>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <select id="search_type" class="form-control"
                                                    style="padding: 0.25rem 0.5rem; font-size: 0.875rem;">
                                                    <option value="partner">Supplier / Customer</option>
                                                    <option value="invoice_number">Invoice Number</option>
                                                    {{-- <option value="type">Transaction Type</option> --}}
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <input type="text" id="search_keyword" name="search_keyword"
                                                    class="form-control search-input"
                                                    style="padding: 0.25rem 0.5rem; font-size: 0.875rem;"
                                                    placeholder="Search..." />

                                                {{-- <select id="search_type_dropdown" class="form-control search-input d-none"
                                                    style="padding: 0.25rem 0.5rem; font-size: 0.875rem;">
                                                    <option value="">All</option>
                                                    <option value="purchase">Purchase</option>
                                                    <option value="sale_return">Sale Return</option>
                                                </select> --}}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover bg-transparent" id="inventoryTable">
                                <thead>
                                    <tr>
                                        <th class="wd-200">Number</th>
                                        <th class="wd-200">Supplier</th>
                                        <th>Stock In</th>
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

@push('scripts')
    <script>
        $(document).ready(function() {

            let allData = [];
            let currentPage = 0;
            let isLoading = false;
            let hasMoreData = true;

            const dataTable = $('#inventoryTable').DataTable({
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
                    [3, 'desc']
                ],
                data: [],
                columns: [
                    // {
                    //     data: 'DT_RowIndex',
                    //     orderable: false,
                    //     searchable: false
                    // },
                    {
                        data: 'transaction_number'
                    },
                    {
                        data: 'partner_name'
                    },
                    {
                        data: 'stock_in'
                    },
                    {
                        data: 'date',
                        visible: false,
                        searchable: false
                    }
                ]
            });

            let searchTimer = null;
            let currentRequest = null;

            function loadMoreData() {
                if (isLoading || !hasMoreData) return;
                isLoading = true;

                // 🚫 Batalkan request sebelumnya kalau masih jalan
                if (currentRequest) {
                    currentRequest.abort();
                }

                currentRequest = $.ajax({
                    url: "{{ url('/erp/productions/stock-in/data') }}",
                    type: 'GET',
                    data: {
                        start: currentPage * 50,
                        length: 50,
                        filter: $('#filter').val(),
                        start_date: $('#start_date').val(),
                        end_date: $('#end_date').val(),
                        search_type: $('#search_type').val(),
                        search_keyword: $('#search_keyword').val(),
                        search_type_dropdown: $('#search_type_dropdown').val(),
                        progress_status: $('#progress_status').val(),
                        search_product: $('#search_product').val(),
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
                            alert(xhr.responseJSON?.message || 'Error loading data.');
                        }
                        isLoading = false;
                    }
                });
            }

            loadMoreData();


            $('.dataTables_scrollBody').on('scroll', function() {
                const scrollTop = $(this).scrollTop();
                const scrollHeight = $(this)[0].scrollHeight;
                const clientHeight = $(this).height();

                // Load earlier (70%) without delay
                if (scrollTop + clientHeight >= scrollHeight * 0.70) {
                    loadMoreData();
                }
            });

            function resetAndReload() {
                allData = [];
                currentPage = 0;
                hasMoreData = true;
                dataTable.clear().draw();
                loadMoreData();
            }

            // $('#progress_status').on('change', function() {
            //     resetAndReload();
            // });

            let lastProgressStatus = $('#progress_status').val();

            $('#filter, #apply-filter, #progress_status, #search_type, #search_type_dropdown, #start_date, #end_date')
                .on('change keyup input paste click', function(e) {
                    clearTimeout(searchTimer);
                    searchTimer = setTimeout(() => {
                        if (e.target.id === 'progress_status') {
                            const currentVal = $('#progress_status').val();
                            if (currentVal === lastProgressStatus)
                                return; // ⛔ jangan reload kalau gak berubah
                            lastProgressStatus = currentVal;
                        }

                        // toggle custom range
                        if ($('#filter').val() === 'custom') {
                            $('.custom-range').removeClass('d-none');
                        } else {
                            $('.custom-range').addClass('d-none');
                        }

                        // toggle dropdown vs keyword
                        if ($('#search_type').val() === 'type') {
                            $('#search_keyword').addClass('d-none').val('');
                            $('#search_type_dropdown').removeClass('d-none');
                        } else {
                            $('#search_keyword').removeClass('d-none');
                            $('#search_type_dropdown').addClass('d-none').val('');
                        }

                        resetAndReload();
                    }, 100);
                });

            // $('#apply-filter').on('click', function() {
            //     resetAndReload();
            // });

            $('#search_type').on('change', function() {
                const selected = $(this).val();
                if (selected === 'type') {
                    $('#search_keyword').addClass('d-none').val('');
                    $('#search_type_dropdown').removeClass('d-none');
                } else {
                    $('#search_keyword').removeClass('d-none');
                    $('#search_type_dropdown').addClass('d-none').val('');
                }
                resetAndReload();
            });


            $('#search_type_dropdown').on('keyup change input paste', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => resetAndReload(), 400);
            });

            // 🔍 Search keyword: ENTER untuk search
            $('#search_keyword, #search_product').on('keypress', function(e) {
                if (e.which === 13) { // ENTER
                    e.preventDefault();
                    resetAndReload();
                }
            });

            // Hapus keyword → reload
            $('#search_keyword, #search_product').on('input', function() {
                if ($(this).val().trim() === '') {
                    resetAndReload();
                }
            });



            $('#inventoryTable tbody').on('click', 'tr', function(e) {
                if ($(e.target).closest('td.dt-control').length) return;

                let $tr = $(this);
                let row = dataTable.row($tr);

                $('#inventoryTable tbody tr').removeClass('action-shown').next('.action-row').remove();

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
                if ($(e.target).closest('#inventoryTable').length) return;
                $('#inventoryTable tbody tr').removeClass('action-shown').next('.action-row').remove();
            });

            $(document).on('click', function(e) {
                if (!$(e.target).closest('#inventoryTable tbody tr, #inventoryTableMobile tbody tr')
                    .length) {
                    $('#inventoryTable tbody tr.shown, #inventoryTableMobile tbody tr.shown').each(
                        function() {
                            var tr = $(this);
                            var table = tr.closest('table').attr('id') === 'inventoryTable' ?
                                dataTable : dataTableMobile;
                            var row = table.row(tr);
                            if (row.child.isShown()) {
                                row.child.hide();
                                tr.removeClass('shown');
                            }
                        });
                }
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
    </script>
@endpush
