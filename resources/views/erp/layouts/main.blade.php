<!DOCTYPE html>
<html lang="zxx">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="keyword" content="">
    <meta name="author" content="WRAPCODERS">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Alisan</title>

    <link rel="shortcut icon" type="image/x-icon" href="#">

    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/bootstrap.min.css') }}">

    <link rel="stylesheet" type="text/css" href="{{ asset('assets/vendors/css/vendors.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/vendors/css/dataTables.bs5.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/vendors/css/tagify.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/vendors/css/tagify-data.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/vendors/css/select2.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/vendors/css/select2-theme.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/vendors/css/jquery.steps.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/vendors/css/quill.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/vendors/css/datepicker.min.css') }}">

    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/theme.min.css') }}">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css" rel="stylesheet">

    <style>
        div.dataTables_wrapper .row:first-child {
            margin: 0 !important;
            padding: 0 !important;
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

    <style>
        #invoiceContent {
            width: 768px;
            max-width: 768px;
            margin: 0 auto;
            font-size: 14px;
            background: #fff;
        }

        @media (max-width: 768px) {
            #invoiceContent {
                width: 100% !important;
                max-width: 100% !important;
                font-size: 12px;
                padding: 10px;
            }

            #invoiceContent table th,
            #invoiceContent table td {
                font-size: 12px;
                padding: 4px;
            }

            .page-header-title h5 {
                font-size: 14px;
            }

            .fs-24 {
                font-size: 18px;
            }

            .fs-4 {
                font-size: 16px;
            }

            .fs-16 {
                font-size: 13px;
            }
        }
    </style>

    <style>
        .select2-container,
        .select2-dropdown,
        .select2-container--open {
            z-index: 2050 !important;
        }
    </style>

    <style>
        /* 🎯 Style khusus untuk tabel dengan class .table-small */
        .table-small {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            /* font lebih kecil */
        }

        .table-small th,
        .table-small td {
            padding: 5px 10px !important;
            /* jarak antar sel kecil */
            vertical-align: middle;
            border: 1px solid #dee2e6;
        }

        .table-small thead th {
            background-color: #f8f9fa;
            /* sedikit abu-abu biar beda */
            font-weight: 600;
            white-space: nowrap;
        }

        .table-small tbody tr:hover {
            background-color: #f1f3f5;
            /* efek hover ringan */
        }

        /* biar rapi saat ditampilkan dalam DataTables juga */
        .dataTables_wrapper .table-small tbody td {
            padding: 5px 10px !important;
        }
    </style>

    <style>
        .main-content {
            padding-bottom: 0px !important;
        }

        /* Warna highlight baris aktif */
        tr.action-active {
            background-color: #f2f4f7 !important;
            /* abu-abu lembut */
            transition: background-color 0.2s ease-in-out;
        }

        /* opsional: ubah warna teks biar tetap kontras */
        tr.action-active td {
            color: #212529;
        }


        .select2-container .select2-selection__rendered {
            white-space: normal !important;
            /* agar teks bisa turun ke baris berikutnya */
            word-break: break-word !important;
            /* potong kata panjang */
            line-height: 1.3 !important;
            /* biar tinggi baris rapi */
            min-height: 38px !important;
            /* sesuaikan dengan tinggi select normal */
            padding: 4px 8px !important;
        }

        .select2-results__option {
            white-space: normal !important;
            word-break: break-word !important;
        }

        .page-header.sticky-top,
        .page-header {
            z-index: 2500 !important;
            /* lebih tinggi dari 2050 */
        }

        /* Jika ada overlay navbar atau topbar juga */
        .nxl-header,
        .topbar,
        .navbar,
        .page-header-title {
            z-index: 2060 !important;
        }

        .modal {
            z-index: 3000 !important;
        }
    </style>

    @stack('styles')

</head>

