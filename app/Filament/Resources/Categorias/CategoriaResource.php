<?php

namespace App\Filament\Resources\Categorias;

use App\Filament\Resources\Categorias\Pages\ManageCategorias;
use App\Filament\Support\SeletorCategoria;
use App\Models\Categoria;
use App\Models\Taxonomia;
use App\Services\CategoriaService;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;
use UnitEnum;

/**
 * Termos de todas as taxonomias de lista própria (categoria, subcategoria,
 * cor, tipo de projeto…). Marcas ficam em Fabricantes. As próprias
 * taxonomias são configuradas em Administração › Taxonomias.
 */
class CategoriaResource extends Resource
{
    protected static ?string $model = Categoria::class;

    // Rótulos em caixa de frase (o padrão do Filament capitaliza cada palavra)
    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|UnitEnum|null $navigationGroup = 'Catálogo';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'termo';

    protected static ?string $pluralModelLabel = 'Categorias e termos';

    protected static ?string $navigationLabel = 'Categorias';

    protected static ?string $recordTitleAttribute = 'nome';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('taxonomia');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('taxonomia_id')
                ->label('Classificação')
                ->options(fn () => SeletorCategoria::taxonomiasAgrupadas())
                ->required()
                ->live()
                ->disabledOn('edit')
                ->helperText('Ex.: Famílias › Categoria, Texturas › Cor. Novas classificações em Administração › Taxonomias.'),
            TextInput::make('nome')
                ->label('Nome')
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(function (Get $get, Set $set, ?string $state, string $operation) {
                    $taxonomia = Taxonomia::find($get('taxonomia_id'));

                    if ($operation === 'create' && $taxonomia) {
                        $set('slug', app(CategoriaService::class)->slugUnico($taxonomia, (string) $state));
                    }
                }),
            TextInput::make('slug')
                ->label('Slug (usado na URL dos filtros)')
                ->required()
                ->alphaDash()
                ->maxLength(255)
                ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule, Get $get, ?Categoria $record) => $rule
                    ->where('taxonomia_id', $record?->taxonomia_id ?? $get('taxonomia_id'))),
            TextInput::make('ordem')
                ->label('Ordem de exibição')
                ->numeric()
                ->default(0)
                ->helperText('Menor aparece primeiro; empate = ordem alfabética.'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('familias'))
            ->defaultSort(fn (Builder $query) => $query->orderBy('ordem')->orderBy('nome'))
            ->columns([
                TextColumn::make('nome')->label('Nome')->searchable()->sortable(),
                TextColumn::make('slug')->label('Slug')->searchable()->color('gray')->copyable(),
                TextColumn::make('taxonomia.nome')->label('Classificação')->badge(),
                TextColumn::make('familias_count')->label('Itens')->numeric()->sortable(),
                TextColumn::make('ordem')->label('Ordem')->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('taxonomia_id')
                    ->label('Classificação')
                    ->options(fn () => SeletorCategoria::taxonomiasAgrupadas()),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->modalDescription(fn (Categoria $record) => "O termo será removido de {$record->familias_count} item(ns). Os itens não são excluídos."),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCategorias::route('/'),
        ];
    }
}
