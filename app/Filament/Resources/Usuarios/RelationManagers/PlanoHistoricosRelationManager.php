<?php

namespace App\Filament\Resources\Usuarios\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PlanoHistoricosRelationManager extends RelationManager
{
    protected static string $relationship = 'planoHistoricos';

    protected static ?string $title = 'Histórico de planos';

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
                TextColumn::make('planoAnterior.nome')->label('De')->placeholder('—'),
                TextColumn::make('planoNovo.nome')->label('Para')->badge(),
                TextColumn::make('origem')->label('Origem')->badge(),
                TextColumn::make('autor.name')->label('Por')->placeholder('Sistema'),
                TextColumn::make('referencia')->label('Transação')->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('observacao')->label('Observação')->wrap()->placeholder('—'),
            ]);
    }
}
