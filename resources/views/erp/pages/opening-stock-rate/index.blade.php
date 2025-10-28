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
                    <div class="card-body px-0 py-4">
                        <div class="row px-4 mb-3">
                            <div class="col-lg-12 d-flex justify-content-between align-items-center">
                                <h4>Opening Stock (Inventory & Production)</h4>
                                <a href="/erp/opening-stock/edit" class="btn btn-primary" id="btnCreateOpening">
                                    <i class="feather-plus me-2"></i>
                                    <span>Create Opening Stock</span>
                                </a>
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
            $('#openingStockList').DataTable({
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
                    url: "{{ url('/erp/opening-stock/data') }}",
                },
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
                        data: 'inventory_stock',
                        name: 'inventory_stock',
                    },
                    {
                        data: 'production_stock',
                        name: 'production_stock',
                    },
                    {
                        data: 'avg_cost',
                        name: 'avg_cost',
                    },
                    {
                        data: 'minimum_stock',
                        name: 'minimum_stock',
                    },
                ],
                order: [
                    [1, 'asc']
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
        });
    </script>
@endpush
