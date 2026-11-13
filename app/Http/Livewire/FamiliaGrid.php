<?php

namespace App\Http\Livewire;

use App\Enums\TipoItem;
use App\Models\Fabricante;
use App\Models\Familia;
use App\Models\Taxonomia;
use App\Services\CategoriaService;
use App\Services\DownloadService;
use App\Services\FamiliaBuscaService;
use App\Services\FavoritoService;
use App\Services\TaxonomiaService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use App\Support\UrlListagem;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Grade de itens com filtros acumulativos e infinite scroll.
 *
 * Os filtros são guiados pelas taxonomias da biblioteca (tabela taxonomias):
 * - famílias: cat → sub → subcl → marca
 * - texturas: cat → marca
 * - projetos: cat, tipo
 * Taxonomias novas (ex.: "cor") aparecem aqui sem alterar código.
 *
 * Uma taxonomia com `pai` só aparece depois que o pai é escolhido; sem pai,
 * aparece desde o início (faceta independente). Ao trocar um filtro, os que
 * dependem dele são limpos.
 *
 * URL: /{biblioteca}/page/{n}/?cat=…&sub=…&marca=…&recurso=gratis,premium&incluir=…&excluir=…&busca=…&orderby=…
 * (a barra "Filtrar por": Recurso, Fabricantes e Avançado; a marca é a taxonomia de fonte fabricante)
 * - filtros: o componente dispara `rv-url` (resources/js/rv-historico.js faz o
 *   pushState; voltar/avançar chamam sincronizarUrl());
 * - paginação SEO: cada bloco de 48 itens é uma página com URL própria; o
 *   primeiro card de cada página leva data-pagina-inicio/data-url e
 *   resources/js/rv-paginacao.js troca a URL (replaceState) conforme a rolagem.
 *   Abrindo /page/3/ direto, a grade começa na página 3 e permite carregar as anteriores.
 * Na tela do fabricante, a marca é fixa e a biblioteca é escolhida nas abas (?biblioteca=textura).
 */
class FamiliaGrid extends Component
{
    /** familia|textura|projeto ('' = todas, só na tela do fabricante) */
    #[Locked]
    public string $biblioteca = '';

    #[Locked]
    public bool $bibliotecaFixa = false;

    /** Slug do fabricante fixo (tela da marca) */
    #[Locked]
    public string $marcaFixa = '';

    /** @var array<string, string> slug da taxonomia => slug do termo/fabricante */
    public array $filtros = [];

    public string $busca = '';

    public string $orderby = 'date';

    /**
     * Filtro "Recurso": gratis e/ou premium (itens pagos); os dois = sem filtro
     *
     * @var list<string>
     */
    public array $recursos = [];

    /**
     * Filtro "Avançado": palavras que o item precisa ter / não pode ter
     *
     * @var list<string>
     */
    public array $incluir = [];

    /** @var list<string> */
    public array $excluir = [];

    /** Campos de digitação do painel Avançado */
    public string $novaIncluir = '';

    public string $novaExcluir = '';

    /** Primeira e última página carregadas (a grade pode começar no meio: /page/3/) */
    public int $paginaInicial = 1;

    public int $paginaFinal = 1;

    /** URL da página 1 da listagem (/familias-revit, /fabricantes/toyota…) */
    #[Locked]
    public string $urlBase = '';

    public function mount(?string $biblioteca = null, ?string $marca = null, ?array $query = null, int $pagina = 1, ?string $urlBase = null): void
    {
        if ($biblioteca) {
            $this->biblioteca     = TipoItem::from($biblioteca)->value;
            $this->bibliotecaFixa = true;
        }

        $this->marcaFixa = (string) $marca;
        $this->urlBase   = $urlBase ?? match (true) {
            $this->bibliotecaFixa => route(TipoItem::from($this->biblioteca)->rotaIndex()),
            $this->marcaFixa !== '' => route('fabricantes.show', $this->marcaFixa),
            default => url()->current(),
        };

        $this->paginaInicial = $this->paginaFinal = max(1, $pagina);

        $this->aplicarQuery($query ?? request()->query());
    }

