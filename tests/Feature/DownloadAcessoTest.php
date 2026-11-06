<?php

namespace Tests\Feature;

use App\Enums\TipoItem;
use App\Models\Familia;
use App\Services\DownloadService;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Tests\Concerns\CriaCatalogo;
use Tests\TestCase;

class DownloadAcessoTest extends TestCase
{
    use CriaCatalogo, DatabaseTruncation;

    private Familia $paga;

    private Familia $gratuita;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCatalogo();

        $this->paga     = $this->familia('Cadeira Paga', [], ['link_download' => 'https://dl.test/paga.rfa']);
        $this->gratuita = $this->familia('Cadeira Livre', [], ['link_download' => 'https://dl.test/livre.rfa', 'gratuita' => true]);
    }

    public function test_estados_do_botao(): void
    {
        $service = app(DownloadService::class);
        $gratis  = $this->usuario('gratis');
        $premium = $this->usuario('premium');

        $this->assertSame(DownloadService::CADASTRAR, $service->estadoBotao(null, $this->paga));
        $this->assertSame(DownloadService::CADASTRAR, $service->estadoBotao(null, $this->gratuita));
        $this->assertSame(DownloadService::ASSINAR, $service->estadoBotao($gratis, $this->paga));
        $this->assertSame(DownloadService::BAIXAR_GRATIS, $service->estadoBotao($gratis, $this->gratuita));
        $this->assertSame(DownloadService::BAIXAR, $service->estadoBotao($premium, $this->paga));
        $this->assertSame(DownloadService::BAIXAR, $service->estadoBotao($premium, $this->gratuita));
    }

    public function test_usuario_sem_plano_e_tratado_como_gratis(): void
    {
        $semPlano = $this->usuario('inexistente');

        $this->assertNull(app(DownloadService::class)->linkPara($semPlano, $this->paga));
        $this->assertSame('https://dl.test/livre.rfa', app(DownloadService::class)->linkPara($semPlano, $this->gratuita));
    }

    public function test_visitante_e_mandado_para_o_cadastro(): void
    {
        $this->post(route('familias.download', $this->paga))->assertRedirect(route('cadastro'));

        $this->postJson(route('familias.download', $this->paga))
            ->assertUnauthorized()
            ->assertJson(['error' => 'nao_autenticado']);
    }

    public function test_gratis_nao_baixa_familia_paga(): void
    {
        $this->actingAs($this->usuario('gratis'))
            ->postJson(route('familias.download', $this->paga))
            ->assertForbidden()
            ->assertJson(['error' => 'sem_permissao'])
            ->assertJsonMissing(['url' => 'https://dl.test/paga.rfa']);
    }

    public function test_gratis_baixa_familia_gratuita(): void
    {
        $this->actingAs($this->usuario('gratis'))
            ->post(route('familias.download', $this->gratuita))
            ->assertRedirect('https://dl.test/livre.rfa');
    }

    public function test_pago_baixa_qualquer_familia(): void
    {
        $this->actingAs($this->usuario('basic'))
            ->postJson(route('familias.download', $this->paga))
            ->assertOk()
            ->assertJson(['url' => 'https://dl.test/paga.rfa']);
    }

    public function test_api_nao_expoe_link_para_visitante(): void
    {
        $this->getJson('/api/familias')
            ->assertOk()
            ->assertDontSee('dl.test', false)
            ->assertJsonStructure(['data', 'meta' => ['total', 'per_page', 'current_page', 'last_page']]);
    }

    public function test_api_expoe_so_links_gratuitos_para_plano_gratis(): void
    {
        $this->actingAs($this->usuario('gratis'))
            ->getJson('/api/familias')
            ->assertSee('livre.rfa', false)
            ->assertDontSee('paga.rfa', false);
    }

    public function test_api_expoe_todos_os_links_para_plano_pago(): void
    {
        $this->actingAs($this->usuario('premium'))
            ->getJson('/api/familias')
            ->assertSee('livre.rfa', false)
            ->assertSee('paga.rfa', false);
    }

    public function test_html_nunca_contem_link_download(): void
    {
        $this->actingAs($this->usuario('premium'))
            ->get('/familias-revit')
            ->assertOk()
            ->assertDontSee('dl.test', false);

        $this->assertArrayNotHasKey('link_download', $this->paga->toArray());
    }

    public function test_basic_e_plus_baixam_familias_e_texturas_mas_nao_projetos(): void
    {
        $service = app(DownloadService::class);
        $textura = $this->familia('Piso Cinza', [], ['tipo' => TipoItem::Textura, 'link_download' => 'https://dl.test/piso.zip']);
        $projeto = $this->familia('Casa 2 Quartos', [], ['tipo' => TipoItem::Projeto, 'link_download' => 'https://dl.test/casa.zip']);

        foreach (['basic', 'plus'] as $plano) {
            $user = $this->usuario($plano);

            $this->assertTrue($service->podeBaixar($user, $this->paga), $plano);
            $this->assertTrue($service->podeBaixar($user, $textura), $plano);
            $this->assertFalse($service->podeBaixar($user, $projeto), $plano);
            $this->assertSame(DownloadService::UPGRADE, $service->estadoBotao($user, $projeto));
        }

        $basic = $this->usuario('basic');
        $this->actingAs($basic)
            ->postJson(route('familias.download', $projeto))
            ->assertForbidden()
            ->assertJson(['message' => 'Projetos prontos são exclusivos do plano Premium.']);

        $this->actingAs($basic)->post(route('familias.download', $projeto))->assertRedirect($projeto->urlPublica());
        $this->get($projeto->urlPublica())->assertSee('Exclusivo Premium')->assertSee('Projetos prontos são exclusivos do plano Premium.');
    }

    public function test_premium_baixa_projetos(): void
    {
        $projeto = $this->familia('Casa 3 Quartos', [], ['tipo' => TipoItem::Projeto, 'link_download' => 'https://dl.test/casa3.zip']);

        $this->assertSame(DownloadService::BAIXAR, app(DownloadService::class)->estadoBotao($this->usuario('premium'), $projeto));
        $this->actingAs($this->usuario('premium'))
            ->post(route('familias.download', $projeto))
            ->assertRedirect('https://dl.test/casa3.zip');
    }

    public function test_projeto_gratuito_e_liberado_para_qualquer_conta(): void
    {
        $projeto = $this->familia('Casa Modelo', [], ['tipo' => TipoItem::Projeto, 'gratuita' => true, 'link_download' => 'https://dl.test/modelo.zip']);

        $this->assertSame('https://dl.test/modelo.zip', app(DownloadService::class)->linkPara($this->usuario('gratis'), $projeto));
        $this->assertSame('https://dl.test/modelo.zip', app(DownloadService::class)->linkPara($this->usuario('basic'), $projeto));
    }
}
