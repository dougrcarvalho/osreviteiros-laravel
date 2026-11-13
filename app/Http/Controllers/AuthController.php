<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Services\CadastroService;
use App\Services\PlanoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showRegister(Request $request, PlanoService $planos): View
    {
        return view('auth.register', [
            'planos'       => $planos->todos(),
            'planoEscolhido' => $planos->porSlug($request->query('plano'))?->slug ?? 'gratis',
        ]);
    }

    public function register(RegisterRequest $request, CadastroService $cadastro, PlanoService $planos): RedirectResponse
    {
        $user = $cadastro->cadastrar($request->validated());

        Auth::login($user);
        $request->session()->regenerate();

        if ($checkout = $cadastro->checkoutUrl($planos->porSlug($request->validated('plano')), $user)) {
            return redirect()->away($checkout);
        }

        return redirect()->intended(route('familias.index'))
            ->with('success', __('Bem-vindo(a) aos Reviteiros!'));
    }

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        // Cliente vindo de uma tentativa de abrir o painel não volta para /admin (403)
        if (! $request->user()->isFuncionario() && str_starts_with((string) $request->session()->get('url.intended'), url('/admin'))) {
            $request->session()->forget('url.intended');
        }

        return redirect()->intended(route('familias.index'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('familias.index');
    }
}
