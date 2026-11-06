<?php

namespace Tests\Feature;

use App\Enums\TipoItem;
use App\Http\Livewire\FamiliaGrid;
use App\Services\CategoriaService;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Livewire\Livewire;
use Tests\Concerns\CriaCatalogo;
use Tests\TestCase;

class FiltroAcumulativoTest extends TestCase
{
    use CriaCatalogo, DatabaseTruncation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCatalogo();

        $auto   = $this->categoria('Automóveis');
        $movel  = $this->categoria('Mobília');
        $carros = $this->categoria('Carros', 2);
        $motos  = $this->categoria('Motos', 2);
        $cadeir = $this->categoria('Cadeiras', 2);
        $suv    = $this->categoria('SUV', 3);
        $sedan  = $this->categoria('Sedan', 3);
        $toyota = $this->marca('Toyota');
        $honda  = $this->marca('Honda');
        $herman = $this->marca('Herman Miller');

        $this->familia('RAV4', [$auto, $carros, $suv, $toyota]);
        $this->familia('Corolla', [$auto, $carros, $sedan, $toyota]);
        $this->familia('CR-V', [$auto, $carros, $suv, $honda]);
        $this->familia('CB 500', [$auto, $motos, $honda]);
        $this->familia('Aeron', [$movel, $cadeir, $herman]);
        $this->familia('Piso Madeira', [$this->termo('Pisos', 'cat', TipoItem::Textura), $herman], ['tipo' => TipoItem::Textura, 'gratuita' => true]);
    }

    private function nomes(array $params): array
    {
        return collect($this->getJson('/api/familias?'.http_build_query($params + ['biblioteca' => 'familia']))->assertOk()->json('data'))
            ->pluck('nome')->sort()->values()->all();
    }

    public function test_filtros_de_todos_os_niveis_sao_acumulativos(): void
    {
        $this->assertSame(['CB 500', 'CR-V', 'Corolla', 'RAV4'], $this->nomes(['cat' => 'automoveis']));
        $this->assertSame(['CR-V', 'Corolla', 'RAV4'], $this->nomes(['cat' => 'automoveis', 'sub' => 'carros']));
        $this->assertSame(['CR-V', 'RAV4'], $this->nomes(['cat' => 'automoveis', 'sub' => 'carros', 'subcl' => 'suv']));
        $this->assertSame(['RAV4'], $this->nomes(['cat' => 'automoveis', 'sub' => 'carros', 'subcl' => 'suv', 'marca' => 'toyota']));
        $this->assertSame([], $this->nomes(['cat' => 'mobilia', 'sub' => 'carros']));
    }

    public function test_filtra_apenas_pela_marca(): void
    {
        $this->assertSame(['CB 500', 'CR-V'], $this->nomes(['marca' => 'honda']));
    }

    public function test_cada_biblioteca_tem_seus_itens_e_taxonomias(): void
    {
        // O slug "cat" é de cada biblioteca: "pisos" só existe em texturas
        $texturas = collect($this->getJson('/api/familias?biblioteca=textura&cat=pisos')->json('data'))->pluck('nome')->all();
        $this->assertSame(['Piso Madeira'], $texturas);

        $this->assertSame([], $this->nomes(['cat' => 'pisos']));
        $this->assertNotContains('Piso Madeira', $this->nomes([]));
    }

    public function test_filtro_gratis(): void
    {
        $this->assertSame([], $this->nomes(['gratis' => 1]));

        $gratis = collect($this->getJson('/api/familias?biblioteca=textura&gratis=1')->json('data'))->pluck('nome')->all();
        $this->assertSame(['Piso Madeira'], $gratis);
    }

    public function test_marca_vale_em_qualquer_biblioteca(): void
    {
        $todas = collect($this->getJson('/api/familias?marca=herman-miller')->json('data'))->pluck('nome')->sort()->values()->all();

        $this->assertSame(['Aeron', 'Piso Madeira'], $todas);
    }

    public function test_opcoes_da_taxonomia_respeitam_o_contexto(): void
    {
        $service = app(CategoriaService::class);
        $slugs   = fn ($opcoes) => $opcoes->pluck('slug')->sort()->values()->all();

        $this->assertSame(['carros', 'motos'], $slugs($service->opcoes($this->taxonomia('sub'), ['cat' => 'automoveis'])));
        $this->assertSame(['sedan', 'suv'], $slugs($service->opcoes($this->taxonomia('subcl'), ['cat' => 'automoveis', 'sub' => 'carros'])));
        $this->assertSame(['honda', 'toyota'], $slugs($service->opcoes($this->taxonomia('marca'), ['cat' => 'automoveis', 'sub' => 'carros', 'subcl' => 'suv'])));

        // Partindo da marca: só categorias que têm itens dela
        $this->assertSame(['automoveis'], $slugs($service->opcoes($this->taxonomia('cat'), ['marca' => 'toyota'])));
        $this->assertSame(['carros'], $slugs($service->opcoes($this->taxonomia('sub'), ['marca' => 'toyota', 'cat' => 'automoveis'])));

        $this->getJson('/api/categorias/opcoes?biblioteca=familia&taxonomia=sub&cat=automoveis')
            ->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/api/categorias/opcoes?biblioteca=familia&taxonomia=nao-existe')->assertNotFound();
    }

    public function test_grid_limpa_os_filtros_dependentes_ao_trocar_a_categoria(): void
    {
        Livewire::test(FamiliaGrid::class, ['biblioteca' => 'familia', 'query' => []])
            ->call('selecionar', 'cat', 'automoveis')
            ->call('selecionar', 'sub', 'carros')
            ->call('selecionar', 'subcl', 'suv')
            ->assertSet('filtros.subcl', 'suv')
            ->assertSee('RAV4')->assertSee('CR-V')->assertDontSee('Corolla')
            ->call('selecionar', 'sub', 'motos')
            ->assertSet('filtros.cat', 'automoveis')
            ->assertSet('filtros.sub', 'motos')
            ->assertSet('filtros.subcl', null)
            ->assertSee('CB 500')->assertDontSee('RAV4')
            ->call('remover', 'cat')
            ->assertSet('filtros', []);
    }

    public function test_grid_atualiza_a_url_pelo_evento(): void
    {
        Livewire::test(FamiliaGrid::class, ['biblioteca' => 'familia', 'query' => []])
            ->call('selecionar', 'cat', 'automoveis')
            ->assertDispatched('rv-url', url: url('/familias-revit?cat=automoveis'), push: true)
            ->call('alternarGratis')
            ->assertDispatched('rv-url', url: url('/familias-revit?cat=automoveis&gratis=1'), push: true)
            ->set('busca', 'rav')
            ->assertDispatched('rv-url', url: url('/familias-revit?cat=automoveis&gratis=1&busca=rav'), push: false);
    }

    public function test_grid_restaura_filtros_da_url_no_voltar_do_navegador(): void
    {
        Livewire::test(FamiliaGrid::class, ['biblioteca' => 'familia', 'query' => ['cat' => 'automoveis', 'sub' => 'carros']])
            ->assertSet('filtros', ['cat' => 'automoveis', 'sub' => 'carros'])
            ->call('sincronizarUrl', ['cat' => 'mobilia'])
            ->assertSet('filtros', ['cat' => 'mobilia'])
            ->assertSee('Aeron')->assertDontSee('RAV4');
    }

    public function test_grid_ignora_filtros_de_outra_biblioteca(): void
    {
        // "pisos" não é termo de Famílias › Categoria: filtro ignorado na URL
        Livewire::test(FamiliaGrid::class, ['biblioteca' => 'textura', 'query' => ['cat' => 'pisos', 'sub' => 'carros']])
            ->assertSet('filtros', ['cat' => 'pisos'])
            ->assertSee('Piso Madeira');
    }

    public function test_tela_da_marca_mantem_a_marca_fixa_e_permite_escolher_a_biblioteca(): void
    {
        Livewire::test(FamiliaGrid::class, ['marca' => 'toyota', 'query' => []])
            ->assertSee('RAV4')->assertSee('Corolla')->assertDontSee('CR-V')
            ->call('filtrarBiblioteca', 'familia')
            ->call('selecionar', 'cat', 'automoveis')
            ->call('selecionar', 'subcl', 'sedan')
            ->assertSee('Corolla')->assertDontSee('RAV4')
            ->call('remover', 'marca')
            ->call('limpar')
            ->assertSet('marcaFixa', 'toyota')
            ->assertDontSee('CR-V');

        Livewire::test(FamiliaGrid::class, ['marca' => 'herman-miller', 'query' => []])
            ->assertSee('Aeron')->assertSee('Piso Madeira')
            ->call('filtrarBiblioteca', 'textura')
            ->assertSee('Piso Madeira')->assertDontSee('Aeron');
    }

    public function test_paginas_das_bibliotecas_e_do_fabricante(): void
    {
        $this->get('/familias-revit?cat=automoveis')->assertOk()->assertSee('RAV4');
        $this->get('/texturas-revit-bim')->assertOk()->assertSee('Piso Madeira')->assertDontSee('RAV4');
        $this->get('/projetos-prontos-revit-bim')->assertOk()->assertDontSee('RAV4');
        $this->get(route('fabricantes.show', 'toyota'))->assertOk()->assertSee('RAV4')->assertDontSee('Aeron');
        $this->get(route('fabricantes.index'))->assertOk()->assertSee('Herman Miller');
    }

    public function test_item_aberto_na_biblioteca_errada_redireciona(): void
    {
        $this->get('/familias-revit/piso-madeira')->assertRedirect('/texturas-revit-bim/piso-madeira');
        $this->get('/texturas-revit-bim/piso-madeira')->assertOk();
        $this->get('/projetos-prontos-revit-bim/rav4')->assertRedirect('/familias-revit/rav4');
    }
}
