# Importação do WordPress — situação e plano

## Já implementado: itens (`reviteiros:import-wp`)
`app/Console/Commands/ImportarWp.php` + `WpExportReader` (lê WXR). Idempotente por `familias.wp_post_id`.
Opções: `--file` (relativo à raiz do projeto), `--tipo=familia|textura|projeto`, `--post-type`, `--ativar`, `--sem-imagens`, `--dry-run`.
Mapeia termos pelo `taxonomias.wp_taxonomia`; marca → `Fabricante`; baixa a imagem de destaque para o disco público.
`familias-export.xml` tem só `familias` (5.689) + anexos (5.952) e 12 `wp:author` (sem roles nem senhas).

## Arquivos de entrada (pasta `import/`, fora do git e do Docker)
| Arquivo | Conteúdo |
|---|---|
| `familias.2026-11-11(2).xml` | 5.768 famílias (post type `familias`) + anexos |
| `texturas.2026-11-11.xml` | 567 texturas (`texturas`), taxonomias `categoria-de-texturas` e `marca-de-texturas` |
| `projetos-2126-11-11.xml` | 29 projetos (`projetos-prontos`), taxonomias `selecione-o-projeto` (categoria) e `selecione-o-tipo-do-projeto` (tipo) |
| `wp_users.sql`, `wp_usermeta.sql` | 23.132 usuários, com roles e hashes de senha (**sensível**: nunca versionar; apagar da VPS após importar) |

## Itens: mapeamento por tipo (implementado)
| | Família | Textura | Projeto |
|---|---|---|---|
| Link de download | `link_download` | `download-links` | `link-download` → `_todosarquivos` → `arquivo-rvt` |
| Gratuita | `baixar-gratis` preenchido | termo `acessar-gratis` ou `download-gratis` preenchido | — |
| Imagem | `_thumbnail_id` | `_thumbnail_id` | 1º id de `imagem-1`/`imagens-projeto` (sem destaque) |
| Taxonomias | categorias, subcategorias, subclassificacao, selecionar-marca | categoria-de-texturas, marca-de-texturas | selecione-o-projeto, selecione-o-tipo-do-projeto |
O termo "ACESSAR GRÁTIS" não vira termo: marca `gratuita` (como na migration de taxonomias). `TaxonomiaSeeder` preenche `wp_taxonomia` de textura/projeto (também em bancos já existentes).
Não importados (sem campo equivalente): metragem, quartos, suítes, banheiros, garagem e vídeo dos projetos; galeria de imagens além da primeira; autor.

## Usuários (implementado): `reviteiros:import-usuarios`
```
php artisan reviteiros:import-usuarios --users=import/wp_users.sql --meta=import/wp_usermeta.sql [--dry-run] [--atualizar-planos]
```
- Lê os dumps SQL direto (`SqlDumpReader`, sem precisar de MySQL do WP). Roles de `wp_capabilities` → `planos.wp_role`; vários roles = plano mais alto.
- Resultado do ensaio nos arquivos reais (23.132 linhas): 23.024 novos — grátis 22.287, basic 377, plus 57, premium 303; 516 sem role de plano (`subscriber`/vazio) → grátis.
- Conta repetida pelo e-mail (104): fica a de maior plano (empate: a mais antiga). E-mail inválido/vazio (4): pulado. Nomes com acento corrompido ("FranÃ§a") são corrigidos.
- Administradores do WP (11) entram como **clientes** com o plano que tinham; definir `funcao` no painel manualmente.
- Plano dos novos: `plano_historicos` com origem `importacao` (gravado em lote). Reexecução não duplica (chave `wp_user_id`/e-mail) e não mexe em plano; `--atualizar-planos` só reaplica para quem ainda está no plano vindo da importação.
- Qualquer usuário importado pode ser reimportado sem ressuscitar senha antiga: `wp_password_hash` só é gravado na criação.

### Senhas (plano B, implementado)
`users.wp_password_hash` guarda o hash do WP (`$P$` phpass, `$wp$2y$` do WP 6.8+ ou MD5). `users.password` recebe um hash aleatório que ninguém conhece.
No login (`UserProviderComSenhaWp`, vale para o site e para o `/admin`), se a senha confere com o hash do WP ela é regravada em bcrypt e o hash antigo é apagado. "Esqueci a senha" também apaga. Verificação em `App\Support\WpPasswordHasher` (testada com o vetor oficial do phpass).

## Ordem de execução na VPS
1. `migrate` (cria `wp_password_hash`) e seeders (entrypoint já faz).
2. Famílias → texturas → projetos (`--ativar` opcional; ver `deploy/DEPLOY-VPS.md`).
3. Usuários por último.
4. Conferir contagens no painel e fazer login com uma conta antiga de cada plano.
5. Apagar `import/` da VPS (contém hashes de senha).

## Pendências / decisões
- Texturas: 23 itens têm `download-gratis` preenchido e 20 têm o termo `acessar-gratis`; entram como gratuitas pela união. Confirmar se 23 é o esperado.
- Status `private` (266 famílias) e `draft` entram inativos, a menos que use `--ativar`.
- Login por e-mail: contas WP sem e-mail válido (4) não entram.
