<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DeliveredController;
use App\Http\Controllers\Admin\ExpenseController;
use App\Http\Controllers\Admin\HistoryProgressOrderController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\OrderDetailController;
use App\Http\Controllers\Admin\ProductCategoriesController;
use App\Http\Controllers\Admin\ProductTagController;
use App\Http\Controllers\Admin\ProductsController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\DiscountController;
use App\Http\Controllers\Admin\FinancialStatementController;
use App\Http\Controllers\Admin\OpeningBalanceController;
use App\Http\Controllers\Admin\SaleListController;
use App\Http\Controllers\Admin\WaitingListController;
use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\AccountListController;
use App\Http\Controllers\Admin\CanceledProductController;
use App\Http\Controllers\Admin\CapitalTransactionController;
use App\Http\Controllers\Admin\DefectProductController;
use App\Http\Controllers\Admin\DefectProductHistoryController;
use App\Http\Controllers\Admin\DeliveryListController;
use App\Http\Controllers\Admin\DeliveryOrderController;
use App\Http\Controllers\Admin\DesignController;
use App\Http\Controllers\Admin\DesignItemController;
use App\Http\Controllers\Admin\HistoryRequestStockController;
use App\Http\Controllers\Admin\HistoryStockInController;
use App\Http\Controllers\Admin\HistoryStockOutController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\MaterialRequestController;
use App\Http\Controllers\Admin\OpeningStockProductionController;
use App\Http\Controllers\Admin\OpeningStockRateController;
use App\Http\Controllers\Admin\OperatorController;
use App\Http\Controllers\Admin\OrderProgressAssignController;
use App\Http\Controllers\Admin\ProductBundleController;
use App\Http\Controllers\Admin\ProductionController;
use App\Http\Controllers\Admin\ProductionStockInController;
use App\Http\Controllers\Admin\ProductionStockSnapshotController;
use App\Http\Controllers\Admin\PurchaseDetailController;
use App\Http\Controllers\Admin\PurchaseListController;
use App\Http\Controllers\Admin\PurchaseOrderController;
use App\Http\Controllers\Admin\WelcomeController;
use App\Http\Controllers\Admin\PurchaseProductController;
use App\Http\Controllers\Admin\PurchaseReturnController;
use App\Http\Controllers\Admin\RejectProductController;
use App\Http\Controllers\Admin\RejectProductHistoryController;
use App\Http\Controllers\Admin\ReportItemsProductionAndWarehouseController;
use App\Http\Controllers\Admin\ReportItemsProductionController;
use App\Http\Controllers\Admin\SaleOrderController;
use App\Http\Controllers\Admin\SaleReturnController;
use App\Http\Controllers\Admin\StockOpnameController;
use App\Http\Controllers\Admin\StockOpnameProductionController;
use App\Http\Controllers\Admin\StockRequestController;
use App\Http\Controllers\AuthController;

use App\Http\Controllers\UserController;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\isLogin;
use App\Models\CanceledProduct;
use App\Models\DefectProduct;
use App\Services\InvoiceNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Can;

Route::get('/', function () {
    return redirect('/erp/welcome');
});

