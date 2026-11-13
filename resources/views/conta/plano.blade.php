<x-conta.layout :titulo="__('Plano e assinatura')">
    {{-- Plano atual --}}
    <section class="rv-conta__painel">
        <h2>{{ __('Seu plano') }}</h2>

        <p class="rv-conta__plano"><i class="ti ti-crown"></i> {{ __($atual?->nome ?? 'Grátis') }}
            @if ($info && $info['preco'] > 0)
                <span class="rv-conta__dica">· R$ {{ \App\Support\Idiomas::numero($info['preco'], 2) }}{{ __('/mês') }}</span>
            @endif
        </p>

        @if ($desde)
            <p class="rv-conta__dica">{{ __('No plano desde :data', ['data' => $desde->isoFormat('LL')]) }}</p>
        @endif

        @if ($info)
            <ul class="rv-conta__beneficios">
                @foreach ($info['beneficios'] as $beneficio)
                    <li><i class="ti ti-check"></i> {{ __($beneficio) }}</li>
                @endforeach
            </ul>
        @endif

        <p class="rv-conta__dica">{{ __('O plano vale até ser alterado por um pagamento confirmado ou pela nossa equipe.') }}</p>
    </section>

    {{-- Upgrade --}}
    @if ($upgrades->isNotEmpty())
        <section class="rv-conta__painel">
            <h2>{{ __('Fazer upgrade') }}</h2>
            <div class="rv-conta__upgrades">
                @foreach ($upgrades as $opcao)
                    <div @class(['rv-conta__upgrade', 'rv-conta__upgrade--destaque' => $opcao['info']['destaque'] ?? false])>
                        <h3>{{ __($opcao['plano']->nome) }}</h3>
                        @if ($opcao['info'])
                            <p class="rv-conta__preco">R$ {{ \App\Support\Idiomas::numero($opcao['info']['preco'], 2) }}<span>{{ __('/mês') }}</span></p>
                            <ul class="rv-conta__beneficios">
                                @foreach ($opcao['info']['beneficios'] as $beneficio)
                                    <li><i class="ti ti-check"></i> {{ __($beneficio) }}</li>
                                @endforeach
                            </ul>
                        @endif

                        @if ($opcao['checkout'])
                            <a href="{{ $opcao['checkout'] }}" class="rv-btn {{ ($opcao['info']['destaque'] ?? false) ? 'rv-btn--dourado' : 'rv-btn--primary' }}">{{ __('Assinar :plano', ['plano' => __($opcao['plano']->nome)]) }}</a>
                        @else
                            <a href="{{ config('reviteiros.home.whatsapp_url') }}" class="rv-btn rv-btn--outline">{{ __('Falar com o suporte') }}</a>
                        @endif
                    </div>
                @endforeach
            </div>
            <p class="rv-conta__dica">{{ __('Após o pagamento, o acesso do novo plano é liberado automaticamente.') }}</p>
        </section>
    @endif

    {{-- Cancelamento --}}
    @if ($atual?->isPago())
        <section class="rv-conta__painel" id="cancelar">
            <h2>{{ __('Cancelar assinatura') }}</h2>

            @if ($pendente)
                <div class="rv-alerta rv-alerta--info">
                    {{ __('Pedido de cancelamento registrado em :data. Nossa equipe vai confirmá-lo em breve.', ['data' => $pendente->created_at->isoFormat('L LT')]) }}
                </div>
                <form method="POST" action="{{ route('conta.cancelamento.desfazer') }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="rv-btn rv-btn--outline rv-btn--auto">{{ __('Desistir do cancelamento') }}</button>
                </form>
            @else
                <p>{{ __('Você pode cancelar quando quiser. O acesso é encerrado no cancelamento; na primeira compra há reembolso em até 7 dias.') }}</p>

                <form method="POST" action="{{ route('conta.cancelamento') }}" x-data="{ aberto: @js($errors->any()) }">
                    @csrf
                    <button type="button" class="rv-btn rv-btn--outline rv-btn--auto" x-show="!aberto" @click="aberto = true">{{ __('Quero cancelar') }}</button>

                    <div x-show="aberto" x-cloak>
                        <div class="rv-campo">
                            <label class="rv-label" for="motivo">{{ __('Quer nos contar o motivo? (opcional)') }}</label>
                            <textarea id="motivo" name="motivo" rows="3" maxlength="1000" class="rv-input">{{ old('motivo') }}</textarea>
                            @error('motivo')<p class="rv-erro-campo">{{ $message }}</p>@enderror
                        </div>
                        <label class="rv-campo flex items-center gap-2 text-sm">
                            <input type="checkbox" name="confirmar" value="1" required> {{ __('Entendo que o acesso ao plano será encerrado.') }}
                        </label>
                        @error('confirmar')<p class="rv-erro-campo">{{ $message }}</p>@enderror
                        <button type="submit" class="rv-btn rv-btn--perigo rv-btn--auto">{{ __('Confirmar cancelamento') }}</button>
                        <button type="button" class="rv-link" @click="aberto = false">{{ __('Voltar') }}</button>
                    </div>
                </form>
            @endif

            <p class="rv-conta__dica mt-3">
                {{ __('Pagou por Pix recorrente? O cancelamento também é feito pelo suporte via WhatsApp.') }}
                <a href="{{ config('reviteiros.home.whatsapp_url') }}" class="rv-link">{{ __('Falar com o suporte') }}</a>
            </p>
        </section>
    @endif

    {{-- Histórico de planos --}}
    @if ($historico->isNotEmpty())
        <section class="rv-conta__painel">
            <h2>{{ __('Histórico do plano') }}</h2>
            <ul class="rv-conta__tempo">
                @foreach ($historico as $item)
                    <li>
                        <time datetime="{{ $item->created_at->toIso8601String() }}">{{ $item->created_at->isoFormat('L') }}</time>
                        <span>
                            @if ($item->planoAnterior)
                                {{ __($item->planoAnterior->nome) }} →
                            @endif
                            <strong>{{ __($item->planoNovo?->nome ?? 'Grátis') }}</strong>
                        </span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</x-conta.layout>
