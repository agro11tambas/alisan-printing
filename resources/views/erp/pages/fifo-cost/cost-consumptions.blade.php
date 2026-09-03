@extends('erp.layouts.main')

@push('styles')
    <style>
        #consumptionTable {
            width: 100% !important;
            min-width: 0;
        }

        #consumptionTable_wrapper .dataTables_scrollBody {
            background-image: none !important;
            height: 62vh !important;
            overflow-y: auto !important;
        }

        .badge-taksiran {
            background: #fef3c7;
            color: #92400e;
            padding: 1px 6px;
            border-radius: 6px;
            font-size: 11px;
        }

        .baris-retur {
            color: #b91c1c;
        }
    </style>
@endpush

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Rincian HPP Penjualan</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">HPP FIFO</li>
                <li class="breadcrumb-item active">Rincian HPP</li>
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
                        <div class="p-3 pb-0">
                            <p class="fs-12 text-muted mb-0">
                                Tiap baris di bawah adalah satu batch pembelian yang dimakan sebuah penjualan.
                                Satu invoice bisa punya beberapa baris kalau qty-nya menghabiskan lebih dari satu batch —
                                di situlah harga modal di export Sale List berasal. Baris bertanda Retur mengembalikan
                                barang ke batch asalnya.
                            </p>
                        </div>

                        <div class="row g-3 p-3">
                            <div class="col-lg-3">
                                <label for="keyword" class="fw-semibold fs-12">Invoice / Produk / No. Purchase</label>
                                <input type="text" id="keyword" class="form-control"
                                    style="padding: 0.25rem 0.5rem; font-size: 0.875rem;" placeholder="Cari lalu Enter...">
                            </div>
                            <div class="col-lg-2">
                                <label for="start_date" class="fw-semibold fs-12">Tgl Order Dari</label>
                                <input type="date" id="start_date" class="form-control"
                                    style="padding: 0.25rem 0.5rem; font-size: 0.875rem;">
                            </div>
                            <div class="col-lg-2">
                                <label for="end_date" class="fw-semibold fs-12">Sampai</label>
                                <input type="date" id="end_date" class="form-control"
                                    style="padding: 0.25rem 0.5rem; font-size: 0.875rem;">
                            </div>
                            <div class="col-lg-3 d-flex align-items-end">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="only_estimated">
                                    <label class="form-check-label fs-12" for="only_estimated">
                                        Hanya yang harganya taksiran
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover bg-transparent" id="consumptionTable">
                                <thead>
                                    <tr>
                                        <th>Invoice</th>
                                        <th>Tgl Order</th>
                                        <th>Produk</th>
                                        <th>Jenis</th>
                                        <th>Batch Asal</th>
                                        <th class="text-end">Qty</th>
                                        <th class="text-end">Modal / Satuan Dasar</th>
                                        <th class="text-end">Total Modal</th>
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

@push('scripts')
    <script>
        $(document).ready(function() {
            let allData = [];
            let currentPage = 0;
            let isLoading = false;
            let hasMoreData = true;
            let currentRequest = null;

            const table = $('#consumptionTable').DataTable({
                processing: false,
                serverSide: false,
                deferRender: true,
                scrollY: '62vh',
                scrollCollapse: true,
                paging: false,
                searching: false,
                lengthChange: false,
                info: false,

                // Urutan ditentukan server (tanggal order, terbaru dulu). Tanpa
                // ini DataTables memakai default-nya sendiri — mengurutkan kolom
                // pertama secara alfabet — sehingga barisnya tampak acak.
                // Mengurutkan di browser juga menyesatkan karena yang dimuat
                // baru sebagian, bukan seluruh data.
                ordering: false,

                data: [],
                columns: [{
                        data: 'order_number'
                    },
                    {
                        data: 'order_date'
                    },
                    {
                        data: 'product_name'
                    },
                    {
                        data: 'type'
                    },
                    {
                        data: 'batch',
                        render: function(value, type, row) {
                            if (type !== 'display') return value;

                            return row.is_estimated ?
                                value + ' <span class="badge-taksiran">taksiran</span>' :
                                value;
                        },
                    },
                    {
                        data: 'qty',
                        className: 'text-end'
                    },
                    {
                        data: 'unit_cost',
                        className: 'text-end'
                    },
                    {
                        data: 'subtotal',
                        className: 'text-end'
                    },
                ],
                createdRow: function(row, data) {
                    if (data.type.startsWith('Retur')) {
                        $(row).addClass('baris-retur');
                    }
                },
            });

            function loadData(reset = false) {
                if (isLoading) return;
                if (!hasMoreData && !reset) return;
                isLoading = true;

                if (reset) {
                    allData = [];
                    currentPage = 0;
                    hasMoreData = true;
                    table.clear().draw();
                }

                if (currentRequest) currentRequest.abort();

                currentRequest = $.ajax({
                    url: "{{ url('/erp/hpp/rincian/data') }}",
                    type: "GET",
                    data: {
                        start: currentPage * 50,
                        length: 50,
                        keyword: $("#keyword").val(),
                        start_date: $("#start_date").val(),
                        end_date: $("#end_date").val(),
                        only_estimated: $("#only_estimated").is(":checked") ? 1 : 0,
                    },
                    success: function(res) {
                        const rows = res.data ?? [];

                        if (rows.length > 0) {
                            allData = allData.concat(rows);
                            table.clear();
                            table.rows.add(allData).draw(false);
                            currentPage++;
                            hasMoreData = Boolean(res.has_more);
                        } else {
                            hasMoreData = false;
                        }
                    },
                    complete: function() {
                        isLoading = false;
                        currentRequest = null;
                    },
                    error: function(xhr) {
                        if (xhr.statusText !== "abort") console.error("AJAX error", xhr);
                        isLoading = false;
                    },
                });
            }

            $("#keyword").on("keydown", function(e) {
                if (e.key === "Enter") {
                    e.preventDefault();
                    loadData(true);
                }
            });

            $("#start_date, #end_date, #only_estimated").on("change", function() {
                loadData(true);
            });

            // Event scroll tidak menggelembung ke document, jadi handler harus
            // dipasang langsung ke elemen yang menggulir — bukan didelegasikan.
            $("#consumptionTable_wrapper .dataTables_scrollBody").on("scroll", function() {
                const el = $(this);

                if (el.scrollTop() + el.height() >= el[0].scrollHeight - 80) {
                    loadData();
                }
            });

            loadData(true);
        });
    </script>
@endpush
