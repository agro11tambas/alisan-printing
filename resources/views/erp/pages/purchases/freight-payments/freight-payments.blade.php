@extends('erp.layouts.main')

@push('styles')
    <style>
        @media (max-width: 768px) {

            #freightPaymentTable td.desktop-only,
            #freightPaymentTable th.desktop-only {
                display: none !important;
            }
        }

        #freightPaymentTable {
            width: 100% !important;
            min-width: 0;
        }

        #freightPaymentTable_wrapper .dataTables_scrollBody {
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

        #freightPaymentTable tbody tr {
            animation: fadeIn 0.3s ease-in;
        }

        .static-action-menu {
            padding: 6px;
            min-width: 900px;
        }

        .action-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px 20px;
        }

        /* Layar sempit-sedang: 4 kolom jadi terlalu padat, dijadikan 2. */
        @media (max-width: 1200px) {
            .static-action-menu {
                min-width: 520px;
            }

            .action-grid {
                grid-template-columns: repeat(2, 1fr);
            }
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
            .static-action-menu {
                min-width: 0;
            }

            .action-grid {
                grid-template-columns: 1fr;
            }
        }

        .preview-list {
            display: block;
        }

        .preview-item {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            margin-bottom: 1rem;
            width: 100%;
        }

        .preview-item img {
            width: 100%;
            height: auto;
            border-radius: 6px;
            margin-bottom: 6px;
            object-fit: cover;
        }

        .preview-item input.note-input {
            width: 100%;
        }

        @include('erp.pages.partials.transaction-list-mobile-header-styles')
    </style>
