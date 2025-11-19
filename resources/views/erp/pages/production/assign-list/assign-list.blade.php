@extends('erp.layouts.main')

@push('styles')
    <style>
        @media (max-width: 768px) {

            #assignBatchTable td.desktop-only,
            #assignBatchTable th.desktop-only {
                display: none !important;
            }
        }

        #assignBatchTable {
            width: 100% !important;
            min-width: 0;
        }

        #assignBatchTable_wrapper .dataTables_scrollBody {
            background-image: none !important;
            height: 60vh !important;
            overflow-y: auto !important;
        }

        .dataTables_scrollBody {
            scroll-behavior: smooth;
        }

        #assignBatchTable tbody tr {
            animation: fadeIn 0.3s ease-in;
        }
    </style>
@endpush

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Assign List</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Production</li>
                <li class="breadcrumb-item">Assign List</li>
            </ul>
        </div>
    </div>
@endsection

@section('content')
    @if (session('error'))
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: "{{ session('error') }}",
            });
        </script>
    @endif
    @if (session('success'))
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: "{{ session('success') }}",
            });
        </script>
    @endif
    <div class="main-content">
        <div class="row">
            <div class="col-lg-12">
                <div class="card stretch stretch-full">
                    <div class="card-body p-0">
                        <div class="row g-3 p-4 pb-3 justify-content-between">
                            <div class="col-lg-4">
                                <label for="filter" class="fw-semibold fs-12">Date</label>
                                <div class="d-flex align-items-center gap-2">
                                    <select id="filter" class="form-control" style="width: 200px;">
                                        <option value="all">All Time</option>
                                        <option value="yearly">Yearly</option>
                                        <option value="year_to_date">Year to Date</option>
                                        <option value="last_30_days">Last 30 Days</option>
                                        <option value="this_month">This Month</option>
                                        <option value="last_7_days">Last 7 Days</option>
                                        <option value="today">Today</option>
                                        <option value="custom">Custom Range</option>
                                    </select>
                                    <input type="date" id="start_date" class="form-control custom-range d-none">
                                    <input type="date" id="end_date" class="form-control custom-range d-none">
                                    <button id="apply-filter" class="btn btn-primary custom-range d-none">Apply</button>
                                </div>
                            </div>
                            <div class="col-lg-8">
                                <div class="row justify-content-end">
                                    <div class="col-lg-2">
                                        <label for="progress_status" class="fw-semibold fs-12">Progress Status</label>
                                        <select id="progress_status" class="form-control"
                                            style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                            <option value="progress">Progress</option>
                                            <option value="completed">Completed</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-2">
                                        <label for="search_product" class="fw-semibold fs-12">Search Product</label>
                                        <input type="text" id="search_product" class="form-control"
                                            placeholder="Product name...">
                                    </div>
                                    <div class="col-lg-5">
                                        <label for="search_type" class="fw-semibold fs-12">Search By</label>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <select id="search_type" class="form-control"
                                                    style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                                    <option value="customer">Customer</option>
                                                    <option value="order_number">Order Number</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
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
                        <ul class="nav nav-tabs mb-3" id="assignTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="assign-list-tab" data-bs-toggle="tab" href="#assign-list"
                                    role="tab">
                                    Assign List
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="assign-summary-tab" data-bs-toggle="tab" href="#assign-summary"
                                    role="tab">
                                    Assign Summary
                                </a>
                            </li>
                        </ul>

                        <div class="table-responsive">
                            <div class="tab-content">
                                {{-- Tab 1: Assign List --}}
                                <div class="tab-pane fade show active" id="assign-list" role="tabpanel">
                                    <table class="table table-hover bg-transparent" id="assignBatchTable">
                                        <thead>
                                            <tr>
                                                <th class="wd-250">Invoice Number</th>
                                                <th class="wd-250">Customer</th>
                                                <th>Assign List</th>
                                                <th>Order Notes</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>

                                {{-- Tab 2: Assign Summary --}}
                                <div class="tab-pane fade" id="assign-summary" role="tabpanel">
                                    <table class="table table-hover bg-transparent" id="assignSummaryTable">
                                        <thead>
                                            <tr>
                                                <th class="wd-300">Product</th>
                                                <th class="wd-150">SKU</th>
                                                <th class="wd-150">Total Assigned Qty</th>
                                                {{-- <th class="wd-100">Unit</th> --}}
                                            </tr>
                                        </thead>
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
    <div class="modal fade" id="modalDeleteAssign" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form id="formDeleteAssign" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title text-white">Hapus Assign Batch</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <p class="fw-semibold mb-2">
                            Yakin ingin menghapus batch ini?
                        </p>
                        <p class="text-muted mb-3">
                            Semua data <strong>assign</strong> di dalam batch ini juga akan ikut terhapus.
                        </p>

                        <div class="alert alert-warning mb-0">
                            <strong>Perhatian:</strong> Tindakan ini tidak dapat dibatalkan.
                        </div>

                        <input type="hidden" id="delete_batch_id">
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger" id="btnConfirmDelete">
                            <i class="feather-trash-2 me-2"></i>Hapus
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="modal fade" id="modalPreviewDesign" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title text-white">Design Preview</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Order Note</label>
                        <div id="previewOrderNote" class="border rounded p-3 bg-light text-dark"
                            style="white-space: pre-wrap; font-size: 14px;"></div>
                    </div>

                    <h6 class="fw-bold mb-3" id="previewProductName"></h6>
                    <div id="previewImageContainer" class="d-flex flex-column gap-3"></div>
                </div>
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

            const batchTable = $('#assignBatchTable').DataTable({
                processing: false,
                serverSide: false,
                scrollY: '60vh',
                scrollCollapse: true,
                paging: false,
                searching: false,
                info: false,
                lengthChange: false,
                order: [
                    [4, 'asc']
                ],
                data: [],
                columns: [
                    // {
                    //     data: 'DT_RowIndex',
                    //     orderable: false,
                    //     searchable: false
                    // },
                    {
                        data: 'assign_code'
                    },
                    {
                        data: 'customer'
                    },
                    {
                        data: 'assign_products'
                    },
                    {
                        data: 'order_notes'
                    },
                    // {
                    //     data: 'note'
                    // },
                    // {
                    //     data: 'action',
                    //     visible: false,
                    //     orderable: false,
                    //     searchable: false
                    // },
                    {
                        data: 'created_at',
                        visible: false,
                        searchable: false
                    }
                ],
                language: {
                    processing: '<div class="spinner-border text-primary" role="status"></div>'
                }
            });

            let searchTimer = null;
            let currentRequest = null;

            function loadMoreData() {
                if (isLoading || !hasMoreData) return;
                isLoading = true;

                // 🚫 Batalkan request sebelumnya kalau masih jalan
                if (currentRequest) {
                    currentRequest.abort();
                }

                currentRequest = $.ajax({
                    url: `/erp/productions/waiting-list/assign-list/data`,
                    type: 'GET',
                    data: {
                        start: currentPage * 50,
                        length: 50,
                        filter: $('#filter').val(),
                        start_date: $('#start_date').val(),
                        end_date: $('#end_date').val(),
                        progress_status: $('#progress_status').val(),
                        search_type: $('#search_type').val(),
                        search_keyword: $('#search_keyword').val(),
                        search_product: $('#search_product').val(),
                    },
                    success: function(response) {
                        if (response && response.data && response.data.length > 0) {
                            allData = allData.concat(response.data);
                            batchTable.clear();
                            batchTable.rows.add(allData).draw(false);
                            currentPage++;
                        } else {
                            hasMoreData = false;
                        }
                    },
                    complete: function() {
                        isLoading = false;
                        currentRequest = null;
                    },
                    error: function(xhr) {
                        if (xhr.statusText !== 'abort') {
                            console.error('AJAX error:', xhr);
                            alert(xhr.responseJSON?.message || 'Error loading data.');
                        }
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

            function resetAndReload() {
                allData = [];
                currentPage = 0;
                hasMoreData = true;
                batchTable.clear().draw();
                loadMoreData();
            }

            $('#assignBatchTable tbody').on('click', 'tr', function(e) {
                if ($(e.target).closest('td.dt-control').length) return;
                let $tr = $(this);
                let row = batchTable.row($tr);
                $('#assignBatchTable tbody tr').removeClass('action-shown').next('.action-row').remove();

                if ($tr.hasClass('action-shown')) {
                    $tr.removeClass('action-shown');
                } else {
                    let actionHtml = row.data().action;
                    let colCount = $tr.find('td').length;
                    let $actionRow = $(`
                <tr class="action-row">
                    <td colspan="${colCount}">
                        <div class="d-flex justify-content-center py-2">${actionHtml}</div>
                    </td>
                </tr>
            `);
                    $tr.after($actionRow);
                    $tr.addClass('action-shown');
                }
            });

            $(document).on('click', function(e) {
                if ($(e.target).closest('#assignBatchTable').length) return;
                $('#assignBatchTable tbody tr').removeClass('action-shown').next('.action-row').remove();
            });

            // ==========================
            // 🔹 FILTER HANDLER — SAME AS DESIGN LIST BEHAVIOR
            // ==========================

            // Kalau dropdown filter berubah
            $('#filter').on('change', function() {
                const val = $(this).val();

                // Kalau custom range → tampilkan input tanggal, tapi JANGAN reload dulu
                if (val === 'custom') {
                    $('.custom-range').removeClass('d-none');
                    return;
                }

                // Kalau bukan custom → sembunyikan input tanggal dan reload
                $('.custom-range').addClass('d-none');
                resetAndReload();
            });

            // Tombol Apply untuk custom range
            $('#apply-filter').on('click', function() {
                resetAndReload();
            });

            // Filter lain tetap auto reload
            $('#progress_status, #search_type, #search_keyword, #search_product, #start_date, #end_date')
                .on('change keyup input paste', function() {
                    clearTimeout(searchTimer);
                    searchTimer = setTimeout(() => resetAndReload(), 200);
                });


            let summaryTableInitialized = false;
            let summaryTable = null;

            function initAssignSummaryTable() {
                if (summaryTableInitialized) return;

                summaryTable = $('#assignSummaryTable').DataTable({
                    processing: true,
                    serverSide: false,
                    ajax: {
                        url: "/erp/productions/assign-list/summary",
                        data: function(d) {
                            d.filter = $('#filter').val();
                            d.start_date = $('#start_date').val();
                            d.end_date = $('#end_date').val();
                            d.search_type = $('#search_type').val();
                            d.search_keyword = $('#search_keyword').val();
                            d.search_product = $('#search_product').val();
                            d.progress_status = $('#progress_status').val();
                        }
                    },
                    columns: [{
                            data: 'product_name'
                        },
                        {
                            data: 'sku'
                        },
                        {
                            data: 'total_assigned_qty'
                        },
                        // {
                        //     data: 'unit'
                        // }
                    ],
                    order: [
                        [2, 'desc']
                    ],
                    paging: false,
                    searching: false,
                    info: false,
                    scrollY: '60vh',
                    scrollCollapse: true,
                });

                summaryTableInitialized = true;
            }

            // Jika tab summary dibuka
            $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
                if ($(e.target).attr('href') === '#assign-summary') {
                    if (!summaryTableInitialized) {
                        initAssignSummaryTable();
                    } else {
                        summaryTable.ajax.reload();
                    }
                }
            });

        });

        $(document).on('click', '.btn-open-delete-modal', function() {
            const batchId = $(this).data('id');
            const batchCode = $(this).data('code');

            $('#formDeleteAssign').attr('action', `/erp/productions/assign-list/delete/${batchId}`);

            $('#modalDeleteAssign .modal-title').text(`Hapus Assign Batch ${batchCode}`);

            $('#delete_batch_id').val(batchId);

            $('#modalDeleteAssign').modal('show');
        });

        $('#formDeleteAssign').on('submit', function() {
            $('#btnConfirmDelete').prop('disabled', true).html(
                '<span class="spinner-border spinner-border-sm me-2"></span> Menghapus...');
        });

        document.addEventListener('click', function(e) {
            const el = e.target.closest('.assign-disabled');

            if (el) {
                const title = el.getAttribute('data-title') || 'Action';

                Swal.fire({
                    icon: 'error',
                    title: 'Tidak dapat ' + title,
                    text: 'Assign ini sudah full progress, tidak bisa diubah lagi!',
                    confirmButtonText: 'OK'
                });
            }
        });

        // === SHOW PREVIEW MODAL ===
        $(document).on('click', '.preview-btn', function() {
            const images = $(this).data('images');
            const product = $(this).data('product');
            const container = $('#previewImageContainer');
            const orderNote = $(this).data('order_note') || '-';

            $('#previewProductName').text(product);
            $('#previewOrderNote').text(orderNote);
            container.empty();

            if (Array.isArray(images) && images.length > 0) {
                images.forEach((img) => {
                    const fileUrl = img.file ?
                        `/${img.file}`.replace(/\/{2,}/g, '/') :
                        '';
                    const note = img.note || '-';

                    const html = `
                <div class="image-item border rounded p-3">
                    <img src="${fileUrl}" 
                         class="img-fluid rounded mb-2 preview-full"
                         style="cursor:pointer;"
                         data-full="${fileUrl}">
                    <p class="small text-muted mb-0">${note}</p>
                </div>
            `;
                    container.append(html);
                });
            } else {
                container.html('<p class="text-muted">No preview images available.</p>');
            }

            $('#modalPreviewDesign').modal('show');
        });

        // === OPEN FULLSCREEN (open new tab) ===
        $(document).on('click', '.preview-full', function() {
            const src = $(this).data('full');
            if (src) window.open(src, '_blank');
        });
    </script>
@endpush
