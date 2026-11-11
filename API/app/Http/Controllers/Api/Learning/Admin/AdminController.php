<?php

namespace App\Http\Controllers\Api\Learning\Admin;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Concerns\ChecksCrudPermissions;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;

/** Base for learning admin controllers: JSON replies + CRUD permission checks. */
abstract class AdminController extends Controller implements HasMiddleware
{
    use ApiResponses, ChecksCrudPermissions;

    /** Rows per page asked by the table (?limit=), default from config/learning.php, max 100. */
    protected function perPage(Request $request): int
    {
        return min(100, max(1, $request->integer('limit', config('learning.per_page'))));
    }

    /** A table filter value: ?filter[name]=… */
    protected function filter(Request $request, string $name): mixed
    {
        $value = $request->input("filter.{$name}");

        return $value === '' ? null : $value;
    }
}
