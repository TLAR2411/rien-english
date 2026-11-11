<?php

use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\Admin\AdminDashboardController as AdminDashboardController;
use App\Http\Controllers\Api\Admin\PermissionController;
use App\Http\Controllers\Api\Admin\PositionController;
use App\Http\Controllers\Api\Admin\RoleController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\UserAccessRequestController;
use App\Http\Controllers\Api\UserController;

// User requests: HR asks for a login account when an employee is created
Route::get("user-access-requests-list", [UserAccessRequestController::class, "list"])->middleware(['auth:api', 'permission:add-users']);
Route::get("user-access-requests-count", [UserAccessRequestController::class, "count"])->middleware(['auth:api', 'permission:add-users']);
Route::get("user-access-requests-employee", [UserAccessRequestController::class, "employee"])->middleware(['auth:api', 'permission:add-users']);
Route::post("user-access-requests-dismiss", [UserAccessRequestController::class, "dismiss"])->middleware(['auth:api', 'permission:add-users']);

// General settings (settings table); secrets are write-only
Route::get("settings-all", [SettingController::class, "all"])->middleware(['auth:api', 'permission:view-settings']);
Route::put("settings", [SettingController::class, "update"])->middleware(['auth:api', 'permission:edit-settings']);

// Admin Dashboard
Route::get("admin-dashboard", [AdminDashboardController::class, "index"])->middleware(['auth:api', 'permission:view-users']);

// Activity Logs
Route::get("activity-log-list", [ActivityLogController::class, "list"])->middleware(['auth:api', 'permission:view-activity-log']);
Route::delete("activity-log-delete-by-date/{before_date}", [ActivityLogController::class, "deleteByDate"])->middleware(['auth:api', 'permission:view-activity-log']);

// Users
Route::get("users-all", [UserController::class, "all"]);
Route::get("users-all-under-users", [UserController::class, "allUnderUsers"]);
Route::get("users-diagram", [UserController::class, "diagram"]);
Route::get("users-show", [UserController::class, "show"])->middleware('permission:view-users');
Route::get("users-list", [UserController::class, "list"])->middleware(['auth:api', 'permission:view-users']);

Route::post("users", [UserController::class, "store"])->middleware(['auth:api', 'permission:add-users']);
Route::put("users", [UserController::class, "update"])->middleware(['auth:api', 'permission:edit-users']);
Route::delete("users/{id}", [UserController::class, "delete"])->middleware(['auth:api', 'permission:delete-users']);
Route::patch("users-disable/{id}", [UserController::class, "disable"])->middleware(['auth:api', 'permission:change-active-users']);
Route::patch("users-under-user", [UserController::class, "changeUnderUser"])->middleware(['auth:api', 'permission:edit-users']);

Route::post("users-password", [UserController::class, "password"]);
Route::post("users-permission", [UserController::class, "permission"])->middleware('permission:change-permission-users');
Route::post("users-change-password", [UserController::class, "changePassword"])->middleware('permission:change-password-users');
Route::post("users-change-username-email", [UserController::class, "changeUsernameEmail"])->middleware('permission:edit-users');
Route::post("users-change-image-path", [UserController::class, "changeImagePath"]);
Route::post("users-link-user", [UserController::class, "linkUser"])->middleware('permission:add-users');

// Branches
Route::get("branches-all", [BranchController::class, "all"]);
Route::get("branches-show", [BranchController::class, "show"]);
Route::get("branches-list", [BranchController::class, "list"])->middleware(['auth:api', 'permission:view-branches']);

Route::post("branches", [BranchController::class, "store"])->middleware(['auth:api', 'permission:add-branches']);
Route::put("branches", [BranchController::class, "update"])->middleware(['auth:api', 'permission:edit-branches']);
Route::delete("branches/{id}", [BranchController::class, "delete"])->middleware(['auth:api', 'permission:delete-branches']);
Route::patch("branches-disable/{id}", [BranchController::class, "disable"])->middleware('permission:edit-branches');

// Departments
Route::get("departments-all", [DepartmentController::class, "all"]);
Route::get("departments-show", [DepartmentController::class, "show"]);
Route::get("departments-list", [DepartmentController::class, "list"])->middleware(['auth:api', 'permission:view-departments']);

Route::post("departments", [DepartmentController::class, "store"])->middleware(['auth:api', 'permission:add-departments']);
Route::put("departments", [DepartmentController::class, "update"])->middleware(['auth:api', 'permission:edit-departments']);
Route::delete("departments/{id}", [DepartmentController::class, "delete"])->middleware(['auth:api', 'permission:delete-departments']);
Route::patch("departments/{id}", [DepartmentController::class, "disable"])->middleware('permission:edit-departments');

// Roles
Route::get("roles-all", [RoleController::class, "all"]);
Route::get("roles-show", [RoleController::class, "show"]);
Route::get("roles-list", [RoleController::class, "list"])->middleware(['auth:api', 'permission:view-roles']);

Route::post("roles", [RoleController::class, "store"])->middleware(['auth:api', 'permission:add-roles']);
Route::put("roles", [RoleController::class, "update"])->middleware(['auth:api', 'permission:edit-roles']);
Route::delete("roles/{id}", [RoleController::class, "delete"])->middleware(['auth:api', 'permission:delete-roles']);

// Positions
Route::get("positions-all", [PositionController::class, "all"]);
Route::get("positions-show", [PositionController::class, "show"]);
Route::get("positions-list", [PositionController::class, "list"])->middleware(['auth:api', 'permission:view-positions']);

Route::post("positions", [PositionController::class, "store"])->middleware(['auth:api', 'permission:add-positions']);
Route::put("positions", [PositionController::class, "update"])->middleware(['auth:api', 'permission:edit-positions']);
Route::delete("positions/{id}", [PositionController::class, "delete"])->middleware(['auth:api', 'permission:delete-positions']);

// Permissions
Route::get("permissions-all", [PermissionController::class, "all"]);
Route::get("permissions-show", [PermissionController::class, "show"]);
Route::get("permissions-list", [PermissionController::class, "list"])->middleware(['auth:api', 'permission:view-permissions']);

Route::post("permissions", [PermissionController::class, "store"])->middleware(['auth:api', 'permission:add-permissions']);
Route::put("permissions", [PermissionController::class, "update"])->middleware(['auth:api', 'permission:edit-permissions']);
Route::delete("permissions/{id}", [PermissionController::class, "delete"])->middleware(['auth:api', 'permission:delete-permissions']);