<body>

    @include('erp.layouts.components.sidebar')

    @include('erp.layouts.components.topbar')

    @yield('account')
    <main class="nxl-container apps-container">
        <div class="nxl-content" style="min-height: 50vh;">
            @yield('breadcrumb')
            @yield('content')
        </div>
        {{-- @include('erp.layouts.components.footer') --}}
    </main>

    @stack('modals')

    <script src="{{ asset('assets/vendors/js/vendors.min.js') }}"></script>
    <script src="{{ asset('assets/vendors/js/dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/vendors/js/dataTables.bs5.min.js') }}"></script>
    <script src="{{ asset('assets/vendors/js/tagify.min.js') }}"></script>
    <script src="{{ asset('assets/vendors/js/tagify-data.min.js') }}"></script>
    <script src="{{ asset('assets/vendors/js/select2.min.js') }}"></script>
    <script src="{{ asset('assets/vendors/js/select2-active.min.js') }}"></script>
    <script src="{{ asset('assets/vendors/js/jquery.steps.min.js') }}"></script>
    <script src="{{ asset('assets/vendors/js/jquery.validate.min.js') }}"></script>
    <script src="{{ asset('assets/vendors/js/quill.min.js') }}"></script>
    <script src="{{ asset('assets/vendors/js/lslstrength.min.js') }}"></script>
    <script src="{{ asset('assets/vendors/js/jquery.print.min.js') }}"></script>
    <script src="{{ asset('assets/vendors/js/datepicker.min.js') }}"></script>
    <script src="{{ asset('assets/js/common-init.min.js') }}"></script>
    <script src="{{ asset('assets/js/customers-init.min.js') }}"></script>
    <script src="{{ asset('assets/js/proposal-create-init.min.j') }}s"></script>
    <script src="{{ asset('assets/js/invoice-view-init.min.js') }}"></script>
    <script src="{{ asset('assets/js/theme-customizer-init.min.js') }}"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/js/lightbox.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

    <link rel="stylesheet" href="https://cdn.datatables.net/scroller/2.4.1/css/scroller.dataTables.min.css">
    <script src="https://cdn.datatables.net/scroller/2.4.1/js/dataTables.scroller.min.js"></script>


    <script>
        document.addEventListener("DOMContentLoaded", function() {
            document.documentElement.classList.add("minimenu");

            const nav = document.querySelector(".nxl-navigation .navbar-content");
            if (!nav) return;

            nav.addEventListener("click", function(e) {
                const toggler = e.target.closest(".nxl-link");
                if (!toggler) return;

                const item = toggler.closest(".nxl-hasmenu");
                if (!item) return;

                const submenu = item.querySelector(":scope > .nxl-submenu");
                if (!submenu) return;

                const href = toggler.getAttribute("href");

                if (!item.classList.contains("open")) {
                    e.preventDefault();
                    item.classList.add("open");

                    item.parentElement.querySelectorAll(":scope > .nxl-hasmenu.open").forEach(function(
                        other) {
                        if (other !== item) other.classList.remove("open");
                    });
                } else {
                    if (href === "#" || href === "javascript:void(0);") {
                        e.preventDefault();
                        item.classList.remove("open");
                    }
                }
            });

            const sidebar = document.querySelector(".nxl-navigation");
            if (sidebar) {
                sidebar.addEventListener("mouseenter", function() {
                    document.documentElement.classList.remove("minimenu");
                });
                sidebar.addEventListener("mouseleave", function() {
                    document.documentElement.classList.add("minimenu");
                });
            }
        });

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    </script>

    <script>
        window.initRowActionHandler = function(tableSelector) {
            const $table = $(tableSelector);

            if ($table.length === 0) return;

            $table.on('click', 'tbody tr', function(e) {
                if ($(e.target).closest('td.dt-control').length) return;

                const $tr = $(this);
                const dt = $table.DataTable();

                // 🔹 Hapus action lama dan highlight aktif sebelumnya
                $(`${tableSelector} tbody tr`)
                    .removeClass('action-shown action-active')
                    .next('.action-row').remove();
                $(`${tableSelector} tbody tr`).prev('.action-row').remove();

                // 🔹 Kalau klik baris yang sama → tutup dan hilangkan highlight
                if ($tr.hasClass('action-shown')) {
                    $tr.removeClass('action-shown action-active');
                    return;
                }

                const row = dt.row($tr);
                const actionHtml = row.data().action;
                const colCount = $tr.find('td').length;

                const $actionRow = $(`
                    <tr class="action-row">
                        <td colspan="${colCount}" class="p-0">
                            <div class="d-flex justify-content-center">
                                <div class="dropdown w-auto position-relative">
                                    <ul class="dropdown-menu show static-action-menu shadow border rounded-3 p-2"
                                        style="display:block; position:absolute; left:50%; transform:translateX(-50%);">
                                        ${actionHtml}
                                    </ul>
                                </div>
                            </div>
                        </td>
                    </tr>
                `);

                // Hitung posisi row di viewport
                const rowRect = $tr[0].getBoundingClientRect();
                const viewportHeight = window.innerHeight;
                const spaceBelow = viewportHeight - rowRect.bottom;
                const spaceAbove = rowRect.top;

                // Arah tampil otomatis
                const $menu = $actionRow.find('.static-action-menu');
                if (spaceBelow < 250 && spaceAbove > spaceBelow) {
                    $menu.css({
                        bottom: '100%',
                        top: 'auto',
                        'margin-bottom': '8px'
                    });
                } else {
                    $menu.css({
                        top: '100%',
                        bottom: 'auto',
                        'margin-top': '8px'
                    });
                }

                // 🔹 Tambahkan highlight aktif
                $tr.after($actionRow).addClass('action-shown action-active');
            });

            // Tutup otomatis kalau klik di luar
            $(document).on('click', function(e) {
                if (!$(e.target).closest(`${tableSelector}`).length) {
                    $(`${tableSelector} tbody tr`)
                        .removeClass('action-shown action-active')
                        .next('.action-row').remove();
                }
            });
        };

        $(document).ready(function() {
            initRowActionHandler('#saleListTable');
            // initRowActionHandler('#deletedSaleListTable');
            initRowActionHandler('#purchaseListTable');
            initRowActionHandler('#deliveryListTable');
            initRowActionHandler('#productTable');
            initRowActionHandler('#productBundleTable');
            initRowActionHandler('#categoryTable');
            initRowActionHandler('#tagTable');
            initRowActionHandler('#rejectProductsTable');
            initRowActionHandler('#rejectDetailTable');
            initRowActionHandler('#defectProductsTable');
            initRowActionHandler('#defectDetailTable');
            initRowActionHandler('#reportItemsTable');
            initRowActionHandler('#canceledDetailTable');
            initRowActionHandler('#discountList');
            initRowActionHandler('#stockOpnameTable');
            initRowActionHandler('#saleOrderTable');
            initRowActionHandler('#saleReturnTable');
            // initRowActionHandler('#deletedSaleReturnTable');
            initRowActionHandler('#designListTable');
            initRowActionHandler('#waitingListTable');
            initRowActionHandler('#assignBatchTable');
            initRowActionHandler('#requestStockTable');
            // initRowActionHandler('#deletedRequestStockTable');
            initRowActionHandler('#deliveryListTable');
            initRowActionHandler('#deliveryOrderTable');
            initRowActionHandler('#purchaseOrderTable');
            initRowActionHandler('#purchaseReturnTable');
            initRowActionHandler('#inventoryTable');
            initRowActionHandler('#expenseList');
            initRowActionHandler('#capitalTransactionList');
            initRowActionHandler('#ShopManagerList');
            initRowActionHandler('#OperatorList');
            initRowActionHandler('#customerList');
            initRowActionHandler('#supplierList');
        });

        document.addEventListener('DOMContentLoaded', function() {
            // Cegah double submit untuk SEMUA form di halaman
            document.querySelectorAll('form').forEach(function(form) {
                form.addEventListener('submit', function(e) {
                    // Kalau form sudah pernah dikirim, cegah submit ulang
                    if (form.dataset.submitted === 'true') {
                        e.preventDefault();
                        return false;
                    }

                    // Tandai sudah dikirim
                    form.dataset.submitted = 'true';

                    // Disable semua tombol submit dalam form ini
                    const submitButtons = form.querySelectorAll(
                        'button[type="submit"], input[type="submit"]');
                    submitButtons.forEach(btn => {
                        btn.disabled = true;
                        // Ubah teks jadi indikator loading
                        if (!btn.dataset.originalText) {
                            btn.dataset.originalText = btn.innerHTML;
                        }
                        btn.innerHTML =
                            '<i class="feather-loader me-2 spin"></i> Processing...';
                    });

                    // Optional: aktifkan lagi setelah 10 detik kalau kamu mau fallback
                    setTimeout(() => {
                        submitButtons.forEach(btn => {
                            btn.disabled = false;
                            if (btn.dataset.originalText) btn.innerHTML = btn
                                .dataset.originalText;
                        });
                        form.dataset.submitted = 'false';
                    }, 10000);
                });
            });
        });
    </script>

    @stack('scripts')
</body>

</html>