    // ─── Ações ──────────────────────────────────────────────────

    public function selecionar(string $taxonomia, string $valor): void
    {
        $tax = $this->taxonomiaDaBiblioteca($taxonomia);

        if (! $tax || $valor === '' || mb_strlen($valor) > 200 || $this->ehMarcaFixa($tax)) {
            return;
        }

        $this->filtros[$tax->slug] = $valor;
        $this->limparDependentes($tax);
        $this->navegou();
    }

    public function remover(string $taxonomia): void
    {
        $tax = $this->taxonomiaDaBiblioteca($taxonomia);

        if (! $tax || $this->ehMarcaFixa($tax)) {
            return;
        }

        unset($this->filtros[$tax->slug]);
        $this->limparDependentes($tax);
        $this->navegou();
    }

    /** Fabricantes: marca ou desmarca um (vários podem ficar ativos; o resultado traz todos) */
    public function alternarMarca(string $slug): void
    {
        $tax = collect(app(TaxonomiaService::class)->doTipo($this->biblioteca))->first(fn (Taxonomia $t) => $t->usaFabricante());

        if (! $tax || $this->ehMarcaFixa($tax) || $slug === '' || mb_strlen($slug) > 120 || str_contains($slug, ',')) {
            return;
        }

        $marcas = FamiliaBuscaService::lista($this->filtros[$tax->slug] ?? '');
        $marcas = in_array($slug, $marcas, true)
            ? array_values(array_diff($marcas, [$slug]))
            : [...$marcas, $slug];

        if ($marcas) {
            $this->filtros[$tax->slug] = implode(',', array_slice($marcas, 0, 30));
        } else {
            unset($this->filtros[$tax->slug]);
        }

        $this->navegou();
    }

    public function filtrarBiblioteca(string $valor): void
    {
        if ($this->bibliotecaFixa) {
            return;
        }

        $this->biblioteca = TipoItem::tryFrom($valor)?->value ?? '';
        $this->filtros    = []; // cada biblioteca tem suas taxonomias
        $this->navegou();
    }

    public function alternarRecurso(string $recurso): void
    {
        if (! in_array($recurso, FamiliaBuscaService::RECURSOS, true)) {
            return;
        }

        $this->recursos = in_array($recurso, $this->recursos, true)
            ? array_values(array_diff($this->recursos, [$recurso]))
            : [...$this->recursos, $recurso];

        $this->navegou();
    }

    /** @param  'incluir'|'excluir'  $lista */
    public function adicionarPalavra(string $lista): void
    {
        if (! in_array($lista, ['incluir', 'excluir'], true)) {
            return;
        }

        $campo = $lista === 'incluir' ? 'novaIncluir' : 'novaExcluir';

        foreach (FamiliaBuscaService::lista(str_replace(',', ' ', $this->{$campo})) as $palavra) {
            if (count($this->{$lista}) < 10 && ! in_array(mb_strtolower($palavra), array_map('mb_strtolower', $this->{$lista}), true)) {
                $this->{$lista}[] = $palavra;
            }
        }

        $this->{$campo} = '';
        $this->navegou();
    }

    /** @param  'incluir'|'excluir'  $lista */
    public function removerPalavra(string $lista, string $palavra): void
    {
        if (! in_array($lista, ['incluir', 'excluir'], true)) {
            return;
        }

        $this->{$lista} = array_values(array_diff($this->{$lista}, [$palavra]));
        $this->navegou();
    }

    public function limpar(): void
    {
        $this->filtros = [];
        $this->busca   = '';
        $this->limparAvancado();

        if (! $this->bibliotecaFixa) {
            $this->biblioteca = '';
        }

        $this->navegou();
    }

    public function carregarMais(): void
    {
        $this->paginaFinal++; // limitado à última página no render
    }

