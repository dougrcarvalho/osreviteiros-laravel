# Importação do WordPress — situação e plano

## Já implementado: itens (`reviteiros:import-wp`)
`app/Console/Commands/ImportarWp.php` + `WpExportReader` (lê WXR). Idempotente por `familias.wp_post_id`.
Opções: `--file` (relativo à raiz do projeto), `--tipo=familia|textura|projeto`, `--post-type`, `--ativar`, `--sem-imagens`, `--dry-run`.
Mapeia termos pelo `taxonomias.wp_taxonomia`; marca → `Fabricante`; baixa a imagem de destaque para o disco público.
`familias-export.xml` tem só `familias` (5.689) + anexos (5.952) e 12 `wp:author` (sem roles nem senhas).

## Plano 1 — Usuários com planos (roles)

**Fonte dos dados.** O XML do WP **não** traz roles nem hash de senha. Extrair direto do banco do WP (ou WP-CLI):
```
wp user list --fields=ID,user_login,user_email,display_name,user_registered,roles,user_pass --format=csv > usuarios-wp.csv
```
(SQL equivalente: `wp_users` + `wp_usermeta` onde `meta_key = 'wp_capabilities'`; ajustar o prefixo.)
Arquivo contém hashes: não versionar, transferir por scp e apagar após a importação.
Pontos a confirmar no WP: (a) o plugin de assinaturas (os metadados `ihc_*` indicam Indeed Ultimate Membership Pro) guarda nível/validade em tabelas próprias — checar se o role reflete a assinatura vigente; (b) usuários com mais de um role.

**Mapeamento role → plano** (já em `planos.wp_role`, via `PlanoSeeder`):
| Role WP | Plano |
|---|---|
| `usurio_grtis` | gratis |
| `reviteiroa_basic` | basic |
| `reviteiro_plus` | plus (descontinuado, tratado como Basic) |
| `reviteiro_premium` | premium |
| `administrator` / `editor` / outros | **não** viram plano nem função automaticamente: listar no relatório e decidir (funcionário = `users.funcao`, criado à mão ou por opção `--funcionarios`) |
| sem role conhecido | gratis + aviso no relatório |

**Comando novo `reviteiros:import-usuarios --file=usuarios-wp.csv [--dry-run] [--enviar-reset]`**
1. Ler o CSV; normalizar e-mail (trim/lowercase); pular e-mail inválido ou duplicado (relatório).
2. Idempotência: chave `wp_user_id` (coluna já existe), com fallback por e-mail.
3. Criar/atualizar `users`: `name` (display_name ou login), `email`, `wp_user_id`, `created_at = user_registered`, `status = ativo`, `funcao = null`.
4. Plano **somente** via `AssinaturaService::alterarPlano($user, $plano, OrigemPlano::Importacao, referencia: "wp:{id}")` (grava `plano_historicos`; auditoria em lote com `semRegistro()`).
5. Reexecução não rebaixa nem altera plano de quem já mudou por webhook/painel: só aplica plano se o usuário é novo ou ainda está no plano vindo de importação anterior.
6. Relatório final: totais por plano, ignorados (motivo), roles desconhecidos, contas de equipe encontradas.

**Senhas (decisão necessária).**
- Opção A (simples): `password` aleatório + e-mail de reset (`PasswordResetController` já existe e cita usuários do WP). Exige SMTP real e dispara e-mail em massa → enviar em lotes.
- Opção B (melhor para o usuário): guardar o hash phpass (`$P$…`) numa coluna `wp_password_hash`; no login, se a senha bate com o phpass, regravar em bcrypt e apagar o hash legado. Sem e-mail em massa; exige implementar a verificação phpass (≈40 linhas) no fluxo de login e do Filament.
- Recomendação: B, com A como contingência (link "esqueci a senha" já funciona).

**Fora do escopo:** vencimento de assinatura (o sistema não tem; vale até webhook/funcionário trocar), curtidas/favoritos por usuário, histórico de downloads do WP.

**Testes:** parser do CSV, mapeamento de roles, idempotência, não-rebaixamento, e-mail duplicado, `plano_historicos` com origem `importacao`.
**Ensaio:** rodar com `--dry-run` no export real e conferir os totais por role contra o painel do WP antes de importar.

## Plano 2 — Texturas e projetos

**Pendências a levantar no WP (preciso de você):**
1. Nome do *post type* de cada um (texturas, projetos prontos) e das taxonomias usadas por eles.
2. Exportar cada tipo em Ferramentas → Exportar (um XML por tipo, mais os anexos) — ou "Todo o conteúdo", que traz os anexos das imagens.
3. Meta de download: confirmar se também usam `link_download`/`baixar-gratis` (o comando lê essas chaves).

**Ajustes no código:**
1. `TaxonomiaSeeder`: hoje textura e projeto têm `wp_taxonomia = null`, então o import avisaria "taxonomia WP sem equivalente". Preencher com os slugs reais do WP (textura: categoria e marca; projeto: categoria e tipo). Como o seeder só cria o que falta, atualizar as já existentes por migration/`Taxonomia::where(...)->update(...)` ou pelo painel (Administração › Taxonomias).
2. `ImportarWp`: aceitar o mesmo `DOMINIO_ALIAS` por tipo, se o WP usar domínios diferentes; sem outras mudanças previstas (já recebe `--tipo` e `--post-type`).
3. Projetos são só Premium (`Plano::liberaTipo`): conferir que `link_download` dos projetos vem preenchido, pois sem o arquivo o botão falha.

**Execução (VPS):**
```
php artisan reviteiros:import-wp --file=... --tipo=textura --post-type=<cpt> --dry-run   # conferir avisos de taxonomia
php artisan reviteiros:import-wp --file=... --tipo=textura --post-type=<cpt> --ativar
php artisan reviteiros:import-wp --file=... --tipo=projeto --post-type=<cpt> --ativar
```
Marcas (`Fabricante`) são compartilhadas: o `firstOrCreate` por slug evita duplicar as já criadas pelas famílias.
**Verificação:** contagem por tipo no painel vs. WP, itens sem imagem (aviso do comando), amostra de 10 itens por biblioteca (URL pública, filtros, download conforme o plano).

## Ordem sugerida
1. Famílias (XML atual) → 2. levantar CPTs/taxonomias de texturas e projetos → 3. ajustar `wp_taxonomia` e importar → 4. extrair usuários, decidir senha (A/B) → 5. implementar e ensaiar `import-usuarios` → 6. importar usuários por último (evita e-mail de reset antes do catálogo estar no ar).
