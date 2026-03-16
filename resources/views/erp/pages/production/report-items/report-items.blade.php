@extends('erp.layouts.main')

@push('styles')
    <style>
        @media (max-width: 768px) {

            #reportItemsTable td.desktop-only,
            #reportItemsTable th.desktop-only {
                display: none !important;
            }
        }

        #reportItemsTable {
            width: 100% !important;
            min-width: 0;
        }

        #reportItemsTable_wrapper .dataTables_scrollBody {
            /* background: #fff !important; */
            background-image: none !important;
            height: 60vh !important;
            overflow-y: auto !important;
        }
    </style>
@endpush

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Report Items</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Production</li>
                <li class="breadcrumb-item active">Report Items</li>
            </ul>
        </div>
        <div class="page-header-right ms-auto">
            <div class="page-header-right-items">
                <div class="d-flex d-md-none">
                    <a href="javascript:void(0)" class="page-header-right-close-toggle">
                        <i class="feather-arrow-left me-2"></i><span>Back</span>
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
    <div class="main-content m-0 m-md-2 m-lg-2 p-0 p-md-0 p-lg-0 pt-2 pt-md-0">
        <div class="row">
            <div class="col-lg-12">
                <div class="card stretch stretch-full">
                    <div class="card-body p-0">
                        <div class="row g-3 p-4 justify-content-between">
                            <div class="col-lg-4 me-2">
                                <div class="row g-3 justify-content-strart">
                                    <div class="col-lg-6">
                                        <label for="product_name" class="fw-semibold fs-12">Item Name</label>
                                        <input type="text" id="product_name" name="product_name" class="form-control"
                                            style="padding: 0.5rem 1rem; font-size: 0.875rem;" placeholder="Search Item...">
                                    </div>
                                </div>
                                <div class="mt-2 d-flex align-items-center gap-2">
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" role="switch" id="dailyToggle">
                                        <label class="form-check-label fs-12 fw-semibold" for="dailyToggle">Daily
                                            Columns</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="d-flex justify-content-end align-items-center p-3">
                                    <button id="btnRequestStock" class="btn btn-primary d-none">
                                        <i class="feather-plus me-2"></i>Request Stock
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover bg-transparent" id="reportItemsTable">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th>Item Name</th>
                                        <!-- <th>Purchase Stock</th> -->
                                        <th>Current Stock</th>
                                        <th>Waiting List</th>
                                        <th>Assign</th>
                                        <th>Finished Products</th>
                                        <th>On Delivery</th>
                                        <th>Incoming Stock</th>
                                        <th>OP Today</th>
                                        <th>CLS Today</th>
                                        <th>Assign Today</th>
                                        <th>Action</th>
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
    <div class="modal fade" id="defectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="defectForm" method="POST" action="{{ url('/erp/defect-product/store-production') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Add Product Defect</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="product_id" id="defect_product_id">
                        <div class="mb-3">
                            <label class="form-label">Product Name</label>
                            <input type="text" id="defect_product_name" class="form-control" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Defect Quantity</label>
                            <input type="number" name="quantity" id="defect_quantity" class="form-control" min="1"
                                required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Note (optional)</label>
                            <textarea name="note" id="defect_note" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Save Defect</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {
            $("#product_name").closest("form").on("submit", function(e) {
                e.preventDefault();
            });

            let allData = [];
            let currentPage = 0;
            let isLoading = false;
            let hasMoreData = true;
            let lastKeyword = '';

            // 🟩 simpan ID produk yang sudah dicentang
            let selectedProducts = [];

            let table = $('#reportItemsTable').DataTable({
                processing: false,
                serverSide: false,
                deferRender: true,
                scrollY: '60vh',
                scrollCollapse: true,
                paging: false,
                searching: false,
                lengthChange: false,
                info: false,
                // order: [
                //     [3, 'desc']
                // ],
                data: [],
                columns: [{
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row) {
                            // 🟩 jika produk sudah dicentang sebelumnya, tandai checked
                            const checked = selectedProducts.includes(row.product_id.toString()) ?
                                'checked' : '';
                            return `<input type="checkbox" class="row-checkbox" value="${row.product_id}" ${checked}>`;
                        },
                    },
                    {
                        data: 'name'
                    },
                    {
                        data: 'available_quantity'
                    },
                    {
                        data: 'pending_waiting_list'
                    },
                    {
                        data: 'assigned_minus_completed'
                    },
                    {
                        data: 'finished_product_stock'
                    },
                    {
                        data: 'on_delivery'
                    },
                    {
                        data: 'incoming_stock'
                    },
                    {
                        data: 'opening_stock_today'
                    },
                    {
                        data: 'closing_stock_today'
                    },
                    {
                        data: 'assign_today'
                    },
                    {
                        data: 'action',
                        orderable: false,
                        searchable: false
                    },
                ],
            });

            let searchTimer = null;
            let currentRequest = null;

            function loadMoreData(reset = false) {
                if (isLoading) return;
                if (!hasMoreData && !reset) return;
                isLoading = true;

                if (reset) {
                    allData = [];
                    currentPage = 0;
                    hasMoreData = true;
                    table.clear().draw();
                }

                // Batalkan request sebelumnya jika masih jalan
                if (currentRequest) {
                    currentRequest.abort();
                }

                currentRequest = $.ajax({
                    url: "{{ url('/erp/productions/report-items/data') }}",
                    type: "GET",
                    data: {
                        start: currentPage * 200,
                        length: 200,
                        product_name: $("#product_name").val(),
                    },
                    success: function(res) {
                        // 🔥 FIX UTAMA: ambil rows dari DataTables server response
                        const rows = res.data ?? [];

                        if (rows.length > 0) {
                            if (reset) allData = [];

                            allData = allData.concat(rows);

                            table.clear();
                            table.rows.add(allData).draw(false);

                            currentPage++;
                            hasMoreData = true;
                            restoreCheckboxState();
                        } else {
                            hasMoreData = false;
                        }
                    },

                    complete: function() {
                        isLoading = false;
                        currentRequest = null;
                    },
                    error: function(xhr) {
                        if (xhr.statusText !== "abort") {
                            console.error("AJAX error", xhr);
                        }
                        isLoading = false;
                    },
                });
            }

            loadMoreData();

            const allColIndexes = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
            const dailyColIndexes = [1, 8, 9, 10]; // OP Today, CLS Today, Assign Today
            const otherColIndexes = allColIndexes.filter(i => !dailyColIndexes.includes(i));

            $("#dailyToggle").on("change", function() {
                const dailyOnly = $(this).is(":checked");

                if (dailyOnly) {
                    // ON: sembunyiin kolom non-daily, tampilkan daily
                    otherColIndexes.forEach(i => table.column(i).visible(false));
                    dailyColIndexes.forEach(i => table.column(i).visible(true));
                } else {
                    // OFF: tampilkan semua
                    allColIndexes.forEach(i => table.column(i).visible(true));
                }
            });

            let scrollTimeout = null;
            $(".dataTables_scrollBody").on("scroll", function() {
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

            // $("#product_name").on("keyup change", function() {
            //     clearTimeout(searchTimer);
            //     searchTimer = setTimeout(() => {
            //         const keyword = $(this).val().trim();
            //         if (keyword !== lastKeyword) {
            //             lastKeyword = keyword;
            //             loadMoreData(true); // reset total
            //         }
            //     }, 200); // debounce biar gak spam
            // });
            $("#product_name").on("keydown", function(e) {
                if (e.key === "Enter") {
                    e.preventDefault();
                    lastKeyword = $("#product_name").val().trim();
                    loadMoreData(true);
                }
            });

            // 🟩 checkbox listener pakai delegated event biar tetap aktif
            $(document).on("change", ".row-checkbox", function() {
                const id = $(this).val();
                if ($(this).is(":checked")) {
                    if (!selectedProducts.includes(id)) selectedProducts.push(id);
                } else {
                    selectedProducts = selectedProducts.filter((x) => x !== id);
                }
                toggleRequestButton();
            });

            $("#selectAll").on("change", function() {
                const checked = $(this).prop("checked");
                $(".row-checkbox").prop("checked", checked).trigger("change");
            });

            function toggleRequestButton() {
                if (selectedProducts.length > 0) {
                    $("#btnRequestStock").removeClass("d-none");
                } else {
                    $("#btnRequestStock").addClass("d-none");
                }
            }

            function restoreCheckboxState() {
                $(".row-checkbox").each(function() {
                    const id = $(this).val();
                    if (selectedProducts.includes(id)) {
                        $(this).prop("checked", true);
                    }
                });
                toggleRequestButton();
            }

            // 🟩 fix tombol request stock
            $(document).on("click", "#btnRequestStock", function() {
                if (selectedProducts.length === 0) {
                    Swal.fire("Peringatan", "Pilih minimal 1 produk untuk request stock.", "warning");
                    return;
                }

                const url = "{{ url('/erp/productions/material-request/create') }}" + "?products=" +
                    selectedProducts.join(",");
                window.location.href = url;
            });

            $(document).on('click', '#reportItemsTable tbody tr', function(e) {
                // Hindari klik langsung di tombol / link di kolom aksi
                if ($(e.target).is('button, a, i, input')) return;

                const checkbox = $(this).find('.row-checkbox');
                if (checkbox.length > 0) {
                    const currentState = checkbox.prop('checked');
                    checkbox.prop('checked', !currentState).trigger('change');
                }
            });
        });

        // modal defect tetap sama
        $(document).on("click", ".btnDefect", function() {
            const id = $(this).data("product-id");
            const name = $(this).data("name");

            $("#defect_product_id").val(id);
            $("#defect_product_name").val(name);
            $("#defect_quantity").val("");
            $("#defect_note").val("");

            $("#defectModal").modal("show");
        });

        $("#defectForm").on("submit", function(e) {
            e.preventDefault();

            $.ajax({
                url: $(this).attr("action"),
                type: "POST",
                data: $(this).serialize(),
                success: function(res) {
                    $("#defectModal").modal("hide");
                    Swal.fire({
                        icon: "success",
                        title: "Defect saved!",
                        text: res.message || "Defect product successfully recorded.",
                    });
                    $("#reportItemsTable").DataTable().ajax.reload();
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: xhr.responseJSON?.message || "Failed to save defect product.",
                    });
                },
            });
        });
    </script>
@endpush
