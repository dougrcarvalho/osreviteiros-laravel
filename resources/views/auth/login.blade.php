<x-layouts.app :title="__('Entrar')">
    <div class="rv-page">
        <div class="rv-container">
            <div class="rv-auth">
                <h1>{{ __('Entrar') }}</h1>

                @if (session('status'))
                    <div class="rv-alerta rv-alerta--sucesso">{{ session('status') }}</div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <div class="rv-campo">
                        <label class="rv-label" for="email">{{ __('E-mail') }}</label>
                        <input id="email" name="email" type="email" class="rv-input" value="{{ old('email') }}" required autocomplete="email" autofocus>
                        @error('email')<p class="rv-erro-campo">{{ $message }}</p>@enderror
                    </div>

                    <div class="rv-campo">
                        <label class="rv-label" for="password">{{ __('Senha') }}</label>
                        <input id="password" name="password" type="password" class="rv-input" required autocomplete="current-password">
                        @error('password')<p class="rv-erro-campo">{{ $message }}</p>@enderror
                    </div>

                    <label class="rv-campo flex items-center gap-2 text-sm">
                        <input type="checkbox" name="remember" value="1"> {{ __('Manter conectado') }}
                    </label>

                    <button type="submit" class="rv-btn rv-btn--primary rv-btn--lg">{{ __('Entrar') }}</button>
                </form>

                <div class="rv-auth__links">
                    <a href="{{ route('password.request') }}">{{ __('Esqueci minha senha') }}</a>
                    <span>{{ __('Veio do site antigo?') }} <a href="{{ route('password.request') }}">{{ __('Crie uma nova senha') }}</a></span>
                    <span>{{ __('Não tem conta?') }} <a href="{{ route('cadastro') }}">{{ __('Cadastre-se grátis') }}</a></span>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
