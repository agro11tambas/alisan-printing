@extends('erp.layouts.main')

@push('styles')
    <style>
        #customerDesignTable {
            width: 100% !important;
            min-width: 0;
        }

        #customerDesignTable_wrapper .dataTables_scrollBody {
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

        .cd-thumb {
            width: 64px;
            height: 64px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #ddd;
            cursor: pointer;
        }

        #cdPasteArea {
            min-height: 110px;
            cursor: pointer;
        }

        .cd-image-card {
            max-width: 220px;
        }

        .cd-image-card img {
            width: 100%;
            height: 130px;
            object-fit: cover;
        }

        /* Tabel design di dalam kolom Design: lebar kolom fix biar judul
           panjang tetap rapi, sama seperti tabel produk di modul Design. */
        #customerDesignTable td .customer-design-wrapper {
            width: 100%;
        }

        .customer-design-table {
            table-layout: fixed;
            width: 100%;
            min-width: 560px;
        }

        .customer-design-table col.col-title {
            width: 28%;
        }

        .customer-design-table col.col-image {
            width: 38%;
        }

        .customer-design-table col.col-note {
            width: 22%;
        }

        .customer-design-table col.col-action {
            width: 12%;
        }

        .customer-design-table th,
        .customer-design-table td {
            white-space: normal !important;
            overflow-wrap: anywhere !important;
            word-break: break-word !important;
            vertical-align: middle;
        }
    </style>
