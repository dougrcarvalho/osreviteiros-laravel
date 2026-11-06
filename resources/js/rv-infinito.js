/**
 * Infinite scroll via IntersectionObserver.
 *
 * Uso: <div x-data="rvInfinito" data-tem-mais="true"></div> (o sentinel no fim do grid).
 * Quando o sentinel entra na viewport chama $wire.carregarMais().
 */
export default () => ({
    observer: null,
    carregando: false,

    init() {
        this.observer = new IntersectionObserver(
            (entries) => {
                if (entries[0].isIntersecting) this.carregar();
            },
            { rootMargin: '400px 0px', threshold: 0 },
        );

        // Pequeno atraso para o layout estabilizar (imagens / fontes)
        setTimeout(() => this.observer?.observe(this.$el), 300);
    },

    async carregar() {
        if (this.carregando || this.$el.dataset.temMais !== 'true') return;

        this.carregando = true;

        try {
            await this.$wire.carregarMais();
        } finally {
            this.carregando = false;
            // Se o sentinel continua visível (tela alta), o observer não dispara
            // de novo sozinho: re-observar força uma nova verificação.
            this.$nextTick(() => {
                this.observer?.unobserve(this.$el);
                this.observer?.observe(this.$el);
            });
        }
    },

    destroy() {
        this.observer?.disconnect();
        this.observer = null;
    },
});
