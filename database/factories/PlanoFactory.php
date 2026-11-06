<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<\App\Models\Plano> */
class PlanoFactory extends Factory
{
    public function definition(): array
    {
        $nome = fake()->unique()->word();

        return ['nome' => ucfirst($nome), 'slug' => Str::slug($nome), 'pago' => true, 'ordem' => 0];
    }
}
