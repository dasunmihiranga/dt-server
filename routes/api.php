<?php

use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BillerController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\TestController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WalletController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Authentication routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);
    });
});

// Test routes (remove in production)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/test-topup', [TestController::class, 'testTopup']);
    Route::get('/test-auth', [TestController::class, 'testAuth']);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // User management (matching frontend expectations)
    Route::get('/user/profile', [UserController::class, 'profile']);
    Route::get('/users/search', [UserController::class, 'search']);
    
    // Dashboard stats (matching frontend expectations)
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    
    // Direct transaction endpoints (matching frontend expectations)
    Route::post('/topup', [WalletController::class, 'topup']);
    Route::post('/transfer', [WalletController::class, 'transfer']);
    
    // Bills endpoint with correct path
    Route::post('/bills/pay', [BillerController::class, 'payBill']);
    Route::get('/billers', [BillerController::class, 'index']); // Add GET billers for frontend
    
    // Wallet operations (alternative paths)
    Route::prefix('wallet')->group(function () {
        Route::get('/balance', [WalletController::class, 'balance']);
        Route::post('/topup', [WalletController::class, 'topup']);
        Route::post('/transfer', [WalletController::class, 'transfer']);
    });

    // Biller operations
    Route::prefix('billers')->group(function () {
        Route::get('/', [BillerController::class, 'index']);
        Route::post('/pay', [BillerController::class, 'payBill']);
    });

    // Transaction history
    Route::prefix('transactions')->group(function () {
        Route::get('/', [TransactionController::class, 'index']);
        Route::get('/stats', [TransactionController::class, 'stats']);
        Route::get('/{id}', [TransactionController::class, 'show']);
    });

    // Activity logs
    Route::prefix('activities')->group(function () {
        Route::get('/', [ActivityController::class, 'index']);
        Route::get('/{id}', [ActivityController::class, 'show']);
    });
});
