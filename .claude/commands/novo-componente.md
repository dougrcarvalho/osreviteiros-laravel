# Comando: /novo-componente

Cria um componente Livewire completo para o projeto Os Reviteiros.

## Uso

```
/novo-componente NomeDoComponente
```

## O que gera

1. `app/Http/Livewire/NomeDoComponente.php` — classe PHP
2. `resources/views/livewire/nome-do-componente.blade.php` — template
3. Registra na lista de componentes em `CLAUDE.md`

## Template PHP

```php
<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Illuminate\View\View;

class {NOME} extends Component
{
    // Propriedades públicas reativas
    // public string $exemplo = '';

    // Eventos que este componente emite
    // protected $emits = ['evento-disparado'];

    public function render(): View
    {
        return view('livewire.{slug}');
    }
}
```

## Template Blade

```html
<div class="rv-{slug}">
    {{-- Conteúdo do componente --}}
</div>
```

## Regras

- Sempre usar prefixo `rv-` nas classes CSS
- Props públicas recebem validação via `rules()`
- Usar `$this->dispatch()` para eventos (Livewire 3)
- Não usar jQuery — Alpine.js para micro-interações
