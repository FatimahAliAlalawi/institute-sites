<?php

use App\Http\Middleware\AdminAuthenticate;
use App\Http\Middleware\EnsureInstalled;
use App\Http\Middleware\RedirectIfInstalled;
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
        // الاستضافات خلف Cloudflare/Proxy: لاكتشاف https بشكل صحيح
        $middleware->trustProxies(at: '*');
        // إشعارات بوابة الدفع تأتي من خادم ميسر (محمية برمز سري بدل CSRF)
        $middleware->validateCsrfTokens(except: ['payments/moyasar/webhook']);
        $middleware->alias([
            'admin' => AdminAuthenticate::class,
            'installed' => EnsureInstalled::class,
            'profile.complete' => \App\Http\Middleware\EnsureProfileComplete::class,
            'not.installed' => RedirectIfInstalled::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
