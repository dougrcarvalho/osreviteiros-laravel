{{-- Versões do mesmo conteúdo nos outros idiomas (SEO) --}}
@foreach (\App\Support\Idiomas::todos() as $codigo => $dados)
    <link rel="alternate" hreflang="{{ $dados['html'] }}" href="{{ \App\Support\Idiomas::urlEm($codigo) }}">
@endforeach
<link rel="alternate" hreflang="x-default" href="{{ \App\Support\Idiomas::urlEm(\App\Support\Idiomas::padrao()) }}">
