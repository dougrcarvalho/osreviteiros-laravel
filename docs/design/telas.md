# Telas — Os Reviteiros Laravel

## Tela 1: Listagem de Famílias (`/familias`)

```
┌─────────────────────────────────────────────────────────┐
│ [LOGO]          FAMÍLIAS    FABRICANTES    [ENTRAR]      │  ← header azul #042C53
└─────────────────────────────────────────────────────────┘

  ← Voltar ao Início

  Os Reviteiros | Famílias Revit BIM
  ────────────────────────────────────────────────────
  [🔍  buscar por 'cadeira...'                        ]

  ACESSAR GRÁTIS  Acessibilidade  Aeronave  Animais  Automóveis  ← chips de categoria
  Área de Serviço  Banheiro  Ciência e Tecnologia  Climatização  ← wrap, nowrap por chip
  Comercial  Construção  Cozinha  Decoração  Elétrico ...

      ↓ (ao clicar em "Automóveis"):
  Automóveis ×  ──── Carros  Motos  Caminhões  ← subcategorias em 2ª linha

      ↓ (ao clicar em "Carros"):
  Automóveis ×  Carros ×  ──── SUV  Sedan  Hatch  ← 3ª linha

  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐
  │          │ │          │ │          │ │          │ │          │
  │  [IMG]   │ │  [IMG]   │ │  [IMG]   │ │  [IMG]   │ │  [IMG]   │
  │  220px   │ │          │ │          │ │          │ │          │
  │          │ │          │ │          │ │          │ │          │
  │──────────│ │──────────│ │──────────│ │──────────│ │──────────│
  │ ♡ Nome.. │ │ ♡ Nome.. │ │ ♡ Nome.. │ │ ♡ Nome.. │ │ ♡ Nome.. │
  │ Revitei..│ │          │ │          │ │          │ │          │
  │ [BAIXAR] │ │ [BAIXAR] │ │ [BAIXAR] │ │ [BAIXAR] │ │ [BAIXAR] │
  └──────────┘ └──────────┘ └──────────┘ └──────────┘ └──────────┘
  (grid auto-fill minmax(220px, 1fr), gap 16px)

  ... (infinite scroll — sentinel invisível no fim)
  [Carregando...]
```

### Hover na imagem → tooltip 500×500px

```
                          ┌────────────────────┐
                          │                    │
                          │    IMAGEM          │
                          │    500 × 500 px    │
                          │                    │
                          └────────────────────┘
  ┌──────────┐
  │  [IMG]◄──┤  (reposiciona se próximo da borda da viewport)
```

### Estados do botão de download

```
[  ↓ BAIXAR        ]  ← azul (#1A6FC4) — logado + plano pago
[  ★ ASSINAR        ]  ← dourado (#C9A227) — logado grátis + família paga
[  ♡ CADASTRAR       ]  ← verde (#38A169) — não logado
[  ↓ BAIXAR GRÁTIS  ]  ← azul — logado grátis + família gratuita
```

---

## Tela 2: Popup de Cadastro / Planos

```
┌─────────────────────────────────────────────────────────┐
│  ╔═══════════════════════════════════════════════════╗  │
│  ║  Acesse as Famílias Revit BIM              [×]   ║  │
│  ║                                                   ║  │
│  ║  Escolha seu plano e comece agora:                ║  │
│  ║                                                   ║  │
│  ║  ┌─────────┐  ┌─────────┐  ┌─────────────────┐  ║  │
│  ║  │         │  │         │  │  ⭐ MAIS POPULAR  │  ║  │
│  ║  │  Grátis │  │  Basic  │  │     Premium      │  ║  │
│  ║  │         │  │         │  │                  │  ║  │
│  ║  │Famílias │  │ Bibliot.│  │ Tudo do Basic +  │  ║  │
│  ║  │selecion.│  │completa │  │ novas famílias   │  ║  │
│  ║  │         │  │         │  │                  │  ║  │
│  ║  │[CRIAR   │  │[ASSINAR]│  │  [ASSINAR        │  ║  │
│  ║  │CONTA]   │  │         │  │   PREMIUM]       │  ║  │
│  ║  └─────────┘  └─────────┘  └─────────────────-┘  ║  │
│  ║                                                   ║  │
│  ║  Já tem conta? Entrar                             ║  │
│  ╚═══════════════════════════════════════════════════╝  │
│  (overlay rgba(0,0,0,.6))                               │
└─────────────────────────────────────────────────────────┘
```

---

## Tela 3: Fabricantes (`/fabricantes`)

```
┌─────────────────────────────────────────────────────────┐
│ [LOGO]          FAMÍLIAS    FABRICANTES    [ENTRAR]      │
└─────────────────────────────────────────────────────────┘

  Fabricantes  [48 fabricantes]
  ────────────────────────────────────────────────────
  [🔍  buscar fabricante...                          ]

  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐
  │          │ │          │ │          │ │          │
  │  [LOGO]  │ │  [LOGO]  │ │    A     │ │  [LOGO]  │  ← inicial se sem logo
  │  220px   │ │  220px   │ │  (48px)  │ │  220px   │
  │          │ │          │ │          │ │          │
  │──────────│ │──────────│ │──────────│ │──────────│
  │ Samsung  │ │  Scania  │ │  Acme    │ │  Ford    │
  │ 142 fam. │ │ 38 fam.  │ │ 12 fam.  │ │ 95 fam.  │
  └──────────┘ └──────────┘ └──────────┘ └──────────┘
  (click → /familias?marca={slug})
```

---

## Tela 4: Header / Navegação

```
┌─────────────────────────────────────────────────────────┐
│                                                         │
│  [OS REVITEIROS]    Famílias    Fabricantes    [Entrar] │
│   (logo: capacete                              ou       │
│    dourado + texto)               [Douglas ▾] quando logado│
│                                                         │
└─────────────────────────────────────────────────────────┘
fundo: #042C53 | altura: 64px | sticky top
```

---

## Fluxo de Usuário

```
Usuário não logado
    → Vê grade de famílias
    → Clica "Baixar" em qualquer família
    → Popup de planos aparece
    → Escolhe plano → /cadastro?plano=XXX
    → Preenche nome/email/senha
    → Se grátis: logado direto, volta para /familias
    → Se pago: redireciona para Hotmart/Eduzz → webhook atualiza plano

Usuário grátis logado
    → Vê grade (botão "Assinar" em famílias pagas)
    → Clica "Baixar Grátis" em família com tag acessar-gratis → download direto
    → Clica "Assinar" → popup de upgrade

Usuário pago logado
    → Todos os botões mostram "Baixar"
    → Download direto via link_download
```

---

## Referências Visuais

- `docs/screens/screenshot-1.png` — Grade principal de famílias (WP)
- `docs/screens/screenshot-2.png` — Detalhe dos cards e botões
- `docs/screens/screenshot-3.png` — Vista geral da página
- `docs/screens/logo.png` — Logo Os Reviteiros (capacete dourado)

**Paleta retirada do logo:**
- Azul escuro distressed: `#042C53`
- Dourado vibrante: `#C9A227`
- Branco: `#FFFFFF`
