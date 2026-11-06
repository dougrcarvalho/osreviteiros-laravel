<?php

namespace App\Services;

use App\Enums\TipoItem;
use App\Models\Taxonomia;
use Illuminate\Support\Collection;

/** Consulta das taxonomias configuradas para cada tipo de item */
class TaxonomiaService
{
    /**
     * Taxonomias ativas de um tipo, na ordem de exibição.
     *
     * @return Collection<int, Taxonomia>
     */
    public function doTipo(TipoItem|string|null $tipo): Collection
    {
        if ($tipo === null || $tipo === '') {
            return collect();
        }

        return Taxonomia::query()->doTipo($tipo)->ativas()->ordenadas()->get();
    }

    public function porSlug(TipoItem|string $tipo, string $slug): ?Taxonomia
    {
        return $this->doTipo($tipo)->firstWhere('slug', $slug);
    }

    /**
     * Slugs de todas as taxonomias que dependem (direta ou indiretamente) da informada.
     * Ao mudar a categoria, a subcategoria e a subclassificação escolhidas deixam de valer.
     *
     * @param  Collection<int, Taxonomia>  $taxonomias  as do mesmo tipo
     * @return list<string>
     */
    public function descendentes(Collection $taxonomias, Taxonomia $taxonomia): array
    {
        $slugs = [];
        $fila  = [$taxonomia->id];

        while ($fila) {
            $pai = array_shift($fila);

            foreach ($taxonomias->where('pai_id', $pai) as $filha) {
                $slugs[] = $filha->slug;
                $fila[]  = $filha->id;
            }
        }

        return $slugs;
    }
}
