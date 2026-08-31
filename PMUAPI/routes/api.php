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
use App\Http\Controllers\Api\V1\PermissionController;
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

        Route::get('roles', [RoleController::class, 'index'])->middleware('can:view roles');
        Route::post('roles', [RoleController::class, 'store'])->middleware('can:create roles');
        Route::get('roles/{role}', [RoleController::class, 'show'])->middleware('can:view roles');
        Route::put('roles/{role}', [RoleController::class, 'update'])->middleware('can:edit roles');
        Route::delete('roles/{role}', [RoleController::class, 'destroy'])->middleware('can:delete roles');
        Route::get('permissions', [PermissionController::class, 'index'])->middleware('can:view roles');

        /*
        |--------------------------------------------------------------------------
        | Users
        |--------------------------------------------------------------------------
        */

        Route::get('users', [UserController::class, 'index'])->middleware('can:view users');
        Route::post('users', [UserController::class, 'store'])->middleware('can:create users');
        Route::get('users/{user}', [UserController::class, 'show'])->middleware('can:view users');
        Route::put('users/{user}', [UserController::class, 'update'])->middleware('can:edit users');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->middleware('can:delete users');

        /*
        |--------------------------------------------------------------------------
        | Stakeholders
        |--------------------------------------------------------------------------
        */

        Route::get('stakeholders', [StakeholderController::class, 'index'])->middleware('can:view stakeholders');
        Route::post('stakeholders', [StakeholderController::class, 'store'])->middleware('can:create stakeholders');
        Route::get('stakeholders/{stakeholder}', [StakeholderController::class, 'show'])->middleware('can:view stakeholders');
        Route::put('stakeholders/{stakeholder}', [StakeholderController::class, 'update'])->middleware('can:edit stakeholders');
        Route::delete('stakeholders/{stakeholder}', [StakeholderController::class, 'destroy'])->middleware('can:delete stakeholders');

        Route::get('stakeholder-types', [StakeholderTypeController::class, 'index'])->middleware('can:view stakeholder types');
        Route::post('stakeholder-types', [StakeholderTypeController::class, 'store'])->middleware('can:create stakeholder types');
        Route::get('stakeholder-types/{stakeholder_type}', [StakeholderTypeController::class, 'show'])->middleware('can:view stakeholder types');
        Route::put('stakeholder-types/{stakeholder_type}', [StakeholderTypeController::class, 'update'])->middleware('can:edit stakeholder types');
        Route::delete('stakeholder-types/{stakeholder_type}', [StakeholderTypeController::class, 'destroy'])->middleware('can:delete stakeholder types');

        /*
        |--------------------------------------------------------------------------
        | Fee Types / Settings / Statuses
        |--------------------------------------------------------------------------
        */

        Route::get('fee-types', [FeeTypeController::class, 'index'])->middleware('can:view fee types');
        Route::post('fee-types', [FeeTypeController::class, 'store'])->middleware('can:create fee types');
        Route::get('fee-types/{fee_type}', [FeeTypeController::class, 'show'])->middleware('can:view fee types');
        Route::put('fee-types/{fee_type}', [FeeTypeController::class, 'update'])->middleware('can:edit fee types');
        Route::delete('fee-types/{fee_type}', [FeeTypeController::class, 'destroy'])->middleware('can:delete fee types');

        Route::get('settings', [SettingsController::class, 'index'])->middleware('can:view settings');
        Route::post('settings', [SettingsController::class, 'store'])->middleware('can:create settings');
        Route::get('settings/{setting}', [SettingsController::class, 'show'])->middleware('can:view settings');
        Route::put('settings/{setting}', [SettingsController::class, 'update'])->middleware('can:edit settings');
        Route::delete('settings/{setting}', [SettingsController::class, 'destroy'])->middleware('can:delete settings');

        Route::get('statuses', [StatusesController::class, 'index'])->middleware('can:view statuses');
        Route::post('statuses', [StatusesController::class, 'store'])->middleware('can:create statuses');
        Route::get('statuses/{status}', [StatusesController::class, 'show'])->middleware('can:view statuses');
        Route::put('statuses/{status}', [StatusesController::class, 'update'])->middleware('can:edit statuses');
        Route::delete('statuses/{status}', [StatusesController::class, 'destroy'])->middleware('can:delete statuses');

        /*
        |--------------------------------------------------------------------------
        | Transactions
        |--------------------------------------------------------------------------
        */

        Route::get('transactions', [TransactionController::class, 'index'])->middleware('can:view transactions');
        Route::post('transactions', [TransactionController::class, 'store'])->middleware('can:create transactions');
        Route::get('transactions/{transaction}', [TransactionController::class, 'show'])->middleware('can:view transactions');
        Route::put('transactions/{transaction}', [TransactionController::class, 'update'])->middleware('can:edit transactions');
        Route::delete('transactions/{transaction}', [TransactionController::class, 'destroy'])->middleware('can:delete transactions');

        /*
        |--------------------------------------------------------------------------
        | Inventory
        |--------------------------------------------------------------------------
        */

        Route::prefix('inventory')->group(function () {

            Route::get('items', [InventoryItemController::class, 'index'])->middleware('can:view inventory');
            Route::post('items', [InventoryItemController::class, 'store'])->middleware('can:create inventory');
            Route::get('items/{item}', [InventoryItemController::class, 'show'])->middleware('can:view inventory');
            Route::put('items/{item}', [InventoryItemController::class, 'update'])->middleware('can:edit inventory');
            Route::delete('items/{item}', [InventoryItemController::class, 'destroy'])->middleware('can:delete inventory');

            Route::get('items/{item}/logs', [InventoryItemController::class, 'logs'])->middleware('can:view inventory');
            Route::post('items/{item}/add-stock', [InventoryItemController::class, 'addStock'])->middleware('can:edit inventory');
            Route::post('items/{item}/deduct-stock', [InventoryItemController::class, 'deductStock'])->middleware('can:edit inventory');
            Route::get('logs', [InventoryItemController::class, 'allLogs'])->middleware('can:view inventory');

            /*
            |--------------------------------------------------------------------------
            | Inventory Planning
            |--------------------------------------------------------------------------
            */

            Route::prefix('planning')->middleware('can:view inventory planning')->group(function () {

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

        Route::get('weather/forecast', [WeatherController::class, 'forecast'])->middleware('can:view weather');
        Route::get('weather', [WeatherController::class, 'index'])->middleware('can:view weather');
        Route::post('weather', [WeatherController::class, 'store'])->middleware('can:create weather');
        Route::get('weather/{weather}', [WeatherController::class, 'show'])->middleware('can:view weather');
        Route::put('weather/{weather}', [WeatherController::class, 'update'])->middleware('can:edit weather');
        Route::delete('weather/{weather}', [WeatherController::class, 'destroy'])->middleware('can:delete weather');

        Route::post('imports', [ImportController::class, 'store'])->middleware('can:create imports');

        /*
        |--------------------------------------------------------------------------
        | Forecasting
        |--------------------------------------------------------------------------
        */

        Route::prefix('forecasts')->group(function () {

            Route::get('/', [ForecastController::class, 'index'])->middleware('can:view forecasts');
            Route::get('/table', [ForecastController::class, 'table'])->middleware('can:view forecasts');
            Route::get('/chart', [ForecastController::class, 'chart'])->middleware('can:view forecasts');
            Route::get('/{forecast}', [ForecastController::class, 'show'])->middleware('can:view forecasts');

            Route::post('/generate', [ForecastController::class, 'generate'])->middleware('can:create forecasts');
            Route::post('/run-model', [ForecastController::class, 'runModel'])->middleware('can:create forecasts');
        });

        /*
        |--------------------------------------------------------------------------
        | Dashboard
        |--------------------------------------------------------------------------
        */

        Route::prefix('dashboard')->middleware('can:view dashboard')->group(function () {

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

        Route::middleware('can:view audit logs')->group(function () {
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
