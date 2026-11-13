@props(['lista' => false])
@php
    $idiomaAtual = \App\Support\Idiomas::atual();
    $idiomas = \App\Support\Idiomas::todos();
@endphp

@if ($lista)
    {{-- Versão em linha (menu do celular) --}}
    <nav aria-label="{{ __('Idioma') }}" class="flex flex-wrap gap-2">
        @foreach ($idiomas as $codigo => $dados)
            <a href="{{ \App\Support\Idiomas::urlEm($codigo) }}" hreflang="{{ $dados['html'] }}" lang="{{ $dados['html'] }}"
               @if ($codigo === $idiomaAtual) aria-current="true" @endif
               class="rv-idioma-op flex min-h-11 items-center rounded-rv-btn border px-3.5 text-sm font-semibold {{ $codigo === $idiomaAtual ? 'border-rv-ink bg-rv-ink text-white' : 'border-rv-line-2 text-rv-ink hover:border-rv-ink' }}">{{ $dados['nome'] }}</a>
        @endforeach
    </nav>
@else
    <details class="rv-idioma relative">
        <summary class="flex cursor-pointer list-none items-center gap-1.5 px-2.5 py-2 text-[15px] font-semibold text-rv-ink hover:text-rv-accent [&::-webkit-details-marker]:hidden" aria-label="{{ __('Idioma') }}: {{ $idiomas[$idiomaAtual]['nome'] }}">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M3 12h18"></path><path d="M12 3c2.5 2.7 3.8 5.7 3.8 9S14.5 18.3 12 21c-2.5-2.7-3.8-5.7-3.8-9S9.5 5.7 12 3z"></path></svg>
            {{ $idiomas[$idiomaAtual]['curto'] }}
        </summary>
        <ul class="absolute right-0 z-50 mt-1 min-w-36 rounded-rv-btn border border-rv-line bg-white py-1 shadow-lg">
            @foreach ($idiomas as $codigo => $dados)
                <li>
                    <a href="{{ \App\Support\Idiomas::urlEm($codigo) }}" hreflang="{{ $dados['html'] }}" lang="{{ $dados['html'] }}"
                       @if ($codigo === $idiomaAtual) aria-current="true" @endif
                       class="block px-4 py-2 text-[15px] whitespace-nowrap {{ $codigo === $idiomaAtual ? 'font-bold text-rv-accent' : 'text-rv-body hover:bg-rv-alt' }}">{{ $dados['nome'] }}</a>
                </li>
            @endforeach
        </ul>
    </details>
@endif
