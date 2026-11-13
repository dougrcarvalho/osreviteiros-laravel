@php
    // Plus foi descontinuado: não é mais oferecido
    $planos = app(\App\Services\PlanoService::class)->todos()->reject(fn ($p) => $p->slug === \App\Models\Plano::PLUS);
    $destaque = \App\Models\Plano::PREMIUM;
    $resumos = [
        'gratis'  => __('Itens gratuitos da biblioteca'),
        'basic'   => __('Famílias e texturas ilimitadas'),
        'premium' => __('Famílias, texturas, projetos prontos e cursos'),
    ];
@endphp

{{--
    Abre com $dispatch('abrir-popup') (não logado → cadastro)
    ou $dispatch('abrir-upgrade') (logado grátis → planos pagos)
    ou $dispatch('abrir-upgrade', { premium: true }) (Basic tentando baixar projeto → só o Premium).
--}}
<div x-data="{ open: false, modo: 'cadastro' }"
     x-on:abrir-popup.window="modo = 'cadastro'; open = true"
     x-on:abrir-upgrade.window="modo = $event.detail?.premium ? 'premium' : 'upgrade'; open = true"
     x-on:keydown.escape.window="open = false"
     x-show="open"
     x-transition.opacity
     x-cloak
     class="rv-popup-overlay"
     role="dialog"
     aria-modal="true"
     aria-labelledby="rv-popup-titulo">

    <div class="rv-popup" @click.outside="open = false">
        <button type="button" @click="open = false" class="rv-popup__close" aria-label="{{ __('Fechar') }}">
            <i class="ti ti-x"></i>
        </button>

        <h2 id="rv-popup-titulo" class="rv-popup__titulo"
            x-text="{ upgrade: @js(__('Desbloqueie toda a biblioteca')), premium: @js(__('Projetos prontos são do Premium')) }[modo] ?? @js(__('Acesse as Famílias Revit BIM'))"></h2>
        <p class="rv-popup__sub"
           x-text="{ upgrade: @js(__('Este item é exclusivo para assinantes. Escolha seu plano:')), premium: @js(__('Faça upgrade para baixar projetos prontos e acessar os cursos:')) }[modo] ?? @js(__('Escolha seu plano e comece agora:'))"></p>

        <div class="rv-planos-grid">
            @foreach ($planos as $plano)
                <div class="rv-plano rv-plano--{{ $plano->slug }} {{ $plano->slug === $destaque ? 'rv-plano--destaque' : '' }}"
                     @if (! $plano->pago) x-show="modo === 'cadastro'"
                     @elseif ($plano->slug !== $destaque) x-show="modo !== 'premium'" @endif>
                    @if ($plano->slug === $destaque)
                        <span class="rv-badge"><i class="ti ti-star-filled"></i> {{ __('Mais popular') }}</span>
                    @endif

                    <h3>{{ __($plano->nome) }}</h3>
                    <p>{{ $resumos[$plano->slug] ?? ($plano->pago ? __('Acesso à biblioteca') : __('Itens gratuitos da biblioteca')) }}</p>

                    @auth
                        @if ($plano->pago && $plano->checkout_url)
                            <a href="{{ app(\App\Services\CadastroService::class)->checkoutUrl($plano, auth()->user()) }}"
                               class="rv-btn {{ $plano->slug === $destaque ? 'rv-btn--dourado' : 'rv-btn--primary' }}">
                                {{ __('Assinar :plano', ['plano' => __($plano->nome)]) }}
                            </a>
                        @endif
                    @else
                        <a href="{{ route('cadastro', ['plano' => $plano->slug]) }}"
                           class="rv-btn {{ ! $plano->pago ? 'rv-btn--outline' : ($plano->slug === $destaque ? 'rv-btn--dourado' : 'rv-btn--primary') }}">
                            {{ $plano->pago ? __('Assinar :plano', ['plano' => __($plano->nome)]) : __('Criar conta grátis') }}
                        </a>
                    @endauth
                </div>
            @endforeach
        </div>

        @guest
            <p class="rv-popup__login">{{ __('Já tem conta?') }} <a href="{{ route('login') }}">{{ __('Entrar') }}</a></p>
        @endguest
    </div>
</div>
