@props(['familia', 'ativo' => false, 'texto' => false])
{{-- Coração de favoritos (resources/js/rv-favorito.js). Visitante: abre o popup de cadastro. --}}
<button type="button"
        @class(['rv-favorito', 'rv-favorito--texto' => $texto])
        x-data="rvFavorito(@js(['url' => route('favoritos.alternar', $familia), 'ativo' => (bool) $ativo, 'logado' => auth()->check(), 'rotuloAdicionar' => __('Adicionar aos favoritos'), 'rotuloRemover' => __('Remover dos favoritos')]))"
        x-on:click.prevent.stop="alternar"
        :class="{ 'rv-favorito--ativo': ativo }"
        :aria-pressed="ativo ? 'true' : 'false'"
        :aria-label="rotulo"
        :title="rotulo">
    <i class="ti" :class="ativo ? 'ti-heart-filled' : 'ti-heart'"></i>
    @if ($texto)
        <span x-text="ativo ? @js(__('Favoritado')) : @js(__('Favoritar'))"></span>
    @endif
</button>
