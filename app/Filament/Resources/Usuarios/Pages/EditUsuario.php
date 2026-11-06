<?php

namespace App\Filament\Resources\Usuarios\Pages;

use App\Filament\Actions\ContaActions;
use App\Filament\Resources\Usuarios\UsuarioResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUsuario extends EditRecord
{
    protected static string $resource = UsuarioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ContaActions::alterarPlano()->after(fn () => $this->refreshFormData(['plano_id'])),
            ActionGroup::make([
                ContaActions::enviarLinkSenha(),
                ContaActions::definirSenha(),
                ContaActions::bloquear(),
                ContaActions::desbloquear(),
                DeleteAction::make()->visible(fn () => auth()->user()->isAdministrador()),
            ])->label('Conta')->button()->color('gray'),
        ];
    }
}
