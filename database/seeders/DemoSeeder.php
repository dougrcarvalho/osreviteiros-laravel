<?php

namespace Database\Seeders;

use App\Enums\Funcao;
use App\Enums\OrigemPlano;
use App\Enums\TipoItem;
use App\Models\Categoria;
use App\Models\Download;
use App\Models\Fabricante;
use App\Models\Familia;
use App\Models\Plano;
use App\Models\Taxonomia;
use App\Models\User;
use App\Services\AssinaturaService;
use App\Services\FamiliaService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Dados fictícios para desenvolvimento local (não roda em produção).
 * Os dados reais vêm da importação do WordPress (Fase 5).
 */
class DemoSeeder extends Seeder
{
    private int $seq = 0;

    public function run(FamiliaService $familias): void
    {
        $this->call(TaxonomiaSeeder::class);

        $marcas = [];
        foreach (['Toyota', 'Scania', 'Honda', 'Herman Miller', 'Tok&Stok', 'Deca', 'Docol', 'Brastemp', 'Electrolux', 'Philips', 'Portobello', 'Eliane', 'Duratex', 'Reviteiros'] as $nome) {
            $marcas[$nome] = Fabricante::updateOrCreate(['slug' => Str::slug($nome)], ['nome' => $nome]);
        }

        $this->familias($familias, $marcas);
        $this->texturas($familias, $marcas);
        $this->projetos($familias);
        $this->usuarios();
    }

    /** Famílias: categoria › subcategoria › subclassificação + marca */
    private function familias(FamiliaService $familias, array $marcas): void
    {
        $arvore = [
            'Automóveis' => ['Carros' => ['SUV', 'Sedan', 'Hatch'], 'Motos' => ['Esportiva', 'Custom'], 'Caminhões' => ['Baú', 'Cavalo Mecânico']],
            'Mobília'    => ['Cadeiras' => ['Escritório', 'Jantar'], 'Mesas' => ['Escritório', 'Centro'], 'Estantes' => ['Livros', 'Nicho']],
            'Banheiro'   => ['Louças' => ['Bacia', 'Cuba'], 'Metais' => ['Torneira', 'Chuveiro']],
            'Cozinha'    => ['Eletrodomésticos' => ['Geladeira', 'Fogão'], 'Armários' => ['Aéreo', 'Balcão']],
            'Iluminação' => ['Luminárias' => ['Pendente', 'Arandela']],
            'Vegetação'  => ['Árvores' => ['Frutíferas', 'Palmeiras']],
        ];

        $marcaDe = [
            'Automóveis' => ['Toyota', 'Scania', 'Honda'],
            'Mobília'    => ['Herman Miller', 'Tok&Stok', 'Reviteiros'],
            'Banheiro'   => ['Deca', 'Docol'],
            'Cozinha'    => ['Brastemp', 'Electrolux'],
            'Iluminação' => ['Philips'],
            'Vegetação'  => ['Reviteiros'],
        ];

        [$cat, $sub, $subcl] = [$this->taxonomia('familia', 'cat'), $this->taxonomia('familia', 'sub'), $this->taxonomia('familia', 'subcl')];

        foreach ($arvore as $nomeCat => $subs) {
            $termoCat = $this->termo($cat, $nomeCat);

            foreach ($subs as $nomeSub => $subcls) {
                $termoSub = $this->termo($sub, $nomeSub);

                foreach ($subcls as $nomeSubcl) {
                    $termoSubcl = $this->termo($subcl, $nomeSubcl);

                    foreach (range(1, 4) as $i) {
                        $this->seq++;
                        $marca = $marcas[$marcaDe[$nomeCat][$this->seq % count($marcaDe[$nomeCat])]];

                        $this->item($familias, TipoItem::Familia, "{$nomeSub} {$nomeSubcl} {$marca->nome} {$i}", "Modelo paramétrico de {$nomeSub} ({$nomeSubcl}).", $marca, [$termoCat, $termoSub, $termoSubcl]);
                    }
                }
            }
        }
    }

    /** Texturas: categoria + marca */
    private function texturas(FamiliaService $familias, array $marcas): void
    {
        $cat = $this->taxonomia('textura', 'cat');

        $porCategoria = [
            'Pisos'         => ['Portobello', 'Eliane', 'Duratex'],
            'Revestimentos' => ['Portobello', 'Eliane'],
            'Madeira'       => ['Duratex', 'Reviteiros'],
            'Pedra'         => ['Eliane', 'Reviteiros'],
        ];

        foreach ($porCategoria as $nomeCat => $nomesMarca) {
            $termo = $this->termo($cat, $nomeCat);

            foreach ($nomesMarca as $nomeMarca) {
                foreach (range(1, 3) as $i) {
                    $this->seq++;
                    $this->item($familias, TipoItem::Textura, "Textura {$nomeCat} {$nomeMarca} {$i}", "Textura de {$nomeCat} para materiais do Revit.", $marcas[$nomeMarca], [$termo]);
                }
            }
        }
    }

