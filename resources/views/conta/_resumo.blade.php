{{-- Cartões de uso: total e por tipo ($resumo vem do UsoService) --}}
<div class="rv-conta__cartoes">
    <div class="rv-conta__cartao">
        <span class="rv-conta__numero">{{ \App\Support\Idiomas::numero($resumo['total']) }}</span>
        <span class="rv-conta__legenda">{{ __('downloads no total') }}</span>
    </div>
    @foreach (\App\Enums\TipoItem::cases() as $tipoResumo)
        <div class="rv-conta__cartao">
            <span class="rv-conta__numero">{{ \App\Support\Idiomas::numero($resumo['por_tipo'][$tipoResumo->value]['total']) }}</span>
            <span class="rv-conta__legenda">
                {{ $tipoResumo->getLabel() }}
                @if ($resumo['por_tipo'][$tipoResumo->value]['distintos'] !== $resumo['por_tipo'][$tipoResumo->value]['total'])
                    · {{ __(':total diferentes', ['total' => $resumo['por_tipo'][$tipoResumo->value]['distintos']]) }}
                @endif
            </span>
        </div>
    @endforeach
</div>
