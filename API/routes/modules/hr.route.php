<?php

use App\Http\Controllers\Api\Hr\EmployeeController;
use Illuminate\Support\Facades\Route;

// Dashboard
Route::get("employees-count-by-position", [EmployeeController::class, "countByPosition"]);
Route::get("employees-monthly-growth", [EmployeeController::class, "monthlyGrowth"]);
Route::get("employees-dashboard-summary", [EmployeeController::class, "dashboardSummary"]);

// Employees
Route::get("employees-show", [EmployeeController::class, "show"])->middleware(['auth:api', 'permission:view-employees']);
Route::get("employees-list", [EmployeeController::class, "list"])->middleware(['auth:api', 'permission:view-employees']);

Route::post("employees", [EmployeeController::class, "store"])->middleware(['auth:api', 'permission:add-employees']);
Route::put("employees", [EmployeeController::class, "update"])->middleware(['auth:api', 'permission:edit-employees']);
Route::delete("employees/{id}", [EmployeeController::class, "delete"])->middleware(['auth:api', 'permission:delete-employees']);
Route::patch("employees-disable/{id}", [EmployeeController::class, "disable"])->middleware('permission:change-active-employees');
Route::patch("employees-terminate/{id}", [EmployeeController::class, "terminate"])->middleware('permission:edit-employees');
Route::patch("employees-terminate-update/{id}", [EmployeeController::class, "updateTermination"])->middleware(['auth:api', 'permission:edit-employees']);
