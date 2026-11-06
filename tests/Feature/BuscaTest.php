<?php

namespace Tests\Feature;

use App\Enums\TipoItem;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Tests\Concerns\CriaCatalogo;
use Tests\TestCase;

class BuscaTest extends TestCase
{
    use CriaCatalogo, DatabaseTruncation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCatalogo();

        $mobilia = $this->categoria('Mobília');
        $cadeira = $this->categoria('Cadeiras', 2);
        $escrit  = $this->categoria('Escritório', 3);
        $herman  = $this->marca('Herman Miller');
        $banho   = $this->categoria('Banheiro');
        $deca    = $this->marca('Deca');

        $this->familia('Aeron', [$mobilia, $cadeira, $escrit, $herman], ['descricao' => 'Ergonômica com encosto em tela']);
        $this->familia('Bacia Monobloco', [$banho, $deca]);
        $this->familia('Piso Porcelanato Cinza', [], ['tipo' => TipoItem::Textura]);
    }

    private function busca(string $termo, array $extra = []): array
    {
        return collect($this->getJson('/api/familias?'.http_build_query(['busca' => $termo] + $extra + ['biblioteca' => $extra['biblioteca'] ?? 'familia']))->assertOk()->json('data'))
            ->pluck('nome')->all();
    }

    public function test_busca_por_nome_e_descricao(): void
    {
        $this->assertSame(['Aeron'], $this->busca('aeron'));
        $this->assertSame(['Aeron'], $this->busca('ergonômica'));
    }

    public function test_busca_por_qualquer_nivel_de_categoria_e_marca(): void
    {
        $this->assertSame(['Aeron'], $this->busca('mobília'));
        $this->assertSame(['Aeron'], $this->busca('escritório'));
        $this->assertSame(['Aeron'], $this->busca('herman'));
        $this->assertSame(['Bacia Monobloco'], $this->busca('deca'));
    }

    public function test_busca_por_tipo_textura_e_projeto(): void
    {
        $this->assertSame(['Piso Porcelanato Cinza'], $this->busca('textura porcelanato', ['biblioteca' => 'textura']));
        $this->assertSame([], $this->busca('textura porcelanato'));
    }

    public function test_varias_palavras_sao_acumulativas_singular_plural_e_acento(): void
    {
        $this->assertSame(['Aeron'], $this->busca('cadeira escritorio'));
        $this->assertSame(['Aeron'], $this->busca('cadeiras'));
        $this->assertSame([], $this->busca('cadeira deca'));
    }

    public function test_busca_combina_com_filtros(): void
    {
        $this->assertSame([], $this->busca('aeron', ['cat' => 'banheiro']));
        $this->assertSame(['Aeron'], $this->busca('aeron', ['cat' => 'mobilia']));
    }

    public function test_renomear_categoria_atualiza_indice(): void
    {
        \App\Models\Categoria::where('slug', 'escritorio')->first()->update(['nome' => 'Home Office']);

        $this->assertSame(['Aeron'], $this->busca('office'));
    }
}
