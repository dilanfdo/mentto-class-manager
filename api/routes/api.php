<?php

use App\Http\Controllers\Admin\OverviewController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Admin\TenantFeatureController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\School;
use App\Support\Features;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // NexusDigitalLabs admin: every client and the features each one uses.
    Route::prefix('admin')->middleware('platform.admin')->group(function () {
        Route::get('/overview', OverviewController::class);
        Route::get('/features', fn () => ['data' => Features::all()]);
        Route::apiResource('tenants', TenantController::class)->except('destroy');
        Route::put('/tenants/{tenant}/features/{feature}', [TenantFeatureController::class, 'update']);
    });

    // A client's own school app, always scoped to that client's data.
    Route::middleware('tenant')->group(function () {
        Route::get('/dashboard', School\DashboardController::class);
        Route::apiResource('students', School\StudentController::class);
        Route::apiResource('classes', School\ClassGroupController::class)->parameters(['classes' => 'class']);

        Route::middleware('feature:fees')->group(function () {
            Route::get('/payments', [School\PaymentController::class, 'index']);
            Route::post('/payments', [School\PaymentController::class, 'store']);
            Route::post('/payments/{payment}/approve', [School\PaymentController::class, 'approve']);
        });

        Route::middleware('feature:attendance')->group(function () {
            Route::get('/classes/{class}/attendance', [School\AttendanceController::class, 'show']);
            Route::put('/classes/{class}/attendance', [School\AttendanceController::class, 'store']);
        });

        Route::middleware('feature:exams')->group(function () {
            Route::get('/exams', [School\ExamController::class, 'index']);
            Route::post('/exams', [School\ExamController::class, 'store']);
            Route::get('/exams/{exam}', [School\ExamController::class, 'show']);
        });

        Route::get('/reports/income', [School\ReportController::class, 'income'])->middleware('feature:reports');
    });
});
