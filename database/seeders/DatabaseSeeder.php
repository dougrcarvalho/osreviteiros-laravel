<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([PlanoSeeder::class, TaxonomiaSeeder::class]);

        if (app()->isLocal()) {
            $this->call(DemoSeeder::class);
        }
    }
}
