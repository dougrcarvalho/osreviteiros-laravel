<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Familias\FamiliaResource;
use App\Models\Familia;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class MaisBaixadas extends TableWidget
{
    protected static ?int $sort = 4;

    protected static ?string $heading = 'Mais baixadas (30 dias)';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => Familia::query()
                ->withCount(['downloads as baixados' => fn ($q) => $q->where('created_at', '>=', now()->subDays(30))])
                ->having('baixados', '>', 0)
                ->orderByDesc('baixados'))
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5)
            ->columns([
                ImageColumn::make('imagem_url')->label('')->square()->imageSize(36),
                TextColumn::make('nome')->label('Item')->url(fn (Familia $record) => FamiliaResource::getUrl('edit', ['record' => $record])),
                TextColumn::make('baixados')->label('Downloads')->numeric(),
            ]);
    }
}
