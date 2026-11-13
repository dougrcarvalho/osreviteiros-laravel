@props(['video', 'pluginUrl'])

@php
    $passos = [
        [__('Busque dentro do Revit'), __('Famílias, texturas, templates e projetos no mesmo painel.')],
        [__('Favorite o que você mais usa'), __('Monte sua biblioteca pessoal e encontre tudo em segundos.')],
        [__('Clique e insira'), __('A família é carregada e posicionada no projeto aberto.')],
    ];
@endphp

{{-- Celular: cabeçalho, vídeo e passos nessa ordem. Desktop: texto à esquerda, vídeo à direita. --}}
<section aria-labelledby="titulo-plugin">
    <div class="mx-auto max-w-rv px-4 md:px-6 py-12 md:py-16 lg:py-24 grid gap-5 md:gap-8 lg:grid-cols-2 lg:gap-x-14 lg:gap-y-[22px] lg:items-center">
        <div class="flex flex-col gap-4 lg:gap-[22px] lg:col-start-1 lg:row-start-1 lg:self-end">
            <p class="text-xs lg:text-sm font-bold tracking-[0.06em] uppercase text-rv-accent">{{ __('O plugin') }}</p>
            <h2 id="titulo-plugin" class="text-[27px] md:text-[34px] lg:text-[40px] leading-[1.12] lg:leading-[1.1] tracking-[-0.02em] font-bold text-balance">{{ __('Do catálogo direto para o seu modelo. Sem pasta de downloads.') }}</h2>
        </div>

        <div id="video-plugin" class="aspect-[16/10] rounded-rv-card lg:rounded-rv-card-lg bg-rv-dark-2 overflow-hidden flex items-center justify-center text-rv-on-dark text-sm lg:text-[15px] scroll-mt-20 lg:col-start-2 lg:row-span-2 lg:row-start-1">
            @if ($video['mp4'] || $video['webm'])
                <video class="size-full object-cover" controls playsinline preload="none" width="1040" height="650"
                       @if ($video['poster']) poster="{{ asset($video['poster']) }}" @endif
                       aria-label="{{ __($video['titulo']) }}">
                    @if ($video['webm'])<source src="{{ asset($video['webm']) }}" type="video/webm">@endif
                    @if ($video['mp4'])<source src="{{ asset($video['mp4']) }}" type="video/mp4">@endif
                </video>
            @elseif ($video['youtube'])
                <button type="button" data-rv-youtube="{{ $video['youtube'] }}" data-rv-classe="size-full border-0"
                        aria-label="{{ __('Reproduzir vídeo') }}: {{ __($video['titulo']) }}"
                        class="group relative size-full cursor-pointer">
                    <img src="{{ $video['poster'] ? asset($video['poster']) : 'https://i.ytimg.com/vi_webp/'.$video['youtube'].'/hqdefault.webp' }}"
                         alt="" width="1040" height="650" loading="lazy" decoding="async" class="size-full object-cover">
                    <span class="absolute inset-0 m-auto size-[72px] rounded-full bg-rv-ink/80 group-hover:bg-rv-accent flex items-center justify-center" aria-hidden="true">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="#FFFFFF"><path d="M8 5v14l11-7z"></path></svg>
                    </span>
                </button>
            @else
                <span class="px-6 text-center">{{ __('[vídeo/GIF curto do plugin inserindo uma família no Revit]') }}</span>
            @endif
        </div>

        <div class="flex flex-col gap-5 lg:gap-[22px] lg:col-start-1 lg:row-start-2 lg:self-start">
            <ol class="flex flex-col gap-4 lg:gap-[18px]">
                @foreach ($passos as [$titulo, $texto])
                    <li class="flex gap-3.5 lg:gap-4">
                        <span class="flex-none size-[30px] lg:size-8 rounded-full bg-rv-accent-soft text-rv-accent text-sm lg:text-base font-bold flex items-center justify-center" aria-hidden="true">{{ $loop->iteration }}</span>
                        <div>
                            <strong class="block text-base lg:text-[17px]">{{ $titulo }}</strong>
                            <span class="text-rv-ink-2 text-[15px] lg:text-base">{{ $texto }}</span>
                        </div>
                    </li>
                @endforeach
            </ol>

            <div class="hidden lg:flex flex-wrap gap-3 pt-1.5">
                <a href="{{ $pluginUrl }}" class="px-[22px] py-3.5 rounded-rv-btn bg-rv-accent hover:bg-rv-accent-hover text-white font-semibold text-base">{{ __('Baixar plugin grátis') }}</a>
                <a href="#video-plugin" class="px-[22px] py-3.5 rounded-rv-btn border border-rv-line-2 hover:border-rv-ink text-rv-ink font-semibold text-base">{{ __('Ver o plugin em 60 s') }}</a>
            </div>
        </div>
    </div>
</section>
