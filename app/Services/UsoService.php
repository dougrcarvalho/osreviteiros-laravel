<?php

namespace App\Services;

use App\Enums\TipoItem;
use App\Models\Download;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/** Histórico de uso do site por usuário (downloads) para a área do aluno. */
class UsoService
{
    /**
     * @return array{total: int, distintos: int, este_mes: int, ultimo: ?Carbon, por_tipo: array<string, array{total: int, distintos: int}>}
     */
    public function resumo(User $user): array
    {
        $linhas = Download::query()
            ->join('familias', 'familias.id', '=', 'downloads.familia_id')
            ->where('downloads.user_id', $user->id)
            ->groupBy('familias.tipo')
            ->selectRaw('familias.tipo as tipo, count(*) as total, count(distinct downloads.familia_id) as distintos')
            ->get()
            ->keyBy('tipo');

        $porTipo = [];
        foreach (TipoItem::cases() as $tipo) {
            $linha = $linhas->get($tipo->value);
            $porTipo[$tipo->value] = ['total' => (int) ($linha->total ?? 0), 'distintos' => (int) ($linha->distintos ?? 0)];
        }

        $base = Download::where('user_id', $user->id);

        return [
            'total'     => array_sum(array_column($porTipo, 'total')),
            'distintos' => (clone $base)->distinct()->count('familia_id'),
            'este_mes'  => (clone $base)->where('created_at', '>=', now()->startOfMonth())->count(),
            'ultimo'    => (clone $base)->max('created_at') ? Carbon::parse((clone $base)->max('created_at')) : null,
            'por_tipo'  => $porTipo,
        ];
    }

    /** Downloads do usuário (mais recentes primeiro), opcionalmente de um tipo */
    public function downloads(User $user, ?TipoItem $tipo = null): Builder
    {
        return Download::query()
            ->where('downloads.user_id', $user->id)
            ->when($tipo, fn (Builder $q) => $q->whereHas('familia', fn (Builder $f) => $f->where('tipo', $tipo->value)))
            ->with('familia.fabricante')
            ->orderByDesc('downloads.created_at')
            ->orderByDesc('downloads.id');
    }
}
