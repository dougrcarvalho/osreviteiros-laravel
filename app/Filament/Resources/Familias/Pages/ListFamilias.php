<?php

namespace App\Filament\Resources\Familias\Pages;

use App\Filament\Resources\Familias\FamiliaResource;
use App\Enums\TipoItem;
use App\Models\Familia;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListFamilias extends ListRecords
{
    protected static string $resource = FamiliaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Novo item'),
        ];
    }

    public function getTabs(): array
    {
        $tabs = ['todos' => Tab::make('Todos')];

        foreach (TipoItem::cases() as $tipo) {
            $tabs[$tipo->value] = Tab::make($tipo->getLabel())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('tipo', $tipo->value))
                ->badge(fn () => Familia::where('tipo', $tipo->value)->count());
        }

        return $tabs;
    }
}
