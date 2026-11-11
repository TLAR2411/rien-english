<?php

/*
| Learning content admin — /api/web/admin/...
| Already behind auth:api + role admin|teacher (see routes/api.php).
*/

use App\Http\Controllers\Api\Learning\Admin\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('dashboard', DashboardController::class);
