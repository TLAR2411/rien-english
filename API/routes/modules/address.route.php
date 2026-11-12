<?php

use App\Http\Controllers\Api\Address\CommuneController;
use App\Http\Controllers\Api\Address\DistrictController;
use App\Http\Controllers\Api\Address\ProvinceController;
use App\Http\Controllers\Api\Address\VillageController;
use Illuminate\Support\Facades\Route;

// Provinces
Route::get('provinces-all', [ProvinceController::class, 'all']);
Route::get('provinces-show', [ProvinceController::class, 'show'])->middleware(['auth:api', 'permission:view-provinces']);
Route::get('provinces-list', [ProvinceController::class, 'list'])->middleware(['auth:api', 'permission:view-provinces']);

Route::post('provinces', [ProvinceController::class, 'store'])->middleware(['auth:api', 'permission:add-provinces']);
Route::put('provinces', [ProvinceController::class, 'update'])->middleware(['auth:api', 'permission:edit-provinces']);
Route::delete('provinces/{id}', [ProvinceController::class, 'delete'])->middleware(['auth:api', 'permission:delete-provinces']);
Route::patch('provinces-disable/{id}', [ProvinceController::class, 'disable'])->middleware('permission:edit-provinces');

// Districts
Route::get('districts-all', [DistrictController::class, 'all']);
Route::get('districts-show', [DistrictController::class, 'show'])->middleware(['auth:api', 'permission:view-districts']);
Route::get('districts-list', [DistrictController::class, 'list'])->middleware(['auth:api', 'permission:view-districts']);

Route::post('districts', [DistrictController::class, 'store'])->middleware(['auth:api', 'permission:add-districts']);
Route::put('districts', [DistrictController::class, 'update'])->middleware(['auth:api', 'permission:edit-districts']);
Route::delete('districts/{id}', [DistrictController::class, 'delete'])->middleware(['auth:api', 'permission:delete-districts']);
Route::patch('districts-disable/{id}', [DistrictController::class, 'disable'])->middleware('permission:edit-districts');

// Communes
Route::get('communes-all', [CommuneController::class, 'all']);
Route::get('communes-show', [CommuneController::class, 'show'])->middleware(['auth:api', 'permission:view-communes']);
Route::get('communes-list', [CommuneController::class, 'list'])->middleware(['auth:api', 'permission:view-communes']);

Route::post('communes', [CommuneController::class, 'store'])->middleware(['auth:api', 'permission:add-communes']);
Route::put('communes', [CommuneController::class, 'update'])->middleware(['auth:api', 'permission:edit-communes']);
Route::delete('communes/{id}', [CommuneController::class, 'delete'])->middleware(['auth:api', 'permission:delete-communes']);
Route::patch('communes-disable/{id}', [CommuneController::class, 'disable'])->middleware('permission:edit-communes');

// Villages
Route::get('villages-all', [VillageController::class, 'all']);
Route::get('villages-show', [VillageController::class, 'show'])->middleware(['auth:api', 'permission:view-villages']);
Route::get('villages-list', [VillageController::class, 'list'])->middleware(['auth:api', 'permission:view-villages']);

Route::post('villages', [VillageController::class, 'store'])->middleware(['auth:api', 'permission:add-villages']);
Route::put('villages', [VillageController::class, 'update'])->middleware(['auth:api', 'permission:edit-villages']);
Route::delete('villages/{id}', [VillageController::class, 'delete'])->middleware(['auth:api', 'permission:delete-villages']);
Route::patch('villages-disable/{id}', [VillageController::class, 'disable'])->middleware('permission:edit-villages');
