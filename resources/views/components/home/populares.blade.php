@props(['populares', 'total'])

@php
    $selos = [
        'gratis'  => [__('Grátis'), 'bg-rv-gratis-bg text-rv-gratis'],
        'basic'   => ['Basic', 'bg-rv-basic-bg text-rv-basic'],
        'premium' => ['Premium', 'bg-rv-premium-bg text-rv-premium'],
    ];

    // Catálogo vazio (ambiente novo): mostra os cards do design como placeholder
    $cards = $populares ?: collect(['gratis', 'basic', 'basic', 'premium'])->map(fn ($selo, $i) => [
        'nome' => '[Nome da família popular '.($i + 1).']', 'url' => '#', 'imagem' => null,
        'fabricante' => null, 'downloads' => '[Nº]', 'selo' => $selo,
    ])->all();
@endphp

{{-- Celular: carrossel com cards de 220px (scroll-snap). Tablet: 2 colunas. Desktop: 4 colunas. --}}
<section aria-labelledby="titulo-populares">
    <div class="mx-auto max-w-rv pt-10 pb-12 md:pt-12 md:pb-16 lg:pt-10 lg:pb-20 flex flex-col gap-4 md:gap-7">
        <div class="flex flex-wrap justify-between items-end gap-3 px-4 md:px-6">
            <div class="flex flex-col gap-1.5">
                <h2 id="titulo-populares" class="text-[27px] md:text-[34px] leading-[1.12] md:leading-normal tracking-[-0.02em] font-bold">
                    <span class="md:hidden">{{ __('Mais baixadas da semana') }}</span>
                    <span class="hidden md:inline">{{ __('Mais baixadas esta semana') }}</span>
                </h2>
                <p class="hidden md:block text-rv-ink-3 text-base">{{ __('Prontas para inserir pelo plugin, já com materiais e parâmetros.') }}</p>
            </div>
            <a href="{{ route('familias.index') }}" class="rv-alvo flex-none py-2 md:py-0 font-semibold text-sm md:text-[15px] text-rv-accent hover:text-rv-accent-hover">
                <span class="md:hidden">{{ __('Ver todas') }}</span>
                <span class="hidden md:inline">{{ __('Ver as :total famílias →', ['total' => $total]) }}</span>
            </a>
        </div>

        <div class="rv-rolagem flex gap-3 overflow-x-auto snap-x snap-mandatory px-4 pb-1 scroll-px-4 md:px-6 md:grid md:grid-cols-2 md:gap-5 md:overflow-visible md:snap-none lg:grid-cols-4">
            @foreach ($cards as $familia)
                @php([$seloNome, $seloClasse] = $selos[$familia['selo']])
                <article class="w-[220px] md:w-auto flex-none snap-start border border-rv-line rounded-xl lg:rounded-rv-card overflow-hidden flex flex-col bg-white">
                    <div class="relative aspect-square bg-[#F1F3F6] flex items-center justify-center">
                        @if ($familia['imagem'])
                            <img src="{{ $familia['imagem'] }}" alt="{{ $familia['nome'] }}" width="600" height="600" loading="lazy" decoding="async"
                                 class="absolute inset-0 size-full object-contain">
                        @else
                            <span class="text-xs text-rv-ink-3">{{ __('[render da família]') }}</span>
                        @endif
                        <span class="absolute top-2.5 left-2.5 lg:top-3 lg:left-3 text-[11px] lg:text-xs font-bold px-2 lg:px-[9px] py-[3px] lg:py-1 rounded-md {{ $seloClasse }}">{{ __($seloNome) }}</span>
                    </div>
                    <div class="p-3 lg:p-4 flex flex-col gap-2.5 lg:gap-3 flex-auto justify-between">
                        <div class="flex flex-col gap-0.5 lg:gap-[3px]">
                            <h3 class="text-[15px] lg:text-base font-semibold leading-[1.3]">{{ $familia['nome'] }}</h3>
                            <span class="text-xs lg:text-[13px] text-rv-ink-3">{{ $familia['fabricante'] }}<span class="hidden md:inline">{{ $familia['fabricante'] ? ' · ' : '' }}{{ __(':total downloads', ['total' => $familia['downloads']]) }}</span></span>
                        </div>
                        <a href="{{ $familia['url'] }}" aria-label="{{ __('Inserir no Revit') }}: {{ $familia['nome'] }}"
                           class="flex justify-center items-center gap-2 min-h-11 rounded-lg lg:rounded-rv-btn border border-rv-ink text-rv-ink hover:bg-rv-ink hover:text-white font-semibold text-sm lg:text-[15px]">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4v11"></path><path d="M7 10l5 5 5-5"></path><path d="M5 20h14"></path></svg>
                            {{ __('Inserir no Revit') }}
                        </a>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>
