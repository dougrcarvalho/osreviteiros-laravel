<div class="rv-familia-grid"
     x-data="rvTooltip"
     @mouseover="mostrar($event)"
     @mousemove="mover($event)"
     @mouseout="esconder($event)">

    {{-- Busca: nome, descrição, tipo, fabricante e qualquer termo de classificação --}}
    <form class="rv-busca" role="search" wire:submit="$refresh">
        <input type="search"
               class="rv-busca__input"
               placeholder="buscar por 'cadeira'..."
               aria-label="Buscar famílias, texturas e projetos"
               wire:model.live.debounce.400ms="busca">
        <button type="submit" class="rv-busca__btn" aria-label="Buscar"><i class="ti ti-search"></i></button>
    </form>

    <div class="rv-toolbar">
        {{-- Abas de biblioteca: só na tela do fabricante (nas demais a biblioteca vem da URL) --}}
        @unless ($bibliotecaFixa)
            <div class="rv-tipos" role="tablist">
                <button type="button" class="rv-tipo {{ $biblioteca === '' ? 'rv-tipo--ativo' : '' }}" wire:click="filtrarBiblioteca('')">Tudo</button>
                @foreach ($tipos as $tipo)
                    <button type="button" class="rv-tipo {{ $biblioteca === $tipo->value ? 'rv-tipo--ativo' : '' }}" wire:click="filtrarBiblioteca('{{ $tipo->value }}')">{{ $tipo->getLabel() }}</button>
                @endforeach
            </div>
        @else
            <div></div>
        @endunless

        <div class="flex items-center gap-3">
            <span class="rv-contagem">{{ number_format($total, 0, ',', '.') }} {{ $total === 1 ? 'resultado' : 'resultados' }}</span>
            <select class="rv-select" wire:model.live="orderby" aria-label="Ordenar">
                <option value="date">Mais recentes</option>
                <option value="az">A–Z</option>
                <option value="curtidas">Mais curtidas</option>
            </select>
        </div>
    </div>

    {{-- Trilha de filtros ativos (acumulativos) --}}
    <div class="rv-categorias rv-categorias--ativas">
        <button type="button"
                class="rv-categoria-chip rv-categoria-chip--gratis {{ $gratis ? 'rv-categoria-chip--ativo-gratis' : '' }}"
                wire:click="alternarGratis"
                aria-pressed="{{ $gratis ? 'true' : 'false' }}">
            ACESSAR GRÁTIS @if ($gratis)<i class="ti ti-x"></i>@endif
        </button>

        @foreach ($ativas as $ativa)
            @if ($ativa['fixo'])
                <span class="rv-categoria-chip rv-categoria-chip--fixo"><i class="ti ti-building-store"></i> {{ $ativa['nome'] }}</span>
            @else
                <button type="button"
                        class="rv-categoria-chip rv-categoria-chip--ativo"
                        wire:click="remover('{{ $ativa['taxonomia']->slug }}')"
                        title="Remover filtro ({{ $ativa['taxonomia']->nome }})">
                    {{ $ativa['nome'] }} <i class="ti ti-x"></i>
                </button>
            @endif
        @endforeach

        @if ($temFiltro)
            <button type="button" class="rv-limpar" wire:click="limpar">limpar filtros</button>
        @endif
    </div>

    {{-- Uma linha por taxonomia disponível (na ordem configurada) --}}
    @foreach ($linhas as $linha)
        @php($tax = $linha['taxonomia'])
        <div class="rv-categorias rv-categorias-{{ $tax->slug }} {{ $tax->pai_id ? 'rv-subcategorias' : '' }}" wire:key="linha-{{ $tax->id }}">
            <span class="rv-categorias__rotulo">{{ $tax->nome_plural }}</span>

            @if ($linha['opcoes']->count() > 30)
                {{-- Listas longas (ex.: marcas): seletor com todas as opções --}}
                <select class="rv-select" aria-label="{{ $tax->nome }}"
                        wire:change="selecionar('{{ $tax->slug }}', $event.target.value)">
                    <option value="">Selecionar {{ mb_strtolower($tax->nome) }}…</option>
                    @foreach ($linha['opcoes'] as $opcao)
                        <option value="{{ $opcao['slug'] }}">{{ $opcao['nome'] }}</option>
                    @endforeach
                </select>
            @else
                @foreach ($linha['opcoes'] as $opcao)
                    <button type="button"
                            wire:key="opcao-{{ $tax->id }}-{{ $opcao['id'] }}"
                            class="rv-categoria-chip"
                            wire:click="selecionar('{{ $tax->slug }}', @js($opcao['slug']))">
                        {{ $opcao['nome'] }}
                    </button>
                @endforeach
            @endif
        </div>
    @endforeach

    {{-- Aberto no meio (/page/3/): link real para a página anterior; com JS carrega acima sem sair do lugar --}}
    @if ($urlAnterior)
        <div class="rv-paginacao rv-paginacao--topo" x-data="rvAnteriores">
            <a href="{{ $urlAnterior }}" rel="prev" class="rv-btn rv-btn--outline rv-btn--auto" @click.prevent="carregar" :aria-busy="carregando">
                <i class="ti ti-arrow-up"></i> Carregar resultados anteriores
            </a>
        </div>
    @endif

    <div id="rv-grid" class="rv-familias-grid" x-data="rvPaginacao"
         wire:loading.class="opacity-60" wire:target="busca,orderby,selecionar,remover,limpar,filtrarBiblioteca,alternarGratis">
        @foreach ($familias as $familia)
            @include('familias._card', ['familia' => $familia, 'botao' => $botoes[$familia->id], 'marcador' => $marcadores[$familia->id] ?? null])
        @endforeach
    </div>

    @if ($familias->isEmpty())
        <div class="rv-vazio">
            <i class="ti ti-mood-empty"></i>
            Nenhum resultado para os filtros selecionados.
            @if ($temFiltro)
                <div class="mt-3"><button type="button" class="rv-link" wire:click="limpar">Limpar filtros</button></div>
            @endif
        </div>
    @endif

    {{-- Sentinel do infinite scroll (IntersectionObserver em resources/js/rv-infinito.js) --}}
    <div id="rv-sentinel" class="rv-sentinel" wire:key="sentinel" x-data="rvInfinito" data-tem-mais="{{ $temMais ? 'true' : 'false' }}"></div>

    <div class="rv-loader" wire:loading.flex wire:target="carregarMais">
        <i class="ti ti-loader-2 animate-spin"></i> Carregando...
    </div>

    {{-- Paginação rastreável (buscadores e sem JS); com JS o infinite scroll carrega sozinho --}}
    @if ($ultimaPagina > 1)
        <nav class="rv-paginacao" aria-label="Paginação">
            <span class="rv-contagem">Página {{ $paginaFinal }} de {{ $ultimaPagina }}</span>
            @if ($urlProxima)
                <a href="{{ $urlProxima }}" rel="next" class="rv-link" wire:click.prevent="carregarMais">Próxima página <i class="ti ti-arrow-right"></i></a>
            @endif
        </nav>
    @endif

    {{-- Preview 500×500 --}}
    <img wire:ignore x-show="aberto" x-cloak :src="src" :style="`left:${x}px;top:${y}px`" class="rv-img-tooltip" alt="" aria-hidden="true">
</div>