    /** Aberto em /page/3/: traz a página 2 para cima (o JS preserva a posição da rolagem) */
    public function carregarAnteriores(): void
    {
        $this->paginaInicial = max(1, $this->paginaInicial - 1);
    }

    /** Digitação na busca e troca de ordem: atualiza a URL sem criar entrada no histórico */
    public function updated(string $prop): void
    {
        if (in_array($prop, ['busca', 'orderby'], true)) {
            $this->normalizar();
            $this->voltarParaPrimeiraPagina();
            $this->dispatch('rv-url', url: $this->urlPagina(1), push: false);
        }
    }

    /**
     * Botão "voltar/avançar" do navegador: restaura todos os filtros da URL.
     *
     * @param  array<string, mixed>  $query
     */
    public function sincronizarUrl(array $query, int $pagina = 1): void
    {
        $this->filtros = [];
        $this->busca   = '';
        $this->limparAvancado();
        $this->orderby = 'date';

        if (! $this->bibliotecaFixa) {
            $this->biblioteca = '';
        }

        $this->aplicarQuery($query);
        $this->paginaInicial = $this->paginaFinal = max(1, $pagina);
    }

    // ─── Renderização ───────────────────────────────────────────

    public function render(FamiliaBuscaService $busca, CategoriaService $categorias, TaxonomiaService $taxonomias, DownloadService $download): View
    {
        $this->normalizar();

        $filtros     = $this->filtrosEfetivos();
        $taxonomiasDaBiblioteca = $taxonomias->doTipo($this->biblioteca);

        $porPagina = FamiliaBuscaService::PER_PAGE;
        $query     = $busca->query($filtros);
        $total     = (clone $query)->reorder()->count();
        $ultima    = UrlListagem::ultimaPagina($total, $porPagina);

        $this->paginaFinal   = min(max($this->paginaFinal, 1), $ultima);
        $this->paginaInicial = min(max($this->paginaInicial, 1), $this->paginaFinal);

        $familias = $query->with('fabricante')
            ->offset(($this->paginaInicial - 1) * $porPagina)
            ->limit(($this->paginaFinal - $this->paginaInicial + 1) * $porPagina)
            ->get();

        // Primeiro card de cada página → URL dela (rv-paginacao.js troca a URL na rolagem)
        $marcadores = [];
        foreach (range($this->paginaInicial, $this->paginaFinal) as $i => $pagina) {
            if ($familia = $familias->get($i * $porPagina)) {
                $marcadores[$familia->id] = ['pagina' => $pagina, 'url' => $this->urlPagina($pagina)];
            }
        }

        $user = Auth::user();

        [$ativas, $linhas, $fabricantes] = $this->navegacao($taxonomiasDaBiblioteca, $filtros, $categorias);

        return view('livewire.familia-grid', [
            'familias' => $familias,
            'total'    => $total,
            'temMais'  => $this->paginaFinal < $ultima,
            'ultimaPagina' => $ultima,
            'marcadores'   => $marcadores,
            'urlAnterior'  => $this->paginaInicial > 1 ? $this->urlPagina($this->paginaInicial - 1) : null,
            'urlProxima'   => $this->paginaFinal < $ultima ? $this->urlPagina($this->paginaFinal + 1) : null,
            'favoritos' => app(FavoritoService::class)->dentre($user, $familias->pluck('id')),
            'ativas'   => $ativas,
            'linhas'   => $linhas,
            'fabricantes' => $fabricantes,
            'botoes'   => $familias->mapWithKeys(fn (Familia $f) => [$f->id => $download->estadoBotao($user, $f)]),
            'tipos'    => TipoItem::cases(),
            'temFiltro' => $this->temFiltro(),
        ]);
    }

