<?php

use App\Http\Middleware\RoleMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

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
        // Staff areas go to the password login; everything else to the customer SMS-code login.
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('admin', 'admin/*', 'delivery', 'delivery/*') ? route('staff.login') : route('login'));
        $middleware->redirectUsersTo(fn () => route(auth()->user()->role->homeRoute()));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
