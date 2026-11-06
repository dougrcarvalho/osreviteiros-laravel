<?php

namespace App\Filament\Resources\Fabricantes\Pages;

use App\Filament\Resources\Fabricantes\FabricanteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageFabricantes extends ManageRecords
{
    protected static string $resource = FabricanteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nova marca'),
        ];
    }
}
