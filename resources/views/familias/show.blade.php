@php
    $descricao = \Illuminate\Support\Str::limit(trim(strip_tags((string) $familia->descricao)) ?: "Baixe {$familia->tipo->singular()} Revit {$familia->nome} na biblioteca Os Reviteiros.", 155);
    $url = $familia->urlPublica();
@endphp

<x-layouts.app :title="$familia->nome" :description="$descricao" :canonical="$url">
    <x-slot:head>
        <meta property="og:type" content="product">
        <meta property="og:site_name" content="Os Reviteiros">
        <meta property="og:title" content="{{ $familia->nome }}">
        <meta property="og:description" content="{{ $descricao }}">
        <meta property="og:url" content="{{ $url }}">
        @if ($familia->imagem_url)
            <meta property="og:image" content="{{ $familia->imagem_url }}">
        @endif
        <meta name="twitter:card" content="summary_large_image">
    </x-slot:head>

    <div class="rv-page">
        <div class="rv-container">
            <a href="{{ route($familia->tipo->rotaIndex()) }}" class="rv-voltar"><i class="ti ti-arrow-left"></i> Voltar a {{ mb_strtolower($familia->tipo->getLabel()) }}</a>

            @if (session('upgrade'))
                @php($soPremium = $botao === \App\Services\DownloadService::UPGRADE)
                <div class="rv-alerta rv-alerta--info" x-data x-init="$dispatch('abrir-upgrade', { premium: @js($soPremium) })">
                    {{ $soPremium ? 'Projetos prontos são exclusivos do plano Premium.' : 'Este item é exclusivo para assinantes.' }}
                </div>
            @endif

            <div class="rv-detalhe">
                <div>
                    @if ($familia->imagem_url)
                        <img src="{{ $familia->imagem_url }}" alt="{{ $familia->nome }}" class="rv-detalhe__imagem">
                    @else
                        <div class="rv-card__sem-imagem rv-detalhe__imagem"><i class="ti ti-cube"></i></div>
                    @endif
                </div>

                <div>
                    <div class="flex gap-2 mb-2">
                        @if ($familia->isGratuita())<span class="rv-badge rv-badge--gratis">Grátis</span>@endif
                        @if ($familia->is_novo)<span class="rv-badge">Novo</span>@endif
                        <span class="rv-badge rv-badge--neutro">{{ $familia->tipo->singular() }}</span>
                    </div>

                    <h1 class="rv-detalhe__titulo">{{ $familia->nome }}</h1>

                    @if ($familia->fabricante)
                        <p class="mt-1 text-sm">
                            Fabricante:
                            <a class="rv-link" href="{{ route('fabricantes.show', $familia->fabricante) }}">{{ $familia->fabricante->nome }}</a>
                        </p>
                    @endif

                    @if ($familia->descricao)
                        <div class="rv-detalhe__descricao">{!! nl2br(e(strip_tags($familia->descricao))) !!}</div>
                    @endif

                    {{-- Cada termo leva à biblioteca já filtrada por ele --}}
                    @foreach ($grupos as $termos)
                        <div class="rv-categorias">
                            <span class="rv-categorias__rotulo">{{ $termos->first()->taxonomia->nome_plural }}</span>
                            @foreach ($termos as $termo)
                                <a href="{{ route($familia->tipo->rotaIndex(), [$termo->taxonomia->slug => $termo->slug]) }}" class="rv-categoria-chip">{{ $termo->nome }}</a>
                            @endforeach
                        </div>
                    @endforeach

                    <div class="mt-4" style="max-width: 320px">
                        @include('familias._botao', ['familia' => $familia, 'botao' => $botao])
                    </div>

                    <p class="mt-3 text-xs" style="color: var(--rv-texto-claro)">
                        <i class="ti ti-heart"></i> {{ $familia->curtidas }} curtidas
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
