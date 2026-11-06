{{-- Botão de download — estados (DownloadService::estadoBotao). link_download nunca vai para o HTML. --}}
@switch($botao)
    @case(\App\Services\DownloadService::CADASTRAR)
        <button type="button" class="rv-btn rv-btn--cadastrar" @click="$dispatch('abrir-popup')">
            <i class="ti ti-user-plus"></i> Baixar Grátis
        </button>
        @break

    @case(\App\Services\DownloadService::ASSINAR)
        <button type="button" class="rv-btn rv-btn--assinar" @click="$dispatch('abrir-upgrade')">
            <i class="ti ti-star"></i> Assinar
        </button>
        @break

    @case(\App\Services\DownloadService::UPGRADE)
        <button type="button" class="rv-btn rv-btn--assinar" @click="$dispatch('abrir-upgrade', { premium: true })">
            <i class="ti ti-crown"></i> Exclusivo Premium
        </button>
        @break

    @default
        <form method="POST" action="{{ route('familias.download', $familia) }}" target="_blank">
            @csrf
            <button type="submit" class="rv-btn rv-btn--baixar">
                <i class="ti ti-download"></i>
                {{ $botao === \App\Services\DownloadService::BAIXAR_GRATIS ? 'Baixar Grátis' : 'Baixar' }}
            </button>
        </form>
@endswitch
