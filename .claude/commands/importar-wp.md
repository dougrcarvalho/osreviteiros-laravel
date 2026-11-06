# Comando: /importar-wp

Guia de importação dos dados do WordPress para o banco Laravel.

## Passo a passo

### 1. Exportar dados do WordPress

No painel WP Admin:
- Ferramentas → Exportar → Exportar tudo → Baixar arquivo XML

Ou via WP-CLI:
```bash
wp export --dir=/tmp/wp-export --post_type=familias
wp export --dir=/tmp/wp-export --post_type=all  # usuários + taxonomias
```

### 2. Rodar o comando de importação

```bash
# Importar categorias e taxonomias primeiro
php artisan reviteiros:importar-categorias --xml=wp-export.xml

# Importar fabricantes (selecionar-marca)
php artisan reviteiros:importar-fabricantes --xml=wp-export.xml

# Importar famílias com metadados
php artisan reviteiros:importar-familias --xml=wp-export.xml

# Importar usuários (sem senhas — exige reset)
php artisan reviteiros:importar-usuarios --xml=wp-export.xml
```

### 3. Mapeamento de roles WP → planos Laravel

| Role WP | Plano Laravel |
|---|---|
| `usurio_grtis` | `gratis` |
| `reviteiroa_basic` | `basic` |
| `reviteiro_plus` | `plus` |
| `reviteiro_premium` | `premium` |

### 4. Verificar importação

```bash
php artisan tinker
>>> Familia::count()     # deve bater com o total WP
>>> Categoria::count()   # todas taxonomias
>>> User::count()        # todos usuários
```

## Notas

- Senhas WP usam hash phppass — **incompatível** com bcrypt do Laravel
- Enviar e-mail de "reset de senha" para todos após importação
- Imagens: o `imagem_url` aponta para o WP; após migração, fazer upload para novo storage (S3 ou local)
- `link_download` contém URLs absolutas — manter como estão inicialmente
