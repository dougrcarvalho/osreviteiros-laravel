<?php

namespace App\Filament\Resources\Taxonomias;

use App\Enums\FonteTaxonomia;
use App\Enums\TipoItem;
use App\Filament\Concerns\SomenteAdministrador;
use App\Filament\Resources\Taxonomias\Pages\ManageTaxonomias;
use App\Models\Taxonomia;
use App\Services\TaxonomiaService;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;
use UnitEnum;

/**
 * Formas de classificar cada tipo de item. É aqui que se cria, por exemplo,
 * a taxonomia "Cor" para Famílias: ela passa a aparecer no formulário do item,
 * nos filtros do site (?cor=azul), na busca e na API sem alterar código.
 */
class TaxonomiaResource extends Resource
{
    use SomenteAdministrador;

    protected static ?string $model = Taxonomia::class;

    // Rótulos em caixa de frase (o padrão do Filament capitaliza cada palavra)
    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|UnitEnum|null $navigationGroup = 'Administração';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'taxonomia';

    protected static ?string $pluralModelLabel = 'Taxonomias (classificações)';

    protected static ?string $navigationLabel = 'Taxonomias';

    protected static ?string $recordTitleAttribute = 'nome';

    private static function fonteDe(Get $get): ?FonteTaxonomia
    {
        $fonte = $get('fonte');

        return $fonte instanceof FonteTaxonomia ? $fonte : FonteTaxonomia::tryFrom((string) $fonte);
    }

    private static function tipoDe(Get $get): ?TipoItem
    {
        $tipo = $get('tipo_item');

        return $tipo instanceof TipoItem ? $tipo : TipoItem::tryFrom((string) $tipo);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Select::make('tipo_item')
                ->label('Biblioteca')
                ->options(TipoItem::opcoes())
                ->required()
                ->live()
                ->disabledOn('edit')
                ->afterStateUpdated(fn (Set $set) => $set('pai_id', null)),
            Select::make('fonte')
                ->label('Tipo de opções')
                ->options(FonteTaxonomia::class)
                ->default(FonteTaxonomia::Termos)
                ->required()
                ->live()
                ->disabledOn('edit')
                ->afterStateUpdated(fn (Set $set, Get $get) => self::fonteDe($get) === FonteTaxonomia::Fabricante
                    ? $set('slug', Taxonomia::SLUG_MARCA)
                    : null)
                ->helperText('"Termos": lista própria (categoria, cor…). "Marcas": usa o cadastro de fabricantes, compartilhado entre bibliotecas.'),

            TextInput::make('nome')
                ->label('Nome (singular)')
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(function (Get $get, Set $set, ?string $state, string $operation) {
                    if ($operation === 'create' && blank($get('slug')) && self::fonteDe($get) !== FonteTaxonomia::Fabricante) {
                        $set('slug', Str::slug((string) $state));
                    }
                })
                ->placeholder('Cor'),
            TextInput::make('nome_plural')->label('Nome (plural)')->required()->maxLength(255)->placeholder('Cores'),

            TextInput::make('slug')
                ->label('Slug (parâmetro da URL)')
                ->required()
                ->maxLength(50)
                ->regex('/^[a-z0-9_-]+$/')
                ->readOnly(fn (Get $get) => self::fonteDe($get) === FonteTaxonomia::Fabricante)
                ->notIn(fn (Get $get) => [
                    ...Taxonomia::RESERVADOS,
                    ...(self::fonteDe($get) === FonteTaxonomia::Fabricante ? [] : [Taxonomia::SLUG_MARCA]),
                ])
                ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule, Get $get) => $rule->where('tipo_item', self::tipoDe($get)?->value))
                ->helperText('Vira o filtro da URL: /familias?cor=azul. Alterar depois quebra links já divulgados.'),
            Select::make('pai_id')
                ->label('Aparece depois de…')
                ->options(function (Get $get, ?Taxonomia $record) {
                    $tipo = self::tipoDe($get);

                    if (! $tipo) {
                        return [];
                    }

                    $todas    = app(TaxonomiaService::class)->doTipo($tipo);
                    $excluir  = $record ? [$record->id, ...$todas->whereIn('slug', app(TaxonomiaService::class)->descendentes($todas, $record))->pluck('id')->all()] : [];

                    return $todas->reject(fn (Taxonomia $t) => in_array($t->id, $excluir, true))->pluck('nome', 'id')->all();
                })
                ->placeholder('— sempre visível —')
                ->helperText('Subcategoria só aparece após escolher a categoria. Deixe vazio para uma classificação independente (ex.: cor).'),

            TextInput::make('ordem')->label('Ordem de exibição')->numeric()->required()->default(0),
            Toggle::make('ativa')->label('Ativa')->default(true)->inline(false)->helperText('Desativada, some do site e do formulário (os dados ficam guardados).'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('pai')->withCount('termos'))
            ->defaultSort(fn (Builder $query) => $query->orderBy('tipo_item')->orderBy('ordem'))
            ->paginated(false)
            ->columns([
                TextColumn::make('tipo_item')->label('Biblioteca')->badge(),
                TextColumn::make('nome')->label('Nome')->weight('semibold')->description(fn (Taxonomia $record) => $record->nome_plural),
                TextColumn::make('slug')->label('Parâmetro')->prefix('?')->copyable()->color('gray'),
                TextColumn::make('pai.nome')->label('Depois de')->placeholder('—'),
                TextColumn::make('fonte')->label('Opções')->badge()->color('gray'),
                TextColumn::make('termos_count')->label('Termos')->numeric()->placeholder('—')
                    ->state(fn (Taxonomia $record) => $record->usaFabricante() ? null : $record->termos_count),
                TextColumn::make('ordem')->label('Ordem'),
                ToggleColumn::make('ativa')->label('Ativa'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->modalDescription(fn (Taxonomia $record) => $record->usaFabricante()
                        ? 'A classificação sai do site e do formulário. Os fabricantes não são excluídos.'
                        : "Os {$record->termos_count} termo(s) desta classificação serão excluídos e removidos dos itens. Os itens não são excluídos."),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageTaxonomias::route('/'),
        ];
    }
}