    /**
     * Trilha de filtros ativos + linhas de opções ainda disponíveis.
     *
     * @param  Collection<int, Taxonomia>  $taxonomias
     * @return array{0: list<array{taxonomia: ?Taxonomia, nome: string, fixo: bool}>, 1: list<array{taxonomia: Taxonomia, opcoes: Collection}>, 2: ?array{taxonomia: Taxonomia, opcoes: Collection}}
     */
    private function navegacao(Collection $taxonomias, array $filtros, CategoriaService $categorias): array
    {
        $ativas = [];
        $linhas = [];
        $fabricantes = null; // dropdown "Fabricantes" (a marca não vira linha de chips)

        /** @var Taxonomia $tax */
        foreach ($taxonomias as $tax) {
            $valor = $filtros[$tax->slug] ?? null;

            if ($tax->usaFabricante() && $this->marcaFixa === '') {
                $fabricantes = ['taxonomia' => $tax, 'opcoes' => $categorias->opcoes($tax, $filtros)];
            }

            if (filled($valor)) {
                // Marca aceita várias (um chip por fabricante); as demais, um valor só
                foreach ($tax->usaFabricante() ? FamiliaBuscaService::lista($valor) : [$valor] as $slug) {
                    $ativas[] = [
                        'taxonomia' => $tax,
                        'valor'     => $slug,
                        'nome'      => $categorias->rotulo($tax, $slug),
                        'fixo'      => $this->ehMarcaFixa($tax),
                    ];
                }

                if (! $tax->usaFabricante()) {
                    continue;
                }
            }

            $pai = $tax->pai_id ? $taxonomias->firstWhere('id', $tax->pai_id) : null;

            if ($pai && blank($filtros[$pai->slug] ?? null)) {
                continue; // só depois do pai escolhido
            }

            if ($tax->usaFabricante()) {
                continue;
            }

            $opcoes = $categorias->opcoes($tax, $filtros);

            if ($opcoes->isNotEmpty()) {
                $linhas[] = ['taxonomia' => $tax, 'opcoes' => $opcoes];
            }
        }

        // Marca fixa sem taxonomia de marca na biblioteca atual (aba "Tudo"): mostra o chip mesmo assim
        if ($this->marcaFixa !== '' && ! collect($ativas)->contains(fn ($a) => $a['fixo'])) {
            array_unshift($ativas, [
                'taxonomia' => null,
                'nome'      => Fabricante::where('slug', $this->marcaFixa)->value('nome') ?? $this->marcaFixa,
                'fixo'      => true,
            ]);
        }

        return [$ativas, $linhas, $fabricantes];
    }

    // ─── Estado ─────────────────────────────────────────────────

    /** @return array<string, mixed> filtros no formato do FamiliaBuscaService */
    private function filtrosEfetivos(): array
    {
        $filtros = [];

        // Só slugs de taxonomias da biblioteca atual (o resto da URL é ignorado)
        foreach (app(TaxonomiaService::class)->doTipo($this->biblioteca) as $tax) {
            if (filled($this->filtros[$tax->slug] ?? null)) {
                $filtros[$tax->slug] = (string) $this->filtros[$tax->slug];
            }
        }

        if ($this->marcaFixa !== '') {
            $filtros['marca'] = $this->marcaFixa;
        }

        return $filtros + [
            'biblioteca' => $this->biblioteca,
            'busca'      => $this->busca,
            'orderby'    => $this->orderby,
            'recurso'    => $this->recursos,
            'incluir'    => $this->incluir,
            'excluir'    => $this->excluir,
        ];
    }

