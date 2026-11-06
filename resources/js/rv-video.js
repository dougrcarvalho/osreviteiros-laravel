// Lite embed do YouTube: só carrega o iframe quando o usuário clica no poster
// (o player do YouTube pesa ~1 MB e derrubaria o Lighthouse da home).
export default function rvVideo() {
    document.addEventListener('click', (event) => {
        const botao = event.target.closest('[data-rv-youtube]');
        if (!botao) return;

        const iframe = document.createElement('iframe');
        iframe.src = `https://www.youtube-nocookie.com/embed/${encodeURIComponent(botao.dataset.rvYoutube)}?autoplay=1&rel=0`;
        iframe.title = botao.getAttribute('aria-label') || 'Vídeo';
        iframe.allow = 'accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture';
        iframe.allowFullscreen = true;
        iframe.className = botao.dataset.rvClasse || '';

        botao.replaceWith(iframe);
    });
}
