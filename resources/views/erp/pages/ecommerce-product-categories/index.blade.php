@extends('erp.layouts.main')

@push('styles')
    <style>
        #ecommerceMainCategoryTable,
        #ecommerceSubCategoryTable {
            width: 100% !important;
            min-width: 0;
        }

        #ecommerceMainCategoryTable_wrapper .dataTables_scrollBody,
        #ecommerceSubCategoryTable_wrapper .dataTables_scrollBody {
            background-image: none !important;
        }

        .dataTables_scrollBody {
            scroll-behavior: smooth;
            height: calc(100vh - 300px) !important;
            min-height: calc(100vh - 300px) !important;
            max-height: calc(100vh - 300px) !important;
        }
    </style>
@endpush

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Ecommerce Product Category</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Ecommerce Product Category</li>
            </ul>
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

    @php($subTabActive = request('tab') === 'sub')

    <div class="main-content m-0 m-md-2 m-lg-2 p-0 p-md-0 p-lg-0 pt-1 pt-md-0">
        <ul class="nav nav-tabs mb-3" id="categoryModuleTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $subTabActive ? '' : 'active' }}" id="main-categories-tab"
                    data-bs-toggle="tab" data-bs-target="#main-categories-tab-pane" type="button" role="tab"
                    aria-controls="main-categories-tab-pane" aria-selected="{{ $subTabActive ? 'false' : 'true' }}">
                    Main Category
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $subTabActive ? 'active' : '' }}" id="sub-categories-tab"
                    data-bs-toggle="tab" data-bs-target="#sub-categories-tab-pane" type="button" role="tab"
                    aria-controls="sub-categories-tab-pane" aria-selected="{{ $subTabActive ? 'true' : 'false' }}">
                    Sub Category
                </button>
            </li>
        </ul>

        <div class="tab-content" id="categoryModuleTabContent">
            <div class="tab-pane fade {{ $subTabActive ? '' : 'show active' }}" id="main-categories-tab-pane"
                role="tabpanel" aria-labelledby="main-categories-tab" tabindex="0">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card stretch stretch-full">
                            <div class="card-body p-0">
                                <div class="row g-3 p-2 justify-content-end align-items-end">
                                    <div class="col-lg-3">
                                        <label for="main_search_keyword" class="fw-semibold fs-12">Search</label>
                                        <input type="text" id="main_search_keyword" class="form-control"
                                            style="padding: 0.25rem 0.5rem; font-size: 0.875rem;" autocomplete="off"
                                            placeholder="Search main category...">
                                    </div>
                                    <div class="col-auto">
                                        <a href="{{ route('erp.ecommerce-product-categories.create') }}"
                                            class="btn btn-primary text-nowrap">
                                            <i class="feather-plus me-2"></i>
                                            <span>Create Main Category</span>
                                        </a>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-hover bg-transparent" id="ecommerceMainCategoryTable">
                                        <thead>
                                            <tr>
                                                <th class="wd-30">No</th>
                                                <th>Image</th>
                                                <th>Name</th>
                                                <th>Sub Category</th>
                                                <th>Slug</th>
                                                <th>Description</th>
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

            <div class="tab-pane fade {{ $subTabActive ? 'show active' : '' }}" id="sub-categories-tab-pane"
                role="tabpanel" aria-labelledby="sub-categories-tab" tabindex="0">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card stretch stretch-full">
                            <div class="card-body p-0">
                                <div class="row g-3 p-2 justify-content-end align-items-end">
                                    <div class="col-lg-3 me-auto">
                                        <label for="filter_main_category_id" class="fw-semibold fs-12">Main
                                            Category</label>
                                        <select id="filter_main_category_id" class="form-control"
                                            style="padding: 0.25rem 0.5rem; font-size: 0.875rem;">
                                            <option value="">Semua main category</option>
                                            @foreach ($mainCategoryOptions as $mainCategory)
                                                <option value="{{ $mainCategory->id }}">{{ $mainCategory->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-lg-3">
                                        <label for="sub_search_keyword" class="fw-semibold fs-12">Search</label>
                                        <input type="text" id="sub_search_keyword" class="form-control"
                                            style="padding: 0.25rem 0.5rem; font-size: 0.875rem;" autocomplete="off"
                                            placeholder="Search sub category...">
                                    </div>
                                    <div class="col-auto">
                                        <a href="{{ route('erp.ecommerce-product-categories.create', ['type' => 'sub']) }}"
                                            class="btn btn-primary text-nowrap">
                                            <i class="feather-plus me-2"></i>
                                            <span>Create Sub Category</span>
                                        </a>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-hover bg-transparent" id="ecommerceSubCategoryTable">
                                        <thead>
                                            <tr>
                                                <th class="wd-30">No</th>
                                                <th>Image</th>
                                                <th>Name</th>
                                                <th>Main Category</th>
                                                <th>Slug</th>
                                                <th>Description</th>
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
        </div>
    </div>
@endsection

@push('modals')
    <div class="modal fade" id="modalDeleteCategory" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" id="formDeleteCategory">
                @csrf
                @method('DELETE')
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title text-white" id="deleteModalLabel">Hapus Category</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Apakah Anda yakin ingin menghapus Category <strong id="CategoryName"></strong>?</p>
                        <p class="text-muted">Data akan masuk ke soft delete.</p>
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
            const dataUrl = "{{ route('erp.ecommerce-product-categories.data') }}";

            function buildTable(selector, scope, extraParams, relationColumn) {
                return $(selector).DataTable({
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
                        [2, 'asc']
                    ],
                    ajax: {
                        url: dataUrl,
                        data: function(d) {
                            d.scope = scope;
                            extraParams(d);
                        }
                    },
                    columns: [{
                            data: 'DT_RowIndex',
                            name: 'DT_RowIndex',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'image',
                            name: 'image',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'name',
                            name: 'name'
                        },
                        {
                            data: relationColumn,
                            name: relationColumn,
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'slug',
                            name: 'slug'
                        },
                        {
                            data: 'description',
                            name: 'description'
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
            }

            // Baris diklik -> munculin menu action di bawahnya (sama seperti sebelumnya)
            function bindRowActions(selector, table) {
                $(`${selector} tbody`).on('click', 'tr', function(e) {
                    if ($(e.target).closest('a, button, form').length) return;

                    const $tr = $(this);
                    if ($tr.hasClass('action-row')) return;

                    const row = table.row($tr);
                    if (!row.data()) return;

                    $(`${selector} tbody tr`)
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
                    if ($(e.target).closest(selector).length) return;

                    $(`${selector} tbody tr`)
                        .removeClass('action-shown action-active')
                        .next('.action-row')
                        .remove();
                });
            }

            const mainTable = buildTable('#ecommerceMainCategoryTable', 'root', function(d) {
                d.search_keyword = $('#main_search_keyword').val();
            }, 'subcategories');

            const subTable = buildTable('#ecommerceSubCategoryTable', 'sub', function(d) {
                d.search_keyword = $('#sub_search_keyword').val();
                d.parent_id = $('#filter_main_category_id').val();
            }, 'parent');

            bindRowActions('#ecommerceMainCategoryTable', mainTable);
            bindRowActions('#ecommerceSubCategoryTable', subTable);

            let mainSearchTimer = null;
            let subSearchTimer = null;

            $('#main_search_keyword').on('keyup', function() {
                clearTimeout(mainSearchTimer);
                mainSearchTimer = setTimeout(() => mainTable.ajax.reload(), 200);
            });

            $('#sub_search_keyword').on('keyup', function() {
                clearTimeout(subSearchTimer);
                subSearchTimer = setTimeout(() => subTable.ajax.reload(), 200);
            });

            $('#filter_main_category_id').on('change', function() {
                subTable.ajax.reload();
            });

            // Tab aktif disimpan di URL biar tetap kebuka setelah create/update/delete
            document.querySelectorAll('#categoryModuleTabs [data-bs-toggle="tab"]').forEach(function(tab) {
                tab.addEventListener('shown.bs.tab', function(event) {
                    const isSub = event.target.id === 'sub-categories-tab';
                    const url = new URL(window.location.href);

                    if (isSub) {
                        url.searchParams.set('tab', 'sub');
                        subTable.columns.adjust().draw(false);
                    } else {
                        url.searchParams.delete('tab');
                        mainTable.columns.adjust().draw(false);
                    }

                    window.history.replaceState({}, '', url);
                });
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('modalDeleteCategory');
            const form = document.getElementById('formDeleteCategory');
            const nameHolder = document.getElementById('CategoryName');

            modal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;

                form.action = button.getAttribute('data-url');
                nameHolder.textContent = button.getAttribute('data-name');
            });
        });
    </script>
@endpush
