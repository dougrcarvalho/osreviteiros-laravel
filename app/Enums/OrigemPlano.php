<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** De onde veio uma troca de plano (plano_historicos.origem) */
enum OrigemPlano: string implements HasColor, HasLabel
{
    case Cadastro = 'cadastro';
    case Manual = 'manual';
    case Hotmart = 'hotmart';
    case Eduzz = 'eduzz';
    case Importacao = 'importacao';

    public function getLabel(): string
    {
        return match ($this) {
            self::Cadastro   => 'Cadastro no site',
            self::Manual     => 'Alteração manual',
            self::Hotmart    => 'Hotmart',
            self::Eduzz      => 'Eduzz',
            self::Importacao => 'Importação WordPress',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Manual     => 'warning',
            self::Hotmart    => 'danger',
            self::Eduzz      => 'info',
            default          => 'gray',
        };
    }
}
