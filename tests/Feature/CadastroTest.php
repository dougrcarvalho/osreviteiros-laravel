<?php

namespace Tests\Feature;

use App\Models\Plano;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Tests\Concerns\CriaCatalogo;
use Tests\TestCase;

class CadastroTest extends TestCase
{
    use CriaCatalogo, DatabaseTruncation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCatalogo();
    }

    private function dados(array $extra = []): array
    {
        return $extra + [
            'name'                  => 'Maria Arquiteta',
            'email'                 => 'maria@exemplo.com',
            'password'              => 'SenhaForte123',
            'password_confirmation' => 'SenhaForte123',
        ];
    }

    public function test_cadastro_gratis_loga_e_volta_para_familias(): void
    {
        $this->post('/cadastro', $this->dados(['plano' => 'gratis']))
            ->assertRedirect(route('familias.index'));

        $this->assertAuthenticated();
        $this->assertSame('gratis', User::first()->plano->slug);
    }

    public function test_plano_pago_so_vale_apos_pagamento(): void
    {
        Plano::where('slug', 'premium')->update(['checkout_url' => 'https://pay.hotmart.com/XYZ']);

        $resposta = $this->post('/cadastro', $this->dados(['plano' => 'premium']));

        $resposta->assertRedirectContains('https://pay.hotmart.com/XYZ?email=maria%40exemplo.com');
        $this->assertFalse(User::first()->hasPlano(), 'Plano pago não pode ser liberado antes do webhook.');
    }

    public function test_login_e_logout(): void
    {
        $user = $this->usuario('basic');

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect(route('familias.index'));
        $this->assertAuthenticatedAs($user);

        $this->post('/logout')->assertRedirect(route('familias.index'));
        $this->assertGuest();
    }

    public function test_paginas_publicas(): void
    {
        $familia = $this->familia('Mesa Teste');

        $this->get('/')->assertOk();
        $this->get('/familias-revit')->assertOk()->assertSee('Mesa Teste');
        $this->get($familia->urlPublica())->assertOk()->assertSee('og:title', false);
        $this->get('/familias/mesa-teste')->assertRedirect('/familias-revit/mesa-teste'); // URL anterior
        $this->get('/cadastro?plano=basic')->assertOk();
        $this->get('/login')->assertOk();
    }
}
