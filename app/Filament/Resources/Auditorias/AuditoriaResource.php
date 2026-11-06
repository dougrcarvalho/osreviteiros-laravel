<?php

namespace App\Filament\Resources\Auditorias;

use App\Filament\Concerns\SomenteAdministrador;
use App\Filament\Resources\Auditorias\Pages\ListAuditorias;
use App\Models\Auditoria;
use App\Models\User;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use UnitEnum;

/** Log de auditoria — somente leitura */
class AuditoriaResource extends Resource
{
    use SomenteAdministrador;

    protected static ?string $model = Auditoria::class;

    // Rótulos em caixa de frase (o padrão do Filament capitaliza cada palavra)
    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Administração';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'registro';

    protected static ?string $pluralModelLabel = 'Auditoria';

    public const TIPOS = [
        'App\\Models\\Familia'    => 'Família',
        'App\\Models\\Categoria'  => 'Termo',
        'App\\Models\\Taxonomia'  => 'Taxonomia',
        'App\\Models\\Fabricante' => 'Marca',
        'App\\Models\\User'       => 'Usuário',
        'App\\Models\\Plano'      => 'Plano',
    ];

    public static function infolist(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextEntry::make('created_at')->label('Data')->dateTime('d/m/Y H:i:s'),
            TextEntry::make('autor.name')->label('Funcionário')->placeholder('Sistema'),
            TextEntry::make('evento')->label('Evento')->badge()->formatStateUsing(fn (string $state) => Auditoria::EVENTOS[$state] ?? $state),
            TextEntry::make('ip')->label('IP')->placeholder('—'),
            TextEntry::make('descricao')->label('Descrição')->columnSpanFull(),
            ViewEntry::make('alteracoes')->label('Alterações')->view('filament.auditoria.alteracoes')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('autor'))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Data')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('autor.name')->label('Funcionário')->placeholder('Sistema')->searchable(),
                TextColumn::make('evento')
                    ->label('Evento')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => Auditoria::EVENTOS[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        'excluido', 'conta_bloqueada'           => 'danger',
                        'criado', 'conta_ativada'               => 'success',
                        'plano_alterado', 'senha_alterada'      => 'warning',
                        default                                 => 'gray',
                    }),
                TextColumn::make('descricao')->label('Descrição')->wrap()->searchable(),
                TextColumn::make('ip')->label('IP')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('autor_id')
                    ->label('Funcionário')
                    ->options(fn () => User::funcionarios()->orderBy('name')->pluck('name', 'id')->all()),
                SelectFilter::make('evento')->label('Evento')->options(Auditoria::EVENTOS)->multiple(),
                SelectFilter::make('auditavel_type')->label('Tipo de registro')->options(self::TIPOS),
                Filter::make('periodo')
                    ->schema([
                        DatePicker::make('de')->label('De'),
                        DatePicker::make('ate')->label('Até'),
                    ])
                    ->columns(2)
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['de'] ?? null, fn ($q, $d) => $q->where('created_at', '>=', Carbon::parse($d)->startOfDay()))
                        ->when($data['ate'] ?? null, fn ($q, $d) => $q->where('created_at', '<=', Carbon::parse($d)->endOfDay()))),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuditorias::route('/'),
        ];
    }
}
