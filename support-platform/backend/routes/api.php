<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TenantController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Authentication
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/password', [AuthController::class, 'updatePassword']);
    
    // Tenant management (Super Admin only - add middleware in production)
    Route::prefix('tenants')->group(function () {
        Route::get('/', [TenantController::class, 'index']);
        Route::post('/', [TenantController::class, 'store']);
        Route::get('/{tenant}', [TenantController::class, 'show']);
        Route::put('/{tenant}', [TenantController::class, 'update']);
        Route::delete('/{tenant}', [TenantController::class, 'destroy']);
        Route::get('/{tenant}/branding', [TenantController::class, 'getBranding']);
        Route::put('/{tenant}/branding', [TenantController::class, 'updateBranding']);
    });
});
