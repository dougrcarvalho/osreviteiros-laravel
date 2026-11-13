<x-layouts.app :title="__('Cadastro')">
    <div class="rv-page">
        <div class="rv-container">
            <div class="rv-auth">
                <h1>{{ __('Criar conta') }}</h1>

                <form method="POST" action="{{ route('cadastro') }}">
                    @csrf

                    <div class="rv-campo">
                        <span class="rv-label">{{ __('Plano') }}</span>
                        <div class="rv-planos-radio">
                            @foreach ($planos as $plano)
                                <label class="rv-plano-opcao">
                                    <input type="radio" name="plano" value="{{ $plano->slug }}" @checked(old('plano', $planoEscolhido) === $plano->slug)>
                                    {{ __($plano->nome) }}
                                </label>
                            @endforeach
                        </div>
                        <p class="mt-1 text-xs" style="color: var(--rv-texto-claro)">
                            {{ __('Planos pagos: após o cadastro você segue para o pagamento. O acesso é liberado assim que ele for confirmado.') }}
                        </p>
                        @error('plano')<p class="rv-erro-campo">{{ $message }}</p>@enderror
                    </div>

                    <div class="rv-campo">
                        <label class="rv-label" for="name">{{ __('Nome') }}</label>
                        <input id="name" name="name" type="text" class="rv-input" value="{{ old('name') }}" required autocomplete="name" autofocus>
                        @error('name')<p class="rv-erro-campo">{{ $message }}</p>@enderror
                    </div>

                    <div class="rv-campo">
                        <label class="rv-label" for="email">{{ __('E-mail') }}</label>
                        <input id="email" name="email" type="email" class="rv-input" value="{{ old('email') }}" required autocomplete="email">
                        @error('email')<p class="rv-erro-campo">{{ $message }}</p>@enderror
                    </div>

                    <div class="rv-campo">
                        <label class="rv-label" for="password">{{ __('Senha') }}</label>
                        <input id="password" name="password" type="password" class="rv-input" required autocomplete="new-password">
                        @error('password')<p class="rv-erro-campo">{{ $message }}</p>@enderror
                    </div>

                    <div class="rv-campo">
                        <label class="rv-label" for="password_confirmation">{{ __('Confirmar senha') }}</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" class="rv-input" required autocomplete="new-password">
                    </div>

                    <button type="submit" class="rv-btn rv-btn--primary rv-btn--lg">{{ __('Criar conta') }}</button>
                </form>

                <div class="rv-auth__links">
                    <span>{{ __('Já tem conta?') }} <a href="{{ route('login') }}">{{ __('Entrar') }}</a></span>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
