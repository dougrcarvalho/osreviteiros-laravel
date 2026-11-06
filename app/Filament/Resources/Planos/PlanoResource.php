<?php

namespace App\Filament\Resources\Planos;

use App\Filament\Concerns\SomenteAdministrador;
use App\Filament\Resources\Planos\Pages\ManagePlanos;
use App\Filament\Resources\Usuarios\UsuarioResource;
use App\Models\Plano;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/** Os 4 planos são fixos (slugs usados no código); aqui só se editam nome, checkout e ordem */
class PlanoResource extends Resource
{
    use SomenteAdministrador;

    protected static ?string $model = Plano::class;

    // Rótulos em caixa de frase (o padrão do Filament capitaliza cada palavra)
    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static string|UnitEnum|null $navigationGroup = 'Administração';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'plano';

    protected static ?string $pluralModelLabel = 'Planos';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('nome')->label('Nome')->required()->maxLength(255),
            TextInput::make('ordem')->label('Ordem')->numeric()->required(),
            TextInput::make('checkout_url')
                ->label('Link de checkout (Hotmart / Eduzz)')
                ->url()
                ->maxLength(255)
                ->columnSpanFull(),
            Toggle::make('pago')->label('Plano pago (libera todas as famílias)')->disabled(),
            TextInput::make('wp_role')->label('Role no WordPress')->disabled(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('users'))
            ->defaultSort('ordem')
            ->paginated(false)
            ->columns([
                TextColumn::make('nome')->label('Plano'),
                IconColumn::make('pago')->label('Pago')->boolean(),
                TextColumn::make('users_count')
                    ->label('Usuários')
                    ->numeric()
                    ->url(fn (Plano $record) => UsuarioResource::getUrl('index', ['filters' => ['plano_id' => ['values' => [$record->id]]]])),
                TextColumn::make('checkout_url')->label('Checkout')->limit(50)->placeholder('Não configurado')->color('gray'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePlanos::route('/'),
        ];
    }
}
