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
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Front-end no mesmo domínio usa a sessão web nas rotas /api (Sanctum SPA)
        // Atrás do Caddy da borda (HTTPS termina lá): confiar em X-Forwarded-*
        $middleware->trustProxies(at: '*');

        // Idioma pelo prefixo da URL (antes do roteamento)
        $middleware->prepend(\App\Http\Middleware\DefinirIdioma::class);

        $middleware->statefulApi();

        $middleware->web(append: [
            \App\Http\Middleware\EncerrarContaBloqueada::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
