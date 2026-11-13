<?php

namespace App\Services;

use App\Models\Familia;
use App\Models\User;

/** Favoritos do usuário (tabela `favoritos`: user_id + familia_id, único por par). */
class FavoritoService
{
    /** @return bool true = ficou favorito; false = foi removido */
    public function alternar(User $user, Familia $familia): bool
    {
        $resultado = $user->favoritos()->toggle($familia->id);

        return ! empty($resultado['attached']);
    }

    /**
     * Quais destes itens o usuário favoritou (para pintar os corações de uma listagem).
     *
     * @param  iterable<int, int>  $ids
     * @return array<int, true> id => true
     */
    public function dentre(?User $user, iterable $ids): array
    {
        $ids = collect($ids)->all();

        if (! $user || ! $ids) {
            return [];
        }

        return $user->favoritos()->whereIn('familias.id', $ids)->pluck('familias.id')
            ->mapWithKeys(fn ($id) => [(int) $id => true])->all();
    }
}
