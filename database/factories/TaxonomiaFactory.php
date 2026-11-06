<?php

namespace Database\Factories;

use App\Enums\FonteTaxonomia;
use App\Enums\TipoItem;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<\App\Models\Taxonomia> */
class TaxonomiaFactory extends Factory
{
    public function definition(): array
    {
        $nome = fake()->unique()->word();

        return [
            'tipo_item'   => TipoItem::Familia,
            'slug'        => Str::slug($nome),
            'nome'        => ucfirst($nome),
            'nome_plural' => ucfirst($nome).'s',
            'fonte'       => FonteTaxonomia::Termos,
            'ordem'       => 10,
        ];
    }

    public function para(TipoItem $tipo): static
    {
        return $this->state(['tipo_item' => $tipo]);
    }
}
