<x-layouts.app :title="__('Meus favoritos')" :description="__('Os itens que você favoritou na biblioteca Os Reviteiros.')">
    <div class="rv-page">
        <div class="rv-container">
            <h1 class="rv-page__titulo">{{ __('Meus favoritos') }}</h1>

            @if ($familias->total() > 0)
                <p class="rv-contagem">{{ \App\Support\Idiomas::numero($familias->total()) }} {{ $familias->total() === 1 ? __('item') : __('itens') }}</p>

                <div class="rv-familias-grid" x-data="rvTooltip"
                     @mouseover="mostrar($event)" @mousemove="mover($event)" @mouseout="esconder($event)">
                    @foreach ($familias as $familia)
                        @include('familias._card', ['familia' => $familia, 'botao' => $botoes[$familia->id], 'favorito' => true])
                    @endforeach

                    <img x-show="aberto" x-cloak :src="src" :style="`left:${x}px;top:${y}px`" class="rv-img-tooltip" alt="" aria-hidden="true">
                </div>

                @if ($familias->hasPages())
                    <nav class="rv-paginacao" aria-label="{{ __('Paginação') }}">
                        @if ($familias->previousPageUrl())
                            <a href="{{ $familias->previousPageUrl() }}" rel="prev" class="rv-link"><i class="ti ti-arrow-left"></i> {{ __('Página anterior') }}</a>
                        @endif
                        <span class="rv-contagem">{{ __('Página :atual de :total', ['atual' => $familias->currentPage(), 'total' => $familias->lastPage()]) }}</span>
                        @if ($familias->nextPageUrl())
                            <a href="{{ $familias->nextPageUrl() }}" rel="next" class="rv-link">{{ __('Próxima página') }} <i class="ti ti-arrow-right"></i></a>
                        @endif
                    </nav>
                @endif
            @else
                <div class="rv-vazio">
                    <i class="ti ti-heart"></i>
                    <p>{{ __('Você ainda não favoritou nada.') }}</p>
                    <p class="mt-1 text-sm">{{ __('Toque no coração de um item para guardá-lo aqui.') }}</p>
                    <div class="mt-4">
                        <a href="{{ route('familias.index') }}" class="rv-btn rv-btn--primary rv-btn--auto">{{ __('Explorar a biblioteca') }}</a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-layouts.app>
