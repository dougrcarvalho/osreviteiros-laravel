<?php

namespace App\Filament\Widgets;

use App\Enums\TipoItem;
use App\Models\Download;
use App\Models\Familia;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class ResumoStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $clientes   = User::clientes();
        $total      = (clone $clientes)->count();
        $novos30    = (clone $clientes)->where('created_at', '>=', now()->subDays(30))->count();
        $novosAntes = (clone $clientes)->whereBetween('created_at', [now()->subDays(60), now()->subDays(30)])->count();
        $pagantes   = (clone $clientes)->whereHas('plano', fn (Builder $q) => $q->where('pago', true))->count();
        $downloads  = Download::where('created_at', '>=', now()->subDays(30))->count();

        $porDia = fn (Builder $q, string $col = 'created_at') => collect(range(13, 0))
            ->map(fn ($d) => (clone $q)->whereDate($col, now()->subDays($d))->count())
            ->all();

        return [
            Stat::make('Usuários', number_format($total, 0, ',', '.'))
                ->description("{$novos30} novos em 30 dias".($novosAntes ? sprintf(' (%+d%%)', round(($novos30 - $novosAntes) / $novosAntes * 100)) : ''))
                ->descriptionIcon($novos30 >= $novosAntes ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($novos30 >= $novosAntes ? 'success' : 'danger')
                ->chart($porDia(User::clientes())),
            Stat::make('Assinantes pagos', number_format($pagantes, 0, ',', '.'))
                ->description($total ? str_replace('.', ',', (string) round($pagantes / $total * 100, 1)).'% dos usuários' : '—')
                ->color('warning'),
            Stat::make('Downloads (30 dias)', number_format($downloads, 0, ',', '.'))
                ->chart($porDia(Download::query()))
                ->color('primary'),
            Stat::make('Itens publicados', number_format(Familia::ativo()->count(), 0, ',', '.'))
                ->description(collect(TipoItem::cases())
                    ->map(fn (TipoItem $tipo) => Familia::ativo()->tipo($tipo)->count().' '.mb_strtolower($tipo->getLabel()))
                    ->implode(' · ')),
        ];
    }
}
