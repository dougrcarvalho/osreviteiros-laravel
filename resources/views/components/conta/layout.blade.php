@props(['titulo'])
<x-layouts.app :title="$titulo">
    <div class="rv-page">
        <div class="rv-container">
            <h1 class="rv-page__titulo">{{ __('Minha conta') }}</h1>

            <nav class="rv-conta__menu" aria-label="{{ __('Minha conta') }}">
                @foreach ([
                    ['conta.perfil', 'ti-user', __('Meus dados')],
                    ['conta.plano', 'ti-crown', __('Plano e assinatura')],
                    ['conta.historico', 'ti-history', __('Histórico de uso')],
                    ['favoritos.index', 'ti-heart', __('Favoritos')],
                ] as [$rota, $icone, $rotulo])
                    <a href="{{ route($rota) }}" @class(['rv-conta__aba', 'rv-conta__aba--ativa' => request()->routeIs($rota)])>
                        <i class="ti {{ $icone }}"></i> {{ $rotulo }}
                    </a>
                @endforeach
            </nav>

            @if (session('success'))
                <div class="rv-alerta rv-alerta--sucesso">{{ session('success') }}</div>
            @endif
            @if (session('erro'))
                <div class="rv-alerta rv-alerta--erro">{{ session('erro') }}</div>
            @endif

            {{ $slot }}
        </div>
    </div>
</x-layouts.app>
