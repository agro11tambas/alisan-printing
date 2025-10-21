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
    <!--! The above 6 meta tags *must* come first in the head; any other head content must come *after* these tags !-->
    <!--! BEGIN: Apps Title-->
    <title>Alisan</title>
    <!--! END:  Apps Title-->
    <!--! BEGIN: Favicon-->
    <link rel="shortcut icon" type="image/x-icon" href="#">
    <!--! END: Favicon-->
    <!--! BEGIN: Bootstrap CSS-->
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/bootstrap.min.css') }}">


    <!--! END: Bootstrap CSS-->
    <!--! BEGIN: Vendors CSS-->
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/vendors/css/vendors.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/vendors/css/dataTables.bs5.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/vendors/css/tagify.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/vendors/css/tagify-data.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/vendors/css/select2.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/vendors/css/select2-theme.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/vendors/css/jquery.steps.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/vendors/css/quill.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/vendors/css/datepicker.min.css') }}">
    <!--! END: Vendors CSS-->
    <!--! BEGIN: Custom CSS-->
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/theme.min.css') }}">
    <!--! END: Custom CSS-->
    <!--! HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries !-->
    <!--! WARNING: Respond.js doesn"t work if you view the page via file: !-->
    <!--[if lt IE 9]>
   <script src="https:oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
   <script src="https:oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
  <![endif]-->

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

        /* Mobile view: biar layout tidak pecah */
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
            z-index: 1050 !important;
        }
    </style>


    @stack('styles')

</head>

<body>
    <!--! ================================================================ !-->
    @include('erp.layouts.components.sidebar')

    @include('erp.layouts.components.topbar')
    <!--! ================================================================ !-->
    <!--! [Start] Main Content !-->
    <!--! ================================================================ !-->
    @yield('account')
    <main class="nxl-container apps-container">
        <div class="nxl-content" style="min-height: 90vh;">
            <!-- [ page-header ] start -->
            @yield('breadcrumb')
            <!-- [ page-header ] end -->
            <!-- [ Main Content ] start -->
            @yield('content')
            <!-- [ Main Content ] end -->
        </div>
        <!-- [ Footer ] start -->
        @include('erp.layouts.components.footer')
        <!-- [ Footer ] end -->
    </main>

    @stack('modals')

    <!--! ================================================================ !-->
    <!--! [End] Main Content !-->
    <!--! ================================================================ !-->
    <!--! ================================================================ !-->
    <!--! Footer Script !-->
    <!--! ================================================================ !-->
    <!--! BEGIN: Vendors JS !-->
    <script src="{{ asset('assets/vendors/js/vendors.min.js') }}"></script>
    <!-- vendors.min.js {always must need to be top} -->
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
    <!--! END: Vendors JS !-->
    <!--! BEGIN: Apps Init  !-->
    <script src="{{ asset('assets/js/common-init.min.js') }}"></script>
    <script src="{{ asset('assets/js/customers-init.min.js') }}"></script>
    <script src="{{ asset('assets/js/proposal-create-init.min.j') }}s"></script>
    <script src="{{ asset('assets/js/invoice-view-init.min.js') }}"></script>
    <!--! END: Apps Init !-->
    <!--! BEGIN: Theme Customizer  !-->
    <script src="{{ asset('assets/js/theme-customizer-init.min.js') }}"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/js/lightbox.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

    <link rel="stylesheet" href="https://cdn.datatables.net/scroller/2.4.1/css/scroller.dataTables.min.css">
    <script src="https://cdn.datatables.net/scroller/2.4.1/js/dataTables.scroller.min.js"></script>


    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // default collapsed
            document.documentElement.classList.add("minimenu");

            const nav = document.querySelector(".nxl-navigation .navbar-content");
            if (!nav) return;

            // Tangani klik menu
            nav.addEventListener("click", function(e) {
                const toggler = e.target.closest(".nxl-link");
                if (!toggler) return;

                const item = toggler.closest(".nxl-hasmenu");
                if (!item) return;

                const submenu = item.querySelector(":scope > .nxl-submenu");
                if (!submenu) return; // kalau bukan menu yg ada anaknya, biarin navigate

                const href = toggler.getAttribute("href");

                if (!item.classList.contains("open")) {
                    // klik pertama → buka submenu, cegah link
                    e.preventDefault();
                    item.classList.add("open");

                    // Tutup hanya submenu di level yg sama
                    item.parentElement.querySelectorAll(":scope > .nxl-hasmenu.open").forEach(function(
                        other) {
                        if (other !== item) other.classList.remove("open");
                    });
                } else {
                    // klik kedua
                    if (href === "#" || href === "javascript:void(0);") {
                        e.preventDefault(); // dummy link → cegah
                        item.classList.remove("open");
                    }
                }
            });

            // ===== Tambahan: Auto expand on hover =====
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

    @stack('scripts')
</body>

</html>
