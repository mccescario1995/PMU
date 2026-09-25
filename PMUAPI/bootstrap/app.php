<?php

use App\Console\Commands\BuildMlFeatures;
use App\Console\Commands\SyncRevenueHistories;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Request;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: "",
    )
    ->withCommands([
        SyncRevenueHistories::class,
        BuildMlFeatures::class,
    ])
    ->withSchedule(function (Schedule $schedule) {
        // Build features daily at 2 AM (after data entry)
        $schedule->command('ml:build-features')->dailyAt('02:00'); 
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(HandleCors::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();