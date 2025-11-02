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
    <div class="main-content">
        <div class="row">
            <div class="col-lg-12">
                <div class="card stretch stretch-full">
                    <div class="card-body p-0">
                        <div class="row g-3 p-4 justify-content-between">
                            <div class="col-lg-4 me-2">
                                <div class="row g-3">
                                    <div class="col-lg-6">
                                        <label for="category_id" class="fw-semibold fs-12">Categories</label>
                                        <select name="category_id[]" id="category_id" class="form-control"
                                            style="padding: 0.5rem 1rem !important; font-size: 0.875rem !important;"
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
                                            style="padding: 0.5rem 1rem !important; font-size: 0.875rem !important;"
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
                            <table class="table table-hover bg-transparent" id="productTable">
                                <thead>
                                    <tr>
                                        {{-- <th class="wd-30">No</th> --}}
                                        {{-- <th>Image</th> --}}
                                        <th>Name</th>
                                        <th>Categories</th>
                                        <th>Merek</th>
                                        <th>Price</th>
                                        <th>SKU</th>
                                        <th>Avg Cost</th>
                                        <th>Fixed Cost</th>
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
        // $(document).ready(function() {
        //     let allData = []; // ⚠️ Simpan semua data yang sudah dimuat
        //     let currentPage = 0;
        //     let isLoading = false;
        //     let hasMoreData = true;

        //     const dataTable = $('#productTable').DataTable({
        //         processing: true,
        //         serverSide: true,
        //         scrollY: '60vh',
        //         scrollCollapse: true,
        //         paging: true,
        //         pageLength: 15,
        //         searching: false,
        //         info: false,
        //         lengthChange: false,
        //         order: [
        //             [1, 'asc']
        //         ],
        //         ajax: {
        //             url: "{{ url('/erp/products/data') }}",
        //             data: function(d) {
        //                 d.search_type = $('#search_type').val();
        //                 d.search_keyword = $('#search_keyword').val();
        //                 d.category_id = $('#category_id').val();
        //                 d.tag_id = $('#tag_id').val();
        //             }
        //         },
        //         columns: [{
        //                 data: 'DT_RowIndex',
        //                 orderable: false,
        //                 searchable: false
        //             },
        //             {
        //                 data: 'name'
        //             },
        //             {
        //                 data: 'categories'
        //             },
        //             {
        //                 data: 'tags'
        //             },
        //             {
        //                 data: 'price'
        //             },
        //             {
        //                 data: 'sku'
        //             },
        //             {
        //                 data: 'avg_cost'
        //             },
        //             {
        //                 data: 'fixed_cost'
        //             }
        //         ],
        //         drawCallback: function() {
        //             $('.dataTables_paginate').hide();
        //         }
        //     });

        //     let isLoading = false;
        //     let scrollTimeout = null;

        //     $('.dataTables_scrollBody').on('scroll', function() {
        //         clearTimeout(scrollTimeout);

        //         const scrollTop = $(this).scrollTop();
        //         const scrollHeight = $(this)[0].scrollHeight;
        //         const clientHeight = $(this).height();

        //         scrollTimeout = setTimeout(() => {
        //             if (scrollTop + clientHeight >= scrollHeight * 0.8 && !isLoading) {
        //                 isLoading = true;

        //                 // ⚠️ Log sebelum load
        //                 const rowsBefore = $('#productTable tbody tr').length;
        //                 console.log('📦 Rows sebelum load:', rowsBefore);

        //                 $('#loadingIndicator').show();

        //                 const info = dataTable.page.info();

        //                 if (info.page < info.pages - 1) {
        //                     dataTable.page('next').draw('page');

        //                     setTimeout(() => {
        //                         // ⚠️ Log setelah load
        //                         const rowsAfter = $('#productTable tbody tr').length;
        //                         console.log('📦 Rows setelah load:', rowsAfter);
        //                         console.log('✅ Bertambah:', rowsAfter - rowsBefore, 'rows');

        //                         isLoading = false;
        //                         $('#loadingIndicator').hide();
        //                     }, 100);
        //                 } else {
        //                     $('#loadingIndicator').html('✅ All products loaded').show();
        //                     setTimeout(() => $('#loadingIndicator').hide(), 2000);
        //                     isLoading = false;
        //                 }
        //             }
        //         }, 0);
        //     });

        //     $('#search_type, #search_keyword, #category_id, #tag_id').on('change keyup', function() {
        //         dataTable.ajax.reload();
        //     });

        //     $('#productTable tbody').on('click', 'tr', function(e) {
        //         if ($(e.target).closest('td.dt-control').length) return;

        //         let $tr = $(this);
        //         let row = dataTable.row($tr);

        //         $('#productTable tbody tr').removeClass('action-shown').next('.action-row').remove();

        //         if ($tr.hasClass('action-shown')) {
        //             $tr.removeClass('action-shown');
        //         } else {
        //             let actionHtml = row.data().action;

        //             let colCount = $tr.find('td').length;
        //             let $actionRow = $(`
    //             <tr class="action-row">
    //                 <td colspan="${colCount}">
    //                     <div class="d-flex justify-content-center">
    //                     ${actionHtml}
    //                     </div>
    //                 </td>
    //             </tr>
    //         `);

        //             $tr.after($actionRow);
        //             $tr.addClass('action-shown');
        //         }
        //     });

        //     $(document).on('click', function(e) {
        //         if ($(e.target).closest('#productTable').length) return;

        //         $('#productTable tbody tr').removeClass('action-shown').next('.action-row').remove();
        //     });

        //     $(document).on('click', function(e) {
        //         if (!$(e.target).closest('#productTable tbody tr, #productTableMobile tbody tr').length) {
        //             $('#productTable tbody tr.shown, #productTableMobile tbody tr.shown').each(function() {
        //                 var tr = $(this);
        //                 var table = tr.closest('table').attr('id') === 'productTable' ? dataTable :
        //                     dataTableMobile;
        //                 var row = table.row(tr);
        //                 if (row.child.isShown()) {
        //                     row.child.hide();
        //                     tr.removeClass('shown');
        //                 }
        //             });
        //         }
        //     });

        // });

        $(document).ready(function() {
            let allData = []; // ⚠️ Simpan semua data yang sudah dimuat
            let currentPage = 0;
            let isLoading = false;
            let hasMoreData = true;

            const dataTable = $('#productTable').DataTable({
                processing: false, // ⚠️ Matikan processing default
                serverSide: false, // ⚠️ Ubah jadi false
                scrollY: '60vh',
                scrollCollapse: true,
                paging: false, // ⚠️ Matikan pagination
                searching: false,
                info: false,
                lengthChange: false,
                order: [
                    [3, 'asc']
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
                        data: 'price'
                    },
                    {
                        data: 'sku'
                    },
                    {
                        data: 'avg_cost'
                    },
                    {
                        data: 'fixed_cost'
                    }
                ]
            });

            // ⚠️ Fungsi untuk load data dari server
            function loadMoreData() {
                if (isLoading || !hasMoreData) return;

                isLoading = true;
                $('#loadingIndicator').show();

                $.ajax({
                    url: "{{ url('/erp/products/data') }}",
                    type: 'GET',
                    data: {
                        start: currentPage * 15,
                        length: 15,
                        search_type: $('#search_type').val(),
                        search_keyword: $('#search_keyword').val(),
                        category_id: $('#category_id').val(),
                        tag_id: $('#tag_id').val()
                    },
                    success: function(response) {
                        console.log('📦 Data loaded:', response.data.length, 'rows');

                        if (response.data.length > 0) {
                            // ⚠️ Append data baru ke array
                            allData = allData.concat(response.data);

                            // ⚠️ Update DataTable dengan semua data
                            dataTable.clear();
                            dataTable.rows.add(allData);
                            dataTable.draw(false); // false = jangan reset scroll position

                            currentPage++;

                            console.log('✅ Total rows sekarang:', allData.length);
                        } else {
                            hasMoreData = false;
                            $('#loadingIndicator').html('✅ All products loaded').show();
                            setTimeout(() => $('#loadingIndicator').hide(), 2000);
                        }

                        isLoading = false;
                        $('#loadingIndicator').hide();
                    },
                    error: function(xhr) {
                        console.error('❌ Error loading data:', xhr);
                        isLoading = false;
                        $('#loadingIndicator').hide();
                    }
                });
            }

            // ⚠️ Load data pertama kali
            loadMoreData();

            // ⚠️ Lazy load saat scroll
            let scrollTimeout = null;
            $('.dataTables_scrollBody').on('scroll', function() {
                clearTimeout(scrollTimeout);

                const scrollTop = $(this).scrollTop();
                const scrollHeight = $(this)[0].scrollHeight;
                const clientHeight = $(this).height();
                const scrollPercent = ((scrollTop + clientHeight) / scrollHeight * 100).toFixed(1);

                // console.log('📜 Scroll:', scrollPercent + '%');

                scrollTimeout = setTimeout(() => {
                    if (scrollTop + clientHeight >= scrollHeight * 0.85) {
                        loadMoreData();
                    }
                }, 100);
            });

            // ⚠️ Reset saat filter berubah
            $('#search_type, #search_keyword, #category_id, #tag_id').on('change keyup', function() {
                allData = [];
                currentPage = 0;
                hasMoreData = true;
                dataTable.clear().draw();
                loadMoreData();
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
    </script>
@endpush
