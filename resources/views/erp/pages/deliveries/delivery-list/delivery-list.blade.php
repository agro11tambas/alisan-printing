@extends('erp.layouts.main')

@push('styles')
    <style>
        /* @media (max-width: 768px) {

                                                                            #deliveryListTable td.desktop-only,
                                                                            #deliveryListTable th.desktop-only {
                                                                                display: none !important;
                                                                            }
                                                                        } */

        #deliveryListTable {
            width: 100% !important;
            min-width: 0;
        }

        #deliveryListTable_wrapper .dataTables_scrollBody {
            background-image: none !important;
            height: 60vh !important;
            overflow-y: auto !important;
        }

        .dataTables_scrollBody {
            scroll-behavior: smooth;
        }

        #deliveryListTable tbody tr {
            animation: fadeIn 0.3s ease-in;
        }

        #deliveryListTable th.mobile-only,
        .dataTables_scrollHead thead th.mobile-only {
            display: none !important;
            visibility: hidden !important;
            width: 0 !important;
            height: 0 !important;
            padding: 0 !important;
            margin: 0 !important;
            border: none !important;
        }

        @media (max-width: 768px) {

            #deliveryListTable thead,
            .dataTables_scrollHead thead {
                display: none !important;
            }

            #deliveryListTable th.desktop-only,
            #deliveryListTable td.desktop-only,
            .dataTables_scrollHead thead th.desktop-only {
                display: none !important;
            }

            #deliveryListTable th.mobile-only,
            #deliveryListTable td.mobile-only {
                display: block !important;
                width: 100%;
                background: #fff;
                border: 1px solid #eee;
                border-radius: 6px;
                margin-bottom: 10px;
                padding: 10px;
            }
        }

        @media (min-width: 769px) {

            #deliveryListTable td.mobile-only,
            #deliveryListTable th.mobile-only {
                display: none !important;
            }

            #deliveryListTable td.desktop-only,
            #deliveryListTable th.desktop-only {
                display: table-cell !important;
            }
        }

        .static-action-menu {
            padding: 12px;
            min-width: 450px;
        }

        .action-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px 20px;
        }

        .action-col {
            display: flex;
            flex-direction: column;
        }

        .action-title {
            font-weight: 600;
            font-size: 13px;
            color: #6c757d;
            border-bottom: 1px solid #e9ecef;
            margin-bottom: 7px;
            padding-bottom: 4px;
        }

        .dropdown-item {
            font-size: 13px;
            padding: 6px 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        @media (max-width: 768px) {
            .action-grid {
                grid-template-columns: 1fr !important;
                gap: 10px;
            }

            .static-action-menu {
                min-width: 100% !important;
                padding: 10px;
            }
        }
    </style>
@endpush

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Delivery Order</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Deliveries</li>
                <li class="breadcrumb-item active">Delivery Order</li>
            </ul>
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
                                <label class="fw-semibold fs-12">Date</label>
                                <div class="d-flex align-items-center gap-2 page-header-right-items-wrapper">
                                    <div class="col-auto">
                                        <select id="filter" class="form-control" style="width: 200px !important;">
                                            <option value="all">All Time</option>
                                            <option value="yearly">Yearly</option>
                                            <option value="year_to_date">Year to Date</option>
                                            <option value="last_30_days">Last 30 Days</option>
                                            <option value="this_month">This Month</option>
                                            <option value="last_7_days">Last 7 Days</option>
                                            <option value="today">Today</option>
                                            <option value="custom">Custom Range</option>
                                        </select>
                                    </div>
                                    <div class="col-auto custom-range d-none">
                                        <input type="date" id="start_date" class="form-control">
                                    </div>
                                    <div class="col-auto custom-range d-none">
                                        <input type="date" id="end_date" class="form-control">
                                    </div>
                                    <div class="col-auto custom-range d-none">
                                        <button id="apply-filter" class="btn btn-primary">Apply</button>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <div class="row justify-content-end">
                                    <div class="col-lg-3">
                                        <label for="status" class="fw-semibold fs-12">Status</label>
                                        <select id="status" class="form-control">
                                            <option value="Ongoing">Ongoing</option>
                                            <option value="finished">Finished</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-3">
                                        <label for="search_product" class="fw-semibold fs-12">Search Product</label>
                                        <input type="text" id="search_product" class="form-control"
                                            placeholder="Product name...">
                                    </div>
                                    <div class="col-lg-6">
                                        <label for="search_type" class="fw-semibold fs-12">Filter By</label>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <select id="search_type" class="form-control">
                                                    <option value="customer">Customer</option>
                                                    <option value="shipment_number">Shipment Number</option>
                                                    <option value="driver">Driver</option>
                                                    <option value="vehicle">Vehicle</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <input type="text" id="search_keyword" class="form-control"
                                                    placeholder="Search..." />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover bg-transparent" id="deliveryListTable">
                                <thead>
                                    <tr>
                                        <th class="desktop-only wd-120">Shipment Number</th>
                                        <th class="desktop-only">Customer</th>
                                        <th class="desktop-only wd-250">Address</th>
                                        <th class="desktop-only">Driver</th>
                                        {{-- <th class="desktop-only">Status</th> --}}
                                        <th class="desktop-only">Items</th>
                                        <th class="mobile-only">Summary</th> <!-- ✅ tambahan -->
                                        <th class="desktop-only">Proof Photos</th>
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

@push('styles')
    <div class="modal fade" id="modalUploadProof" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" enctype="multipart/form-data" id="formUploadProof">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Upload Bukti Waybill & Pengantaran</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Ambil Foto Bukti (Surat Jalan & Pengantaran)</label>
                            <input type="file" id="proof_camera" class="form-control" accept="image/*"
                                capture="environment">
                            <div id="preview-container" class="mt-3 d-flex flex-wrap gap-2"></div>
                            <div class="invalid-feedback d-block text-danger" id="error-proof_photos"></div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-md" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-md">Upload</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="modalChangeStatus" tabindex="-1" aria-labelledby="changeStatusModal"
        aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" id="formChangeStatus">
                @csrf
                @method('PUT')
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title text-white" id="changeStatusModal">Verifikasi</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Apakah Anda yakin ingin memverifikasi Delivery List <strong id="DeliveryListName"></strong>?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-md" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-md">Verifikasi</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="modalDeleteDelivery" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" id="formDeleteDelivery">
                @csrf
                @method('DELETE')
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title text-white">Hapus Delivery List</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Apakah Anda yakin ingin menghapus Delivery List
                            <strong id="deleteDeliveryName"></strong>?
                        </p>
                        <p class="text-danger mb-0">
                            *Tindakan ini akan menghapus semua item di dalamnya secara permanen.
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-md" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger btn-md">Hapus</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="modalPreviewProof" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title">Proof Photos</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6 class="fw-bold mb-3" id="proofShipment"></h6>
                    <div id="proofPhotoContainer" class="d-flex flex-wrap gap-3 justify-content-start"></div>
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

            const dataTable = $('#deliveryListTable').DataTable({
                processing: false,
                serverSide: false,
                scrollY: '60vh',
                scrollCollapse: true,
                paging: false,
                searching: false,
                info: false,
                lengthChange: false,
                order: [
                    [7, 'desc']
                ],
                data: [],
                columns: [

                    {
                        data: 'shipment_number',
                        name: 'shipment_number',
                        className: 'desktop-only'
                    },
                    {
                        data: 'customer',
                        name: 'customer',
                        className: 'desktop-only'
                    },
                    {
                        data: 'address',
                        name: 'address',
                        className: 'desktop-only'
                    },
                    {
                        data: 'driver',
                        name: 'driver',
                        className: 'desktop-only'
                    },
                    // {
                    //     data: 'status',
                    //     name: 'status',
                    //     className: 'desktop-only'
                    // },
                    {
                        data: 'items',
                        name: 'items',
                        className: 'desktop-only'
                    },
                    {
                        data: 'items_mobile',
                        name: 'items_mobile',
                        className: 'mobile-only'
                    },
                    {
                        data: 'proof_photos',
                        name: 'proof_photos',
                        className: 'desktop-only'
                    },
                    {
                        data: 'id',
                        visible: false,
                        searchable: false
                    }
                ],
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
                    url: "{{ url('/erp/deliveries/delivery-list/data') }}",
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
                        if (response && response.data && response.data.length > 0) {
                            allData = allData.concat(response.data);
                            dataTable.clear();
                            dataTable.rows.add(allData).draw(false);
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
                            console.error('AJAX Error:', xhr);
                            alert(xhr.responseJSON?.message || 'Gagal memuat data.');
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
                dataTable.clear().draw();
                loadMoreData();
            }

            // ==========================================================
            // 🔹 FIXED FILTER HANDLER (no reload on custom range)
            // ==========================================================

            // Kalau dropdown filter berubah
            $('#filter').on('change', function() {
                const val = $(this).val();

                // Kalau custom range → tampilkan input tanggal, tapi jangan reload
                if (val === 'custom') {
                    $('.custom-range').removeClass('d-none');
                    return;
                }

                // Selain custom → sembunyikan input tanggal dan reload
                $('.custom-range').addClass('d-none');
                resetAndReload();
            });

            // Tombol Apply Filter → reload manual untuk custom range
            $('#apply-filter').on('click', function() {
                resetAndReload();
            });

            // Filter lain tetap auto reload
            $('#status, #search_type, #search_keyword, #search_product, #start_date, #end_date')
                .on('change keyup input paste', function() {
                    clearTimeout(searchTimer);
                    searchTimer = setTimeout(() => resetAndReload(), 200);
                });


            $('#deliveryListTable tbody').on('click', 'tr', function(e) {
                if ($(e.target).closest('td.dt-control').length) return;
                let $tr = $(this);
                let row = dataTable.row($tr);

                $('#deliveryListTable tbody tr').removeClass('action-shown').next('.action-row').remove();

                if ($tr.hasClass('action-shown')) {
                    $tr.removeClass('action-shown');
                } else {
                    let actionHtml = row.data().action;
                    let colCount = $tr.find('td').length;
                    let $actionRow = $(`
                <tr class="action-row">
                    <td colspan="${colCount}">
                        <div class="d-flex justify-content-start">
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
                if ($(e.target).closest('#deliveryListTable').length) return;
                $('#deliveryListTable tbody tr').removeClass('action-shown').next('.action-row').remove();
            });

            $(document).on('click', '.btn-upload-proof', function() {
                const url = $(this).data('url');
                const id = $(this).data('id');
                const existingPhotosRaw = $(this).attr('data-photos');
                let existingPhotos = [];

                photoFiles = [];
                $('#preview-container').html('');

                $('#formUploadProof').attr('action', url);
                $('#formUploadProof')[0].reset();
                $('#error-proof_photos').text('');
                $('#proof-preview').html('');


                if (existingPhotosRaw && existingPhotosRaw !== '[]') {
                    try {
                        existingPhotos = JSON.parse(existingPhotosRaw);
                    } catch (err) {}
                }

                if (Array.isArray(existingPhotos) && existingPhotos.length > 0) {
                    let html = '';
                    existingPhotos.forEach((img) => {
                        const imgSrc = img.startsWith('http') ?
                            img :
                            `${window.location.origin}/${img.replace(/^\/+/, '')}`;
                        html += `
                    <a href="${imgSrc}" data-lightbox="proof-${id}" data-title="Preview Bukti">
                        <img src="${imgSrc}" width="100" height="80"
                            style="border-radius:8px;object-fit:cover;border:1px solid #ddd;margin:3px;">
                    </a>`;
                    });
                    $('#proof-preview').html(html);
                } else {
                    $('#proof-preview').html('<p class="text-muted">Belum ada bukti yang diupload.</p>');
                }

                $('#modalUploadProof').modal('show');
            });

            $('#formUploadProof').on('submit', function(e) {
                let valid = true;
                $('#error-proof_waybill, #error-proof_delivery').text('');

                const waybill = $('input[name="proof_waybill"]').val();
                const delivery = $('input[name="proof_delivery"]').val();

                if (!waybill) {
                    valid = false;
                    $('#error-proof_waybill').text('Bukti surat jalan wajib diupload.');
                }
                if (!delivery) {
                    valid = false;
                    $('#error-proof_delivery').text('Bukti pengantaran wajib diupload.');
                }

                if (!valid) e.preventDefault();
            });

            $(document).on('click', '.btn-verify', function() {
                let name = $(this).data('name');
                let url = $(this).data('url');

                $('#DeliveryListName').text(name);
                $('#formChangeStatus').attr('action', url);
                $('#modalChangeStatus').modal('show');
            });

            $(document).on('click', '.btn-delete-delivery', function() {
                const name = $(this).data('name');
                const url = $(this).data('url');

                $('#deleteDeliveryName').text(name);
                $('#formDeleteDelivery').attr('action', url);
                $('#modalDeleteDelivery').modal('show');
            });
        });

        function compressImage(file, quality = 0.7) {
            return new Promise((resolve) => {
                const reader = new FileReader();
                reader.readAsDataURL(file);
                reader.onload = event => {
                    const img = new Image();
                    img.src = event.target.result;
                    img.onload = () => {
                        const canvas = document.createElement('canvas');
                        const ctx = canvas.getContext('2d');
                        const MAX_WIDTH = 1280;
                        const scaleSize = MAX_WIDTH / img.width;
                        canvas.width = MAX_WIDTH;
                        canvas.height = img.height * scaleSize;
                        ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                        canvas.toBlob(
                            (blob) => resolve(new File([blob], file.name, {
                                type: 'image/jpeg'
                            })),
                            'image/jpeg',
                            quality
                        );
                    };
                };
            });
        }

        let photoFiles = [];

        $(document).on('change', '#proof_camera', function(e) {
            const file = e.target.files[0];
            if (!file) return;

            photoFiles.push(file);

            const reader = new FileReader();
            reader.onload = ev => {
                $('#preview-container').append(`
            <div class="position-relative d-inline-block m-1">
                <img src="${ev.target.result}" 
                     class="img-thumbnail" 
                     style="width: 100px; height: 80px; object-fit: cover; border-radius: 6px;">
            </div>
        `);
            };
            reader.readAsDataURL(file);

            e.target.value = '';
        });

        document.getElementById('formUploadProof').addEventListener('submit', async function(e) {
            e.preventDefault();

            if (photoFiles.length === 0) {
                alert('Silakan ambil minimal 1 foto bukti.');
                return;
            }

            const formData = new FormData(this);

            for (const file of photoFiles) {
                const compressed = await compressImage(file, 0.6);
                formData.append('proof_photos[]', compressed, file.name);
            }

            const url = this.getAttribute('action');
            const response = await fetch(url, {
                method: 'POST',
                body: formData
            });

            if (response.ok) {
                Swal.fire('Berhasil!', 'Foto bukti berhasil diupload.', 'success').then(() => location
                    .reload());
            } else {
                Swal.fire('Gagal!', 'Terjadi kesalahan saat upload.', 'error');
            }
        });

        $(document).on('click', '.btn-preview-proof', function() {
            const photos = $(this).data('photos');
            const shipment = $(this).data('shipment');
            const container = $('#proofPhotoContainer');

            $('#proofShipment').text(`Shipment: ${shipment}`);
            container.empty();

            if (Array.isArray(photos) && photos.length > 0) {
                photos.forEach((path, idx) => {
                    const src = path.startsWith('http') ? path :
                        `${window.location.origin}/${path.replace(/^\/+/, '')}`;
                    const html = `
                <div class="text-center border rounded p-2" style="max-width:240px;">
                    <img src="${src}" 
                        class="img-fluid rounded mb-2 proof-image" 
                        style="width:180px;height:180px;object-fit:cover;cursor:pointer;"
                        data-full="${src}" alt="Proof ${idx + 1}">
                    <p class="small text-muted mb-0">Photo ${idx + 1}</p>
                </div>
            `;
                    container.append(html);
                });
            } else {
                container.html('<p class="text-muted">No proof images available.</p>');
            }

            $('#modalPreviewProof').modal('show');
        });

        $(document).on('click', '.proof-image', function() {
            const src = $(this).data('full');
            window.open(src, '_blank');
        });
    </script>
@endpush
