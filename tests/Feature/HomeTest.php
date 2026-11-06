<?php

namespace Tests\Feature;

use App\Models\Download;
use App\Services\HomeService;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\Cache;
use Tests\Concerns\CriaCatalogo;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use CriaCatalogo, DatabaseTruncation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCatalogo();
        Cache::forget(HomeService::CACHE_KEY);
    }

    public function test_home_tem_seo_e_um_unico_h1(): void
    {
        $html = $this->get('/')
            ->assertOk()
            ->assertSee('<html lang="pt-BR">', false)
            ->assertSee('<title>Famílias Revit, Texturas e Projetos Prontos | Os Reviteiros</title>', false)
            ->assertSee('<link rel="canonical" href="'.route('home').'">', false)
            ->assertSee('<meta name="twitter:card" content="summary_large_image">', false)
            ->assertSee('property="og:title"', false)
            ->assertSee('Encontre a família certa e insira no Revit em um clique.')
            ->getContent();

        $this->assertSame(1, substr_count($html, '<h1'));

        preg_match('/<meta name="description" content="([^"]+)"/', $html, $m);
        $this->assertLessThanOrEqual(155, mb_strlen(html_entity_decode($m[1])));
        $this->assertStringContainsString('5.422 famílias', html_entity_decode($m[1]));
    }

    public function test_json_ld_valido_com_os_tipos_exigidos(): void
    {
        $html = $this->get('/')->getContent();

        preg_match('#<script type="application/ld\+json">(.+?)</script>#s', $html, $m);
        $json = json_decode($m[1], true, flags: JSON_THROW_ON_ERROR);

        $tipos = array_column($json['@graph'], '@type');
        $this->assertEqualsCanonicalizing(['Organization', 'WebSite', 'SoftwareApplication', 'FAQPage'], $tipos);

        $site = collect($json['@graph'])->firstWhere('@type', 'WebSite');
        $this->assertSame(route('familias.index').'?busca={search_term_string}', $site['potentialAction']['target']['urlTemplate']);

        $app = collect($json['@graph'])->firstWhere('@type', 'SoftwareApplication');
        $this->assertSame(['0.00', '19.97', '59.97'], array_column($app['offers'], 'price'));

        // A resposta placeholder do Revit LT fica fora do FAQPage
        $faq = collect($json['@graph'])->firstWhere('@type', 'FAQPage');
        $this->assertCount(5, $faq['mainEntity']);
        $this->assertNotContains('Funciona no Revit LT?', array_column($faq['mainEntity'], 'name'));
    }

    public function test_busca_do_hero_vai_para_a_biblioteca_de_familias(): void
    {
        $this->get('/')
            ->assertSee('action="'.route('familias.index').'"', false)
            ->assertSee('name="busca"', false)
            ->assertSee('<label for="busca"', false);
    }

    public function test_cabecalho_tem_menu_do_celular_e_barra_fixa_depois_do_hero(): void
    {
        $this->get('/')
            ->assertSee('<dialog id="rv-menu"', false)
            ->assertSee('id="rv-menu-abrir"', false)
            ->assertSee('aria-expanded="false"', false)
            ->assertSee('data-rv-cta-hero', false)
            ->assertSee('id="rv-barra-acao" hidden', false)
            ->assertSee('5.422 famílias no Revit');
    }

    public function test_celular_mostra_seis_ambientes_e_o_desktop_mostra_todos(): void
    {
        $html = $this->get('/')->getContent();

        // Os 12 ambientes existem; 6 deles (destaque_mobile) aparecem em qualquer tela, os outros só a partir de md
        $this->assertSame(12, substr_count($html, 'class="group flex flex-col gap-2 lg:gap-2.5 text-rv-ink"'));
        $this->assertSame(6, substr_count($html, '<li class="hidden md:block">'));
    }

    public function test_planos_no_celular_tem_premium_primeiro_por_css(): void
    {
        $html = $this->get('/')->getContent();

        // A ordem do HTML continua Grátis, Basic, Premium; o order-1 é que coloca o Premium primeiro no celular
        preg_match('/class="([^"]*order-1[^"]*)"/', $html, $m);
        $this->assertStringContainsString('border-rv-accent', $m[1] ?? '');
        // Grátis e Basic mostram só o resumo no celular; a lista completa fica só no desktop
        $this->assertStringContainsString('Famílias da categoria gratuita, para conhecer o plugin.', $html);
        $this->assertStringContainsString('Famílias e texturas ilimitadas, no site e no plugin.', $html);
    }

    public function test_depoimentos_ficam_escondidos_por_padrao(): void
    {
        $this->get('/')
            ->assertDontSee('Quem usa não volta a modelar do zero')
            ->assertDontSee('Camila Ferraz');
    }

    public function test_depoimentos_ilustrativos_nunca_sao_publicados_fora_do_local(): void
    {
        config(['reviteiros.home.mostrar_depoimentos' => true]);

        $this->get('/')->assertDontSee('Camila Ferraz');
    }

    public function test_categorias_e_fabricantes_usam_dados_reais(): void
    {
        $banheiro = $this->categoria('Banheiro');
        $deca = $this->marca('Deca');
        $this->familia('Bacia Monobloco', [$banheiro, $deca], ['gratuita' => true]);

        $this->get('/')
            ->assertSee('href="'.route('familias.index', ['cat' => 'banheiro']).'"', false)
            ->assertSee('1 famílias')
            ->assertSee('href="'.route('fabricantes.show', 'deca').'"', false)
            ->assertSee('[Nº] famílias'); // ambientes sem termo no banco
    }

    public function test_mais_baixadas_da_semana(): void
    {
        $popular = $this->familia('Cuba de Apoio Oval', [$this->marca('Docol')]);
        $antiga = $this->familia('Mesa Antiga');

        Download::forceCreate(['familia_id' => $popular->id, 'created_at' => now()->subDay()]);
        Download::forceCreate(['familia_id' => $popular->id, 'created_at' => now()->subDays(2)]);
        $antiga->forceFill(['downloads_count' => 99])->save();

        $html = $this->get('/')->assertSee('Cuba de Apoio Oval')->assertSee('Docol')->getContent();

        // A da semana vem antes da que só tem downloads antigos
        $this->assertLessThan(strpos($html, 'Mesa Antiga'), strpos($html, 'Cuba de Apoio Oval'));
        $this->assertStringContainsString($popular->urlPublica(), $html);
    }
}
