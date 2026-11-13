<x-layouts.app :title="__('Nova senha')">
    <div class="rv-page">
        <div class="rv-container">
            <div class="rv-auth">
                <h1>{{ __('Criar nova senha') }}</h1>

                <form method="POST" action="{{ route('password.update') }}">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">

                    <div class="rv-campo">
                        <label class="rv-label" for="email">{{ __('E-mail') }}</label>
                        <input id="email" name="email" type="email" class="rv-input" value="{{ old('email', $email) }}" required>
                        @error('email')<p class="rv-erro-campo">{{ $message }}</p>@enderror
                    </div>
                    <div class="rv-campo">
                        <label class="rv-label" for="password">{{ __('Nova senha') }}</label>
                        <input id="password" name="password" type="password" class="rv-input" required autocomplete="new-password" autofocus>
                        @error('password')<p class="rv-erro-campo">{{ $message }}</p>@enderror
                    </div>
                    <div class="rv-campo">
                        <label class="rv-label" for="password_confirmation">{{ __('Confirmar nova senha') }}</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" class="rv-input" required autocomplete="new-password">
                    </div>
                    <button type="submit" class="rv-btn rv-btn--primary rv-btn--lg">{{ __('Salvar senha') }}</button>
                </form>
            </div>
        </div>
    </div>
</x-layouts.app>
