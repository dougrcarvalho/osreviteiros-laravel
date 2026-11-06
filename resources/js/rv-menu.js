// Menu do cabeçalho (celular e tablet): <dialog> nativo, aberto com showModal().
// O navegador cuida do Esc, do foco preso dentro do painel e do foco de volta ao botão.
export default function rvMenu() {
    const dialog = document.getElementById('rv-menu');
    const abrir = document.getElementById('rv-menu-abrir');
    if (!dialog || !abrir) return;

    const fechar = () => dialog.close();

    abrir.addEventListener('click', () => {
        abrir.setAttribute('aria-expanded', 'true');
        dialog.showModal();
    });

    dialog.addEventListener('close', () => {
        abrir.setAttribute('aria-expanded', 'false');
    });

    // Clique no fundo escuro ou em um link (inclusive âncoras como #planos) fecha o painel
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog || event.target.closest('a')) fechar();
    });

    // Se a tela passar do breakpoint do menu (xl, 1280px), o painel não pode ficar aberto
    window.matchMedia('(min-width: 1280px)').addEventListener('change', (event) => {
        if (event.matches && dialog.open) fechar();
    });
}