    /** @param  array<string, mixed>  $query */
    private function aplicarQuery(array $query): void
    {
        if (! $this->bibliotecaFixa) {
            $this->biblioteca = TipoItem::tryFrom((string) ($query['biblioteca'] ?? ''))?->value ?? '';
        }

        $this->busca   = is_string($query['busca'] ?? null) ? $query['busca'] : '';
        $this->orderby = is_string($query['orderby'] ?? null) ? $query['orderby'] : 'date';

        $recursos = FamiliaBuscaService::lista($query['recurso'] ?? '');
        if (in_array($query['gratis'] ?? null, ['1', 1, true], true)) { // URL antiga (?gratis=1)
            $recursos[] = 'gratis';
        }
        $this->recursos = array_values(array_unique(array_intersect(FamiliaBuscaService::RECURSOS, $recursos)));
        $this->incluir  = array_slice(FamiliaBuscaService::lista($query['incluir'] ?? ''), 0, 10);
        $this->excluir  = array_slice(FamiliaBuscaService::lista($query['excluir'] ?? ''), 0, 10);

        foreach (app(TaxonomiaService::class)->doTipo($this->biblioteca) as $tax) {
            $valor = $query[$tax->slug] ?? null;

            if (is_string($valor) && $valor !== '' && mb_strlen($valor) <= 600 && ! $this->ehMarcaFixa($tax)) {
                $this->filtros[$tax->slug] = $tax->usaFabricante()
                    ? implode(',', array_slice(FamiliaBuscaService::lista($valor), 0, 30))
                    : $valor;
            }
        }

        $this->normalizar();
    }

    /** @return array<string, string> parâmetros da URL (só o que difere do padrão) */
    private function queryAtual(): array
    {
        $query = [];

        if (! $this->bibliotecaFixa && $this->biblioteca !== '') {
            $query['biblioteca'] = $this->biblioteca;
        }

        foreach (app(TaxonomiaService::class)->doTipo($this->biblioteca) as $tax) {
            if (filled($this->filtros[$tax->slug] ?? null) && ! $this->ehMarcaFixa($tax)) {
                $query[$tax->slug] = $this->filtros[$tax->slug];
            }
        }

        if ($this->recursos) {
            $query['recurso'] = implode(',', $this->recursos);
        }

        if ($this->incluir) {
            $query['incluir'] = implode(',', $this->incluir);
        }

        if ($this->excluir) {
            $query['excluir'] = implode(',', $this->excluir);
        }

        if ($this->busca !== '') {
            $query['busca'] = $this->busca;
        }

        if ($this->orderby !== 'date') {
            $query['orderby'] = $this->orderby;
        }

        return $query;
    }

    private function limparAvancado(): void
    {
        $this->recursos = $this->incluir = $this->excluir = [];
        $this->novaIncluir = $this->novaExcluir = '';
    }

    private function navegou(): void
    {
        $this->voltarParaPrimeiraPagina();
        $this->dispatch('rv-url', url: $this->urlPagina(1), push: true);
    }

    private function voltarParaPrimeiraPagina(): void
    {
        $this->paginaInicial = $this->paginaFinal = 1;
    }

    private function urlPagina(int $pagina): string
    {
        return UrlListagem::pagina($this->urlBase, $pagina, $this->queryAtual());
    }

    private function temFiltro(): bool
    {
        return $this->busca !== '' || $this->recursos || $this->incluir || $this->excluir || ! empty(array_filter($this->filtros))
            || (! $this->bibliotecaFixa && $this->biblioteca !== '');
    }

    private function taxonomiaDaBiblioteca(string $slug): ?Taxonomia
    {
        return app(TaxonomiaService::class)->porSlug($this->biblioteca, $slug);
    }

    private function ehMarcaFixa(Taxonomia $tax): bool
    {
        return $this->marcaFixa !== '' && $tax->usaFabricante();
    }

    /** Limpa os filtros que dependem (direta ou indiretamente) de $tax */
    private function limparDependentes(Taxonomia $tax): void
    {
        $service = app(TaxonomiaService::class);

        foreach ($service->descendentes($service->doTipo($this->biblioteca), $tax) as $slug) {
            $descendente = $service->porSlug($this->biblioteca, $slug);

            // A marca é escolhida no dropdown "Fabricantes", independente da categoria
            if ($descendente && ($this->ehMarcaFixa($descendente) || $descendente->usaFabricante())) {
                continue;
            }

            unset($this->filtros[$slug]);
        }
    }

    private function normalizar(): void
    {
        if (! in_array($this->orderby, FamiliaBuscaService::ORDENS, true)) {
            $this->orderby = 'date';
        }

        $this->busca = mb_substr($this->busca, 0, 100);
    }
}
