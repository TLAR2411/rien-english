<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'decrypt.request' => \App\Http\Middleware\DecryptRequest::class,
            'encrypt.response' => \App\Http\Middleware\EncryptResponse::class,
            'branch.access' => \App\Http\Middleware\EnsureBranchAccess::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'api/*',
            'oauth/*',
        ]);

        $middleware->trustProxies(
            at: '*',  // trust all — see caveat below
            headers: Request::HEADER_X_FORWARDED_FOR
            | Request::HEADER_X_FORWARDED_HOST
            | Request::HEADER_X_FORWARDED_PORT
            | Request::HEADER_X_FORWARDED_PROTO,
        );

        // Do not enable statefulApi() — this app uses Passport Bearer tokens
        // (Postman-style), not Sanctum cookie/CSRF SPA auth. Enabling it makes
        // browser requests behave differently from Postman and can break login.
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
