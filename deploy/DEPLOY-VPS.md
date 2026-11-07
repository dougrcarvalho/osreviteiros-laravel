# Deploy de homologação — osreviteiros.zivit.com.br

Pré-requisito: leia `/root/web/edge/NOVO-SITE.md` (receita "App em container próprio").

## Arquivos de deploy neste repositório
| Arquivo | Função |
|---|---|
| `Dockerfile` | Imagem multi-stage (composer → vite → FrankenPHP/PHP 8.5, escuta em `:8080`) |
| `compose.prod.yaml` | `app` (rede `proxy`, alias `osreviteiros-app`) + `mysql` (rede interna) |
| `.env.production.example` | Modelo do `.env` da VPS |
| `deploy/osreviteiros.caddy` | Copiar para `/root/web/edge/sites/osreviteiros.caddy` |
| `deploy/entrypoint.sh` | migrate + seeders de planos/taxonomias + caches, a cada start |

## Passos na VPS
1. `cd /root/web && git clone <repo> osreviteiros && cd osreviteiros`
2. `cp .env.production.example .env` e preencher senhas do banco; gerar `APP_KEY`:
   `docker compose -f compose.prod.yaml run --rm --no-deps -e RUN_MIGRATIONS=false app php artisan key:generate --show`
3. `docker compose -f compose.prod.yaml up -d --build` (o app espera o MySQL e roda as migrations sozinho).
4. `cp deploy/osreviteiros.caddy /root/web/edge/sites/` e então, em `/root/web/edge`:
   `docker compose exec caddy caddy validate --config /etc/caddy/Caddyfile` e `... caddy reload ...`
   (não precisa recriar o Caddy: nenhum volume novo).
5. DNS `osreviteiros.zivit.com.br` → VPS (Cloudflare em Full/Full strict).
6. Testar: `curl -sI https://osreviteiros.zivit.com.br/up` e `/familias-revit`.

## Dados
- O banco nasce só com planos e taxonomias. Para dados de demonstração (usuários demo, itens):
  `docker compose -f compose.prod.yaml exec app php artisan db:seed --class=DemoSeeder --force`
  (o `DatabaseSeeder` só chama o demo em ambiente local; o DemoSeeder pode ser rodado direto).
- Primeiro administrador: criar via `php artisan tinker` (campo `users.funcao = 'administrador'`; confira os valores em `app/Models/User.php`).
- **Importar o catálogo do WordPress** (detalhes e plano de usuários/texturas/projetos: `docs/specs/importacao-wp.md`):
  1. Copiar o XML para o volume (não vai no git; o `--file` é relativo a `/app`):
     `scp familias-export.xml vps:/root/web/osreviteiros/` e
     `docker compose -f compose.prod.yaml cp familias-export.xml app:/app/storage/app/familias-export.xml`
  2. Teste: `docker compose -f compose.prod.yaml exec app php artisan reviteiros:import-wp --file=storage/app/familias-export.xml --dry-run | tail`
  3. Real, em segundo plano (baixa ~5,7 mil imagens de osreviteiros.com.br; 15–30 min; a VPS precisa de saída para a internet):
     `docker compose -f compose.prod.yaml exec -d app sh -c 'php artisan reviteiros:import-wp --file=storage/app/familias-export.xml --ativar > storage/logs/import.log 2>&1'`
  4. Texturas e projetos: `--tipo=textura|projeto --post-type=<cpt do WP>` com o XML de cada um (pendente: levantar os post types e preencher `taxonomias.wp_taxonomia`).
  5. Mídia de rascunhos exige `WP_API_USER` e `WP_API_APP_PASSWORD` no `.env` (recriar o container após editar).
- **Usuários:** ainda não há importador (plano no spec acima). Não vêm no XML de itens.
- Arquivos de download (zips): `link_download` mantém a URL do WP. Para subir os zips reais, enviar por `rsync` ao volume `app-storage` (`/app/storage/app/private`, dono `www-data`) ou pelo painel.

## Atualizar
`git pull && docker compose -f compose.prod.yaml up -d --build`

## Regras
Sem `down -v`; não publicar 80/443; MySQL fora da rede `proxy`; não imprimir segredos.

---
## Prompt para o Claude Code na VPS
````
Leia /root/web/edge/NOVO-SITE.md e /root/web/osreviteiros/deploy/DEPLOY-VPS.md e siga-os.
Site novo: osreviteiros.zivit.com.br (homologação), app Laravel em container (FrankenPHP, porta interna 8080, alias osreviteiros-app), upload máximo 520MB.
1. Verifique `docker ps`, `docker logs --tail 20 caddy` e `dig +short osreviteiros.zivit.com.br`.
2. Clone/atualize o repo em /root/web/osreviteiros, crie o .env a partir de .env.production.example (gere senhas fortes e a APP_KEY; não imprima segredos) e suba com compose.prod.yaml.
3. Copie deploy/osreviteiros.caddy para /root/web/edge/sites/, rode `caddy validate` e só então `caddy reload` (sem recriar o Caddy).
4. Teste com curl -sI https://osreviteiros.zivit.com.br/up e com --resolve, e me diga status e certificado.
Regras: não derrube outros sites, sem `down -v`, não mexa em outros sites. Se algo fugir do roteiro, pare e pergunte.
````
