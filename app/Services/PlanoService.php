<?php

namespace App\Services;

use App\Models\Plano;
use Illuminate\Support\Collection;

class PlanoService
{
    /** @return Collection<int, Plano> */
    public function todos(): Collection
    {
        return Plano::ordenados()->get();
    }

    /** @return Collection<int, Plano> */
    public function pagos(): Collection
    {
        return Plano::ordenados()->where('pago', true)->get();
    }

    public function gratis(): Plano
    {
        return Plano::where('slug', Plano::GRATIS)->firstOrFail();
    }

    public function porSlug(?string $slug): ?Plano
    {
        return $slug ? Plano::where('slug', $slug)->first() : null;
    }

    public function porWpRole(string $role): ?Plano
    {
        return Plano::where('wp_role', $role)->first();
    }
}
