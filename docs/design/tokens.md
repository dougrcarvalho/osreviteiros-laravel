# Design Tokens — Os Reviteiros

## Paleta de Cores

```css
:root {
    /* ── Marca ─────────────────────────────────── */
    --rv-azul-escuro:   #042C53;    /* fundo hero, header, footer */
    --rv-azul-medio:    #0A4A8A;    /* hover de itens primários */
    --rv-azul-claro:    #1A6FC4;    /* botões primários, links ativos */
    --rv-dourado:       #C9A227;    /* acento (logo, badges, CTA premium) */
    --rv-dourado-hover: #B8901F;    /* hover do dourado */

    /* ── Neutros ───────────────────────────────── */
    --rv-cinza-bg:      #F5F7FA;    /* fundo geral da página */
    --rv-cinza-card:    #FFFFFF;    /* fundo de cards */
    --rv-cinza-borda:   #E2E8F0;    /* bordas, divisores */
    --rv-cinza-hover:   #EDF2F7;    /* hover em elementos cinza */

    /* ── Texto ─────────────────────────────────── */
    --rv-texto-escuro:  #1A202C;    /* headings principais */
    --rv-texto-medio:   #4A5568;    /* corpo de texto */
    --rv-texto-claro:   #718096;    /* captions, metadados */
    --rv-texto-xclaro:  #A0AEC0;    /* placeholders */

    /* ── Estados ───────────────────────────────── */
    --rv-sucesso:       #38A169;
    --rv-erro:          #E53E3E;
    --rv-aviso:         #D69E2E;
    --rv-info:          #3182CE;

    /* ── Planos ────────────────────────────────── */
    --rv-plano-gratis:  #718096;
    --rv-plano-basic:   #1A6FC4;
    --rv-plano-plus:    #805AD5;
    --rv-plano-premium: #C9A227;
}
```

## Tipografia

```css
/* Google Fonts import */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

:root {
    --rv-font:         'Inter', system-ui, -apple-system, sans-serif;

    /* Scale */
    --rv-text-xs:      0.75rem;     /* 12px */
    --rv-text-sm:      0.875rem;    /* 14px */
    --rv-text-base:    1rem;        /* 16px */
    --rv-text-lg:      1.125rem;    /* 18px */
    --rv-text-xl:      1.25rem;     /* 20px */
    --rv-text-2xl:     1.5rem;      /* 24px */
    --rv-text-3xl:     1.875rem;    /* 30px */
    --rv-text-4xl:     2.25rem;     /* 36px */

    /* Weight */
    --rv-font-normal:  400;
    --rv-font-medium:  500;
    --rv-font-semi:    600;
    --rv-font-bold:    700;

    /* Line-height */
    --rv-leading-tight: 1.25;
    --rv-leading-base:  1.5;
    --rv-leading-loose: 1.75;
}
```

## Espaçamento

```css
:root {
    --rv-space-1:   0.25rem;   /* 4px */
    --rv-space-2:   0.5rem;    /* 8px */
    --rv-space-3:   0.75rem;   /* 12px */
    --rv-space-4:   1rem;      /* 16px */
    --rv-space-5:   1.25rem;   /* 20px */
    --rv-space-6:   1.5rem;    /* 24px */
    --rv-space-8:   2rem;      /* 32px */
    --rv-space-10:  2.5rem;    /* 40px */
    --rv-space-12:  3rem;      /* 48px */
    --rv-space-16:  4rem;      /* 64px */
}
```

## Border Radius

```css
:root {
    --rv-radius-sm:   4px;
    --rv-radius-md:   8px;
    --rv-radius-lg:   12px;
    --rv-radius-xl:   16px;
    --rv-radius-pill: 9999px;
}
```

## Sombras

```css
:root {
    --rv-shadow-sm:   0 1px 3px rgba(0,0,0,.08);
    --rv-shadow-md:   0 4px 12px rgba(0,0,0,.10);
    --rv-shadow-lg:   0 8px 24px rgba(0,0,0,.12);
    --rv-shadow-card: 0 2px 8px rgba(4,44,83,.08);
}
```

## Grid de Famílias

```css
.rv-familias-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: var(--rv-space-4);
    padding: var(--rv-space-4) 0;
}

/* Responsivo */
@media (max-width: 768px) {
    .rv-familias-grid {
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        gap: var(--rv-space-3);
    }
}
```

## Cards

