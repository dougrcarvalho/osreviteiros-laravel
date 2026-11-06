<?php

namespace App\Filament\Resources\Funcionarios\Pages;

use App\Filament\Resources\Funcionarios\FuncionarioResource;
use App\Models\Plano;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageFuncionarios extends ManageRecords
{
    protected static string $resource = FuncionarioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Novo funcionário')
                ->mutateDataUsing(fn (array $data) => $data + ['plano_id' => Plano::where('slug', Plano::GRATIS)->value('id')]),
        ];
    }
}
