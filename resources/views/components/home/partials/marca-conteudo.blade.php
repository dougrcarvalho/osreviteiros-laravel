@if ($fabricante['logo'])
<img src="{{ $fabricante['logo'] }}" alt="{{ $fabricante['nome'] }}" loading="lazy" class="h-7 lg:h-9 w-auto max-w-none object-contain">
@else
{{ $fabricante['nome'] }}
@endif
