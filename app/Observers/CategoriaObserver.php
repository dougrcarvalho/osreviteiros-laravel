<?php

namespace App\Observers;

use App\Models\Categoria;
use App\Services\BuscaIndexService;
use App\Services\CategoriaService;

class CategoriaObserver
{
    public function __construct(
        private BuscaIndexService $index,
        private CategoriaService $categorias,
    ) {}

    public function updated(Categoria $categoria): void
    {
        if ($categoria->wasChanged('nome')) {
            $this->index->reindexarTodas($categoria->familias()->getQuery());
        }

        $this->categorias->limparCache();
    }

    public function deleted(Categoria $categoria): void
    {
        $this->categorias->limparCache();
    }
}
