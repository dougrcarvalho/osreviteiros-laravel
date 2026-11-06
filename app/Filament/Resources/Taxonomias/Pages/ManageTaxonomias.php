<?php

namespace App\Filament\Resources\Taxonomias\Pages;

use App\Enums\TipoItem;
use App\Filament\Resources\Taxonomias\TaxonomiaResource;
use App\Models\Taxonomia;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ManageTaxonomias extends ManageRecords
{
    protected static string $resource = TaxonomiaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nova taxonomia'),
        ];
    }

    public function getTabs(): array
    {
        $tabs = ['todas' => Tab::make('Todas')];

        foreach (TipoItem::cases() as $tipo) {
            $tabs[$tipo->value] = Tab::make($tipo->getLabel())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('tipo_item', $tipo->value))
                ->badge(fn () => Taxonomia::where('tipo_item', $tipo->value)->count());
        }

        return $tabs;
    }
}
