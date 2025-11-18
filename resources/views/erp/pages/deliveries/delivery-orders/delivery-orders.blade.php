@extends('erp.layouts.main')

@push('styles')
    <style>
        @media (max-width: 768px) {

            #deliveryOrderTable td.desktop-only,
            #deliveryOrderTable th.desktop-only {
                display: none !important;
            }
        }

        #deliveryOrderTable {
            width: 100% !important;
            min-width: 0;
        }

        #deliveryOrderTable_wrapper .dataTables_scrollBody {
            background-image: none !important;
            height: 60vh !important;
            overflow-y: auto !important;
        }

        .dataTables_scrollBody {
            scroll-behavior: smooth;
        }

        #deliveryOrderTable tbody tr {
            animation: fadeIn 0.3s ease-in;
        }
    </style>
@endpush

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Delivery List</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Deliveries</li>
                <li class="breadcrumb-item">Delivery List</li>
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
                                        <label for="status" class="fw-semibold fs-12">DO Status</label>
                                        <select id="status" class="form-control">
                                            <option value="Ongoing">Ongoing</option>
                                            {{-- <option value="Shipped">Shipped</option> --}}
                                            <option value="Finished">Finished</option>
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
                                                    <option value="delivery_number">Delivery Number</option>
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
                            <table class="table table-hover bg-transparent table-hover" id="deliveryOrderTable">
                                <thead>
                                    <tr>
                                        <th class="wd-250">Delivery Number</th>
                                        <th class="wd-250">Customer</th>
                                        <th>Products</th>
                                        <th>Order Notes</th>
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
    <div class="modal fade" id="modalDeleteDO" tabindex="-1" aria-labelledby="deleteDOModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" id="formDeleteDO">
                @csrf
                @method('DELETE')
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">Hapus Delivery Order</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Apakah Anda yakin ingin menghapus Delivery Order <strong id="DOName"></strong>?</p>
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

    <div class="modal fade-scale" id="modalAddDeliveryList" tabindex="-1" aria-labelledby="modalAddDeliveryListLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="d-flex flex-column mb-0">
                        <span class="fs-18 fw-bold mb-1">Add Delivery List</span>
                    </h2>
                    <a href="javascript:void(0)" class="avatar-text avatar-md bg-soft-danger close-icon"
                        data-bs-dismiss="modal">
                        <i class="feather-x text-danger"></i>
                    </a>
                </div>

                <form method="POST" id="formAddDeliveryList">
                    @csrf
                    <input type="hidden" id="delivery_order_id" name="delivery_order_id">

                    <div class="modal-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="fw-semibold">Shipment Number</label>
                                <input type="text" class="form-control" id="shipment_number" name="shipment_number"
                                    required>
                            </div>
                            <div class="col-md-6">
                                <label class="fw-semibold">Shipment Date</label>
                                <input type="date" class="form-control" name="shipment_date"
                                    value="{{ date('Y-m-d') }}" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="fw-semibold">Driver</label>
                                <input type="text" class="form-control" name="driver" required>
                            </div>
                            <div class="col-md-6">
                                <label class="fw-semibold">Vehicle</label>
                                <input type="text" class="form-control" name="vehicle" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-12">
                                <label class="fw-semibold">Items</label>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>Ordered Qty</th>
                                                <th>Already Shipped</th>
                                                <th>Shipped Qty (Now)</th>
                                                <th>Note</th>
                                            </tr>
                                        </thead>
                                        <tbody id="delivery_items_body">
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-12">
                                <label class="fw-semibold">Note</label>
                                <textarea class="form-control" name="note" rows="2" placeholder="Optional note..."></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer d-flex justify-content-between">
                        <button type="button" class="btn btn-secondary btn-md" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-md">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {

            // ========== VARIABEL UNTUK LAZY LOAD ==========
            let allData = [];
            let currentPage = 0;
            let isLoading = false;
            let hasMoreData = true;

            // ========== INISIALISASI DATATABLE ==========
            const dataTable = $('#deliveryOrderTable').DataTable({
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
                    // { data: 'DT_RowIndex', orderable: false, searchable: false },
                    {
                        data: 'delivery_number',
                        name: 'delivery_number'
                    },
                    {
                        data: 'customer',
                        name: 'customer'
                    },
                    {
                        data: 'products',
                        name: 'products'
                    },
                    {
                        data: 'order_notes',
                        name: 'order_notes'
                    },
                    {
                        data: 'created_at',
                        name: 'created_at',
                        visible: false,
                        searchable: false
                    },
                    // { data: 'action', orderable: false, searchable: false }
                ],
            });

            let searchTimer = null;
            let currentRequest = null;

            function loadMoreData() {
                if (isLoading || !hasMoreData) return;
                isLoading = true;

                // 🚫 Batalkan request sebelumnya jika masih berjalan
                if (currentRequest) {
                    currentRequest.abort();
                }

                currentRequest = $.ajax({
                    url: "{{ url('/erp/deliveries/delivery-orders/data') }}",
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
                            alert(xhr.responseJSON?.message || 'Error loading data.');
                        }
                        isLoading = false;
                    }
                });
            }

            // ========== LOAD DATA PERTAMA ==========
            loadMoreData();

            // ========== EVENT SCROLL UNTUK LAZY LOAD ==========
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

            // ========== RESET & RELOAD ==========
            function resetAndReload() {
                allData = [];
                currentPage = 0;
                hasMoreData = true;
                dataTable.clear().draw();
                loadMoreData();
            }

            // ========== SEMUA EVENT LAMA TETAP, HANYA PANGGIL resetAndReload() ==========
            $('#status').on('change', function() {
                resetAndReload();
            });

            // ==========================================================
            // 🔹 FIXED FILTER HANDLER (no reload on custom range)
            // ==========================================================

            // Kalau dropdown filter berubah
            $('#filter').on('change', function() {
                const val = $(this).val();

                // Kalau custom range → tampilkan input tanggal tapi jangan reload
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


            // ========= ACTION ROW (TIDAK DIUBAH) ==========
            $('#deliveryOrderTable tbody').on('click', 'tr', function(e) {
                if ($(e.target).closest('td.dt-control').length) return;

                let $tr = $(this);
                let row = dataTable.row($tr);

                $('#deliveryOrderTable tbody tr').removeClass('action-shown').next('.action-row').remove();

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

            // ========== CLOSE ACTION ROW SAAT KLIK DI LUAR ==========
            $(document).on('click', function(e) {
                if ($(e.target).closest('#deliveryOrderTable').length) return;
                $('#deliveryOrderTable tbody tr').removeClass('action-shown').next('.action-row').remove();
            });
        });


        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('modalDeleteDO');
            const form = document.getElementById('formDeleteDO');
            const nameHolder = document.getElementById('DOName');

            modal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const name = button.getAttribute('data-name');
                const url = button.getAttribute('data-url');

                form.action = url;
                nameHolder.textContent = name;
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('modalAddDeliveryList');
            const form = document.getElementById('formAddDeliveryList');
            const inputDOId = document.getElementById('delivery_order_id');

            modal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const doId = button.getAttribute('data-id');
                const url = button.getAttribute('data-url');

                inputDOId.value = doId;
                form.action = url;
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('modalAddDeliveryList');
            const form = document.getElementById('formAddDeliveryList');
            const inputDOId = document.getElementById('delivery_order_id');
            const shipmentNumberInput = document.getElementById('shipment_number');
            const itemsBody = document.getElementById('delivery_items_body');

            modal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const doId = button.getAttribute('data-id');
                const url = button.getAttribute('data-url');

                inputDOId.value = doId;
                form.action = url;

                fetch(`/erp/deliveries/delivery-list/generate-number/${doId}`)
                    .then(res => res.json())
                    .then(data => {
                        shipmentNumberInput.value = data.number;
                    });

                fetch(`/erp/deliveries/delivery-orders/${doId}/items`)
                    .then(res => res.json())
                    .then(data => {
                        itemsBody.innerHTML = '';
                        data.items.forEach(item => {
                            itemsBody.innerHTML += `
                <tr>
                    <td>${item.product_name}
                        <input type="hidden" name="items[${item.id}][product_id]" value="${item.product_id}">
                        <input type="hidden" name="items[${item.id}][delivery_order_item_id]" value="${item.id}">
                    </td>
                    <td>${item.ordered_qty}</td>
                    <td>${item.already_shipped}</td>
                    <td>
                        <input type="number" class="form-control"
                            name="items[${item.id}][shipped_quantity]"
                            min="0"
                            max="${item.ordered_qty - item.already_shipped}"
                            value="0">
                    </td>
                    <td>
                        <input type="text" class="form-control" name="items[${item.id}][note]" placeholder="Note">
                    </td>
                </tr>
            `;
                        });
                    });

            });
        });
    </script>
@endpush
