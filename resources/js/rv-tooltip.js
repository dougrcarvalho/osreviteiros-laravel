/**
 * Preview 500×500 da imagem do card, seguindo o mouse.
 *
 * Uso: x-data="rvTooltip" no container do grid; as imagens marcam
 * data-preview="url". Delegação de eventos — funciona com cards
 * adicionados pelo infinite scroll.
 */
const TAMANHO = 500;
const OFFSET = 16;

export default () => ({
    aberto: false,
    src: '',
    x: 0,
    y: 0,

    mostrar(e) {
        const img = e.target.closest('[data-preview]');
        if (!img?.dataset.preview) return;

        this.src = img.dataset.preview;
        this.aberto = true;
        this.mover(e);
    },

    mover(e) {
        if (!this.aberto) return;

        let x = e.clientX + OFFSET;
        let y = e.clientY + OFFSET;

        if (x + TAMANHO > window.innerWidth) x = e.clientX - TAMANHO - OFFSET;
        if (y + TAMANHO > window.innerHeight) y = e.clientY - TAMANHO - OFFSET;

        this.x = Math.max(8, x);
        this.y = Math.max(8, y);
    },

    esconder(e) {
        if (e.target.closest('[data-preview]')) this.aberto = false;
    },
});
