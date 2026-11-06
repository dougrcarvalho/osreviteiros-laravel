<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** Perfil de funcionário. Cliente/aluno = funcao null. */
enum Funcao: string implements HasColor, HasLabel
{
    case Administrador = 'administrador';
    case Editor = 'editor';

    public function getLabel(): string
    {
        return match ($this) {
            self::Administrador => 'Administrador',
            self::Editor        => 'Editor',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Administrador => 'danger',
            self::Editor        => 'info',
        };
    }
}
