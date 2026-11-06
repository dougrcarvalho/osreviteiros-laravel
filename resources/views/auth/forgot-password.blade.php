<x-layouts.app title="Esqueci minha senha">
    <div class="rv-page">
        <div class="rv-container">
            <div class="rv-auth">
                <h1>Redefinir senha</h1>
                <p class="mb-4 text-sm">Informe seu e-mail e enviaremos um link para você criar uma nova senha.</p>

                @if (session('status'))
                    <div class="rv-alerta rv-alerta--sucesso">{{ session('status') }}</div>
                @endif

                <form method="POST" action="{{ route('password.email') }}">
                    @csrf
                    <div class="rv-campo">
                        <label class="rv-label" for="email">E-mail</label>
                        <input id="email" name="email" type="email" class="rv-input" value="{{ old('email') }}" required autofocus>
                        @error('email')<p class="rv-erro-campo">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="rv-btn rv-btn--primary rv-btn--lg">Enviar link</button>
                </form>

                <div class="rv-auth__links"><a href="{{ route('login') }}">Voltar ao login</a></div>
            </div>
        </div>
    </div>
</x-layouts.app>
