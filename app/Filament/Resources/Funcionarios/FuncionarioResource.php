<?php

namespace App\Filament\Resources\Funcionarios;

use App\Enums\Funcao;
use App\Filament\Actions\ContaActions;
use App\Filament\Concerns\SomenteAdministrador;
use App\Filament\Resources\Funcionarios\Pages\ManageFuncionarios;
use App\Models\User;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Password;
use UnitEnum;

class FuncionarioResource extends Resource
{
    use SomenteAdministrador;

    protected static ?string $model = User::class;

    // Rótulos em caixa de frase (o padrão do Filament capitaliza cada palavra)
    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $slug = 'funcionarios';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|UnitEnum|null $navigationGroup = 'Administração';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'funcionário';

    protected static ?string $pluralModelLabel = 'Funcionários';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->funcionarios();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('name')->label('Nome')->required()->maxLength(255),
            TextInput::make('email')->label('E-mail')->email()->required()->maxLength(255)->unique(ignoreRecord: true),
            Select::make('funcao')
                ->label('Perfil')
                ->options(Funcao::class)
                ->required()
                ->default(Funcao::Editor)
                // Não deixa o administrador rebaixar a si mesmo
                ->disabled(fn (?User $record) => $record?->is(auth()->user()))
                ->helperText('Editor: catálogo e atendimento. Administrador: também funcionários, planos e auditoria.'),
            TextInput::make('password')
                ->label('Senha')
                ->password()
                ->revealable()
                ->rule(Password::defaults())
                ->required(fn (string $operation) => $operation === 'create')
                ->dehydrated(fn (?string $state) => filled($state))
                ->helperText(fn (string $operation) => $operation === 'edit' ? 'Deixe em branco para manter a atual.' : null),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->label('Nome')->searchable()->description(fn (User $record) => $record->email),
                TextColumn::make('funcao')->label('Perfil')->badge(),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('last_login_at')->label('Último login')->since()->placeholder('Nunca'),
                TextColumn::make('created_at')->label('Desde')->date('d/m/Y'),
            ])
            ->recordActions([
                EditAction::make(),
                ActionGroup::make([
                    ContaActions::enviarLinkSenha(),
                    ContaActions::bloquear(),
                    ContaActions::desbloquear(),
                    DeleteAction::make()->hidden(fn (User $record) => $record->is(auth()->user())),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageFuncionarios::route('/'),
        ];
    }
}
