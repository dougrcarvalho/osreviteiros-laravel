<?php

namespace App\Filament\Resources\Categorias\Pages;

use App\Enums\TipoItem;
use App\Filament\Resources\Categorias\CategoriaResource;
use App\Models\Categoria;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ManageCategorias extends ManageRecords
{
    protected static string $resource = CategoriaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Novo termo'),
        ];
    }

    /** Uma aba por biblioteca */
    public function getTabs(): array
    {
        $tabs = [];

        foreach (TipoItem::cases() as $tipo) {
            $doTipo = fn (Builder $query) => $query->whereHas('taxonomia', fn ($t) => $t->where('tipo_item', $tipo->value));

            $tabs[$tipo->value] = Tab::make($tipo->getLabel())
                ->modifyQueryUsing($doTipo)
                ->badge(fn () => $doTipo(Categoria::query())->count());
        }

        return $tabs;
    }
}
