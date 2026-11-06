<?php

namespace App\Filament\Resources\Fabricantes;

use App\Filament\Resources\Fabricantes\Pages\ManageFabricantes;
use App\Models\Fabricante;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use UnitEnum;

/** Marcas (termo nível 4 "selecionar-marca" + dados do fabricante) */
class FabricanteResource extends Resource
{
    protected static ?string $model = Fabricante::class;

    // Rótulos em caixa de frase (o padrão do Filament capitaliza cada palavra)
    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static string|UnitEnum|null $navigationGroup = 'Catálogo';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'marca';

    protected static ?string $pluralModelLabel = 'Marcas / fabricantes';

    protected static ?string $recordTitleAttribute = 'nome';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('nome')
                ->label('Nome')
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(fn (Set $set, ?string $state, string $operation) => $operation === 'create' ? $set('slug', Str::slug((string) $state)) : null),
            TextInput::make('slug')
                ->label('Slug (/fabricantes/… e ?marca=…)')
                ->required()
                ->alphaDash()
                ->maxLength(255)
                ->unique(ignoreRecord: true),
            FileUpload::make('logo_path')
                ->label('Logo')
                ->image()
                ->disk(config('reviteiros.imagens.disk'))
                ->directory('fabricantes')
                ->visibility('public')
                ->maxSize(2048),
            TextInput::make('logo_url')
                ->label('…ou URL do logo')
                ->url()
                ->maxLength(255)
                ->helperText('Usado quando não há logo enviado.'),
            TextInput::make('site_url')->label('Site do fabricante')->url()->maxLength(255),
            Textarea::make('descricao')->label('Descrição')->rows(3)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('familias'))
            ->defaultSort('nome')
            ->columns([
                ImageColumn::make('logo_url')->label('')->imageSize(40)->defaultImageUrl(null),
                TextColumn::make('nome')->label('Nome')->searchable()->sortable(),
                TextColumn::make('slug')->label('Slug')->color('gray')->searchable(),
                TextColumn::make('familias_count')->label('Itens')->numeric()->sortable(),
            ])
            ->recordActions([
                Action::make('ver')
                    ->label('Site')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn (Fabricante $record) => route('fabricantes.show', $record), shouldOpenInNewTab: true),
                EditAction::make(),
                DeleteAction::make()
                    ->modalDescription(fn (Fabricante $record) => "A marca será removida de {$record->familias_count} item(ns). As famílias não são excluídas."),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageFabricantes::route('/'),
        ];
    }
}
