<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $seo['title'] }}</title>
    <meta name="description" content="{{ $seo['description'] }}">
    <link rel="canonical" href="{{ $seo['canonical'] }}">

    <meta property="og:type" content="website">
    <meta property="og:locale" content="pt_BR">
    <meta property="og:site_name" content="Os Reviteiros">
    <meta property="og:title" content="{{ $seo['title'] }}">
    <meta property="og:description" content="{{ $seo['description'] }}">
    <meta property="og:url" content="{{ $seo['canonical'] }}">
    <meta property="og:image" content="{{ $seo['image'] }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seo['title'] }}">
    <meta name="twitter:description" content="{{ $seo['description'] }}">
    <meta name="twitter:image" content="{{ $seo['image'] }}">

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="32x32">
    <link rel="icon" href="{{ asset('images/logo/favicon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Schibsted+Grotesk:wght@400;500;600;700;800&display=swap">

    @vite(['resources/css/app.css', 'resources/js/site.js'])

    @isset($jsonLd)
        <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
    @endisset
</head>
<body>
@php
    $pluginUrl = config('reviteiros.home.plugin_url');
    $totalFamilias = number_format(config('reviteiros.home.total_familias'), 0, ',', '.');
    $links = [
        ['Famílias', route('familias.index')],
        ['Texturas', route('texturas.index')],
        ['Projetos prontos', route('projetos.index')],
        ['Templates', '#'],
        ['Cursos', '#'],
        ['Fabricantes', route('fabricantes.index')],
        ['Planos', route('home').'#planos'],
    ];
