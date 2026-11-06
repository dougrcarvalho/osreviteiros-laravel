<?php

namespace App\Filament\Resources\Familias\Pages;

use App\Filament\Resources\Familias\FamiliaFormProcessor;
use App\Filament\Resources\Familias\FamiliaResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateFamilia extends CreateRecord
{
    protected static string $resource = FamiliaResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $processor = app(FamiliaFormProcessor::class);
        [$dados, $extras] = $processor->separar($data);

        return DB::transaction(function () use ($dados, $extras, $processor) {
            $familia = static::getModel()::create($dados);
            $processor->aplicar($familia, $extras);

            return $familia;
        });
    }
}
