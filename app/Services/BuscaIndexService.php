<?php

namespace App\Services;

use App\Models\Familia;

/**
 * Mantém a coluna desnormalizada `familias.busca_texto`, que permite
 * encontrar uma família por qualquer termo dela ou de qualquer nível
 * de categoria (incluindo a marca/fabricante).
 */
class BuscaIndexService
{
    public function textoPara(Familia $familia): string
    {
        $familia->loadMissing(['categorias', 'fabricante']);

        $partes = [
            $familia->nome,
            str_replace('-', ' ', $familia->slug),
            $familia->tipo->getLabel().' '.$familia->tipo->singular(),
            strip_tags((string) $familia->descricao),
            $familia->fabricante?->nome,
        ];

        foreach ($familia->categorias as $categoria) {
            $partes[] = $categoria->nome;
        }

        $texto = preg_replace('/\s+/u', ' ', implode(' ', array_filter($partes)));

        return trim(mb_substr($texto, 0, 60000));
    }

    public function reindexar(Familia $familia): void
    {
        // Update direto: não dispara eventos do model (evita loop no observer)
        Familia::whereKey($familia->getKey())->update(['busca_texto' => $this->textoPara($familia)]);
    }

    /** @param  \Illuminate\Database\Eloquent\Builder<Familia>|null  $query */
    public function reindexarTodas($query = null): int
    {
        $total = 0;

        ($query ?? Familia::query())
            ->with(['categorias', 'fabricante'])
            ->chunkById(200, function ($familias) use (&$total) {
                foreach ($familias as $familia) {
                    $this->reindexar($familia);
                    $total++;
                }
            });

        return $total;
    }
}
