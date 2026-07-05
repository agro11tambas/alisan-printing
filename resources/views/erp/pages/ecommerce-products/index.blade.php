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
    @if (session('success'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: @json(session('success')),
                });
            });
        </script>
    @endif

    @if (session('error'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: @json(session('error')),
                });
            });
        </script>
    @endif

    <div class="main-content m-0 m-md-2 m-lg-2 p-0 p-md-0 p-lg-0 pt-1 pt-md-0">
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
                                        <th>Category</th>
                                        <th>Unit</th>
                                        <th>Created At</th>
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
                        <p>Apakah Anda yakin ingin menghapus Product <strong id="ProductName"></strong>?</p>
                        <p class="text-muted">Product dan option akan masuk ke soft delete.</p>
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
                ajax: {
                    url: "{{ route('erp.ecommerce-products.data') }}",
                    data: function(d) {
                        d.search_keyword = $('#search_keyword').val();
                        d.category_id = $('#category_id').val();
                    }
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

            $('#search_keyword').on('keyup', function() {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(() => dataTable.ajax.reload(), 200);
            });

            $('#category_id').on('change', function() {
                dataTable.ajax.reload();
            });

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
