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

    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/bootstrap.min.css') }}">

    <link rel="stylesheet" type="text/css" href="{{ asset('assets/vendors/css/vendors.min.css') }}">
    {{-- DataTables, Select2, Lightbox, dan Scroller adalah bagian terberat dari
         aset halaman ini: sekitar 60 KB CSS dan 175 KB JavaScript. Keduanya
         dimuat di SEMUA halaman, termasuk yang tidak punya tabel maupun dropdown.

         Laporan waktu muat dari browser pengguna 1 September 2026 menunjukkan
         dom_interactive 987 ms pada halaman yang dokumennya sendiri sudah sampai
         di 182 ms — hampir 800 ms habis mengunduh dan menjalankan JavaScript.

         Halaman yang tidak membutuhkannya bisa melewatkannya dengan menulis
         @section('assets_mode', 'ringan') di awal view. Yang tidak menulis apa
         pun tetap memuat semuanya, jadi halaman lama tidak berubah perilaku. --}}
    @php
        $assetsRingan = trim($__env->yieldContent('assets_mode')) === 'ringan';
    @endphp

    @unless ($assetsRingan)
        <link rel="stylesheet" type="text/css" href="{{ asset('assets/vendors/css/dataTables.bs5.min.css') }}">
        <link rel="stylesheet" type="text/css" href="{{ asset('assets/vendors/css/select2.min.css') }}">
        <link rel="stylesheet" type="text/css" href="{{ asset('assets/vendors/css/select2-theme.min.css') }}">
    @endunless

    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/theme.min.css') }}">

    @unless ($assetsRingan)
        <link href="{{ asset('assets/vendors/css/lightbox.min.css') }}" rel="stylesheet">
        <link rel="stylesheet" href="{{ asset('assets/vendors/css/scroller.dataTables.min.css') }}">
    @endunless

    <style>
        div.dataTables_wrapper .row:first-child {
            margin: 0 !important;
            padding: 0 !important;
        }
    </style>


    <style>
        /* Default (desktop) */
        .nxl-page-title {
            font-size: 1rem;
            line-height: 1.4;
        }

        /* Tablet */
        @media (max-width: 768px) {
            .nxl-page-title {
                font-size: 0.85rem;
            }
        }

        /* Mobile */
        @media (max-width: 480px) {
            .nxl-page-title {
                font-size: 0.7rem;
                line-height: 1.2;
            }
        }

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
                padding: 5px;
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
            /* z-index: 9997 !important; */
        }
    </style>

    <style>
        .table-small {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .table-small th,
        .table-small td {
            padding: 5px 10px !important;
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
            padding: 4px;
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
            /* z-index: 99999 !important; */
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
            /* z-index: 6500 !important; */
            background: #ffffff !important;
            border-radius: 10px;
            min-width: 200px;
            padding: 5px 0;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.534);
        }

        .nxl-navigation .nxl-hasmenu.open>.nxl-submenu {
            display: block !important;
        }

        html:not(.app-skin-dark).minimenu .nxl-submenu {
            position: fixed !important;
            /* margin-left: 20px !important; */
            left: 65px !important;
            /* z-index: 6000 !important; */

            background: #ffffff !important;
            /* light mode */
            pointer-events: auto !important;

            border-radius: 12px;
            padding: 5px 0;
            min-width: 200px;

            box-shadow: 0 8px 18px rgba(0, 0, 0, 0.534);
            border: 1px solid rgba(0, 0, 0, 0.05);

            animation: fadeIn .15s ease;
        }

        html.app-skin-dark.minimenu .nxl-submenu {
            position: fixed !important;
            /* margin-left: 20px !important; */
            left: 65px !important;
            /* z-index: 9000 !important; */

            /* light mode */
            pointer-events: auto !important;

            border-radius: 12px;
            padding: 5px 0;
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

        /* FIX LEVEL 3 SUBMENU (seperti Account List) DI DARK MODE */
        html.app-skin-dark .nxl-navigation .nxl-submenu .nxl-submenu {
            background: transparent !important;
        }
        html.app-skin-dark.minimenu .nxl-navigation .nxl-submenu .nxl-submenu {
            background: #111827 !important;
        }

        html.minimenu .nxl-navigation .nxl-submenu {
            position: fixed !important;
            top: 0 !important;
            left: 65px !important;
            /* tepat di samping sidebar minimize */
            transform: translateY(calc(var(--item-top) * 1px)) !important;
            /* z-index: 99999 !important; */

            /* background: var(--submenu-bg, #ffffff) !important; */
            border-radius: 10px;
            padding: 5px 0;
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
            left: 65px !important;
            /* z-index: 6000 !important; */
        }

        /* Minimize: hanya LEVEL 1 yang floating */
        html.minimenu .nxl-hasmenu>.nxl-submenu {
            position: fixed !important;
            left: 65px !important;
            /* samping sidebar */
            top: var(--submenu-top) !important;
            display: block !important;

            min-width: 220px !important;
            padding: 5px 0 !important;
            /* background: var(--submenu-bg, #fff) !important; */
            border-radius: 10px !important;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.15) !important;
            /* z-index: 7000 !important; */
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
            /* z-index: 99996 !important; */
        }

        .page-header.sticky-top,
        .page-header,
        .topbar,
        .navbar,
        .page-header-title {
            /* z-index: 9995 !important; */
        }

        /* Header di atas breadcrumb */
        .nxl-header {
            /* z-index: 9995 !important; */
        }


        .nxl-user-dropdown {
            /* z-index: 99999 !important; */
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
            /* z-index: 9996 !important; */
        }

        .modal-backdrop {
            /* z-index: 9995 !important; */
        }

        .nxl-navigation,
        .nxl-navigation .navbar-wrapper,
        .nxl-navigation .navbar-content {
            /* z-index: 4000 !important; */
            /* Firefox */
        }

        /* Default (desktop) */
        .page-header-right-items-wrapper {
            flex-wrap: nowrap;
        }

        /* Mobile */
        @media (max-width: 576px) {
            .page-header-right-items-wrapper {
                flex-wrap: wrap;
                gap: 6px !important;
            }

            /* Select filter tetap full */
            .page-header-right-items-wrapper>.col-auto:first-child {
                width: 100%;
            }

            /* Start & End date 1 baris */
            .page-header-right-items-wrapper .custom-range {
                width: calc(50% - 3px);
            }

            /* Apply button full width */
            .page-header-right-items-wrapper .custom-range:last-child {
                width: 100%;
            }

            /* Control size */
            .page-header-right-items-wrapper select,
            .page-header-right-items-wrapper input,
            .page-header-right-items-wrapper button {
                font-size: 0.75rem;
                padding: 0.45rem 0.75rem;
            }
        }

        .select2-container {
            z-index: auto !important;
        }
    </style>

    @stack('styles')
    <style>
        /* Compact Topbar Overrides */
        .nxl-header {
            height: 60px !important;
            min-height: 60px !important;
        }
        .nxl-header .header-wrapper {
            height: 60px !important;
            min-height: 60px !important;
        }
        .nxl-header .header-wrapper .nxl-h-item {
            min-height: 60px !important;
        }
        .nxl-header .nxl-head-link {
            width: 35px !important;
            height: 35px !important;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .nxl-header .nxl-head-link i {
            font-size: 16px !important;
        }
        .nxl-header .user-avtar {
            width: 35px !important;
            height: 35px !important;
        }
        .nxl-container {
            top: 60px !important;
            min-height: calc(100vh - 60px) !important;
        }
        .page-header {
            top: 60px !important;
        }
        /* Compact Sidebar Menu Overrides */
        .nxl-navigation .navbar-content .nxl-item .nxl-link {
            padding: 8px 15px !important;
            font-size: 0.85rem !important;
        }
        .nxl-navigation .navbar-content .nxl-item.nxl-caption {
            padding: 10px 15px 5px !important;
            font-size: 0.75rem !important;
        }
        /* Compact Sidebar Width Overrides */
        @media (min-width: 1025px) {
            html:not(.minimenu) .nxl-navigation {
                width: 230px !important;
            }
            html:not(.minimenu) .nxl-container {
                margin-left: 230px !important;
            }
            html:not(.minimenu) .nxl-header {
                left: 230px !important;
            }
            html:not(.minimenu) .page-header {
                left: 230px !important;
            }

            /* Minimenu Overrides */
            html.minimenu .nxl-navigation {
                width: 80px !important;
            }
            html.minimenu .nxl-container {
                margin-left: 80px !important;
            }
            html.minimenu .nxl-header {
                left: 80px !important;
            }
            html.minimenu .page-header {
                left: 80px !important;
            }
            html.minimenu .nxl-navigation .navbar-content {
                width: 80px !important;
            }
            html.minimenu .nxl-navigation .logo-sm {
                width: 35px !important;
                margin: 0 auto;
            }
            html.minimenu .nxl-navigation .navbar-content .nxl-link {
                margin: 0 10px !important;
                padding: 8px 15px !important;
                justify-content: center;
            }
            html.minimenu .nxl-navigation .navbar-content .nxl-micon {
                margin-right: 0 !important;
            }
        }

        /* DataTables Overrides */
        .dataTables_wrapper .row:first-child,
        .dataTables_wrapper .row:last-child {
            padding: 25px 25px;
        }
        .dataTables_wrapper .row:last-child {
            border-top: none !important;
        }

        /* Compact Table Overrides */
        .table-responsive .table tr td,
        .table-responsive .table tr th,
        .table>:not(caption)>*>* {
            padding: 5px !important;
        }

        /* Stretch Main Table Card and Inner Scroll to Bottom safely */
        .nxl-content {
            padding-bottom: 0 !important;
        }
        /* Memaksa kartu putih sampai dasar layar */
        .main-content > .row > .col-lg-12 > .card.stretch-full {
            margin-bottom: 0 !important;
            min-height: calc(100vh - 115px) !important;
            border-bottom-left-radius: 0 !important;
            border-bottom-right-radius: 0 !important;
        }
        /* MENGALAHKAN kode bawaan (60vh) menggunakan specificity hack :not(#fake) */
        :not(#fake) .main-content > .row > .col-lg-12 > .card.stretch-full .dataTables_scrollBody {
            height: auto !important; 
            max-height: calc(100vh - 210px) !important; 
        }
    </style>
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
    @unless ($assetsRingan)
        <script src="{{ asset('assets/vendors/js/dataTables.min.js') }}"></script>
        <script src="{{ asset('assets/vendors/js/dataTables.bs5.min.js') }}"></script>
        <script src="{{ asset('assets/vendors/js/select2.min.js') }}"></script>
        <script src="{{ asset('assets/vendors/js/select2-active.min.js') }}"></script>
    @endunless
    <script src="{{ asset('assets/js/common-init.min.js') }}"></script>

    @unless ($assetsRingan)
        <script src="{{ asset('assets/vendors/js/lightbox.min.js') }}"></script>
    @endunless

    @if (request()->is(
            'erp/sales/sale-orders',
            'erp/sales/sale-orders/invoice/*',
            'erp/sales/sale-list',
            'erp/sales/sale-list/invoice/*',
            'erp/sales/sale-list/invoice-image/*',
            'erp/sales/sale-returns/invoice/*'
        ))
        <script src="{{ asset('assets/vendors/js/html2canvas.min.js') }}"></script>
    @endif


    @unless ($assetsRingan)
        <script src="{{ asset('assets/vendors/js/dataTables.scroller.min.js') }}"></script>
    @endunless

    <script>
        window.initRowActionHandler = function(tableSelector) {
            const $table = $(tableSelector);

            if ($table.length === 0) return;

            $table.on('click', 'tbody tr', function(e) {
                if ($(e.target).closest('td.dt-control').length) return;

                // Klik di dalam menu action (tombol Delete, Edit, dll) jangan diproses di sini.
                // Kalau diproses, baris menu ikut ke-remove sebelum Bootstrap sempat membaca
                // tombolnya, jadi modal (delete / change status) tidak pernah terbuka lagi.
                if ($(e.target).closest('.action-row').length) return;

                const $tr = $(this);
                const dt = $table.DataTable();

                // Bukan baris data DataTable (mis. baris "No data available") -> abaikan
                if (!dt.row($tr).data()) return;

                $(`${tableSelector} tbody tr`)
                    .removeClass('action-shown action-active')
                    .next('.action-row').remove();
                $(`${tableSelector} tbody tr`).prev('.action-row').remove();

                if ($tr.hasClass('action-shown')) {
                    $tr.removeClass('action-shown action-active');
                    return;
                }

                const row = dt.row($tr);
                const actionHtml = row.data().action || '';
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
                                    <ul class="dropdown-menu show static-action-menu shadow border rounded-3 p-1"
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
            initRowActionHandler('#supplierList');

            $(document).on('click.erpRowActions', function(e) {
                if ($(e.target).closest('table.dataTable').length) return;

                $('table.dataTable tbody tr.action-shown')
                    .removeClass('action-shown action-active')
                    .next('.action-row').remove();
            });

            // Safety net: kalau ada modal yang di-hide barengan SweetAlert / re-render tabel,
            // kadang backdrop-nya nyangkut dan nutupin seluruh halaman (klik jadi mati,
            // seolah harus refresh dulu). Bersihkan sisa backdrop setiap modal ditutup.
            $(document).on('hidden.bs.modal', '.modal', function() {
                if ($('.modal.show').length === 0) {
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open').css({
                        'overflow': '',
                        'padding-right': ''
                    });
                }
            });
        });
    </script>

    <script>
        (function() {
            'use strict';

            if (window.__erpSubmitProtectionInitialized) {
                return;
            }

            window.__erpSubmitProtectionInitialized = true;

            const currentLockedButtons = new Set();

            function lockButton(btn) {
                if (!btn || btn.disabled) {
                    return;
                }

                btn.disabled = true;

                if (!btn.dataset.originalText) {
                    btn.dataset.originalText = btn.innerHTML;
                }

                btn.innerHTML = '<i class="feather-loader me-2 spin"></i> Processing...';
                btn.style.pointerEvents = 'none';
                currentLockedButtons.add(btn);
            }

            function unlockButton(btn) {
                if (!btn) {
                    return;
                }

                btn.disabled = false;
                btn.style.pointerEvents = '';

                if (btn.dataset.originalText) {
                    btn.innerHTML = btn.dataset.originalText;
                }

                currentLockedButtons.delete(btn);
            }

            function unlockAllCurrentButtons() {
                [...currentLockedButtons].forEach(unlockButton);

                document.querySelectorAll('form[data-submitting="true"]').forEach(function(form) {
                    delete form.dataset.submitting;
                });
            }

            document.addEventListener('submit', function(event) {
                const form = event.target;

                if (!(form instanceof HTMLFormElement)) {
                    return;
                }

                // Form delete Sale Order punya penguncian dan lifecycle AJAX sendiri.
                // Jangan kunci tombol di handler global sebelum AJAX-nya berjalan.
                if (form.id === 'formDeleteOrder') {
                    return;
                }

                // Form-level validation and AJAX handlers run before this delegated handler.
                if (event.defaultPrevented) {
                    delete form.dataset.submitting;
                    return;
                }

                if (form.dataset.submitting === 'true') {
                    event.preventDefault();
                    event.stopImmediatePropagation();
                    return;
                }

                form.dataset.submitting = 'true';

                const submitter = event.submitter
                    || form.querySelector('button[type="submit"], input[type="submit"]');

                queueMicrotask(function() {
                    if (event.defaultPrevented) {
                        delete form.dataset.submitting;
                        return;
                    }

                    lockButton(submitter);
                });
            });

            document.addEventListener('invalid', function(event) {
                const form = event.target?.form;

                if (!form) {
                    return;
                }

                delete form.dataset.submitting;
                form.querySelectorAll('button[type="submit"], input[type="submit"]')
                    .forEach(unlockButton);
            }, true);

            window.addEventListener('pageshow', unlockAllCurrentButtons);

            window.unlockAllButtons = unlockAllCurrentButtons;
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
            document.querySelectorAll(".nxl-submenu").forEach(menu => {
                menu.style.background = "";
                menu.style.border = "";
            });
        });

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        (function installExpiredSessionRedirect() {
            if (window.__erpExpiredSessionRedirectInstalled) {
                return;
            }

            window.__erpExpiredSessionRedirectInstalled = true;

            function redirectToLogin() {
                window.location.replace('/login');
            }

            $(document).on('ajaxError.erpExpiredSession', function(event, xhr) {
                if (xhr.status === 419) {
                    redirectToLogin();
                }
            });

            if (window.fetch) {
                const originalFetch = window.fetch.bind(window);

                window.fetch = async function(...args) {
                    const response = await originalFetch(...args);
                    const redirectedToLogin = response.redirected
                        && new URL(response.url, window.location.origin).pathname === '/login';

                    if (response.status === 419 || redirectedToLogin) {
                        redirectToLogin();
                    }

                    return response;
                };
            }
        })();
        (function installRequestDiagnostics() {
            if (window.__erpRequestDiagnosticsInstalled) {
                return;
            }

            window.__erpRequestDiagnosticsInstalled = true;

            function diagnosticsEnabled() {
                try {
                    return window.localStorage.getItem('erp_debug_requests') === '1';
                } catch (error) {
                    return false;
                }
            }

            $(document)
                .on('ajaxSend.erpDiagnostics', function(event, xhr, settings) {
                    xhr.__erpStartedAt = performance.now();

                    if (diagnosticsEnabled()) {
                        console.debug('[ERP AJAX:start]', settings.type || 'GET', settings.url);
                    }
                })
                .on('ajaxComplete.erpDiagnostics', function(event, xhr, settings) {
                    if (!diagnosticsEnabled()) {
                        return;
                    }

                    const duration = xhr.__erpStartedAt
                        ? Math.round(performance.now() - xhr.__erpStartedAt)
                        : null;

                    console.debug(
                        '[ERP AJAX:end]',
                        settings.type || 'GET',
                        settings.url,
                        xhr.status,
                        duration === null ? '-' : `${duration}ms`
                    );
                });

            window.addEventListener('error', function(event) {
                if (diagnosticsEnabled()) {
                    console.error('[ERP JS:error]', event.error || event.message);
                }
            });

            window.addEventListener('unhandledrejection', function(event) {
                if (diagnosticsEnabled()) {
                    console.error('[ERP Promise:unhandled]', event.reason);
                }
            });
        })();

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

    @php
        $flashAlert = match (true) {
            session()->has('logout_notice') => [
                'icon' => 'warning',
                'title' => 'Session Expired',
                'text' => session('logout_notice'),
            ],
            session()->has('error') => [
                'icon' => 'error',
                'title' => 'Gagal!',
                'text' => session('error'),
            ],
            session()->has('warning') => [
                'icon' => 'warning',
                'title' => 'Perhatian!',
                'text' => session('warning'),
            ],
            session()->has('success') => [
                'icon' => 'success',
                'title' => 'Berhasil!',
                'text' => session('success'),
            ],
            default => null,
        };
    @endphp

    @if ($flashAlert)
        <script>
            Swal.fire({{ \Illuminate\Support\Js::from($flashAlert) }});
        </script>
    @endif

    @stack('scripts')

    {{-- Ukur waktu muat dari sisi browser pengguna dan laporkan kalau lambat.
         Instrumen lain di aplikasi ini semuanya mengukur dari dalam server, jadi
         buta terhadap DNS, TLS, CDN, jaringan pengguna, dan waktu render. Hanya
         browser pengguna yang bisa melihat bagian itu. Hasilnya masuk ke
         storage/logs/performance-*.log sebagai performance.client_timing. --}}
    <script>
        (function reportClientTiming() {
            // Direkam sedini mungkin. Tab yang dibuka di latar belakang
            // diperlambat browser: DOMContentLoaded bisa tertunda puluhan detik
            // walaupun server dan jaringan sempurna. Tanpa penanda ini, kejadian
            // seperti itu tidak bisa dibedakan dari aplikasi yang benar-benar
            // lambat.
            var hiddenSaatMuat = document.visibilityState === 'hidden';
            document.addEventListener('visibilitychange', function () {
                if (document.visibilityState === 'hidden') hiddenSaatMuat = true;
            });

            // Menunggu 'load' supaya seluruh aset ikut terhitung, lalu ditunda
            // sedikit agar laporan tidak ikut memperlambat halaman.
            window.addEventListener('load', function () {
                setTimeout(function () {
                    try {
                        var nav = performance.getEntriesByType('navigation')[0];
                        if (!nav) return;

                        var total = Math.round(nav.duration);
                        // Ambang sama dengan sisi server, diatur lewat
                        // CLIENT_TIMING_LOG_MS. Disaring di sini juga supaya
                        // pemuatan normal tidak mengirim request sama sekali.
                        if (!total || total < {{ (int) config('app.client_timing_log_ms', 3000) }}) return;

                        var resources = performance.getEntriesByType('resource') || [];
                        var slowest = null;
                        resources.forEach(function (r) {
                            if (!slowest || r.duration > slowest.duration) slowest = r;
                        });

                        var conn = navigator.connection || {};

                        var payload = {
                            path: location.pathname.slice(0, 255),
                            total_ms: total,
                            dns_ms: Math.round(nav.domainLookupEnd - nav.domainLookupStart),
                            tcp_ms: Math.round(nav.connectEnd - nav.connectStart),
                            tls_ms: nav.secureConnectionStart ? Math.round(nav.connectEnd - nav.secureConnectionStart) : 0,
                            ttfb_ms: Math.round(nav.responseStart - nav.requestStart),
                            download_ms: Math.round(nav.responseEnd - nav.responseStart),
                            dom_ready_ms: Math.round(nav.domContentLoadedEventEnd),
                            // Dua angka ini memisahkan dua hal yang sangat berbeda:
                            // dom_interactive = HTML selesai diurai dan seluruh
                            // <script> selesai diunduh + dieksekusi.
                            // dcl_handlers   = lama SELURUH $(document).ready()
                            // berjalan. Kalau yang membengkak yang kedua, biang
                            // lambatnya ada di kode kita, bukan di ukuran file JS.
                            dom_interactive_ms: Math.round(nav.domInteractive),
                            dcl_handlers_ms: Math.round(nav.domContentLoadedEventEnd - nav.domContentLoadedEventStart),
                            resource_count: resources.length,
                            slowest_resource: slowest ? String(slowest.name).slice(0, 300) : null,
                            slowest_resource_ms: slowest ? Math.round(slowest.duration) : null,
                            hidden_saat_muat: hiddenSaatMuat,
                            connection: conn.effectiveType || null,
                            downlink_mbps: typeof conn.downlink === 'number' ? conn.downlink : null
                        };

                        var token = document.querySelector('meta[name="csrf-token"]');

                        fetch(@json(url('/erp/client-timing')), {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': token ? token.getAttribute('content') : ''
                            },
                            body: JSON.stringify(payload),
                            keepalive: true
                        }).catch(function () {});
                    } catch (e) {
                        // Diagnostik tidak boleh merusak halaman.
                    }
                }, 1000);
            });
        })();
    </script>
</body>

</html>
