<?php

namespace App\Providers;

use App\Services\AuditoriaService;
use App\Auth\UserProviderComSenhaWp;
use Illuminate\Support\Facades\Auth;
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
        // Aceita a senha do WordPress dos usuários importados (convertida no 1º login)
        Auth::provider('eloquent-wp', fn ($app, array $config) => new UserProviderComSenhaWp($app['hash'], $config['model']));
    }
}
