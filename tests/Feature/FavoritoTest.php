<?php

namespace Tests\Feature;

use App\Http\Livewire\FamiliaGrid;
use App\Models\Familia;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Livewire\Livewire;
use Tests\Concerns\CriaCatalogo;
use Tests\TestCase;

class FavoritoTest extends TestCase
{
    use CriaCatalogo, DatabaseTruncation;

    private Familia $a;

    private Familia $b;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCatalogo();

        $this->a = $this->familia('Cadeira Azul');
        $this->b = $this->familia('Mesa Verde');
    }

    public function test_visitante_nao_favorita_nem_ve_a_pagina(): void
    {
        $this->postJson(route('favoritos.alternar', $this->a))->assertUnauthorized();
        $this->get(route('favoritos.index'))->assertRedirect(route('login'));
        $this->assertDatabaseCount('favoritos', 0);
    }

    public function test_alterna_favorito_e_nao_duplica(): void
    {
        $user = $this->usuario('gratis');

        $this->actingAs($user)->postJson(route('favoritos.alternar', $this->a))->assertOk()->assertJson(['favorito' => true]);
        $this->assertDatabaseHas('favoritos', ['user_id' => $user->id, 'familia_id' => $this->a->id]);

        $this->postJson(route('favoritos.alternar', $this->a))->assertOk()->assertJson(['favorito' => false]);
        $this->assertDatabaseCount('favoritos', 0);
    }

    public function test_item_inativo_nao_pode_ser_favoritado(): void
    {
        $this->b->update(['ativo' => false]);

        $this->actingAs($this->usuario('gratis'))->postJson(route('favoritos.alternar', $this->b))->assertNotFound();
    }

    public function test_pagina_lista_so_os_favoritos_do_usuario_mais_recentes_primeiro(): void
    {
        $eu = $this->usuario('gratis');
        $outro = $this->usuario('basic');

        $eu->favoritos()->attach($this->a->id, ['created_at' => now()->subDay(), 'updated_at' => now()->subDay()]);
        $eu->favoritos()->attach($this->b->id);
        $outro->favoritos()->attach($this->familia('Sofá Alheio')->id);

        $this->actingAs($eu)->get(route('favoritos.index'))
            ->assertOk()
            ->assertSeeInOrder(['Mesa Verde', 'Cadeira Azul'])
            ->assertDontSee('Sofá Alheio');
    }

    public function test_pagina_vazia_convida_a_explorar(): void
    {
        $this->actingAs($this->usuario('gratis'))->get(route('favoritos.index'))
            ->assertOk()->assertSee('Você ainda não favoritou nada.');
    }

    public function test_grid_marca_o_coracao_dos_favoritados(): void
    {
        $user = $this->usuario('gratis');
        $user->favoritos()->attach($this->a->id);

        $html = Livewire::actingAs($user)->test(FamiliaGrid::class, ['biblioteca' => 'familia', 'query' => []])->html();

        $this->assertSame(1, substr_count($html, 'ativo\\u0022:true'));
        $this->assertSame(1, substr_count($html, 'ativo\\u0022:false'));
    }

    public function test_pagina_do_item_tem_o_botao(): void
    {
        $this->get($this->a->urlPublica())->assertOk()->assertSee('rvFavorito', false);
    }

    public function test_excluir_usuario_ou_item_limpa_os_favoritos(): void
    {
        $user = $this->usuario('gratis');
        $user->favoritos()->attach($this->a->id);

        $this->a->delete();
        $this->assertDatabaseCount('favoritos', 0);
    }
}