```css
.rv-card {
    background: var(--rv-cinza-card);
    border: 1px solid var(--rv-cinza-borda);
    border-radius: var(--rv-radius-lg);
    box-shadow: var(--rv-shadow-card);
    overflow: hidden;
    transition: box-shadow 0.2s, transform 0.2s;
}

.rv-card:hover {
    box-shadow: var(--rv-shadow-md);
    transform: translateY(-2px);
}

/* Imagem do card */
.rv-card__imagem {
    width: 100%;
    height: 220px;
    object-fit: cover;
    display: block;
    cursor: zoom-in;
}

/* Tooltip de preview (500×500) */
.rv-img-tooltip {
    position: fixed;
    width: 500px;
    height: 500px;
    border-radius: var(--rv-radius-lg);
    box-shadow: var(--rv-shadow-lg);
    pointer-events: none;
    z-index: 9999;
    display: none;
}
```

## Botões

```css
/* Base */
.rv-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: var(--rv-space-2);
    padding: var(--rv-space-2) var(--rv-space-4);
    border-radius: var(--rv-radius-md);
    font-size: var(--rv-text-sm);
    font-weight: var(--rv-font-semi);
    line-height: 1;
    cursor: pointer;
    transition: background 0.15s, opacity 0.15s;
    text-decoration: none;
    border: none;
    width: 100%;
}

/* Variantes */
.rv-btn--baixar {
    background: var(--rv-azul-claro);
    color: #fff;
}
.rv-btn--baixar:hover { background: var(--rv-azul-medio); }

.rv-btn--cadastrar {
    background: var(--rv-sucesso);
    color: #fff;
}
.rv-btn--cadastrar:hover { opacity: 0.9; }

.rv-btn--assinar {
    background: var(--rv-dourado);
    color: #fff;
}
.rv-btn--assinar:hover { background: var(--rv-dourado-hover); }

.rv-btn--outline {
    background: transparent;
    border: 1.5px solid var(--rv-azul-claro);
    color: var(--rv-azul-claro);
}
.rv-btn--outline:hover { background: var(--rv-azul-claro); color: #fff; }
```

## Categorias (chips de navegação)

```css
.rv-categorias {
    display: flex;
    flex-wrap: wrap;
    gap: var(--rv-space-2);
    padding: var(--rv-space-4) 0;
}

.rv-categoria-chip {
    padding: var(--rv-space-1) var(--rv-space-4);
    border-radius: var(--rv-radius-pill);
    border: 1.5px solid var(--rv-cinza-borda);
    font-size: var(--rv-text-sm);
    font-weight: var(--rv-font-medium);
    color: var(--rv-texto-medio);
    background: var(--rv-cinza-card);
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.15s;
}

.rv-categoria-chip:hover,
.rv-categoria-chip--ativo {
    background: var(--rv-azul-escuro);
    border-color: var(--rv-azul-escuro);
    color: #fff;
}

/* Hierarquia: subcategorias aparecem com indent */
.rv-subcategorias { padding-left: var(--rv-space-4); }
.rv-subcategorias .rv-categoria-chip { font-size: var(--rv-text-xs); }
```

## Header / Navegação

```css
.rv-header {
    background: var(--rv-azul-escuro);
    color: #fff;
    padding: 0 var(--rv-space-6);
    height: 64px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: 0 2px 8px rgba(0,0,0,.2);
}

.rv-logo {
    height: 40px;
    width: auto;
}
```

## Popup de Planos

```css
.rv-popup-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.6);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: var(--rv-space-4);
}

.rv-popup {
    background: #fff;
    border-radius: var(--rv-radius-xl);
    padding: var(--rv-space-8);
    max-width: 640px;
    width: 100%;
    box-shadow: var(--rv-shadow-lg);
    position: relative;
}

.rv-planos-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: var(--rv-space-4);
    margin-top: var(--rv-space-6);
}

.rv-plano {
    border: 2px solid var(--rv-cinza-borda);
    border-radius: var(--rv-radius-lg);
    padding: var(--rv-space-5);
    text-align: center;
    transition: border-color 0.2s;
}

.rv-plano--destaque {
    border-color: var(--rv-dourado);
    position: relative;
}
```

## Ícones (Tabler Icons)

```html
<!-- CDN -->
<link rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css">

<!-- Uso -->
<i class="ti ti-download"></i>
<i class="ti ti-user-plus"></i>
<i class="ti ti-star"></i>
<i class="ti ti-building-store"></i>
<i class="ti ti-search"></i>
<i class="ti ti-x"></i>
```

## Logo

Arquivo: `docs/screens/logo.png`
- Capacete dourado + texto "OS REVITEIROS" em fonte distressed azul escuro
- Usar em fundo azul escuro (`#042C53`) — o dourado contrasta perfeitamente
- Versão branca disponível para fundo escuro
- Altura mínima: 32px; recomendado: 40px no header
