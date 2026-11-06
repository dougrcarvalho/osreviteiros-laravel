@props(['fabricantes'])

<section class="bg-rv-alt border-y border-rv-line" aria-labelledby="titulo-fabricantes">
    <div class="mx-auto max-w-rv py-6 md:py-8 lg:py-12 flex flex-col gap-3.5 lg:gap-6 lg:items-center">
        <h2 id="titulo-fabricantes" class="sr-only">Fabricantes</h2>
        <p class="px-4 md:px-6 text-[13px] lg:text-[15px] text-rv-ink-2 text-center">
            <span class="lg:hidden">Produtos reais de fabricantes como</span>
            <span class="hidden lg:inline">Famílias modeladas a partir de produtos reais de fabricantes como</span>
        </p>
        <ul class="rv-rolagem flex gap-7 overflow-x-auto px-4 md:px-6 lg:px-0 lg:flex-wrap lg:justify-center lg:gap-x-11 lg:gap-y-[18px] whitespace-nowrap lg:whitespace-normal text-lg lg:text-[22px] font-bold text-[#3A414C] tracking-[-0.01em]">
            @foreach ($fabricantes as $fabricante)
                <li class="flex-none flex items-center">
                    @if ($fabricante['url'])
                        <a href="{{ $fabricante['url'] }}" class="inline-flex items-center text-[#3A414C] hover:text-rv-accent" title="{{ $fabricante['nome'] }}">
                            @include('components.home.partials.marca-conteudo', ['fabricante' => $fabricante])
                        </a>
                    @else
                        <span class="inline-flex items-center" title="{{ $fabricante['nome'] }}">
                            @include('components.home.partials.marca-conteudo', ['fabricante' => $fabricante])
                        </span>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
</section>
