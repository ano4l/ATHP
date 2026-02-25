<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\LeaveController;
use App\Http\Controllers\Api\RequisitionController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/dashboard', [DashboardController::class, 'overview']);
    Route::get('/notifications', [DashboardController::class, 'notifications']);
    Route::post('/notifications/{notification}/read', [DashboardController::class, 'markNotificationRead']);
    Route::get('/audit', [DashboardController::class, 'auditLog']);
    Route::get('/reports', [DashboardController::class, 'reports']);

    Route::get('/requisitions', [RequisitionController::class, 'index']);
    Route::post('/requisitions', [RequisitionController::class, 'store']);
    Route::get('/requisitions/{requisition}', [RequisitionController::class, 'show']);
    Route::post('/requisitions/{requisition}/approve', [RequisitionController::class, 'approve']);
    Route::post('/requisitions/{requisition}/deny', [RequisitionController::class, 'deny']);
    Route::post('/requisitions/{requisition}/modify', [RequisitionController::class, 'requestModification']);
    Route::post('/requisitions/{requisition}/process', [RequisitionController::class, 'process']);
    Route::post('/requisitions/{requisition}/fulfil', [RequisitionController::class, 'fulfil']);
    Route::post('/requisitions/{requisition}/close', [RequisitionController::class, 'close']);
    Route::post('/requisitions/{requisition}/attachments', [RequisitionController::class, 'uploadAttachment']);

    Route::get('/leaves', [LeaveController::class, 'index']);
    Route::post('/leaves', [LeaveController::class, 'store']);
    Route::post('/leaves/{leave}/approve', [LeaveController::class, 'approve']);
    Route::post('/leaves/{leave}/deny', [LeaveController::class, 'deny']);
});
