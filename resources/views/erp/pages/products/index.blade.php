@extends('erp.layouts.main')

@push('styles')
    <style>
        @media (max-width: 768px) {

            #productTable td.desktop-only,
            #productTable th.desktop-only {
                display: none !important;
            }
        }

        #productTable {
            width: 100% !important;
            min-width: 0;
        }

        #productTable_wrapper .dataTables_scrollBody {
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

        #productTable tbody tr {
            animation: fadeIn 0.3s ease-in;
        }
    </style>
@endpush

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Products</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Products</li>
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
                    <a href="/erp/products/create-product" class="btn btn-primary">
                        <i class="feather-plus me-2"></i>
                        <span>Create Product</span>
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
    <div class="main-content m-0 m-md-2 m-lg-2 p-0 p-md-0 p-lg-0 pt-1 pt-md-0">
        <div class="row">
            <div class="col-lg-12">
                <div class="card stretch stretch-full">
                    <div class="card-body p-0">
                        <div class="row g-3 p-2 justify-content-between">
                            <div class="col-lg-4 me-2">
                                <div class="row g-3">
                                    <div class="col-lg-6">
                                        <label for="category_id" class="fw-semibold fs-12">Categories</label>
                                        <select name="category_id[]" id="category_id" class="form-control"
                                            style="padding: 0.25rem 0.5rem !important; font-size: 0.875rem !important;"
                                            data-select2-selector="tag">
                                            <option value="" selected>All</option>
                                            @foreach ($categories as $category)
                                                <option value="{{ $category->id }}" data-bg="bg-primary">
                                                    {{ $category->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-lg-6">
                                        <label for="tag_id" class="fw-semibold fs-12">Merek</label>
                                        <select name="tag_id[]" id="tag_id" class="form-control"
                                            style="padding: 0.25rem 0.5rem !important; font-size: 0.875rem !important;"
                                            data-select2-selector="tag">
                                            <option value="" selected>All</option>
                                            @foreach ($tags as $tag)
                                                <option value="{{ $tag->id }}" data-bg="bg-success">{{ $tag->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-2">
                                <div class="row g-3 justify-content-end">
                                    <div class="col-lg-12">
                                        <label for="search_keyword" class="fw-semibold fs-12">Search</label>
                                        <input type="text" id="search_keyword" name="search_keyword"
                                            class="form-control search-input"
                                            style="padding: 0.25rem 0.5rem; font-size: 0.875rem;"
                                            placeholder="Search by Product or SKU..." />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover bg-transparent" id="productTable">
                                <thead>
                                    <tr>
                                        {{-- <th class="wd-30">No</th> --}}
                                        {{-- <th>Image</th> --}}
                                        <th>Name</th>
                                        <th>Categories</th>
                                        <th>Merek</th>
                                        <th>Base Unit</th>
                                        <th>SKU</th>
                                        <th>Product Units</th>
                                        <th>Avg Cost</th>
                                        {{-- <th>Fixed Cost</th> --}}
                                        <!-- <th class="text-end">Actions</th> -->
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
                        <p>Apakah Anda yakin ingin menghapus Product <strong id="ProductName"></strong>?</p>
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
            let savedScrollTop = parseInt(sessionStorage.getItem('product_scroll_top') || '0');

            const savedSearch = sessionStorage.getItem('product_search');
            const savedCategory = sessionStorage.getItem('product_category');
            const savedTag = sessionStorage.getItem('product_tag');

            if (savedSearch) $('#search_keyword').val(savedSearch);
            if (savedCategory) $('#category_id').val(savedCategory);
            if (savedTag) $('#tag_id').val(savedTag);

            const dataTable = $('#productTable').DataTable({
                processing: false, // ⚠️ Matikan processing default
                serverSide: false, // ⚠️ Ubah jadi false
                scrollY: '60vh',
                scrollCollapse: true,
                paging: false, // ⚠️ Matikan pagination
                searching: false,
                info: false,
                lengthChange: false,
                ordering: false,
                order: [
                    [2, 'asc']
                ],
                data: [], // ⚠️ Mulai dengan array kosong
                columns: [
                    // {
                    //     data: null,
                    //     orderable: false,
                    //     searchable: false,
                    //     render: function(data, type, row, meta) {
                    //         return meta.row + 1; // Nomor urut otomatis
                    //     }
                    // },
                    {
                        data: 'name'
                    },
                    {
                        data: 'categories'
                    },
                    {
                        data: 'tags'
                    },
                    {
                        data: 'base_unit_name'
                    },
                    {
                        data: 'sku'
                    },
                    {
                        data: 'product_units',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'avg_cost'
                    },
                    // {
                    //     data: 'fixed_cost'
                    // }
                ]
            });

            let searchTimer = null;
            let currentRequest = null;
            // ⚠️ Fungsi untuk load data dari server
            function loadMoreData(isNewSearch = false) {
                if (!isNewSearch && (isLoading || !hasMoreData)) return;

                // Batalkan request sebelumnya jika masih jalan
                if (currentRequest) {
                    currentRequest.abort();
                }

                isLoading = true;
                $('#loadingIndicator').show();

                currentRequest = $.ajax({
                    url: "{{ url('/erp/products/data') }}",
                    type: 'GET',
                    data: {
                        start: currentPage * 200,
                        length: 200,
                        search_keyword: $('#search_keyword').val() ? $('#search_keyword').val().trim() : '',
                        category_id: $('#category_id').val(),
                        tag_id: $('#tag_id').val()
                    },
                    success: function(response) {
                        if (isNewSearch) {
                            dataTable.clear();
                        }
                        if (response.data.length > 0) {
                            allData = allData.concat(response.data);
                            if (dataTable.rows().count() === 0) {
                                dataTable.rows.add(response.data).draw(false);
                            } else {
                                let newNodes = dataTable.rows.add(response.data).nodes();
                                $(dataTable.table().body()).append(newNodes);
                            }
                            currentPage++;

                            setTimeout(() => {
                                const scrollBody = $('.dataTables_scrollBody');

                                if (savedScrollTop > 0) {
                                    scrollBody.scrollTop(savedScrollTop);

                                    if (scrollBody.scrollTop() < savedScrollTop &&
                                        hasMoreData && !isLoading) {
                                        loadMoreData();
                                    }
                                }
                            }, 100);
                        } else {
                            if (isNewSearch) {
                                dataTable.draw(); // Draw the empty table
                            }
                            hasMoreData = false;
                            $('#loadingIndicator').html('✅ All products loaded').show();
                            setTimeout(() => $('#loadingIndicator').hide(), 2000);
                        }
                    },
                    complete: function() {
                        isLoading = false;
                        currentRequest = null;
                        $('#loadingIndicator').hide();
                    },
                    error: function(xhr) {
                        if (xhr.statusText !== "abort") {
                            console.error("AJAX error", xhr);
                        }
                        isLoading = false;
                        $('#loadingIndicator').hide();
                    }
                });

            }

            // ⚠️ Load data pertama kali
            loadMoreData();

            // ⚠️ Lazy load saat scroll

            $('.dataTables_scrollBody').on('scroll', function() {
                clearTimeout(scrollTimeout);

                sessionStorage.setItem('product_scroll_top', $(this).scrollTop());

                const scrollTop = $(this).scrollTop();
                const scrollHeight = $(this)[0].scrollHeight;
                const clientHeight = $(this).height();
                const scrollPercent = ((scrollTop + clientHeight) / scrollHeight * 100).toFixed(1);


                scrollTimeout = setTimeout(() => {
                    if (scrollTop + clientHeight >= scrollHeight * 0.85) {
                        loadMoreData();
                    }
                }, 100);
            });

            let lastKeyword = $('#search_keyword').val() ? $('#search_keyword').val().trim() : '';

            function triggerSearch() {
                sessionStorage.setItem('product_search', $('#search_keyword').val());
                sessionStorage.setItem('product_category', $('#category_id').val());
                sessionStorage.setItem('product_tag', $('#tag_id').val());
                sessionStorage.removeItem('product_scroll_top');

                clearTimeout(searchTimer);
                searchTimer = setTimeout(() => {
                    allData = [];
                    currentPage = 0;
                    hasMoreData = true;
                    savedScrollTop = 0;
                    loadMoreData(true);
                }, 50);
            }

            $('#search_keyword').on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    const keyword = $(this).val().trim();
                    if (keyword !== lastKeyword) {
                        lastKeyword = keyword;
                        triggerSearch();
                    }
                }
            });

            $('#search_keyword').on('input', function() {
                const val = $(this).val().trim();
                if (val === '' && lastKeyword !== '') {
                    lastKeyword = '';
                    triggerSearch();
                }
            });

            $('#category_id, #tag_id').on('change', function() {
                triggerSearch();
            });


            // Action button handlers (tetap sama)
            $('#productTable tbody').on('click', 'tr', function(e) {
                if ($(e.target).closest('td.dt-control').length) return;

                let $tr = $(this);
                let row = dataTable.row($tr);

                $('#productTable tbody tr').removeClass('action-shown').next('.action-row').remove();

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
                if ($(e.target).closest('#productTable').length) return;
                $('#productTable tbody tr').removeClass('action-shown').next('.action-row').remove();
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('modalDeleteProduct');
            const form = document.getElementById('formDeleteProduct');
            const nameHolder = document.getElementById('ProductName');

            modal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-id');
                const name = button.getAttribute('data-name');
                const url = button.getAttribute('data-url');

                form.action = url;
                nameHolder.textContent = name;
            });
        });


        // const savedSearch = sessionStorage.getItem('product_search');
        // const savedCategory = sessionStorage.getItem('product_category');
        // const savedTag = sessionStorage.getItem('product_tag');

        // if (savedSearch) $('#search_keyword').val(savedSearch);
        // if (savedCategory) $('#category_id').val(savedCategory).trigger('change');
        // if (savedTag) $('#tag_id').val(savedTag).trigger('change');


        // $('#search_keyword').on('input', function() {
        //     sessionStorage.setItem('product_search', $(this).val());
        // });
        // $('#category_id').on('change', function() {
        //     sessionStorage.setItem('product_category', $(this).val());
        // });
        // $('#tag_id').on('change', function() {
        //     sessionStorage.setItem('product_tag', $(this).val());
        // });
    </script>
@endpush
