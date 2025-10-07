@extends('erp.layouts.main')

@push('styles')
<style>
    @media (max-width: 768px) {

        #reportItemsTable td.desktop-only,
        #reportItemsTable th.desktop-only {
            display: none !important;
        }
    }

    #reportItemsTable {
        width: 100% !important;
        min-width: 0;
    }

    #reportItemsTable_wrapper .dataTables_scrollBody {
        /* background: #fff !important; */
        background-image: none !important;
    }
</style>
@endpush

@section('breadcrumb')
<div class="page-header sticky-top">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">Warehouse</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
            <li class="breadcrumb-item">Warehouse</li>
        </ul>
    </div>
    <div class="page-header-right ms-auto">
        <div class="page-header-right-items">
            <div class="d-flex d-md-none">
                <a href="javascript:void(0)" class="page-header-right-close-toggle">
                    <i class="feather-arrow-left me-2"></i><span>Back</span>
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
    <div class="row">
        <div class="col-lg-12">
            <div class="card stretch stretch-full">
                <div class="card-body p-0">
                    <div class="row g-3 p-4 justify-content-between">
                        <div class="col-lg-4 me-2">
                            
                        </div>
                        <div class="col-lg-4">
                            <div class="row g-3 justify-content-end">
                                <div class="col-lg-6">
                                    <label for="product_name" class="fw-semibold fs-12">Item Name</label>
                                    <input type="text" id="product_name" name="product_name" class="form-control" style="padding: 0.5rem 1rem; font-size: 0.875rem;" placeholder="Search Item...">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover bg-transparent" id="reportItemsTable">
                            <thead>
                                <tr>
                                    <th class="wd-30">No</th>
                                    <th>Item Name</th>
                                    <!-- <th>Purchase Stock</th> -->
                                    <th>Current Stock</th>
                                    <th>Stock After Sales</th>
                                    <th>Incoming Stock</th>
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
        let table = $('#reportItemsTable').DataTable({
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
                url: "{{ url('/erp/inventory/report-items/data') }}",
                data: function(d) {
                    d.product_name = $('#product_name').val();
                }
            },
            columns: [{
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'name',
                    name: 'name'
                },
                // {
                //     data: 'stock',
                //     name: 'stock'
                // },
                {
                    data: 'inventory_stock',
                    name: 'inventory_stock'
                },
                {
                    data: 'stock_after_sales',
                    name: 'stock_after_sales'
                },
                {
                    data: 'incoming_stock',
                    name: 'incoming_stock'
                }
            ]
        });

        $('#product_name').on('keyup change', function() {
            table.ajax.reload();
        });
    });
</script>
@endpush