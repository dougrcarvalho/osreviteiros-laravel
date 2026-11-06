@props(['seo'])
{{-- rel prev/next da paginação SEO (/page/{n}/) --}}
@if ($seo['prev'])
    <link rel="prev" href="{{ $seo['prev'] }}">
@endif
@if ($seo['next'])
    <link rel="next" href="{{ $seo['next'] }}">
@endif
