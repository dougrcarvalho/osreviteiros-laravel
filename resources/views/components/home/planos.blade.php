@props(['planos'])

{{-- Mesma ordem do HTML (Grátis, Basic, Premium). No celular o CSS reordena: Premium primeiro,
     e Grátis/Basic mostram só o resumo. --}}
<section id="planos" class="bg-rv-alt border-t border-rv-line scroll-mt-16 lg:scroll-mt-6" aria-labelledby="titulo-planos">
    <div class="mx-auto max-w-rv px-4 md:px-6 py-12 md:py-16 lg:py-[88px] flex flex-col gap-[18px] lg:gap-10">
        <div class="flex flex-col gap-2.5 items-start lg:items-center lg:text-center">
            <h2 id="titulo-planos" class="text-[27px] md:text-[34px] lg:text-[40px] leading-[1.12] lg:leading-normal tracking-[-0.02em] font-bold text-balance">{{ __('Menos que uma hora do seu trabalho por mês.') }}</h2>
            <p class="text-[15px] lg:text-[17px] text-rv-ink-2">
                <span class="md:hidden">{{ __('Comece grátis e assine quando a biblioteca virar parte do seu dia.') }}</span>
                <span class="hidden md:inline">{{ __('Comece grátis. Faça upgrade quando a biblioteca virar parte do seu dia.') }}</span>
            </p>
        </div>

        <div class="grid gap-4 lg:grid-cols-[repeat(auto-fit,minmax(min(300px,100%),1fr))] lg:gap-5 items-stretch md:grid-cols-2">
            @foreach ($planos as $plano)
                <div @class([
                    'relative bg-white rounded-2xl lg:rounded-rv-card-lg p-[22px] lg:p-8 flex flex-col gap-3.5 lg:gap-5',
                    'border-2 border-rv-accent md:col-span-2 lg:col-span-1' => $plano['destaque'],
                    'border border-rv-line' => ! $plano['destaque'],
                    'order-1' => $plano['ordem_mobile'] === 1,
                    'order-2' => $plano['ordem_mobile'] === 2,
                    'order-3' => $plano['ordem_mobile'] === 3,
                    'lg:order-none',
                ])>
                    @if ($plano['destaque'])
                        <span class="lg:absolute lg:-top-[13px] lg:left-8 self-start lg:self-auto bg-rv-accent text-white text-[11px] lg:text-xs font-bold px-2.5 py-1 lg:py-[5px] rounded-full tracking-[0.04em]">{{ __('MAIS COMPLETO') }}</span>
                    @endif

                    <div class="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-1 lg:flex-col lg:items-stretch lg:gap-0">
                        <div class="flex flex-col lg:gap-1.5">
                            <h3 class="text-lg lg:text-xl font-bold">{{ __($plano['nome']) }}</h3>
                            <p class="hidden lg:block mt-1.5 text-rv-ink-3 text-[15px]">{{ __($plano['descricao']) }}</p>
                        </div>

                        <p @class([
                            'font-extrabold tracking-[-0.02em] lg:text-[44px] lg:leading-tight',
                            'basis-full text-4xl lg:basis-auto' => $plano['destaque'],
                            'text-2xl ml-auto lg:ml-0' => ! $plano['destaque'],
                        ])>
                            {{ $plano['precoFmt'] }}@if ($plano['preco'] > 0)<span class="text-sm lg:text-base font-medium text-rv-ink-3">{{ __('/mês') }}</span>@endif
                        </p>
                    </div>

                    @if ($plano['resumo'])
                        <p class="lg:hidden text-[15px] leading-[1.5] text-rv-ink-2">{{ __($plano['resumo']) }}</p>
                    @endif

                    <ul @class([
                        'list-disc pl-[18px] flex-col gap-2 lg:gap-2.5 text-rv-body text-[15px] leading-[1.45] flex-auto',
                        'hidden lg:flex' => $plano['resumo'],
                        'flex' => ! $plano['resumo'],
                    ])>
                        @foreach ($plano['beneficios'] as $beneficio)
                            <li>{{ __($beneficio) }}</li>
                        @endforeach
                    </ul>

                    <a href="{{ $plano['url'] }}" @class([
                        'flex justify-center items-center min-h-12 lg:min-h-0 p-3.5 rounded-rv-btn text-base lg:text-[15px]',
                        'bg-rv-accent hover:bg-rv-accent-hover text-white font-bold min-h-[52px]' => $plano['destaque'],
                        'border border-rv-ink text-rv-ink hover:bg-rv-ink hover:text-white font-semibold' => ! $plano['destaque'],
                    ])>{{ __($plano['cta']) }}</a>
                </div>
            @endforeach
        </div>

        <p class="text-[13px] lg:text-sm text-center text-rv-ink-2 text-balance">{{ __('Cartão ou Pix recorrente · Cancele quando quiser · Reembolso em até 7 dias na primeira compra') }}</p>
    </div>
</section>
