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
        }

        #designListTable tbody tr {
            animation: fadeIn 0.3s ease-in;
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
    <div class="main-content">
        <div class="row">
            <div class="col-lg-12">
                <div class="card stretch stretch-full">
                    <div class="card-body p-0">
                        <div class="row g-3 p-4 justify-content-between">
                            <div class="col-lg-4">
                                <label for="filter" class="fw-semibold fs-12">Date Filter</label>
                                <div class="d-flex align-items-center gap-2 page-header-right-items-wrapper">
                                    <select id="filter" class="form-control" style="width:180px;">
                                        <option value="all">All Time</option>
                                        <option value="yearly">Yearly</option>
                                        <option value="year_to_date">Year to Date</option>
                                        <option value="last_30_days">Last 30 Days</option>
                                        <option value="this_month">This Month</option>
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
                                    <div class="col-lg-6">
                                        <label for="search_type" class="fw-semibold fs-12">Search</label>
                                        <div class="row g-2">
                                            <div class="col-md-5">
                                                <select id="search_type" class="form-control">
                                                    <option value="design_number">Design Number</option>
                                                    <option value="customer">Customer</option>
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
                                        <th class="w-30">Design Number</th>
                                        <th class="w-20">Customer</th>
                                        <th class="w-50">Products</th>
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

                        <div class="mb-3">
                            <label class="form-label">Upload / Paste Screenshot (Multiple)</label>

                            <div id="pasteArea" class="border rounded p-3 text-center"
                                style="min-height: 120px; cursor: pointer;">
                                <p class="text-muted small mb-2">
                                    Klik di sini lalu tekan <strong>Ctrl + V</strong> untuk paste screenshot
                                </p>
                                <div id="previewContainer" class="d-flex flex-wrap gap-3 justify-content-start"></div>
                            </div>

                            {{-- <input type="file" class="form-control mt-2" id="preview_image" name="preview_image[]"
                                accept="image/*" multiple> --}}
                        </div>

                        {{-- <div class="mb-3">
                            <label for="note" class="form-label">Note</label>
                            <textarea class="form-control" name="note" id="note" rows="3" placeholder="Add a note..."></textarea>
                        </div> --}}
                    </div>
                    <div class="modal-footer">
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
                    <img id="viewerImage" src="" alt="Preview" class="img-fluid rounded mb-3"
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

    <div class="modal fade" id="multiImageViewerModal" tabindex="-1" aria-labelledby="multiImageViewerModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title text-white" id="multiImageViewerModalLabel">Preview Design</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6 class="fw-bold mb-3" id="multiViewerProduct"></h6>
                    <div id="multiViewerContainer" class="d-flex flex-wrap gap-3 justify-content-start"></div>
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
            let searchTimeout = null;

            const table = $('#designListTable').DataTable({
                processing: false,
                serverSide: false,
                scrollY: '60vh',
                scrollCollapse: true,
                paging: false,
                searching: false,
                info: false,
                lengthChange: false,
                order: [
                    [3, 'desc']
                ],
                data: [],
                columns: [{
                        data: 'design_number',
                        name: 'design_number'
                    },
                    {
                        data: 'customer',
                        name: 'customer'
                    },
                    {
                        data: 'products',
                        name: 'products',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'id',
                        name: 'id',
                        visible: false,
                        searchable: false
                    }
                ]
            });

            function loadMoreData() {
                if (isLoading || !hasMoreData) return;
                isLoading = true;

                $.ajax({
                    url: "{{ url('/erp/design/data') }}",
                    type: 'GET',
                    data: {
                        start: currentPage * 15,
                        length: 15,
                        filter: $('#filter').val(),
                        start_date: $('#start_date').val(),
                        end_date: $('#end_date').val(),
                        search_type: $('#search_type').val(),
                        search_keyword: $('#search_keyword').val(),
                        status: $('#status').val(),
                    },
                    success: function(response) {
                        if (response && response.data && response.data.length > 0) {
                            allData = allData.concat(response.data);
                            table.clear().rows.add(allData).draw(false);
                            currentPage++;
                        } else {
                            hasMoreData = false;
                        }
                        isLoading = false;
                    },
                    error: function(xhr) {
                        console.error('❌ AJAX Error:', xhr.responseText);
                        isLoading = false;
                    }
                });
            }

            function resetAndReload() {
                allData = [];
                currentPage = 0;
                hasMoreData = true;
                table.clear().draw();
                loadMoreData();
            }

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

            $('#filter').on('change', function() {
                if ($(this).val() === 'custom') {
                    $('.custom-range').removeClass('d-none');
                } else {
                    $('.custom-range').addClass('d-none');
                    resetAndReload();
                }
            });

            $('#apply-filter').on('click', function() {
                resetAndReload();
            });

            $('#status').on('change', function() {
                resetAndReload();
            });

            $('#search_keyword').on('keyup', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => resetAndReload(), 400);
            });

            $('#search_type').on('change', function() {
                resetAndReload();
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

                $('#design_item_id').val(id);

                $('#note').val(note || '');

                $('#preview_image').val('');

                pastedImageBlobs = [];
                $('#previewContainer').empty();

                const previewContainer = $('#uploadModal .modal-body .old-preview');
                if (previewUrl) {
                    if (previewContainer.length === 0) {
                        $('#uploadModal .modal-body').prepend(`
                            <div class="old-preview mb-3 text-center">
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

            $('#uploadForm').on('submit', function(e) {
                e.preventDefault();

                if (pastedImageBlobs.length === 0 && !$('#preview_image')[0].files.length) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'No image selected',
                        text: 'Silakan upload atau paste minimal 1 gambar.',
                    });
                    return;
                }

                const formData = new FormData(this);

                formData.delete('preview_image[]');
                formData.delete('note_per_image[]');

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
                            text: err.responseJSON?.message || 'Upload failed.',
                        });
                    }
                });
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
                const url = $(this).attr('action');
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
                    <div class="border rounded p-2 position-relative" style="max-width:250px;">
                        <img src="${event.target.result}" class="rounded border mb-2" 
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
                <div class="border rounded p-2 position-relative" style="max-width:250px;">
                    <img src="${event.target.result}" class="rounded border mb-2" 
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
        });

        $(document).on('click', '.preview-btn', function() {
            const images = $(this).data('images');
            const product = $(this).data('product');
            const container = $('#multiViewerContainer');
            container.empty();

            $('#multiViewerProduct').text(product);

            if (Array.isArray(images) && images.length > 0) {
                images.forEach(img => {
                    const fileUrl = img.file ? `/${img.file}`.replace(/\/{2,}/g, '/') : '';
                    const note = img.note || '-';
                    const itemHTML = `
                <div class="text-center border rounded p-2" style="max-width:260px;">
                    <img src="${fileUrl}" class="img-fluid rounded mb-2" 
                        style="width:180px;height:180px;object-fit:cover;cursor:pointer;"
                        onclick="window.open('${fileUrl}', '_blank')">
                    <p class="small text-muted mb-0">${note}</p>
                </div>
            `;
                    container.append(itemHTML);
                });
            } else {
                container.html('<p class="text-muted">Tidak ada gambar tersedia.</p>');
            }

            $('#multiImageViewerModal').modal('show');
        });
    </script>
@endpush
