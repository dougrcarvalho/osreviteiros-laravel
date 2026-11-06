<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Usuarios\UsuarioResource;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class UltimosCadastros extends TableWidget
{
    protected static ?int $sort = 5;

    protected static ?string $heading = 'Últimos cadastros';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => User::clientes()->with('plano')->latest())
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5)
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->description(fn (User $record) => $record->email)
                    ->url(fn (User $record) => UsuarioResource::getUrl('edit', ['record' => $record])),
                TextColumn::make('plano.nome')->label('Plano')->badge()->color(fn (User $record) => $record->hasPlano() ? 'warning' : 'gray'),
                TextColumn::make('created_at')->label('Quando')->since(),
            ]);
    }
}
