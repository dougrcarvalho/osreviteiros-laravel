<?php

namespace Tests\Feature;

use App\Enums\TipoItem;
use App\Http\Livewire\FamiliaGrid;
use App\Models\Familia;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Livewire\Livewire;
use Tests\Concerns\CriaCatalogo;
use Tests\TestCase;

/** URLs das bibliotecas e paginação SEO do infinite scroll (/page/{n}/) */
class PaginacaoSeoTest extends TestCase
{
    use CriaCatalogo, DatabaseTruncation;

    /** URL absoluta literal — o helper url() do Laravel removeria a barra final de /page/2/ */
    private function u(string $caminho): string
    {
        return rtrim(config('app.url'), '/').$caminho;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCatalogo();

        // 100 famílias = 3 páginas de 48; ordem padrão: mais recentes (maior id) primeiro
        foreach (range(1, 100) as $i) {
            Familia::factory()->create(['nome' => sprintf('Familia %03d', $i), 'slug' => "familia-{$i}"]);
        }
    }

    public function test_urls_das_bibliotecas(): void
    {
        $this->get('/familias-revit')->assertOk()->assertSee('Familia 100');
        $this->get('/texturas-revit-bim')->assertOk()->assertDontSee('Familia 100');
        $this->get('/projetos-prontos-revit-bim')->assertOk()->assertDontSee('Familia 100');
        $this->get('/familias-revit/familia-1')->assertOk()->assertSee('Familia 001');

        $this->assertSame($this->u('/familias-revit/familia-1'), Familia::where('slug', 'familia-1')->first()->urlPublica());
    }

    public function test_urls_antigas_redirecionam_301(): void
    {
        $this->get('/familias?cat=x')->assertRedirect('/familias-revit?cat=x')->assertStatus(301);
        $this->get('/familias/familia-1')->assertRedirect('/familias-revit/familia-1')->assertStatus(301);
        $this->get('/texturas')->assertRedirect('/texturas-revit-bim')->assertStatus(301);
        $this->get('/projetos')->assertRedirect('/projetos-prontos-revit-bim')->assertStatus(301);
    }

    public function test_cada_pagina_tem_url_e_conteudo_proprios(): void
    {
        $this->get('/familias-revit/page/2/')
            ->assertOk()
            ->assertSee('Familia 052')->assertSee('Familia 005')   // itens 49–96
            ->assertDontSee('Familia 100')->assertDontSee('Familia 004')
            ->assertSee('<title>Biblioteca de Famílias Revit BIM – Página 2 | Os Reviteiros</title>', false)
            ->assertSee('<link rel="canonical" href="'.$this->u('/familias-revit/page/2/').'">', false)
            ->assertSee('<link rel="prev" href="'.$this->u('/familias-revit').'">', false)
            ->assertSee('<link rel="next" href="'.$this->u('/familias-revit/page/3/').'">', false)
            ->assertSee('data-pagina-inicio="2" data-url="'.$this->u('/familias-revit/page/2/').'"', false)
            ->assertSee('Carregar resultados anteriores');

        $this->get('/familias-revit/page/3/')
            ->assertOk()
            ->assertSee('Familia 004')->assertSee('Familia 001')
            ->assertDontSee('<link rel="next"', false);

        $this->get('/familias-revit')
            ->assertSee('<link rel="canonical" href="'.$this->u('/familias-revit').'">', false)
            ->assertDontSee('<link rel="prev"', false)
            ->assertSee('<link rel="next" href="'.$this->u('/familias-revit/page/2/').'">', false);
    }

    public function test_pagina_1_redireciona_e_pagina_inexistente_da_404(): void
    {
        $this->get('/familias-revit/page/1/')->assertRedirect('/familias-revit')->assertStatus(301);
        $this->get('/familias-revit/page/1/?cat=x')->assertRedirect('/familias-revit?cat=x');
        $this->get('/familias-revit/page/4/')->assertNotFound();
        $this->get('/texturas-revit-bim/page/2/')->assertNotFound();
    }

    public function test_filtros_entram_nas_urls_paginadas(): void
    {
        $auto = $this->categoria('Automóveis');
        foreach (Familia::orderBy('id')->limit(60)->get() as $familia) {
            $familia->categorias()->attach($auto);
        }

        $this->get('/familias-revit/page/2/?cat=automoveis')
            ->assertOk()
            ->assertSee('<link rel="canonical" href="'.$this->u('/familias-revit/page/2/?cat=automoveis').'">', false)
            ->assertSee('<link rel="prev" href="'.$this->u('/familias-revit?cat=automoveis').'">', false);

        $this->get('/familias-revit/page/3/?cat=automoveis')->assertNotFound();
    }

    public function test_infinite_scroll_marca_a_url_de_cada_pagina(): void
    {
        Livewire::test(FamiliaGrid::class, ['biblioteca' => 'familia', 'query' => []])
            ->assertSee('data-pagina-inicio="1" data-url="'.$this->u('/familias-revit').'"', false)
            ->call('carregarMais')
            ->assertSee('data-pagina-inicio="2" data-url="'.$this->u('/familias-revit/page/2/').'"', false)
            ->call('carregarMais')
            ->assertSee('data-pagina-inicio="3" data-url="'.$this->u('/familias-revit/page/3/').'"', false)
            ->assertSee('Familia 001')
            ->call('carregarMais')                 // além da última: não muda
            ->assertSet('paginaFinal', 3);
    }

    public function test_aberto_no_meio_carrega_as_paginas_anteriores(): void
    {
        Livewire::test(FamiliaGrid::class, ['biblioteca' => 'familia', 'query' => [], 'pagina' => 3])
            ->assertDontSee('Familia 052')
            ->call('carregarAnteriores')
            ->assertSet('paginaInicial', 2)
            ->assertSee('Familia 052')->assertSee('Familia 001')
            ->call('carregarAnteriores')
            ->assertSet('paginaInicial', 1)
            ->assertSee('Familia 100');
    }

    public function test_mudar_filtro_volta_para_a_pagina_1(): void
    {
        $this->categoria('Automóveis');

        Livewire::test(FamiliaGrid::class, ['biblioteca' => 'familia', 'query' => [], 'pagina' => 2])
            ->call('alternarRecurso', 'gratis')
            ->assertSet('paginaInicial', 1)
            ->assertDispatched('rv-url', url: $this->u('/familias-revit?recurso=gratis'), push: true);
    }

    public function test_voltar_do_navegador_restaura_a_pagina(): void
    {
        Livewire::test(FamiliaGrid::class, ['biblioteca' => 'familia', 'query' => []])
            ->call('sincronizarUrl', [], 2)
            ->assertSet('paginaInicial', 2)
            ->assertSee('Familia 052')->assertDontSee('Familia 100');
    }

    public function test_tela_do_fabricante_tambem_pagina(): void
    {
        $marca = $this->marca('Toyota');
        Familia::query()->update(['fabricante_id' => $marca->id]);

        $this->get('/fabricantes/toyota/page/2/')
            ->assertOk()
            ->assertSee('<link rel="canonical" href="'.$this->u('/fabricantes/toyota/page/2/').'">', false)
            ->assertSee('Familia 052');

        $this->get('/fabricantes/toyota/page/2/?biblioteca=textura')->assertNotFound();
    }
}
