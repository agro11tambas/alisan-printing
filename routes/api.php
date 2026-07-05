<?php

use App\Http\Controllers\Api\Erp\AuthApiController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Erp\ProductCategoryApiController;
use App\Http\Controllers\Api\Erp\ProductTagApiController;
use App\Http\Controllers\Api\Erp\ProductUnitApiController;
use App\Http\Controllers\Api\Public\CustomerAuthController;
use App\Http\Controllers\Api\Public\EcommerceSaleOrderController;

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
        // Manual auth
        Route::post('/register', [CustomerAuthController::class, 'register']);
        Route::post('/login', [CustomerAuthController::class, 'login']);

        // Google OAuth
        Route::get('/google/redirect', [CustomerAuthController::class, 'redirectToGoogle']);
        Route::get('/google/callback', [CustomerAuthController::class, 'handleGoogleCallback']);

        // Protected customer auth
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/me', [CustomerAuthController::class, 'me']);
            Route::post('/logout', [CustomerAuthController::class, 'logout']);
        });
    });

    Route::prefix('ecommerce')->middleware('auth:sanctum')->group(function () {
        Route::get('/sale-orders', [EcommerceSaleOrderController::class, 'index']);
        Route::post('/sale-orders', [EcommerceSaleOrderController::class, 'store']);
        Route::get('/sale-orders/{order}', [EcommerceSaleOrderController::class, 'show']);
    });

    // Public ecommerce API
    // Route::prefix('ecommerce')->group(function () {
    //     Route::get('/product-groups', [EcommerceProductController::class, 'index']);
    //     Route::get('/product-groups/{slug}', [EcommerceProductController::class, 'show']);
    //     Route::get('/categories', [EcommerceCategoryController::class, 'index']);
    // });
});
