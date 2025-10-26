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

                        {{-- Table --}}
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
        <div class="modal-dialog modal-dialog-centered">
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
                            <label for="preview_image" class="form-label">Select Image</label>
                            <input type="file" class="form-control" id="preview_image" name="preview_image"
                                accept="image/*" required>
                        </div>

                        <div class="mb-3">
                            <label for="note" class="form-label">Note</label>
                            <textarea class="form-control" name="note" id="note" rows="3" placeholder="Add a note..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Upload</button>
                    </div>
                </div>
            </form>
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
@endpush



@push('scripts')
    <script>
        $(document).ready(function() {
            const table = $('#designListTable').DataTable({
                processing: true,
                serverSide: true,
                deferRender: true,
                scrollY: 600,
                scroller: true,
                paging: true,
                searching: false,
                lengthChange: false,
                info: false,
                ajax: {
                    url: "{{ url('/erp/design/data') }}",
                    data: function(d) {
                        d.filter = $('#filter').val();
                        d.start_date = $('#start_date').val();
                        d.end_date = $('#end_date').val();
                        d.search_type = $('#search_type').val();
                        d.search_keyword = $('#search_keyword').val();
                        d.status = $('#status').val();
                    }
                },
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
                ]
            });

            // 🔥 BARIS ACTION FIXED
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

            // klik luar tabel = tutup action
            $(document).on('click', function(e) {
                if ($(e.target).closest('#designListTable').length) return;
                $('#designListTable tbody tr').removeClass('action-shown').next('.action-row').remove();
            });

            // 🔎 FILTERS
            $('#filter').on('change', function() {
                if ($(this).val() === 'custom') $('.custom-range').removeClass('d-none');
                else {
                    $('.custom-range').addClass('d-none');
                    table.ajax.reload();
                }
            });
            $('#apply-filter, #status').on('click change', function() {
                table.ajax.reload();
            });
            $('#search_keyword').on('keyup', function() {
                table.ajax.reload();
            });

            // 🔥🔥🔥 KEMBALIKAN BAGIAN INI — TIDAK DIHAPUS 🔥🔥🔥

            // 👉 Saat tombol Upload diklik
            $(document).on('click', '.upload-btn', function() {
                const id = $(this).data('id');
                const previewUrl = $(this).data('preview');
                const note = $(this).data('note');

                // Set ID ke input hidden
                $('#design_item_id').val(id);

                // Isi note lama (kalau ada)
                $('#note').val(note || '');

                // Reset input file (biar bersih)
                $('#preview_image').val('');

                // Kalau ada gambar lama, tampilkan di modal
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
                    // hapus preview lama kalau tidak ada
                    previewContainer.remove();
                }
            });

            // 👉 submit upload form via ajax
            $('#uploadForm').on('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);

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
                        location.reload();
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

            // 👉 verify design modal
            $(document).on('click', '.btn-verify', function() {
                let id = $(this).data('id');
                let name = $(this).data('name');
                let url = $(this).data('url');

                $('#DesignName').text(name);
                $('#formVerifyDesign').attr('action', url);
            });

            // 👉 submit verify form
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
                        table.ajax.reload(null, false);
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
        });
    </script>
@endpush
