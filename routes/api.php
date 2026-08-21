<?php

use App\Http\Controllers\Api\Erp\AuthApiController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Erp\ProductCategoryApiController;
use App\Http\Controllers\Api\Erp\ProductTagApiController;
use App\Http\Controllers\Api\Erp\ProductUnitApiController;
use App\Http\Controllers\Api\Public\CustomerAuthController;
use App\Http\Controllers\Api\Public\EcommerceSaleOrderController;
use App\Http\Controllers\Api\Public\EcommerceProductController;
use App\Http\Controllers\Api\Public\EcommerceProductCategoryController;
use App\Http\Controllers\Api\Public\EcommerceInformationController;
use App\Http\Controllers\Api\Public\EcommerceDiscountController;
use App\Http\Controllers\Api\Public\CustomerCartController;

Route::prefix('v1')->group(function () {
    // ERP admin login
    Route::post('/login', [AuthApiController::class, 'login']);

    // ERP protected API
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthApiController::class, 'logout']);
        Route::get('/me', [AuthApiController::class, 'me']);

        Route::middleware(['permission:products', 'subpermission:product-categories'])->group(function () {
            Route::apiResource('product-categories', ProductCategoryApiController::class);
        });

        Route::middleware(['permission:products', 'subpermission:product-tags'])->group(function () {
            Route::apiResource('product-tags', ProductTagApiController::class);
        });

        Route::middleware(['permission:products', 'subpermission:product-units'])->group(function () {
            Route::apiResource('product-units', ProductUnitApiController::class);
        });
    });

    // Customer Login
    Route::prefix('ecommerce/auth')->group(function () {
        Route::post('/register', [CustomerAuthController::class, 'register']);
        Route::post('/login', [CustomerAuthController::class, 'login'])->middleware('throttle:5,1');
        Route::get('/password-reset/validate', [CustomerAuthController::class, 'validatePasswordResetToken'])
            ->middleware('throttle:30,1');
        Route::post('/password-reset', [CustomerAuthController::class, 'resetPassword'])
            ->middleware('throttle:5,1');

        // Protected customer auth
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/me', [CustomerAuthController::class, 'me']);
            Route::post('/logout', [CustomerAuthController::class, 'logout']);
            Route::put('/profile', [CustomerAuthController::class, 'updateProfile']);
            Route::put('/password', [CustomerAuthController::class, 'changePassword']);

            Route::put('/addresses/{id}', [CustomerAuthController::class, 'updateAddress']);
            Route::put('/addresses/{id}/default', [CustomerAuthController::class, 'setDefaultAddress']);
            Route::delete('/addresses/{id}', [CustomerAuthController::class, 'deleteAddress']);
        });
    });

    Route::prefix('ecommerce')->middleware('auth:sanctum')->group(function () {
        Route::get('/cart', [CustomerCartController::class, 'index']);
        Route::put('/cart', [CustomerCartController::class, 'sync']);
        Route::get('/sale-orders', [EcommerceSaleOrderController::class, 'index']);
        // Didaftarkan sebelum /sale-orders/{order} supaya "sync" tidak ditelan
        // route model binding dan berakhir 404.
        Route::get('/sale-orders/sync', [EcommerceSaleOrderController::class, 'sync']);
        Route::get('/sale-orders/{order}', [EcommerceSaleOrderController::class, 'show']);
    });

    // Public ecommerce API
    Route::prefix('ecommerce')->group(function () {
        Route::post('/sale-orders', [EcommerceSaleOrderController::class, 'store']);
        Route::get('/products', [EcommerceProductController::class, 'index']);
        Route::get('/products/{slug}', [EcommerceProductController::class, 'show']);
        Route::get('/categories', [EcommerceProductCategoryController::class, 'index']);
        Route::get('/information', [EcommerceInformationController::class, 'index']);
        Route::get('/discounts', [EcommerceDiscountController::class, 'index']);
    });
});
