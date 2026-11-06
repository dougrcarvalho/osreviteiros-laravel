<?php

namespace App\Filament\Resources\Usuarios\Pages;

use App\Enums\OrigemPlano;
use App\Filament\Resources\Usuarios\UsuarioResource;
use App\Models\Plano;
use App\Models\User;
use App\Services\AssinaturaService;
use App\Services\ContaService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateUsuario extends CreateRecord
{
    protected static string $resource = UsuarioResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $enviarLink = blank($data['password'] ?? null);

        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'password' => filled($data['password'] ?? null) ? $data['password'] : Str::random(40),
            ]);

            app(AssinaturaService::class)->alterarPlano($user, Plano::find($data['plano_id']), OrigemPlano::Manual, 'Conta criada pelo painel');

            return $user;
        });

        if ($enviarLink) {
            app(ContaService::class)->enviarLinkSenha($user);
        }

        return $user;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->record]);
    }
}
