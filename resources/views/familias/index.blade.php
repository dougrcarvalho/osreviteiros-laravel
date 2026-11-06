@php
    $sufixo = $seo['pagina'] > 1 ? ' – Página '.$seo['pagina'] : '';
@endphp

<x-layouts.app :title="'Biblioteca de '.$tipo->getLabel().' Revit BIM'.$sufixo" :canonical="$seo['canonical']">
    <x-slot:head>
        <x-paginacao-seo :seo="$seo" />
    </x-slot:head>

    <div class="rv-page">
        <div class="rv-container">
            @if (session('success'))
                <div class="rv-alerta rv-alerta--sucesso">{{ session('success') }}</div>
            @endif

            <h1 class="rv-page__titulo">Os Reviteiros | {{ $tipo->getLabel() }} Revit BIM</h1>

            <livewire:familia-grid
                :biblioteca="$tipo->value"
                :pagina="$seo['pagina']"
                :url-base="$seo['base']"
                :key="'grid-'.$tipo->value" />
        </div>
    </div>
</x-layouts.app>
