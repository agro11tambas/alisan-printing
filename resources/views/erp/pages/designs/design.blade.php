@extends('erp.layouts.main')

@push('styles')
    <style>
        @media (max-width: 768px) {

            #designListTable td.desktop-only,
            #designListTable th.desktop-only {
                display: none !important;
            }
        }

        #designListTable {
            width: 100% !important;
            min-width: 0;
        }

        #designListTable_wrapper .dataTables_scrollBody {
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

        #designListTable tbody tr {
            animation: fadeIn 0.3s ease-in;
        }

        /* Tabel produk di dalam kolom Products: lebar kolom fix, nama panjang tetap rapi */
        #designListTable td .design-product-wrapper {
            width: 100%;
        }

        .design-product-table {
            table-layout: fixed;
            width: 100%;
            min-width: 560px;
        }

        .design-product-table col.col-product {
            width: 34%;
        }

        .design-product-table col.col-mode {
            width: 14%;
        }

        .design-product-table col.col-qty {
            width: 16%;
        }

        .design-product-table col.col-action {
            width: 20%;
        }

        .design-product-table col.col-note {
            width: 16%;
        }

        .design-product-table th,
        .design-product-table td {
            white-space: normal !important;
            overflow-wrap: anywhere !important;
            word-break: break-word !important;
            vertical-align: middle;
        }

        /* Nama produk panjang: turun ke baris berikutnya, bukan melebarkan kolom */
        .design-product-table td.product-name {
            max-width: 0;
            /* dipaksa ikut lebar colgroup */
            white-space: normal !important;
            overflow-wrap: anywhere !important;
            word-break: break-word !important;
            line-height: 1.35;
        }

        #multiViewerContainer .image-item {
            width: 100%;
        }

        #multiViewerContainer .image-item img {
            width: 100%;
            height: auto;
            max-height: 500px;
            object-fit: contain;
            border-radius: 8px;
            border: 1px solid #ddd;
        }

        #multiViewerContainer .image-item p {
            margin-top: 8px;
        }

        #multiViewerContainer .image-item p {
            font-size: 16px !important;
            font-weight: 600 !important;
            /* Bold */
            color: #333 !important;
        }

        /* Kartu pilihan design customer: satu grid rata, satu design terpilih. */
        .picker-card {
            cursor: pointer;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 6px;
            height: 100%;
            margin: 0;
            transition: border-color .15s ease, box-shadow .15s ease;
        }

        .picker-card:hover {
            border-color: #a5b4fc;
        }

        .picker-card.is-selected {
            border-color: #4b49ac;
            box-shadow: 0 0 0 2px rgba(75, 73, 172, .25);
        }

        .picker-card img {
            width: 100%;
            height: 110px;
            object-fit: contain;
            background: #f8f9fa;
            border-radius: 6px;
        }

        .picker-card .picker-title {
            font-size: 12px;
            font-weight: 600;
            color: #333;
            margin-top: 6px;
        }

        .picker-card .picker-meta {
            font-size: 11px;
            color: #8a94a6;
        }

        .picker-card .picker-title,
        .picker-card .picker-meta {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    </style>
@endpush

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Design List</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Design</li>
            </ul>
        </div>
    </div>
@endsection

@section('content')
    <div class="main-content m-0 m-md-2 m-lg-2 p-0 p-md-0 p-lg-0 pt-1 pt-md-0">
        <div class="row">
            <div class="col-lg-12">
                <div class="card stretch stretch-full">
                    <div class="card-body p-0">
                        <div class="row g-3 p-2 justify-content-between">
                            <div class="col-lg-4">
                                <label for="filter" class="fw-semibold fs-12">Date Filter</label>
                                <div class="d-flex align-items-center gap-2 page-header-right-items-wrapper">
                                    <select id="filter" class="form-control" style="width:180px;">
                                        <option value="all">All Time</option>
                                        <option value="this_month">This Month</option>
                                        <option value="yearly">Yearly</option>
                                        <option value="year_to_date">Year to Date</option>
                                        <option value="last_30_days">Last 30 Days</option>
                                        <option value="last_7_days">Last 7 Days</option>
                                        <option value="today">Today</option>
                                        <option value="custom">Custom Range</option>
                                    </select>
                                    <input type="date" id="start_date" class="form-control custom-range d-none"
                                        style="width:150px;">
                                    <input type="date" id="end_date" class="form-control custom-range d-none"
                                        style="width:150px;">
                                    <button id="apply-filter"
                                        class="btn btn-primary btn-sm custom-range d-none">Apply</button>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="row justify-content-end">
                                    <div class="col-lg-3">
                                        <label for="status" class="fw-semibold fs-12">Status</label>
                                        <select id="status" class="form-control">
                                            <option value="Pending">Pending</option>
                                            <option value="Verified">Verified</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-3">
                                        <label for="search_product" class="fw-semibold fs-12">Search Product</label>
                                        <input type="text" id="search_product" class="form-control"
                                            placeholder="Product name...">
                                    </div>
                                    <div class="col-lg-6">
                                        <label for="search_type" class="fw-semibold fs-12">Search</label>
                                        <div class="row g-2">
                                            <div class="col-md-5">
                                                <select id="search_type" class="form-control">
                                                    <option value="customer">Customer</option>
                                                    <option value="design_number">Design Number</option>
                                                    <option value="product">Product</option>
                                                </select>
                                            </div>
                                            <div class="col-md-7">
                                                <input type="text" id="search_keyword" class="form-control"
                                                    placeholder="Search...">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover bg-transparent" id="designListTable">
                                <thead>
                                    <tr>
                                        <th class="wd-250">Design Number</th>
                                        <th class="wd-350">Customer</th>
                                        <th>Products</th>
                                        <th>Order Note</th>
                                        <th>Chat</th>
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
    <div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form id="uploadForm" method="POST" enctype="multipart/form-data">
                @csrf
                @method('POST')
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title text-white" id="uploadModalLabel">Upload Design Preview</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="design_item_id" name="design_item_id">
                        <div class="mb-2">
                            <label class="form-label fw-semibold">Order Note</label>
                            <div id="orderNoteBox" class="border rounded p-2 bg-light text-dark"
                                style="white-space: pre-wrap; font-size: 14px;"></div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Upload / Paste Screenshot (Multiple)</label>

                            <div id="pasteArea" class="border rounded p-2 text-center"
                                style="min-height: 120px; cursor: pointer;">
                                <p class="text-muted small mb-1">
                                    Klik di sini lalu tekan <strong>Ctrl + V</strong> untuk paste screenshot
                                </p>
                                <div id="previewContainer" class="d-flex flex-wrap gap-3 justify-content-start"></div>
                            </div>

                            {{-- <input type="file" class="form-control mt-1" id="preview_image" name="preview_image[]"
                                accept="image/*" multiple> --}}
                        </div>

                        {{-- <div class="mb-2">
                            <label for="note" class="form-label">Note</label>
                            <textarea class="form-control" name="note" id="note" rows="3" placeholder="Add a note..."></textarea>
                        </div> --}}
                    </div>
                    <div class="modal-footer">
                        {{-- data-id diisi handler .upload-btn waktu modal dibuka, karena
                             satu modal dipakai bergantian oleh semua design item. --}}
                        @if (config('features.customer_design'))
                            <button type="button" class="btn btn-outline-secondary pick-customer-design-btn"
                                id="uploadPickCustomerDesign">
                                <i class="feather-folder"></i> Design Customer
                            </button>
                        @endif
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Upload</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="imageViewerModal" tabindex="-1" aria-labelledby="imageViewerModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title" id="imageViewerModalLabel">Preview Image</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="viewerImage" src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" alt="Preview" class="img-fluid rounded mb-2"
                        style="max-height: 70vh; object-fit: contain;">
                    <p id="viewerNote" class="text-muted fs-6"></p>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="verifyDesignModal" tabindex="-1" aria-labelledby="verifyDesignModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <form id="formVerifyDesign" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header bg-primary">
                        <h5 class="modal-title text-white">Verifikasi Design</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Apakah Anda yakin ingin memverifikasi design <strong id="DesignName"></strong>?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Verifikasi</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="unverifyDesignModal" tabindex="-1" aria-labelledby="unverifyDesignModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <form id="formUnverifyDesign" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title text-white">Batalkan Verifikasi Design</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>
                            Apakah Anda yakin ingin <strong>membatalkan verifikasi</strong> design
                            <strong id="UnverifyDesignName"></strong>?
                        </p>
                        <p class="text-muted small mb-0">
                            Tindakan ini akan menghapus data <strong>Order Progress</strong>,
                            <strong>Delivery Order</strong>, dan stok <strong>Pending Waiting List</strong> yang terkait.
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">Ya, Batalkan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if (config('features.customer_design'))
        <div class="modal fade" id="customerDesignPickerModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title text-white">Pilih Design Customer</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="picker_design_item_id">
                        <div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-3">
                            <div>
                                <div class="fw-semibold" id="pickerCustomerName">-</div>
                                <small class="text-muted" id="pickerProductName"></small>
                            </div>
                            <input type="text" id="pickerSearch" class="form-control form-control-sm"
                                style="max-width:240px;" placeholder="Cari judul design...">
                        </div>
                        <div id="pickerContainer" class="row g-2"></div>
                    </div>
                    <div class="modal-footer">
                        <div class="d-flex flex-wrap align-items-end gap-2 w-100">
                            <div class="flex-grow-1" style="min-width:220px;">
                                <label for="pickerNote" class="form-label fw-semibold fs-12 mb-1">Catatan</label>
                                <input type="text" id="pickerNote" class="form-control form-control-sm"
                                    placeholder="Catatan untuk design ini (opsional)">
                                <small class="text-muted d-block mt-1" id="pickerSelectedInfo">Belum ada design
                                    dipilih</small>
                            </div>
                            <div class="d-flex gap-2 ms-auto">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                <button type="button" class="btn btn-primary" id="pickerApplyButton">Pasang ke
                                    Design</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="modal fade" id="multiImageViewerModal" tabindex="-1" aria-labelledby="multiImageViewerModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title text-white" id="multiImageViewerModalLabel">Preview Design</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label fw-semibold">Order Note</label>
                        <div id="multiViewerOrderNote" class="border rounded p-2 bg-light text-dark"
                            style="white-space: pre-wrap; font-size: 14px;"></div>
                    </div>
                    <h6 class="fw-bold mb-2" id="multiViewerProduct"></h6>
                    <div id="multiViewerContainer" class="d-flex flex-column gap-3"></div>
                </div>
                <div class="modal-footer">
                    {{-- Hapus per gambar ada di tiap kartu; tombol ini untuk
                         mengosongkan preview item sekaligus. data-id diisi
                         handler .preview-btn waktu modal dibuka. --}}
                    <button type="button" class="btn btn-outline-danger delete-preview-btn"
                        id="previewDeleteAll">
                        <i class="feather-trash-2"></i> Hapus Semua Preview
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
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

            const table = $('#designListTable').DataTable({
                processing: false,
                serverSide: false,
                scrollY: '60vh',
                scrollCollapse: true,
                paging: false,
                searching: false,
                info: false,
                lengthChange: false,
                ordering: false,
                order: [
                    [5, 'asc']
                ],
                data: [],
                columns: [{
                        data: 'design_number',
                        name: 'design_number',
                        width: '15%'
                    },
                    {
                        data: 'customer',
                        name: 'customer',
                        width: '18%'
                    },
                    {
                        data: 'products',
                        name: 'products',
                        orderable: false,
                        searchable: false,
                        width: '45%'
                    },
                    {
                        data: 'order_note',
                        name: 'order_note',
                        orderable: false,
                        searchable: false,
                        width: '12%'
                    },
                    {
                        data: 'whatsapp',
                        name: 'whatsapp',
                        orderable: false,
                        searchable: false,
                        width: '10%'
                    },
                    {
                        data: 'created_at',
                        name: 'created_at',
                        visible: false,
                        searchable: false
                    }
                ]
            });

            let searchTimeout = null;
            let currentRequest = null;
            // Naik tiap kali filter berubah. Respons dengan token lama dibuang,
            // supaya data filter sebelumnya tidak ikut nempel ke tabel.
            let requestToken = 0;

            function loadMoreData() {
                if (isLoading || !hasMoreData) return;
                isLoading = true;

                const token = requestToken;

                currentRequest = $.ajax({
                    url: "{{ url('/erp/design/data') }}",
                    type: 'GET',
                    data: {
                        start: currentPage * 50,
                        length: 50,
                        filter: $('#filter').val(),
                        start_date: $('#start_date').val(),
                        end_date: $('#end_date').val(),
                        search_type: $('#search_type').val(),
                        search_keyword: $('#search_keyword').val(),
                        status: $('#status').val(),
                        search_product: $('#search_product').val(),
                    },
                    success: function(response) {
                        // Filter sudah berganti sejak request ini dikirim:
                        // datanya milik filter lama, jangan digabung.
                        if (token !== requestToken) return;

                        if (response && response.data && response.data.length > 0) {
                            allData = allData.concat(response.data);
                            table.clear();
                            table.rows.add(allData).draw(false);
                            currentPage++;
                        } else {
                            hasMoreData = false;
                        }
                    },
                    complete: function() {
                        if (token !== requestToken) return;

                        isLoading = false;
                        currentRequest = null;
                    },
                    error: function(xhr) {
                        if (token !== requestToken) return;

                        if (xhr.statusText !== 'abort') {
                            console.error('AJAX Error:', xhr);
                        }
                        isLoading = false;
                    }
                });
            }


            function resetAndReload() {
                // Batalkan request yang masih jalan dan tandai respons lamanya
                // sebagai kedaluwarsa sebelum tabel dikosongkan.
                requestToken++;

                if (currentRequest) {
                    currentRequest.abort();
                    currentRequest = null;
                }

                isLoading = false;
                allData = [];
                currentPage = 0;
                hasMoreData = true;
                table.clear().draw();
                loadMoreData();
            }


            $('.dataTables_scrollBody').on('scroll', function() {
                const scrollTop = $(this).scrollTop();
                const scrollHeight = $(this)[0].scrollHeight;
                const clientHeight = $(this).height();

                // Load earlier (70%) without delay
                if (scrollTop + clientHeight >= scrollHeight * 0.70) {
                    loadMoreData();
                }
            });

            // 🔹 Hanya filter lain yang trigger reload otomatis (bukan #filter)
            $('#status, #search_type, #start_date, #end_date')
                .on('change keyup input paste', function() {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        resetAndReload();
                    }, 100);
                });

            // 🔹 Kalau dropdown filter diubah, cuma toggle custom date range TANPA reload
            $('#filter').on('change', function() {
                if ($(this).val() === 'custom') {
                    $('.custom-range').removeClass('d-none');
                } else {
                    $('.custom-range').addClass('d-none');
                }

                resetAndReload(); // ⬅️ WAJIB DITAMBAHKAN
            });

            // 🔹 Tombol apply baru reload data
            $('#apply-filter').on('click', function() {
                resetAndReload();
            });

            // $('#search_keyword, #search_product').on('keyup input paste', function() {
            //     clearTimeout(searchTimeout);
            //     searchTimeout = setTimeout(() => {
            //         const keyword = $('#search_keyword').val().trim();
            //         const productSearch = $('#search_product').val().trim();

            //         if (keyword === '' && productSearch === '') {
            //             // 🧹 Kalau kosong semua -> tampilkan semua data lagi
            //             allData = [];
            //             currentPage = 0;
            //             hasMoreData = true;
            //             table.clear().draw();
            //             loadMoreData();
            //         } else {
            //             // 🔍 Kalau ada isi -> jalankan search
            //             resetAndReload();
            //         }
            //     }, 300);
            // });


            $('#search_keyword, #search_product').on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    resetAndReload();
                }
            });

            // Kosong → auto reload
            $('#search_keyword, #search_product').on('input', function() {
                if ($(this).val().trim() === '') {
                    resetAndReload();
                }
            });

            loadMoreData();

            $('#designListTable tbody').on('click', 'tr', function(e) {
                if ($(e.target).closest('td.dt-control').length) return;

                let $tr = $(this);
                let row = table.row($tr);

                $('#designListTable tbody tr').removeClass('action-shown').next('.action-row').remove();

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
                if (!$(e.target).closest('#designListTable').length) {
                    $('#designListTable tbody tr').removeClass('action-shown').next('.action-row').remove();
                }
            });

            $(document).on('click', '.upload-btn', function() {
                const id = $(this).data('id');
                const previewUrl = $(this).data('preview');
                const note = $(this).data('note');
                const orderNote = $(this).data('order_note') || '-';
                $('#orderNoteBox').text(orderNote);

                $('#design_item_id').val(id);

                // Modal ini dipakai bergantian oleh semua design item, jadi tombol
                // di dalamnya harus diarahkan ulang ke item yang sedang dibuka.
                const product = $(this).data('product') || '';

                $('#uploadPickCustomerDesign').data('id', id).data('product', product);

                $('#note').val(note || '');

                $('#preview_image').val('');

                pastedImageBlobs = [];
                $('#previewContainer').empty();

                const previewContainer = $('#uploadModal .modal-body .old-preview');
                if (previewUrl) {
                    if (previewContainer.length === 0) {
                        $('#uploadModal .modal-body').prepend(`
                            <div class="old-preview mb-2 text-center">
                                <p class="text-muted small mb-1">Current Preview:</p>
                                    <img src="${previewUrl}" width="120" height="90"
                                        style="border-radius:8px;object-fit:cover;object-position:center;border:1px solid #ddd;">
                                <hr>
                            </div>
                        `);
                    } else {
                        previewContainer.find('img').attr('src', previewUrl);
                    }
                } else {
                    previewContainer.remove();
                }
            });

            // $('#uploadForm').on('submit', function(e) {
            //     e.preventDefault();

            //     if (pastedImageBlobs.length === 0 && !$('#preview_image')[0].files.length) {
            //         Swal.fire({
            //             icon: 'warning',
            //             title: 'No image selected',
            //             text: 'Silakan upload atau paste minimal 1 gambar.',
            //         });
            //         return;
            //     }

            //     const formData = new FormData(this);

            //     formData.delete('preview_image[]');
            //     formData.delete('note_per_image[]');

            //     const notes = [];
            //     $('#previewContainer .note-input').each(function() {
            //         notes.push($(this).val());
            //     });

            //     pastedImageBlobs.forEach((blob, index) => {
            //         formData.append('preview_image[]', blob, `screenshot_${index + 1}.png`);
            //         formData.append('note_per_image[]', notes[index] || '');
            //     });

            //     $.ajax({
            //         url: '/erp/design-items/' + $('#design_item_id').val() + '/upload',
            //         method: 'POST',
            //         data: formData,
            //         processData: false,
            //         contentType: false,
            //         success: function(res) {
            //             $('#uploadModal').modal('hide');
            //             Swal.fire({
            //                 icon: 'success',
            //                 title: 'Success',
            //                 text: res.message,
            //             });
            //             resetAndReload();
            //         },
            //         error: function(err) {
            //             Swal.fire({
            //                 icon: 'error',
            //                 title: 'Failed',
            //                 text: err.responseJSON?.message || 'Upload failed.',
            //             });
            //         }
            //     });
            // });

            $('#uploadForm').on('submit', function(e) {
                e.preventDefault();

                if (pastedImageBlobs.length === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'No image selected',
                        text: 'Silakan upload atau paste minimal 1 gambar.'
                    });
                    return;
                }

                const formData = new FormData(this);

                // Tambahkan token manual untuk memastikan tidak hilang
                formData.append('_token', $('meta[name="csrf-token"]').attr('content'));

                // Buang input preview lama (yang sudah dihapus dari HTML)
                formData.delete('preview_image[]');
                formData.delete('note_per_image[]');

                // Tambahkan file hasil paste
                const notes = [];
                $('#previewContainer .note-input').each(function() {
                    notes.push($(this).val());
                });

                pastedImageBlobs.forEach((blob, index) => {
                    formData.append('preview_image[]', blob, `screenshot_${index + 1}.png`);
                    formData.append('note_per_image[]', notes[index] || '');
                });

                $.ajax({
                    url: '/erp/design-items/' + $('#design_item_id').val() + '/upload',
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(res) {
                        $('#uploadModal').modal('hide');
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: res.message,
                        });
                        resetAndReload();
                    },
                    error: function(err) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Failed',
                            text: ajaxErrorMessage(err, 'Upload gagal'),
                        });
                    }
                });
            });

            // Hapus preview. Satu endpoint untuk dua kasus: tanpa `index`
            // berarti buang semua gambar, dengan `index` cuma satu gambar.
            // Indeks tidak pernah dipakai untuk menyusun path file — server
            // membaca ulang path dari database — jadi aman dikirim dari klien.
            function deletePreview($button, itemId, index, question) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Hapus preview?',
                    text: question,
                    showCancelButton: true,
                    confirmButtonText: 'Hapus',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#d33',
                }).then(function(result) {
                    // SweetAlert2 yang dibundel project ini versi lama: hasil
                    // konfirmasinya `result.value`. `result.isConfirmed` baru ada
                    // di v9+, dan kalau dipakai di sini nilainya selalu undefined
                    // sehingga tombol Hapus tidak pernah mengirim request.
                    const dikonfirmasi = result === true ||
                        result?.isConfirmed === true ||
                        result?.value === true;

                    if (!dikonfirmasi) {
                        return;
                    }

                    const payload = {
                        _method: 'DELETE'
                    };

                    if (index !== null) {
                        payload.index = index;
                    }

                    $button.prop('disabled', true);

                    $.ajax({
                        url: '/erp/design-items/' + itemId + '/preview',
                        method: 'POST',
                        data: payload,
                        success: function(res) {
                            hideModal('multiImageViewerModal');
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil',
                                text: res.message,
                            });
                            resetAndReload();
                        },
                        error: function(err) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal',
                                text: ajaxErrorMessage(err, 'Gagal menghapus preview'),
                            });
                        },
                        complete: function() {
                            $button.prop('disabled', false);
                        }
                    });
                });
            }

            $(document).on('click', '.delete-preview-btn', function() {
                const $button = $(this);
                const count = $button.data('count') || 0;

                deletePreview($button, $button.data('id'), null,
                    'Semua preview (' + count + ' gambar) untuk ' + $button.data('product') +
                    ' akan dihapus.');
            });

            $(document).on('click', '.preview-image-delete', function() {
                const $button = $(this);

                deletePreview($button, $button.data('id'), $button.data('index'),
                    'Gambar ini akan dihapus dari preview.');
            });

            $(document).on('click', '.btn-verify', function() {
                let id = $(this).data('id');
                let name = $(this).data('name');
                let url = $(this).data('url');

                $('#DesignName').text(name);
                $('#formVerifyDesign').attr('action', url);
            });

            $('#formVerifyDesign').on('submit', function(e) {
                e.preventDefault();
                const $form = $(this);

                if ($form.data('submitting')) {
                    return;
                }

                $form.data('submitting', true);
                const $submitButton = $form.find('button[type="submit"]');
                $submitButton.prop('disabled', true);

                const url = $form.attr('action');
                const token = $('meta[name="csrf-token"]').attr('content');

                $.ajax({
                    url: url,
                    method: 'POST',
                    data: {
                        _token: token
                    },
                    success: function(res) {
                        $('#verifyDesignModal').modal('hide');
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: res.message
                        });

                        resetAndReload();
                    },

                    error: function(err) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: err.responseJSON?.message || 'Terjadi kesalahan'
                        });
                    },
                    complete: function() {
                        $form.data('submitting', false);
                        $submitButton.prop('disabled', false);
                    }
                });
            });

            // ========== Handle BATAL VERIFIED (UNVERIFY) ==========
            $(document).on('click', '.btn-unverify', function() {
                let id = $(this).data('id');
                let name = $(this).data('name');
                let url = $(this).data('url');

                $('#UnverifyDesignName').text(name);
                $('#formUnverifyDesign').attr('action', url);
            });

            $('#formUnverifyDesign').on('submit', function(e) {
                e.preventDefault();
                const url = $(this).attr('action');
                const token = $('meta[name="csrf-token"]').attr('content');

                $.ajax({
                    url: url,
                    method: 'POST',
                    data: {
                        _token: token
                    },
                    success: function(res) {
                        $('#unverifyDesignModal').modal('hide');
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: res.message
                        });
                        resetAndReload();
                    },
                    error: function(err) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: err.responseJSON?.message || 'Terjadi kesalahan'
                        });
                    }
                });
            });

            let pastedImageBlobs = [];

            $('#pasteArea').on('paste', function(e) {
                const items = (e.originalEvent.clipboardData || e.clipboardData).items;
                for (const item of items) {
                    if (item.type.indexOf('image') === 0) {
                        const blob = item.getAsFile();
                        pastedImageBlobs.push(blob);

                        const reader = new FileReader();
                        reader.onload = function(event) {
                            const imgHTML = `
                    <div class="border rounded p-1 position-relative" style="max-width:250px;">
                        <img src="${event.target.result}" class="rounded border mb-1" 
                             style="width:100%;height:150px;object-fit:cover;">
                        <textarea class="form-control form-control-sm note-input" 
                            placeholder="Catatan untuk gambar ini..."></textarea>
                    </div>
                `;
                            $('#previewContainer').append(imgHTML);
                        };
                        reader.readAsDataURL(blob);
                        $('#preview_image').val('');
                    }
                }
            });

            $('#preview_image').on('change', function(e) {
                $('#previewContainer').empty();
                pastedImageBlobs = [];
                for (const file of e.target.files) {
                    pastedImageBlobs.push(file);
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        const imgHTML = `
                <div class="border rounded p-1 position-relative" style="max-width:250px;">
                    <img src="${event.target.result}" class="rounded border mb-1" 
                         style="width:100%;height:150px;object-fit:cover;">
                    <textarea class="form-control form-control-sm note-input" 
                        placeholder="Catatan untuk gambar ini..."></textarea>
                </div>
            `;
                        $('#previewContainer').append(imgHTML);
                    };
                    reader.readAsDataURL(file);
                }
            });

            $(document).on('click', '.img-viewer', function(e) {
                e.preventDefault();
                const imgSrc = $(this).data('src');
                const note = $(this).data('note') || '-';
                $('#viewerImage').attr('src', imgSrc);
                $('#viewerNote').text(note);
                $('#imageViewerModal').modal('show');
            });
            {{-- Modul Design Customer dimatikan: lihat config/features.php --}}
            @if (config('features.customer_design'))

                // ========== Pilih design dari katalog Design Customer ==========
                let customerDesignCatalog = [];

                function escapeHtml(value) {
                    return $('<div>').text(value ?? '').html();
                }

                function updatePickerSelectedInfo() {
                    const $checked = $('#pickerContainer .picker-image:checked');

                    $('#pickerContainer .picker-card').removeClass('is-selected');

                    if ($checked.length === 0) {
                        $('#pickerSelectedInfo').text('Belum ada design dipilih');
                        return;
                    }

                    $checked.closest('.picker-card').addClass('is-selected');
                    $('#pickerSelectedInfo').text('Terpilih: ' + $checked.data('title'));
                }

                function renderCustomerDesignCatalog(keyword) {
                    const container = $('#pickerContainer');
                    const term = (keyword || '').trim().toLowerCase();

                    container.empty();

                    // Semua gambar diratakan jadi satu grid — judul design ikut di
                    // tiap kartu, jadi tidak perlu bingkai per design yang bikin
                    // modalnya melebar ke bawah.
                    const cards = [];

                    customerDesignCatalog.forEach(design => {
                        const cocok = term === '' ||
                            (design.title || '').toLowerCase().includes(term) ||
                            (design.notes || '').toLowerCase().includes(term);

                        if (!cocok) return;

                        (Array.isArray(design.images) ? design.images : []).forEach((img, index) => {
                            cards.push({
                                designId: design.id,
                                title: design.title,
                                index: index,
                                note: img.note || '',
                                createdAt: design.created_at || '',
                                url: ('/' + img.file).replace(/\/{2,}/g, '/')
                            });
                        });
                    });

                    if (cards.length === 0) {
                        container.html(
                            '<div class="col-12"><p class="text-muted mb-0">' +
                            (customerDesignCatalog.length === 0 ?
                                'Belum ada design untuk customer ini. Tambahkan lewat menu <strong>Design &rsaquo; Design Customer</strong>.' :
                                'Tidak ada design yang cocok dengan pencarian.') +
                            '</p></div>'
                        );
                        updatePickerSelectedInfo();
                        return;
                    }

                    cards.forEach(card => {
                        const inputId = `picker_${card.designId}_${card.index}`;
                        const caption = card.note || card.createdAt;

                        container.append(`
                            <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                                <label class="picker-card d-block" for="${inputId}">
                                    <input type="radio" name="picker_choice" id="${inputId}"
                                        class="picker-image d-none" data-design-id="${card.designId}"
                                        data-index="${card.index}" data-title="${escapeHtml(card.title)}"
                                        data-note="${escapeHtml(card.note)}">
                                    <img src="${card.url}" alt="${escapeHtml(card.title)}">
                                    <div class="picker-title" title="${escapeHtml(card.title)}">${escapeHtml(card.title)}</div>
                                    <div class="picker-meta" title="${escapeHtml(caption)}">${escapeHtml(caption || '-')}</div>
                                </label>
                            </div>
                        `);
                    });

                    updatePickerSelectedInfo();
                }

                $(document).on('click', '.pick-customer-design-btn', function(e) {
                    e.stopPropagation();

                    // Picker dibuka di atas modal Upload, tidak menggantikannya:
                    // menutup modal Upload dulu berarti menunggu transisi Bootstrap
                    // selesai, dan di halaman ini transisi itu tidak selalu tuntas
                    // sehingga picker-nya bisa tidak muncul sama sekali. Markup
                    // picker berada setelah modal Upload di DOM, jadi ia otomatis
                    // tampil di atas. Kalau ditutup lewat Batal, operator kembali ke
                    // modal Upload yang masih terbuka.
                    openCustomerDesignPicker($(this).data('id'), $(this).data('product') || '');
                });

                function openCustomerDesignPicker(itemId, product) {
                    $('#picker_design_item_id').val(itemId);
                    $('#pickerProductName').text(product);
                    $('#pickerSearch').val('');
                    $('#pickerNote').val('');
                    $('#pickerCustomerName').text('Memuat...');
                    $('#pickerSelectedInfo').text('Belum ada design dipilih');
                    $('#pickerContainer').html('<div class="col-12 text-muted">Memuat design customer...</div>');
                    $('#customerDesignPickerModal').modal('show');

                    $.get('/erp/design-items/' + itemId + '/customer-designs', function(res) {
                        customerDesignCatalog = res.data || [];
                        $('#pickerCustomerName').text(res.customer || 'Customer tidak diketahui');
                        renderCustomerDesignCatalog('');
                    }).fail(function(err) {
                        $('#customerDesignPickerModal').modal('hide');
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: ajaxErrorMessage(err, 'Gagal memuat design customer')
                        });
                    });
                }

                $('#pickerSearch').on('input', function() {
                    renderCustomerDesignCatalog($(this).val());
                });

                // Radio, jadi memilih kartu lain otomatis melepas pilihan sebelumnya:
                // satu design item hanya boleh memakai satu design customer.
                $(document).on('change', '.picker-image', function() {
                    updatePickerSelectedInfo();

                    // Catatan bawaan gambar dipakai sebagai isian awal, masih bisa
                    // ditimpa operator sebelum dipasang.
                    $('#pickerNote').val($(this).data('note') || '');
                });

                $('#pickerApplyButton').on('click', function() {
                    const $selected = $('#pickerContainer .picker-image:checked').first();

                    if ($selected.length === 0) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Belum ada yang dipilih',
                            text: 'Pilih satu design terlebih dahulu.'
                        });
                        return;
                    }

                    const $button = $(this);
                    $button.prop('disabled', true);

                    $.ajax({
                        url: '/erp/design-items/' + $('#picker_design_item_id').val() + '/attach-customer-design',
                        method: 'POST',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content'),
                            design_id: $selected.data('design-id'),
                            index: $selected.data('index'),
                            note: $('#pickerNote').val() || ''
                        },
                        success: function(res) {
                            // Preview item sudah diganti, jadi modal Upload di
                            // belakang picker ikut ditutup — isinya sudah basi.
                            hideModal('customerDesignPickerModal');
                            hideModal('uploadModal');
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil',
                                text: res.message
                            });
                            resetAndReload();
                        },
                        error: function(err) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal',
                                text: ajaxErrorMessage(err, 'Gagal memasang design customer')
                            });
                        },
                        complete: function() {
                            $button.prop('disabled', false);
                        }
                    });
                });
            @endif
        });

        $(document).on('click', '.preview-btn', function() {
            const itemId = $(this).data('id');
            const images = $(this).data('images');
            const product = $(this).data('product');
            const orderNote = $(this).data('order_note') || '-';
            const container = $('#multiViewerContainer');

            container.empty();

            $('#multiViewerProduct').text(product);
            $('#multiViewerOrderNote').text(orderNote || '-');

            const count = Array.isArray(images) ? images.length : 0;

            $('#previewDeleteAll')
                .data('id', itemId)
                .data('product', product)
                .data('count', count)
                .toggleClass('d-none', count === 0);

            if (Array.isArray(images) && images.length > 0) {
                images.forEach((img, index) => {
                    const fileUrl = img.file ? `/${img.file}`.replace(/\/{2,}/g, '/') : '';
                    const note = escapeHtml(img.note || '-');
                    // Indeks gambar dipakai server untuk menentukan entri mana
                    // yang dibuang, jadi urutannya harus sama persis dengan
                    // isi kolom preview_image.
                    const itemHTML = `
                        <div class="image-item border rounded p-2">
                            <img src="${fileUrl}" class="img-fluid rounded mb-1"
                                style="cursor:pointer;"
                                onclick="window.open('${fileUrl}', '_blank')">
                            <div class="d-flex justify-content-between align-items-center gap-2">
                                <p class="small text-muted mb-0">${note}</p>
                                <button type="button" class="btn btn-sm btn-outline-danger preview-image-delete"
                                    data-id="${itemId}" data-index="${index}">
                                    <i class="feather-trash-2"></i> Hapus
                                </button>
                            </div>
                        </div>
                    `;
                    container.append(itemHTML);
                });
            } else {
                container.html('<p class="text-muted">Tidak ada gambar tersedia.</p>');
            }

            $('#multiImageViewerModal').modal('show');
        });

        function escapeHtml(value) {
            return $('<div>').text(value ?? '').html();
        }

        // Tutup modal lewat API native Bootstrap, dengan $(...).modal('hide')
        // sebagai cadangan. Plugin jQuery-nya kadang tidak bereaksi kalau
        // transisi modal sebelumnya tidak pernah selesai.
        function hideModal(target) {
            const el = typeof target === 'string' ? document.getElementById(target) : target;

            if (!el) {
                return;
            }

            if (window.bootstrap?.Modal) {
                bootstrap.Modal.getOrCreateInstance(el).hide();

                return;
            }

            $(el).modal('hide');
        }

        // Pesan error yang menyebutkan penyebabnya.
        //
        // Sebelumnya semua kegagalan tampil sebagai "Upload failed." karena
        // hanya `responseJSON.message` yang dibaca. Respons yang tidak dibuat
        // Laravel — 413 dari nginx, atau body kosong waktu upload melebihi
        // post_max_size PHP — tidak punya JSON sama sekali, jadi penyebab yang
        // paling sering terjadi justru yang paling tidak terlihat.
        function ajaxErrorMessage(err, fallback) {
            const status = err.status;
            const json = err.responseJSON;

            if (json?.message) {
                return json.message;
            }

            // Error validasi Laravel: tampilkan pesan pertamanya.
            if (json?.errors) {
                const first = Object.values(json.errors)[0];
                if (Array.isArray(first) && first.length) {
                    return first[0];
                }
            }

            if (status === 0) {
                return 'Koneksi ke server terputus. Cek jaringan lalu ulangi.';
            }

            if (status === 403) {
                return 'Akses ditolak (403). Akun ini belum punya izin untuk halaman Design.';
            }

            if (status === 419) {
                return 'Sesi kedaluwarsa (419). Refresh halaman lalu ulangi.';
            }

            if (status === 413) {
                return 'File terlalu besar (413) — ditolak web server sebelum sampai ke aplikasi. ' +
                    'Kecilkan gambarnya, atau minta admin server menaikkan client_max_body_size.';
            }

            return (fallback || 'Terjadi kesalahan') + ' (HTTP ' + status + ' ' + (err.statusText || '') + ')';
        }
    </script>
@endpush
