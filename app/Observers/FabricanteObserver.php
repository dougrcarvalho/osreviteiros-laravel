<?php

namespace App\Observers;

use App\Models\Fabricante;
use App\Services\BuscaIndexService;
use App\Services\CategoriaService;
use Illuminate\Support\Facades\Storage;

/** Fabricante é a fonte das taxonomias "Marca": mudanças refletem na busca e nos filtros */
class FabricanteObserver
{
    public function __construct(
        private BuscaIndexService $index,
        private CategoriaService $categorias,
    ) {}

    public function saving(Fabricante $fabricante): void
    {
        if ($fabricante->isDirty('logo_path')) {
            $disk = config('reviteiros.imagens.disk');

            if ($antigo = $fabricante->getOriginal('logo_path')) {
                Storage::disk($disk)->delete($antigo);
            }

            $fabricante->logo_url = $fabricante->logo_path
                ? Storage::disk($disk)->url($fabricante->logo_path)
                : ($fabricante->getOriginal('logo_path') ? null : $fabricante->logo_url);
        }
    }

    public function saved(Fabricante $fabricante): void
    {
        if ($fabricante->wasChanged('nome')) {
            $this->index->reindexarTodas($fabricante->familias()->getQuery());
        }

        $this->categorias->limparCache();
    }

    public function deleted(Fabricante $fabricante): void
    {
        $this->categorias->limparCache();
    }
}
