# Spec: Famílias Revit BIM

## Migration

```php
// database/migrations/YYYY_MM_DD_create_familias_table.php

Schema::create('familias', function (Blueprint $table) {
    $table->id();
    $table->string('nome');
    $table->string('slug')->unique();
    $table->text('descricao')->nullable();
    $table->string('imagem_url')->nullable();
    $table->string('link_download')->nullable();   // NUNCA expor sem verificação de plano
    $table->unsignedInteger('curtidas')->default(0);
    $table->unsignedBigInteger('fabricante_id')->nullable();
    $table->boolean('ativo')->default(true);
    $table->timestamps();

    $table->foreign('fabricante_id')->references('id')->on('fabricantes')->nullOnDelete();
    $table->index(['ativo', 'created_at']);
    $table->fullText(['nome', 'descricao']);
});

// Pivot: familia_categoria
Schema::create('familia_categoria', function (Blueprint $table) {
    $table->unsignedBigInteger('familia_id');
    $table->unsignedBigInteger('categoria_id');
    $table->primary(['familia_id', 'categoria_id']);
});
```

## Model

```php
// app/Models/Familia.php

class Familia extends Model
{
    protected $fillable = [
        'nome', 'slug', 'descricao', 'imagem_url',
        'link_download', 'curtidas', 'fabricante_id', 'ativo',
    ];

    // ─── Relacionamentos ────────────────────────────────────────
    public function categorias(): BelongsToMany
    {
        return $this->belongsToMany(Categoria::class, 'familia_categoria');
    }

    public function fabricante(): BelongsTo
    {
        return $this->belongsTo(Fabricante::class);
    }

    // ─── Scopes ─────────────────────────────────────────────────
    public function scopeAtivo(Builder $q): Builder
    {
        return $q->where('ativo', true);
    }

    public function scopePorCategoria(Builder $q, string $slug): Builder
    {
        return $q->whereHas('categorias', fn($c) => $c->where('slug', $slug));
    }

    public function scopeSearch(Builder $q, string $busca): Builder
    {
        return $q->whereFullText(['nome', 'descricao'], $busca)
                 ->orWhere('nome', 'like', "%{$busca}%");
    }

    // ─── Helpers de acesso ──────────────────────────────────────
    public function isGratuita(): bool
    {
        return $this->categorias->contains('slug', 'acessar-gratis');
    }

    public function getLinkDownloadFor(?User $user): ?string
    {
        if (! $user) return null;
        if ($user->hasPlanoGratis() && ! $this->isGratuita()) return null;
        return $this->link_download;
    }

    // ─── Cast / Acessor ─────────────────────────────────────────
    public function getIsNovoAttribute(): bool
    {
        return $this->created_at->gt(now()->subDays(30));
    }
}
```

## Controller (Index)

```php
// app/Http/Controllers/FamiliaController.php

public function index(Request $request)
{
    return view('familias.index', [
        'categorias' => Categoria::nivel(1)->ordenadas()->get(),
    ]);
}
```

## API Controller (Infinite Scroll)

```php
// app/Http/Controllers/Api/FamiliaController.php

public function index(Request $request): JsonResponse
{
    $validated = $request->validate([
        'page'       => 'integer|min:1',
        'per_page'   => 'integer|min:1|max:96',
        'cat'        => 'nullable|string',
        'sub'        => 'nullable|string',
        'subcl'      => 'nullable|string',
        'marca'      => 'nullable|string',
        'busca'      => 'nullable|string|max:100',
        'orderby'    => 'nullable|in:date,az,curtidas',
    ]);

    $query = Familia::ativo()->with(['fabricante', 'categorias']);

    // Filtros acumulativos (todos os níveis ativos)
    foreach (['cat', 'sub', 'subcl', 'marca'] as $param) {
        if (! empty($validated[$param])) {
            $query->porCategoria($validated[$param]);
        }
    }

    if (! empty($validated['busca'])) {
        $query->search($validated['busca']);
    }

    // Ordenação
    match ($validated['orderby'] ?? 'date') {
        'az'       => $query->orderBy('nome'),
        'curtidas' => $query->orderByDesc('curtidas'),
        default    => $query->latest(),
    };

    $paginator = $query->paginate($validated['per_page'] ?? 48);
    $user      = $request->user();

    $items = $paginator->map(function (Familia $f) use ($user) {
        return [
            'id'           => $f->id,
            'nome'         => $f->nome,
            'slug'         => $f->slug,
            'url'          => route('familias.show', $f->slug),
            'imagem'       => $f->imagem_url,
            'fabricante'   => $f->fabricante?->nome,
            'is_novo'      => $f->is_novo,
            'curtidas'     => $f->curtidas,
            // Access flags
            'is_gratuita'  => $f->isGratuita(),
            'is_logged'    => (bool) $user,
            'has_plano'    => $user?->hasPlano(),
            'link_download'=> $f->getLinkDownloadFor($user),
        ];
    });

    return response()->json([
        'data' => $items,
        'meta' => [
            'total'        => $paginator->total(),
            'per_page'     => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page'    => $paginator->lastPage(),
        ],
    ]);
}
```

## Livewire: FamiliaGrid

```php
// app/Http/Livewire/FamiliaGrid.php

class FamiliaGrid extends Component
{
    public string $busca     = '';
    public string $cat       = '';
    public string $sub       = '';
    public string $subcl     = '';
    public string $marca     = '';
    public string $orderby   = 'date';
    public int    $page      = 1;

    // Subcategorias visíveis (carregadas ao clicar em categoria)
    public array $subcategorias   = [];
    public array $subclassificacoes = [];
    public array $marcas          = [];

    public function updatedCat(string $slug): void
    {
        $this->sub   = '';
        $this->subcl = '';
        $this->marca = '';
        $this->page  = 1;
        $this->subcategorias = Categoria::filhosDeSlug($slug, 'subcategorias')->toArray();
    }

    public function updatedSub(string $slug): void
    {
        $this->subcl = '';
        $this->marca = '';
        $this->page  = 1;
        $this->subclassificacoes = Categoria::filhosDeSlug($slug, 'subclassificacao')->toArray();
    }

    // ... similar para subcl → marcas

    public function render(): View
    {
        // O grid em si é renderizado via JS (infinite scroll)
        // Este componente só gerencia o estado dos filtros
        return view('livewire.familia-grid');
    }
}
```

## Botões de Download (4 estados)

```html
<!-- Não logado -->
<button class="rv-btn rv-btn--cadastrar" @click="$dispatch('abrir-popup')">
    Baixar Grátis
</button>

<!-- Logado grátis, família não-gratuita -->
<button class="rv-btn rv-btn--assinar" @click="$dispatch('abrir-upgrade')">
    Assinar para Baixar
</button>

<!-- Logado grátis, família gratuita -->
<a href="{{ $link }}" class="rv-btn rv-btn--baixar" target="_blank">
    <i class="ti ti-download"></i> Baixar
</a>

<!-- Logado com plano pago -->
<a href="{{ $link }}" class="rv-btn rv-btn--baixar" target="_blank">
    <i class="ti ti-download"></i> Baixar
</a>
```
