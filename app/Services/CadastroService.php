<?php

namespace App\Services;

use App\Enums\OrigemPlano;
use App\Models\Plano;
use App\Models\User;
use Illuminate\Auth\Events\Registered;

class CadastroService
{
    public function __construct(
        private PlanoService $planos,
        private AssinaturaService $assinaturas,
    ) {}

    /**
     * Todo cadastro nasce no plano grátis. O plano pago escolhido só é
     * aplicado pelo webhook (Hotmart/Eduzz) após a confirmação do pagamento.
     *
     * @param  array{name: string, email: string, password: string}  $dados
     */
    public function cadastrar(array $dados): User
    {
        $user = User::create([
            'name'     => $dados['name'],
            'email'    => $dados['email'],
            'password' => $dados['password'],
        ]);

        $this->assinaturas->alterarPlano($user, $this->planos->gratis(), OrigemPlano::Cadastro);

        event(new Registered($user));

        return $user;
    }

    /** URL de checkout do plano escolhido, se for pago */
    public function checkoutUrl(?Plano $plano, User $user): ?string
    {
        if (! $plano?->isPago() || ! $plano->checkout_url) {
            return null;
        }

        $separador = str_contains($plano->checkout_url, '?') ? '&' : '?';

        // Hotmart/Eduzz pré-preenchem o e-mail; o webhook usa o mesmo e-mail para achar o usuário
        return $plano->checkout_url.$separador.http_build_query(['email' => $user->email, 'name' => $user->name]);
    }
}