@endpush

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Freight Payment</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item"><a href="/erp/purchases/purchase-list">Purchase List</a></li>
                <li class="breadcrumb-item">Freight Payment</li>
            </ul>
        </div>
        <div class="page-header-right ms-auto">
            <div class="page-header-right-items transaction-list-desktop-actions">
                <div class="d-flex d-md-none">
                    <a href="javascript:void(0)" class="page-header-right-close-toggle">
                        <i class="feather-arrow-left me-2"></i><span>Back</span>
                    </a>
                </div>
                <div class="d-flex align-items-center gap-2 page-header-right-items-wrapper">
                    <a href="/erp/purchases/purchase-list" class="btn btn-light-brand">
                        <i class="feather-arrow-left me-2"></i>
                        <span>Back to Purchase List</span>
                    </a>
                </div>
            </div>
            <div class="d-md-none d-flex align-items-center">
                <a href="/erp/purchases/purchase-list" class="btn btn-light-brand transaction-list-mobile-action">
                    <i class="feather-arrow-left"></i>
                    <span>Purchase List</span>
                </a>
            </div>
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
                            <div class="col-lg-4 me-2">
                                <label for="filter" class="fw-semibold fs-12">Date</label>
                                <div class="d-flex align-items-center gap-2 page-header-right-items-wrapper">
                                    <div class="col-auto">
                                        <select id="filter" class="form-control"
                                            style="padding: 0.25rem 0.5rem; font-size: 0.875rem; width: 200px !important;">
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
                                            style="padding: 0.25rem 0.5rem; font-size: 0.875rem;">
                                    </div>
                                    <div class="col-auto custom-range d-none">
                                        <input type="date" id="end_date" class="form-control"
                                            style="padding: 0.25rem 0.5rem; font-size: 0.875rem;">
                                    </div>
                                    <div class="col-auto custom-range d-none">
                                        <button id="apply-filter" class="btn btn-primary">Apply</button>
                                    </div>
                                </div>
                            </div>
                            {{--
                                Filter status, menempel di kiri "Filter By".

                                ms-auto-nya yang bikin menempel: baris ini memakai
                                justify-content-between, jadi tanpa itu sisa ruangnya
                                dibagi rata dan Status melayang di tengah-tengah,
                                jauh dari Filter By.

                                Tidak ada pilihan "semua" -- halaman ini daftar
                                kerjaan, jadi Progress sekaligus jadi keadaan awalnya.
                            --}}
                            <div class="col-lg-2 ms-auto">
                                <label for="filter_payment_status" class="fw-semibold fs-12">Status</label>
                                <select id="filter_payment_status" class="form-control"
                                    style="padding: 0.25rem 0.5rem; font-size: 0.875rem;">
                                    <option value="Progress" selected>Progress</option>
                                    <option value="Completed">Completed</option>
                                </select>
                            </div>
                            <div class="col-lg-4">
                                <label for="search_type" class="fw-semibold fs-12">Filter By</label>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <select id="search_type" class="form-control"
                                            style="padding: 0.25rem 0.5rem; font-size: 0.875rem;">
                                            <option value="waybill">Nomor Surat Jalan</option>
                                            <option value="supplier">Supplier</option>
                                            <option value="invoice">Invoice</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="text" id="search_keyword" name="search_keyword"
                                            class="form-control search-input"
                                            style="padding: 0.25rem 0.5rem; font-size: 0.875rem;" placeholder="Search..." />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover bg-transparent" id="freightPaymentTable">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th>No. Surat Jalan</th>
                                        <th>Stock In Date</th>
                                        <th>Supplier</th>
                                        <th class="desktop-only">Invoice</th>
                                        <th>Freight Total</th>
                                        <th>Freight Paid</th>
                                        <th>Remaining</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('modals')
    <div class="modal fade-scale" id="modalFreightPayment" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="fw-bold mb-0">Mark as Paid (Freight)</h5>
                    <a href="javascript:void(0)" class="avatar-text avatar-md bg-soft-danger close-icon"
                        data-bs-dismiss="modal">
                        <i class="feather-x text-danger"></i>
                    </a>
                </div>
                <form method="POST" id="markAsPaidFormFreight" enctype="multipart/form-data"
                    action="{{ url('/erp/purchases/freight-payments/mark-as-paid') }}">
                    @csrf
                    <input type="hidden" id="freight_waybill_key" name="waybill_key">
                    <div class="modal-body">
                        <div class="row g-3 mb-2">
                            <div class="col-md-6">
                                <label class="fw-semibold">Nomor Surat Jalan:</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="freight_waybill_label" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="fw-semibold">Supplier:</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="freight_supplier_label" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="row g-3 mb-2">
                            <div class="col-md-6">
                                <label for="transaction_type_freight" class="fw-semibold">Purchase Account:</label>
                                <div class="input-group">
                                    <select class="form-select" id="transaction_type_freight"
                                        data-select2-selector="tag" name="transaction_type">
                                        @foreach ($transactionTypes as $type)
                                            <option value="{{ $type->id }}" data-bg="bg-danger">
                                                {{ $type->type ?? $type->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <small class="text-danger d-none" id="error_transaction_type_freight"></small>
                            </div>
                            <div class="col-md-6">
                                <label for="transaction_date_freight" class="fw-semibold">Transaction Date:</label>
                                <div class="input-group">
                                    <input type="date" id="transaction_date_freight" name="transaction_date"
                                        class="form-control" value="{{ date('Y-m-d') }}">
                                </div>
                                <small class="text-danger d-none" id="error_transaction_date_freight"></small>
                            </div>
                        </div>
                        <div class="row g-3 mb-2">
                            <div class="col-md-6">
                                <label for="cash_bank_account_id_freight" class="fw-semibold">Cash/Bank Account:</label>
                                <div class="input-group">
                                    @php
                                        $bgColors = ['bg-danger', 'bg-warning', 'bg-primary', 'bg-indigo', 'bg-success'];
                                    @endphp
                                    <select class="form-select" id="cash_bank_account_id_freight"
                                        data-select2-selector="tag" name="cash_bank_account_id">
                                        <option value="" disabled {{ !$defaultAccount ? 'selected' : '' }} hidden>
                                            Pilih Cash/Bank Account</option>

                                        @foreach ($cashAccounts as $cash)
                                            @php $bg = $bgColors[$loop->index % count($bgColors)]; @endphp
                                            <option value="{{ $cash->id }}" data-bg="{{ $bg }}"
                                                {{ isset($defaultAccount) && $defaultAccount->id == $cash->id ? 'selected' : '' }}>
                                                Cash - {{ $cash->type }}
                                            </option>
                                        @endforeach

                                        @foreach ($bankAccounts as $bank)
                                            @php $bg = $bgColors[$loop->index % count($bgColors)]; @endphp
                                            <option value="{{ $bank->id }}" data-bg="{{ $bg }}"
                                                {{ isset($defaultAccount) && $defaultAccount->id == $bank->id ? 'selected' : '' }}>
                                                Bank - {{ $bank->type }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="paid_amount_freight" class="fw-semibold">Paid Amount:</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="paid_amount_freight"
                                        name="paid_amount" value="0">
                                </div>
                                <small class="text-danger d-none" id="error_paid_amount_freight"></small>
                                <span class="fw-semibold fs-12" id="paid_amount_display_freight">Paid: Rp. 0</span>
                            </div>
                        </div>
                        <div class="row g-3 mb-2">
                            <div class="col-md-12">
                                <label for="note_freight" class="fw-semibold">Note (optional):</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="note_freight" name="note">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <label class="fw-semibold">Upload / Paste Proof (optional):</label>

                            <div id="pasteProofAreaFreight" class="border rounded p-2 text-center"
                                style="min-height: 120px; cursor: pointer;">
                                <p class="text-muted small mb-1">
                                    Klik di sini lalu tekan <strong>Ctrl + V</strong> untuk paste screenshot bukti transfer
                                </p>

                                <div id="proofPreviewContainerFreight" class="preview-list"></div>
                            </div>

                            <small class="text-danger d-none" id="error_freight_payment_proof"></small>
                        </div>

                    </div>
                    <div class="modal-footer d-flex justify-content-between">
                        <div>
                            <p class="m-0">Balance:</p>
                            <h5 class="fw-semibold text-danger" id="total_amount_display_freight">0</h5>
                        </div>
                        <button type="submit" class="btn btn-primary">Mark as Paid</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endpush

@push('modals')
    {{-- Foto Surat Jalan & Bukti Penerimaan Barang dari Stock In. --}}
    <div class="modal fade-scale" id="modalFreightDocuments" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="fw-bold mb-0">Foto Dokumen Stock In</h5>
                        <small class="text-muted" id="freightDocsSubtitle"></small>
                    </div>
                    <a href="javascript:void(0)" class="avatar-text avatar-md bg-soft-danger close-icon"
                        data-bs-dismiss="modal">
                        <i class="feather-x text-danger"></i>
                    </a>
                </div>
                <div class="modal-body">
                    <div id="freightDocsBody">
                        <div class="text-center text-muted py-4">
                            <div class="spinner-border spinner-border-sm me-2"></div> Memuat foto…
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <small class="text-muted me-auto">
                        Klik foto untuk memperbesar. Fotonya milik Stock In — untuk menggantinya, ubah dari halaman
                        Stock In.
                    </small>
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
@endpush
@push('scripts')
    <script>
        $(document).ready(function() {
            const fmt = new Intl.NumberFormat('id-ID');

            // Rincian per produk di balik satu surat jalan. Dimuat begitu barisnya
            // dibuka, bukan ikut dikirim di setiap halaman tabel, supaya query
            // daftarnya tetap ringan.
            const detailCache = {};

            function formatItems(items) {
                if (!items || items.length === 0) {
                    return '<div class="p-1 text-muted">Tidak ada rincian freight</div>';
                }

                let html = `
                    <div class="table-responsive p-1">
                        <table class="table bg-transparent table-sm table-bordered mb-0 w-auto">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>SKU</th>
                                    <th>Invoice</th>
                                    <th>Stock In Date</th>
                                    <th class="text-end">Stock In</th>
                                    <th class="text-end">Freight / Unit</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                items.forEach(item => {
                    html += `
                                <tr>
                                    <td>${item.product}</td>
                                    <td>${item.sku}</td>
                                    <td>${item.invoice_number}</td>
                                    <td>${item.change_date}</td>
                                    <td class="text-end">${item.qty} <small class="text-muted">(${item.qty_base})</small></td>
                                    <td class="text-end">${item.freight}</td>
                                    <td class="text-end">${item.subtotal}</td>
                                </tr>
                    `;
                });

                html += `
                            </tbody>
                        </table>
                    </div>
                `;
                return html;
            }

            function loadDetail(waybillKey) {
                if (detailCache[waybillKey]) {
                    return $.Deferred().resolve(detailCache[waybillKey]).promise();
                }

                return $.get("{{ url('/erp/purchases/freight-payments/detail') }}", {
                    waybill_key: waybillKey
                }).then(function(res) {
                    detailCache[waybillKey] = res;
                    return res;
                });
            }

            if ($.fn.DataTable.isDataTable('#freightPaymentTable')) {
                $('#freightPaymentTable').DataTable().clear().destroy();
            }

            const dataTable = $('#freightPaymentTable').DataTable({
                processing: true,
                serverSide: true,
                deferRender: true,
                scrollY: '60vh',
                scrollCollapse: true,
                ordering: false,
                searching: false,
                lengthChange: false,
                info: false,
                pageLength: 25,
                pagingType: 'simple',
                ajax: {
                    url: "{{ url('/erp/purchases/freight-payments/data') }}",
                    data: function(d) {
                        d.filter = $('#filter').val();
                        d.start_date = $('#start_date').val();
                        d.end_date = $('#end_date').val();
                        d.search_type = $('#search_type').val();
                        d.search_keyword = $('#search_keyword').val();
                        d.payment_status = $('#filter_payment_status').val();
                    }
                },
                columns: [{
                        className: 'dt-control text-center',
                        orderable: false,
                        data: null,
                        defaultContent: '<i class="feather feather-plus"></i>',
                        width: '20px'
                    },
                    {
                        data: 'waybill_number'
                    },
                    {
                        data: 'change_date'
                    },
                    {
                        data: 'supplier_name'
                    },
                    {
                        data: 'invoice_numbers',
                        className: 'desktop-only'
                    },
                    {
                        data: 'total_freight'
                    },
                    {
                        data: 'paid_amount'
                    },
                    {
                        data: 'remaining_amount'
                    },
                    {
                        data: 'status'
                    }
                ]
            });

            function reloadTable() {
                dataTable.ajax.reload();
            }

            // ================= FILTER (pola sama dengan Purchase List) =================
            $('#filter').on('change', function() {
                if ($(this).val() === 'custom') {
                    $('.custom-range').removeClass('d-none');
                } else {
                    $('.custom-range').addClass('d-none');
                    $('#start_date').val('');
                    $('#end_date').val('');
                }

                reloadTable();
            });

            $('#apply-filter').on('click', function() {
                reloadTable();
            });

            $('#search_type').on('change', reloadTable);

            $('#filter_payment_status').on('change', reloadTable);

            $('#search_keyword').on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    reloadTable();
                }
            });

            $('#search_keyword').on('input', function() {
                if ($(this).val().trim() === '') {
                    reloadTable();
                }
            });

            // ================= EXPAND RINCIAN + MENU AKSI =================
            $('#freightPaymentTable tbody').on('click', 'td.dt-control', function() {
                const tr = $(this).closest('tr');
                const row = dataTable.row(tr);
                const icon = $(this).find('i');

                if (row.child.isShown()) {
                    row.child.hide();
                    tr.removeClass('shown');
                    icon.removeClass('feather-minus').addClass('feather-plus');
                    return;
                }

                row.child('<div class="p-2 text-muted">Memuat rincian...</div>').show();
                tr.addClass('shown');
                icon.removeClass('feather-plus').addClass('feather-minus');

                loadDetail(row.data().waybill_key).then(function(res) {
                    if (row.child.isShown()) {
                        row.child(formatItems(res.items)).show();
                    }
                });
            });

            $('#freightPaymentTable tbody').on('click', 'tr', function(e) {
                if ($(e.target).closest('td.dt-control').length) return;
                if ($(this).hasClass('action-row')) return;

                const $tr = $(this);
                const row = dataTable.row($tr);

                if (!row.data()) return;

                $('#freightPaymentTable tbody tr').removeClass('action-shown').next('.action-row').remove();

                if ($tr.hasClass('action-shown')) {
                    $tr.removeClass('action-shown');
                    return;
                }

                const colCount = $tr.find('td').length;
                const $actionRow = $(`
                    <tr class="action-row">
                        <td colspan="${colCount}">
                            <div class="d-flex justify-content-center">
                                ${row.data().action}
                            </div>
                        </td>
                    </tr>
                `);

                $tr.after($actionRow);
                $tr.addClass('action-shown');
            });

            $(document).on('click', function(e) {
                if ($(e.target).closest('#freightPaymentTable').length) return;
                if ($(e.target).closest('.modal').length) return;
                $('#freightPaymentTable tbody tr').removeClass('action-shown').next('.action-row').remove();
            });

            // ================= FOTO DOKUMEN STOCK IN =================
            const documentCache = {};

            function documentCard(doc, group) {
                const sources = (doc.sources || []).join('<br>');

                // PDF tidak bisa jadi thumbnail; dibuatkan kartu bericon yang
                // membuka file aslinya di tab baru.
                const media = doc.is_pdf
                    ? `
                        <a href="${doc.url}" target="_blank" rel="noopener"
                            class="d-flex flex-column align-items-center justify-content-center text-decoration-none"
                            style="aspect-ratio:4/3;background:#f5f5f5;border-radius:6px;">
                            <i class="feather-file-text" style="font-size:28px"></i>
                            <small class="mt-1">Buka PDF</small>
                        </a>
                    `
                    : `
                        <a href="${doc.url}" data-lightbox="${group}">
                            <div style="aspect-ratio:4/3;overflow:hidden;border-radius:6px;background:#f5f5f5;">
                                <img src="${doc.url}" alt="Foto dokumen"
                                    style="width:100%;height:100%;object-fit:cover;display:block;">
                            </div>
                        </a>
                    `;

                return `
                    <div class="col-6 col-md-4">
                        ${media}
                        <div class="fs-11 text-muted mt-1">${sources}</div>
                    </div>
                `;
            }

            function documentSection(title, docs, group) {
                if (!docs || docs.length === 0) {
                    return `
                        <div class="mb-3">
                            <div class="fw-semibold mb-2">${title}</div>
                            <div class="text-muted fs-12">Tidak ada foto.</div>
                        </div>
                    `;
                }

                return `
                    <div class="mb-3">
                        <div class="fw-semibold mb-2">${title} <span class="text-muted fw-normal">(${docs.length})</span></div>
                        <div class="row g-2">
                            ${docs.map(doc => documentCard(doc, group)).join('')}
                        </div>
                    </div>
                `;
            }

            $(document).on('click', '.btn-freight-documents', function() {
                const data = $(this).data();
                const key = data.waybillKey;

                $('#freightDocsSubtitle').text(`${data.waybillNumber} — ${data.supplier}`);
                $('#freightDocsBody').html(
                    '<div class="text-center text-muted py-4"><div class="spinner-border spinner-border-sm me-2"></div> Memuat foto…</div>'
                );

                $('#freightPaymentTable tbody tr').removeClass('action-shown').next('.action-row').remove();
                $('#modalFreightDocuments').modal('show');

                const request = documentCache[key]
                    ? $.Deferred().resolve(documentCache[key]).promise()
                    : $.get("{{ url('/erp/purchases/freight-payments/documents') }}", {
                        waybill_key: key
                    }).then(function(res) {
                        documentCache[key] = res;
                        return res;
                    });

                request.done(function(res) {
                    const total = (res.waybill || []).length + (res.receipt || []).length;

                    if (total === 0) {
                        $('#freightDocsBody').html(
                            '<div class="text-center text-muted py-4">Stock In di balik surat jalan ini belum punya foto dokumen.</div>'
                        );
                        return;
                    }

                    $('#freightDocsBody').html(
                        documentSection('Surat Jalan', res.waybill, 'freight-waybill-' + key) +
                        documentSection('Bukti Penerimaan Barang', res.receipt, 'freight-receipt-' + key)
                    );
                }).fail(function() {
                    $('#freightDocsBody').html(
                        '<div class="text-center text-danger py-4">Gagal memuat foto dokumen.</div>'
                    );
                });
            });

TTXT_PLACEHOLDER            // ================= MARK AS PAID =================
            $(document).on('click', '.btn-mark-paid-freight', function() {
                const data = $(this).data();
                const remaining = parseFloat(data.remaining) || 0;
                const paid = parseFloat(data.paid) || 0;

                $('#freight_waybill_key').val(data.waybillKey);
                $('#freight_waybill_label').val(data.waybillNumber);
                $('#freight_supplier_label').val(data.supplier);
                $('#total_amount_display_freight').text(fmt.format(remaining));
                $('#paid_amount_freight').val(fmt.format(remaining));
                $('#paid_amount_display_freight').text('Paid: Rp. ' + fmt.format(paid));
                $('#note_freight').val('');
                $('#proofPreviewContainerFreight').html('');
                pastedProofFreightBlobs = [];

                $('#freightPaymentTable tbody tr').removeClass('action-shown').next('.action-row').remove();
                $('#modalFreightPayment').modal('show');
            });

            // Format ribuan + label "Paid", sama seperti modal Purchase List.
            $('#paid_amount_freight').on('input', function() {
                const angka = this.value.replace(/\D/g, '') || '0';
                this.value = fmt.format(angka);
            });

            // ================= PASTE / UPLOAD BUKTI =================
            let pastedProofFreightBlobs = [];
            const pasteAreaFreight = document.getElementById('pasteProofAreaFreight');
            const previewContainerFreight = document.getElementById('proofPreviewContainerFreight');

            if (pasteAreaFreight) {
                pasteAreaFreight.setAttribute('tabindex', '0');

                pasteAreaFreight.addEventListener('click', (e) => {
                    if (e.target === pasteAreaFreight) {
                        pasteAreaFreight.focus();
                    }
                });

                pasteAreaFreight.addEventListener('paste', (e) => {
                    // Paste di kolom catatan dibiarkan normal; selain itu diambil gambarnya.
                    if (e.target.classList.contains('note-input')) {
                        return;
                    }

                    e.preventDefault();

                    for (const item of e.clipboardData.items) {
                        if (item.type.indexOf('image') === 0) {
                            const blob = item.getAsFile();
                            pastedProofFreightBlobs.push(blob);

                            const reader = new FileReader();
                            reader.onload = function(event) {
                                const wrapper = document.createElement('div');
                                wrapper.classList.add('preview-item', 'mb-1');

                                const img = document.createElement('img');
                                img.src = event.target.result;
                                img.classList.add('img-thumbnail');
                                img.style.maxHeight = '150px';
                                img.style.marginBottom = '5px';

                                const noteInput = document.createElement('input');
                                noteInput.type = 'text';
                                noteInput.classList.add('form-control', 'form-control-sm',
                                    'note-input');
                                noteInput.placeholder = 'Tambahkan catatan...';

                                const removeBtn = document.createElement('button');
                                removeBtn.type = 'button';
                                removeBtn.className = 'btn btn-sm btn-danger mt-1';
                                removeBtn.innerHTML = '<i class="feather-x"></i> Hapus';
                                removeBtn.onclick = function() {
                                    const index = Array.from(previewContainerFreight.children)
                                        .indexOf(wrapper);
                                    pastedProofFreightBlobs.splice(index, 1);
                                    wrapper.remove();
                                };

                                wrapper.appendChild(img);
                                wrapper.appendChild(noteInput);
                                wrapper.appendChild(removeBtn);
                                previewContainerFreight.appendChild(wrapper);
                            };
                            reader.readAsDataURL(blob);
                        }
                    }
                });
            }

            $('#markAsPaidFormFreight').on('submit', function(e) {
                e.preventDefault();

                const form = this;
                const formData = new FormData(form);

                const notes = [];
                $('#proofPreviewContainerFreight .note-input').each(function() {
                    notes.push($(this).val());
                });

                pastedProofFreightBlobs.forEach((blob, index) => {
                    formData.append(`payment_proof[${index}]`, blob, `proof_${index + 1}.png`);
                    formData.append(`note_per_image[${index}]`, notes[index] || '');
                });

                formData.set('paid_amount', parseFloat($('#paid_amount_freight').val().replace(/\./g, '')) ||
                    0);

                $.ajax({
                    url: form.action,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(res) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: res.message ?? 'Pembayaran freight berhasil disimpan!',
                            timer: 1800,
                            showConfirmButton: false
                        });

                        $('#modalFreightPayment').modal('hide');
                        form.reset();
                        pastedProofFreightBlobs = [];
                        $('#proofPreviewContainerFreight').html('');

                        delete detailCache[$('#freight_waybill_key').val()];
                        dataTable.ajax.reload(null, false);
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: xhr.responseJSON?.message ?? 'Terjadi kesalahan saat menyimpan'
                        });
                    }
                });
            });
        });
    </script>
@endpush