@endpush

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Design Customer</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item"><a href="/erp/design">Design</a></li>
                <li class="breadcrumb-item">Design Customer</li>
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
                                <div class="row justify-content-end g-2">
                                    <div class="col-lg-6">
                                        <label for="filter_customer" class="fw-semibold fs-12">Customer</label>
                                        <select id="filter_customer" class="form-control">
                                            <option value="">Semua Customer</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-6">
                                        <label for="search_keyword" class="fw-semibold fs-12">Search</label>
                                        <input type="text" id="search_keyword" class="form-control"
                                            placeholder="Judul design / catatan...">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover bg-transparent" id="customerDesignTable">
                                <thead>
                                    <tr>
                                        <th class="wd-250">Customer</th>
                                        <th>Design</th>
                                        <th class="wd-150">Aksi</th>
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
    <div class="modal fade" id="customerDesignModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <form id="customerDesignForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title text-white" id="customerDesignModalLabel">Tambah Design Customer</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="customer_design_id">

                        <div class="mb-3">
                            <label for="customer_id" class="form-label fw-semibold">Customer <span
                                    class="text-danger">*</span></label>
                            <select id="customer_id" name="customer_id" class="form-control" style="width:100%;"></select>
                        </div>

                        <div class="mb-3">
                            <label for="title" class="form-label fw-semibold">Judul Design <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title"
                                placeholder="Contoh: Logo Kaos Lengan Panjang" maxlength="255">
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label fw-semibold">Catatan</label>
                            <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="Catatan design (opsional)"></textarea>
                        </div>

                        <div class="mb-3 d-none" id="cdExistingWrapper">
                            <label class="form-label fw-semibold">Design Tersimpan</label>
                            <div id="cdExistingContainer" class="d-flex flex-wrap gap-3"></div>
                            <small class="text-muted">Klik <strong>Hapus</strong> untuk membuang gambar dari design
                                ini.</small>
                        </div>

                        <div class="mb-2">
                            <label class="form-label fw-semibold">Upload Design</label>
                            <div id="cdPasteArea" class="border rounded p-2 text-center">
                                <p class="text-muted small mb-1">
                                    Klik di sini lalu tekan <strong>Ctrl + V</strong> untuk paste screenshot,
                                    atau pilih file lewat tombol di bawah.
                                </p>
                                <input type="file" class="form-control mt-1" id="cdFileInput" accept="image/*" multiple>
                                <div id="cdPreviewContainer" class="d-flex flex-wrap gap-3 justify-content-start mt-2">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="cdSubmitButton">Simpan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="customerDesignDeleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title text-white">Hapus Design Customer?</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menghapus design <strong id="cdDeleteName"></strong>?</p>
                    <p class="text-muted small mb-0">Design yang sudah terpasang di modul Design tidak ikut terhapus.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger" id="cdDeleteConfirm">Hapus</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="customerDesignViewerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title text-white" id="cdViewerTitle">Preview Design</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="cdViewerContainer" class="d-flex flex-column gap-3"></div>
                </div>
            </div>
        </div>
    </div>
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {
            const csrfToken = $('meta[name="csrf-token"]').attr('content');

            let allData = [];
            let currentPage = 0;
            let isLoading = false;
            let hasMoreData = true;
            let requestToken = 0;
            let currentRequest = null;

            // File baru yang belum tersimpan (hasil paste maupun pilih file).
            let pendingFiles = [];
            let pendingNotes = [];
            // Gambar milik design yang sedang diedit, index-nya dipakai server
            // untuk tahu mana yang dipertahankan.
            let existingImages = [];
            let removedIndexes = [];
            let deleteTargetId = null;

            function escapeHtml(value) {
                return $('<div>').text(value ?? '').html();
            }

            function imageUrl(file) {
                return ('/' + file).replace(/\/{2,}/g, '/');
            }

            const table = $('#customerDesignTable').DataTable({
                processing: false,
                serverSide: false,
                scrollY: '60vh',
                scrollCollapse: true,
                paging: false,
                searching: false,
                info: false,
                lengthChange: false,
                ordering: false,
                data: [],
                columns: [{
                        data: 'customer_html',
                        width: '22%'
                    },
                    {
                        data: 'designs_html',
                        width: '63%'
                    },
                    {
                        data: 'action_html',
                        width: '15%'
                    }
                ]
            });

            function buildRow(row) {
                const latest = row.latest_at ?
                    `<small class="text-muted d-block">Terakhir: ${escapeHtml(row.latest_at)}</small>` : '';

                return {
                    id: row.id,
                    raw: row,
                    customer_html: `
                        <div style="white-space:normal;word-break:break-word;">
                            <div class="fw-semibold">${escapeHtml(row.customer)}</div>
                            <small class="text-muted d-block">${escapeHtml(row.phone || '-')}</small>
                            <span class="badge bg-soft-primary text-primary mt-1">${row.total_designs} design</span>
                            <span class="badge bg-soft-dark text-dark mt-1">${row.total_images} gambar</span>
                            ${latest}
                        </div>`,
                    designs_html: row.designs_html,
                    action_html: `
                        <button type="button" class="btn btn-sm btn-primary cd-add"
                            data-customer-id="${row.id}" data-customer-name="${escapeHtml(row.customer)}">
                            <i class="feather-plus"></i> Tambah Design
                        </button>`
                };
            }

            function loadMoreData() {
                if (isLoading || !hasMoreData) return;
                isLoading = true;

                const token = requestToken;

                currentRequest = $.ajax({
                    url: "{{ url('/erp/design/customer-designs/data') }}",
                    type: 'GET',
                    data: {
                        start: currentPage * 50,
                        length: 50,
                        filter: $('#filter').val(),
                        start_date: $('#start_date').val(),
                        end_date: $('#end_date').val(),
                        customer_id: $('#filter_customer').val(),
                        search_keyword: $('#search_keyword').val(),
                    },
                    success: function(response) {
                        if (token !== requestToken) return;

                        if (response && response.data && response.data.length > 0) {
                            allData = allData.concat(response.data.map(buildRow));
                            table.clear();
                            table.rows.add(allData).draw(false);
                            currentPage++;
                        }

                        hasMoreData = !!(response && response.has_more);
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

                if (scrollTop + clientHeight >= scrollHeight * 0.70) {
                    loadMoreData();
                }
            });

            const customerSelectConfig = {
                ajax: {
                    url: "{{ url('/erp/design/customer-designs/customers') }}",
                    dataType: 'json',
                    delay: 250,
                    data: params => ({
                        q: params.term
                    }),
                    processResults: data => data
                },
                minimumInputLength: 0,
                width: '100%'
            };

            $('#filter_customer').select2(Object.assign({}, customerSelectConfig, {
                placeholder: 'Semua Customer',
                allowClear: true
            }));

            $('#customer_id').select2(Object.assign({}, customerSelectConfig, {
                placeholder: 'Pilih customer',
                dropdownParent: $('#customerDesignModal')
            }));

            $('#filter_customer').on('change', resetAndReload);

            $('#filter').on('change', function() {
                if ($(this).val() === 'custom') {
                    $('.custom-range').removeClass('d-none');
                } else {
                    $('.custom-range').addClass('d-none');
                }
                resetAndReload();
            });

            $('#apply-filter').on('click', resetAndReload);
            $('#start_date, #end_date').on('change', resetAndReload);

            $('#search_keyword').on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    resetAndReload();
                }
            });

            $('#search_keyword').on('input', function() {
                if ($(this).val().trim() === '') {
                    resetAndReload();
                }
            });

            loadMoreData();

            function renderPendingFiles() {
                const container = $('#cdPreviewContainer');
                container.empty();

                // Kartu dibuat sinkron dulu supaya urutannya persis sama dengan
                // urutan file yang dikirim; FileReader hanya mengisi src-nya.
                pendingFiles.forEach((file, index) => {
                    const $card = $(`
                        <div class="border rounded p-1 cd-image-card" data-pending-index="${index}">
                            <img class="rounded border mb-1" alt="preview">
                            <textarea class="form-control form-control-sm cd-new-note"
                                placeholder="Catatan gambar ini...">${escapeHtml(pendingNotes[index] || '')}</textarea>
                            <button type="button" class="btn btn-sm btn-outline-danger w-100 mt-1 cd-remove-pending"
                                data-index="${index}">Batal</button>
                        </div>
                    `);

                    container.append($card);

                    const reader = new FileReader();
                    reader.onload = function(event) {
                        $card.find('img').attr('src', event.target.result);
                    };
                    reader.readAsDataURL(file);
                });
            }

            function renderExistingImages() {
                const container = $('#cdExistingContainer');
                container.empty();

                const visible = existingImages
                    .map((img, index) => ({
                        img,
                        index
                    }))
                    .filter(entry => !removedIndexes.includes(entry.index));

                if (visible.length === 0) {
                    $('#cdExistingWrapper').addClass('d-none');
                    return;
                }

                $('#cdExistingWrapper').removeClass('d-none');

                visible.forEach(entry => {
                    container.append(`
                        <div class="border rounded p-1 cd-image-card" data-index="${entry.index}">
                            <img src="${imageUrl(entry.img.file)}" class="rounded border mb-1">
                            <textarea class="form-control form-control-sm cd-existing-note"
                                data-index="${entry.index}"
                                placeholder="Catatan gambar ini...">${escapeHtml(entry.img.note || '')}</textarea>
                            <button type="button" class="btn btn-sm btn-outline-danger w-100 mt-1 cd-remove-existing"
                                data-index="${entry.index}">Hapus</button>
                        </div>
                    `);
                });
            }

            function resetForm() {
                $('#customer_design_id').val('');
                $('#title').val('');
                $('#notes').val('');
                $('#cdFileInput').val('');
                $('#customer_id').val(null).trigger('change');
                pendingFiles = [];
                pendingNotes = [];
                existingImages = [];
                removedIndexes = [];
                $('#cdPreviewContainer').empty();
                $('#cdExistingContainer').empty();
                $('#cdExistingWrapper').addClass('d-none');
            }

            // Tambah design langsung dari baris customer: customer-nya sudah
            // terisi, operator tinggal isi judul dan gambarnya.
            $(document).on('click', '.cd-add', function() {
                resetForm();

                const option = new Option($(this).data('customer-name'), $(this).data('customer-id'), true, true);
                $('#customer_id').append(option).trigger('change');

                $('#customerDesignModalLabel').text('Tambah Design — ' + $(this).data('customer-name'));
                $('#customerDesignModal').modal('show');
            });

            $(document).on('click', '.cd-edit', function() {
                const id = $(this).data('id');

                $.get("{{ url('/erp/design/customer-designs') }}/" + id, function(design) {
                    resetForm();

                    $('#customerDesignModalLabel').text('Edit Design Customer');
                    $('#customer_design_id').val(design.id);
                    $('#title').val(design.title);
                    $('#notes').val(design.notes || '');

                    const option = new Option(design.customer_name, design.customer_id, true, true);
                    $('#customer_id').append(option).trigger('change');

                    existingImages = design.images || [];
                    renderExistingImages();

                    $('#customerDesignModal').modal('show');
                }).fail(function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: 'Design tidak ditemukan.'
                    });
                });
            });

            $(document).on('click', '.cd-remove-existing', function() {
                removedIndexes.push(parseInt($(this).data('index'), 10));
                renderExistingImages();
            });

            function addPendingFile(file) {
                pendingFiles.push(file);
                pendingNotes.push('');
            }

            // Catatan disimpan di array supaya tidak hilang ketika daftar
            // gambar digambar ulang (mis. setelah salah satu dibatalkan).
            $(document).on('input', '#cdPreviewContainer .cd-new-note', function() {
                const index = parseInt($(this).closest('.cd-image-card').data('pending-index'), 10);
                pendingNotes[index] = $(this).val();
            });

            $(document).on('click', '.cd-remove-pending', function() {
                const index = parseInt($(this).data('index'), 10);
                pendingFiles.splice(index, 1);
                pendingNotes.splice(index, 1);
                renderPendingFiles();
            });

            $('#cdFileInput').on('change', function(e) {
                for (const file of e.target.files) {
                    addPendingFile(file);
                }
                renderPendingFiles();
            });

            $('#cdPasteArea').on('paste', function(e) {
                const items = (e.originalEvent.clipboardData || e.clipboardData).items;

                for (const item of items) {
                    if (item.type.indexOf('image') === 0) {
                        addPendingFile(item.getAsFile());
                    }
                }

                renderPendingFiles();
            });

            $('#customerDesignForm').on('submit', function(e) {
                e.preventDefault();

                const id = $('#customer_design_id').val();
                const isEdit = !!id;

                if (!$('#customer_id').val()) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Customer belum dipilih',
                        text: 'Silakan pilih customer terlebih dahulu.'
                    });
                    return;
                }

                if (!$('#title').val().trim()) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Judul kosong',
                        text: 'Silakan isi judul design.'
                    });
                    return;
                }

                const keptCount = existingImages.filter((img, index) => !removedIndexes.includes(index)).length;

                if (pendingFiles.length === 0 && keptCount === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Belum ada gambar',
                        text: 'Upload atau paste minimal 1 gambar design.'
                    });
                    return;
                }

                const formData = new FormData();
                formData.append('_token', csrfToken);
                formData.append('customer_id', $('#customer_id').val());
                formData.append('title', $('#title').val().trim());
                formData.append('notes', $('#notes').val());

                if (isEdit) {
                    $('#cdExistingContainer .cd-existing-note').each(function() {
                        const index = $(this).data('index');
                        formData.append('kept_images[]', index);
                        formData.append('kept_image_notes[' + index + ']', $(this).val());
                    });
                }

                const newNotes = $('#cdPreviewContainer .cd-new-note').map(function() {
                    return $(this).val();
                }).get();

                pendingFiles.forEach((file, index) => {
                    const name = file.name || `design_${index + 1}.png`;
                    formData.append('images[]', file, name);
                    formData.append('image_notes[' + index + ']', newNotes[index] || '');
                });

                const $button = $('#cdSubmitButton');
                $button.prop('disabled', true);

                $.ajax({
                    url: isEdit ?
                        "{{ url('/erp/design/customer-designs') }}/" + id : "{{ url('/erp/design/customer-designs') }}",
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(res) {
                        $('#customerDesignModal').modal('hide');
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: res.message
                        });
                        resetAndReload();
                    },
                    error: function(err) {
                        const errors = err.responseJSON?.errors;
                        const message = errors ?
                            Object.values(errors).flat().join('\n') :
                            (err.responseJSON?.message || 'Gagal menyimpan design customer.');

                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: message
                        });
                    },
                    complete: function() {
                        $button.prop('disabled', false);
                    }
                });
            });

            $(document).on('click', '.cd-delete', function() {
                deleteTargetId = $(this).data('id');
                $('#cdDeleteName').text($(this).data('name'));
                $('#customerDesignDeleteModal').modal('show');
            });

            $('#cdDeleteConfirm').on('click', function() {
                if (!deleteTargetId) return;

                const $button = $(this);
                $button.prop('disabled', true);

                $.ajax({
                    url: "{{ url('/erp/design/customer-designs') }}/" + deleteTargetId,
                    method: 'POST',
                    data: {
                        _token: csrfToken,
                        _method: 'DELETE'
                    },
                    success: function(res) {
                        $('#customerDesignDeleteModal').modal('hide');
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
                            text: err.responseJSON?.message || 'Gagal menghapus design.'
                        });
                    },
                    complete: function() {
                        $button.prop('disabled', false);
                        deleteTargetId = null;
                    }
                });
            });

            // Data gambar dibawa di atribut data-images milik tombol/thumbnail
            // yang dirender partial, jadi viewer tidak perlu request ulang.
            $(document).on('click', '.cd-view', function() {
                const images = $(this).data('images') || [];
                const container = $('#cdViewerContainer');

                $('#cdViewerTitle').text($(this).data('title') + ' — ' + $(this).data('customer'));
                container.empty();

                if (images.length === 0) {
                    container.html('<p class="text-muted">Tidak ada gambar tersedia.</p>');
                } else {
                    images.forEach(img => {
                        const url = imageUrl(img.file);
                        container.append(`
                            <div class="border rounded p-2 text-center">
                                <img src="${url}" class="img-fluid rounded mb-1"
                                    style="max-height:500px;cursor:pointer;"
                                    onclick="window.open('${url}', '_blank')">
                                <p class="small text-muted mb-0">${escapeHtml(img.note || '-')}</p>
                            </div>
                        `);
                    });
                }

                $('#customerDesignViewerModal').modal('show');
            });
        });
    </script>
@endpush
