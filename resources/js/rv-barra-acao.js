// Barra fixa do celular: só aparece depois que o botão do hero sai da tela.
// Enquanto ela estiver visível, o <body> ganha espaço embaixo (classe rv-com-barra),
// para que a barra não cubra o rodapé.
export default function rvBarraAcao() {
    const barra = document.getElementById('rv-barra-acao');
    const gatilho = document.querySelector('[data-rv-cta-hero]');
    if (!barra || !gatilho || !('IntersectionObserver' in window)) return;

    const observer = new IntersectionObserver(([entrada]) => {
        const mostrar = !entrada.isIntersecting;

        barra.hidden = !mostrar;
        document.body.classList.toggle('rv-com-barra', mostrar);
    });

    observer.observe(gatilho);
}
