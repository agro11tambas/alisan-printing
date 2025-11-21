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
    <link rel="stylesheet" href="https://cdn.datatables.net/scroller/2.3.0/css/scroller.dataTables.min.css">

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
            background-color: transparent;
            /* sedikit abu-abu biar beda */
            font-weight: 600;
            white-space: nowrap;
        }

        .table-small tbody tr:hover {
            background-color: transparent;
            /* efek hover ringan */
        }

        /* biar rapi saat ditampilkan dalam DataTables juga */
        .dataTables_wrapper .table-small tbody td {
            padding: 5px 10px !important;
        }

        .shimmer-wrapper {
            padding: 15px;
        }

        .shimmer {
            height: 50px;
            margin-bottom: 10px;
            background: linear-gradient(to right,
                    #f6f7f8 0%,
                    #edeef1 20%,
                    #f6f7f8 40%,
                    #f6f7f8 100%);
            background-size: 800px 50px;
            animation: shimmer 1.5s linear infinite;
            border-radius: 6px;
        }

        @keyframes shimmer {
            0% {
                background-position: -800px 0;
            }

            100% {
                background-position: 800px 0;
            }
        }
    </style>

    <style>
        .main-content {
            padding-bottom: 0px !important;
        }

        /* Warna highlight baris aktif */
        /* LIGHT MODE */
        html:not(.app-skin-dark) tr.action-active {
            background-color: #e5e9ef !important;
            /* abu soft tapi bold */
            transition: background-color 0.2s ease-in-out;
        }

        /* DARK MODE */
        html.app-skin-dark tr.action-active {
            background-color: #1f2937 !important;
            /* dark bold */
            transition: background-color 0.2s ease-in-out;
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
            /* z-index: 2500 !important; */
            /* lebih tinggi dari 2050 */
        }

        /* Jika ada overlay navbar atau topbar juga */
        .nxl-header,
        .topbar,
        .navbar,
        .page-header-title {
            /* z-index: 2660 !important; */
        }

        .modal {
            /* z-index: 3000 !important; */
        }

        .dataTables_scrollBody {
            overflow: visible !important;
        }

        table.dataTable {
            border-collapse: separate !important;
            border-spacing: 0;
        }

        .nxl-content,
        .card,
        .card-body,
        .table-responsive,
        .dataTables_wrapper {
            overflow: visible !important;
        }

        /* JANGAN buka overflow semua wrapper, cukup ini aja */
        .dataTables_scrollBody {
            overflow: visible !important;
            position: relative !important;
        }

        /* Biar action menu nongol di atas */
        tr.action-row {
            position: relative;
            /* z-index: 1050; */
        }

        /* Dropdown style tetap rapi */
        .static-action-menu {
            position: absolute !important;
            /* z-index: 1100 !important; */
            left: 50%;
            transform: translateX(-50%);
            /* background: transparent; */
            border: 1px solid transparent;
            border-radius: 10px;
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.15);
            min-width: 200px;
            padding: 6px 0;
        }


        /* .card-body {
            overflow: visible !important;
        } */

        /* Pastikan container utama sidebar tidak scroll */
        .nxl-navigation {
            overflow: hidden !important;
        }

        /* Wrapper juga jangan scroll */
        .nxl-navigation .navbar-wrapper {
            height: 100vh !important;
            overflow: hidden !important;
        }

        /* HANYA navbar-content yang boleh scroll */
        .nxl-navigation .navbar-content {
            height: calc(100vh - 60px) !important;
            overflow-y: auto !important;
            overflow-x: hidden !important;
            padding-bottom: 50px !important;
        }

        /* HILANGKAN SCROLLBAR DI SIDEBAR */
        .nxl-navigation .navbar-content::-webkit-scrollbar {
            display: none !important;
        }

        /* FIX AGAR SUBMENU LEVEL 2 MUNCUL SAAT MINIMIZE */
        html.minimenu .nxl-navigation .nxl-submenu .nxl-submenu {
            display: block !important;
            position: fixed !important;
            left: calc(60px + 10px) !important;
            /* samping icon */
            top: auto !important;
            z-index: 6500 !important;
            background: #ffffff !important;
            border-radius: 10px;
            min-width: 200px;
            padding: 10px 0;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.534);
        }

        .nxl-navigation .nxl-hasmenu.open>.nxl-submenu {
            display: block !important;
        }

        html:not(.app-skin-dark).minimenu .nxl-submenu {
            position: fixed !important;
            /* margin-left: 20px !important; */
            left: unset !important;
            top: unset !important;
            z-index: 6000 !important;

            background: #ffffff !important;
            /* light mode */
            pointer-events: auto !important;

            border-radius: 12px;
            padding: 10px 0;
            min-width: 200px;

            box-shadow: 0 8px 18px rgba(0, 0, 0, 0.534);
            border: 1px solid rgba(0, 0, 0, 0.05);

            animation: fadeIn .15s ease;
        }

        html.app-skin-dark.minimenu .nxl-submenu {
            position: fixed !important;
            /* margin-left: 20px !important; */
            left: unset !important;
            top: unset !important;
            z-index: 6000 !important;

            /* light mode */
            pointer-events: auto !important;

            border-radius: 12px;
            padding: 10px 0;
            min-width: 200px;

            box-shadow: 0 8px 18px rgba(0, 0, 0, 0.534);

            animation: fadeIn .15s ease;
            background: #111827 !important;
            border: 1px solid rgba(255, 255, 255, 0.06);
        }

        /* Light Mode */
        html:not(.app-skin-dark).minimenu .nxl-submenu li a {
            color: #333 !important;
        }

        html:not(.app-skin-dark).minimenu .nxl-submenu li a:hover {
            color: #000 !important;
        }

        /* separator */
        html.minimenu .nxl-submenu li+li {
            border-top: 1px solid transparent;
        }

        /* animasi */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-4px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* DARK MODE: active/open */
        html.app-skin-dark .nxl-hasmenu.open>.nxl-link {
            color: #ffffff !important;
        }

        html.app-skin-dark .nxl-navigation .nxl-submenu .nxl-link {
            color: #e5e7eb !important;
        }

        html.app-skin-dark .nxl-navigation .nxl-submenu .nxl-link:hover {
            color: #ffffff !important;
            background: rgba(255, 255, 255, 0.06) !important;
        }

        html.app-skin-dark .nxl-navigation .nxl-submenu .nxl-item.active>.nxl-link {
            color: #ffffff !important;
        }

        html.app-skin-dark .nxl-navigation .nxl-submenu .nxl-link,
        html.app-skin-dark .nxl-navigation .nxl-submenu .nxl-link span {
            /* color: #e5e7eb !important; */
        }

        html.minimenu .nxl-navigation .nxl-submenu {
            position: fixed !important;
            top: 0 !important;
            left: 75px !important;
            /* tepat di samping sidebar minimize */
            transform: translateY(calc(var(--item-top) * 1px)) !important;
            z-index: 6000 !important;

            /* background: var(--submenu-bg, #ffffff) !important; */
            border-radius: 10px;
            padding: 10px 0;
            min-width: 200px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.15);
        }

        /* =========================================== */
        /*  FINAL OVERRIDE SUBMENU FLOATING – TANPA    */
        /*  MENGGANGGU / MENGHAPUS CSS LAIN            */
        /* =========================================== */

        /* Default: semua submenu kembali normal */
        /* FIX TEXT SUBMENU ACCOUNT LIST DI MINIMIZE */
        html.minimenu .nxl-submenu {
            display: block !important;
            position: fixed !important;
            left: 75px !important;
            z-index: 6000 !important;
        }

        /* Minimize: hanya LEVEL 1 yang floating */
        html.minimenu .nxl-hasmenu>.nxl-submenu {
            position: fixed !important;
            left: 75px !important;
            /* samping sidebar */
            top: var(--submenu-top) !important;
            display: block !important;

            min-width: 220px !important;
            padding: 10px 0 !important;
            /* background: var(--submenu-bg, #fff) !important; */
            border-radius: 10px !important;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.15) !important;
            z-index: 7000 !important;
        }

        /* Level 2 & 3 → kembali normal */
        html.minimenu .nxl-hasmenu>.nxl-submenu .nxl-submenu {
            position: absolute !important;
            left: 100% !important;
            top: 0 !important;
            display: none !important;
        }

        /* Hover buka submenu level 2 */
        html.minimenu .nxl-hasmenu>.nxl-submenu .nxl-hasmenu:hover>.nxl-submenu {
            display: block !important;
        }

        /* HILANGKAN ICON SPACE PADA SUBMENU */
        html.minimenu .nxl-submenu .nxl-micon {
            width: 0 !important;
            min-width: 0 !important;
            margin-right: 0 !important;
            padding: 0 !important;
            overflow: hidden !important;
        }

        /* Biar teks langsung mulai dari paling kiri */
        html.minimenu .nxl-submenu .nxl-link {
            padding-left: 6px !important;
        }

        html.minimenu .nxl-navigation .navbar-content .nxl-submenu .nxl-link {
            margin-left: 15px !important;
            margin-right: 15px !important;
        }

        /* Hanya item yang BENAR-BENAR punya submenu */
        html.minimenu .nxl-navigation .nxl-hasmenu:has(> ul.nxl-submenu)>.nxl-link {
            position: relative;
        }

        html.minimenu .nxl-navigation .nxl-hasmenu:has(> ul.nxl-submenu)>.nxl-link::after {
            content: "›";
            position: absolute;
            right: 6px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 14px;
            opacity: 0.7;
            pointer-events: none;
        }

        /* Hover lebih terang */
        html.minimenu .nxl-navigation .nxl-hasmenu:has(> ul.nxl-submenu)>.nxl-link:hover::after {
            opacity: 1;
        }

        .swal2-container {
            z-index: 99999 !important;
        }

        /* TOPBAR + BREADCRUMB naik jadi 5000 */
        .page-header.sticky-top,
        .page-header,
        .nxl-header,
        .topbar,
        .navbar,
        .page-header-title {
            z-index: 5000 !important;
            /* position: relative !important; */
        }

        html.minimenu .nxl-hasmenu>.nxl-submenu {
            top: auto !important;
            bottom: auto !important;
        }

        /* FIX: hilangkan paksaan top=0 yang bikin turun terus */
        html.minimenu .nxl-submenu {
            top: auto !important;
        }

        /* Submenu default posisi turun */
        html.minimenu .nxl-submenu.dropdown-down {
            top: var(--submenu-top, 0px) !important;
        }

        /* Submenu drop-up jika mentok bawah */
        html.minimenu .nxl-submenu.dropdown-up {
            bottom: 10px !important;
            top: auto !important;
        }

        .modal {
            z-index: 9999 !important;
        }

        .modal-backdrop {
            z-index: 9998 !important;
        }

        .nxl-navigation,
        .nxl-navigation .navbar-wrapper,
        .nxl-navigation .navbar-content {
            z-index: 4000 !important;
            /* Firefox */
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

    {{-- <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script> --}}
    <script src="https://cdn.datatables.net/scroller/2.4.1/js/dataTables.scroller.min.js"></script>

    <script>
        window.initRowActionHandler = function(tableSelector) {
            const $table = $(tableSelector);

            if ($table.length === 0) return;

            $table.on('click', 'tbody tr', function(e) {
                if ($(e.target).closest('td.dt-control').length) return;

                const $tr = $(this);
                const dt = $table.DataTable();

                $(`${tableSelector} tbody tr`)
                    .removeClass('action-shown action-active')
                    .next('.action-row').remove();
                $(`${tableSelector} tbody tr`).prev('.action-row').remove();

                if ($tr.hasClass('action-shown')) {
                    $tr.removeClass('action-shown action-active');
                    return;
                }

                const row = dt.row($tr);
                const actionHtml = row.data().action;
                const colCount = $tr.find('td').length;

                let menuPosition = 'left: 200px; transform:none;';
                if (tableSelector === '#deliveryListTable') {
                    menuPosition = 'left: 20px; transform:none;';
                }

                const $actionRow = $(`
                    <tr class="action-row">
                        <td colspan="${colCount}" class="p-0">
                            <div class="d-flex justify-content-${tableSelector === '#deliveryListTable' ? 'start' : 'start'}">
                                <div class="dropdown w-auto position-relative">
                                    <ul class="dropdown-menu show static-action-menu shadow border rounded-3 p-2"
                                        style="display:block; position:absolute; ${menuPosition}">
                                        ${actionHtml}
                                    </ul>
                                </div>
                            </div>
                        </td>
                    </tr>
                `);

                const scrollBody = $table.closest('.dataTables_scrollBody')[0];
                const scrollRect = scrollBody.getBoundingClientRect();
                const rowRect = $tr[0].getBoundingClientRect();

                const spaceAbove = rowRect.top - scrollRect.top;
                const spaceBelow = scrollRect.bottom - rowRect.bottom;

                const $menu = $actionRow.find('.static-action-menu');
                const safeTop = 200; // minimal area aman agar dropdown selalu turun

                if (spaceAbove < safeTop) {
                    // Row terlalu dekat atas → PAKSA TURUN
                    $menu.css({
                        top: '100%',
                        bottom: 'auto',
                        'margin-top': '0px'
                    });
                } else if (spaceBelow < 250 && spaceAbove > spaceBelow) {
                    // Bawah sempit → naik
                    $menu.css({
                        bottom: '100%',
                        top: 'auto',
                        'margin-bottom': '0px'
                    });
                } else {
                    // Default turun
                    $menu.css({
                        top: '100%',
                        bottom: 'auto',
                        'margin-top': '0px'
                    });
                }


                $tr.after($actionRow).addClass('action-shown action-active');
            });

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
            initRowActionHandler('#editedSaleListTable');
            initRowActionHandler('#purchaseListTable');
            initRowActionHandler('#deliveryListTable');
            initRowActionHandler('#deliveryOrderTable');
            initRowActionHandler('#productTable');
            initRowActionHandler('#productBundleTable');
            initRowActionHandler('#categoryTable');
            initRowActionHandler('#tagTable');
            initRowActionHandler('#rejectProductsTable');
            initRowActionHandler('#rejectDetailTable');
            initRowActionHandler('#defectProductsTable');
            initRowActionHandler('#defectDetailTable');
            // initRowActionHandler('#reportItemsTable');
            initRowActionHandler('#canceledDetailTable');
            initRowActionHandler('#discountList');
            initRowActionHandler('#stockOpnameTable');
            initRowActionHandler('#saleOrderTable');
            initRowActionHandler('#saleReturnTable');
            initRowActionHandler('#designListTable');
            initRowActionHandler('#waitingListTable');
            initRowActionHandler('#assignBatchTable');
            initRowActionHandler('#requestStockTable');
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
    </script>

    <script>
        (function() {
            'use strict';

            const buttonState = new WeakMap();
            const clickTimestamps = new WeakMap();
            let currentLockedButtons = [];

            function lockButton(btn) {
                btn.disabled = true;

                if (!btn.dataset.originalText) {
                    btn.dataset.originalText = btn.innerHTML;
                }

                btn.innerHTML = '<i class="feather-loader me-2 spin"></i> Processing...';
                btn.style.pointerEvents = 'none';
                buttonState.set(btn, true);

                if (!currentLockedButtons.includes(btn)) {
                    currentLockedButtons.push(btn);
                }
            }

            function unlockButton(btn) {
                btn.disabled = false;
                btn.style.pointerEvents = '';
                buttonState.delete(btn);

                if (btn.dataset.originalText) {
                    btn.innerHTML = btn.dataset.originalText;
                }

                const index = currentLockedButtons.indexOf(btn);
                if (index > -1) {
                    currentLockedButtons.splice(index, 1);
                }
            }

            function unlockAllCurrentButtons() {
                currentLockedButtons.forEach(function(btn) {
                    unlockButton(btn);
                });
                currentLockedButtons = [];
            }

            function initProtection() {

                document.querySelectorAll('button[type="submit"], input[type="submit"]').forEach(function(btn) {

                    if (btn.dataset.protected) return;
                    btn.dataset.protected = 'true';

                    btn.addEventListener('click', function(e) {
                        const now = Date.now();
                        const lastClick = clickTimestamps.get(btn) || 0;

                        if (now - lastClick < 2000) {
                            e.preventDefault();
                            e.stopPropagation();
                            e.stopImmediatePropagation();
                            console.warn('⚠️ Tolong tunggu sebentar...');
                            return false;
                        }

                        clickTimestamps.set(btn, now);

                        setTimeout(() => lockButton(btn), 10);

                        setTimeout(() => unlockButton(btn), 5000);

                    }, true);

                });

                document.querySelectorAll('form').forEach(function(form) {

                    if (form.dataset.protected) return;
                    form.dataset.protected = 'true';

                    form.addEventListener('invalid', function(e) {
                        setTimeout(function() {
                            unlockAllCurrentButtons();
                        }, 100);
                    }, true);

                    form.addEventListener('submit', function(e) {
                        setTimeout(function() {
                            unlockAllCurrentButtons();
                        }, 5000);
                    });

                });

                document.addEventListener('click', function(e) {
                    if (!e.target.matches('button[type="submit"], input[type="submit"]')) {
                        unlockAllCurrentButtons();
                    }
                }, true);

                document.addEventListener('keydown', function(e) {
                    if (e.key !== 'Tab') {
                        unlockAllCurrentButtons();
                    }
                }, true);

                const observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        mutation.addedNodes.forEach(function(node) {
                            if (node.nodeType === 1) {
                                if (node.matches(
                                        'form, button[type="submit"], input[type="submit"]')) {
                                    initProtection();
                                }
                                if (node.querySelectorAll) {
                                    const forms = node.querySelectorAll('form');
                                    const buttons = node.querySelectorAll(
                                        'button[type="submit"], input[type="submit"]');
                                    if (forms.length > 0 || buttons.length > 0) {
                                        initProtection();
                                    }
                                }
                            }
                        });
                    });
                });

                observer.observe(document.body, {
                    childList: true,
                    subtree: true
                });
            }

            // 🚀 INIT
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initProtection);
            } else {
                initProtection();
            }

            if (typeof jQuery !== 'undefined') {
                jQuery(document).ready(initProtection);
            }

            // 🎯 Global unlock function
            window.unlockAllButtons = function() {
                unlockAllCurrentButtons();
                console.log('✅ Semua button di-unlock');
            };

        })();
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Default langsung minimize
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

                // MODE SIDEBAR MINIMIZE (minimenu)
                const isMini = document.documentElement.classList.contains("minimenu");

                if (isMini) {
                    // e.preventDefault();

                    // Tutup dropdown lain
                    document.querySelectorAll(".nxl-hasmenu .nxl-submenu").forEach(el => {
                        if (el !== submenu) el.style.display = "none";
                    });

                    // Toggle
                    const show = submenu.style.display !== "block";
                    submenu.style.display = show ? "block" : "none";

                    if (show) {
                        const itemRect = item.getBoundingClientRect();
                        const sidebarRect = document.querySelector(".nxl-navigation")
                            .getBoundingClientRect();

                        submenu.style.position = "fixed";

                        // ⬅️ Posisi tepat di samping icon
                        submenu.style.top = (itemRect.top) + "px";

                        // ⬅️ Sampingin langsung setelah sidebar minimize
                        submenu.style.left = (sidebarRect.right + 28) + "px";

                        // ⬅️ Biar rata tengah icon (opsional)
                        submenu.style.transform = "translateY(0)";

                        submenu.style.zIndex = "6000";
                        submenu.style.minWidth = "190px";
                        submenu.style.borderRadius = "10px";
                        submenu.style.boxShadow = "0 3px 6px rgba(0,0,0,0.20)";
                        submenu.style.padding = "8px 0";

                        // Hapus margin default yang bikin turun
                        submenu.style.marginTop = "0";
                    }

                    return;
                }

                // MODE NORMAL (sidebar besar)
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
        });

        document.addEventListener("DOMContentLoaded", function() {

            const btnMini = document.getElementById("menu-mini-button");
            const btnExpend = document.getElementById("menu-expend-button");

            function closeAllMinimizeDropdowns() {
                document.querySelectorAll(".nxl-submenu").forEach(el => {
                    el.style.display = "none";
                    el.removeAttribute("style"); // bersihkan floating minimize
                });

                // tutup semua open
                document.querySelectorAll(".nxl-hasmenu.open").forEach(li => {
                    li.classList.remove("open");
                });
            }

            // Saat MAXIMIZE ditekan
            if (btnExpend) {
                btnExpend.addEventListener("click", function() {
                    setTimeout(() => {
                        // sidebar sudah maximize
                        closeAllMinimizeDropdowns();
                    }, 50);
                });
            }

            // Saat MINIMIZE ditekan → opsional bisa bersihkan juga
            if (btnMini) {
                btnMini.addEventListener("click", function() {
                    setTimeout(() => {
                        closeAllMinimizeDropdowns();
                    }, 50);
                });
            }

        });

        document.addEventListener("DOMContentLoaded", function() {

            const observer = new MutationObserver(() => {
                document.querySelectorAll(".nxl-submenu").forEach(menu => {
                    menu.style.background = "";
                    menu.style.border = "";
                });
            });

            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        });

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        document.addEventListener("DOMContentLoaded", function() {

            document.querySelectorAll(".nxl-hasmenu > .nxl-link").forEach(link => {

                link.addEventListener("mouseenter", function() {
                    if (!document.documentElement.classList.contains("minimenu")) return;

                    const li = this.closest(".nxl-hasmenu");
                    const submenu = li.querySelector(":scope > .nxl-submenu");
                    const rect = li.getBoundingClientRect();

                    submenu.style.setProperty("--submenu-top", rect.top + "px");
                });

            });

        });

        document.addEventListener("DOMContentLoaded", function() {

            document.querySelectorAll(".nxl-hasmenu > .nxl-link").forEach(link => {

                link.addEventListener("mouseenter", function() {
                    if (!document.documentElement.classList.contains("minimenu")) return;

                    const li = this.closest(".nxl-hasmenu");
                    const submenu = li.querySelector(":scope > .nxl-submenu");
                    if (!submenu) return;

                    submenu.classList.remove("dropdown-up", "dropdown-down");

                    const itemRect = li.getBoundingClientRect();
                    const sidebarRect = document.querySelector(".nxl-navigation")
                        .getBoundingClientRect();

                    submenu.style.display = "block";
                    submenu.style.position = "fixed";
                    submenu.style.left = (sidebarRect.right + 28) + "px";
                    submenu.style.top = itemRect.top + "px";

                    // DETEKSI KETINGGIAN SUBMENU
                    const menuRect = submenu.getBoundingClientRect();
                    const vh = window.innerHeight;

                    if (menuRect.bottom > vh - 20) {
                        submenu.classList.add("dropdown-up");
                        submenu.style.top = "auto";
                    } else {
                        submenu.classList.add("dropdown-down");
                    }
                });

            });

        });
    </script>

    @stack('scripts')
</body>

</html>
