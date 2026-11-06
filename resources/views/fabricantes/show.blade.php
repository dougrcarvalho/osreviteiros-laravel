<x-layouts.app :title="$fabricante->nome.' — Famílias Revit'.($seo['pagina'] > 1 ? ' – Página '.$seo['pagina'] : '')"
               :description="'Todas as famílias Revit BIM do fabricante '.$fabricante->nome.' na biblioteca Os Reviteiros.'"
               :canonical="$seo['canonical']">
    <x-slot:head>
        <x-paginacao-seo :seo="$seo" />
    </x-slot:head>
    <div class="rv-page">
        <div class="rv-container">
            <a href="{{ route('fabricantes.index') }}" class="rv-voltar"><i class="ti ti-arrow-left"></i> Todos os fabricantes</a>

            <div class="rv-marca-hero">
                @if ($fabricante->logo_url)
                    <img src="{{ $fabricante->logo_url }}" alt="{{ $fabricante->nome }}" class="rv-marca-hero__logo">
                @else
                    <div class="rv-fabricante-card__inicial rv-marca-hero__logo" style="height:96px">{{ $fabricante->inicial }}</div>
                @endif
                <div>
                    <h1>{{ $fabricante->nome }}</h1>
                    @if ($fabricante->descricao)
                        <p class="mt-1 text-sm">{{ $fabricante->descricao }}</p>
                    @endif
                    @if ($fabricante->site_url)
                        <a href="{{ $fabricante->site_url }}" class="rv-link text-sm" target="_blank" rel="noopener nofollow">
                            <i class="ti ti-external-link"></i> Site do fabricante
                        </a>
                    @endif
                </div>
            </div>

            {{-- Marca fixa: lista tudo da marca (todas as bibliotecas) e permite filtrar pelas demais classificações --}}
            <livewire:familia-grid :marca="$fabricante->slug" :pagina="$seo['pagina']" :url-base="$seo['base']" />
        </div>
    </div>
</x-layouts.app>
