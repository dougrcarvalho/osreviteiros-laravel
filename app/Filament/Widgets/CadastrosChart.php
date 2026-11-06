<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class CadastrosChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Novos cadastros por mês';

    protected ?string $maxHeight = '260px';

    protected function getData(): array
    {
        $inicio = now()->startOfMonth()->subMonths(11);

        $porMes = User::clientes()
            ->where('created_at', '>=', $inicio)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as mes, COUNT(*) as total")
            ->groupBy('mes')
            ->pluck('total', 'mes');

        $meses = collect(range(0, 11))->map(fn ($i) => $inicio->copy()->addMonths($i));

        return [
            'datasets' => [[
                'label'           => 'Cadastros',
                'data'            => $meses->map(fn (Carbon $m) => (int) ($porMes[$m->format('Y-m')] ?? 0))->all(),
                'borderColor'     => '#1A6FC4',
                'backgroundColor' => 'rgba(26,111,196,.15)',
                'fill'            => true,
                'tension'         => .3,
            ]],
            'labels' => $meses->map(fn (Carbon $m) => $m->translatedFormat('M/y'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
