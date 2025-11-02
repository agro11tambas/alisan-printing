@extends('erp.layouts.main')

@push('styles')
    <style>
        @media (max-width: 768px) {

            #waitingListTable td.desktop-only,
            #waitingListTable th.desktop-only {
                display: none !important;
            }
        }

        #waitingListTable {
            width: 100% !important;
            min-width: 0;
        }

        #waitingListTable_wrapper .dataTables_scrollBody {
            background-image: none !important;
            height: 60vh !important;
            overflow-y: auto !important;
        }

        .dataTables_scrollBody {
            scroll-behavior: smooth;
        }

        #waitingListTable tbody tr {
            animation: fadeIn 0.3s ease-in;
        }
    </style>
@endpush

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Waiting List</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Production</li>
                <li class="breadcrumb-item">Waiting List</li>
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
                                <label for="" class="fw-semibold fs-12">Date</label>
                                <div class="d-flex align-items-center gap-2 page-header-right-items-wrapper">
                                    <div class="col-auto">
                                        <select id="filter" class="form-control"
                                            style="padding: 0.5rem 1rem; font-size: 0.875rem; width: 200px !important;">
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
                                        <input type="date" id="start_date" class="form-control"
                                            style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                    </div>
                                    <div class="col-auto custom-range d-none">
                                        <input type="date" id="end_date" class="form-control"
                                            style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                    </div>
                                    <div class="col-auto custom-range d-none">
                                        <button id="apply-filter" class="btn btn-primary">Apply</button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="row justify-content-end">
                                    <div class="col-lg-3">
                                        <label for="progress_status" class="fw-semibold fs-12">Progress Status</label>
                                        <select id="progress_status" class="form-control"
                                            style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                            <option value="progress">Progress</option>
                                            <option value="completed">Completed</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-6">
                                        <label for="search_type" class="fw-semibold fs-12">Filter By</label>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <select id="search_type" class="form-control"
                                                    style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                                    <option value="order_number">Order Number</option>
                                                    <option value="customer">Customer</option>
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
                        <div class="table-responsive">
                            <table class="table table-hover bg-transparent" id="waitingListTable">
                                <thead>
                                    <tr>
                                        {{-- <th class="wd-30">No</th> --}}
                                        <th class="wd-250">Order Number</th>
                                        <th class="wd-250">Customer</th>
                                        <th>Progress</th>
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
    <div class="modal fade" id="modalDeleteOrder" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" id="formDeleteOrder">
                @csrf
                @method('DELETE')
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title text-white" id="deleteModalLabel">Hapus Order</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Apakah Anda yakin ingin menghapus Order <strong id="OrderName"></strong>?</p>
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

    <div class="modal fade" id="modalChangeStatus" tabindex="-1" aria-labelledby="changeStatusModal"
        aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" id="formChangeStatus">
                @csrf
                @method('PUT')
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title text-white" id="changeStatusModal">Ubah Status</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Apakah Anda yakin ingin Mengubah Status Order <strong id="OrderName"></strong>?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-md" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-md">Change</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- 🖼️ Modal Image Viewer -->
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

    <div class="modal fade" id="modalPreviewDesign" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title text-white">Design Preview</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6 class="fw-bold mb-3" id="previewProductName"></h6>
                    <div id="previewImageContainer" class="d-flex flex-wrap gap-3 justify-content-start"></div>
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

            const dataTable = $('#waitingListTable').DataTable({
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
                columns: [
                    // {
                    //     data: 'DT_RowIndex',
                    //     orderable: false,
                    //     searchable: false
                    // },
                    {
                        data: 'invoice_number',
                        name: 'invoice_number'
                    },
                    {
                        data: 'customer',
                        name: 'customer'
                    },
                    {
                        data: 'progress',
                        name: 'progress'
                    },
                    {
                        data: 'id',
                        name: 'id',
                        visible: false,
                        searchable: false
                    }
                ],
            });

            function loadMoreData() {
                if (isLoading || !hasMoreData) return;
                isLoading = true;

                $.ajax({
                    url: "{{ url('/erp/productions/waiting-list/data') }}",
                    type: 'GET',
                    data: {
                        start: currentPage * 15,
                        length: 15,
                        filter: $('#filter').val(),
                        start_date: $('#start_date').val(),
                        end_date: $('#end_date').val(),
                        search_type: $('#search_type').val(),
                        search_keyword: $('#search_keyword').val(),
                        payment_status: $('#search_payment_status').val(),
                        progress_status: $('#progress_status').val(),
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
                        isLoading = false;
                    },
                    error: function(xhr) {
                        alert(xhr.responseJSON?.message || 'Error loading data.');
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

            $('#progress_status').on('change', function() {
                resetAndReload();
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

            $('#search_type').on('change', function() {
                const selected = $(this).val();
                if (selected === 'payment_status') {
                    $('#search_keyword').addClass('d-none').val('');
                } else {
                    $('#search_keyword').removeClass('d-none');
                }
                resetAndReload();
            });

            let searchTimeout = null;
            $('#search_keyword').on('keyup', function() {
                if ($('#search_type').val() !== 'payment_status') {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => resetAndReload(), 400);
                }
            });

            $('#waitingListTable tbody').on('click', 'tr', function(e) {
                if ($(e.target).closest('td.dt-control').length) return;

                let $tr = $(this);
                let row = dataTable.row($tr);

                $('#waitingListTable tbody tr').removeClass('action-shown').next('.action-row').remove();

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
                if ($(e.target).closest('#waitingListTable').length) return;
                $('#waitingListTable tbody tr').removeClass('action-shown').next('.action-row').remove();
            });

            $(document).on('click', function(e) {
                if (!$(e.target).closest('#waitingListTable tbody tr, #waitingListTableMobile tbody tr')
                    .length) {
                    $('#waitingListTable tbody tr.shown, #waitingListTableMobile tbody tr.shown').each(
                        function() {
                            var tr = $(this);
                            var table = tr.closest('table').attr('id') === 'waitingListTable' ?
                                dataTable : dataTableMobile;
                            var row = table.row(tr);
                            if (row.child.isShown()) {
                                row.child.hide();
                                tr.removeClass('shown');
                            }
                        });
                }
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('modalDeleteOrder');
            const form = document.getElementById('formDeleteOrder');
            const nameHolder = document.getElementById('OrderName');

            modal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-id');
                const name = button.getAttribute('data-name');
                const url = button.getAttribute('data-url');

                form.action = url;
                nameHolder.textContent = name;
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('modalChangeStatus');
            const form = document.getElementById('formChangeStatus');
            const nameHolder = document.getElementById('OrderName');

            modal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-id');
                const name = button.getAttribute('data-name');
                const url = button.getAttribute('data-url');

                form.action = url;
                nameHolder.textContent = name;
            });
        });

        $(document).on('click', '.img-viewer', function(e) {
            e.preventDefault();

            const imgSrc = $(this).data('src');
            const imgNote = $(this).data('note') || '-';

            $('#viewerImage').attr('src', imgSrc);
            $('#viewerNote').text(imgNote);
            $('#imageViewerModal').modal('show');
        });

        $(document).on('click', '.preview-btn', function() {
            const images = $(this).data('images');
            const product = $(this).data('product');
            const container = $('#previewImageContainer');

            $('#previewProductName').text(product);
            container.empty();

            if (Array.isArray(images) && images.length > 0) {
                images.forEach((img, i) => {
                    const fileUrl = img.file ? `/${img.file}`.replace(/\/{2,}/g, '/') : '';
                    const note = img.note || '-';
                    const html = `
                <div class="text-center border rounded p-2" style="max-width:250px;">
                    <img src="${fileUrl}" class="img-fluid rounded mb-2" 
                         style="width:180px;height:180px;object-fit:cover;cursor:pointer;"
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

        $(document).on('click', '#previewImageContainer img', function() {
            const src = $(this).data('full');
            window.open(src, '_blank');
        });
    </script>
@endpush
