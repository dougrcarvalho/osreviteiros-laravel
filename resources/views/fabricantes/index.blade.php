<x-layouts.app title="Fabricantes" description="Fabricantes e marcas com famílias Revit BIM disponíveis na biblioteca Os Reviteiros." :canonical="route('fabricantes.index')">
    <section class="rv-page">
        <div class="rv-container rv-fabricantes">
            <header class="rv-fabricantes__header">
                <h1>Fabricantes</h1>
                <span class="rv-badge">{{ $fabricantes->count() }} fabricantes</span>
            </header>

            {{-- Busca client-side (Alpine.js) --}}
            <div x-data="{ busca: '' }">
                <div class="rv-busca" style="margin-left:0">
                    <input type="search" x-model="busca" placeholder="buscar fabricante..." class="rv-busca__input" aria-label="Buscar fabricante">
                    <span class="rv-busca__btn flex items-center"><i class="ti ti-search"></i></span>
                </div>

                <div class="rv-fabricantes-grid">
                    @foreach ($fabricantes as $fab)
                        <a href="{{ route('fabricantes.show', $fab) }}"
                           class="rv-fabricante-card"
                           data-nome="{{ \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii($fab->nome)) }}"
                           x-show="!busca || $el.dataset.nome.includes(busca.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase())">
                            @if ($fab->logo_url)
                                <img src="{{ $fab->logo_url }}" alt="{{ $fab->nome }}" class="rv-fabricante-card__logo" loading="lazy">
                            @else
                                <div class="rv-fabricante-card__inicial">{{ $fab->inicial }}</div>
                            @endif

                            <div class="rv-fabricante-card__info">
                                <strong>{{ $fab->nome }}</strong>
                                <span>{{ $fab->familias_count }} {{ $fab->familias_count === 1 ? 'família' : 'famílias' }}</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
</x-layouts.app>