Route::get('/login', [AuthController::class, 'loginView'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);

Route::middleware(['auth', 'check.session'])->group(function () {
    Route::get('/erp/welcome', [WelcomeController::class, 'index']);
    Route::middleware(['auth', 'permission:dashboard'])->group(function () {
        Route::get('/erp/dashboard', [DashboardController::class, 'getDashboard'])->name('dashboard');
        Route::get('/erp/dashboard/summary', [DashboardController::class, 'summary'])->name('dashboard.summary');
    });

    Route::middleware(['auth', 'permission:products'])->group(function () {
        Route::middleware(['auth', 'subpermission:product-categories'])->group(function () {
            Route::get('/erp/products/categories/data', [ProductCategoriesController::class, 'data']);
            Route::get('/erp/products/categories', [ProductCategoriesController::class, 'index']);
            Route::get('/erp/products/categories/create-category', [ProductCategoriesController::class, 'create']);
            Route::post('/erp/products/categories/store', [ProductCategoriesController::class, 'store']);
            Route::get('/erp/products/categories/edit-category/{id}', [ProductCategoriesController::class, 'edit']);
            Route::put('/erp/products/categories/update/{id}', [ProductCategoriesController::class, 'update']);
            Route::delete('/erp/products/categories/delete/{id}', [ProductCategoriesController::class, 'delete']);
        });

        Route::middleware(['auth', 'subpermission:product-tags'])->group(function () {
            Route::get('/erp/products/tags/data', [ProductTagController::class, 'data']);
            Route::get('/erp/products/tags', [ProductTagController::class, 'index']);
            Route::get('/erp/products/tags/create-tag', [ProductTagController::class, 'create']);
            Route::post('/erp/products/tags/store', [ProductTagController::class, 'store']);
            Route::get('/erp/products/tags/edit-tag/{id}', [ProductTagController::class, 'edit']);
            Route::put('/erp/products/tags/update/{id}', [ProductTagController::class, 'update']);
            Route::delete('/erp/products/tags/delete/{id}', [ProductTagController::class, 'delete']);
        });

        Route::middleware(['auth', 'subpermission:product-list'])->group(function () {
            Route::get('/erp/products/data', [ProductsController::class, 'data']);
            Route::get('/erp/products', [ProductsController::class, 'index']);
            Route::get('/erp/products/create-product', [ProductsController::class, 'create']);
            Route::post('/erp/products/store', [ProductsController::class, 'store']);
            Route::get('/erp/products/edit-product/{id}', [ProductsController::class, 'edit']);
            Route::put('/erp/products/update/{id}', [ProductsController::class, 'update']);
            Route::delete('/erp/products/delete/{id}', [ProductsController::class, 'delete']);
            Route::post('/erp/products/generate-combinations', [ProductsController::class, 'generateCombinations']);
            Route::get('/erp/products/{id}/combinations', [ProductsController::class, 'getCombinations']);
        });

        Route::middleware(['auth', 'subpermission:product-bundles'])->group(function () {
            Route::get('/erp/products/product-bundles/data', [ProductBundleController::class, 'dataProductBundles']);
            Route::get('/erp/products/product-bundles', [ProductBundleController::class, 'getProductBundles']);
            Route::get('/erp/products/product-bundles/create-product-bundle', [ProductBundleController::class, 'create']);
            Route::post('/erp/products/product-bundles/store', [ProductBundleController::class, 'store']);
            Route::get('/erp/products/product-bundles/edit-product-bundle/{id}', [ProductBundleController::class, 'edit']);
            Route::put('/erp/products/product-bundles/update/{id}', [ProductBundleController::class, 'update']);
            Route::delete('/erp/products/product-bundles/delete/{id}', [ProductBundleController::class, 'delete']);
            Route::get('/search-products', [ProductBundleController::class, 'search']);
        });
    });

    Route::middleware(['auth', 'permission:adjustment'])->group(function () {
        Route::middleware(['auth', 'subpermission:canceled'])->group(function () {
            Route::get('/erp/adjustment-products/canceled-products', [CanceledProductController::class, 'getCanceledProducts']);
            Route::get('/erp/adjustment-products/canceled-products/data', [CanceledProductController::class, 'dataCanceledProducts']);
            Route::post('/erp/adjustment-products/canceled-products/return-to-warehouse/{id}', [CanceledProductController::class, 'returnToWarehouse']);
            Route::get('/erp/adjustment-products/canceled-products/detail/{id}', [CanceledProductController::class, 'detailCanceledProducts']);
            Route::get('/erp/adjustment-products/canceled-products/detail/{id}/data', [CanceledProductController::class, 'dataDetailCanceledProducts']);
            Route::get('/erp/adjustment-products/canceled-products/history/{id}', [CanceledProductController::class, 'getCanceledProductHistory']);
            Route::get('/erp/adjustment-products/canceled-products/history/{id}/data', [CanceledProductController::class, 'dataCanceledProductHistory']);
        });

        Route::middleware(['auth', 'subpermission:defect'])->group(function () {
            Route::get('/erp/adjustment-products/defect-products', [DefectProductController::class, 'getDefectProducts']);
            Route::get('/erp/adjustment-products/defect-products/data', [DefectProductController::class, 'dataDefectProducts']);
            Route::get('/erp/adjustment-products/defect-products/detail-defect-products/{id}', [DefectProductController::class, 'detailDefectProducts'])->name('erp.defect-products.details');
            Route::get('/erp/adjustment-products/defect-products/detail-defect-products/data/{id}', [DefectProductController::class, 'dataDetailDefectProducts']);
            Route::post('/erp/adjustment-products/defect-products/return-to-supplier/{id}', [DefectProductHistoryController::class, 'returnToSupplier']);
            Route::post('/erp/adjustment-products/defect-products/eliminate/{id}', [DefectProductHistoryController::class, 'eliminate']);
            Route::get('/erp/adjustment-products/defect-products/history/{id}', [DefectProductHistoryController::class, 'historyPage'])->name('erp.defect-products.history');
            Route::get('/erp/adjustment-products/defect-products/history/data/{id}', [DefectProductHistoryController::class, 'dataHistory']);
        });

        Route::middleware(['auth', 'subpermission:reject'])->group(function () {
            Route::get('/erp/adjustment-products/reject-products', [RejectProductController::class, 'getRejectProducts']);
            Route::get('/erp/adjustment-products/reject-products/data', [RejectProductController::class, 'dataRejectProducts']);
            Route::get('/erp/adjustment-products/reject-products/detail-reject-products/{id}', [RejectProductController::class, 'detailRejectProducts'])->name('erp.reject-products.details');
            Route::get('/erp/adjustment-products/reject-products/detail-reject-products/data/{id}', [RejectProductController::class, 'dataDetailRejectProducts']);
            Route::post('/erp/adjustment-products/reject-products/return-to-warehouse/{id}', [RejectProductController::class, 'returnToWarehouse']);
            Route::get('/erp/adjustment-products/reject-products/history/{id}', [RejectProductHistoryController::class, 'rejectHistoryPage'])->name('erp.reject-products.history');
            Route::get('/erp/adjustment-products/reject-products/history/data/{id}', [RejectProductHistoryController::class, 'dataRejectHistory']);
        });
    });

    Route::middleware(['auth', 'permission:discounts'])->group(function () {
        Route::get('/erp/discounts/data', [DiscountController::class, 'dataDiscount']);
        Route::get('/erp/discounts', [DiscountController::class, 'getDiscount']);
        Route::get('/erp/discounts/create-discount', [DiscountController::class, 'create']);
        Route::post('/erp/discounts/store', [DiscountController::class, 'store']);
        Route::get('/erp/discounts/edit-discount/{id}', [DiscountController::class, 'edit']);
        Route::put('/erp/discounts/update/{id}', [DiscountController::class, 'update']);
        Route::delete('/erp/discounts/delete/{id}', [DiscountController::class, 'delete']);
    });

    Route::middleware(['auth', 'permission:inventory'])->group(function () {
        Route::middleware(['auth', 'subpermission:opening-stock-rate'])->group(function () {
            // Route::get('/erp/opening-stock-rate/data', [OpeningStockRateController::class, 'dataOpeningStockRate']);
            // Route::get('/erp/opening-stock-rate', [OpeningStockRateController::class, 'getOpeningStockRate']);
            // Route::get('/erp/opening-stock-rate/create-opening-stock-rate', [OpeningStockRateController::class, 'create']);
            // Route::post('/erp/opening-stock-rate/store', [OpeningStockRateController::class, 'store']);
            // Route::get('/erp/opening-stock-rate/edit-opening-stock-rate', [OpeningStockRateController::class, 'edit']);
            // Route::put('/erp/opening-stock-rate/update', [OpeningStockRateController::class, 'update']);
            // Route::delete('/erp/opening-stock-rate/delete/{id}', [OpeningStockRateController::class, 'delete']);

            Route::get('/erp/opening-stock', [OpeningStockRateController::class, 'index'])
                ->name('erp.opening-stock.index');
            Route::get('/erp/opening-stock/data', [OpeningStockRateController::class, 'dataOpeningStockOverview'])
                ->name('erp.opening-stock.data');

            Route::get('/erp/opening-stock/edit', [OpeningStockRateController::class, 'edit'])
                ->name('erp.opening-stock.edit');

            Route::put('/erp/opening-stock/update', [OpeningStockRateController::class, 'update'])
                ->name('erp.opening-stock.update');
        });

        // Route::middleware(['auth', 'subpermission:opening-stock-production'])->group(function () {
        //     Route::get('/erp/productions/opening-stock', [OpeningStockProductionController::class, 'getOpeningStockProduction']);
        //     Route::get('/erp/productions/opening-stock/edit-opening-stock', [OpeningStockProductionController::class, 'edit']);
        //     Route::put('/erp/productions/opening-stock/update', [OpeningStockProductionController::class, 'update']);
        // });

        Route::middleware(['auth', 'subpermission:stock-opname'])->group(function () {
            Route::get('/erp/inventory/stock-opname', [StockOpnameController::class, 'getStockOpname']);
            Route::get('/erp/inventory/stock-opname/data', [StockOpnameController::class, 'dataStockOpname']);
            Route::get('/erp/inventory/stock-opname/create-stock-opname', [StockOpnameController::class, 'create']);
            Route::post('/erp/inventory/stock-opname/store', [StockOpnameController::class, 'store']);
            Route::get('/erp/inventory/stock-opname/edit-stock-opname/{id}', [StockOpnameController::class, 'edit']);
            Route::put('/erp/inventory/stock-opname/update/{id}', [StockOpnameController::class, 'update']);
            Route::delete('/erp/inventory/stock-opname/delete/{id}', [StockOpnameController::class, 'delete']);
        });

        Route::middleware(['auth', 'subpermission:stock-opname-production'])->group(function () {
            Route::get('/erp/productions/stock-opname', [StockOpnameProductionController::class, 'getStockOpnameProduction']);
            Route::get('/erp/productions/stock-opname/data', [StockOpnameProductionController::class, 'dataStockOpnameProduction']);
            Route::get('/erp/productions/stock-opname/create-stock-opname', [StockOpnameProductionController::class, 'create']);
            Route::post('/erp/productions/stock-opname/store', [StockOpnameProductionController::class, 'store']);
            Route::get('/erp/productions/stock-opname/edit-stock-opname/{id}', [StockOpnameProductionController::class, 'edit']);
            Route::put('/erp/productions/stock-opname/update/{id}', [StockOpnameProductionController::class, 'update']);
            Route::delete('/erp/productions/stock-opname/delete/{id}', [StockOpnameProductionController::class, 'delete']);
        });

        Route::middleware(['auth', 'subpermission:inventory-report-items'])->group(function () {
            Route::get('/erp/report-items', [ReportItemsProductionAndWarehouseController::class, 'getCombinedReportItems']);
            Route::get('/erp/report-items/data', [ReportItemsProductionAndWarehouseController::class, 'dataCombinedReportItems']);
        });
    });

    Route::middleware(['auth', 'permission:sales'])->group(function () {
        Route::middleware(['auth', 'subpermission:sale-orders'])->group(function () {
            Route::get('/erp/sales/sale-orders/data', [SaleOrderController::class, 'dataSaleOrder']);
            Route::get('/erp/sales/sale-orders', [SaleOrderController::class, 'getSaleOrder']);
            Route::get('/erp/sales/sale-orders/create-order', [SaleOrderController::class, 'create']);
            Route::post('/erp/sales/sale-orders/store', [SaleOrderController::class, 'store']);
            Route::get('/erp/sales/sale-orders/edit-order/{id}', [SaleOrderController::class, 'edit']);
            Route::put('/erp/sales/sale-orders/update/{id}', [SaleOrderController::class, 'update']);
            Route::delete('/erp/sales/sale-orders/delete/{id}', [SaleOrderController::class, 'delete']);
            Route::get('/erp/sales/sale-orders/detail-order/{id}', [SaleOrderController::class, 'getSaleOrderDetail']);
            Route::post('/erp/sales/mark-as-sale-list/{id}', [SaleOrderController::class, 'markAsSaleList']);
            Route::get('/erp/sales/sale-orders/invoice/{id}', [SaleOrderController::class, 'getInvoice']);
            Route::get('/erp/sales/generate-invoice-number', function (Request $request) {
                $date = $request->query('date');
                $invoice = InvoiceNumberService::generate('INV', $date);
                return response()->json(['invoice_number' => $invoice]);
            });
        });

        Route::middleware(['auth', 'subpermission:sale-list'])->group(function () {
            Route::get('/erp/sales/sale-list/data', [SaleListController::class, 'dataSaleList']);
            Route::get('/erp/sales/sale-list', [SaleListController::class, 'getSaleList']);
            Route::get('/erp/sales/sale-list/create-order', [SaleListController::class, 'create']);
            Route::post('/erp/sales/sale-list/store', [SaleListController::class, 'store']);
            Route::get('/erp/sales/sale-list/edit-order/{id}', [SaleListController::class, 'edit']);
            Route::put('/erp/sales/sale-list/update/{id}', [SaleListController::class, 'update']);
            Route::delete('/erp/sales/sale-list/delete/{id}', [SaleListController::class, 'delete']);
            Route::get('/erp/sales/sale-list/detail-order/{id}', [SaleListController::class, 'getSaleListDetail']);
            Route::post('/erp/sales/sale-list/mark-as-paid/{id}', [SaleListController::class, 'markAsPaid']);
            Route::get('/erp/sales/sale-list/invoice/{id}', [SaleListController::class, 'getInvoice']);
            Route::get('/erp/sales/sale-list/invoice-image/{id}', [SaleListController::class, 'getInvoiceImage']);
            Route::get('/erp/sales/sale-list/payment-history/{id}', [SaleListController::class, 'getPaymentHistory']);
            Route::put('/erp/sales/sale-list/update-payment/{groupId}', [SaleListController::class, 'updatePayment']);
            Route::get('/erp/sales/sale-list/edit-history/{id}', [SaleListController::class, 'getEditHistory']);
            Route::post('/erp/sales/sale-list/return-money/{id}', [SaleListController::class, 'returnMoney']);
            Route::get('/erp/sales/sale-list/data-deleted', [SaleListController::class, 'dataDeletedSaleList']);
            Route::get('/erp/sales/sale-list/data-edited', [SaleListController::class, 'dataSaleListEdited']);
            Route::delete('/erp/sales/sale-list/force-delete/{id}', [SaleListController::class, 'forceDelete'])->name('sales.forceDelete');
            Route::post('/erp/sales/sale-list/restore/{id}', [SaleListController::class, 'restore'])->name('sales.restore');
            Route::put('/erp/sales/mark-as-waiting-list/{id}', [SaleListController::class, 'markAsWaitingList']);
            Route::post('/erp/sales/sale-list/verify-payment/{groupId}', [SaleListController::class, 'verifyPayment'])->name('sale-list.verify-payment');
            Route::post('/erp/sales/sale-list/unverify-payment/{groupId}', [SaleListController::class, 'unverifyPayment'])->name('sale-list.unverify-payment');

            Route::post('/erp/sales/sale-list/force-delete/{id}', [SaleListController::class, 'forceDeleteOwner'])
                ->name('sales.sale-list.forceDeleteOwner')
                ->middleware('auth');

            Route::post('/erp/invoice/convert-to-image', [SaleListController::class, 'convertToImage'])->name('invoice.convert');
            Route::get('/invoices/{filename}', [SaleListController::class, 'showInvoice'])->name('invoice.show');
        });

        Route::middleware(['auth', 'subpermission:sale-returns'])->group(function () {
            Route::get('/erp/sales/sale-returns/data', [SaleReturnController::class, 'dataSaleReturns']);
            Route::get('/erp/sales/sale-returns', [SaleReturnController::class, 'getSaleReturns']);
            Route::get('/erp/sales/sale-returns/create-sale-return/{id}', [SaleReturnController::class, 'create']);
            Route::post('/erp/sales/sale-returns/store', [SaleReturnController::class, 'store']);
            Route::get('/erp/sales/sale-returns/edit-sale-return/{id}', [SaleReturnController::class, 'edit']);
            Route::put('/erp/sales/sale-returns/update/{id}', [SaleReturnController::class, 'update']);
            Route::delete('/erp/sales/sale-returns/delete/{id}', [SaleReturnController::class, 'delete']);
            Route::get('/erp/sales/sale-returns/detail-order/{id}', [SaleReturnController::class, 'getSaleReturnDetail']);
            Route::post('/erp/sales/sale-returns/mark-as-refund/{id}', [SaleReturnController::class, 'markAsRefund']);
            Route::post(
                '/erp/sales/sale-returns/mark-as-customer-deposit/{id}',
                [SaleReturnController::class, 'markAsCustomerDeposit']
            );
            Route::get('/erp/sales/sale-returns/get-canceled-products/{id}', [SaleReturnController::class, 'getCanceledProducts']);
            Route::post('/erp/sales/sale-returns/process-return-to-warehouse/{id}', [SaleReturnController::class, 'processReturnToWarehouse']);
            Route::get('/erp/sales/sale-returns/invoice/{id}', [SaleReturnController::class, 'getInvoice']);
            Route::get('/erp/sales/sale-return/payment-history/{id}', [SaleReturnController::class, 'getPaymentHistory']);
            Route::put('/erp/sales/sale-return/update-payment/{groupId}', [SaleReturnController::class, 'updatePayment']);
            Route::get('/erp/sales/sale-return/edit-history/{id}', [SaleReturnController::class, 'getEditHistory']);
            Route::get('/erp/sales/sale-returns/data-deleted', [SaleReturnController::class, 'dataDeletedSaleReturns']);
            Route::delete('/erp/sales/sale-returns/force-delete/{id}', [SaleReturnController::class, 'forceDelete'])->name('sale-returns.forceDelete');
            Route::post('/erp/sales/sale-returns/restore/{id}', [SaleReturnController::class, 'restore'])->name('sale-returns.restore');

            Route::post('/erp/sales/sale-returns/verify-payment/{groupId}', [SaleReturnController::class, 'verifyPayment'])->name('sale-returns.verify-payment');
        });
    });

    Route::middleware(['auth', 'permission:design'])->group(function () {
        Route::get('/erp/design', [DesignController::class, 'getDesign'])->name('design');
        Route::get('/erp/design/data', [DesignController::class, 'dataDesign']);
        Route::post('/erp/design-items/{id}/upload', [DesignItemController::class, 'upload'])->name('design-items.upload');
        Route::post('/erp/design/{id}/verify', [DesignController::class, 'verify'])->name('design.verify');
        Route::post('/erp/design/{id}/unverify', [DesignController::class, 'unverify'])->name('design.unverify');
    });

    Route::middleware(['auth', 'permission:production'])->group(function () {
        Route::middleware(['auth', 'subpermission:waiting-list'])->group(function () {
            Route::get('/erp/productions/waiting-list/data', [WaitingListController::class, 'dataWaitingList']);
            Route::get('/erp/productions/waiting-list', [WaitingListController::class, 'getWaitingList']);
            // Route::get('/erp/complete-list/data', [WaitingListController::class, 'dataCompleteList']);
            // Route::get('/erp/complete-list', [WaitingListController::class, 'getCompleteList']);
            // Route::put('/erp/mark-as-complete-list/{id}', [WaitingListController::class, 'markAsCompleteList']);
            // Route::put('/erp/mark-as-delivery/{id}', [WaitingListController::class, 'markAsDelivery']);
            Route::get('/erp/productions/waiting-list/add-assign/{id}', [OrderProgressAssignController::class, 'create']);
            Route::post('/erp/productions/waiting-list/assign/{id}', [OrderProgressAssignController::class, 'store']);

            Route::get('/erp/productions/waiting-list/history-order/{id}', [HistoryProgressOrderController::class, 'getHistory']);
            Route::delete('/erp/productions/waiting-list/history-order/delete-history/{id}', [HistoryProgressOrderController::class, 'deleteHistory']);
            Route::delete('/erp/productions/waiting-list/history-order/delete-batch/{id}', [HistoryProgressOrderController::class, 'deleteBatch']);

            Route::put('/erp/productions/waiting-list/history-order/update-history/{id}', [HistoryProgressOrderController::class, 'updateHistory']);
            Route::get('/erp/productions/waiting-list/history-order/{id}/data', [HistoryProgressOrderController::class, 'dataOrderHistory']);
            Route::get('/erp/productions/waiting-list/history-order/{id}', [HistoryProgressOrderController::class, 'getOrderHistory']);
            Route::get('/erp/productions/waiting-list/add-request-stocks/{id}', [StockRequestController::class, 'addRequestStocks']);
            Route::post('/erp/productions/waiting-list/request-stocks/{id}', [StockRequestController::class, 'store']);
        });

        Route::middleware(['auth', 'subpermission:assign-list'])->group(function () {
            Route::get('/erp/productions/assign-list/edit-assign/{batch_id}', [OrderProgressAssignController::class, 'edit']);
            Route::put('/erp/productions/assign-list/assign/update/{batch_id}', [OrderProgressAssignController::class, 'update']);

            Route::get('/erp/productions/assign-list/add-progress/{batch_id}', [HistoryProgressOrderController::class, 'create']);
            Route::post('/erp/productions/assign-list/add-progress/{batch_id}', [HistoryProgressOrderController::class, 'store']);

            Route::get('/erp/productions/waiting-list/assign-list/data', [OrderProgressAssignController::class, 'dataAssignList']);
            Route::get('/erp/productions/waiting-list/assign-list', [OrderProgressAssignController::class, 'getAssignList']);
            Route::delete('/erp/productions/assign-list/delete/{id}', [OrderProgressAssignController::class, 'delete']);

            Route::get('/erp/productions/waiting-list/assign-batch/{batch}/assigns', [OrderProgressAssignController::class, 'getAssignsByBatch']);
            Route::get('/erp/productions/waiting-list/assign-list/summary', [OrderProgressAssignController::class, 'AssignSummary']);
        });

        Route::middleware(['auth', 'subpermission:request-stocks'])->group(function () {
            Route::get('/erp/productions/material-request/data', [MaterialRequestController::class, 'dataMaterialRequest']);
            Route::get('/erp/productions/material-request', [MaterialRequestController::class, 'getMaterialRequest']);
            Route::get('/erp/productions/material-request/create', [MaterialRequestController::class, 'create']);
            Route::post('/erp/productions/material-request/store', [MaterialRequestController::class, 'store']);
            Route::get('/erp/productions/material-request/edit/{id}', [MaterialRequestController::class, 'edit']);
            Route::put('/erp/productions/material-request/update/{id}', [MaterialRequestController::class, 'update']);
            Route::delete('/erp/productions/material-request/delete/{id}', [MaterialRequestController::class, 'delete']);
            Route::delete('/erp/productions/material-request/delete-empty/{id}', [MaterialRequestController::class, 'deleteEmpty']);

            Route::put('/erp/productions/material-request/mark-as-verified/{id}', [MaterialRequestController::class, 'markAsVerified']);

            Route::get('/erp/productions/stock-request/history/{id}', [HistoryRequestStockController::class, 'getRequestStockHistory']);
            Route::get('/erp/productions/stock-request/history/{id}/data', [HistoryRequestStockController::class, 'dataRequestStockHistory']);

            Route::get('/erp/productions/stock-request/data-deleted', [MaterialRequestController::class, 'dataDeletedRequestStock']);
            Route::get('/erp/material-request/summary', [MaterialRequestController::class, 'RequestSummary'])
                ->name('material-request.summary');
            Route::delete('/erp/productions/stock-request/force-delete/{id}', [MaterialRequestController::class, 'forceDelete'])->name('request-stocks.forceDelete');
            Route::post('/erp/productions/stock-request/restore/{id}', [MaterialRequestController::class, 'restore'])->name('request-stocks.restore');
        });

        Route::middleware(['auth', 'subpermission:report-items'])->group(function () {
            Route::get('/erp/productions/report-items', [ReportItemsProductionController::class, 'getReportItems']);
            Route::get('/erp/productions/report-items/data', [ReportItemsProductionController::class, 'dataReportItems']);

            Route::post('/erp/defect-product/store-production', [ReportItemsProductionController::class, 'storeProduction'])
                ->name('erp.defect-product.store-production');
        });

        Route::middleware(['auth', 'subpermission:snapshot-report'])->group(function () {
            Route::get('/erp/productions/snapshot-report', [ProductionStockSnapshotController::class, 'getSnapshotReport']);
            Route::get('/erp/productions/snapshot-report/data', [ProductionStockSnapshotController::class, 'dataSnapshotReport']);
        });

        // Stock in
        Route::middleware(['auth', 'subpermission:stock-in-production'])->group(function () {
            Route::get('/erp/productions/stock-in/data', [ProductionController::class, 'dataStockIn']);
            Route::get('/erp/productions/stock-in', [ProductionController::class, 'getStockIn']);
            // Route::get('/erp/productions/stock-in/add-stock-in/{id}', [ProductionStockInController::class, 'addStockIn']);
            // Route::post('/erp/productions/stock-in/store/{id}', [ProductionStockInController::class, 'store']);
            Route::get('/erp/productions/stock-in/add-stock-in/{supplier_id}/{year}/{month}', [ProductionStockInController::class, 'addStockIn']);
            Route::post('/erp/productions/stock-in/store-grouped/{supplier_id}/{year}/{month}', [ProductionStockInController::class, 'storeGrouped']);
            Route::get('/erp/productions/stock-in/edit-stock-in/{id}', [ProductionStockInController::class, 'edit']);
            Route::put('/erp/productions/stock-in/update/{id}', [ProductionStockInController::class, 'update']);
            // Route::get('/erp/productions/stock-in/history/{id}/data', [ProductionStockInController::class, 'dataHistory']);
            // Route::get('/erp/productions/stock-in/history/{id}', [ProductionStockInController::class, 'getHistory']);
            Route::get('/erp/productions/stock-in/history/{supplier_id}/{year}/{month}', [ProductionStockInController::class, 'getHistory']);
            Route::get('/erp/productions/stock-in/history/{supplier_id}/{year}/{month}/data', [ProductionStockInController::class, 'dataHistory']);
            Route::post('/erp/productions/stock-in/history/item/{id}/update', [ProductionStockInController::class, 'updateHistoryItem']);
        });

        // Route::middleware(['auth', 'subpermission:canceled-products'])->group(function () {
        //     Route::get('/erp/productions/canceled-products', [CanceledProductController::class, 'getCanceledProducts']);
        //     Route::get('/erp/productions/canceled-products/data', [CanceledProductController::class, 'dataCanceledProducts']);
        //     Route::post('/erp/productions/canceled-products/return-to-warehouse/{id}', [CanceledProductController::class, 'returnToWarehouse']);

        //     Route::get('/erp/productions/canceled-products/detail/{id}', [CanceledProductController::class, 'detailCanceledProducts']);
        //     Route::get('/erp/productions/canceled-products/detail/{id}/data', [CanceledProductController::class, 'dataDetailCanceledProducts']);

        //     Route::get('/erp/productions/canceled-products/history/{id}', [CanceledProductController::class, 'getCanceledProductHistory']);
        //     Route::get('/erp/productions/canceled-products/history/{id}/data', [CanceledProductController::class, 'dataCanceledProductHistory']);
        // });

        // Route::get('/erp/productions/waiting-list/history-order/{id}', [HistoryProgressOrderController::class, 'getOrderHistory']);
        // Route::get('/erp/productions/waiting-list/history-order/{id}/data', [HistoryProgressOrderController::class, 'dataOrderHistory']);

        // Route::get('/erp/productions/waiting-list/detail-order/{id}', [OrderDetailController::class, 'getOrderDetail']);
        // Route::get('/erp/invoice-order/{id}', [InvoiceController::class, 'getInvoice']);
        // Route::get('/erp/invoice-png/{id}', [InvoiceController::class, 'downloadInvoicePNG']);
    });

    Route::middleware(['auth', 'permission:delivery'])->group(function () {
        Route::middleware(['auth', 'subpermission:delivery-orders'])->group(function () {
            Route::get('/erp/deliveries/delivery-orders/data', [DeliveryOrderController::class, 'dataDeliveryOrders']);
            Route::get('/erp/deliveries/delivery-orders', [DeliveryOrderController::class, 'getDeliveryOrders']);
            Route::get('/erp/deliveries/delivery-list/generate-number/{doId}', [DeliveryOrderController::class, 'generateNumber']);
            Route::get('/erp/deliveries/delivery-orders/{id}/items', [DeliveryOrderController::class, 'getItems']);
            Route::get('/erp/deliveries/delivery-list/create-delivery-list/{doId}', [DeliveryListController::class, 'create']);
            Route::post('/erp/deliveries/delivery-list/store/{doId}', [DeliveryListController::class, 'store']);
            Route::get('/erp/deliveries/delivery-list/edit-delivery-list/{id}', [DeliveryListController::class, 'edit']);
            Route::put('/erp/deliveries/delivery-list/update/{id}', [DeliveryListController::class, 'update']);
            Route::get('/erp/deliveries/delivery-orders/history-delivery-order/{id}', [DeliveryOrderController::class, 'getDeliveryHistory']);
            Route::get('/erp/deliveries/delivery-orders/history-delivery-order/{id}/data', [DeliveryOrderController::class, 'dataDeliveryHistory']);
            Route::post('/erp/deliveries/delivery-orders/update-history/{id}', [DeliveryOrderController::class, 'updateDeliveryHistory']);
        });

        Route::middleware(['auth', 'subpermission:delivery-list'])->group(function () {
            Route::get('/erp/deliveries/delivery-list/data', [DeliveryListController::class, 'dataDeliveryList']);
            Route::get('/erp/deliveries/delivery-list', [DeliveryListController::class, 'getDeliveryList']);
            Route::get('/erp/deliveries/delivery-list/print-waybill/{id}', [DeliveryListController::class, 'printWaybill']);
            Route::post('/erp/deliveries/delivery-list/{id}/upload-proof', [DeliveryListController::class, 'uploadProof'])
                ->name('delivery-list.upload-proof');

            Route::put('/erp/deliveries/delivery-list/{id}/verify', [DeliveryListController::class, 'verify'])
                ->name('delivery-list.verify');

            Route::delete('/erp/deliveries/delivery-list/{id}/destroy', [App\Http\Controllers\Admin\DeliveryListController::class, 'destroy'])
                ->name('delivery-list.destroy');
        });
    });

    Route::middleware(['auth', 'permission:purchases'])->group(function () {
        Route::middleware(['auth', 'subpermission:purchase-orders'])->group(function () {
            Route::get('/erp/purchases/purchase-orders/detail-purchase/{id}', [PurchaseDetailController::class, 'getPurchaseOrderDetail']);
            Route::get('/erp/purchases/purchase-orders/data', [PurchaseOrderController::class, 'dataPurchaseOrders']);
            Route::get('/erp/purchases/purchase-orders', [PurchaseOrderController::class, 'getPurchaseOrders']);
            Route::get('/erp/purchases/purchase-orders/create-purchase', [PurchaseOrderController::class, 'create']);
            Route::post('/erp/purchases/purchase-orders/store', [PurchaseOrderController::class, 'store']);
            Route::get('/erp/purchases/purchase-orders/edit-purchase/{id}', [PurchaseOrderController::class, 'edit']);
            Route::put('/erp/purchases/purchase-orders/update/{id}', [PurchaseOrderController::class, 'update']);
            Route::delete('/erp/purchases/purchase-orders/delete/{id}', [PurchaseOrderController::class, 'delete']);
            Route::get('/erp/purchases/purchase-orders/mark-as-purchase-list/{id}', [PurchaseOrderController::class, 'markAsPurchaseList']);
            Route::put('/erp/purchases/purchase-orders/mark-as-purchase-list/update/{id}', [PurchaseOrderController::class, 'updatePurchaseList'])->name('purchase-orders.update-purchase-list');
        });

        Route::middleware(['auth', 'subpermission:purchase-list'])->group(function () {
            Route::get('/erp/purchases/check-number', [PurchaseListController::class, 'checkNumber'])->name('purchases.check-number');
            Route::get('/erp/purchases/get-latest-price/{productId}', [PurchaseListController::class, 'getLatestPrice']);
            Route::get('/erp/purchases/purchase-list/detail-purchase/{id}', [PurchaseDetailController::class, 'getPurchaseListDetail']);
            Route::get('/erp/purchases/purchase-list/data', [PurchaseListController::class, 'dataPurchaseList']);
            Route::get('/erp/purchases/purchase-list', [PurchaseListController::class, 'getPurchaseList']);
            Route::get('/erp/purchases/purchase-list/create-purchase', [PurchaseListController::class, 'create']);
            Route::post('/erp/purchases/purchase-list/store', [PurchaseListController::class, 'store']);
            Route::get('/erp/purchases/purchase-list/edit-purchase/{id}', [PurchaseListController::class, 'edit']);
            Route::put('/erp/purchases/purchase-list/update/{id}', [PurchaseListController::class, 'update']);
            Route::delete('/erp/purchases/purchase-list/delete/{id}', [PurchaseListController::class, 'delete']);
            Route::post('/erp/purchases/purchase-list/mark-as-paid/{id}', [PurchaseListController::class, 'markAsPaid']);
            Route::post('/erp/purchases/purchase-list/mark-as-paid-product/{id}', [PurchaseListController::class, 'markAsPaidProduct'])->name('purchases.markAsPaidProduct');
            Route::post('/erp/purchases/purchase-list/mark-as-paid-freight/{id}', [PurchaseListController::class, 'markAsPaidFreight'])->name('purchases.markAsPaidFreight');
            Route::get('/erp/purchases/purchase-list/payment-history/{id}', [PurchaseListController::class, 'getPaymentHistory']);
            Route::put('/erp/purchases/purchase-list/update-payment/{id}', [PurchaseListController::class, 'updatePayment']);
            // Route::post('/erp/purchases/purchase-list/update-payment-item/{id}', [PurchaseListController::class, 'updatePayment'])->name('purchases.updatePaymentItem');
            Route::get('/erp/purchases/purchase-list/edit-history/{id}', [PurchaseListController::class, 'getEditHistory']);
            Route::get('/erp/purchases/purchase-list/data-deleted', [PurchaseListController::class, 'dataDeletedPurchaseList']);
            Route::delete('/erp/purchases/purchase-list/force-delete/{id}', [PurchaseListController::class, 'forceDelete'])->name('purchases.forceDelete');
            Route::post('/erp/purchases/purchase-list/restore/{id}', [PurchaseListController::class, 'restore'])->name('purchases.restore');
            Route::post('/erp/purchases/purchase-list/verify-payment/{groupId}', [PurchaseListController::class, 'verifyPayment'])->name('purchase-list.verify-payment');
            Route::post('/erp/purchases/purchase-list/force-delete/{id}', [PurchaseListController::class, 'forceDeleteOwner'])
                ->name('purchases.purchase-list.forceDeleteOwner')
                ->middleware('auth');
        });

        Route::middleware(['auth', 'subpermission:purchase-returns'])->group(function () {
            Route::get('/erp/purchases/purchase-returns/detail-purchase/{id}', [PurchaseDetailController::class, 'getPurchaseReturnDetail']);
            Route::get('/erp/purchases/purchase-returns/data', [PurchaseReturnController::class, 'dataPurchaseReturns']);
            Route::get('/erp/purchases/purchase-returns', [PurchaseReturnController::class, 'getPurchaseReturns']);
            Route::get('/erp/purchases/purchase-returns/create-purchase-return/{id}', [PurchaseReturnController::class, 'create']);
            Route::post('/erp/purchases/purchase-returns/store', [PurchaseReturnController::class, 'store']);
            Route::get('/erp/purchases/purchase-returns/edit-purchase-return/{id}', [PurchaseReturnController::class, 'edit']);
            Route::put('/erp/purchases/purchase-returns/update/{id}', [PurchaseReturnController::class, 'update']);
            Route::delete('/erp/purchases/purchase-returns/delete/{id}', [PurchaseReturnController::class, 'delete']);
            Route::post('/erp/purchases/purchase-returns/mark-as-refund/{id}', [PurchaseReturnController::class, 'markAsRefund']);
            Route::post('/erp/purchases/purchase-returns/mark-as-refund-product/{id}', [PurchaseReturnController::class, 'markAsRefundProduct'])->name('purchase-returns.markAsRefundProduct');
            Route::post('/erp/purchases/purchase-returns/mark-as-refund-freight/{id}', [PurchaseReturnController::class, 'markAsRefundFreight'])->name('purchase-returns.markAsRefundFreight');
            Route::get('/erp/purchases/purchase-returns/payment-history/{id}', [PurchaseReturnController::class, 'getPaymentHistory']);
            Route::put('/erp/purchases/purchase-returns/update-payment/{groupId}', [PurchaseReturnController::class, 'updatePayment']);
            Route::get('/erp/purchases/purchase-returns/edit-history/{id}', [PurchaseReturnController::class, 'getEditHistory']);
            Route::get('/erp/purchases/purchase-returns/data-deleted', [PurchaseReturnController::class, 'dataDeletedPurchaseReturns']);
            Route::delete('/erp/purchases/purchase-returns/force-delete/{id}', [PurchaseReturnController::class, 'forceDelete'])->name('purchase-returns.forceDelete');
            Route::post('/erp/purchases/purchase-returns/restore/{id}', [PurchaseReturnController::class, 'restore'])->name('purchase-returns.restore');
            Route::post('/erp/purchases/purchase-returns/verify-payment/{groupId}', [PurchaseReturnController::class, 'verifyPayment'])->name('purchase-returns.verify-payment');
        });
    });

    Route::middleware(['auth', 'permission:warehouse'])->group(function () {
        Route::middleware(['auth', 'subpermission:stock-in'])->group(function () {
            Route::get('/erp/inventory/stock-in/data', [InventoryController::class, 'dataStockIn']);
            Route::get('/erp/inventory/stock-in', [InventoryController::class, 'getStockIn']);

            // Route::get('/erp/inventory/stock-in/add-stock-in/{id}', [HistoryStockInController::class, 'addStockIn']);
            Route::get('/erp/inventory/stock-in/add-stock-in/{supplier_id}/{year}/{month}', [HistoryStockInController::class, 'addStockIn']);

            // Route::post('/erp/inventory/stock-in/store/{id}', [HistoryStockInController::class, 'store']);
            Route::post('/erp/inventory/stock-in/store/{supplier_id}/{year}/{month}', [HistoryStockInController::class, 'storeGrouped']);

            Route::get('/erp/inventory/stock-in/edit-stock-in/{id}', [HistoryStockInController::class, 'edit']);
            Route::put('/erp/inventory/stock-in/update/{id}', [HistoryStockInController::class, 'update']);
            // Route::get('/erp/inventory/stock-in/history/{id}/data', [HistoryStockInController::class, 'dataHistory']);
            // Route::get('/erp/inventory/stock-in/history/{id}', [HistoryStockInController::class, 'getHistory']);
            Route::get('/erp/inventory/stock-in/history/{supplier_id}/{year}/{month}', [HistoryStockInController::class, 'getHistory']);
            Route::get('/erp/inventory/stock-in/history/{supplier_id}/{year}/{month}/data', [HistoryStockInController::class, 'dataHistory']);

            Route::post('/erp/inventory/stock-in/history/item/{id}/update', [HistoryStockInController::class, 'updateHistoryItem']);
        });

        Route::middleware(['auth', 'subpermission:stock-out'])->group(function () {
            Route::get('/erp/inventory/stock-out/data', [InventoryController::class, 'dataStockOut']);
            Route::get('/erp/inventory/stock-out', [InventoryController::class, 'getStockOut']);
            Route::get('/erp/inventory/stock-out/add-stock-out/{id}', [HistoryStockOutController::class, 'addStockOut']);
            Route::post('/erp/inventory/stock-out/store/{id}', [HistoryStockOutController::class, 'store']);
            Route::get('/erp/inventory/stock-out/edit-stock-out/{id}', [HistoryStockOutController::class, 'edit']);
            Route::put('/erp/inventory/stock-out/update/{id}', [HistoryStockOutController::class, 'update']);
            Route::get('/erp/inventory/stock-out/history/{id}', [HistoryStockOutController::class, 'getHistory']);
            Route::get('/erp/inventory/stock-out/history/{id}/data', [HistoryStockOutController::class, 'dataHistory']);
            Route::delete('/erp/inventory/stock-out/delete/{id}', [HistoryStockOutController::class, 'delete']);
        });

        Route::middleware(['auth', 'subpermission:warehouse-report-items'])->group(function () {
            Route::get('/erp/inventory/report-items/data', [InventoryController::class, 'dataReportItems']);
            Route::get('/erp/inventory/report-items', [InventoryController::class, 'getReportItems']);

            Route::post('/erp/defect-product/store', [InventoryController::class, 'store'])
                ->name('erp.defect-product.store');
        });
    });

    Route::middleware(['auth', 'permission:expenses'])->group(function () {
        Route::get('/erp/expenses/data', [ExpenseController::class, 'dataExpense']);
        Route::get('/erp/expenses', [ExpenseController::class, 'index']);
        Route::get('/erp/expenses/create-expense', [ExpenseController::class, 'create']);
        Route::post('/erp/expenses/store', [ExpenseController::class, 'store']);
        Route::get('/erp/expenses/edit-expense/{id}', [ExpenseController::class, 'edit']);
        Route::put('/erp/expenses/update/{id}', [ExpenseController::class, 'update']);
        Route::delete('/erp/expenses/delete/{id}', [ExpenseController::class, 'delete']);
    });

    Route::middleware(['auth', 'permission:capital-transaction'])->group(function () {
        Route::get('/erp/capital-transactions/data', [CapitalTransactionController::class, 'dataCapitalTransaction']);
        Route::get('/erp/capital-transactions', [CapitalTransactionController::class, 'index']);

        Route::get('/erp/capital-transactions/create-capital-transaction', [CapitalTransactionController::class, 'create']);
        Route::post('/erp/capital-transactions/store', [CapitalTransactionController::class, 'store']);
        Route::get('/erp/capital-transactions/edit-capital-transaction/{id}', [CapitalTransactionController::class, 'edit']);
        Route::put('/erp/capital-transactions/update/{id}', [CapitalTransactionController::class, 'update']);
        Route::delete('/erp/capital-transactions/delete/{id}', [CapitalTransactionController::class, 'delete']);
    });

    Route::middleware(['auth', 'permission:accounts'])->group(function () {
        Route::middleware(['auth', 'subpermission:manage-opening-balance'])->group(function () {
            Route::get('/erp/accounts/opening-balance/data', [OpeningBalanceController::class, 'dataOpeningBalance']);
            Route::get('/erp/accounts/opening-balance', [OpeningBalanceController::class, 'getOpeningBalance']);
            Route::get('/erp/accounts/opening-balance/create-opening-balance', [OpeningBalanceController::class, 'create']);
            Route::post('/erp/accounts/opening-balance/store', [OpeningBalanceController::class, 'store']);
            Route::get('/erp/accounts/opening-balance/edit-opening-balance', [OpeningBalanceController::class, 'edit']);
            Route::put('/erp/accounts/opening-balance/update', [OpeningBalanceController::class, 'update']);
            Route::delete('/erp/accounts/opening-balance/delete/{id}', [OpeningBalanceController::class, 'delete']);
        });

        Route::middleware(['auth', 'subpermission:manage-accounts'])->group(function () {
            Route::get('/erp/accounts', [AccountController::class, 'getAccount']);
            Route::get('/erp/accounts/data', [AccountController::class, 'dataAccount']);
            Route::get('/erp/accounts/create-account', [AccountController::class, 'create']);
            Route::post('/erp/accounts/store', [AccountController::class, 'store']);
            Route::get('/erp/accounts/edit-account/{id}', [AccountController::class, 'edit']);
            Route::put('/erp/accounts/update/{id}', [AccountController::class, 'update']);
            Route::delete('/erp/accounts/delete/{id}', [AccountController::class, 'delete']);
            Route::post('/erp/accounts/mark-default/{id}', [AccountController::class, 'markAsDefault']);
            Route::post('/erp/accounts/remove-default/{id}', [AccountController::class, 'removeDefault']);
        });

        Route::middleware(['auth', 'subpermission:account-expense'])->group(function () {
            Route::get('/erp/accounts/expense/data', [AccountListController::class, 'dataExpense']);
            Route::get('/erp/accounts/expense', [AccountListController::class, 'getExpense']);
        });

        Route::middleware(['auth', 'subpermission:account-customer-deposit'])->group(function () {
            Route::get('/erp/accounts/customer-deposit/data', [AccountListController::class, 'dataCustomerDeposit']);
            Route::get('/erp/accounts/customer-deposit', [AccountListController::class, 'getCustomerDeposit']);
        });

        Route::middleware(['auth', 'subpermission:account-bank'])->group(function () {
            Route::get('/erp/accounts/bank/data', [AccountListController::class, 'dataBank']);
            Route::get('/erp/accounts/bank', [AccountListController::class, 'getBank']);
        });

        Route::middleware(['auth', 'subpermission:account-cash'])->group(function () {
            Route::get('/erp/accounts/cash/data', [AccountListController::class, 'dataCash']);
            Route::get('/erp/accounts/cash', [AccountListController::class, 'getCash']);
        });

        Route::middleware(['auth', 'subpermission:account-sale'])->group(function () {
            Route::get('/erp/accounts/sale/data', [AccountListController::class, 'dataSale']);
            Route::get('/erp/accounts/sale', [AccountListController::class, 'getSale']);
        });

        Route::middleware(['auth', 'subpermission:account-purchase'])->group(function () {
            Route::get('/erp/accounts/purchase/data', [AccountListController::class, 'dataPurchase']);
            Route::get('/erp/accounts/purchase', [AccountListController::class, 'getPurchase']);
        });

        Route::middleware(['auth', 'subpermission:account-capital'])->group(function () {
            Route::get('/erp/accounts/capital/data', [AccountListController::class, 'dataCapital']);
            Route::get('/erp/accounts/capital', [AccountListController::class, 'getCapital']);
        });
    });

    Route::middleware(['auth', 'permission:financial-report'])->group(function () {
        Route::get('/erp/financial-report/profit-loss', [FinancialStatementController::class, 'profitLoss']);
        Route::get('/erp/financial-report/profit-loss/summary', [FinancialStatementController::class, 'profitLossSummary']);

        Route::get('/erp/financial-report/profit-loss/daily', [FinancialStatementController::class, 'profitLossDailyView']);
        Route::get('/erp/financial-report/profit-loss/daily/data', [FinancialStatementController::class, 'profitLossDaily']);
    });

    Route::middleware(['auth', 'permission:shop-manager'])->group(function () {
        Route::get('/erp/shop-manager/users/data', [UserController::class, 'dataUsers']);
        Route::get('/erp/shop-manager/users', [UserController::class, 'getUsers']);

        Route::get('/erp/shop-manager/create-user', [UserController::class, 'create']);
        Route::post('/erp/shop-manager/store', [UserController::class, 'store']);
        Route::delete('/erp/shop-manager/delete/{id}', [UserController::class, 'delete']);
        Route::get('/erp/shop-manager/edit-user/{id}', [UserController::class, 'edit']);
        Route::put('/erp/shop-manager/update/{id}', [UserController::class, 'update']);
    });

    Route::middleware(['auth', 'permission:operator'])->group(function () {
        Route::get('/erp/shop-manager/operators/data', [OperatorController::class, 'dataOperators']);
        Route::get('/erp/shop-manager/operators', [OperatorController::class, 'getOperators']);
        Route::get('/erp/shop-manager/operators/create-operator', [OperatorController::class, 'create']);
        Route::post('/erp/shop-manager/operators/store', [OperatorController::class, 'store']);
        Route::get('/erp/shop-manager/operators/edit-operator/{id}', [OperatorController::class, 'edit']);
        Route::put('/erp/shop-manager/operators/update/{id}', [OperatorController::class, 'update']);
        Route::delete('/erp/shop-manager/operators/delete/{id}', [OperatorController::class, 'delete']);
        Route::get('/erp/shop-manager/operators/{id}/detail', [OperatorController::class, 'show'])->name('operators.show');
        Route::get('/erp/shop-manager/operators/detail/{id}/data', [OperatorController::class, 'dataShow']);
    });

    Route::middleware(['auth', 'permission:customer'])->group(function () {
        Route::get('/erp/customers/data', [CustomerController::class, 'data']);
        Route::get('/erp/customers', [CustomerController::class, 'index']);
        Route::get('/erp/customers/create-customer', [CustomerController::class, 'create']);
        Route::post('/erp/customers/store', [CustomerController::class, 'store']);
        Route::get('/erp/customers/detail-customer/{id}', [CustomerController::class, 'detail']);
        Route::get('/erp/customers/edit-customer/{id}', [CustomerController::class, 'edit']);
        Route::put('/erp/customers/update/{id}', [CustomerController::class, 'update']);
        Route::put('/erp/customers/delete/{id}', [CustomerController::class, 'delete']);
    });

    Route::middleware(['auth', 'permission:supplier'])->group(function () {
        Route::get('/erp/suppliers/data', [SupplierController::class, 'data']);
        Route::get('/erp/suppliers', [SupplierController::class, 'index']);
        Route::get('/erp/suppliers/create-supplier', [SupplierController::class, 'create']);
        Route::post('/erp/suppliers/store', [SupplierController::class, 'store']);
        Route::get('/erp/suppliers/detail-supplier/{id}', [SupplierController::class, 'detail']);
        Route::get('/erp/suppliers/edit-supplier/{id}', [SupplierController::class, 'edit']);
        Route::put('/erp/suppliers/update/{id}', [SupplierController::class, 'update']);
        Route::put('/erp/suppliers/delete/{id}', [SupplierController::class, 'delete']);
    });

    Route::middleware(['auth', 'permission:invoice'])->group(function () {
        Route::get('/erp/invoices/data', [InvoiceController::class, 'dataInvoice']);
        Route::get('/erp/invoices', [InvoiceController::class, 'index']);
        Route::get('/erp/invoices/create-invoice', [InvoiceController::class, 'create']);
        Route::post('/erp/invoices/store', [InvoiceController::class, 'store']);
        Route::get('/erp/invoices/edit-invoice/{id}', [InvoiceController::class, 'edit']);
        Route::put('/erp/invoices/update/{id}', [InvoiceController::class, 'update']);
        Route::delete('/erp/invoices/delete/{id}', [InvoiceController::class, 'delete']);
    });
});
