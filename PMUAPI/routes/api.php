<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController as ApiAuthController;

// v1 Controllers
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\StakeholderController;
use App\Http\Controllers\Api\V1\FeeTypeController;
use App\Http\Controllers\Api\V1\TransactionController;
use App\Http\Controllers\Api\V1\InventoryItemController;
use App\Http\Controllers\Api\V1\WeatherController;
use App\Http\Controllers\Api\V1\ForecastController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\AuditLogController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'PMU API is running'
    ]);
});

// API ROUTES
Route::post('/login', [ApiAuthController::class, 'login']);
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// v1 Routes
Route::prefix('v1')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    */

    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {

        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        /*
        |--------------------------------------------------------------------------
        | Roles
        |--------------------------------------------------------------------------
        */

        Route::apiResource('roles', RoleController::class);

        /*
        |--------------------------------------------------------------------------
        | Users
        |--------------------------------------------------------------------------
        */

        Route::apiResource('users', UserController::class);

        /*
        |--------------------------------------------------------------------------
        | Stakeholders
        |--------------------------------------------------------------------------
        */

        Route::apiResource('stakeholders', StakeholderController::class);

        /*
        |--------------------------------------------------------------------------
        | Fee Types
        |--------------------------------------------------------------------------
        */

        Route::apiResource('fee-types', FeeTypeController::class);

        /*
        |--------------------------------------------------------------------------
        | Transactions
        |--------------------------------------------------------------------------
        */

        Route::apiResource('transactions', TransactionController::class);

        /*
        |--------------------------------------------------------------------------
        | Inventory
        |--------------------------------------------------------------------------
        */

        Route::prefix('inventory')->group(function () {

            Route::apiResource('items', InventoryItemController::class);

            Route::get(
                'items/{item}/logs',
                [InventoryItemController::class, 'logs']
            );

            Route::post(
                'items/{item}/add-stock',
                [InventoryItemController::class, 'addStock']
            );

            Route::post(
                '/items/{item}/deduct-stock',
                [InventoryItemController::class, 'deductStock']
            );

            Route::get(
                '/logs',
                [InventoryItemController::class, 'allLogs']
            );
        });

        /*
        |--------------------------------------------------------------------------
        | Weather
        |--------------------------------------------------------------------------
        */

        Route::apiResource('weather', WeatherController::class);

        /*
        |--------------------------------------------------------------------------
        | Forecasting
        |--------------------------------------------------------------------------
        */

        Route::prefix('forecasts')->group(function () {

            Route::get('/', [ForecastController::class, 'index']);

            Route::get(
                '/chart',
                [ForecastController::class, 'chart']
            );

            Route::get(
                '/{forecast}',
                [ForecastController::class, 'show']
            );

            Route::post(
                '/generate',
                [ForecastController::class, 'generate']
            );
        });

        /*
        |--------------------------------------------------------------------------
        | Dashboard
        |--------------------------------------------------------------------------
        */

        Route::prefix('dashboard')->group(function () {

            Route::get('/', [DashboardController::class, 'index']);

            Route::get(
                '/revenue-trend',
                [DashboardController::class, 'revenueTrend']
            );

            Route::get(
                '/revenue-breakdown',
                [DashboardController::class, 'revenueBreakdown']
            );

            Route::get(
                '/inventory-summary',
                [DashboardController::class, 'inventorySummary']
            );
        });

        /*
        |--------------------------------------------------------------------------
        | Reports
        |--------------------------------------------------------------------------
        */

        Route::prefix('reports')->group(function () {

            Route::get(
                '/daily',
                [ReportController::class, 'daily']
            );

            Route::get(
                '/monthly',
                [ReportController::class, 'monthly']
            );

            Route::get(
                '/annual',
                [ReportController::class, 'annual']
            );

            Route::get(
                '/monthly/pdf',
                [ReportController::class, 'monthlyPdf']
            );

            Route::get(
                '/monthly/excel',
                [ReportController::class, 'monthlyExcel']
            );
        });

        /*
        |--------------------------------------------------------------------------
        | Audit Logs
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/audit-logs',
            [AuditLogController::class, 'index']
        );

        Route::get(
            '/audit-logs/{auditLog}',
            [AuditLogController::class, 'show']
        );
    });
});