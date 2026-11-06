<article class="rv-card" wire:key="familia-{{ $familia->id }}"
         @if (! empty($marcador)) data-pagina-inicio="{{ $marcador['pagina'] }}" data-url="{{ $marcador['url'] }}" @endif>
    <a href="{{ $familia->urlPublica() }}" class="rv-card__img-wrap">
        @if ($familia->imagem_url)
            <img src="{{ $familia->imagem_url }}"
                 alt="{{ $familia->nome }}"
                 class="rv-card__imagem"
                 loading="lazy"
                 data-preview="{{ $familia->imagem_url }}">
        @else
            <div class="rv-card__sem-imagem"><i class="ti ti-cube"></i></div>
        @endif

        <span class="rv-card__badges">
            @if ($familia->isGratuita())
                <span class="rv-badge rv-badge--gratis">Grátis</span>
            @endif
            @if ($familia->is_novo)
                <span class="rv-badge">Novo</span>
            @endif
            @if ($familia->tipo !== \App\Enums\TipoItem::Familia)
                <span class="rv-badge rv-badge--neutro">{{ $familia->tipo->singular() }}</span>
            @endif
        </span>
    </a>

    <div class="rv-card__body">
        <a href="{{ $familia->urlPublica() }}" class="rv-card__nome" title="{{ $familia->nome }}">{{ $familia->nome }}</a>
        <div class="rv-card__meta">
            <span>{{ $familia->fabricante?->nome ?? 'Reviteiros' }}</span>
            <span>{{ $familia->created_at?->format('Y') }}</span>
        </div>
    </div>

    <div class="rv-card__footer">
        @include('familias._botao', ['familia' => $familia, 'botao' => $botao])
    </div>
</article>
