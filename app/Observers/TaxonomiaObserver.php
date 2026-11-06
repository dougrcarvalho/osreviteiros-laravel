<?php

namespace App\Observers;

use App\Models\Taxonomia;
use App\Services\CategoriaService;

class TaxonomiaObserver
{
    public function __construct(private CategoriaService $categorias) {}

    public function saved(Taxonomia $taxonomia): void
    {
        $this->categorias->limparCache();
    }

    public function deleted(Taxonomia $taxonomia): void
    {
        $this->categorias->limparCache();
    }
}
