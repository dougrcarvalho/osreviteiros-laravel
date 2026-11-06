@extends('layouts.site')

@section('content')
    <x-home.hero :total="$totalFmt" :buscas="$buscas" :plugin-url="$pluginUrl" />
    <x-home.plugin :video="$video" :plugin-url="$pluginUrl" />
    <x-home.fabricantes :fabricantes="$fabricantes" />
    <x-home.categorias :categorias="$categorias" />
    <x-home.populares :populares="$populares" :total="$totalFmt" />
    <x-home.solicitacao />
    <x-home.brasil />
    @if ($depoimentos)
        <x-home.depoimentos :depoimentos="$depoimentos" />
    @endif
    <x-home.planos :planos="$planos" />
    <x-home.faq :faq="$faq" />
    <x-home.cta-final :plugin-url="$pluginUrl" />
@endsection
