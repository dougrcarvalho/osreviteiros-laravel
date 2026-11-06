<?php

namespace App\Filament\Support;

use App\Models\Categoria;
use App\Models\Fabricante;
use App\Models\Taxonomia;
use App\Services\CategoriaService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Str;

/**
 * Seletores de classificação do item, gerados a partir das taxonomias do tipo:
 * termos (múltiplos, com criação rápida) ou fabricante (único).
 */
class SeletorCategoria
{
    /** Termos de uma taxonomia → state `termos.{slug}` (lista de ids) */
    public static function termos(Taxonomia $taxonomia): Select
    {
        $preload = Categoria::daTaxonomia($taxonomia)->count() <= 60;

        return Select::make("termos.{$taxonomia->slug}")
            ->label($taxonomia->nome_plural)
            ->multiple()
            ->searchable()
            ->preload($preload)
            ->options(fn () => $preload ? self::query($taxonomia)->pluck('nome', 'id')->all() : [])
            ->getSearchResultsUsing(fn (string $search) => self::query($taxonomia)
                ->where('nome', 'like', '%'.addcslashes($search, '%_\\').'%')
                ->limit(50)
                ->pluck('nome', 'id')
                ->all())
            ->getOptionLabelsUsing(fn (array $values) => Categoria::whereIn('id', $values)->pluck('nome', 'id')->all())
            ->createOptionForm([
                TextInput::make('nome')->label('Nome')->required()->maxLength(255),
            ])
            ->createOptionUsing(fn (array $data) => app(CategoriaService::class)->criar($taxonomia, $data['nome'])->getKey())
            ->createOptionModalHeading("Novo termo: {$taxonomia->nome}");
    }

    /** Fabricante (um por item) → coluna familias.fabricante_id */
    public static function fabricante(Taxonomia $taxonomia): Select
    {
        return Select::make('fabricante_id')
            ->label($taxonomia->nome)
            ->searchable()
            ->preload(Fabricante::count() <= 60)
            ->options(fn () => Fabricante::orderBy('nome')->limit(60)->pluck('nome', 'id')->all())
            ->getSearchResultsUsing(fn (string $search) => Fabricante::where('nome', 'like', '%'.addcslashes($search, '%_\\').'%')
                ->orderBy('nome')->limit(50)->pluck('nome', 'id')->all())
            ->getOptionLabelUsing(fn ($value) => Fabricante::find($value)?->nome)
            ->createOptionForm([
                TextInput::make('nome')->label('Nome')->required()->maxLength(255),
            ])
            ->createOptionUsing(function (array $data) {
                $base = Str::slug($data['nome']) ?: 'marca';
                $slug = $base;

                for ($i = 2; Fabricante::where('slug', $slug)->exists(); $i++) {
                    $slug = "{$base}-{$i}";
                }

                return Fabricante::create(['nome' => $data['nome'], 'slug' => $slug])->getKey();
            })
            ->createOptionModalHeading('Nova marca / fabricante');
    }

    private static function query(Taxonomia $taxonomia)
    {
        return Categoria::daTaxonomia($taxonomia)->ordenadas();
    }

    /**
     * Opções agrupadas "Famílias" => [id => "Categoria"], para escolher uma taxonomia.
     *
     * @return array<string, array<int, string>>
     */
    public static function taxonomiasAgrupadas(bool $somenteTermos = true, bool $somenteRaiz = false): array
    {
        return Taxonomia::query()
            ->when($somenteTermos, fn ($q) => $q->deTermos())
            ->when($somenteRaiz, fn ($q) => $q->whereNull('pai_id'))
            ->ordenadas()
            ->get()
            ->groupBy(fn (Taxonomia $t) => $t->tipo_item->getLabel())
            ->map(fn ($grupo) => $grupo->pluck('nome', 'id')->all())
            ->all();
    }
}
