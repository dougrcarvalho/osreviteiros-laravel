<?php

namespace App\Filament\Resources\Usuarios;

use App\Enums\StatusConta;
use App\Filament\Actions\ContaActions;
use App\Filament\Resources\Usuarios\Pages\CreateUsuario;
use App\Filament\Resources\Usuarios\Pages\EditUsuario;
use App\Filament\Resources\Usuarios\Pages\ListUsuarios;
use App\Filament\Resources\Usuarios\RelationManagers\DownloadsRelationManager;
use App\Filament\Resources\Usuarios\RelationManagers\PlanoHistoricosRelationManager;
use App\Models\Plano;
use App\Models\User;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use UnitEnum;

/** Clientes / alunos (users sem funcao) */
class UsuarioResource extends Resource
{
    protected static ?string $model = User::class;

    // Rótulos em caixa de frase (o padrão do Filament capitaliza cada palavra)
    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $slug = 'usuarios';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Assinantes';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'usuário';

    protected static ?string $pluralModelLabel = 'Usuários / alunos';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->clientes();
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email'];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Section::make('Dados')
                ->columnSpan(2)
                ->columns(2)
                ->schema([
                    TextInput::make('name')->label('Nome')->required()->maxLength(255),
                    TextInput::make('email')->label('E-mail')->email()->required()->maxLength(255)->unique(ignoreRecord: true),

                    // Somente na criação; depois, use "Alterar plano" (gera histórico)
                    Select::make('plano_id')
                        ->label('Plano')
                        ->options(fn () => Plano::ordenados()->pluck('nome', 'id')->all())
                        ->default(fn () => Plano::where('slug', Plano::GRATIS)->value('id'))
                        ->required()
                        ->visibleOn('create'),
                    TextInput::make('password')
                        ->label('Senha')
                        ->password()
                        ->revealable()
                        ->helperText('Deixe em branco para enviar um link para o usuário criar a senha.')
                        ->visibleOn('create'),
                ]),
            Section::make('Conta')
                ->columnSpan(1)
                ->visibleOn('edit')
                ->schema([
                    TextEntry::make('plano.nome')
                        ->label('Plano atual')
                        ->badge()
                        ->color(fn (User $record) => $record->hasPlano() ? 'warning' : 'gray')
                        ->placeholder('Grátis (sem plano)'),
                    TextEntry::make('status')->label('Status')->badge(),
                    TextEntry::make('bloqueio_motivo')
                        ->label('Motivo do bloqueio')
                        ->visible(fn (User $record) => $record->isBloqueado()),
                    Grid::make(2)->schema([
                        TextEntry::make('created_at')->label('Cadastro')->dateTime('d/m/Y H:i'),
                        TextEntry::make('last_login_at')->label('Último login')->since()->placeholder('Nunca'),
                        TextEntry::make('last_login_ip')->label('IP do último login')->placeholder('—'),
                        TextEntry::make('downloads_count')
                            ->label('Downloads')
                            ->state(fn (User $record) => $record->downloads()->count()),
                    ]),
                    TextEntry::make('wp_user_id')
                        ->label('Importado do WordPress')
                        ->formatStateUsing(fn ($state) => "Sim (ID {$state})")
                        ->placeholder('Não'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('plano')->withCount('downloads'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable()
                    ->description(fn (User $record) => $record->email),
                TextColumn::make('email')->label('E-mail')->searchable()->hidden(),
                TextColumn::make('plano.nome')
                    ->label('Plano')
                    ->badge()
                    ->color(fn (User $record) => $record->hasPlano() ? 'warning' : 'gray')
                    ->placeholder('—'),
                TextColumn::make('status')->label('Status')->badge()->sortable(),
                TextColumn::make('created_at')->label('Cadastro')->date('d/m/Y')->sortable(),
                TextColumn::make('last_login_at')
                    ->label('Último login')
                    ->since()
                    ->dateTimeTooltip('d/m/Y H:i')
                    ->placeholder('Nunca')
                    ->sortable(),
                TextColumn::make('downloads_count')->label('Downloads')->numeric()->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('plano_id')
                    ->label('Plano')
                    ->multiple()
                    ->options(fn () => Plano::ordenados()->pluck('nome', 'id')->all()),
                TernaryFilter::make('pagante')
                    ->label('Assinante pago')
                    ->queries(
                        true: fn (Builder $query) => $query->whereHas('plano', fn ($p) => $p->where('pago', true)),
                        false: fn (Builder $query) => $query->where(fn ($q) => $q->whereNull('plano_id')->orWhereHas('plano', fn ($p) => $p->where('pago', false))),
                    ),
                SelectFilter::make('status')->label('Status')->options(StatusConta::class),
                self::filtroPeriodo('cadastro', 'created_at', 'Cadastro'),
                self::filtroPeriodo('ultimo_login', 'last_login_at', 'Último login'),
                TernaryFilter::make('nunca_logou')
                    ->label('Login')
                    ->trueLabel('Nunca fez login')
                    ->falseLabel('Já fez login')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNull('last_login_at'),
                        false: fn (Builder $query) => $query->whereNotNull('last_login_at'),
                    ),
                TernaryFilter::make('wp_user_id')
                    ->label('Origem')
                    ->trueLabel('Importados do WordPress')
                    ->falseLabel('Cadastrados no novo site')
                    ->nullable(),
            ])
            ->filtersFormColumns(2)
            ->recordActions([
                EditAction::make(),
                ActionGroup::make([
                    ContaActions::alterarPlano(),
                    ContaActions::enviarLinkSenha(),
                    ContaActions::definirSenha(),
                    ContaActions::bloquear(),
                    ContaActions::desbloquear(),
                    DeleteAction::make()->visible(fn () => auth()->user()->isAdministrador()),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ContaActions::alterarPlanoEmMassa(),
                    DeleteBulkAction::make()->visible(fn () => auth()->user()->isAdministrador()),
                ]),
            ]);
    }

    private static function filtroPeriodo(string $nome, string $coluna, string $rotulo): Filter
    {
        return Filter::make($nome)
            ->schema([
                DatePicker::make('de')->label("{$rotulo} de"),
                DatePicker::make('ate')->label("{$rotulo} até"),
            ])
            ->columns(2)
            ->query(fn (Builder $query, array $data) => $query
                ->when($data['de'] ?? null, fn ($q, $d) => $q->where($coluna, '>=', Carbon::parse($d)->startOfDay()))
                ->when($data['ate'] ?? null, fn ($q, $d) => $q->where($coluna, '<=', Carbon::parse($d)->endOfDay())))
            ->indicateUsing(function (array $data) use ($rotulo) {
                $partes = array_filter([
                    ($data['de'] ?? null) ? 'de '.Carbon::parse($data['de'])->format('d/m/Y') : null,
                    ($data['ate'] ?? null) ? 'até '.Carbon::parse($data['ate'])->format('d/m/Y') : null,
                ]);

                return $partes ? "{$rotulo} ".implode(' ', $partes) : null;
            });
    }

    public static function getRelations(): array
    {
        return [
            PlanoHistoricosRelationManager::class,
            DownloadsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListUsuarios::route('/'),
            'create' => CreateUsuario::route('/create'),
            'edit'   => EditUsuario::route('/{record}/edit'),
        ];
    }
}
