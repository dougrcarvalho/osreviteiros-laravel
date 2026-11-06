# Spec: Infinite Scroll + Filtros de Categoria

## Comportamento esperado

1. Página carrega com PHP renderizando os primeiros 48 cards
2. JS detecta o sentinel no final da lista via `IntersectionObserver`
3. Ao visibilizar o sentinel → fetch na API → appenda novos cards
4. Ao clicar em categoria → reseta página para 1, carrega subcategorias
5. URL atualizada via `history.pushState` sem reload

## JavaScript (assets/js/familias.js)

```javascript
const CFG = {
    restUrl: window.ReviteirosFamilias?.restUrl ?? '/api/familias',
    nonce:   window.ReviteirosFamilias?.nonce   ?? '',
    perPage: window.ReviteirosFamilias?.perPage ?? 48,
    baseUrl: window.ReviteirosFamilias?.baseUrl ?? '/familias',
};

let state = {
    page:    1,
    loading: false,
    done:    false,
    cat:     '',
    sub:     '',
    subcl:   '',
    marca:   '',
    busca:   '',
    orderby: 'date',
};

// ── Infinite Scroll ──────────────────────────────────────
const sentinel = document.querySelector('#rv-sentinel');
const observer = new IntersectionObserver(entries => {
    if (entries[0].isIntersecting && !state.loading && !state.done) {
        carregarMais();
    }
}, { rootMargin: '200px', threshold: 0.1 });

// Delay inicial para layout estabilizar
setTimeout(() => { if (sentinel) observer.observe(sentinel); }, 300);

async function carregarMais() {
    state.loading = true;
    mostrarLoader(true);

    const params = buildParams();
    const url    = `${CFG.restUrl}?${new URLSearchParams(params)}`;

    try {
        const res  = await fetch(url, { headers: { 'X-WP-Nonce': CFG.nonce } });
        const json = await res.json();

        json.data.forEach(item => {
            document.querySelector('#rv-grid').insertAdjacentHTML('beforeend', buildCard(item));
        });

        state.page++;
        if (state.page > json.meta.last_page) {
            state.done = true;
            observer.disconnect();
        }
    } catch(e) {
        console.error('Erro ao carregar famílias', e);
    } finally {
        state.loading = false;
        mostrarLoader(false);
    }
}

// ── Construir parâmetros acumulativos ───────────────────
function buildParams() {
    const p = {
        page:     state.page,
        per_page: CFG.perPage,
        orderby:  state.orderby,
    };
    if (state.cat)    p.cat   = state.cat;
    if (state.sub)    p.sub   = state.sub;
    if (state.subcl)  p.subcl = state.subcl;
    if (state.marca)  p.marca = state.marca;
    if (state.busca)  p.busca = state.busca;
    return p;
}

// ── Atualizar URL (sem reload) ──────────────────────────
function pushUrl() {
    const url  = new URL(CFG.baseUrl);
    if (state.cat)   url.searchParams.set('cat',   state.cat);
    if (state.sub)   url.searchParams.set('sub',   state.sub);
    if (state.subcl) url.searchParams.set('subcl', state.subcl);
    if (state.marca) url.searchParams.set('marca', state.marca);
    history.pushState({}, '', url.toString());
}

// ── Clicar em categoria ─────────────────────────────────
document.addEventListener('click', async e => {
    const chip = e.target.closest('.rv-categoria-chip');
    if (!chip) return;

    e.preventDefault();

    const nivel = chip.dataset.nivel;
    const slug  = chip.dataset.slug;

    // Reset dos níveis abaixo
    if (nivel === '1') { state.cat = slug; state.sub = ''; state.subcl = ''; state.marca = ''; }
    if (nivel === '2') { state.sub = slug; state.subcl = ''; state.marca = ''; }
    if (nivel === '3') { state.subcl = slug; state.marca = ''; }
    if (nivel === '4') { state.marca = slug; }

    state.page = 1;
    state.done = false;

    // Marca chip ativo
    document.querySelectorAll(`.rv-categorias-nivel-${nivel} .rv-categoria-chip`)
            .forEach(c => c.classList.remove('rv-categoria-chip--ativo'));
    chip.classList.add('rv-categoria-chip--ativo');

    // Limpa grid e recarrega
    document.querySelector('#rv-grid').innerHTML = '';
    await carregarMais();

    // Carrega subcategorias do nível seguinte
    if (nivel < 4) {
        await carregarSubcategorias(slug, parseInt(nivel) + 1);
    }

    pushUrl();
});

// ── Carregar subcategorias ──────────────────────────────
async function carregarSubcategorias(parentSlug, nivelFilho) {
    const filtrosAtivos = [state.cat, state.sub, state.subcl].filter(Boolean);
    const url = `/api/familias-categorias?parent=${parentSlug}&filtros[]=${filtrosAtivos.join('&filtros[]=')}`;

    const res  = await fetch(url);
    const json = await res.json();

    const container = document.querySelector(`#rv-subcats-nivel-${nivelFilho}`);
    if (!container) return;

    if (json.data.length === 0) {
        container.innerHTML = '';
        return;
    }

    container.innerHTML = json.data.map(cat => `
        <button class="rv-categoria-chip" data-slug="${cat.slug}" data-nivel="${nivelFilho}">
            ${cat.nome}
        </button>
    `).join('');
}

