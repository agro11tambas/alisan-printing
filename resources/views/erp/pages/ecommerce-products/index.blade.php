@extends('erp.layouts.main')

@push('styles')
    <style>
        #ecommerceProductTable {
            width: 100% !important;
            min-width: 0;
        }

        #ecommerceProductTable_wrapper .dataTables_scrollBody {
            background-image: none !important;
        }

        .dataTables_scrollBody {
            scroll-behavior: smooth;
            height: calc(100vh - 260px) !important;
            min-height: calc(100vh - 260px) !important;
            max-height: calc(100vh - 260px) !important;
        }

        .ecommerce-delete-modal .modal-content {
            border: 0;
            border-radius: 14px;
            box-shadow: 0 18px 55px rgba(0, 0, 0, 0.22);
        }

        .ecommerce-delete-modal .modal-header,
        .ecommerce-delete-modal .modal-footer {
            border: 0;
        }

        .ecommerce-delete-icon {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(220, 53, 69, 0.12);
            color: #dc3545;
            font-size: 28px;
        }
    </style>
@endpush

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Ecommerce Product</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Ecommerce Product</li>
            </ul>
        </div>
        <div class="page-header-right ms-auto">
            <div class="page-header-right-items">
                <div class="d-flex align-items-center gap-2 page-header-right-items-wrapper">
                    <a href="{{ route('erp.ecommerce-products.create') }}" class="btn btn-primary">
                        <i class="feather-plus me-2"></i>
                        <span>Create Product</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="main-content m-0 m-md-2 m-lg-2 p-0 p-md-0 p-lg-0 pt-1 pt-md-0">
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
            </div>
        @endif

        <div class="row">
            <div class="col-lg-12">
                <div class="card stretch stretch-full">
                    <div class="card-body p-0">
                        <div class="row g-3 p-2 justify-content-between">
                            <div class="col-lg-5">
                                <div class="row g-3">
                                    <div class="col-lg-12">
                                        <label for="category_id" class="fw-semibold fs-12">Category</label>
                                        <select id="category_id" class="form-control" data-select2-selector="tag">
                                            <option value="">All</option>
                                            @foreach ($categories as $category)
                                                <option value="{{ $category->id }}" data-bg="bg-primary">
                                                    {{ $category->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <label for="search_keyword" class="fw-semibold fs-12">Search</label>
                                <input type="text" id="search_keyword" class="form-control"
                                    style="padding: 0.25rem 0.5rem; font-size: 0.875rem;" placeholder="Search product...">
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover bg-transparent" id="ecommerceProductTable">
                                <thead>
                                    <tr>
                                        <th>Image</th>
                                        <th>Title</th>
                                        <th>Status</th>
                                        <th>Category</th>
                                        <th>Unit</th>
                                        <th>Created At</th>
                                        <th>Action</th>
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

@push('modals')
    <div class="modal fade ecommerce-delete-modal" id="modalDeleteProduct" tabindex="-1"
        aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" id="formDeleteProduct" class="modal-content">
                @csrf
                @method('DELETE')
                <div class="modal-header pb-0">
                    <span></span>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body text-center px-4 pt-2 pb-4">
                    <span class="ecommerce-delete-icon mb-3">
                        <i class="feather-trash-2"></i>
                    </span>
                    <h4 class="fw-bold mb-2" id="deleteModalLabel">Hapus Product?</h4>
                    <p class="text-muted mb-4">
                        Product <strong id="ProductName" class="text-dark"></strong> beserta variant-nya akan dihapus.
                    </p>
                    <div class="rounded-3 bg-light p-3 text-start">
                        Data akan masuk ke soft delete dan masih dapat direstore kembali.
                    </div>
                </div>
                <div class="modal-footer px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="feather-trash-2 me-2"></i>Hapus Product
                    </button>
                </div>
            </form>
        </div>
    </div>
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {
            if ($.fn.DataTable.isDataTable('#ecommerceProductTable')) {
                return;
            }

            let currentTableRequest = null;

            const dataTable = $('#ecommerceProductTable').DataTable({
                processing: true,
                serverSide: true,
                deferRender: true,
                scrollY: 600,
                scroller: true,
                paging: true,
                searching: false,
                lengthChange: false,
                info: false,
                pagingType: 'simple',
                order: [
                    [1, 'asc']
                ],
                ajax: function(data, callback) {
                    currentTableRequest?.abort();

                    data.search_keyword = $('#search_keyword').val();
                    data.category_id = $('#category_id').val();

                    const request = $.ajax({
                        url: "{{ route('erp.ecommerce-products.data') }}",
                        data,
                        dataType: 'json'
                    });

                    currentTableRequest = request;

                    request
                        .done(callback)
                        .fail(function(xhr, status, error) {
                            if (status === 'abort') {
                                return;
                            }

                            console.error('Gagal memuat DataTable ecommerce product:', error);
                            callback({
                                draw: data.draw,
                                recordsTotal: 0,
                                recordsFiltered: 0,
                                data: []
                            });
                        })
                        .always(function() {
                            if (currentTableRequest === request) {
                                currentTableRequest = null;
                            }
                        });
                },
                columns: [{
                        data: 'image',
                        name: 'main_image',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'title',
                        name: 'title'
                    },
                    {
                        data: 'status',
                        name: 'is_active',
                        searchable: false
                    },
                    {
                        data: 'category',
                        name: 'category',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'unit',
                        name: 'unit',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'created_at',
                        name: 'created_at'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        visible: false
                    }
                ]
            });

            let searchTimer = null;
            let lastFilterKey = JSON.stringify({
                search: '',
                category: ''
            });

            function reloadIfFiltersChanged() {
                const filterKey = JSON.stringify({
                    search: $('#search_keyword').val() || '',
                    category: $('#category_id').val() || ''
                });

                if (filterKey === lastFilterKey) {
                    return;
                }

                lastFilterKey = filterKey;
                dataTable.ajax.reload();
            }

            $('#search_keyword').off('.ecommerceProducts').on('input.ecommerceProducts', function() {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(reloadIfFiltersChanged, 350);
            });

            $('#category_id').off('.ecommerceProducts').on(
                'change.ecommerceProducts',
                reloadIfFiltersChanged
            );

            $('#ecommerceProductTable tbody').on('click', 'tr', function(e) {
                if ($(e.target).closest('a, button, form').length) return;

                const $tr = $(this);
                const row = dataTable.row($tr);

                $('#ecommerceProductTable tbody tr')
                    .removeClass('action-shown action-active')
                    .next('.action-row')
                    .remove();

                if ($tr.hasClass('action-shown')) {
                    $tr.removeClass('action-shown action-active');
                    return;
                }

                const actionHtml = row.data().action;
                const colCount = $tr.find('td').length;
                const $actionRow = $(`
                    <tr class="action-row">
                        <td colspan="${colCount}" class="p-0">
                            <div class="d-flex justify-content-start">
                                <div class="dropdown w-auto position-relative">
                                    <ul class="dropdown-menu show static-action-menu shadow border rounded-3 p-1"
                                        style="display:block; position:absolute; left:200px; transform:none;">
                                        ${actionHtml}
                                    </ul>
                                </div>
                            </div>
                        </td>
                    </tr>
                `);

                $tr.after($actionRow).addClass('action-shown action-active');
            });

            $(document).on('click', function(e) {
                if ($(e.target).closest('#ecommerceProductTable').length) return;

                $('#ecommerceProductTable tbody tr')
                    .removeClass('action-shown action-active')
                    .next('.action-row')
                    .remove();
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('modalDeleteProduct');
            const form = document.getElementById('formDeleteProduct');
            const nameHolder = document.getElementById('ProductName');

            modal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;

                form.action = button.getAttribute('data-url');
                nameHolder.textContent = button.getAttribute('data-name');
            });
        });
    </script>
@endpush
