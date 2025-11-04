@extends('erp.layouts.main')

@push('styles')
    <style>
        @media (max-width: 768px) {

            #inventoryTable td.desktop-only,
            #inventoryTable th.desktop-only {
                display: none !important;
            }
        }

        #inventoryTable {
            width: 100% !important;
            min-width: 0;
        }

        #inventoryTable_wrapper .dataTables_scrollBody {
            background-image: none !important;
            height: 60vh !important;
            overflow-y: auto !important;
        }

        .dataTables_scrollBody {
            scroll-behavior: smooth;
        }

        #inventoryTable tbody tr {
            animation: fadeIn 0.3s ease-in;
        }
    </style>
@endpush

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Stock Out</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Warehouse</li>
                <li class="breadcrumb-item">Stock Out</li>
            </ul>
        </div>
        <!-- <div class="page-header-right ms-auto">
                                        <div class="page-header-right-items">
                                            <div class="d-flex d-md-none">
                                                <a href="javascript:void(0)" class="page-header-right-close-toggle">
                                                    <i class="feather-arrow-left me-2"></i><span>Back</span>
                                                </a>
                                            </div>
                                            <div class="d-flex align-items-center gap-2 page-header-right-items-wrapper">
                                                <a href="/erp/inventory/stock-out/add-stock-out/" class="btn btn-primary">
                                                    <i class="feather-plus me-2"></i>
                                                    <span>Add Stock Out</span>
                                                </a>
                                            </div>
                                        </div>
                                        <div class="d-md-none d-flex align-items-center">
                                            <a href="javascript:void(0)" class="page-header-right-open-toggle">
                                                <i class="feather-align-right fs-20"></i>
                                            </a>
                                        </div>
                                    </div> -->
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
                                    <div class="col-lg-3">
                                        <label for="search_product" class="fw-semibold fs-12">Search Product</label>
                                        <input type="text" id="search_product" class="form-control"
                                            placeholder="Product name...">
                                    </div>
                                    <div class="col-lg-6">
                                        <label for="search_type" class="fw-semibold fs-12">Filter By</label>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <select id="search_type" class="form-control"
                                                    style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                                    <option value="invoice_number">Invoice / Order No</option>
                                                    <option value="partner">Supplier / Customer</option>
                                                    <option value="type">Transaction Type</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <input type="text" id="search_keyword" name="search_keyword"
                                                    class="form-control search-input"
                                                    style="padding: 0.5rem 1rem; font-size: 0.875rem;"
                                                    placeholder="Search..." />

                                                <select id="search_type_dropdown" class="form-control search-input d-none"
                                                    style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                                    <option value="">All</option>
                                                    <option value="sale">Sale Account</option>
                                                    <option value="purchase_return">Purchase Return</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover bg-transparent" id="inventoryTable">
                                <thead>
                                    <tr>
                                        <th class="wd-200">Number</th>
                                        <th class="wd-200">Date</th>
                                        <th>Stock Out</th>
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
    <div class="modal fade" id="modalDeleteStockOut" tabindex="-1" aria-labelledby="deleteModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" id="formDeleteStockOut">
                @csrf
                @method('DELETE')
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title text-white" id="deleteModalLabel">Hapus StockOut</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Apakah Anda yakin ingin menghapus StockOut <strong id="StockOutName"></strong>?</p>
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

    <div class="modal fade" id="modalAddStockOut" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form id="formAddStockOut" enctype="multipart/form-data">
                @csrf
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title text-white">Verifikasi Stock Out</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="fw-semibold">Tanggal Stock Out</label>
                            <input type="date" name="change_date" id="change_date" class="form-control"
                                value="{{ now()->format('Y-m-d') }}">
                        </div>
                        <div class="mb-3">
                            <label class="fw-semibold">Catatan</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Catatan optional..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="btnConfirmStockOut">Konfirmasi Stock
                            Out</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="modalResponsibility" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title text-white">Konfirmasi Tanggung Jawab</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Jika terjadi selisih, apakah Anda bersedia bertanggung jawab atas hasil Stock Out ini?</p>
                    <p class="text-muted mb-0">Pastikan data sudah sesuai sebelum melanjutkan.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-md" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-warning btn-md" id="btnConfirmResponsibility">Ya, Saya
                        Bertanggung Jawab</button>
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

            const dataTable = $('#inventoryTable').DataTable({
                processing: false,
                serverSide: false,
                scrollY: '60vh',
                scrollCollapse: true,
                paging: false,
                searching: false,
                info: false,
                lengthChange: false,
                order: [
                    [1, 'desc']
                ],
                data: [],
                columns: [
                    // {
                    //     data: 'DT_RowIndex',
                    //     orderable: false,
                    //     searchable: false
                    // },
                    {
                        data: 'transaction_number'
                    },
                    {
                        data: 'date'
                    },
                    {
                        data: 'stock_out'
                    },
                ]
            });

            function loadMoreData() {
                if (isLoading || !hasMoreData) return;
                isLoading = true;

                $.ajax({
                    url: "{{ url('/erp/inventory/stock-out/data') }}",
                    type: 'GET',
                    data: {
                        start: currentPage * 15,
                        length: 15,
                        filter: $('#filter').val(),
                        start_date: $('#start_date').val(),
                        end_date: $('#end_date').val(),
                        search_type: $('#search_type').val(),
                        search_keyword: $('#search_keyword').val(),
                        search_type_dropdown: $('#search_type_dropdown').val(),
                        progress_status: $('#progress_status').val(),
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
                if (selected === 'type') {
                    $('#search_keyword').addClass('d-none').val('');
                    $('#search_type_dropdown').removeClass('d-none');
                } else {
                    $('#search_keyword').removeClass('d-none');
                    $('#search_type_dropdown').addClass('d-none').val('');
                }
                resetAndReload();
            });

            let searchTimeout = null;
            $('#search_keyword, #search_type_dropdown, #search_product').on('keyup change input paste', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => resetAndReload(), 400);
            });

            $('#inventoryTable tbody').on('click', 'tr', function(e) {
                if ($(e.target).closest('td.dt-control').length) return;

                let $tr = $(this);
                let row = dataTable.row($tr);

                $('#inventoryTable tbody tr').removeClass('action-shown').next('.action-row').remove();

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
                if ($(e.target).closest('#inventoryTable').length) return;
                $('#inventoryTable tbody tr').removeClass('action-shown').next('.action-row').remove();
            });

            $(document).on('click', function(e) {
                if (!$(e.target).closest('#inventoryTable tbody tr, #inventoryTableMobile tbody tr')
                    .length) {
                    $('#inventoryTable tbody tr.shown, #inventoryTableMobile tbody tr.shown').each(
                        function() {
                            var tr = $(this);
                            var table = tr.closest('table').attr('id') === 'inventoryTable' ?
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

        $(document).ready(function() {
            let selectedInventoryId = null;
            let tempFormData = {};

            $(document).on('click', '.btn-open-stockout-modal', function() {
                selectedInventoryId = $(this).data('id');
                const number = $(this).data('number');
                $('#modalOrderNumber').text(number);
                $('#modalAddStockOut').modal('show');
            });

            $('#formAddStockOut').on('submit', function(e) {
                e.preventDefault();
                const form = $(this);

                tempFormData = {
                    _token: '{{ csrf_token() }}',
                    inventory_id: selectedInventoryId,
                    change_date: $('#change_date').val(),
                    notes: form.find('textarea[name="notes"]').val(),
                };

                $('#modalAddStockOut').modal('hide');
                setTimeout(() => {
                    $('#modalResponsibility').modal('show');
                }, 300);
            });

            $('#btnConfirmResponsibility').on('click', function() {
                $('#modalResponsibility').modal('hide');

                $.ajax({
                    url: `/erp/inventory/stock-out/store/${selectedInventoryId}`,
                    method: 'POST',
                    data: tempFormData,
                    success: function(response) {
                        Swal.close();
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: 'Stock Out berhasil diverifikasi.',
                        }).then(() => {
                            location.reload();
                        });
                    },
                    error: function(xhr) {
                        Swal.close();
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: xhr.responseJSON?.message ||
                                'Terjadi kesalahan saat membuat Stock Out.',
                        });
                    }
                });
            });
        });
    </script>
@endpush
