@extends('erp.layouts.main')

@push('styles')
    <style>
        @media (max-width: 768px) {

            #productBundleTable td.desktop-only,
            #productBundleTable th.desktop-only {
                display: none !important;
            }
        }

        #productBundleTable {
            width: 100% !important;
            min-width: 0;
        }

        #productBundleTable_wrapper .dataTables_scrollBody {
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
                <h5 class="m-b-10">Product Bundles</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Product Bundles</li>
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
                    <a href="/erp/products/product-bundles/create-product-bundle" class="btn btn-primary">
                        <i class="feather-plus me-2"></i>
                        <span>Create Product Bundles</span>
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
    <div class="main-content m-0 m-md-2 m-lg-2 p-0 p-md-0 p-lg-0 pt-2 pt-md-0">
        <div class="row">
            <div class="col-lg-12">
                <div class="card stretch stretch-full">
                    <div class="card-body p-0">
                        <div class="row g-3 p-4 justify-content-between">
                            <div class="col-lg-4 me-2">

                            </div>
                            <div class="col-lg-4">
                                <div class="row g-3 justify-content-end">
                                    <div class="col-lg-12">
                                        <label for="search_type" class="fw-semibold fs-12">Filter By</label>
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <select id="search_type" class="form-control"
                                                    style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                                    <option value="name">Product Name</option>
                                                    <option value="sku">SKU</option>
                                                </select>
                                            </div>
                                            <div class="col-md-8">
                                                <input type="text" id="search_keyword" name="search_keyword"
                                                    class="form-control search-input"
                                                    style="padding: 0.5rem 1rem; font-size: 0.875rem;"
                                                    placeholder="Search..." />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover bg-transparent" id="productBundleTable">
                                <thead>
                                    <tr>
                                        <th class="wd-10">No</th>
                                        <th class="wd-40">Primary Product</th>
                                        <th class="wd-50">Secondary Products</th>
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
    <div class="modal fade" id="modalDeleteProduct" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" id="formDeleteProduct">
                @csrf
                @method('DELETE')
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title text-white" id="deleteModalLabel">Hapus Product</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Apakah Anda yakin ingin menghapus Product <strong id="ProductBundleName"></strong>?</p>
                        <p class="text-muted">Data yang dihapus tidak dapat dikembalikan.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-md" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger btn-md">Hapus</button>
                    </div>
                </div>
            </form>
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

            const dataTable = $('#productBundleTable').DataTable({
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
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'primary_product'
                    },
                    {
                        data: 'secondary_products',
                        orderable: false,
                        searchable: false
                    }
                ]
            });

            function loadMoreData() {
                if (isLoading || !hasMoreData) return;
                isLoading = true;

                $.ajax({
                    url: "{{ url('/erp/products/product-bundles/data') }}",
                    type: 'GET',
                    data: {
                        start: currentPage * 25,
                        length: 25,
                        search_type: $('#search_type').val(),
                        search_keyword: $('#search_keyword').val(),
                        category_id: $('#category_id').val(),
                        tag_id: $('#tag_id').val()
                    },
                    success: function(res) {
                        if (res && res.data && res.data.length > 0) {
                            allData = allData.concat(res.data);
                            dataTable.clear();
                            dataTable.rows.add(allData).draw(false);
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

            $('#search_type, #search_keyword, #category_id, #tag_id').on('change keyup', function() {
                allData = [];
                currentPage = 0;
                hasMoreData = true;
                dataTable.clear().draw();
                loadMoreData();
            });

            $('#productBundleTable tbody').on('click', 'tr', function(e) {
                if ($(e.target).closest('.dropdown, button, a').length) return;

                let $tr = $(this);
                let row = dataTable.row($tr);

                $('#productBundleTable tbody tr')
                    .removeClass('action-shown')
                    .next('.action-row')
                    .remove();

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
                if ($(e.target).closest('#productBundleTable').length) return;

                $('#productBundleTable tbody tr')
                    .removeClass('action-shown')
                    .next('.action-row')
                    .remove();
            });

        });

        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('modalDeleteProduct');
            const form = document.getElementById('formDeleteProduct');
            const nameHolder = document.getElementById('ProductBundleName');

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
