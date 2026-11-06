<?php

namespace App\Services;

use App\Models\Categoria;
use App\Models\Fabricante;
use App\Models\Taxonomia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Opções de cada taxonomia na navegação do site.
 *
 * As opções de uma taxonomia são os termos (ou fabricantes) presentes nos
 * itens que atendem a TODOS os demais filtros ativos — equivalente ao
 * rv_child_terms() do WordPress. Por isso a navegação funciona tanto descendo
 * (cat → sub → subcl) quanto partindo de uma marca ou de uma faceta (cor).
 */
class CategoriaService
{
    private const CACHE_VERSAO = 'categorias:versao';

    private const CACHE_TTL = 600;

    public function __construct(private FamiliaBuscaService $busca) {}

    /**
     * @param  array<string, mixed>  $filtros
     * @return Collection<int, array{id: int, nome: string, slug: string}>
     */
    public function opcoes(Taxonomia $taxonomia, array $filtros): Collection
    {
        // A própria taxonomia não restringe suas opções
        $contexto = array_filter(
            array_diff_key($filtros, [$taxonomia->slug => true, 'orderby' => true]),
            fn ($v) => filled($v),
        );
        ksort($contexto);

        $chave = sprintf('categorias:opcoes:v%d:%d:%s', $this->versao(), $taxonomia->id, md5(serialize($contexto)));

        // Cache guarda arrays simples (o Laravel não desserializa objetos do cache)
        $linhas = Cache::remember($chave, self::CACHE_TTL, function () use ($taxonomia, $contexto) {
            $contexto['biblioteca'] = $taxonomia->tipo_item->value;
            $filtraItens = fn (Builder $q) => $this->busca->aplicarFiltros($q->ativo(), $contexto);

            $query = $taxonomia->usaFabricante()
                ? Fabricante::query()->whereHas('familias', $filtraItens)->orderBy('nome')
                : Categoria::query()->daTaxonomia($taxonomia)->whereHas('familias', $filtraItens)->ordenadas();

            return $query->get(['id', 'nome', 'slug'])->toArray();
        });

        return collect($linhas);
    }

    /** Nome do termo/fabricante selecionado (para os chips da trilha de filtros) */
    public function rotulo(Taxonomia $taxonomia, string $slug): string
    {
        $modelo = $taxonomia->usaFabricante()
            ? Fabricante::where('slug', $slug)->first()
            : Categoria::daTaxonomia($taxonomia)->where('slug', $slug)->first();

        return $modelo?->nome ?? $slug;
    }

    /** Cria um termo. Taxonomias de fabricante não têm termos próprios. */
    public function criar(Taxonomia $taxonomia, string $nome, ?string $slug = null): Categoria
    {
        if ($taxonomia->usaFabricante()) {
            throw new InvalidArgumentException('Marcas são cadastradas em Fabricantes.');
        }

        return Categoria::create([
            'taxonomia_id' => $taxonomia->id,
            'nome'         => $nome,
            'slug'         => $this->slugUnico($taxonomia, $slug ?: $nome),
        ]);
    }

    /** Slug único dentro da taxonomia (como no WordPress, onde era único por taxonomia) */
    public function slugUnico(Taxonomia $taxonomia, string $texto, ?int $ignorarId = null): string
    {
        $base = Str::slug($texto) ?: 'termo';
        $slug = $base;
        $i    = 2;

        while (Categoria::daTaxonomia($taxonomia)->where('slug', $slug)->when($ignorarId, fn ($q) => $q->whereKeyNot($ignorarId))->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    /** Invalida o cache de opções (chamado quando termos, itens ou taxonomias mudam) */
    public function limparCache(): void
    {
        Cache::forever(self::CACHE_VERSAO, $this->versao() + 1);
    }

    private function versao(): int
    {
        return (int) Cache::get(self::CACHE_VERSAO, 1);
    }
}
