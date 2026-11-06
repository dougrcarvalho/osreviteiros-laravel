<?php

namespace App\Services;

use App\Models\Familia;

class FamiliaService
{
    public function __construct(
        private BuscaIndexService $index,
        private CategoriaService $categorias,
        private AuditoriaService $auditoria,
    ) {}

    /**
     * Sincroniza os termos do item (pivot não dispara eventos do model) e
     * atualiza índice de busca, cache de opções e auditoria.
     *
     * @param  list<int>  $termoIds
     */
    public function sincronizarCategorias(Familia $familia, array $termoIds): void
    {
        $antes    = $familia->categorias()->pluck('nome', 'categorias.id');
        $mudancas = $familia->categorias()->sync(array_values(array_unique(array_map('intval', $termoIds))));
        $familia->unsetRelation('categorias');

        $this->index->reindexar($familia);
        $this->categorias->limparCache();

        if ($mudancas['attached'] || $mudancas['detached']) {
            $depois = $familia->categorias()->pluck('nome', 'categorias.id');

            $this->auditoria->registrar('categorias', $familia, 'Alterou classificação de '.$this->auditoria->rotulo($familia), [
                'classificacao' => ['de' => $antes->sort()->implode(', '), 'para' => $depois->sort()->implode(', ')],
            ]);
        }
    }
}
