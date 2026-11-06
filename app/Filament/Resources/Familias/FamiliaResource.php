<?php

namespace App\Filament\Resources\Familias;

use App\Filament\Resources\Familias\Pages\CreateFamilia;
use App\Filament\Resources\Familias\Pages\EditFamilia;
use App\Filament\Resources\Familias\Pages\ListFamilias;
use App\Filament\Resources\Familias\Schemas\FamiliaForm;
use App\Filament\Resources\Familias\Tables\FamiliasTable;
use App\Models\Familia;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class FamiliaResource extends Resource
{
    protected static ?string $model = Familia::class;

    // Rótulos em caixa de frase (o padrão do Filament capitaliza cada palavra)
    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static string|UnitEnum|null $navigationGroup = 'Catálogo';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'item do catálogo';

    protected static ?string $pluralModelLabel = 'Famílias, texturas e projetos';

    protected static ?string $navigationLabel = 'Famílias / Texturas / Projetos';

    protected static ?string $recordTitleAttribute = 'nome';

    public static function form(Schema $schema): Schema
    {
        return FamiliaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FamiliasTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListFamilias::route('/'),
            'create' => CreateFamilia::route('/create'),
            'edit'   => EditFamilia::route('/{record}/edit'),
        ];
    }
}
