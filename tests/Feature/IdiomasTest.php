<?php

namespace Tests\Feature;

use App\Models\Familia;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Tests\Concerns\CriaCatalogo;
use Tests\TestCase;

class IdiomasTest extends TestCase
{
    use CriaCatalogo, DatabaseTruncation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCatalogo();
    }

    private function base(string $caminho): string
    {
        return rtrim(config('app.url'), '/').$caminho;
    }

    public function test_portugues_e_o_padrao_sem_prefixo(): void
    {
        $this->get($this->base('/'))
            ->assertOk()
            ->assertSee('<html lang="pt-BR">', false)
            ->assertSee('Baixar plugin grátis');
    }

    public function test_cada_idioma_traduz_a_home_e_usa_o_prefixo_nos_links(): void
    {
        $this->get($this->base('/en'))->assertOk()
            ->assertSee('<html lang="en">', false)
            ->assertSee('Download the free plugin')
            ->assertSee('href="'.$this->base('/en/familias-revit').'"', false)
            ->assertDontSee('Baixar plugin grátis');

        $this->get($this->base('/fr'))->assertOk()->assertSee('<html lang="fr">', false)->assertSee('Télécharger le plugin gratuit');
        $this->get($this->base('/es'))->assertOk()->assertSee('<html lang="es">', false)->assertSee('Descargar el plugin gratis');
    }

    public function test_hreflang_e_canonical_por_idioma(): void
    {
        $this->get($this->base('/fr/familias-revit'))
            ->assertOk()
            ->assertSee('<link rel="canonical" href="'.$this->base('/fr/familias-revit').'">', false)
            ->assertSee('hreflang="pt-BR" href="'.$this->base('/familias-revit').'"', false)
            ->assertSee('hreflang="en" href="'.$this->base('/en/familias-revit').'"', false)
            ->assertSee('hreflang="x-default" href="'.$this->base('/familias-revit').'"', false);
    }

    public function test_paginacao_seo_mantem_o_prefixo_e_a_barra_final(): void
    {
        Familia::factory()->count(60)->create(['tipo' => 'familia']);

        $this->get($this->base('/en/familias-revit/page/2/'))
            ->assertOk()
            ->assertSee('<link rel="canonical" href="'.$this->base('/en/familias-revit/page/2/').'">', false)
            ->assertSee('hreflang="es" href="'.$this->base('/es/familias-revit/page/2/').'"', false);

        $this->get($this->base('/en/familias-revit/page/1/'))->assertRedirect($this->base('/en/familias-revit'));
    }

    public function test_idioma_nao_vaza_entre_requisicoes(): void
    {
        $this->get($this->base('/en'))->assertSee('Skip to content');

        // (o helper de teste monta a URL com url(), que ainda carrega a raiz /en da requisição anterior)
        $this->get($this->base('/'))->assertSee('Pular para o conteúdo')->assertDontSee('Skip to content');
    }

    public function test_validacao_vem_no_idioma_da_pagina(): void
    {
        $this->post($this->base('/en/login'), ['email' => 'nao-e-email', 'password' => ''])
            ->assertSessionHasErrors('email');

        $this->assertStringContainsString('valid email', session('errors')->first('email'));
    }

    public function test_seletor_de_idioma_lista_os_quatro(): void
    {
        $this->get($this->base('/'))
            ->assertSee('hreflang="en"', false)
            ->assertSee('hreflang="fr"', false)
            ->assertSee('hreflang="es"', false);
    }
}
