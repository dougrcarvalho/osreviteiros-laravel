<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum StatusConta: string implements HasColor, HasIcon, HasLabel
{
    case Ativo = 'ativo';
    case Bloqueado = 'bloqueado';

    public function getLabel(): string
    {
        return match ($this) {
            self::Ativo     => 'Ativa',
            self::Bloqueado => 'Bloqueada',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Ativo     => 'success',
            self::Bloqueado => 'danger',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Ativo     => 'heroicon-m-check-circle',
            self::Bloqueado => 'heroicon-m-no-symbol',
        };
    }
}
