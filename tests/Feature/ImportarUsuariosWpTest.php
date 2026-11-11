<?php

namespace Tests\Feature;

use App\Enums\OrigemPlano;
use App\Models\PlanoHistorico;
use App\Models\User;
use App\Services\Importacao\SqlDumpReader;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Tests\Concerns\CriaCatalogo;
use Tests\TestCase;

class ImportarUsuariosWpTest extends TestCase
{
    use CriaCatalogo, DatabaseTruncation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCatalogo();
    }

    private function importar(array $extra = []): void
    {
        $this->artisan('reviteiros:import-usuarios', $extra + [
            '--users' => 'tests/Fixtures/wp_users.sql',
            '--meta'  => 'tests/Fixtures/wp_usermeta.sql',
        ])->assertSuccessful();
    }

    public function test_leitor_de_dump_trata_aspas_quebras_e_virgulas(): void
    {
        $linhas = iterator_to_array((new SqlDumpReader)->linhas(base_path('tests/Fixtures/wp_usermeta.sql'), 'wp_usermeta'), false);

        $this->assertCount(6, $linhas);
        $this->assertSame("linha 1\nlinha 2, com (parênteses) e 'aspas'", $linhas[3]['meta_value']);
        $this->assertSame('a:1:{s:16:"reviteiroa_basic";b:1;}', $linhas[0]['meta_value']);
    }

    public function test_importa_usuarios_com_plano_do_role(): void
    {
        $this->importar();

        $this->assertSame(4, User::count()); // inválido e duplicado ficam de fora
        $this->assertSame('basic', User::where('email', 'ana@exemplo.com')->first()->plano->slug);
        $this->assertSame('premium', User::where('email', 'bia@exemplo.com')->first()->plano->slug);
        $this->assertSame('premium', User::where('email', 'eva@exemplo.com')->first()->plano->slug); // admin + premium
        $this->assertSame('gratis', User::where('email', 'fabio@exemplo.com')->first()->plano->slug);
        $this->assertNull(User::where('email', 'eva@exemplo.com')->first()->funcao); // admin do WP não vira funcionário
        $this->assertSame('Ana França', User::where('email', 'ana@exemplo.com')->first()->name);
        $this->assertSame(4, PlanoHistorico::where('origem', OrigemPlano::Importacao)->count());
    }

    public function test_reimportar_nao_duplica_nem_ressuscita_senha_antiga(): void
    {
        $this->importar();
        $ana = User::where('email', 'ana@exemplo.com')->first();
        $ana->forceFill(['wp_password_hash' => null])->save(); // já converteu a senha

        $this->importar();

        $this->assertSame(4, User::count());
        $this->assertNull($ana->fresh()->wp_password_hash);
    }

    public function test_login_com_senha_do_wp_converte_para_bcrypt(): void
    {
        $this->importar();
        $ana = User::where('email', 'ana@exemplo.com')->first();
        $this->assertNotNull($ana->wp_password_hash);

        $this->post('/login', ['email' => 'ana@exemplo.com', 'password' => 'test12345'])->assertRedirect();

        $this->assertAuthenticatedAs($ana);
        $ana->refresh();
        $this->assertNull($ana->wp_password_hash);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('test12345', $ana->password));
    }

    public function test_login_com_senha_errada_falha(): void
    {
        $this->importar();

        $this->post('/login', ['email' => 'ana@exemplo.com', 'password' => 'errada'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_dry_run_nao_grava(): void
    {
        $this->importar(['--dry-run' => true]);

        $this->assertSame(0, User::count());
    }
}