// ── Build Card ──────────────────────────────────────────
function buildCard(item) {
    const canDownload = item.is_logged && item.link_download &&
                        (item.has_plano || (! item.has_plano && item.is_gratuita));

    let btn = '';
    if (!item.is_logged) {
        btn = `<button class="rv-btn rv-btn--cadastrar" data-popup="cadastro">Baixar Grátis</button>`;
    } else if (canDownload) {
        btn = `<a href="${item.link_download}" class="rv-btn rv-btn--baixar" target="_blank">
                   <i class="ti ti-download"></i> Baixar
               </a>`;
    } else {
        btn = `<button class="rv-btn rv-btn--assinar" data-popup="upgrade">Assinar para Baixar</button>`;
    }

    return `
    <div class="rv-card" data-id="${item.id}">
        <div class="rv-card__img-wrap">
            <img src="${item.imagem || ''}"
                 alt="${item.nome}"
                 class="rv-card__imagem"
                 loading="lazy"
                 data-preview="${item.imagem || ''}">
        </div>
        <div class="rv-card__body">
            <p class="rv-card__nome">${item.nome}</p>
            <p class="rv-card__marca">${item.fabricante || ''}</p>
        </div>
        <div class="rv-card__footer">
            ${btn}
        </div>
    </div>`;
}

// ── Tooltip de imagem (500×500) ─────────────────────────
const tooltip = document.createElement('img');
tooltip.className = 'rv-img-tooltip';
tooltip.style.cssText = 'position:fixed;width:500px;height:500px;object-fit:contain;' +
    'border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,.2);pointer-events:none;' +
    'z-index:9999;display:none;background:#fff;';
document.body.appendChild(tooltip);

document.addEventListener('mouseover', e => {
    const img = e.target.closest('.rv-card__imagem');
    if (!img || !img.dataset.preview) return;
    tooltip.src = img.dataset.preview;
    tooltip.style.display = 'block';
});
document.addEventListener('mousemove', e => {
    if (tooltip.style.display === 'none') return;
    let x = e.clientX + 16;
    let y = e.clientY + 16;
    if (x + 500 > window.innerWidth)  x = e.clientX - 516;
    if (y + 500 > window.innerHeight) y = e.clientY - 516;
    tooltip.style.left = x + 'px';
    tooltip.style.top  = y + 'px';
});
document.addEventListener('mouseout', e => {
    if (e.target.closest('.rv-card__imagem')) tooltip.style.display = 'none';
});
```

## Blade: resources/views/familias/index.blade.php (estrutura)

```html
{{-- Inicialização: CFG para o JS --}}
<script>
window.ReviteirosFamilias = {
    restUrl: "{{ url('/api/familias') }}",
    nonce:   "",   {{-- Sanctum usa cookie/token, não nonce WP --}}
    perPage: 48,
    baseUrl: "{{ url('/familias') }}",
};
</script>

{{-- Categorias nível 1 (PHP server-side) --}}
<div class="rv-categorias rv-categorias-nivel-1">
    @foreach($categorias as $cat)
    <button class="rv-categoria-chip {{ request('cat') === $cat->slug ? 'rv-categoria-chip--ativo' : '' }}"
            data-slug="{{ $cat->slug }}"
            data-nivel="1">
        {{ $cat->nome }}
    </button>
    @endforeach
</div>

{{-- Subcategorias (preenchidas via JS) --}}
<div id="rv-subcats-nivel-2" class="rv-categorias rv-subcategorias"></div>
<div id="rv-subcats-nivel-3" class="rv-categorias rv-subcategorias"></div>
<div id="rv-subcats-nivel-4" class="rv-categorias rv-subcategorias"></div>

{{-- Grid de famílias --}}
<div id="rv-grid" class="rv-familias-grid">
    {{-- Cards iniciais renderizados pelo PHP via Blade --}}
    @foreach($familias as $familia)
        @include('familias._card', ['familia' => $familia])
    @endforeach
</div>

{{-- Sentinel para infinite scroll --}}
<div id="rv-sentinel" style="height:1px"></div>
<div id="rv-loader" class="rv-loader" style="display:none">Carregando...</div>
```