@endphp
<div class="font-site leading-[normal] text-rv-ink bg-white min-h-screen">
    <a href="#conteudo" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-white focus:px-4 focus:py-2 focus:shadow-lg">Pular para o conteúdo</a>

    <header class="sticky top-0 z-30 border-b border-rv-line bg-white/95 backdrop-blur-sm xl:static">
        <div class="mx-auto max-w-rv px-4 md:px-6 h-[60px] xl:h-auto xl:py-[18px] flex items-center gap-1 xl:gap-x-6 xl:gap-y-4 xl:flex-nowrap">
            <a href="{{ route('home') }}" class="flex flex-auto items-center min-w-0 lg:flex-none">
                <img src="{{ asset('images/logo/logo-horizontal.svg') }}" alt="Os Reviteiros" width="200" height="28" class="h-7 w-auto">
            </a>

            {{-- Abaixo de 1280px: conta + menu --}}
            <div class="flex items-center xl:hidden">
                @auth
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" aria-label="Sair da conta" class="flex size-11 items-center justify-center text-rv-ink cursor-pointer">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"></circle><path d="M4 21c1.5-4 4.5-6 8-6s6.5 2 8 6"></path></svg>
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" aria-label="Entrar na conta" class="flex size-11 items-center justify-center text-rv-ink">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"></circle><path d="M4 21c1.5-4 4.5-6 8-6s6.5 2 8 6"></path></svg>
                    </a>
                @endauth
                <button type="button" id="rv-menu-abrir" aria-label="Abrir menu" aria-controls="rv-menu" aria-expanded="false" class="flex size-11 items-center justify-center text-rv-ink cursor-pointer">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16"></path><path d="M4 12h16"></path><path d="M4 17h16"></path></svg>
                </button>
            </div>

            {{-- 1280px ou mais: menu completo --}}
            <nav aria-label="Principal" class="hidden xl:flex flex-wrap xl:flex-nowrap gap-x-[22px] gap-y-1.5 text-[15px] font-medium flex-auto xl:min-w-0">
                @foreach ($links as [$nome, $url])
                    <a href="{{ $url }}" class="rv-alvo whitespace-nowrap text-rv-body hover:text-rv-accent">{{ $nome }}</a>
                @endforeach
            </nav>

            <div class="hidden xl:flex gap-2.5 items-center">
                @auth
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="px-4 py-[11px] text-[15px] font-semibold text-rv-ink hover:text-rv-accent cursor-pointer">Sair</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="px-4 py-[11px] text-[15px] font-semibold text-rv-ink hover:text-rv-accent">Entrar</a>
                @endauth
                <a href="{{ $pluginUrl }}" class="px-[18px] py-[11px] rounded-rv-btn bg-rv-accent hover:bg-rv-accent-hover text-white text-[15px] font-semibold">Baixar plugin grátis</a>
            </div>
        </div>
    </header>

    {{-- Painel do menu (celular e tablet). Esc fecha e o foco fica dentro do painel. --}}
    <dialog id="rv-menu" aria-label="Menu" class="fixed inset-y-0 right-0 m-0 h-dvh max-h-none w-[min(320px,88vw)] max-w-none bg-white p-0 text-rv-ink backdrop:bg-rv-ink/40">
        <div class="flex h-full flex-col">
            <div class="flex h-[60px] flex-none items-center justify-between border-b border-rv-line pl-4 pr-2">
                <span class="font-bold">Menu</span>
                <form method="dialog">
                    <button type="submit" aria-label="Fechar menu" class="flex size-11 items-center justify-center text-rv-ink cursor-pointer">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12"></path><path d="M18 6L6 18"></path></svg>
                    </button>
                </form>
            </div>

            <nav aria-label="Menu principal" class="flex flex-col p-2">
                @foreach ($links as [$nome, $url])
                    <a href="{{ $url }}" class="flex min-h-12 items-center rounded-rv-btn px-3 text-base font-medium text-rv-body hover:bg-rv-alt">{{ $nome }}</a>
                @endforeach
            </nav>

            <div class="mt-auto flex flex-col gap-2.5 border-t border-rv-line p-4">
                @guest
                    <a href="{{ route('login') }}" class="flex min-h-12 items-center justify-center rounded-rv-btn border border-rv-ink font-semibold text-rv-ink">Entrar</a>
                @endguest
                <a href="{{ $pluginUrl }}" class="flex min-h-12 items-center justify-center rounded-rv-btn bg-rv-accent font-bold text-white">Baixar plugin grátis</a>
            </div>
        </div>
    </dialog>

    <main id="conteudo">
        @yield('content')
    </main>

    <footer class="bg-rv-footer text-rv-footer-text">
        <div class="mx-auto flex max-w-rv flex-col gap-6 px-4 py-8 md:px-6 lg:grid lg:grid-cols-4 lg:gap-8 lg:py-14 text-[14px] lg:text-[15px]">
            <div class="flex flex-col gap-2.5 lg:row-start-1 lg:col-start-1">
                <img src="{{ asset('images/logo/logo-horizontal-negativo.svg') }}" alt="Os Reviteiros" width="200" height="28" class="h-7 w-auto self-start lg:h-8">
            </div>

            <nav aria-label="Rodapé" class="grid grid-cols-2 gap-x-4 lg:hidden">
                <a href="{{ route('familias.index') }}" class="rv-alvo py-2.5 text-rv-footer-text hover:text-white">Famílias Revit</a>
                <a href="{{ route('texturas.index') }}" class="rv-alvo py-2.5 text-rv-footer-text hover:text-white">Texturas</a>
                <a href="{{ route('projetos.index') }}" class="rv-alvo py-2.5 text-rv-footer-text hover:text-white">Projetos prontos</a>
                <a href="#" class="rv-alvo py-2.5 text-rv-footer-text hover:text-white">Cursos</a>
                <a href="#" class="rv-alvo py-2.5 text-rv-footer-text hover:text-white">Para empresas</a>
                <a href="#" class="rv-alvo py-2.5 text-rv-footer-text hover:text-white">Termos e privacidade</a>
            </nav>

            <div class="hidden lg:col-span-3 lg:col-start-2 lg:row-start-1 lg:grid lg:grid-cols-3 lg:gap-8">
                <nav aria-label="Biblioteca" class="flex flex-col gap-2 items-start">
                    <strong class="text-white">Biblioteca</strong>
                    <a href="{{ route('familias.index') }}" class="rv-alvo text-rv-footer-text hover:text-white">Famílias Revit</a>
                    <a href="{{ route('texturas.index') }}" class="rv-alvo text-rv-footer-text hover:text-white">Texturas</a>
                    <a href="{{ route('projetos.index') }}" class="rv-alvo text-rv-footer-text hover:text-white">Projetos prontos</a>
                    <a href="#" class="rv-alvo text-rv-footer-text hover:text-white">Templates</a>
                </nav>
                <nav aria-label="Aprender" class="flex flex-col gap-2 items-start">
                    <strong class="text-white">Aprender</strong>
                    <a href="#" class="rv-alvo text-rv-footer-text hover:text-white">Cursos</a>
                    <a href="#" class="rv-alvo text-rv-footer-text hover:text-white">Blog</a>
                    <a href="#" class="rv-alvo text-rv-footer-text hover:text-white">Tutoriais do plugin</a>
                </nav>
                <nav aria-label="Empresa" class="flex flex-col gap-2 items-start">
                    <strong class="text-white">Empresa</strong>
                    <a href="#" class="rv-alvo text-rv-footer-text hover:text-white">Para empresas</a>
                    <a href="{{ config('reviteiros.home.whatsapp_url') }}" class="rv-alvo text-rv-footer-text hover:text-white">Suporte via WhatsApp</a>
                    <a href="#" class="rv-alvo text-rv-footer-text hover:text-white">Termos e privacidade</a>
                </nav>
            </div>

            <span class="text-[13px] lg:row-start-2 lg:col-start-1 lg:text-[15px]">© {{ date('Y') }} Os Reviteiros · CNPJ {{ config('reviteiros.home.cnpj') }}</span>
        </div>
    </footer>

    {{-- Barra fixa do celular: aparece depois do hero (ver rv-barra-acao.js) --}}
    <div id="rv-barra-acao" hidden class="fixed inset-x-0 bottom-0 z-40 flex items-center gap-2.5 border-t border-rv-line bg-white/95 px-4 pt-2.5 pb-3.5 backdrop-blur-sm md:hidden">
        <div class="flex min-w-0 flex-auto flex-col">
            <strong class="text-sm text-rv-ink">Comece grátis</strong>
            <span class="text-xs text-rv-ink-3">{{ $totalFamilias }} famílias no Revit</span>
        </div>
        <a href="{{ $pluginUrl }}" class="flex min-h-12 flex-none items-center rounded-rv-btn bg-rv-accent px-[18px] text-[15px] font-bold text-white">Baixar plugin</a>
    </div>
</div>
</body>
</html>
