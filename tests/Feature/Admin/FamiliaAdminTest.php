<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\Familias\Pages\CreateFamilia;
use App\Filament\Resources\Familias\Pages\EditFamilia;
use App\Models\Auditoria;
use App\Models\Categoria;
use App\Models\Familia;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Concerns\CriaCatalogo;
use Tests\TestCase;

class FamiliaAdminTest extends TestCase
{
    use CriaCatalogo, DatabaseTruncation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCatalogo();
        Storage::fake(config('reviteiros.arquivos.disk'));
        Storage::fake(config('reviteiros.imagens.disk'));
    }

    private function criarComUpload(): Familia
    {
        $mobilia = $this->categoria('Mobília');
        $cadeira = $this->categoria('Cadeiras', 2);
        $herman  = $this->marca('Herman Miller');

        $this->actingAs($this->funcionario());

        Livewire::test(CreateFamilia::class)
            ->fillForm([
                'tipo'           => 'familia',
                'nome'           => 'Cadeira Aeron',
                'slug'           => 'cadeira-aeron',
                'descricao'      => 'Ergonômica',
                'ativo'          => true,
                'termos'         => ['cat' => [$mobilia->id], 'sub' => [$cadeira->id]],
                'fabricante_id'  => $herman->id,
                'imagem_origem'  => 'upload',
                'imagem_upload'  => UploadedFile::fake()->image('capa.jpg', 600, 600),
                'arquivo_origem' => 'upload',
                'arquivo_upload' => UploadedFile::fake()->create('cadeira-aeron.zip', 2048, 'application/zip'),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        return Familia::where('slug', 'cadeira-aeron')->firstOrFail();
    }

    public function test_cria_familia_com_upload_categorias_e_fabricante(): void
    {
        $familia = $this->criarComUpload();

        $this->assertSame(['cadeiras', 'mobilia'], $familia->categorias->pluck('slug')->sort()->values()->all());
        $this->assertSame('Herman Miller', $familia->fabricante?->nome);

        // Arquivo no disco privado e link para a rota protegida
        $this->assertNotNull($familia->arquivo_path);
        Storage::disk(config('reviteiros.arquivos.disk'))->assertExists($familia->arquivo_path);
        $this->assertSame(route('arquivos.download', ['path' => $familia->arquivo_path]), $familia->link_download);
        $this->assertSame('cadeira-aeron.zip', $familia->arquivo_nome);

        // Imagem pública
        Storage::disk(config('reviteiros.imagens.disk'))->assertExists($familia->imagem_path);
        $this->assertNotNull($familia->imagem_url);

        // Índice de busca e auditoria
        $this->assertStringContainsString('Herman Miller', $familia->busca_texto);
        $this->assertTrue(Auditoria::where('evento', 'criado')->where('auditavel_id', $familia->id)->whereNotNull('autor_id')->exists());
    }

    public function test_arquivo_enviado_so_e_entregue_a_quem_pode_baixar(): void
    {
        $familia = $this->criarComUpload();
        $url     = $familia->link_download;
        auth()->logout();

        // Visitante → login
        $this->get($url)->assertRedirect(route('login'));

        // Link repassado para quem tem plano grátis → 403
        $this->actingAs($this->usuario('gratis'))->get($url)->assertForbidden();
    }

    public function test_assinante_baixa_o_arquivo_enviado(): void
    {
        $familia = $this->criarComUpload();
        auth()->logout();
        $premium = $this->usuario('premium');

        $this->actingAs($premium)
            ->post(route('familias.download', $familia))
            ->assertRedirect($familia->link_download);

        $this->actingAs($premium)
            ->get($familia->link_download)
            ->assertOk()
            ->assertDownload('cadeira-aeron.zip');

        $this->assertSame(1, $familia->fresh()->downloads_count);
    }

    public function test_trocar_para_link_externo_apaga_o_arquivo(): void
    {
        $familia = $this->criarComUpload();
        $path    = $familia->arquivo_path;

        Livewire::test(EditFamilia::class, ['record' => $familia->getRouteKey()])
            ->fillForm(['arquivo_origem' => 'url', 'link_download' => 'https://drive.example.com/aeron.zip'])
            ->call('save')
            ->assertHasNoFormErrors();

        $familia->refresh();
        $this->assertNull($familia->arquivo_path);
        $this->assertSame('https://drive.example.com/aeron.zip', $familia->link_download);
        Storage::disk(config('reviteiros.arquivos.disk'))->assertMissing($path);
    }

    public function test_edicao_mantem_categorias_e_marca_gratis(): void
    {
        $familia = $this->criarComUpload();

        Livewire::test(EditFamilia::class, ['record' => $familia->getRouteKey()])
            ->assertFormSet(['termos.cat' => $familia->categorias->where('taxonomia_id', $this->taxonomia('cat')->id)->pluck('id')->values()->all(), 'gratuita' => false])
            ->fillForm(['gratuita' => true, 'nome' => 'Cadeira Aeron Remastered'])
            ->call('save')
            ->assertHasNoFormErrors();

        $familia->refresh();
        $this->assertTrue($familia->isGratuita());
        $this->assertSame(2, $familia->categorias->count());
        $this->assertSame('Herman Miller', $familia->fabricante?->nome);
        $this->assertTrue(Auditoria::where('evento', 'categorias')->where('auditavel_id', $familia->id)->exists());
        $this->assertTrue(Auditoria::where('evento', 'atualizado')->where('auditavel_id', $familia->id)->exists());
    }

    public function test_excluir_familia_apaga_os_arquivos(): void
    {
        $familia = $this->criarComUpload();
        [$arquivo, $imagem] = [$familia->arquivo_path, $familia->imagem_path];

        $familia->delete();

        Storage::disk(config('reviteiros.arquivos.disk'))->assertMissing($arquivo);
        Storage::disk(config('reviteiros.imagens.disk'))->assertMissing($imagem);
    }

    public function test_termo_criado_no_formulario_tem_slug_unico_por_taxonomia(): void
    {
        $this->actingAs($this->funcionario());

        $termo = app(\App\Services\CategoriaService::class)->criar($this->taxonomia('sub'), 'Sofás & Poltronas');

        $this->assertSame('sofas-poltronas', $termo->slug);
        $this->assertSame('Subcategoria', $termo->taxonomia->nome);

        // Slug repetido na mesma taxonomia ganha sufixo; em outra taxonomia não
        $this->assertSame('sofas-poltronas-2', app(\App\Services\CategoriaService::class)->criar($this->taxonomia('sub'), 'Sofás & Poltronas')->slug);
        $this->assertSame('sofas-poltronas', app(\App\Services\CategoriaService::class)->criar($this->taxonomia('subcl'), 'Sofás & Poltronas')->slug);

        // Marcas não têm termos próprios: usam Fabricantes
        $this->expectException(\InvalidArgumentException::class);
        app(\App\Services\CategoriaService::class)->criar($this->taxonomia('marca'), 'Tok&Stok');
    }
}
