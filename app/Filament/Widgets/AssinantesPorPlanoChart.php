<?php

namespace App\Filament\Widgets;

use App\Models\Plano;
use Filament\Widgets\ChartWidget;

class AssinantesPorPlanoChart extends ChartWidget
{
    protected static ?int $sort = 3;

    protected ?string $heading = 'Usuários por plano';

    protected ?string $maxHeight = '260px';

    private const CORES = ['gratis' => '#A0AEC0', 'basic' => '#1A6FC4', 'plus' => '#805AD5', 'premium' => '#C9A227'];

    protected function getData(): array
    {
        $planos = Plano::ordenados()->withCount(['users' => fn ($q) => $q->clientes()])->get();

        return [
            'datasets' => [[
                'data'            => $planos->pluck('users_count')->all(),
                'backgroundColor' => $planos->map(fn (Plano $p) => self::CORES[$p->slug] ?? '#718096')->all(),
            ]],
            'labels' => $planos->pluck('nome')->all(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
