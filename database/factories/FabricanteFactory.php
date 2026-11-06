<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<\App\Models\Fabricante> */
class FabricanteFactory extends Factory
{
    public function definition(): array
    {
        $nome = fake()->unique()->company();

        return ['nome' => $nome, 'slug' => Str::slug($nome)];
    }
}
