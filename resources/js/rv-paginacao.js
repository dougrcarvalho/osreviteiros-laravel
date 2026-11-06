/**
 * Paginação SEO durante o infinite scroll.
 *
 * O primeiro card de cada página traz data-pagina-inicio + data-url
 * (/familias-revit/page/3/?…). Conforme o usuário rola, a URL passa a ser
 * a da página que ocupa a parte de cima da tela — com replaceState, sem
 * recarregar e sem criar entradas no histórico.
 */
const LINHA = 0.35; // fração da altura da janela usada como referência

export default () => ({
    agendado: false,

    init() {
        this.aoRolar = () => {
            if (this.agendado) return;
            this.agendado = true;
            requestAnimationFrame(() => {
                this.agendado = false;
                this.atualizar();
            });
        };

        window.addEventListener('scroll', this.aoRolar, { passive: true });
    },

    atualizar() {
        const marcadores = this.$el.querySelectorAll('[data-pagina-inicio]');
        if (!marcadores.length) return;

        const linha = window.innerHeight * LINHA;
        let url = marcadores[0].dataset.url;

        for (const marcador of marcadores) {
            if (marcador.getBoundingClientRect().top > linha) break;
            url = marcador.dataset.url;
        }

        // Fim da rolagem: a última página pode ser curta demais para alcançar a linha
        const noFim = window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 4;
        const ultimo = marcadores[marcadores.length - 1];
        if (noFim && ultimo.getBoundingClientRect().top < window.innerHeight) {
            url = ultimo.dataset.url;
        }

        const destino = new URL(url, window.location.href);

        if (destino.pathname + destino.search !== window.location.pathname + window.location.search) {
            window.history.replaceState(window.history.state, '', destino);
        }
    },

    destroy() {
        window.removeEventListener('scroll', this.aoRolar);
    },
});

/**
 * "Carregar resultados anteriores" (grade aberta em /page/3/): insere a
 * página anterior acima sem a tela pular — mantém o card atual no mesmo lugar.
 */
export function rvAnteriores() {
    return {
        carregando: false,

        async carregar() {
            if (this.carregando) return;
            this.carregando = true;

            const ancora = this.$root.closest('.rv-familia-grid')?.querySelector('[data-pagina-inicio]');
            const antes = ancora?.getBoundingClientRect().top;

            try {
                await this.$wire.carregarAnteriores();
            } finally {
                this.carregando = false;
                this.$nextTick(() => {
                    if (ancora?.isConnected && antes !== undefined) {
                        window.scrollBy(0, ancora.getBoundingClientRect().top - antes);
                    }
                });
            }
        },
    };
}
