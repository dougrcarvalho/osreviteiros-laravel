<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** Conta bloqueada pelo painel perde a sessão na próxima requisição */
class EncerrarContaBloqueada
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isBloqueado()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => __('Esta conta está bloqueada. Entre em contato com o suporte.')]);
        }

        return $next($request);
    }
}
