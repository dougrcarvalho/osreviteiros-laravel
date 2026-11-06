<?php

namespace App\Filament\Resources\Usuarios\RelationManagers;

use App\Filament\Resources\Familias\FamiliaResource;
use App\Models\Download;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DownloadsRelationManager extends RelationManager
{
    protected static string $relationship = 'downloads';

    protected static ?string $title = 'Downloads';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Data')->dateTime('d/m/Y H:i'),
                TextColumn::make('familia.nome')
                    ->label('Item')
                    ->url(fn (Download $record) => $record->familia ? FamiliaResource::getUrl('edit', ['record' => $record->familia]) : null),
            ]);
    }
}
