@props(['depoimentos'])

{{-- Só é renderizada com reviteiros.home.mostrar_depoimentos = true (ver HomeService::depoimentos) --}}
<section aria-labelledby="titulo-depoimentos">
    <div class="mx-auto max-w-rv py-12 md:py-16 lg:py-24 flex flex-col gap-6 lg:gap-9">
        <div class="flex flex-col gap-2.5 items-start lg:items-center text-left lg:text-center px-4 md:px-6">
            @if (collect($depoimentos)->contains('ilustrativo', true))
                <span class="text-[11px] lg:text-xs font-bold text-rv-premium bg-rv-premium-bg px-2.5 py-1 rounded-md">[EXEMPLOS ILUSTRATIVOS — substituir por depoimentos reais antes de publicar]</span>
            @endif
            <h2 id="titulo-depoimentos" class="text-[27px] md:text-[34px] lg:text-[40px] leading-[1.12] lg:leading-normal tracking-[-0.02em] font-bold text-balance">Quem usa não volta a modelar do zero</h2>
            <p class="text-[15px] lg:text-[17px] text-rv-ink-2">Arquitetos, projetistas e engenheiros de todo o Brasil.</p>
        </div>

        <div class="rv-rolagem flex gap-3 overflow-x-auto snap-x snap-mandatory px-4 pb-1 scroll-px-4 md:px-6 md:grid md:grid-cols-2 md:gap-5 md:overflow-visible md:snap-none lg:grid-cols-4">
            @foreach ($depoimentos as $depoimento)
                <figure class="w-[300px] md:w-auto flex-none snap-start border border-rv-line rounded-2xl lg:rounded-rv-card-lg p-5 lg:p-7 flex flex-col gap-4 lg:gap-[22px] bg-white">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="#0B4FC4" aria-hidden="true"><path d="M9.5 6C6.5 7 5 9.3 5 12.5V18h5.5v-5.5H7.8c0-2 1-3.4 2.7-4.1L9.5 6zm9 0c-3 1-4.5 3.3-4.5 6.5V18h5.5v-5.5h-2.7c0-2 1-3.4 2.7-4.1L18.5 6z"></path></svg>
                    <blockquote class="text-base lg:text-[17px] leading-[1.55] text-rv-ink flex-auto">{{ $depoimento['texto'] }}</blockquote>
                    <figcaption class="flex items-center gap-2.5 lg:gap-3 pt-3.5 lg:pt-[18px] border-t border-[#EEF0F2]">
                        <span class="flex-none size-10 lg:size-11 rounded-full bg-rv-accent-soft text-rv-accent font-bold text-sm lg:text-[15px] flex items-center justify-center" aria-hidden="true">{{ $depoimento['iniciais'] }}</span>
                        <span class="flex flex-col gap-0.5">
                            <strong class="text-sm lg:text-[15px]">{{ $depoimento['nome'] }}</strong>
                            <span class="text-xs lg:text-[13px] text-rv-ink-3">{{ $depoimento['cargo'] }}</span>
                        </span>
                    </figcaption>
                </figure>
            @endforeach
        </div>
    </div>
</section>
