<?php

namespace App\Filament\Resources\Familias\Pages;

use App\Filament\Resources\Familias\FamiliaFormProcessor;
use App\Filament\Resources\Familias\FamiliaResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditFamilia extends EditRecord
{
    protected static string $resource = FamiliaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('ver')
                ->label('Ver no site')
                ->icon('heroicon-m-arrow-top-right-on-square')
                ->color('gray')
                ->url(fn () => $this->record->urlPublica(), shouldOpenInNewTab: true),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return app(FamiliaFormProcessor::class)->preencher($this->record, $data);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $processor = app(FamiliaFormProcessor::class);
        [$dados, $extras] = $processor->separar($data);

        DB::transaction(function () use ($record, $dados, $extras, $processor) {
            $record->update($dados);
            $processor->aplicar($record, $extras);
        });

        return $record;
    }
}
