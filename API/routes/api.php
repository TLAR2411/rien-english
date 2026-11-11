<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\CacheController;
use App\Http\Controllers\Api\LogViewerController;
use App\Http\Controllers\ExportController;
use App\Models\Core\Company;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => '/web'], function () {

    Route::post('login', [AuthController::class, 'login']);

    Route::post('refresh', [AuthController::class, 'refresh']);

    Route::get('/img', [\App\Http\Controllers\ImageController::class, 'resize']);

    Route::get('app-name', fn () => response()->json(['status' => true, 'data' => Company::orderBy('id')->value('name_kh')]));

    // Session routes skip branch.access: right after a user switch the app still
    // sends the previous user's branch.
    Route::group(['middleware' => ['auth:api']], function () {
        Route::get('bootstrap', [AuthController::class, 'bootstrap']);
        Route::post('change-user', [AuthController::class, 'changeUser']);
        Route::post('logout', [AuthController::class, 'logout']);
    });

    Route::group(['middleware' => ['auth:api', 'branch.access']], function () {
        Route::post('log-viewer-list', [LogViewerController::class, 'list'])->middleware('permission:view-activity-log');

        Route::post('export-excel', [ExportController::class, 'exportExcel'])->middleware('permission:view-settings');
        Route::post('cache-clear', [CacheController::class, 'clear'])->middleware('permission:edit-settings');

        include 'modules/address.route.php';
        include 'modules/admin.route.php';
    });
});