    /** Projetos: categoria + tipo (sem marca) */
    private function projetos(FamiliaService $familias): void
    {
        [$cat, $tipo] = [$this->taxonomia('projeto', 'cat'), $this->taxonomia('projeto', 'tipo')];

        $combinacoes = [
            'Residencial' => ['Casa', 'Sobrado', 'Apartamento'],
            'Comercial'   => ['Loja', 'Escritório'],
            'Industrial'  => ['Galpão'],
        ];

        foreach ($combinacoes as $nomeCat => $tipos) {
            $termoCat = $this->termo($cat, $nomeCat);

            foreach ($tipos as $nomeTipo) {
                $termoTipo = $this->termo($tipo, $nomeTipo);

                foreach (range(1, 2) as $i) {
                    $this->seq++;
                    $this->item($familias, TipoItem::Projeto, "Projeto {$nomeTipo} {$nomeCat} {$i}", "Projeto pronto de {$nomeTipo} ({$nomeCat}).", null, [$termoCat, $termoTipo]);
                }
            }
        }
    }

    private function item(FamiliaService $familias, TipoItem $tipo, string $nome, string $descricao, ?Fabricante $marca, array $termos): void
    {
        $familia = Familia::updateOrCreate(['slug' => Str::slug($nome)], [
            'tipo'          => $tipo,
            'nome'          => $nome,
            'descricao'     => $descricao,
            'imagem_url'    => "https://picsum.photos/seed/rv{$this->seq}/600/600",
            'link_download' => 'https://example.com/downloads/'.Str::slug($nome).'.zip',
            'curtidas'      => ($this->seq * 7) % 97,
            'fabricante_id' => $marca?->id,
            'gratuita'      => $this->seq % 5 === 0,
            'ativo'         => true,
        ]);
        $familia->forceFill(['created_at' => now()->subDays($this->seq * 2)])->saveQuietly();

        $familias->sincronizarCategorias($familia, collect($termos)->pluck('id')->all());
    }

    private function taxonomia(string $tipo, string $slug): Taxonomia
    {
        return Taxonomia::where('tipo_item', $tipo)->where('slug', $slug)->firstOrFail();
    }

    private function termo(Taxonomia $taxonomia, string $nome): Categoria
    {
        return Categoria::firstOrCreate(
            ['taxonomia_id' => $taxonomia->id, 'slug' => Str::slug($nome)],
            ['nome' => $nome],
        );
    }

    private function usuarios(): void
    {
        $assinaturas = app(AssinaturaService::class);

        foreach (['gratis', 'basic', 'premium'] as $slug) {
            $user = User::updateOrCreate(['email' => "{$slug}@reviteiros.test"], [
                'name'     => 'Teste '.ucfirst($slug),
                'password' => 'password',
            ]);
            $assinaturas->alterarPlano($user, Plano::where('slug', $slug)->first(), OrigemPlano::Cadastro);
        }

        // Funcionários do painel /admin (senha "password")
        User::updateOrCreate(['email' => 'admin@reviteiros.test'], [
            'name' => 'Admin Reviteiros', 'password' => 'password', 'funcao' => Funcao::Administrador,
        ]);
        User::updateOrCreate(['email' => 'editor@reviteiros.test'], [
            'name' => 'Editor Reviteiros', 'password' => 'password', 'funcao' => Funcao::Editor,
        ]);

        // Alunos fictícios espalhados nos últimos 12 meses + downloads (dashboard)
        $planos = Plano::pluck('id', 'slug');
        $ids    = Familia::pluck('id');

        foreach (range(1, 80) as $i) {
            $slug = $i % 7 === 0 ? 'premium' : ($i % 4 === 0 ? 'basic' : ($i % 11 === 0 ? 'plus' : 'gratis'));
            $user = User::factory()->create([
                'plano_id'      => $planos[$slug],
                'created_at'    => now()->subDays(($i * 4) % 365),
                'last_login_at' => $i % 3 ? now()->subDays($i % 20) : null,
            ]);

            foreach (range(1, $i % 6) as $_) {
                Download::create(['familia_id' => $ids->random(), 'user_id' => $user->id, 'created_at' => now()->subDays(random_int(0, 45))]);
            }
        }

        foreach (Familia::withCount('downloads')->get() as $familia) {
            $familia->forceFill(['downloads_count' => $familia->downloads_count])->saveQuietly();
        }
    }
}
