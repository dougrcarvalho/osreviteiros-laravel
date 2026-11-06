# Spec: Taxonomias por biblioteca

> Substitui a hierarquia fixa de 4 níveis descrita em `categorias-fabricantes.md`
> e os parâmetros fixos de `infinite-scroll.md` / `api.md`, onde conflitarem.

## Conceito

Cada tipo de item (`TipoItem`: família, textura, projeto) é uma **biblioteca** com URL própria e
suas próprias **taxonomias** (formas de classificar). Elas ficam na tabela `taxonomias` e são
editadas em **Painel › Administração › Taxonomias**; nada disso é fixo no código.

| Biblioteca | Taxonomias padrão (`TaxonomiaSeeder`) |
|---|---|
| Famílias | `cat` › `sub` (pai: cat) › `subcl` (pai: sub); `marca` (pai: cat, fonte fabricante) |
| Texturas | `cat`; `marca` (pai: cat, fonte fabricante) |
| Projetos | `cat`; `tipo` |

## Modelo de dados

```
taxonomias: id, tipo_item, slug, nome, nome_plural, pai_id, fonte, ordem, ativa, wp_taxonomia
            UNIQUE (tipo_item, slug)
categorias (termos): id, taxonomia_id, nome, slug, ordem, wp_term_id
            UNIQUE (taxonomia_id, slug)
familia_categoria (pivot item ⇄ termo)
familias.fabricante_id  → marca (fonte = fabricante)
familias.gratuita       → "acessar grátis"
```

- `slug` da taxonomia = parâmetro de URL (`?cor=azul`). Reservados: `Taxonomia::RESERVADOS`.
- `pai_id`: a taxonomia só aparece depois que o pai é escolhido; ao mudar o pai os dependentes são limpos.
  Sem pai = faceta independente (ex.: cor), sempre visível e nunca limpa por outro filtro.
- `fonte = termos`: opções vêm de `categorias`. `fonte = fabricante`: vêm de `fabricantes`
  (entidade única, com logo e página própria, compartilhada entre bibliotecas). O slug deve ser `marca`.

## Filtros

`FamiliaBuscaService::aplicarFiltros()` — tudo AND:

| Parâmetro | Significado |
|---|---|
| `biblioteca` | `familia` \| `textura` \| `projeto` (nas rotas `/familias-revit` etc. vem do caminho) |
| `gratis` | `1` = só itens "acessar grátis" |
| `marca` | slug do fabricante — vale em qualquer biblioteca |
| `busca` | nome, descrição, tipo, fabricante e nome de qualquer termo do item |
| `<slug>` | qualquer taxonomia da biblioteca; ignorado se não existir nela |

As opções de cada taxonomia (`CategoriaService::opcoes`) são os termos presentes nos itens que
atendem a todos os outros filtros ativos — assim dá para começar por uma marca, por uma cor ou
descer a hierarquia, e o resultado nunca fica vazio.

## Como adicionar uma classificação nova (ex.: "Cor" em Famílias)

1. Painel › Administração › Taxonomias › **Nova taxonomia**: biblioteca *Famílias*, nome *Cor*/*Cores*,
   slug `cor`, sem "Aparece depois de…" (independente), ordem 5.
2. Painel › Categorias › **Novo termo** (classificação *Famílias › Cor*): Azul, Vermelho…
3. No cadastro das famílias o campo **Cores** já aparece. No site, `/familias-revit?cor=azul`, a linha de
   chips "Cores", a busca ("azul") e `GET /api/familias?biblioteca=familia&cor=azul` já funcionam.

Teste de referência: `tests/Feature/TaxonomiaTest::test_nova_taxonomia_cor_funciona_sem_alterar_codigo`.

## Importação do WordPress

`taxonomias.wp_taxonomia` guarda o nome da taxonomia no WP (`categorias`, `subcategorias`,
`subclassificacao`, `selecionar-marca`). O termo `acessar-gratis` vira `familias.gratuita = true`;
`selecionar-marca` vira `Fabricante` (+ `familias.fabricante_id`). Para texturas e projetos, preencher
`wp_taxonomia` das respectivas taxonomias antes de importar.
