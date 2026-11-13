<div class="rv-familia-grid"
     x-data="rvTooltip"
     @mouseover="mostrar($event)"
     @mousemove="mover($event)"
     @mouseout="esconder($event)">

    {{-- Busca: nome, descrição, tipo, fabricante e qualquer termo de classificação --}}
    <form class="rv-busca" role="search" wire:submit="$refresh">
        <input type="search"
               class="rv-busca__input"
               placeholder="{{ __('buscar por \'cadeira\'...') }}"
               aria-label="{{ __('Buscar famílias, texturas e projetos') }}"
               wire:model.live.debounce.400ms="busca">
        <button type="submit" class="rv-busca__btn" aria-label="{{ __('Buscar') }}"><i class="ti ti-search"></i></button>
    </form>

    <div class="rv-toolbar">
        {{-- Abas de biblioteca: só na tela do fabricante (nas demais a biblioteca vem da URL) --}}
        @unless ($bibliotecaFixa)
            <div class="rv-tipos" role="tablist">
                <button type="button" class="rv-tipo {{ $biblioteca === '' ? 'rv-tipo--ativo' : '' }}" wire:click="filtrarBiblioteca('')">{{ __('Tudo') }}</button>
                @foreach ($tipos as $tipo)
                    <button type="button" class="rv-tipo {{ $biblioteca === $tipo->value ? 'rv-tipo--ativo' : '' }}" wire:click="filtrarBiblioteca('{{ $tipo->value }}')">{{ $tipo->getLabel() }}</button>
                @endforeach
            </div>
        @else
            <div></div>
        @endunless

        <div class="flex items-center gap-3">
            <span class="rv-contagem">{{ \App\Support\Idiomas::numero($total) }} {{ $total === 1 ? __('resultado') : __('resultados') }}</span>
            <select class="rv-select" wire:model.live="orderby" aria-label="{{ __('Ordenar') }}">
                <option value="date">{{ __('Mais recentes') }}</option>
                <option value="az">{{ __('A–Z') }}</option>
                <option value="curtidas">{{ __('Mais curtidas') }}</option>
            </select>
        </div>
    </div>

    {{-- Filtrar por: Recurso, Fabricantes e Avançado --}}
    <div class="rv-filtrar" x-data="{ aberto: null }" @click.outside="aberto = null" @keydown.escape.window="aberto = null">
        <span class="rv-filtrar__rotulo">{{ __('Filtrar por') }}</span>

        <div class="rv-filtrar__barra">
            {{-- Recurso --}}
            <div class="rv-filtro">
                <button type="button" class="rv-filtro__btn {{ $recursos ? 'rv-filtro__btn--ativo' : '' }}"
                        :class="{ 'rv-filtro__btn--aberto': aberto === 'recurso' }"
                        @click="aberto = aberto === 'recurso' ? null : 'recurso'" :aria-expanded="aberto === 'recurso'">
                    <i class="ti ti-crown"></i> {{ __('Recurso') }} <i class="ti ti-chevron-down rv-filtro__seta"></i>
                </button>
                <div class="rv-filtro__painel" x-show="aberto === 'recurso'" x-cloak x-transition.opacity>
                    <span class="rv-filtro__titulo">{{ __('Recurso') }}</span>
                    <label class="rv-check">
                        <input type="checkbox" wire:click="alternarRecurso('gratis')" @checked(in_array('gratis', $recursos, true))> {{ __('Grátis') }}
                    </label>
                    <label class="rv-check">
                        <input type="checkbox" wire:click="alternarRecurso('premium')" @checked(in_array('premium', $recursos, true))> {{ __('Premium') }}
                    </label>
                    <p class="rv-filtro__dica">
                        @if ($recursos === ['gratis'])
                            {{ __('Mostrando só itens grátis') }}
                        @elseif ($recursos === ['premium'])
                            {{ __('Mostrando só itens Premium') }}
                        @else
                            {{ __('Mostrando todos os itens') }}
                        @endif
                    </p>
                </div>
            </div>

            {{-- Fabricantes (não aparece na tela da marca, onde ela já é fixa) --}}
            @if ($fabricantes)
                @php($marcasSel = \App\Services\FamiliaBuscaService::lista($this->filtros[$fabricantes['taxonomia']->slug] ?? ''))
                <div class="rv-filtro" x-data="{ q: '' }">
                    <button type="button" class="rv-filtro__btn {{ $marcasSel ? 'rv-filtro__btn--ativo' : '' }}"
                            :class="{ 'rv-filtro__btn--aberto': aberto === 'fabricantes' }"
                            @click="aberto = aberto === 'fabricantes' ? null : 'fabricantes'" :aria-expanded="aberto === 'fabricantes'">
                        <i class="ti ti-building-factory-2"></i> {{ __('Fabricantes') }} @if ($marcasSel)<span class="rv-filtro__qtd">{{ count($marcasSel) }}</span>@endif <i class="ti ti-chevron-down rv-filtro__seta"></i>
                    </button>
                    <div class="rv-filtro__painel rv-filtro__painel--largo" x-show="aberto === 'fabricantes'" x-cloak x-transition.opacity>
                        <span class="rv-filtro__titulo">{{ __('Fabricantes') }}</span>
                        <input type="search" class="rv-filtro__input" x-model="q" placeholder="{{ __('Exemplo: Portobello') }}" aria-label="{{ __('Buscar fabricante') }}">
                        <div class="rv-filtro__chips">
                            @foreach ($fabricantes['opcoes'] as $opcao)
                                <button type="button"
                                        wire:key="fab-{{ $opcao['id'] }}"
                                        class="rv-categoria-chip {{ in_array($opcao['slug'], $marcasSel, true) ? 'rv-categoria-chip--ativo' : '' }}"
                                        data-nome="{{ \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii($opcao['nome'])) }}"
                                        x-show="!q || $el.dataset.nome.includes(q.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase())"
                                        aria-pressed="{{ in_array($opcao['slug'], $marcasSel, true) ? 'true' : 'false' }}"
                                        wire:click="alternarMarca({{ \Illuminate\Support\Js::from($opcao['slug']) }})">
                                    {{ $opcao['nome'] }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            {{-- Avançado --}}
            <div class="rv-filtro">
                <button type="button" class="rv-filtro__btn {{ ($incluir || $excluir) ? 'rv-filtro__btn--ativo' : '' }}"
                        :class="{ 'rv-filtro__btn--aberto': aberto === 'avancado' }"
                        @click="aberto = aberto === 'avancado' ? null : 'avancado'" :aria-expanded="aberto === 'avancado'">
                    <i class="ti ti-settings"></i> {{ __('Avançado') }} <i class="ti ti-chevron-down rv-filtro__seta"></i>
                </button>
                <div class="rv-filtro__painel" x-show="aberto === 'avancado'" x-cloak x-transition.opacity>
                    @foreach (['incluir' => [__('Adicionar palavras-chave'), __('Exemplo: Natal'), 'novaIncluir'], 'excluir' => [__('Excluir palavras-chave'), __('Exemplo: ...'), 'novaExcluir']] as $lista => [$titulo, $exemplo, $campo])
                        <form class="rv-filtro__grupo" wire:submit="adicionarPalavra('{{ $lista }}')">
                            <label class="rv-filtro__titulo" for="rv-{{ $lista }}">{{ $titulo }}</label>
                            <input id="rv-{{ $lista }}" type="text" maxlength="60" class="rv-filtro__input" wire:model="{{ $campo }}" placeholder="{{ $exemplo }}" autocomplete="off">
                            <button type="submit" class="rv-filtro__mais">+ {{ $titulo }}</button>
                            @if ($this->{$lista})
                                <div class="rv-filtro__chips">
                                    @foreach ($this->{$lista} as $palavra)
                                        <button type="button" class="rv-categoria-chip rv-categoria-chip--ativo" wire:key="av-{{ $lista }}-{{ $palavra }}" wire:click="removerPalavra('{{ $lista }}', @js($palavra))" title="{{ __('Remover filtro') }}">
                                            {{ $palavra }} <i class="ti ti-x"></i>
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </form>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Trilha de filtros ativos (acumulativos) --}}
    <div class="rv-categorias rv-categorias--ativas">
        @foreach ($ativas as $ativa)
            @if ($ativa['fixo'])
                <span class="rv-categoria-chip rv-categoria-chip--fixo"><i class="ti ti-building-store"></i> {{ $ativa['nome'] }}</span>
            @else
                <button type="button"
                        class="rv-categoria-chip rv-categoria-chip--ativo"
                        wire:key="ativa-{{ $ativa['taxonomia']->slug }}-{{ $ativa['valor'] }}"
                        wire:click="{{ $ativa['taxonomia']->usaFabricante() ? 'alternarMarca('.\Illuminate\Support\Js::from($ativa['valor']).')' : "remover('".$ativa['taxonomia']->slug."')" }}"
                        title="{{ __('Remover filtro') }} ({{ __($ativa['taxonomia']->nome) }})">
                    {{ $ativa['nome'] }} <i class="ti ti-x"></i>
                </button>
            @endif
        @endforeach

        @foreach ($recursos as $recurso)
            <button type="button" class="rv-categoria-chip rv-categoria-chip--ativo" wire:click="alternarRecurso('{{ $recurso }}')" title="{{ __('Remover filtro') }}">
                {{ $recurso === 'gratis' ? __('Grátis') : __('Premium') }} <i class="ti ti-x"></i>
            </button>
        @endforeach

        @foreach (['incluir' => __('Contém'), 'excluir' => __('Sem')] as $lista => $rotuloLista)
            @foreach ($this->{$lista} as $palavra)
                <button type="button" class="rv-categoria-chip rv-categoria-chip--ativo" wire:key="pal-{{ $lista }}-{{ $palavra }}" wire:click="removerPalavra('{{ $lista }}', @js($palavra))" title="{{ __('Remover filtro') }}">
                    {{ $rotuloLista }}: {{ $palavra }} <i class="ti ti-x"></i>
                </button>
            @endforeach
        @endforeach

        @if ($temFiltro)
            <button type="button" class="rv-limpar" wire:click="limpar">{{ __('limpar filtros') }}</button>
        @endif
    </div>

    {{-- Uma linha por taxonomia disponível (na ordem configurada) --}}
    @foreach ($linhas as $linha)
        @php($tax = $linha['taxonomia'])
        <div class="rv-categorias rv-categorias-{{ $tax->slug }} {{ $tax->pai_id ? 'rv-subcategorias' : '' }}" wire:key="linha-{{ $tax->id }}">
            <span class="rv-categorias__rotulo">{{ __($tax->nome_plural) }}</span>

            @if ($linha['opcoes']->count() > 30)
                {{-- Listas longas (ex.: marcas): seletor com todas as opções --}}
                <select class="rv-select" aria-label="{{ __($tax->nome) }}"
                        wire:change="selecionar('{{ $tax->slug }}', $event.target.value)">
                    <option value="">{{ __('Selecionar :nome', ['nome' => mb_strtolower(__($tax->nome))]) }}…</option>
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
                <i class="ti ti-arrow-up"></i> {{ __('Carregar resultados anteriores') }}
            </a>
        </div>
    @endif

    <div id="rv-grid" class="rv-familias-grid" x-data="rvPaginacao"
         wire:loading.class="opacity-60" wire:target="busca,orderby,selecionar,remover,limpar,filtrarBiblioteca,alternarRecurso,adicionarPalavra,removerPalavra">
        @foreach ($familias as $familia)
            @include('familias._card', ['familia' => $familia, 'botao' => $botoes[$familia->id], 'marcador' => $marcadores[$familia->id] ?? null, 'favorito' => $favoritos[$familia->id] ?? false])
        @endforeach
    </div>

    @if ($familias->isEmpty())
        <div class="rv-vazio">
            <i class="ti ti-mood-empty"></i>
            {{ __('Nenhum resultado para os filtros selecionados.') }}
            @if ($temFiltro)
                <div class="mt-3"><button type="button" class="rv-link" wire:click="limpar">{{ __('Limpar filtros') }}</button></div>
            @endif
        </div>
    @endif

    {{-- Sentinel do infinite scroll (IntersectionObserver em resources/js/rv-infinito.js) --}}
    <div id="rv-sentinel" class="rv-sentinel" wire:key="sentinel" x-data="rvInfinito" data-tem-mais="{{ $temMais ? 'true' : 'false' }}"></div>

    <div class="rv-loader" wire:loading.flex wire:target="carregarMais">
        <i class="ti ti-loader-2 animate-spin"></i> {{ __('Carregando...') }}
    </div>

    {{-- Paginação rastreável (buscadores e sem JS); com JS o infinite scroll carrega sozinho --}}
    @if ($ultimaPagina > 1)
        <nav class="rv-paginacao" aria-label="{{ __('Paginação') }}">
            <span class="rv-contagem">{{ __('Página :atual de :total', ['atual' => $paginaFinal, 'total' => $ultimaPagina]) }}</span>
            @if ($urlProxima)
                <a href="{{ $urlProxima }}" rel="next" class="rv-link" wire:click.prevent="carregarMais">{{ __('Próxima página') }} <i class="ti ti-arrow-right"></i></a>
            @endif
        </nav>
    @endif

    {{-- Preview 500×500 --}}
    <img wire:ignore x-show="aberto" x-cloak :src="src" :style="`left:${x}px;top:${y}px`" class="rv-img-tooltip" alt="" aria-hidden="true">
</div>
