<?php

namespace Tests\Feature\Admin;

use App\Enums\Funcao;
use App\Enums\StatusConta;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Tests\Concerns\CriaCatalogo;
use Tests\TestCase;

class AcessoPainelTest extends TestCase
{
    use CriaCatalogo, DatabaseTruncation;

    private const COMUNS = ['/admin', '/admin/familias', '/admin/familias/create', '/admin/categorias', '/admin/fabricantes', '/admin/usuarios'];

    private const SO_ADMIN = ['/admin/funcionarios', '/admin/planos', '/admin/auditorias'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCatalogo();
    }

    public function test_visitante_vai_para_o_login_do_painel(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_cliente_nao_acessa_o_painel(): void
    {
        $this->actingAs($this->usuario('premium'))->get('/admin')->assertForbidden();
    }

    public function test_administrador_acessa_tudo(): void
    {
        $this->actingAs($this->funcionario(Funcao::Administrador));

        foreach ([...self::COMUNS, ...self::SO_ADMIN] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_editor_nao_gerencia_funcionarios_planos_nem_auditoria(): void
    {
        $this->actingAs($this->funcionario(Funcao::Editor));

        foreach (self::COMUNS as $url) {
            $this->get($url)->assertOk();
        }

        foreach (self::SO_ADMIN as $url) {
            $this->get($url)->assertForbidden();
        }
    }

    public function test_funcionario_bloqueado_perde_o_acesso(): void
    {
        $editor = $this->funcionario(Funcao::Editor);
        $editor->forceFill(['status' => StatusConta::Bloqueado])->save();

        $this->actingAs($editor)->get('/admin/familias')->assertForbidden();
    }
}
