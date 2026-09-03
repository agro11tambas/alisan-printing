<nav class="nxl-navigation">
    <div class="navbar-wrapper">
        <div class="m-header" style="padding: 8px 15px !important; height: 60px !important;">
            {{-- Sebelumnya dua <img src="#"> di sini. Bagi browser "#" berarti
                 alamat halaman yang sedang dibuka, bukan gambar kosong: setiap
                 halaman ERP jadi diunduh ulang dua kali lalu dibuang. Laporan
                 waktu muat dari browser pengguna 31 Agustus 2026 merekamnya —
                 /erp/welcome termuat sebagai resource selama 2.069 ms padahal
                 server menjawab dalam 93 ms.

                 Diganti teks, bukan gambar: tidak ada request tambahan sama
                 sekali, dan tidak bergantung pada file logo yang bisa berpindah.
                 logo-lg tampil saat sidebar terbuka, logo-sm saat menyempit.
                 Nama ditulis langsung, bukan config('app.name'): kalau APP_NAME
                 di server tidak diisi, yang tampil jadi "Laravel". --}}
            <a href="/erp/welcome" class="b-brand">
                <span class="logo logo-lg fw-bold fs-5">Alisan Printing</span>
                <span class="logo logo-sm fw-bold fs-5">A</span>
            </a>
        </div>
        <div class="navbar-content">
            <ul class="nxl-navbar">
                <li class="nxl-item nxl-caption">
                    <label>Navigation</label>
                </li>
                @if (Auth::check() && Auth::user()->hasPermission('dashboard'))
                    <li class="nxl-item nxl-hasmenu">
                        <a href="/erp/dashboard" class="nxl-link {{ request()->is('/erp/dashboard') ? 'active' : '' }}">
                            <span class="nxl-micon"><i class="feather-airplay"></i></span>
                            <span class="nxl-mtext ">Dashboards</span><span class="nxl-arrow"></span>
                        </a>
                    </li>
                @endif
                @if (Auth::check() && (Auth::user()->hasSubPermission('product-list') || Auth::user()->hasSubPermission('product-categories')))
                    <li
                        class="nxl-item nxl-hasmenu {{ request()->is('erp/ecommerce-products*') || request()->is('erp/ecommerce-product-categories*') || request()->is('erp/ecommerce-information*') ? 'active' : '' }}">
                        <a href="javascript:void(0);" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-shopping-cart"></i></span>
                            <span class="nxl-mtext ">Ecommerce</span><span class="nxl-arrow"><i
                                    class="feather-chevron-right"></i></span>
                        </a>
                        <ul class="nxl-submenu">
                            <li class="nxl-item {{ request()->is('erp/ecommerce-products*') ? 'active' : '' }}"><a
                                    class="nxl-link" href="/erp/ecommerce-products"><span class="">Ecommerce
                                        Product</span></a></li>
                            <li
                                class="nxl-item {{ request()->is('erp/ecommerce-product-categories*') ? 'active' : '' }}">
                                <a class="nxl-link" href="/erp/ecommerce-product-categories"><span
                                        class="">Ecommerce Category</span></a>
                            </li>
                            <li
                                class="nxl-item {{ request()->is('erp/ecommerce-information*') ? 'active' : '' }}">
                                <a class="nxl-link" href="/erp/ecommerce-information"><span
                                        class="">Information</span></a>
                            </li>
                        </ul>
                    </li>
                @endif
                @if (Auth::check() && Auth::user()->hasPermission('products'))
                    <li
                        class="nxl-item nxl-hasmenu {{ request()->is('products/product-list*') || request()->is('products/categories*') || request()->is('products/tags*') || request()->is('products/units*') || request()->is('products/product-bundles*') ? 'active' : '' }}">
                        <a href="javascript:void(0);" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-briefcase"></i></span>
                            <span class="nxl-mtext ">Products</span><span class="nxl-arrow"><i
                                    class="feather-chevron-right"></i></span>
                        </a>
                        <ul class="nxl-submenu">
                            @if (Auth::user()->hasSubPermission('product-list'))
                                <li class="nxl-item {{ request()->is('products/product-list*') ? 'active' : '' }}"><a
                                        class="nxl-link" href="/erp/products"><span class="">Produk</span></a>
                                </li>
                            @endif
                            @if (Auth::user()->hasSubPermission('product-bundles'))
                                <li class="nxl-item {{ request()->is('products/product-bundles*') ? 'active' : '' }}"><a
                                        class="nxl-link" href="/erp/products/product-bundles"><span
                                            class="">Produk Bundle</span></a></li>
                            @endif
                            @if (Auth::user()->hasSubPermission('product-categories'))
                                <li class="nxl-item {{ request()->is('products/categories*') ? 'active' : '' }}"><a
                                        class="nxl-link" href="/erp/products/categories"><span class="">Kategori
                                            Produk</span></a></li>
                            @endif
                            @if (Auth::user()->hasSubPermission('product-tags'))
                                <li class="nxl-item {{ request()->is('products/tags*') ? 'active' : '' }}"><a
                                        class="nxl-link" href="/erp/products/tags"><span class="">Merek
                                            Produk</span></a></li>
                            @endif
                            @if (Auth::user()->hasSubPermission('product-units'))
                                <li class="nxl-item {{ request()->is('products/units*') ? 'active' : '' }}"><a
                                        class="nxl-link" href="/erp/products/units"><span class="">Satuan
                                            Produk</span></a></li>
                            @endif

                            @if (Auth::user()->hasSubPermission('price-modes'))
                                <li class="nxl-item {{ request()->is('products/price-modes*') ? 'active' : '' }}">
                                    <a class="nxl-link" href="/erp/products/price-modes"><span>Mode</span></a>
                                </li>
                            @endif

                        </ul>
                    </li>
                @endif
                @if (Auth::check() && Auth::user()->hasPermission('adjustment'))
                    <li
                        class="nxl-item nxl-hasmenu {{ request()->is('adjustment-products/canceled-products*') || request()->is('adjustment-products/defect-products*') ? 'active' : '' }}">
                        <a href="javascript:void(0);" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-briefcase"></i></span>
                            <span class="nxl-mtext ">Adjustment Products</span><span class="nxl-arrow"><i
                                    class="feather-chevron-right"></i></span>
                        </a>
                        <ul class="nxl-submenu">
                            @if (Auth::user()->hasSubPermission('canceled'))
                                <li
                                    class="nxl-item {{ request()->is('adjustment-products/canceled-products*') ? 'active' : '' }}">
                                    <a class="nxl-link" href="/erp/adjustment-products/canceled-products"><span
                                            class="">Canceled Product</span></a>
                                </li>
                            @endif
                            @if (Auth::user()->hasSubPermission('defect'))
                                <li
                                    class="nxl-item {{ request()->is('adjustment-products/defect-products*') ? 'active' : '' }}">
                                    <a class="nxl-link" href="/erp/adjustment-products/defect-products"><span
                                            class="">Defect Product</span></a>
                                </li>
                            @endif
                            @if (Auth::user()->hasSubPermission('reject'))
                                <li
                                    class="nxl-item {{ request()->is('adjustment-products/reject-products*') ? 'active' : '' }}">
                                    <a class="nxl-link" href="/erp/adjustment-products/reject-products"><span
                                            class="">QC Reject Product</span></a>
                                </li>
                            @endif
                        </ul>
                    </li>
                @endif
                @if (Auth::check() && Auth::user()->hasPermission('discounts'))
                    <li class="nxl-item nxl-hasmenu">
                        <a href="/erp/discounts" class="nxl-link {{ request()->is('discounts*') ? 'active' : '' }}">
                            <span class="nxl-micon"><i class="feather-dollar-sign"></i></span>
                            <span class="nxl-mtext ">Diskon</span><span class="nxl-arrow"></span>
                        </a>
                    </li>
                @endif
                @if (Auth::check() && Auth::user()->hasPermission('inventory'))
                    <li class="nxl-item nxl-hasmenu">
                        <a href="javascript:void(0);" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-briefcase"></i></span>
                            <span class="nxl-mtext ">Inventory</span><span class="nxl-arrow"><i
                                    class="feather-chevron-right"></i></span>
                        </a>
                        <ul class="nxl-submenu">
                            @if (Auth::user()->hasSubPermission('opening-stock-rate'))
                                <li class="nxl-item"><a class="nxl-link" href="/erp/opening-stock">Opening Stock &
                                        Rate</a></li>
                            @endif
                            @if (Auth::user()->hasSubPermission('stock-opname'))
                                <li class="nxl-item"><a class="nxl-link" href="/erp/inventory/stock-opname">Stock Opname
                                        Warehouse</a></li>
                            @endif
                            @if (Auth::user()->hasSubPermission('stock-opname-production'))
                                <li class="nxl-item"><a class="nxl-link" href="/erp/productions/stock-opname">Stock
                                        Opname Production</a></li>
                            @endif
                            @if (Auth::user()->hasSubPermission('inventory-report-items'))
                                <li class="nxl-item"><a class="nxl-link" href="/erp/report-items">Report Items</a></li>
                            @endif
                        </ul>
                    </li>
                @endif
                @if (Auth::check() && Auth::user()->hasPermission('sales'))
                    <li class="nxl-item nxl-hasmenu">
                        <a href="javascript:void(0);" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-shopping-cart"></i></span>
                            <span class="nxl-mtext ">Sales</span><span class="nxl-arrow"><i
                                    class="feather-chevron-right"></i></span>
                        </a>
                        <ul class="nxl-submenu">
                            @if (Auth::user()->hasSubPermission('sale-orders'))
                                <li class="nxl-item"><a
                                        class="nxl-link {{ request()->is('sales/sale-orders*') ? 'active' : '' }}"
                                        href="/erp/sales/sale-orders"><span class="">Sale Orders
                                            (Draf)</span></a></li>
                            @endif
                            @if (Auth::user()->hasSubPermission('sale-list'))
                                <li class="nxl-item "><a
                                        class="nxl-link {{ request()->is('sales/sale-list*') ? 'active' : '' }}"
                                        href="/erp/sales/sale-list"><span class="">Sale List</span></a></li>
                            @endif
                            @if (Auth::user()->hasSubPermission('sale-returns'))
                                <li class="nxl-item"><a
                                        class="nxl-link {{ request()->is('sales/sale-returns*') ? 'active' : '' }}"
                                        href="/erp/sales/sale-returns"><span class="">Sale Return</span></a>
                                </li>
                            @endif
                        </ul>
                    </li>
                @endif
                @if (Auth::check() && Auth::user()->hasPermission('design'))
                    <li class="nxl-item nxl-hasmenu">
                        <a href="/erp/design" class="nxl-link {{ request()->is('/erp/design') ? 'active' : '' }}">
                            <span class="nxl-micon"><i class="feather-box"></i></span>
                            <span class="nxl-mtext ">Design</span><span class="nxl-arrow"></span>
                        </a>
                    </li>
                @endif
                @if (Auth::check() &&
                        (Auth::user()->hasPermission('production') ||
                            Auth::user()->hasPermission('operator') ||
                            Auth::user()->hasPermission('machine')))
                    <li class="nxl-item nxl-hasmenu">
                        <a href="javascript:void(0);" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-stop-circle"></i></span>
                            <span class="nxl-mtext ">Production</span><span class="nxl-arrow"><i
                                    class="feather-chevron-right"></i></span>
                        </a>
                        <ul class="nxl-submenu">
                            @if (Auth::user()->hasSubPermission('waiting-list'))
                                <li class="nxl-item"><a
                                        class="nxl-link {{ request()->is('productions/waiting-list') || request()->is('productions/waiting-list/*') ? 'active' : '' }}"
                                        href="/erp/productions/waiting-list"><span class="">Waiting
                                            List</span></a></li>
                            @endif
                            @if (Auth::user()->hasSubPermission('assign-list'))
                                <li class="nxl-item"><a
                                        class="nxl-link {{ request()->is('productions/waiting-list/assign-list') || request()->is('productions/waiting-list/assign-list/*') ? 'active' : '' }}"
                                        href="/erp/productions/waiting-list/assign-list"><span class="">Assign
                                            List</span></a></li>
                            @endif
                            @if (Auth::user()->hasSubPermission('request-stocks'))
                                <li class="nxl-item "><a
                                        class="nxl-link {{ request()->is('productions/request-stocks*') ? 'active' : '' }}"
                                        href="/erp/productions/material-request"><span class="">Request
                                            Stocks</span></a></li>
                            @endif
                            @if (Auth::user()->hasSubPermission('report-items'))
                                <li class="nxl-item"><a
                                        class="nxl-link {{ request()->is('productions/report-items*') ? 'active' : '' }}"
                                        href="/erp/productions/report-items"><span class="">Report
                                            Items</span></a></li>
                            @endif
                            @if (Auth::user()->hasSubPermission('snapshot-report'))
                                <li class="nxl-item"><a
                                        class="nxl-link {{ request()->is('productions/snapshot-report*') ? 'active' : '' }}"
                                        href="/erp/productions/snapshot-report"><span class="">Snapshot
                                            Report</span></a></li>
                            @endif
                            @if (Auth::user()->hasSubPermission('stock-in-production'))
                                <li class="nxl-item "><a class="nxl-link" href="/erp/productions/stock-in"><span
                                            class="">Stock In</span></a></li>
                            @endif
                            @if (Auth::user()->hasPermission('operator'))
                                <li class="nxl-item"><a
                                        class="nxl-link {{ request()->is('erp/shop-manager/operators*') ? 'active' : '' }}"
                                        href="/erp/shop-manager/operators"><span class="">Operator</span></a></li>
                            @endif
                            @if (Auth::user()->hasPermission('machine'))
                                <li class="nxl-item"><a
                                        class="nxl-link {{ request()->is('erp/productions/machines*') ? 'active' : '' }}"
                                        href="/erp/productions/machines"><span class="">Mesin</span></a></li>
                            @endif
                        </ul>
                    </li>
                @endif
                @if (Auth::check() && Auth::user()->hasPermission('delivery'))
                    <li class="nxl-item nxl-hasmenu">
                        <a href="javascript:void(0);" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-truck"></i></span>
                            <span class="nxl-mtext ">Deliveries</span><span class="nxl-arrow"><i
                                    class="feather-chevron-right"></i></span>
                        </a>
                        <ul class="nxl-submenu">
                            @if (Auth::user()->hasSubPermission('delivery-orders'))
                                <li class="nxl-item "><a
                                        class="nxl-link {{ request()->is('deliveries/delivery-orders*') ? 'active' : '' }}"
                                        href="/erp/deliveries/delivery-orders"><span class="">Delivery
                                            List</span></a></li>
                            @endif
                            @if (Auth::user()->hasSubPermission('delivery-list'))
                                <li class="nxl-item "><a
                                        class="nxl-link {{ request()->is('deliveries/delivery-list*') ? 'active' : '' }}"
                                        href="/erp/deliveries/delivery-list"><span class="">Delivery
                                            Order</span></a></li>
                            @endif
                        </ul>
                    </li>
                @endif
                @if (Auth::check() && Auth::user()->hasPermission('purchases'))
                    <li class="nxl-item nxl-hasmenu">
                        <a href="javascript:void(0);" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-package"></i></span>
                            <span class="nxl-mtext ">Purchases</span><span class="nxl-arrow"><i
                                    class="feather-chevron-right"></i></span>
                        </a>
                        <ul class="nxl-submenu">
                            @if (Auth::user()->hasSubPermission('purchase-orders'))
                                <li class="nxl-item "><a
                                        class="nxl-link {{ request()->is('purchases/purchase-orders*') || request()->is('purchases/purchase-list*') ? 'active' : '' }}"
                                        href="/erp/purchases/purchase-orders"><span class="">Purchase
                                            List</span></a></li>
                            @endif
                            @if (Auth::user()->hasSubPermission('purchase-returns'))
                                <li class="nxl-item"><a
                                        class="nxl-link {{ request()->is('purchases/purchase-returns*') ? 'active' : '' }}"
                                        href="/erp/purchases/purchase-returns"><span class="">Purchase
                                            Return</a></li>
                            @endif
                        </ul>
                    </li>
                @endif
                @if (Auth::check() && Auth::user()->hasPermission('warehouse'))
                    <li class="nxl-item nxl-hasmenu">
                        <a href="javascript:void(0);"
                            class="nxl-link {{ request()->is('inventory*') ? 'active' : '' }}">
                            <span class="nxl-micon"><i class="feather-home"></i></span>
                            <span class="nxl-mtext ">Warehouse</span><span class="nxl-arrow"><i
                                    class="feather-chevron-right"></i></span>
                        </a>
                        <ul class="nxl-submenu">
                            @if (Auth::user()->hasSubPermission('stock-in'))
                                <li class="nxl-item "><a class="nxl-link" href="/erp/inventory/stock-in"><span
                                            class="">Stock In</span></a></li>
                            @endif
                            @if (Auth::user()->hasSubPermission('stock-out'))
                                <li class="nxl-item "><a class="nxl-link" href="/erp/inventory/stock-out"><span
                                            class="">Stock Out</span></a></li>
                            @endif
                            @if (Auth::user()->hasSubPermission('warehouse-report-items'))
                                <li class="nxl-item "><a class="nxl-link" href="/erp/inventory/report-items"><span
                                            class="">Report Items</span></a></li>
                            @endif
                        </ul>
                    </li>
                @endif
                @if (Auth::check() && Auth::user()->hasPermission('expenses'))
                    <li class="nxl-item nxl-hasmenu">
                        <a href="/erp/expenses" class="nxl-link {{ request()->is('expenses*') ? 'active' : '' }}">
                            <span class="nxl-micon"><i class="feather-book"></i></span>
                            <span class="nxl-mtext ">Expense</span><span class="nxl-arrow"></span>
                        </a>
                    </li>
                @endif
                @if (Auth::check() && Auth::user()->hasPermission('capital-transaction'))
                    <li class="nxl-item nxl-hasmenu">
                        <a href="/erp/capital-transactions"
                            class="nxl-link {{ request()->is('capital-transactions*') ? 'active' : '' }}">
                            <span class="nxl-micon"><i class="feather-book"></i></span>
                            <span class="nxl-mtext ">Capital Transactions</span><span class="nxl-arrow"></span>
                        </a>
                    </li>
                @endif
                @if (Auth::check() && Auth::user()->hasPermission('accounts'))
                    <li class="nxl-item nxl-hasmenu">
                        <a href="javascript:void(0);"
                            class="nxl-link {{ request()->is('accounts*') ? 'active' : '' }}">
                            <span class="nxl-micon"><i class="feather-credit-card"></i></span>
                            <span class="nxl-mtext ">Accounts</span><span class="nxl-arrow"><i
                                    class="feather-chevron-right"></i></span>
                        </a>
                        <ul class="nxl-submenu">
                            @if (Auth::user()->hasSubPermission('manage-accounts'))
                                <li class="nxl-item "><a class="nxl-link" href="/erp/accounts"><span
                                            class="">Manage Accounts</span></a></li>
                            @endif
                            <li class="nxl-item nxl-hasmenu">
                                <a class="nxl-link" href="javascript:void(0);">
                                    <span class="">Account List</span><span class="nxl-arrow"><i
                                            class="feather-chevron-right"></i></span>
                                </a>
                                <ul class="nxl-submenu">
                                    @if (Auth::user()->hasSubPermission('account-bank'))
                                        <li class="nxl-item "><a class="nxl-link" href="/erp/accounts/bank"><span
                                                    class="">Bank</span></a></li>
                                    @endif
                                    @if (Auth::user()->hasSubPermission('account-cash'))
                                        <li class="nxl-item "><a class="nxl-link" href="/erp/accounts/cash"><span
                                                    class="">Cash</span></a></li>
                                    @endif
                                    @if (Auth::user()->hasSubPermission('account-sale'))
                                        <li class="nxl-item "><a class="nxl-link" href="/erp/accounts/sale"><span
                                                    class="">Sale</span></a></li>
                                    @endif
                                    @if (Auth::user()->hasSubPermission('account-purchase'))
                                        <li class="nxl-item "><a class="nxl-link" href="/erp/accounts/purchase"><span
                                                    class="">Purchase</span></a></li>
                                    @endif
                                    @if (Auth::user()->hasSubPermission('account-expense'))
                                        <li class="nxl-item "><a class="nxl-link" href="/erp/accounts/expense"><span
                                                    class="">Expense</span></a></li>
                                    @endif
                                    @if (Auth::user()->hasSubPermission('account-capital'))
                                        <li class="nxl-item "><a class="nxl-link" href="/erp/accounts/capital"><span
                                                    class="">Capital</span></a></li>
                                    @endif
                                    @if (Auth::user()->hasSubPermission('account-customer-deposit'))
                                        <li class="nxl-item "><a class="nxl-link"
                                                href="/erp/accounts/customer-deposit"><span class="">Customer
                                                    Deposit</span></a></li>
                                    @endif
                                </ul>
                            </li>
                            @if (Auth::user()->hasSubPermission('manage-opening-balance'))
                                <li class="nxl-item "><a class="nxl-link" href="/erp/accounts/opening-balance"><span
                                            class="">Manage Opening Balance</span></a></li>
                            @endif
                        </ul>
                    </li>
                @endif
                @if (Auth::check() && Auth::user()->hasPermission('fifo-cost'))
                    <li class="nxl-item nxl-hasmenu">
                        <a href="javascript:void(0)" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-layers"></i></span>
                            <span class="nxl-mtext ">HPP FIFO</span><span class="nxl-arrow"><i
                                    class="feather-chevron-right"></i></span>
                        </a>
                        <ul class="nxl-submenu">
                            @if (Auth::user()->hasSubPermission('cost-layers'))
                                <li class="nxl-item"><a
                                        class="nxl-link {{ request()->is('hpp/batch-purchase*') ? 'active' : '' }}"
                                        href="/erp/hpp/batch-purchase"><span class="">Batch Purchase</span></a></li>
                            @endif
                            @if (Auth::user()->hasSubPermission('cost-consumptions'))
                                <li class="nxl-item"><a
                                        class="nxl-link {{ request()->is('hpp/rincian*') ? 'active' : '' }}"
                                        href="/erp/hpp/rincian"><span class="">Rincian HPP</span></a></li>
                            @endif
                        </ul>
                    </li>
                @endif
                @if (Auth::check() && Auth::user()->hasPermission('financial-report'))
                    <li class="nxl-item nxl-hasmenu">
                        <a href="javascript:void(0)" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-file-text"></i></span>
                            <span class="nxl-mtext ">Financial Report</span><span class="nxl-arrow"><i
                                    class="feather-chevron-right"></i></span>
                        </a>
                        <ul class="nxl-submenu">
                            <li class="nxl-item"><a
                                    class="nxl-link {{ request()->is('financial-report/profit-loss*') ? 'active' : '' }}"
                                    href="/erp/financial-report/profit-loss"><span class="">Profit &
                                        Loss</span></a></li>
                            <li class="nxl-item"><a
                                    class="nxl-link {{ request()->is('financial-report/profit-loss/daily*') ? 'active' : '' }}"
                                    href="/erp/financial-report/profit-loss/daily"><span class="">Profit & Loss
                                        Daily</span></a></li>
                        </ul>
                    </li>
                @endif
                {{-- @if (Auth::check() && Auth::user()->hasPermission('shop-manager'))
                    <li class="nxl-item nxl-hasmenu">
                        <a href="/erp/shop-manager/users" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-user-check"></i></span>
                            <span class="nxl-mtext ">Shop Manager</span><span class="nxl-arrow"></span>
                        </a>
                    </li>
                @endif
                @if (Auth::check() && Auth::user()->hasPermission('operator'))
                    <li class="nxl-item nxl-hasmenu">
                        <a href="/erp/shop-manager/operators" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-user-check"></i></span>
                            <span class="nxl-mtext ">Operator</span><span class="nxl-arrow"></span>
                        </a>
                    </li>
                @endif
                @if (Auth::check() && Auth::user()->hasPermission('customer'))
                    <li class="nxl-item nxl-hasmenu">
                        <a href="/erp/customers" class="nxl-link {{ request()->is('customers*') ? 'active' : '' }}">
                            <span class="nxl-micon"><i class="feather-users"></i></span>
                            <span class="nxl-mtext ">Customers</span><span class="nxl-arrow"></span>
                        </a>
                    </li>
                @endif
                @if (Auth::check() && Auth::user()->hasPermission('supplier'))
                    <li class="nxl-item nxl-hasmenu">
                        <a href="/erp/suppliers" class="nxl-link {{ request()->is('suppliers*') ? 'active' : '' }}">
                            <span class="nxl-micon"><i class="feather-users"></i></span>
                            <span class="nxl-mtext ">Suppliers</span><span class="nxl-arrow"></span>
                        </a>
                    </li>
                @endif --}}
                @if (Auth::check() &&
                        (Auth::user()->hasPermission('shop-manager') ||
                            Auth::user()->hasPermission('customer') ||
                            Auth::user()->hasPermission('supplier')))
                    <li class="nxl-item nxl-hasmenu">
                        <a href="javascript:void(0)" class="nxl-link">
                            <span class="nxl-micon"><i class="feather-users"></i></span>
                            <span class="nxl-mtext">Users</span>
                            <span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
                        </a>

                        <ul class="nxl-submenu">
                            @if (Auth::user()->hasPermission('shop-manager'))
                                <li class="nxl-item">
                                    <a href="/erp/shop-manager/users"
                                        class="nxl-link {{ request()->is('erp/shop-manager/users*') ? 'active' : '' }}">
                                        <span>Shop Manager</span>
                                    </a>
                                </li>
                            @endif

                            @if (Auth::user()->hasPermission('customer'))
                                <li class="nxl-item">
                                    <a href="/erp/customers"
                                        class="nxl-link {{ request()->is('erp/customers*') || request()->is('erp/customer-accounts*') ? 'active' : '' }}">
                                        <span>Customer</span>
                                    </a>
                                </li>
                            @endif

                            @if (Auth::user()->hasPermission('supplier'))
                                <li class="nxl-item">
                                    <a href="/erp/suppliers"
                                        class="nxl-link {{ request()->is('erp/suppliers*') ? 'active' : '' }}">
                                        <span>Suppliers</span>
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </li>
                @endif
                @if (Auth::check() && Auth::user()->hasPermission('invoice'))
                    <li class="nxl-item nxl-hasmenu">
                        <a href="/erp/invoices" class="nxl-link {{ request()->is('invoices*') ? 'active' : '' }}">
                            <span class="nxl-micon"><i class="feather-credit-card"></i></span>
                            <span class="nxl-mtext ">Invoice Setting</span><span class="nxl-arrow"></span>
                        </a>
                    </li>
                @endif
                @if (Auth::check() && Auth::user()->hasPermission('settings'))
                    <li class="nxl-item nxl-hasmenu">
                        <a href="/erp/settings"
                            class="nxl-link {{ request()->is('erp/settings*') ? 'active' : '' }}">
                            <span class="nxl-micon"><i class="feather-settings"></i></span>
                            <span class="nxl-mtext ">Settings</span><span class="nxl-arrow"></span>
                        </a>
                    </li>
                    <li class="nxl-item nxl-hasmenu">
                        <a href="/erp/reset-stock"
                            class="nxl-link {{ request()->is('erp/reset-stock*') ? 'active' : '' }}">
                            <span class="nxl-micon"><i class="feather-refresh-cw"></i></span>
                            <span class="nxl-mtext">Reset Stock</span><span class="nxl-arrow"></span>
                        </a>
                    </li>
                @endif
            </ul>
        </div>
    </div>
</nav>
