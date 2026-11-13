@props(['title' => null, 'description' => null, 'canonical' => null])
<!DOCTYPE html>
<html lang="{{ \App\Support\Idiomas::htmlLang() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ? $title.' | Os Reviteiros' : __('Os Reviteiros — Famílias Revit BIM') }}</title>
    <meta name="description" content="{{ $description ?: __('Biblioteca de famílias Revit BIM, texturas e projetos para arquitetos e engenheiros. Baixe e aplique direto no seu projeto.') }}">
    @if ($canonical)<link rel="canonical" href="{{ $canonical }}">@endif
    <x-hreflang />
    {{ $head ?? '' }}
    @stack('head')

    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Schibsted+Grotesk:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen flex flex-col">
    <header class="rv-header">
        <div class="rv-container rv-header__inner">
            <a href="{{ route('familias.index') }}" aria-label="{{ __('Os Reviteiros — início') }}">
                <img src="{{ asset('images/logo.png') }}" alt="Os Reviteiros" class="rv-logo">
            </a>

            <nav class="rv-nav">
                @foreach (\App\Enums\TipoItem::cases() as $tipoNav)
                    <a href="{{ route($tipoNav->rotaIndex()) }}"
                       class="rv-nav__link {{ request()->routeIs($tipoNav->chaveRota().'.*') ? 'rv-nav__link--ativo' : '' }}">{{ $tipoNav->getLabel() }}</a>
                @endforeach
                <a href="{{ route('fabricantes.index') }}"
                   class="rv-nav__link {{ request()->routeIs('fabricantes.*') ? 'rv-nav__link--ativo' : '' }}">{{ __('Fabricantes') }}</a>

                <x-seletor-idioma />

                @auth
                    <a href="{{ route('favoritos.index') }}" class="rv-nav__link {{ request()->routeIs('favoritos.*') ? 'rv-nav__link--ativo' : '' }}"><i class="ti ti-heart"></i> {{ __('Favoritos') }}</a>
                    <div class="rv-user-menu" x-data="{ aberto: false }" @click.outside="aberto = false" @keydown.escape="aberto = false">
                        <button type="button" class="rv-user-menu__btn" @click="aberto = !aberto" :aria-expanded="aberto">
                            <i class="ti ti-user-circle"></i>
                            <span>{{ \Illuminate\Support\Str::of(auth()->user()->name)->before(' ')->limit(14) }}</span>
                            <i class="ti ti-chevron-down"></i>
                        </button>
                        <div class="rv-user-menu__drop" x-show="aberto" x-transition x-cloak>
                            <div class="rv-user-menu__item" style="cursor:default">
                                <i class="ti ti-crown"></i> {{ __('Plano :nome', ['nome' => __(auth()->user()->plano?->nome ?? 'Grátis')]) }}
                            </div>
                            <a href="{{ route('conta.perfil') }}" class="rv-user-menu__item"><i class="ti ti-user"></i> {{ __('Minha conta') }}</a>
                            <a href="{{ route('favoritos.index') }}" class="rv-user-menu__item"><i class="ti ti-heart"></i> {{ __('Meus favoritos') }}</a>
                            @unless (auth()->user()->hasPlano())
                                <button type="button" class="rv-user-menu__item" @click="aberto = false; $dispatch('abrir-upgrade')">
                                    <i class="ti ti-star"></i> {{ __('Fazer upgrade') }}
                                </button>
                            @endunless
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="rv-user-menu__item"><i class="ti ti-logout"></i> {{ __('Sair') }}</button>
                            </form>
                        </div>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="rv-nav__entrar">{{ __('Entrar') }}</a>
                @endauth
            </nav>
        </div>
    </header>

    <main class="flex-1">
        {{ $slot }}
    </main>

    <footer class="rv-footer">
        <div class="rv-container">© {{ date('Y') }} Os Reviteiros — {{ __('Todos os direitos reservados') }}</div>
    </footer>

    <x-popup-planos />

    @livewireScriptConfig
</body>
</html>
