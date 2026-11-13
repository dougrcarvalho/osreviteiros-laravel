@props(['categorias'])

@php($tons = ['bg-[#E9EEF5]', 'bg-[#EEF1EC]', 'bg-[#F3EEEA]', 'bg-[#ECEEF3]'])

{{-- Celular: 6 ambientes (destaque_mobile) e o botão "Ver os 12". Tablet e desktop: todos. --}}
<section aria-labelledby="titulo-ambientes">
    <div class="mx-auto max-w-rv px-4 md:px-6 pt-12 pb-6 md:pt-16 md:pb-8 lg:pt-20 lg:pb-10 flex flex-col gap-[18px] lg:gap-7">
        <div class="flex flex-wrap justify-between items-end gap-3">
            <h2 id="titulo-ambientes" class="text-[27px] md:text-[34px] tracking-[-0.02em] font-bold">{{ __('Navegue por ambiente') }}</h2>
            <a href="{{ route('familias.index') }}" class="rv-alvo hidden md:inline font-semibold text-[15px] text-rv-accent hover:text-rv-accent-hover">{{ __('Ver todas as categorias →') }}</a>
        </div>

        <ul class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-[repeat(auto-fit,minmax(min(176px,100%),1fr))] gap-3 md:gap-4">
            @foreach ($categorias as $categoria)
                <li @class(['hidden md:block' => ! $categoria['destaque_mobile']])>
                    <a href="{{ $categoria['url'] ?? '#' }}" class="group flex flex-col gap-2 lg:gap-2.5 text-rv-ink">
                        <div class="relative aspect-[4/3] rounded-[10px] lg:rounded-xl {{ $tons[$loop->index % 4] }} overflow-hidden flex items-end p-2.5">
                            @if ($categoria['imagem'])
                                <img src="{{ $categoria['imagem'] }}" alt="" width="400" height="300" loading="lazy" decoding="async"
                                     class="absolute inset-0 size-full object-cover transition-transform duration-300 group-hover:scale-105">
                            @else
                                <span class="text-[11px] text-rv-ink-2 bg-white/80 px-[7px] py-[3px] rounded-md">{{ __('[foto do ambiente]') }}</span>
                            @endif
                        </div>
                        <div class="flex flex-col gap-px lg:gap-0.5">
                            <h3 class="font-semibold text-[15px] lg:text-base group-hover:text-rv-accent">{{ $categoria['nome'] }}</h3>
                            <span class="text-xs lg:text-[13px] text-rv-ink-3">{{ __(':total famílias', ['total' => $categoria['total']]) }}</span>
                        </div>
                    </a>
                </li>
            @endforeach
        </ul>

        <a href="{{ route('familias.index') }}" class="md:hidden flex justify-center items-center min-h-12 rounded-rv-btn border border-rv-line-2 text-rv-ink font-semibold text-[15px]">{{ __('Ver os :total ambientes', ['total' => count($categorias)]) }}</a>
    </div>
</section>
