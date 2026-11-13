@props(['total', 'buscas', 'pluginUrl'])

<section class="bg-rv-alt border-b border-rv-line">
    <div class="mx-auto max-w-rv-narrow px-4 md:px-6 pt-9 pb-8 md:pt-16 md:pb-14 lg:pt-[88px] lg:pb-[72px] flex flex-col items-start lg:items-center text-left lg:text-center gap-4 lg:gap-[22px]">
        <p class="text-xs lg:text-sm font-semibold tracking-[0.06em] uppercase text-rv-ink-2">
            <span class="md:hidden">{{ __('Plugin para Revit · :total famílias', ['total' => $total]) }}</span>
            <span class="hidden md:inline">{{ __('Plugin para Revit · Famílias · Texturas · Projetos prontos') }}</span>
        </p>

        <h1 class="text-[34px] md:text-[44px] lg:text-[clamp(38px,5.4vw,64px)] leading-[1.06] lg:leading-[1.04] tracking-[-0.03em] font-extrabold text-balance">{{ __('Encontre a família certa e insira no Revit em um clique.') }}</h1>

        <p class="text-base md:text-lg lg:text-[19px] leading-[1.55] text-rv-ink-2 max-w-[680px] text-pretty">
            <span class="md:hidden">{{ __('Famílias de fabricantes reais, texturas, templates e projetos prontos para prefeitura, tudo dentro do plugin.') }}</span>
            <span class="hidden md:inline">{{ __(':total famílias de fabricantes reais, texturas sem emendas, templates e projetos prontos para prefeitura — tudo dentro do plugin, sem baixar arquivo por arquivo.', ['total' => $total]) }}</span>
        </p>

        <form role="search" method="GET" action="{{ route('familias.index') }}"
              class="w-full max-w-[720px] lg:mt-2.5 flex gap-2 bg-white border border-rv-line-2 rounded-xl lg:rounded-rv-card p-1.5 lg:p-2 shadow-[0_6px_20px_rgba(17,20,24,0.06)] lg:shadow-[0_8px_30px_rgba(17,20,24,0.06)] focus-within:border-rv-accent">
            <label for="busca" class="sr-only">{{ __('Buscar famílias, texturas e projetos') }}</label>
            <div class="flex items-center gap-2.5 flex-auto min-w-0 pl-2 lg:pl-3">
                <svg class="hidden lg:block" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#6B7380" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="M20 20l-3.5-3.5"></path></svg>
                <input id="busca" name="busca" type="search" maxlength="100" placeholder="{{ __('Ex.: bacia Deca, sofá 3 lugares, porcelanato…') }}"
                       class="border-0 outline-none text-base lg:text-[17px] w-full min-w-0 min-h-11 bg-transparent text-rv-ink placeholder:text-rv-icon">
            </div>
            <button type="submit" aria-label="{{ __('Buscar') }}" class="flex flex-none size-12 lg:size-auto lg:min-h-12 items-center justify-center gap-2 rounded-lg lg:rounded-rv-btn bg-rv-ink hover:bg-rv-body text-white font-semibold text-base lg:px-6 cursor-pointer">
                <svg class="lg:hidden" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="M20 20l-3.5-3.5"></path></svg>
                <span class="hidden lg:inline">{{ __('Buscar') }}</span>
            </button>
        </form>

        <div class="rv-rolagem -mx-4 md:mx-0 w-[calc(100%+2rem)] md:w-full flex gap-2 overflow-x-auto px-4 md:px-0 whitespace-nowrap lg:flex-wrap lg:justify-center lg:overflow-visible lg:whitespace-normal text-sm">
            <span class="hidden lg:inline text-rv-ink-3 py-1.5">{{ __('Mais buscados:') }}</span>
            @foreach ($buscas as $busca)
                <a href="{{ $busca['url'] }}" class="rv-alvo flex-none inline-flex items-center min-h-11 lg:min-h-0 px-3.5 lg:px-3 py-2.5 lg:py-1.5 border border-rv-line-2 rounded-full text-rv-body bg-white hover:border-rv-accent hover:text-rv-accent">{{ $busca['termo'] }}</a>
            @endforeach
        </div>

        <a href="{{ $pluginUrl }}" data-rv-cta-hero class="lg:hidden self-stretch flex justify-center items-center min-h-[52px] mt-1 rounded-rv-btn bg-rv-accent hover:bg-rv-accent-hover text-white font-bold text-[17px]">{{ __('Baixar plugin grátis') }}</a>

        <p class="text-[13px] md:text-sm lg:mt-1.5 text-rv-ink-2">
            <span class="md:hidden">{{ __('Comece grátis · Revit · Cancele quando quiser') }}</span>
            <span class="hidden md:inline">{{ __('Comece grátis · Funciona no Revit e no Revit LT (baixando pelo site) · Cancele quando quiser') }}</span>
        </p>
    </div>
</section>
