@extends('erp.layouts.main')

@push('styles')
    <style>
        @media (max-width: 768px) {

            #rejectProductsTable td.desktop-only,
            #rejectProductsTable th.desktop-only {
                display: none !important;
            }
        }

        #rejectProductsTable {
            width: 100% !important;
            min-width: 0;
        }

        #rejectProductsTable_wrapper .dataTables_scrollBody {
            background-image: none !important;
        }
    </style>
@endpush

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Inventory</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Inventory</li>
                <li class="breadcrumb-item">Reject Products</li>
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
                text: "{{ session('success') }}"
            });
        </script>
    @endif
    @if (session('error'))
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: "{{ session('error') }}"
            });
        </script>
    @endif

    <div class="main-content m-0 m-md-2 m-lg-2 p-0 p-md-0 p-lg-0 pt-1 pt-md-0">
        <div class="row">
            <div class="col-lg-12">
                <div class="card stretch stretch-full">
                    <div class="card-body p-0">
                        <div class="row g-3 p-2 justify-content-between">
                            <div class="col-lg-2">
                                <label for="product_name" class="fw-semibold fs-12">Product Name</label>
                                <input type="text" id="product_name" name="product_name" class="form-control"
                                    placeholder="Search Product..." style="padding: 0.25rem 0.5rem; font-size: 0.875rem;">
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover bg-transparent" id="rejectProductsTable">
                                <thead>
                                    <tr>
                                        <th class="wd-30">No</th>
                                        <th>Product</th>
                                        <th>Total Reject</th>
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
            let dataTable = $('#rejectProductsTable').DataTable({
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
                    url: "{{ url('/erp/adjustment-products/reject-products/data') }}",
                    data: function(d) {
                        d.product_name = $('#product_name').val();
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'product_name',
                        name: 'product_name'
                    },
                    {
                        data: 'total_reject',
                        name: 'total_reject',
                        render: data =>
                            `<span class='text-danger fw-bold'>${parseInt(data).toLocaleString()}</span>`
                    },
                ]
            });

            $('#product_name').on('keyup change', function() {
                dataTable.ajax.reload();
            });

            $('#rejectProductsTable tbody').on('click', 'tr', function(e) {
                if ($(e.target).closest('td.dt-control').length) return;
                let $tr = $(this);
                let row = dataTable.row($tr);

                $('#rejectProductsTable tbody tr').removeClass('action-shown').next('.action-row').remove();

                if ($tr.hasClass('action-shown')) {
                    $tr.removeClass('action-shown');
                } else {
                    let actionHtml = row.data().action;
                    let colCount = $tr.find('td').length;
                    let $actionRow = $(`
                    <tr class="action-row">
                        <td colspan="${colCount}">
                            <div class="d-flex justify-content-center">${actionHtml}</div>
                        </td>
                    </tr>
                `);

                    $tr.after($actionRow);
                    $tr.addClass('action-shown');
                }
            });

            $(document).on('click', function(e) {
                if ($(e.target).closest('#rejectProductsTable').length) return;
                $('#rejectProductsTable tbody tr').removeClass('action-shown').next('.action-row').remove();
            });
        });
    </script>
@endpush
