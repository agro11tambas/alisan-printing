@extends('erp.layouts.main')

@push('styles')
    <style>
        #layerTable {
            width: 100% !important;
            min-width: 0;
        }

        #layerTable_wrapper .dataTables_scrollBody {
            background-image: none !important;
            height: 58vh !important;
            overflow-y: auto !important;
        }

        .hpp-summary-card {
            border-radius: 10px;
            padding: 10px 14px;
            background: rgba(13, 110, 253, .06);
        }

        .hpp-summary-card .label {
            font-size: 12px;
            color: #6b7280;
        }

        .hpp-summary-card .value {
            font-size: 18px;
            font-weight: 600;
        }

        .batch-habis {
            color: #9ca3af;
        }
    </style>
@endpush

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Batch Purchase (Snapshot)</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">HPP FIFO</li>
                <li class="breadcrumb-item active">Batch Purchase</li>
            </ul>
        </div>
        <div class="page-header-right ms-auto">
            <div class="page-header-right-items">
                <form action="{{ route('erp.hpp.rebuild') }}" method="POST"
                    onsubmit="return confirm('Hitung ulang seluruh HPP FIFO dari awal? Proses ini bisa memakan waktu pada data besar.')">
                    @csrf
                    <button type="submit" class="btn btn-light-brand">
                        <i class="feather-refresh-cw me-2"></i><span>Hitung Ulang HPP</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="main-content m-0 m-md-2 m-lg-2 p-0 p-md-0 p-lg-0 pt-1 pt-md-0">
        @if (session('success'))
            <div class="alert alert-success mx-2">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger mx-2">{{ session('error') }}</div>
        @endif

        <div class="row">
            <div class="col-lg-12">
                <div class="card stretch stretch-full">
                    <div class="card-body p-0">
                        <div class="row g-3 p-3">
                            <div class="col-lg-3">
                                <label for="product_name" class="fw-semibold fs-12">Produk / No. Purchase</label>
                                <input type="text" id="product_name" class="form-control"
                                    style="padding: 0.25rem 0.5rem; font-size: 0.875rem;" placeholder="Cari lalu Enter...">
                            </div>
                            <div class="col-lg-2">
                                <label for="start_date" class="fw-semibold fs-12">Tanggal Masuk Dari</label>
                                <input type="date" id="start_date" class="form-control"
                                    style="padding: 0.25rem 0.5rem; font-size: 0.875rem;">
                            </div>
                            <div class="col-lg-2">
                                <label for="end_date" class="fw-semibold fs-12">Sampai</label>
                                <input type="date" id="end_date" class="form-control"
                                    style="padding: 0.25rem 0.5rem; font-size: 0.875rem;">
                            </div>
                            <div class="col-lg-2 d-flex align-items-end">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="only_remaining">
                                    <label class="form-check-label fs-12" for="only_remaining">Hanya batch yang masih
                                        ada</label>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 px-3 pb-3">
                            <div class="col-lg-3">
                                <div class="hpp-summary-card">
                                    <div class="label">Jumlah Batch</div>
                                    <div class="value" id="sum_batches">0</div>
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="hpp-summary-card">
                                    <div class="label">Sisa Qty (satuan dasar)</div>
                                    <div class="value" id="sum_qty">0</div>
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="hpp-summary-card">
                                    <div class="label">Nilai Persediaan (FIFO)</div>
                                    <div class="value" id="sum_value">0</div>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover bg-transparent" id="layerTable">
                                <thead>
                                    <tr>
                                        <th>Produk</th>
                                        <th>SKU</th>
                                        <th>No. Purchase</th>
                                        <th>Sumber</th>
                                        <th>Tgl Masuk</th>
                                        <th class="text-end">Qty Masuk</th>
                                        <th class="text-end">Terpakai</th>
                                        <th class="text-end">Sisa</th>
                                        <th class="text-end">Modal / Satuan Dasar</th>
                                        <th class="text-end">Nilai Sisa</th>
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

            const table = $('#layerTable').DataTable({
                processing: false,
                serverSide: false,
                deferRender: true,
                scrollY: '58vh',
                scrollCollapse: true,
                paging: false,
                searching: false,
                lengthChange: false,
                info: false,

                // Urutan ditentukan server (tanggal batch masuk, terbaru dulu).
                // Tanpa ini DataTables mengurutkan kolom pertama secara alfabet
                // dan urutan tanggalnya hilang.
                ordering: false,

                data: [],
                columns: [{
                        data: 'product_name'
                    },
                    {
                        data: 'sku'
                    },
                    {
                        data: 'reference'
                    },
                    {
                        data: 'source'
                    },
                    {
                        data: 'layer_date'
                    },
                    {
                        data: 'qty_in',
                        className: 'text-end'
                    },
                    {
                        data: 'qty_used',
                        className: 'text-end'
                    },
                    {
                        data: 'qty_remaining',
                        className: 'text-end'
                    },
                    {
                        data: 'unit_cost',
                        className: 'text-end'
                    },
                    {
                        data: 'remaining_value',
                        className: 'text-end'
                    },
                ],
                createdRow: function(row, data) {
                    // Batch yang sudah habis diredupkan: masih penting sebagai
                    // jejak, tapi bukan stok yang sekarang dimiliki.
                    if (data.qty_remaining === '0') {
                        $(row).addClass('batch-habis');
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
                    url: "{{ url('/erp/hpp/batch-purchase/data') }}",
                    type: "GET",
                    data: {
                        start: currentPage * 50,
                        length: 50,
                        product_name: $("#product_name").val(),
                        start_date: $("#start_date").val(),
                        end_date: $("#end_date").val(),
                        only_remaining: $("#only_remaining").is(":checked") ? 1 : 0,
                    },
                    success: function(res) {
                        const rows = res.data ?? [];

                        if (res.summary) {
                            $("#sum_batches").text(res.summary.batches);
                            $("#sum_qty").text(res.summary.qty_remaining);
                            $("#sum_value").text(res.summary.value_remaining);
                        }

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

            $("#product_name").on("keydown", function(e) {
                if (e.key === "Enter") {
                    e.preventDefault();
                    loadData(true);
                }
            });

            $("#start_date, #end_date, #only_remaining").on("change", function() {
                loadData(true);
            });

            // Event scroll tidak menggelembung ke document, jadi handler harus
            // dipasang langsung ke elemen yang menggulir — bukan didelegasikan.
            $("#layerTable_wrapper .dataTables_scrollBody").on("scroll", function() {
                const el = $(this);

                if (el.scrollTop() + el.height() >= el[0].scrollHeight - 80) {
                    loadData();
                }
            });

            loadData(true);
        });
    </script>
@endpush
