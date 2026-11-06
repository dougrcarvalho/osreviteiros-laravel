<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<\App\Models\Familia> */
class FamiliaFactory extends Factory
{
    public function definition(): array
    {
        $nome = fake()->unique()->words(3, true);

        return [
            'tipo'          => \App\Enums\TipoItem::Familia,
            'nome'          => ucfirst($nome),
            'slug'          => Str::slug($nome),
            'descricao'     => fake()->sentence(),
            'link_download' => 'https://example.com/'.Str::slug($nome).'.rfa',
            'ativo'         => true,
        ];
    }
}
