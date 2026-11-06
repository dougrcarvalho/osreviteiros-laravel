# Edge (Caddy): o que foi feito e como acrescentar um site

## O que foi feito
- O nginx + certbot que ficavam no compose de `/root/web/zivit` foram substituídos por um **Caddy** em `/root/web/edge`.
  Ele é o **único** projeto que publica as portas 80/443; HTTPS (Let's Encrypt), redirect http→https e renovação são automáticos.
- Cada site é um projeto independente. Todos se ligam pela rede Docker externa **`proxy`**.
- Sites PHP legados (catracapadaria, zivit, catraca.zivit, lavemarte, inspirare) são servidos pelo container `php`
  (alias `php-legacy:9000` na rede `proxy`). O Caddy monta o código deles em `:ro`, no **mesmo caminho** do container `php`.
- O somaleve é um app Docker próprio; o serviço `app` tem o alias `somaleve-app` na rede `proxy` e o Caddy faz `reverse_proxy somaleve-app:8080`.
- Os domínios passam pelo Cloudflare (SSL/TLS precisa estar em **Full** ou **Full (strict)**, nunca Flexible).

## Estrutura
```
/root/web/edge/
├── compose.yaml        # caddy + volumes dos sites PHP (:ro) + rede proxy
├── Caddyfile           # snippet (php_site) + "import sites/*.caddy"
├── sites/<site>.caddy  # um arquivo por site
└── .env                # ACME_EMAIL
```

## Receitas por tipo de site

PHP legado (arquivos em /root/web/<pasta>, servidos pelo container `php`):
```
meusite.com.br, www.meusite.com.br {
	root * /var/www/<pasta>          # use <pasta>/public se for Laravel
	import php_site
}
```
Exige: (1) a pasta montada no container `php` (compose do zivit) em `/var/www/<pasta>`;
(2) a mesma pasta montada `:ro` no Caddy em `edge/compose.yaml`, no mesmo caminho.

App em container próprio (Laravel/Node/etc.):
```
app.meusite.com.br {
	encode zstd gzip
	reverse_proxy <alias-do-app>:<porta>
}
```
Exige: o container do app entrar na rede externa `proxy` com um alias **único**
(`networks: { proxy: { aliases: [<alias-do-app>] } }` e `networks: { proxy: { external: true } }`).
Não ponha banco/Redis na rede `proxy`; ficam na rede interna do projeto.

## Passos para acrescentar um site
1. DNS do domínio apontando para a VPS (Cloudflare: Full/Full strict).
2. Crie `edge/sites/<site>.caddy` (receita acima).
3. Se for PHP legado, adicione o volume nos dois lugares (compose do `php` e do `edge`) e recrie o `php`.
4. Valide e recarregue **sem derrubar os outros sites**:
   ```
   cd /root/web/edge
   docker compose exec caddy caddy validate --config /etc/caddy/Caddyfile
   docker compose exec caddy caddy reload --config /etc/caddy/Caddyfile
   ```
   (Se acrescentou volume novo, é preciso `docker compose up -d` para recriar o Caddy: causa um blip de segundos.)
5. Acompanhe `docker logs --tail 50 caddy` até o certificado ser obtido e teste com `curl -sI https://<dominio>`.

## Regras
- Só o Caddy publica 80/443. Nenhum outro container usa `ports: 80/443`.
- Não use `docker compose down -v` nem apague o volume `caddy-data` (guarda os certificados).
- Não altere o código dos outros sites.
- Backup do `edge/` e do volume `caddy-data` fazem parte dos backups da VPS.

---

## Prompt para o Claude Code na VPS (copie, preencha os campos <...>)
````
Preciso acrescentar um site novo à estrutura de borda desta VPS. Leia primeiro /root/web/edge/NOVO-SITE.md e siga as receitas e regras dele.

Site novo:
- Domínio(s): <dominio, www.dominio>
- Tipo: <PHP legado em /root/web/<pasta> (public? sim/não)> OU <app em container: pasta do projeto, porta interna, compose a usar>
- Observações: <upload máximo, redirects, www→apex, etc.>

Faça:
1. Verifique o estado atual (`docker ps`, `docker logs --tail 20 caddy`) e confirme que o DNS do domínio aponta para esta VPS (`dig +short`).
2. Crie edge/sites/<site>.caddy; se for PHP legado, acrescente o volume no compose do php e no edge/compose.yaml; se for app em container, coloque-o na rede externa `proxy` com alias único e suba-o.
3. Valide (`caddy validate`) antes de recarregar; use `caddy reload` (sem derrubar os outros sites). Se precisar recriar o Caddy por causa de volume novo, me avise antes.
4. Teste com `curl -sI` e `--resolve <dominio>:443:127.0.0.1`, e me diga o status e se o certificado foi emitido.
Regras: não derrube os sites existentes, não use `down -v`, não mexa no código de outros sites e não imprima segredos. Se algo fugir do roteiro, pare e pergunte.
````
