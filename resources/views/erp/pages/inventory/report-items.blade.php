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
            height: 60vh !important;
            overflow-y: auto !important;
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
                <li class="breadcrumb-item active">Report Items</li>
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
                                <div class="row g-3 justify-content-start">
                                    <div class="col-lg-6">
                                        <label for="product_name" class="fw-semibold fs-12">Item Name</label>
                                        <input type="text" id="product_name" name="product_name" class="form-control"
                                            style="padding: 0.5rem 1rem; font-size: 0.875rem;" placeholder="Search Item...">
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4">
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
                                        <th>Outgoing Stock</th>
                                        <th>Action</th>
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
    <!-- Modal Defect -->
    <div class="modal fade" id="defectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="defectForm" method="POST" action="{{ url('/erp/defect-product/store') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Add Product Defect</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="product_id" id="defect_product_id">

                        <div class="mb-3">
                            <label class="form-label">Product Name</label>
                            <input type="text" id="defect_product_name" class="form-control" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Defect Quantity</label>
                            <input type="number" name="quantity" id="defect_quantity" class="form-control" min="1"
                                required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Note (optional)</label>
                            <textarea name="note" id="defect_note" class="form-control" rows="2"></textarea>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Save Defect</button>
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
            let lastKeyword = '';

            let table = $('#reportItemsTable').DataTable({
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
                    [3, 'asc']
                ],
                data: [],
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'name'
                    },
                    // { data: 'stock' },
                    {
                        data: 'inventory_stock'
                    },
                    {
                        data: 'stock_after_sales'
                    },
                    {
                        data: 'incoming_stock'
                    },
                    {
                        data: 'outgoing_stock'
                    },
                    {
                        data: 'action',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    }
                ]
            });

            function loadMoreData(reset = false) {
                if (isLoading) return;
                if (!hasMoreData && !reset) return;
                isLoading = true;

                if (reset) {
                    allData = [];
                    currentPage = 0;
                    hasMoreData = true;
                    table.clear().draw();
                }

                $.ajax({
                    url: "{{ url('/erp/inventory/report-items/data') }}",
                    type: 'GET',
                    data: {
                        start: (reset ? 0 : currentPage * 200),
                        length: 200,
                        product_name: $('#product_name').val().trim() || null,
                    },
                    success: function(res) {
                        if (res && res.data && res.data.length > 0) {
                            if (reset) allData = [];
                            allData = allData.concat(res.data);
                            table.clear();
                            table.rows.add(allData).draw(false);
                            currentPage++;
                            hasMoreData = true;
                        } else {
                            if (reset) {
                                currentPage = 0;
                                hasMoreData = true;
                            } else {
                                hasMoreData = false;
                            }
                        }
                        isLoading = false;
                    },
                    error: function(xhr) {
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

            let searchTimer;
            $('#product_name').on('keyup change', function() {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(() => {
                    const keyword = $(this).val().trim();
                    if (keyword !== lastKeyword) {
                        lastKeyword = keyword;
                        loadMoreData(true); // 🔹 reset total, biar hasil balik lagi
                    }
                }, 200);
            });
        });

        $(document).on('click', '.btnDefect', function() {
            const id = $(this).data('id');
            const name = $(this).data('name');

            $('#defect_product_id').val(id);
            $('#defect_product_name').val(name);
            $('#defect_quantity').val('');
            $('#defect_note').val('');

            $('#defectModal').modal('show');
        });

        $('#defectForm').on('submit', function(e) {
            e.preventDefault();

            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: $(this).serialize(),
                success: function(res) {
                    $('#defectModal').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Defect saved!',
                        text: res.message || 'Defect product successfully recorded.'
                    });
                    $('#reportItemsTable').DataTable().ajax.reload();
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: xhr.responseJSON?.message || 'Failed to save defect product.'
                    });
                }
            });
        });
    </script>
@endpush
