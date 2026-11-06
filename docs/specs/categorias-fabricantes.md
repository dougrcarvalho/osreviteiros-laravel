# Spec: Categorias e Fabricantes

## Migration: Categorias (árvore hierárquica)

```php
Schema::create('categorias', function (Blueprint $table) {
    $table->id();
    $table->string('nome');
    $table->string('slug')->unique();
    $table->unsignedBigInteger('parent_id')->nullable();
    $table->tinyInteger('nivel')->default(1);  // 1=categoria, 2=subcategoria, 3=subclassificacao, 4=marca
    $table->string('taxonomia');               // mirror do slug WP: categorias|subcategorias|subclassificacao|selecionar-marca
    $table->integer('ordem')->default(0);
    $table->timestamps();

    $table->foreign('parent_id')->references('id')->on('categorias')->nullOnDelete();
    $table->index(['nivel', 'parent_id']);
    $table->index('slug');
});
```

## Migration: Fabricantes

```php
Schema::create('fabricantes', function (Blueprint $table) {
    $table->id();
    $table->string('nome');
    $table->string('slug')->unique();
    $table->string('logo_url')->nullable();
    $table->text('descricao')->nullable();
    $table->string('site_url')->nullable();
    $table->timestamps();
});
```

## Model: Categoria

```php
class Categoria extends Model
{
    protected $fillable = ['nome', 'slug', 'parent_id', 'nivel', 'taxonomia', 'ordem'];

    // ─── Relacionamentos ────────────────────────────────────────
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'parent_id');
    }

    public function filhos(): HasMany
    {
        return $this->hasMany(Categoria::class, 'parent_id')->orderBy('nome');
    }

    public function familias(): BelongsToMany
    {
        return $this->belongsToMany(Familia::class, 'familia_categoria');
    }

    // ─── Scopes ─────────────────────────────────────────────────
    public function scopeNivel(Builder $q, int $nivel): Builder
    {
        return $q->where('nivel', $nivel);
    }

    public function scopeOrdenadas(Builder $q): Builder
    {
        return $q->orderBy('nome');
    }

    // ─── Static helpers ─────────────────────────────────────────

    /**
     * Retorna filhos de uma categoria pelo slug, filtrados pelas famílias
     * que existem dentro do contexto acumulativo de filtros.
     *
     * Equivalent to WordPress rv_child_terms() / get_familia_child_terms()
     */
    public static function filhosDeSlug(string $parentSlug, array $filtrosAtivos = []): Collection
    {
        $parent = static::where('slug', $parentSlug)->firstOrFail();

        // Busca famílias que atendem a TODOS os filtros ativos
        $familiaIds = Familia::ativo()
            ->when($filtrosAtivos, function (Builder $q) use ($filtrosAtivos) {
                foreach ($filtrosAtivos as $slug) {
                    $q->whereHas('categorias', fn($c) => $c->where('slug', $slug));
                }
            })
            ->pluck('id');

        // Retorna filhos que têm famílias dentro desse conjunto
        return static::where('parent_id', $parent->id)
            ->whereHas('familias', fn($q) => $q->whereIn('familias.id', $familiaIds))
            ->orderBy('nome')
            ->get(['id', 'nome', 'slug', 'nivel']);
    }
}
```

## Model: Fabricante

```php
class Fabricante extends Model
{
    protected $fillable = ['nome', 'slug', 'logo_url', 'descricao', 'site_url'];

    public function familias(): HasMany
    {
        return $this->hasMany(Familia::class);
    }

    public function getTotalFamiliasAttribute(): int
    {
        return $this->familias()->count();
    }

    public function getInicialAttribute(): string
    {
        return strtoupper(mb_substr($this->nome, 0, 1));
    }
}
```

## Controller: FabricanteController

```php
class FabricanteController extends Controller
{
    public function index(): View
    {
        $fabricantes = Fabricante::withCount('familias')
            ->orderBy('nome')
            ->get();

        return view('fabricantes.index', compact('fabricantes'));
    }
}
```

## View: fabricantes/index.blade.php (estrutura)

```html
<section class="rv-fabricantes">
    <header class="rv-fabricantes__header">
        <h1>Fabricantes</h1>
        <span class="rv-badge">{{ $fabricantes->count() }} fabricantes</span>
    </header>

    <!-- Busca client-side (Alpine.js) -->
    <div x-data="{ busca: '' }">
        <input type="text"
               x-model="busca"
               placeholder="Buscar fabricante..."
               class="rv-input">

        <div class="rv-fabricantes-grid">
            @foreach($fabricantes as $fab)
            <a href="/familias?marca={{ $fab->slug }}"
               class="rv-fabricante-card"
               x-show="!busca || '{{ strtolower($fab->nome) }}'.includes(busca.toLowerCase())"
               data-nome="{{ $fab->nome }}">

                @if($fab->logo_url)
                    <img src="{{ $fab->logo_url }}" alt="{{ $fab->nome }}"
                         class="rv-fabricante-card__logo">
                @else
                    <div class="rv-fabricante-card__inicial">{{ $fab->inicial }}</div>
                @endif

                <div class="rv-fabricante-card__info">
                    <strong>{{ $fab->nome }}</strong>
                    <span>{{ $fab->familias_count }} família(s)</span>
                </div>
            </a>
            @endforeach
        </div>
    </div>
</section>
```

## Importação de dados WP

```php
// app/Console/Commands/ImportarCategorias.php
// Lê o XML export do WP e popula a tabela categorias

php artisan reviteiros:importar-categorias --xml=wp-export.xml

/*
Mapeamento de taxonomias WP → nivel Laravel:
  categorias        → nivel 1
  subcategorias     → nivel 2
  subclassificacao  → nivel 3
  selecionar-marca  → nivel 4 (+ cria fabricante se não existir)
*/
```
