<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Dashboard\ManagementDashboardController;
use App\Http\Controllers\Api\V1\Property\PropertyController;
use App\Http\Controllers\Api\V1\Property\PropertyImageController;
use App\Http\Controllers\Api\V1\Property\PropertyWorkflowController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {

    Route::prefix('auth')->group(function (): void {
        Route::post('login', [AuthController::class, 'login'])
            ->middleware('throttle:5,1');

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
        });
    });

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::apiResource('properties', PropertyController::class)
            ->only(['index', 'store', 'show', 'update']);
        Route::post('properties/{property}/images', [PropertyImageController::class, 'store']);
        Route::delete('properties/{property}/images/{image}', [PropertyImageController::class, 'destroy']);

        Route::get('property-workflow', [PropertyWorkflowController::class, 'index']);
        Route::get('property-workflow/{property}', [PropertyWorkflowController::class, 'show']);
        Route::post('properties/{property}/workflow/submit', [PropertyWorkflowController::class, 'submit']);
        Route::post('properties/{property}/workflow/review', [PropertyWorkflowController::class, 'review']);
        Route::post('properties/{property}/workflow/decision', [PropertyWorkflowController::class, 'decide']);

        Route::get('dashboard/summary', [ManagementDashboardController::class, 'summary']);
        Route::get('dashboard/reports', [ManagementDashboardController::class, 'reports']);
    });

});
