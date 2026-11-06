<?php

namespace Database\Seeders;

use App\Models\Plano;
use Illuminate\Database\Seeder;

class PlanoSeeder extends Seeder
{
    public function run(): void
    {
        $planos = [
            ['nome' => 'Grátis',  'slug' => 'gratis',  'wp_role' => 'usurio_grtis',      'pago' => false, 'ordem' => 1],
            ['nome' => 'Basic',   'slug' => 'basic',   'wp_role' => 'reviteiroa_basic',  'pago' => true,  'ordem' => 2],
            ['nome' => 'Plus',    'slug' => 'plus',    'wp_role' => 'reviteiro_plus',    'pago' => true,  'ordem' => 3],
            ['nome' => 'Premium', 'slug' => 'premium', 'wp_role' => 'reviteiro_premium', 'pago' => true,  'ordem' => 4],
        ];

        foreach ($planos as $plano) {
            Plano::updateOrCreate(
                ['slug' => $plano['slug']],
                $plano + ['checkout_url' => config("reviteiros.checkout.{$plano['slug']}")],
            );
        }
    }
}
