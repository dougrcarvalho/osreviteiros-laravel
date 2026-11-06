/**
 * Histórico do navegador para os filtros do grid (FamiliaGrid).
 *
 * O componente informa a nova URL pelo evento `rv-url`:
 *   push: true  → clique em filtro: nova entrada no histórico
 *   push: false → digitação na busca / ordenação: só atualiza a URL
 * No "voltar/avançar" (popstate) os filtros e a página (/page/{n}/) da URL
 * são reaplicados de uma vez. A troca de URL durante a rolagem fica em rv-paginacao.js.
 */
const COMPONENTE = 'familia-grid';

const paginaDaUrl = () => Number(window.location.pathname.match(/\/page\/(\d+)\/?$/)?.[1] ?? 1);

export default function rvHistorico(Livewire) {
    Livewire.on('rv-url', (evento) => {
        const { url, push = false } = Array.isArray(evento) ? evento[0] : evento;
        if (!url) return;

        const destino = new URL(url, window.location.href);
        if (destino.href === window.location.href) return;

        window.history[push ? 'pushState' : 'replaceState'](null, '', destino);
    });

    window.addEventListener('popstate', () => {
        const query = Object.fromEntries(new URLSearchParams(window.location.search));

        Livewire.all()
            .filter((c) => c.name === COMPONENTE)
            .forEach((c) => c.$wire.sincronizarUrl(query, paginaDaUrl()));
    });
}
