<?php

namespace Tests\Feature;

use App\Enums\FonteTaxonomia;
use App\Enums\TipoItem;
use App\Filament\Resources\Familias\Pages\CreateFamilia;
use App\Filament\Resources\Taxonomias\Pages\ManageTaxonomias;
use App\Http\Livewire\FamiliaGrid;
use App\Models\Familia;
use App\Models\Taxonomia;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Livewire\Livewire;
use Tests\Concerns\CriaCatalogo;
use Tests\TestCase;

/** Estrutura por biblioteca e extensibilidade (novas taxonomias sem mexer em código) */
class TaxonomiaTest extends TestCase
{
    use CriaCatalogo, DatabaseTruncation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCatalogo();
    }

    private function slugs(TipoItem $tipo): array
    {
        return Taxonomia::doTipo($tipo)->ordenadas()->pluck('slug')->all();
    }

    public function test_cada_biblioteca_tem_a_sua_estrutura(): void
    {
        $this->assertSame(['cat', 'sub', 'subcl', 'marca'], $this->slugs(TipoItem::Familia));
        $this->assertSame(['cat', 'marca'], $this->slugs(TipoItem::Textura));
        $this->assertSame(['cat', 'tipo'], $this->slugs(TipoItem::Projeto));

        $this->assertTrue($this->taxonomia('marca', TipoItem::Textura)->usaFabricante());
        $this->assertSame('cat', $this->taxonomia('sub')->pai->slug);
    }

    public function test_projeto_filtra_por_categoria_e_tipo(): void
    {
        $cat  = $this->termo('Residencial', 'cat', TipoItem::Projeto);
        $casa = $this->termo('Casa', 'tipo', TipoItem::Projeto);
        $loja = $this->termo('Loja', 'tipo', TipoItem::Projeto);

        $this->familia('Casa Praia', [$cat, $casa], ['tipo' => TipoItem::Projeto]);
        $this->familia('Loja Shopping', [$loja], ['tipo' => TipoItem::Projeto]);

        $nomes = fn (string $q) => collect($this->getJson("/api/familias?biblioteca=projeto&{$q}")->json('data'))->pluck('nome')->all();

        $this->assertSame(['Casa Praia'], $nomes('cat=residencial&tipo=casa'));
        $this->assertSame(['Loja Shopping'], $nomes('tipo=loja'));
        $this->get('/projetos-prontos-revit-bim?tipo=casa')->assertOk()->assertSee('Casa Praia')->assertDontSee('Loja Shopping');
    }

    public function test_nova_taxonomia_cor_funciona_sem_alterar_codigo(): void
    {
        // Administrador cria "Cor" para Famílias (faceta independente: sem pai)
        $this->novaTaxonomia('cor', 'Cor', plural: 'Cores');
        $azul     = $this->termo('Azul', 'cor');
        $vermelho = $this->termo('Vermelho', 'cor');
        $auto     = $this->categoria('Automóveis');

        $this->familia('Carro Azul', [$auto, $azul]);
        $this->familia('Carro Vermelho', [$auto, $vermelho]);

        // 1) Filtro na URL e na API
        $nomes = fn (string $q) => collect($this->getJson("/api/familias?biblioteca=familia&{$q}")->json('data'))->pluck('nome')->all();
        $this->assertSame(['Carro Azul'], $nomes('cor=azul'));
        $this->assertSame(['Carro Vermelho'], $nomes('cat=automoveis&cor=vermelho'));

        // 2) Grid: linha "Cores" visível desde o início, acumulando com a categoria
        Livewire::test(FamiliaGrid::class, ['biblioteca' => 'familia', 'query' => []])
            ->assertSee('Cores')->assertSee('Azul')
            ->call('selecionar', 'cor', 'azul')
            ->assertSee('Carro Azul')->assertDontSee('Carro Vermelho')
            ->call('selecionar', 'cat', 'automoveis')
            ->assertSet('filtros', ['cor' => 'azul', 'cat' => 'automoveis']); // trocar cat não limpa a cor

        // 3) Busca pelo nome do termo
        $this->assertSame(['Carro Vermelho'], $nomes('busca=vermelho'));

        // 4) Outras bibliotecas não ganham a taxonomia
        $this->assertSame(['cat', 'marca'], $this->slugs(TipoItem::Textura));
    }

    public function test_formulario_do_item_mostra_as_taxonomias_do_tipo(): void
    {
        $this->novaTaxonomia('cor', 'Cor');
        $azul = $this->termo('Azul', 'cor');
        $this->actingAs($this->funcionario());

        // Família: Categoria, Subcategoria, Subclassificação, Marca e Cor
        Livewire::test(CreateFamilia::class)
            ->fillForm(['tipo' => 'familia'])
            ->assertFormFieldExists('termos.cor')
            ->assertFormFieldExists('termos.sub')
            ->assertFormFieldExists('fabricante_id')
            ->fillForm(['tipo' => 'projeto'])
            ->assertFormFieldExists('termos.tipo')
            ->assertFormFieldDoesNotExist('termos.cor')
            ->assertFormFieldDoesNotExist('fabricante_id');

        // Salvar um item com a nova taxonomia
        Livewire::test(CreateFamilia::class)
            ->fillForm([
                'tipo' => 'familia', 'nome' => 'Cadeira Azul', 'slug' => 'cadeira-azul', 'ativo' => true,
                'termos' => ['cor' => [$azul->id]], 'imagem_origem' => 'url', 'arquivo_origem' => 'url',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(['Azul'], Familia::where('slug', 'cadeira-azul')->first()->categorias->pluck('nome')->all());
    }

    public function test_termos_de_outro_tipo_sao_ignorados_ao_salvar(): void
    {
        $pisos = $this->termo('Pisos', 'cat', TipoItem::Textura);
        $this->actingAs($this->funcionario());

        Livewire::test(CreateFamilia::class)
            ->fillForm([
                'tipo' => 'familia', 'nome' => 'Item', 'slug' => 'item', 'ativo' => true,
                'termos' => ['cat' => [$pisos->id]], 'imagem_origem' => 'url', 'arquivo_origem' => 'url',
            ])
            ->call('create');

        $this->assertSame(0, Familia::where('slug', 'item')->first()->categorias()->count());
    }

    public function test_administrador_cadastra_taxonomia_e_valida_o_slug(): void
    {
        $this->actingAs($this->funcionario());

        $dados = ['tipo_item' => 'textura', 'fonte' => 'termos', 'nome' => 'Cor', 'nome_plural' => 'Cores', 'ordem' => 3, 'ativa' => true];

        Livewire::test(ManageTaxonomias::class)
            ->callAction('create', $dados + ['slug' => 'cor'])
            ->assertHasNoActionErrors();

        $cor = $this->taxonomia('cor', TipoItem::Textura);
        $this->assertSame(FonteTaxonomia::Termos, $cor->fonte);

        // slug reservado, repetido na biblioteca e "marca" para lista própria
        foreach (['busca', 'cor', 'marca', 'Slug Inválido'] as $invalido) {
            Livewire::test(ManageTaxonomias::class)
                ->callAction('create', $dados + ['slug' => $invalido])
                ->assertHasActionErrors(['slug']);
        }

        // mesmo slug em outra biblioteca é permitido
        Livewire::test(ManageTaxonomias::class)
            ->callAction('create', ['tipo_item' => 'projeto'] + $dados + ['slug' => 'cor'])
            ->assertHasNoActionErrors();
    }

    public function test_editor_nao_gerencia_taxonomias(): void
    {
        $this->actingAs($this->funcionario(\App\Enums\Funcao::Editor))->get('/admin/taxonomias')->assertForbidden();
        $this->actingAs($this->funcionario())->get('/admin/taxonomias')->assertOk();
    }

    public function test_taxonomia_inativa_some_do_site_e_excluir_remove_os_termos(): void
    {
        $cor  = $this->novaTaxonomia('cor', 'Cor');
        $azul = $this->termo('Azul', 'cor');
        $this->familia('Carro Azul', [$azul]);

        $cor->update(['ativa' => false]);
        Livewire::test(FamiliaGrid::class, ['biblioteca' => 'familia', 'query' => ['cor' => 'azul']])
            ->assertSet('filtros', [])
            ->assertDontSee('Cores');

        $cor->delete();
        $this->assertDatabaseMissing('categorias', ['id' => $azul->id]);
        $this->assertDatabaseHas('familias', ['nome' => 'Carro Azul']);
    }
}
