<?php

namespace App\Filament\Resources\Familias\Tables;

use App\Enums\TipoItem;
use App\Models\Categoria;
use App\Models\Familia;
use App\Models\Taxonomia;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class FamiliasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['fabricante', 'categorias.taxonomia']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                ImageColumn::make('imagem_url')
                    ->label('')
                    ->square()
                    ->imageSize(48)
                    ->defaultImageUrl(asset('images/logo.png')),
                TextColumn::make('nome')
                    ->label('Nome')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    // Termos das demais taxonomias (sub, subclassificação, tipo, cor…)
                    ->description(fn (Familia $record) => $record->categorias
                        ->reject(fn (Categoria $c) => $c->taxonomia?->slug === 'cat')
                        ->pluck('nome')->implode(' · ')),
                TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge(),
                TextColumn::make('categoria')
                    ->label('Categoria')
                    ->state(fn (Familia $record) => $record->categorias->filter(fn (Categoria $c) => $c->taxonomia?->slug === 'cat')->pluck('nome')->all())
                    ->badge()
                    ->color('gray')
                    ->limitList(2),
                TextColumn::make('fabricante.nome')
                    ->label('Marca')
                    ->placeholder('—')
                    ->toggleable(),
                IconColumn::make('gratuita')
                    ->label('Grátis')
                    ->boolean()
                    ->trueColor('success')
                    ->falseIcon('heroicon-o-lock-closed')
                    ->falseColor('gray'),
                IconColumn::make('arquivo')
                    ->label('Arquivo')
                    ->state(fn (Familia $record) => $record->temArquivoLocal() ? 'local' : ($record->link_download ? 'link' : 'nenhum'))
                    ->icon(fn (string $state) => ['local' => 'heroicon-o-server', 'link' => 'heroicon-o-link', 'nenhum' => 'heroicon-o-exclamation-triangle'][$state])
                    ->color(fn (string $state) => $state === 'nenhum' ? 'danger' : 'gray')
                    ->tooltip(fn (string $state) => ['local' => 'Enviado ao servidor', 'link' => 'Link externo', 'nenhum' => 'Sem arquivo!'][$state]),
                TextColumn::make('downloads_count')
                    ->label('Downloads')
                    ->numeric()
                    ->sortable(),
                ToggleColumn::make('ativo')
                    ->label('Publicado'),
                TextColumn::make('created_at')
                    ->label('Cadastro')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                TernaryFilter::make('ativo')->label('Publicado'),
                TernaryFilter::make('gratuita')->label('Acessar grátis'),
                SelectFilter::make('tipo')->label('Tipo')->options(TipoItem::opcoes()),
                TernaryFilter::make('sem_arquivo')
                    ->label('Arquivo de download')
                    ->trueLabel('Sem arquivo')
                    ->falseLabel('Com arquivo')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNull('link_download'),
                        false: fn (Builder $query) => $query->whereNotNull('link_download'),
                    ),
                SelectFilter::make('termo')
                    ->label('Categoria')
                    ->options(fn () => Taxonomia::deTermos()->whereNull('pai_id')->ordenadas()->with('termos')->get()
                        ->mapWithKeys(fn (Taxonomia $t) => [$t->rotuloCompleto() => $t->termos->sortBy('nome')->pluck('nome', 'id')->all()])->all())
                    ->searchable()
                    ->query(fn (Builder $query, array $data) => $query->when($data['value'], fn ($query, $id) => $query->whereHas('categorias', fn ($c) => $c->whereKey($id)))),
                SelectFilter::make('fabricante_id')
                    ->label('Marca')
                    ->relationship('fabricante', 'nome')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                Action::make('ver')
                    ->label('Site')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn (Familia $record) => $record->urlPublica(), shouldOpenInNewTab: true),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('publicar')
                        ->label('Publicar')
                        ->icon('heroicon-m-eye')
                        ->action(fn (Collection $records) => $records->each->update(['ativo' => true]))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('despublicar')
                        ->label('Despublicar')
                        ->icon('heroicon-m-eye-slash')
                        ->action(fn (Collection $records) => $records->each->update(['ativo' => false]))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
