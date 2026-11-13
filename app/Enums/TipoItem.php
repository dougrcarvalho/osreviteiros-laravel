<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

/**
 * Tipo de item do catálogo (familias.tipo). Cada tipo é uma "biblioteca"
 * do site, com URL própria e suas próprias taxonomias (ver Taxonomia).
 */
enum TipoItem: string implements HasColor, HasIcon, HasLabel
{
    case Familia = 'familia';
    case Textura = 'textura';
    case Projeto = 'projeto';

    /** Rótulo no plural (abas, menus) */
    public function getLabel(): string
    {
        return match ($this) {
            self::Familia => __('Famílias'),
            self::Textura => __('Texturas'),
            self::Projeto => __('Projetos'),
        };
    }

    public function singular(): string
    {
        return match ($this) {
            self::Familia => __('Família'),
            self::Textura => __('Textura'),
            self::Projeto => __('Projeto'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Familia => 'primary',
            self::Textura => 'warning',
            self::Projeto => 'success',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Familia => 'heroicon-m-cube',
            self::Textura => 'heroicon-m-swatch',
            self::Projeto => 'heroicon-m-building-office-2',
        };
    }

    /** Primeiro segmento da URL pública (SEO; /familias-revit = URL das famílias no WordPress) */
    public function segmento(): string
    {
        return match ($this) {
            self::Familia => 'familias-revit',
            self::Textura => 'texturas-revit-bim',
            self::Projeto => 'projetos-prontos-revit-bim',
        };
    }

    /** URL usada antes (/familias…): redireciona 301 para segmento() */
    public function segmentoAntigo(): string
    {
        return match ($this) {
            self::Familia => 'familias',
            self::Textura => 'texturas',
            self::Projeto => 'projetos',
        };
    }

    /** Prefixo estável dos nomes de rota (independe da URL): familias.index, texturas.pagina… */
    public function chaveRota(): string
    {
        return $this->segmentoAntigo();
    }

    public function rotaIndex(): string
    {
        return $this->chaveRota().'.index';
    }

    /** /{segmento}/page/{pagina}/ */
    public function rotaPagina(): string
    {
        return $this->chaveRota().'.pagina';
    }

    public function rotaShow(): string
    {
        return $this->chaveRota().'.show';
    }

    /** @return array<string, string> valor => rótulo no plural */
    public static function opcoes(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $t) => [$t->value => $t->getLabel()])->all();
    }
}
