@php
    // [título, texto do desktop, texto do celular, ícone]
    $itens = [
        [__('Projetos prontos'), __('Conforme normas de prefeitura e financiamento, para adaptar e entregar.'), __('Conforme normas de prefeitura e financiamento.'), '<path d="M3 21h18"></path><path d="M5 21V8l7-5 7 5v13"></path><path d="M10 21v-6h4v6"></path>'],
        [__('Dimensionamento hidrossanitário'), __('Ferramenta incluída no Premium para dimensionar direto no modelo.'), __('Ferramenta incluída no Premium.'), '<path d="M12 3s6 6.5 6 11a6 6 0 0 1-12 0c0-4.5 6-11 6-11z"></path>'],
        [__('Fabricantes nacionais'), __('Deca, Docol, Amanco Wavin, Brasilit e outros produtos que o seu cliente realmente compra.'), __('Produtos que o seu cliente realmente compra.'), '<path d="M20 7l-8-4-8 4 8 4 8-4z"></path><path d="M4 7v10l8 4 8-4V7"></path><path d="M12 11v10"></path>'],
        [__('Famílias sob pedido'), __('Não encontrou? A equipe modela e a família entra na biblioteca.'), __('Não encontrou? A equipe modela.'), '<path d="M12 5v14"></path><path d="M5 12h14"></path>'],
    ];
@endphp

{{-- Celular: lista numerada dentro de um card. Desktop: 4 cards em grade (ícones). --}}
<section class="bg-rv-alt border-y border-rv-line" aria-labelledby="titulo-brasil">
    <div class="mx-auto max-w-rv px-4 md:px-6 py-12 md:py-16 lg:py-24 flex flex-col lg:flex-row gap-4 md:gap-6 lg:gap-14 lg:items-start">
        <div class="flex flex-col gap-4 lg:gap-[18px] lg:flex-[1_1_0] lg:min-w-0">
            <p class="text-xs lg:text-sm font-bold tracking-[0.06em] uppercase text-rv-accent">{{ __('Feito para o Brasil') }}</p>
            <h2 id="titulo-brasil" class="text-[27px] md:text-[34px] lg:text-[40px] leading-[1.12] lg:leading-[1.1] tracking-[-0.02em] font-bold text-balance">{{ __('Biblioteca gringa não aprova projeto na prefeitura.') }}</h2>
            <p class="hidden lg:block text-lg leading-[1.6] text-rv-ink-2">{{ __('Projetos prontos dentro das normas de prefeitura e financiamento, produtos de fabricantes nacionais e dimensionamento hidrossanitário pensado para a realidade brasileira.') }}</p>
            <a href="#planos" class="hidden lg:inline-flex self-start mt-1.5 px-[22px] py-3.5 rounded-rv-btn bg-rv-accent hover:bg-rv-accent-hover text-white font-semibold text-base">{{ __('Ver o que vem no Premium') }}</a>
        </div>

        <ol class="flex flex-col bg-white border border-rv-line rounded-2xl divide-y divide-[#EEF0F2] lg:flex-[1.4_1_0] lg:min-w-0 lg:grid lg:grid-cols-[repeat(auto-fit,minmax(min(250px,100%),1fr))] lg:gap-4 lg:divide-y-0 lg:border-0 lg:bg-transparent lg:rounded-none">
            @foreach ($itens as [$titulo, $texto, $textoCurto, $icone])
                <li class="flex gap-3.5 p-4 lg:flex-col lg:gap-3 lg:p-[26px] lg:bg-white lg:border lg:border-rv-line lg:rounded-rv-card">
                    <span class="lg:hidden flex-none size-9 rounded-[10px] bg-rv-accent-soft text-rv-accent font-bold flex items-center justify-center" aria-hidden="true">{{ $loop->iteration }}</span>
                    <svg class="hidden lg:block" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#0B4FC4" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $icone !!}</svg>
                    <div class="flex flex-col gap-0.5 lg:gap-3">
                        <h3 class="text-base lg:text-lg font-bold">{{ $titulo }}</h3>
                        <p class="md:hidden text-sm leading-normal text-rv-ink-2">{{ $textoCurto }}</p>
                        <p class="hidden md:block text-[15px] leading-[1.55] text-rv-ink-2">{{ $texto }}</p>
                    </div>
                </li>
            @endforeach
        </ol>
    </div>
</section>
