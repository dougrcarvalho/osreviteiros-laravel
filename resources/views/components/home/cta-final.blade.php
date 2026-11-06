@props(['pluginUrl'])

<section class="bg-rv-accent" aria-labelledby="titulo-cta">
    <div class="mx-auto max-w-rv-narrow px-4 md:px-6 py-12 lg:py-20 flex flex-col gap-[18px] lg:gap-5 items-stretch lg:items-center text-center text-white">
        <h2 id="titulo-cta" class="text-[27px] md:text-[34px] lg:text-[40px] leading-[1.15] lg:leading-normal tracking-[-0.02em] font-extrabold text-balance">Seu próximo projeto começa com a biblioteca pronta.</h2>
        <a href="{{ $pluginUrl }}" class="flex justify-center items-center min-h-[52px] lg:min-h-0 lg:inline-flex px-[26px] py-3.5 lg:py-4 rounded-xl bg-white hover:bg-rv-alt text-rv-ink font-bold text-base lg:text-[17px]">Baixar plugin grátis</a>
    </div>
</section>
