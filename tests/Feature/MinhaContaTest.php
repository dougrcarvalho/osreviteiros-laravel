<?php

namespace Tests\Feature;

use App\Enums\OrigemPlano;
use App\Models\Download;
use App\Models\Plano;
use App\Services\AssinaturaService;
use App\Services\UsoService;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Tests\Concerns\CriaCatalogo;
use Tests\TestCase;

class MinhaContaTest extends TestCase
{
    use CriaCatalogo, DatabaseTruncation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCatalogo();

        Plano::where('slug', 'basic')->update(['checkout_url' => 'https://pay.test/basic']);
        Plano::where('slug', 'premium')->update(['checkout_url' => 'https://pay.test/premium']);
    }

    public function test_visitante_vai_para_o_login(): void
    {
        foreach (['conta.perfil', 'conta.plano', 'conta.historico'] as $rota) {
            $this->get(route($rota))->assertRedirect(route('login'));
        }
    }

    public function test_altera_o_nome_mas_nao_o_email_nem_o_plano(): void
    {
        $user = $this->usuario('gratis');
        $email = $user->email;

        $this->actingAs($user)
            ->put(route('conta.perfil.atualizar'), ['name' => '  Maria   da Silva ', 'email' => 'outro@x.com', 'plano_id' => 99])
            ->assertRedirect(route('conta.perfil'));

        $user->refresh();
        $this->assertSame('Maria da Silva', $user->name);
        $this->assertSame($email, $user->email);
        $this->assertNotSame(99, $user->plano_id);
    }

    public function test_nome_e_obrigatorio(): void
    {
        $this->actingAs($this->usuario('gratis'))->put(route('conta.perfil.atualizar'), ['name' => ' '])->assertSessionHasErrors('name');
    }

    private function cartoesDeUpgrade(string $plano): int
    {
        $html = $this->actingAs($this->usuario($plano))->get(route('conta.plano'))->assertOk()->getContent();

        return preg_match_all('/class="rv-conta__upgrade[" ]/', $html);
    }

    public function test_plano_basic_oferece_upgrade_so_para_o_premium_e_permite_cancelar(): void
    {
        $this->assertSame(1, $this->cartoesDeUpgrade('basic'));

        $this->get(route('conta.plano'))
            ->assertSee('Assinar Premium')
            ->assertSee('https://pay.test/premium?', false)
            ->assertSee('Cancelar assinatura');
    }

    public function test_plano_gratis_oferece_upgrade_e_nao_tem_cancelamento(): void
    {
        $this->assertSame(2, $this->cartoesDeUpgrade('gratis'));

        $this->get(route('conta.plano'))->assertDontSee('Cancelar assinatura');
    }

    public function test_premium_nao_tem_upgrade(): void
    {
        $this->assertSame(0, $this->cartoesDeUpgrade('premium'));
    }

    public function test_cancelamento_exige_confirmacao_e_fica_pendente_sem_mudar_o_plano(): void
    {
        $user = $this->usuario('premium');

        $this->actingAs($user)->post(route('conta.cancelamento'), ['motivo' => 'Caro'])->assertSessionHasErrors('confirmar');
        $this->assertDatabaseCount('solicitacoes_cancelamento', 0);

        $this->post(route('conta.cancelamento'), ['confirmar' => 1, 'motivo' => 'Caro'])->assertRedirect();
        $this->post(route('conta.cancelamento'), ['confirmar' => 1])->assertRedirect(); // duplicado não cria outro

        $this->assertDatabaseCount('solicitacoes_cancelamento', 1);
        $this->assertDatabaseHas('solicitacoes_cancelamento', ['user_id' => $user->id, 'motivo' => 'Caro', 'atendida_em' => null]);
        $this->assertSame('premium', $user->refresh()->plano->slug);
        $this->assertDatabaseHas('auditorias', ['evento' => 'cancelamento_solicitado']);

        $this->get(route('conta.plano'))->assertSee('Desistir do cancelamento');
    }

    public function test_gratis_nao_cria_pedido_de_cancelamento(): void
    {
        $this->actingAs($this->usuario('gratis'))->post(route('conta.cancelamento'), ['confirmar' => 1]);
        $this->assertDatabaseCount('solicitacoes_cancelamento', 0);
    }

    public function test_desistir_do_cancelamento(): void
    {
        $user = $this->usuario('basic');

        $this->actingAs($user)->post(route('conta.cancelamento'), ['confirmar' => 1]);
        $this->delete(route('conta.cancelamento.desfazer'))->assertRedirect(route('conta.plano'));

        $this->assertSame(0, $user->cancelamentos()->pendentes()->count());
    }

    public function test_voltar_ao_gratis_conclui_o_pedido_pendente(): void
    {
        $user = $this->usuario('premium');
        $this->actingAs($user)->post(route('conta.cancelamento'), ['confirmar' => 1]);

        app(AssinaturaService::class)->alterarPlano($user->refresh(), Plano::where('slug', 'gratis')->first(), OrigemPlano::Manual);

        $this->assertSame(0, $user->cancelamentos()->pendentes()->count());
        $this->assertNotNull($user->cancelamentos()->first()->atendida_em);
    }

    public function test_historico_de_uso_conta_por_tipo_e_lista_so_os_meus(): void
    {
        $user = $this->usuario('premium');
        $outro = $this->usuario('basic');

        $cadeira = $this->familia('Cadeira Azul');
        $piso = $this->familia('Piso Claro', [], ['tipo' => 'textura']);
        $casa = $this->familia('Casa 2 Quartos', [], ['tipo' => 'projeto']);

        foreach ([$cadeira, $cadeira, $piso, $casa] as $item) {
            Download::create(['familia_id' => $item->id, 'user_id' => $user->id]);
        }
        Download::create(['familia_id' => $cadeira->id, 'user_id' => $outro->id]);

        $resumo = app(UsoService::class)->resumo($user);
        $this->assertSame(4, $resumo['total']);
        $this->assertSame(3, $resumo['distintos']);
        $this->assertSame(['total' => 2, 'distintos' => 1], $resumo['por_tipo']['familia']);
        $this->assertSame(1, $resumo['por_tipo']['textura']['total']);
        $this->assertSame(1, $resumo['por_tipo']['projeto']['total']);

        $this->actingAs($user)->get(route('conta.historico'))
            ->assertOk()->assertSee('Cadeira Azul')->assertSee('Piso Claro')->assertSee('Casa 2 Quartos');

        $this->get(route('conta.historico', ['tipo' => 'textura']))
            ->assertOk()->assertSee('Piso Claro')->assertDontSee('Cadeira Azul');

        $this->actingAs($outro)->get(route('conta.historico'))->assertSee('Cadeira Azul')->assertDontSee('Piso Claro');
    }

    public function test_historico_vazio_e_perfil_funcionam(): void
    {
        $user = $this->usuario('gratis');

        $this->actingAs($user)->get(route('conta.historico'))->assertOk()->assertSee('Você ainda não baixou nada.');
        $this->get(route('conta.perfil'))->assertOk()->assertSee($user->email);
    }

    public function test_area_do_aluno_traduzida(): void
    {
        $this->actingAs($this->usuario('basic'))->get(rtrim(config('app.url'), '/').'/en/minha-conta/plano')
            ->assertOk()->assertSee('Plan and subscription')->assertSee('Cancel subscription');
    }
}
