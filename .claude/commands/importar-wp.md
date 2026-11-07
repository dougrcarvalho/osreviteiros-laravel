# Comando: /importar-wp

Importação do WordPress para o Laravel. Plano completo (usuários, texturas, projetos): `docs/specs/importacao-wp.md`.

## Itens (famílias, texturas, projetos) — implementado

1. No WP: Ferramentas → Exportar (um XML por tipo de conteúdo; inclua os anexos para as imagens).
2. Copie o XML para a raiz do projeto (o `--file` é relativo a ela; XMLs não vão para o git).
3. Rode via Sail (local) ou `docker compose -f compose.prod.yaml exec app` (VPS):

```bash
php artisan reviteiros:import-wp --file=familias-export.xml --dry-run            # confere sem gravar
php artisan reviteiros:import-wp --file=familias-export.xml --ativar             # famílias (post-type padrão: familias)
php artisan reviteiros:import-wp --file=texturas.xml --tipo=textura --post-type=<cpt> --ativar
php artisan reviteiros:import-wp --file=projetos.xml --tipo=projeto --post-type=<cpt> --ativar
```

Opções: `--ativar` (rascunhos entram ativos), `--sem-imagens`, `--dry-run`. Idempotente (`wp_post_id`).
Termos são mapeados por `taxonomias.wp_taxonomia`; aviso "taxonomia WP sem equivalente" = falta preencher esse campo.
Imagens vêm de `WP_URL` (padrão osreviteiros.com.br); mídia de rascunho exige `WP_API_USER` + `WP_API_APP_PASSWORD`.
`link_download` é gravado como veio do WP (URL absoluta).

## Usuários — ainda não implementado
Ver "Plano 1" em `docs/specs/importacao-wp.md` (roles → planos via `planos.wp_role`, `AssinaturaService`, senhas phpass).

## Verificar
```bash
php artisan tinker
>>> App\Models\Familia::selectRaw('tipo, count(*) n')->groupBy('tipo')->get()
```
