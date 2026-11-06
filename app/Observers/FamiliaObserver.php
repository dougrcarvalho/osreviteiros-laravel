<?php

namespace App\Observers;

use App\Models\Familia;
use App\Services\ArquivoService;
use App\Services\BuscaIndexService;
use Illuminate\Support\Facades\Storage;
use App\Services\CategoriaService;

class FamiliaObserver
{
    public function __construct(
        private BuscaIndexService $index,
        private CategoriaService $categorias,
    ) {}

    public function saved(Familia $familia): void
    {
        if ($familia->wasRecentlyCreated || $familia->wasChanged(['nome', 'slug', 'descricao', 'tipo', 'fabricante_id'])) {
            $this->index->reindexar($familia);
        }

        if ($familia->wasRecentlyCreated || $familia->wasChanged(['ativo', 'tipo', 'gratuita', 'fabricante_id'])) {
            $this->categorias->limparCache();
        }
    }

    public function deleted(Familia $familia): void
    {
        // Arquivos enviados pelo painel saem junto com a família
        app(ArquivoService::class)->apagar($familia);

        if ($familia->imagem_path) {
            Storage::disk(config('reviteiros.imagens.disk'))->delete($familia->imagem_path);
        }

        $this->categorias->limparCache();
    }
}
