@php
    $sufixo = $seo['pagina'] > 1 ? ' – '.__('Página :n', ['n' => $seo['pagina']]) : '';
@endphp

<x-layouts.app :title="__('Biblioteca de :tipo Revit BIM', ['tipo' => $tipo->getLabel()]).$sufixo" :canonical="$seo['canonical']">
    <x-slot:head>
        <x-paginacao-seo :seo="$seo" />
    </x-slot:head>

    <div class="rv-page">
        <div class="rv-container">
            @if (session('success'))
                <div class="rv-alerta rv-alerta--sucesso">{{ session('success') }}</div>
            @endif

            <h1 class="rv-page__titulo">Os Reviteiros | {{ __(':tipo Revit BIM', ['tipo' => $tipo->getLabel()]) }}</h1>

            <livewire:familia-grid
                :biblioteca="$tipo->value"
                :pagina="$seo['pagina']"
                :url-base="$seo['base']"
                :key="'grid-'.$tipo->value" />
        </div>
    </div>

    <button type="button" class="rv-topo" aria-label="{{ __('Voltar ao topo') }}"
        x-data="{ visivel: false }"
        x-on:scroll.window.passive="visivel = window.scrollY > window.innerHeight"
        x-show="visivel" x-cloak x-transition.opacity
        x-on:click="window.scrollTo({ top: 0, behavior: 'smooth' })">
        <i class="ti ti-arrow-up"></i>
    </button>
</x-layouts.app>
