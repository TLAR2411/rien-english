<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'status' => true,
        'message' => config('app.name').' API',
        'version' => '1.0.0',
        'environment' => app()->environment(), // Optional: helpful for debugging dev vs prod
        'maintenance_mode' => app()->isDownForMaintenance(),
    ]);
});

Route::get('/img', [\App\Http\Controllers\ImageController::class, 'resize']);

// Image proxy for html2canvas. Only fetches from hosts we own (app.proxy_allowed_hosts).
Route::get('/h2c-proxy', function (\Illuminate\Http\Request $request) {
    $url = $request->query('url');
    abort_unless(filter_var($url, FILTER_VALIDATE_URL), 400);
    abort_unless(in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true), 400);
    abort_unless(in_array(parse_url($url, PHP_URL_HOST), config('app.proxy_allowed_hosts'), true), 403);

    $client = new \GuzzleHttp\Client(['timeout' => 10, 'allow_redirects' => false]);
    $res = $client->get($url);

    $ctype = $res->getHeaderLine('Content-Type');
    abort_unless(str_starts_with($ctype, 'image/'), 415);

    return response($res->getBody(), 200, [
        'Content-Type' => $ctype,
        'Cache-Control' => 'no-cache',
        'Access-Control-Allow-Origin' => '*',
    ]);
});
