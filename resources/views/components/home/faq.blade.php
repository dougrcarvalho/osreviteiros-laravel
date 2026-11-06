@props(['faq'])

<section aria-labelledby="titulo-faq">
    <div class="mx-auto max-w-rv-faq px-4 md:px-6 py-12 md:py-16 lg:py-[88px] flex flex-col gap-4 lg:gap-6">
        <h2 id="titulo-faq" class="text-[27px] md:text-[34px] tracking-[-0.02em] font-bold lg:text-center">Perguntas frequentes</h2>

        <div class="flex flex-col border-t border-rv-line">
            @foreach ($faq as $item)
                <details class="group border-b border-rv-line lg:px-1">
                    <summary class="flex justify-between items-center gap-3 min-h-14 lg:min-h-0 py-2 lg:py-5 text-base lg:text-[17px] font-semibold cursor-pointer list-none [&::-webkit-details-marker]:hidden">
                        {{ $item['pergunta'] }}
                        <span aria-hidden="true" class="flex-none text-rv-icon text-xl lg:text-base transition-transform group-open:rotate-45">+</span>
                    </summary>
                    <p class="pb-4 lg:pb-5 -mt-1 lg:-mt-2 text-rv-ink-2 text-[15px] lg:text-base leading-[1.6]">{{ $item['resposta'] }}</p>
                </details>
            @endforeach
        </div>

        <a href="{{ config('reviteiros.home.whatsapp_url') }}" class="lg:hidden flex justify-center items-center min-h-14 rounded-rv-btn border border-rv-line-2 text-rv-ink font-semibold text-[15px]">Falar com o suporte no WhatsApp</a>
    </div>
</section>
