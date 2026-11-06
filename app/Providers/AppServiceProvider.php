<?php

namespace App\Providers;

use App\Services\AuditoriaService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Guarda o estado de semRegistro() durante a requisição
        $this->app->singleton(AuditoriaService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
