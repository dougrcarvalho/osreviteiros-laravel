<?php

namespace Tests\Concerns;

use App\Enums\Funcao;
use App\Enums\TipoItem;
use App\Models\Categoria;
use App\Models\Fabricante;
use App\Models\Familia;
use App\Models\Plano;
use App\Models\Taxonomia;
use App\Models\User;
use App\Services\FamiliaService;
use Database\Seeders\PlanoSeeder;
use Database\Seeders\TaxonomiaSeeder;
use Illuminate\Support\Str;

/**
 * Usar junto com DatabaseTruncation (não RefreshDatabase): o FULLTEXT do
 * InnoDB não enxerga linhas de transações não commitadas.
 */
trait CriaCatalogo
{
    /** Planos + taxonomias padrão das 3 bibliotecas */
    protected function seedCatalogo(): void
    {
        $this->seed(PlanoSeeder::class);
        $this->seed(TaxonomiaSeeder::class);
    }

    protected function usuario(string $plano): User
    {
        return User::factory()->create(['plano_id' => Plano::where('slug', $plano)->value('id')]);
    }

    protected function funcionario(Funcao $funcao = Funcao::Administrador): User
    {
        return User::factory()->create(['funcao' => $funcao]);
    }

    protected function taxonomia(string $slug, TipoItem $tipo = TipoItem::Familia): Taxonomia
    {
        return Taxonomia::where('tipo_item', $tipo->value)->where('slug', $slug)->firstOrFail();
    }

    /** Cria uma taxonomia nova (ex.: "cor") como o administrador faria no painel */
    protected function novaTaxonomia(string $slug, string $nome, TipoItem $tipo = TipoItem::Familia, ?string $pai = null, ?string $plural = null): Taxonomia
    {
        return Taxonomia::create([
            'tipo_item'   => $tipo,
            'slug'        => $slug,
            'nome'        => $nome,
            'nome_plural' => $plural ?? $nome.'s',
            'pai_id'      => $pai ? $this->taxonomia($pai, $tipo)->id : null,
            'ordem'       => 50,
        ]);
    }

    protected function termo(string $nome, string $taxonomia = 'cat', TipoItem $tipo = TipoItem::Familia, ?string $slug = null): Categoria
    {
        return Categoria::factory()->daTaxonomia($this->taxonomia($taxonomia, $tipo))
            ->create(['nome' => $nome, 'slug' => $slug ?? Str::slug($nome)]);
    }

    /** Atalho para Famílias: nível 1 = cat, 2 = sub, 3 = subcl */
    protected function categoria(string $nome, int $nivel = 1, ?string $slug = null): Categoria
    {
        return $this->termo($nome, [1 => 'cat', 2 => 'sub', 3 => 'subcl'][$nivel], TipoItem::Familia, $slug);
    }

    protected function marca(string $nome): Fabricante
    {
        return Fabricante::factory()->create(['nome' => $nome, 'slug' => Str::slug($nome)]);
    }

    /**
     * @param  list<Categoria|Fabricante>  $classificacao  termos (pivot) e/ou o fabricante
     */
    protected function familia(string $nome, array $classificacao = [], array $attrs = []): Familia
    {
        $marca = collect($classificacao)->first(fn ($c) => $c instanceof Fabricante);

        $familia = Familia::factory()->create($attrs + [
            'nome'          => $nome,
            'slug'          => Str::slug($nome),
            'fabricante_id' => $marca?->id,
        ]);

        app(FamiliaService::class)->sincronizarCategorias(
            $familia,
            collect($classificacao)->filter(fn ($c) => $c instanceof Categoria)->pluck('id')->all(),
        );

        return $familia->fresh();
    }
}
