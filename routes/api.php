<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Automation\PropertyAutomationController;
use App\Http\Controllers\Api\V1\Clients\ClientMatchingController;
use App\Http\Controllers\Api\V1\Dashboard\ManagementDashboardController;
use App\Http\Controllers\Api\V1\Owner\OwnerContactRequestController;
use App\Http\Controllers\Api\V1\Owner\OwnerController;
use App\Http\Controllers\Api\V1\Property\OwnerContactAccessRequestController;
use App\Http\Controllers\Api\V1\Property\PropertyController;
use App\Http\Controllers\Api\V1\Property\PropertyImageController;
use App\Http\Controllers\Api\V1\Property\PropertySearchController;
use App\Http\Controllers\Api\V1\Property\PropertyWorkflowController;
use App\Http\Controllers\Api\V1\Property\SavedPropertySearchController;
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
        Route::get('properties/map-points', [PropertyAutomationController::class, 'mapPoints']);
        
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

        Route::get('clients', [ClientMatchingController::class, 'index']);
        Route::post('clients', [ClientMatchingController::class, 'store']);
        Route::get('clients/{client}', [ClientMatchingController::class, 'show']);
        Route::put('clients/{client}', [ClientMatchingController::class, 'update']);
        Route::put('clients/{client}/requirements', [ClientMatchingController::class, 'saveRequirement']);
        Route::get('clients/{client}/matches', [ClientMatchingController::class, 'matches']);
        Route::post('clients/{client}/matches/{property}/shortlist', [ClientMatchingController::class, 'shortlist']);
        Route::delete('clients/{client}/matches/{property}/shortlist', [ClientMatchingController::class, 'removeShortlist']);
        Route::get('clients/{client}/activities', [ClientMatchingController::class, 'activities']);
        Route::post('clients/{client}/activities', [ClientMatchingController::class, 'storeActivity']);

        Route::get('automation/overview', [PropertyAutomationController::class, 'overview']);
        Route::get('automation/alerts', [PropertyAutomationController::class, 'alerts']);
        Route::post('automation/alerts/{alert}/acknowledge', [PropertyAutomationController::class, 'acknowledgeAlert']);
        Route::get('automation/duplicates', [PropertyAutomationController::class, 'duplicateCandidates']);
        Route::put('properties/{property}/location', [PropertyAutomationController::class, 'updateLocation']);
        Route::put('properties/{property}/asking-price', [PropertyAutomationController::class, 'updatePrice']);
        Route::get('properties/{property}/price-history', [PropertyAutomationController::class, 'priceHistory']);

        Route::get('property-search', PropertySearchController::class);
        Route::apiResource('saved-property-searches', SavedPropertySearchController::class)
            ->only(['index', 'store', 'destroy']);
        Route::post(
            'properties/{property}/owner-contact-requests',
            [OwnerContactAccessRequestController::class, 'store']
        );

        Route::apiResource('owners', OwnerController::class)
        ->only(['index', 'store', 'show', 'update', 'destroy']);

        Route::post('owners/{owner}/contact-requests', [OwnerContactRequestController::class, 'store']);
        Route::get('owner-contact-requests', [OwnerContactRequestController::class, 'index']);
        Route::patch('owner-contact-requests/{contactRequest}/review', [OwnerContactRequestController::class, 'review']);
    });

});
