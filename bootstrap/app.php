<?php

use App\Http\Middleware\RoleMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Behind a tunnel / PaaS load balancer: honour X-Forwarded-* so URLs come out as https.
        $middleware->trustProxies(at: '*');
        $middleware->alias(['role' => RoleMiddleware::class]);
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route(auth()->user()->role->homeRoute()));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
