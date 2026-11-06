<?php

namespace Tests\Feature\Admin;

use App\Enums\Funcao;
use App\Enums\OrigemPlano;
use App\Enums\StatusConta;
use App\Filament\Resources\Usuarios\Pages\EditUsuario;
use App\Filament\Resources\Usuarios\Pages\ListUsuarios;
use App\Models\Auditoria;
use App\Models\Plano;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\Concerns\CriaCatalogo;
use Tests\TestCase;

class UsuarioAdminTest extends TestCase
{
    use CriaCatalogo, DatabaseTruncation;

    private User $aluno;

    private User $editor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCatalogo();

        $this->aluno  = $this->usuario('gratis');
        $this->editor = $this->funcionario(Funcao::Editor);
        $this->actingAs($this->editor);
    }

    public function test_alterar_plano_grava_historico_e_auditoria(): void
    {
        $premium = Plano::where('slug', 'premium')->first();

        Livewire::test(EditUsuario::class, ['record' => $this->aluno->getRouteKey()])
            ->callAction('alterarPlano', ['plano_id' => $premium->id, 'observacao' => 'Pagamento confirmado por PIX'])
            ->assertHasNoActionErrors();

        $this->aluno->refresh();
        $this->assertTrue($this->aluno->hasPlano());

        $historico = $this->aluno->planoHistoricos()->first();
        $this->assertSame(OrigemPlano::Manual, $historico->origem);
        $this->assertSame($this->editor->id, $historico->autor_id);
        $this->assertSame('Pagamento confirmado por PIX', $historico->observacao);

        $this->assertTrue(Auditoria::where('evento', 'plano_alterado')->where('autor_id', $this->editor->id)->exists());
    }

    public function test_alterar_plano_em_massa(): void
    {
        $outro = $this->usuario('gratis');
        $basic = Plano::where('slug', 'basic')->first();

        Livewire::test(ListUsuarios::class)
            ->callTableBulkAction('alterarPlanoEmMassa', [$this->aluno, $outro], ['plano_id' => $basic->id])
            ->assertHasNoTableBulkActionErrors();

        $this->assertSame(2, User::where('plano_id', $basic->id)->count());
    }

    public function test_bloquear_impede_login_e_derruba_sessao(): void
    {
        Livewire::test(EditUsuario::class, ['record' => $this->aluno->getRouteKey()])
            ->callAction('bloquear', ['motivo' => 'Compartilhamento de conta']);

        $this->aluno->refresh();
        $this->assertSame(StatusConta::Bloqueado, $this->aluno->status);
        $this->assertSame('Compartilhamento de conta', $this->aluno->bloqueio_motivo);

        // Sessão aberta é encerrada
        $this->actingAs($this->aluno)->get('/familias-revit')->assertRedirect(route('login'));

        // Novo login é recusado
        auth()->logout();
        $this->post('/login', ['email' => $this->aluno->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();

        // Reativar
        $this->actingAs($this->editor);
        Livewire::test(EditUsuario::class, ['record' => $this->aluno->getRouteKey()])->callAction('desbloquear');
        $this->assertSame(StatusConta::Ativo, $this->aluno->fresh()->status);
    }

    public function test_definir_senha(): void
    {
        Livewire::test(EditUsuario::class, ['record' => $this->aluno->getRouteKey()])
            ->callAction('definirSenha', ['senha' => 'NovaSenha#2026', 'senha_confirmation' => 'NovaSenha#2026'])
            ->assertHasNoActionErrors();

        auth()->logout();
        $this->post('/login', ['email' => $this->aluno->email, 'password' => 'NovaSenha#2026'])->assertRedirect(route('familias.index'));

        $registro = Auditoria::where('evento', 'senha_alterada')->first();
        $this->assertNotNull($registro);
        $this->assertStringNotContainsString('NovaSenha', json_encode($registro->toArray()));
    }

    public function test_enviar_link_de_redefinicao(): void
    {
        Notification::fake();

        Livewire::test(EditUsuario::class, ['record' => $this->aluno->getRouteKey()])
            ->callAction('enviarLinkSenha');

        Notification::assertSentTo($this->aluno, ResetPassword::class);
    }

    public function test_editor_nao_exclui_usuario_mas_administrador_sim(): void
    {
        Livewire::test(ListUsuarios::class)->assertTableActionHidden('delete', $this->aluno);

        $this->actingAs($this->funcionario(Funcao::Administrador));
        Livewire::test(ListUsuarios::class)->assertTableActionVisible('delete', $this->aluno);
    }

    public function test_lista_de_usuarios_nao_mostra_funcionarios(): void
    {
        Livewire::test(ListUsuarios::class)
            ->assertCanSeeTableRecords([$this->aluno])
            ->assertCanNotSeeTableRecords([$this->editor]);
    }

    public function test_login_registra_data_e_ip(): void
    {
        auth()->logout();
        $this->post('/login', ['email' => $this->aluno->email, 'password' => 'password']);

        $this->assertNotNull($this->aluno->fresh()->last_login_at);
        $this->assertNotNull($this->aluno->fresh()->last_login_ip);
    }

    public function test_alteracoes_de_clientes_nao_entram_na_auditoria(): void
    {
        auth()->logout();
        $this->post('/cadastro', [
            'name' => 'Novo Aluno', 'email' => 'novo@exemplo.com',
            'password' => 'SenhaForte123', 'password_confirmation' => 'SenhaForte123',
        ]);

        $this->assertSame(0, Auditoria::count());
        $this->assertSame(OrigemPlano::Cadastro, User::where('email', 'novo@exemplo.com')->first()->planoHistoricos()->first()->origem);
    }
}
