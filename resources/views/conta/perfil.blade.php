<x-conta.layout :titulo="__('Meus dados')">
    <div class="rv-conta__grade">
        <section class="rv-conta__painel">
            <h2>{{ __('Meus dados') }}</h2>

            <form method="POST" action="{{ route('conta.perfil.atualizar') }}">
                @csrf
                @method('PUT')

                <div class="rv-campo">
                    <label class="rv-label" for="name">{{ __('Nome') }}</label>
                    <input id="name" name="name" type="text" class="rv-input" value="{{ old('name', auth()->user()->name) }}" required maxlength="255" autocomplete="name">
                    @error('name')<p class="rv-erro-campo">{{ $message }}</p>@enderror
                </div>

                <div class="rv-campo">
                    <label class="rv-label" for="email">{{ __('E-mail') }}</label>
                    <input id="email" type="email" class="rv-input" value="{{ auth()->user()->email }}" disabled>
                    <p class="rv-conta__dica">{{ __('Para alterar o e-mail, fale com o suporte.') }}</p>
                </div>

                <button type="submit" class="rv-btn rv-btn--primary rv-btn--auto">{{ __('Salvar alterações') }}</button>
            </form>

            <p class="rv-conta__dica mt-4">
                {{ __('Membro desde :data', ['data' => auth()->user()->created_at->isoFormat('LL')]) }}
                · <a href="{{ route('password.request') }}" class="rv-link">{{ __('Alterar minha senha') }}</a>
            </p>
        </section>

        <section class="rv-conta__painel">
            <h2>{{ __('Seu plano') }}</h2>
            <p class="rv-conta__plano"><i class="ti ti-crown"></i> {{ __(auth()->user()->plano?->nome ?? 'Grátis') }}</p>
            <a href="{{ route('conta.plano') }}" class="rv-btn rv-btn--outline rv-btn--auto">{{ __('Gerenciar plano') }}</a>

            <h2 class="mt-6">{{ __('Resumo de uso') }}</h2>
            <p class="rv-conta__dica">
                {{ __(':total itens favoritos', ['total' => $favoritos]) }}
                @if ($resumo['ultimo'])
                    · {{ __('último download em :data', ['data' => $resumo['ultimo']->isoFormat('L')]) }}
                @endif
            </p>
            <a href="{{ route('conta.historico') }}" class="rv-link">{{ __('Ver histórico completo') }} <i class="ti ti-arrow-right"></i></a>
        </section>
    </div>

    @include('conta._resumo')
</x-conta.layout>
