<?php

namespace App\Auth;

use App\Support\WpPasswordHasher;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;

/**
 * Provider Eloquent que também aceita a senha antiga do WordPress (users.wp_password_hash).
 * Ao acertar, grava a senha em bcrypt e apaga o hash legado: a migração é transparente.
 * Vale para o site e para o painel (mesmo guard `web`).
 */
class UserProviderComSenhaWp extends EloquentUserProvider
{
    public function validateCredentials(UserContract $user, array $credentials): bool
    {
        if (parent::validateCredentials($user, $credentials)) {
            return true;
        }

        $senha = $credentials['password'] ?? '';
        $legado = $user->getAttribute('wp_password_hash');

        if (! is_string($senha) || ! WpPasswordHasher::confere($senha, $legado)) {
            return false;
        }

        $user->forceFill(['password' => $senha, 'wp_password_hash' => null])->save();

        return true;
    }
}
