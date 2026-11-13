<x-conta.layout :titulo="__('Histórico de uso')">
    @include('conta._resumo')

    <p class="rv-conta__dica">
        {{ __(':total downloads neste mês', ['total' => $resumo['este_mes']]) }}
        · {{ __(':total itens diferentes baixados', ['total' => $resumo['distintos']]) }}
    </p>

    <div class="rv-tipos" role="tablist" style="margin: var(--rv-space-4) 0">
        <a href="{{ route('conta.historico') }}" @class(['rv-tipo', 'rv-tipo--ativo' => ! $tipo])>{{ __('Tudo') }}</a>
        @foreach (\App\Enums\TipoItem::cases() as $opcao)
            <a href="{{ route('conta.historico', ['tipo' => $opcao->value]) }}" @class(['rv-tipo', 'rv-tipo--ativo' => $tipo === $opcao])>{{ $opcao->getLabel() }}</a>
        @endforeach
    </div>

    @if ($downloads->isEmpty())
        <div class="rv-vazio">
            <i class="ti ti-download"></i>
            <p>{{ __('Você ainda não baixou nada.') }}</p>
            <div class="mt-4"><a href="{{ route('familias.index') }}" class="rv-btn rv-btn--primary rv-btn--auto">{{ __('Explorar a biblioteca') }}</a></div>
        </div>
    @else
        <div class="rv-conta__lista">
            @foreach ($downloads as $download)
                @php($item = $download->familia)
                <div class="rv-conta__linha">
                    @if ($item->imagem_url)
                        <img src="{{ $item->imagem_url }}" alt="" class="rv-conta__miniatura" loading="lazy">
                    @else
                        <span class="rv-conta__miniatura rv-conta__miniatura--vazia"><i class="ti ti-cube"></i></span>
                    @endif

                    <div class="rv-conta__linha-info">
                        @if ($item->ativo)
                            <a href="{{ $item->urlPublica() }}" class="rv-conta__linha-nome">{{ $item->nome }}</a>
                        @else
                            <span class="rv-conta__linha-nome">{{ $item->nome }}</span>
                        @endif
                        <span class="rv-conta__dica">{{ $item->fabricante?->nome ?? 'Reviteiros' }}</span>
                    </div>

                    <span class="rv-badge rv-badge--neutro">{{ $item->tipo->singular() }}</span>
                    <time class="rv-conta__data" datetime="{{ $download->created_at->toIso8601String() }}">{{ $download->created_at->isoFormat('L LT') }}</time>
                </div>
            @endforeach
        </div>

        @if ($downloads->hasPages())
            <nav class="rv-paginacao" aria-label="{{ __('Paginação') }}">
                @if ($downloads->previousPageUrl())
                    <a href="{{ $downloads->previousPageUrl() }}" rel="prev" class="rv-link"><i class="ti ti-arrow-left"></i> {{ __('Página anterior') }}</a>
                @endif
                <span class="rv-contagem">{{ __('Página :atual de :total', ['atual' => $downloads->currentPage(), 'total' => $downloads->lastPage()]) }}</span>
                @if ($downloads->nextPageUrl())
                    <a href="{{ $downloads->nextPageUrl() }}" rel="next" class="rv-link">{{ __('Próxima página') }} <i class="ti ti-arrow-right"></i></a>
                @endif
            </nav>
        @endif
    @endif
</x-conta.layout>
