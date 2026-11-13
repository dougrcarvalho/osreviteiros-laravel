/**
 * Botão de favoritos (<x-favorito>). Atualiza na hora e confirma no servidor;
 * se falhar, volta ao estado anterior. Visitante / sessão expirada → popup de cadastro.
 */
export default ({ url, ativo, logado, rotuloAdicionar, rotuloRemover }) => ({
    ativo,
    carregando: false,

    get rotulo() {
        return this.ativo ? rotuloRemover : rotuloAdicionar;
    },

    async alternar() {
        if (!logado) {
            this.$dispatch('abrir-popup');
            return;
        }
        if (this.carregando) return;

        const anterior = this.ativo;
        this.carregando = true;
        this.ativo = !anterior;

        try {
            const resposta = await fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (resposta.status === 401 || resposta.status === 419) {
                this.ativo = anterior;
                this.$dispatch('abrir-popup');
                return;
            }
            if (!resposta.ok) throw new Error(resposta.status);

            this.ativo = (await resposta.json()).favorito;
        } catch {
            this.ativo = anterior;
        } finally {
            this.carregando = false;
        }
    },
});
