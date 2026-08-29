<?php

use App\Http\Controllers\Api\AuthController as ApiAuthController;
use App\Http\Controllers\Api\V1\AuditLogController;
use App\Http\Controllers\Api\V1\AuthController;
// v1 Controllers
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\FeeTypeController;
use App\Http\Controllers\Api\V1\ForecastController;
use App\Http\Controllers\Api\V1\ImportController;
use App\Http\Controllers\Api\V1\InventoryItemController;
use App\Http\Controllers\Api\V1\InventoryPlanningController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\SettingsController;
use App\Http\Controllers\Api\V1\StakeholderController;
use App\Http\Controllers\Api\V1\StakeholderTypeController;
use App\Http\Controllers\Api\V1\StatusesController;
use App\Http\Controllers\Api\V1\TransactionController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\WeatherController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'PMU API is running',
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

        Route::middleware('can:manage roles')->group(function () {
            Route::apiResource('roles', RoleController::class);
        });

        /*
        |--------------------------------------------------------------------------
        | Users
        |--------------------------------------------------------------------------
        */

        Route::middleware('can:manage users')->group(function () {
            Route::apiResource('users', UserController::class);
        });

        /*
        |--------------------------------------------------------------------------
        | Stakeholders
        |--------------------------------------------------------------------------
        */

        Route::middleware('can:manage stakeholders')->group(function () {
            Route::apiResource('stakeholders', StakeholderController::class);
            Route::apiResource('stakeholder-types', StakeholderTypeController::class);
        });

        /*
        |--------------------------------------------------------------------------
        | Fee Types
        |--------------------------------------------------------------------------
        */

        Route::middleware('can:manage settings')->group(function () {
            Route::apiResource('fee-types', FeeTypeController::class);
            Route::apiResource('settings', SettingsController::class);
            Route::apiResource('statuses', StatusesController::class);
        });

        /*
        |--------------------------------------------------------------------------
        | Transactions
        |--------------------------------------------------------------------------
        */

        Route::middleware('can:manage transactions')->group(function () {
            Route::apiResource('transactions', TransactionController::class);
        });

        /*
        |--------------------------------------------------------------------------
        | Inventory
        |--------------------------------------------------------------------------
        */

        Route::prefix('inventory')->middleware('can:manage inventory')->group(function () {

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

            /*
            |--------------------------------------------------------------------------
            | Inventory Planning
            |--------------------------------------------------------------------------
            */

            Route::prefix('planning')->group(function () {

                Route::get('/', [InventoryPlanningController::class, 'index']);

                Route::get(
                    '/view',
                    [InventoryPlanningController::class, 'planningView']
                );
            });
        });

        /*
        |--------------------------------------------------------------------------
        | Weather
        |--------------------------------------------------------------------------
        */

        Route::middleware('can:manage settings')->group(function () {
            Route::apiResource('weather', WeatherController::class);
            Route::apiResource('imports', ImportController::class);
        });

        /*
        |--------------------------------------------------------------------------
        | Forecasting
        |--------------------------------------------------------------------------
        */

        Route::prefix('forecasts')->middleware('can:view reports')->group(function () {

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

            Route::post(
                '/run-model',
                [ForecastController::class, 'runModel']
            );
        });

        /*
        |--------------------------------------------------------------------------
        | Dashboard
        |--------------------------------------------------------------------------
        */

        Route::prefix('dashboard')->middleware('can:view reports')->group(function () {

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

            Route::get(
                '/weather-revenue-correlation',
                [DashboardController::class, 'weatherRevenueCorrelation']
            );
        });

        /*
        |--------------------------------------------------------------------------
        | Reports
        |--------------------------------------------------------------------------
        */

        Route::prefix('reports')->middleware('can:view reports')->group(function () {

            Route::get(
                '/daily',
                [ReportController::class, 'daily']
            );

            Route::get(
                '/daily/excel',
                [ReportController::class, 'dailyExcel']
            );

            Route::get(
                '/daily/pdf',
                [ReportController::class, 'dailyPdf']
            );

            Route::get(
                '/daily/xlsx',
                [ReportController::class, 'dailyXlsx']
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

            Route::get(
                '/monthly/xlsx',
                [ReportController::class, 'monthlyXlsx']
            );

            Route::get(
                '/annual/excel',
                [ReportController::class, 'annualExcel']
            );

            Route::get(
                '/annual/xlsx',
                [ReportController::class, 'annualXlsx']
            );

            Route::get(
                '/annual/pdf',
                [ReportController::class, 'annualPdf']
            );
        });

        /*
        |--------------------------------------------------------------------------
        | Audit Logs
        |--------------------------------------------------------------------------
        */

        Route::middleware('can:manage users')->group(function () {
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
});

Route::middleware('auth:sanctum')->put('/auth/profile', [AuthController::class, 'updateProfile']);
