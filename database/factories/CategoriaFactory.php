<?php

namespace Database\Factories;

use App\Models\Taxonomia;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<\App\Models\Categoria> */
class CategoriaFactory extends Factory
{
    public function definition(): array
    {
        $nome = fake()->unique()->words(2, true);

        return [
            'taxonomia_id' => Taxonomia::factory(),
            'nome'         => ucfirst($nome),
            'slug'         => Str::slug($nome),
        ];
    }

    public function daTaxonomia(Taxonomia $taxonomia): static
    {
        return $this->state(['taxonomia_id' => $taxonomia->id]);
    }
}
