<?php

use App\Http\Middleware\EnsureTwoFactorConfirmed;
use App\Http\Middleware\EnsureUserIsAdmin;
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
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'two-factor' => EnsureTwoFactorConfirmed::class,
        ]);

        $middleware->trustProxies(at: '*');
        $middleware->prepend(\App\Http\Middleware\UseRequestHost::class);

        $middleware->web(append: [
            \App\Http\Middleware\SetDisplayCurrency::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'billing/webhook/*',
        ]);

        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
