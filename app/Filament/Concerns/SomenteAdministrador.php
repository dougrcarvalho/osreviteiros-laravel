<?php

namespace App\Filament\Concerns;

/** Recurso visível apenas para o perfil Administrador */
trait SomenteAdministrador
{
    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->isAdministrador();
    }
}
