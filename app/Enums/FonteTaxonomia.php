<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/** De onde vêm as opções de uma taxonomia */
enum FonteTaxonomia: string implements HasLabel
{
    /** Termos próprios (tabela categorias): categoria, cor, tipo de projeto… */
    case Termos = 'termos';

    /** Fabricantes (entidade compartilhada entre os tipos, com logo e página própria) */
    case Fabricante = 'fabricante';

    public function getLabel(): string
    {
        return match ($this) {
            self::Termos     => 'Termos (lista própria)',
            self::Fabricante => 'Marcas / fabricantes',
        };
    }
}
