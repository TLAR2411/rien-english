<?php

namespace App\Http\Middleware;

use Closure;

class ImageCors
{
    public function handle($request, Closure $next)
    {
        $resp = $next($request);
        $resp->headers->set('Access-Control-Allow-Origin', 'http://localhost:5173'); // or '*'
        $resp->headers->set('Access-Control-Allow-Methods', 'GET, OPTIONS');
        $resp->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
        $resp->headers->set('Access-Control-Allow-Credentials', 'false');
        return $resp;
    }
}
