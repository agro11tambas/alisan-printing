@extends('erp.layouts.main')

@push('styles')
    <style>
        @media (max-width: 768px) {

            #openingStockList td.desktop-only,
            #openingStockList th.desktop-only {
                display: none !important;
            }
        }

        #openingStockList {
            width: 100% !important;
            min-width: 0;
        }

        #openingStockList_wrapper .dataTables_scrollBody {
            /* background: #fff !important; */
            background-image: none !important;
            height: 60vh !important;
            overflow-y: auto !important;
        }
    </style>
@endpush

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Opening Stock Overview</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item active">Opening Stock Overview</li>
            </ul>
        </div>
        <div class="page-header-right ms-auto">
            <div class="page-header-right-items d-flex align-items-center gap-2">
                <a href="/erp/opening-stock/edit" class="btn btn-primary" id="btnCreateOpening">
                    <i class="feather-plus me-2"></i>
                    <span>Create Opening Stock</span>
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
                    <div class="card-body px-0 py-2">
                        <div class="row px-2 mb-2">
                            <div class="col-lg-12 d-flex justify-content-between align-items-center">
                                <h4>Opening Stock (Inventory & Production)</h4>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover bg-transparent align-middle" id="openingStockList">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Product Name</th>
                                        <th>Opening Stock (Inventory)</th>
                                        <th>Opening Stock (Production)</th>
                                        <th>Opening Rate</th>
                                        <th>Minimum Stock</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
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

            const table = $('#openingStockList').DataTable({
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
                    [1, 'asc']
                ],
                data: [],
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        className: 'text-center',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'product_name',
                        name: 'product_name'
                    },
                    {
                        data: 'opening_stock',
                        name: 'opening_stock'
                    },
                    {
                        data: 'production_stock',
                        name: 'production_stock'
                    },
                    {
                        data: 'opening_rate',
                        name: 'opening_rate'
                    },
                    {
                        data: 'minimum_stock',
                        name: 'minimum_stock'
                    },
                ],
                pageLength: 25,
                language: {
                    search: "Search:",
                    lengthMenu: "Show _MENU_ entries",
                    zeroRecords: "No data available",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    infoEmpty: "No records available",
                    infoFiltered: "(filtered from _MAX_ total entries)"
                }
            });

            function loadMoreData() {
                if (isLoading || !hasMoreData) return;
                isLoading = true;

                $.ajax({
                    url: "{{ url('/erp/opening-stock/data') }}",
                    type: 'GET',
                    data: {
                        start: currentPage * 50,
                        length: 50,
                    },
                    success: function(res) {
                        if (res && res.data && res.data.length > 0) {
                            allData = allData.concat(res.data);
                            table.clear();
                            table.rows.add(allData).draw(false);
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

        });
    </script>
@endpush
