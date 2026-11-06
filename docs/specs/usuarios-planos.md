# Spec: Usuários e Planos

## Migration: Planos

```php
Schema::create('planos', function (Blueprint $table) {
    $table->id();
    $table->string('nome');           // "Grátis", "Basic", "Plus", "Premium"
    $table->string('slug')->unique(); // 'gratis', 'basic', 'plus', 'premium'
    $table->boolean('pago')->default(false);
    $table->string('checkout_url')->nullable();  // Hotmart ou Eduzz
    $table->integer('ordem')->default(0);
    $table->timestamps();
});
```

## Migration: Users

```php
// Adicionar ao migration padrão do Laravel:
Schema::table('users', function (Blueprint $table) {
    $table->unsignedBigInteger('plano_id')->nullable()->after('email');
    $table->foreign('plano_id')->references('id')->on('planos')->nullOnDelete();
});
```

## Seed: Planos

```php
Plano::insert([
    ['nome'=>'Grátis',   'slug'=>'gratis',   'pago'=>false, 'checkout_url'=>'https://eduzz.com/...',   'ordem'=>1],
    ['nome'=>'Basic',    'slug'=>'basic',    'pago'=>true,  'checkout_url'=>'https://hotmart.com/...', 'ordem'=>2],
    ['nome'=>'Plus',     'slug'=>'plus',     'pago'=>true,  'checkout_url'=>'https://hotmart.com/...', 'ordem'=>3],
    ['nome'=>'Premium',  'slug'=>'premium',  'pago'=>true,  'checkout_url'=>'https://hotmart.com/...', 'ordem'=>4],
]);
```

## Model: Plano

```php
class Plano extends Model
{
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function isPago(): bool
    {
        return $this->pago;
    }
}
```

## Model: User

```php
class User extends Authenticatable
{
    protected $fillable = ['name', 'email', 'password', 'plano_id'];

    public function plano(): BelongsTo
    {
        return $this->belongsTo(Plano::class);
    }

    // ─── Helpers de plano ───────────────────────────────────────
    public function hasPlano(): bool
    {
        return $this->plano?->isPago() ?? false;
    }

    public function hasPlanoGratis(): bool
    {
        return $this->plano?->slug === 'gratis';
    }

    public function podeDownload(Familia $familia): bool
    {
        if ($this->hasPlano()) return true;
        if ($this->hasPlanoGratis() && $familia->isGratuita()) return true;
        return false;
    }
}
```

## Controller: AuthController

```php
class AuthController extends Controller
{
    public function showRegister(): View
    {
        $planos = Plano::orderBy('ordem')->get();
        return view('auth.register', compact('planos'));
    }

    public function register(RegisterRequest $request): RedirectResponse
    {
        $plano = Plano::findOrFail($request->plano_id);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'plano_id' => $plano->id,
        ]);

        Auth::login($user);

        // Redirecionar por plano
        if ($plano->isPago()) {
            return redirect($plano->checkout_url);
        }

        return redirect()->route('familias.index')
                         ->with('success', 'Bem-vindo(a) aos Reviteiros!');
    }
}
```

## Popup de Cadastro (3 planos)

```html
<!-- resources/views/components/popup-planos.blade.php -->
<div x-data="{ open: false }"
     x-on:abrir-popup.window="open = true"
     x-show="open"
     class="rv-popup-overlay">

    <div class="rv-popup" @click.outside="open = false">
        <button @click="open = false" class="rv-popup__close">
            <i class="ti ti-x"></i>
        </button>

        <h2>Acesse as Famílias Revit</h2>
        <p>Escolha seu plano e comece agora</p>

        <div class="rv-planos-grid">
            <!-- Grátis -->
            <div class="rv-plano rv-plano--gratis">
                <h3>Grátis</h3>
                <p>Acesso a famílias selecionadas</p>
                <a href="/cadastro?plano=gratis" class="rv-btn rv-btn--outline">
                    Criar Conta Grátis
                </a>
            </div>

            <!-- Basic -->
            <div class="rv-plano rv-plano--basic">
                <h3>Basic</h3>
                <p>Biblioteca completa</p>
                <a href="/cadastro?plano=basic" class="rv-btn rv-btn--primary">
                    Assinar Basic
                </a>
            </div>

            <!-- Premium -->
            <div class="rv-plano rv-plano--premium rv-plano--destaque">
                <span class="rv-badge">Mais Popular</span>
                <h3>Premium</h3>
                <p>Tudo do Basic + novas famílias</p>
                <a href="/cadastro?plano=premium" class="rv-btn rv-btn--dourado">
                    Assinar Premium
                </a>
            </div>
        </div>

        <p class="rv-popup__login">
            Já tem conta? <a href="/login">Entrar</a>
        </p>
    </div>
</div>
```

## Webhook de Pagamento

```php
// routes/api.php
Route::post('/webhooks/hotmart',  [WebhookController::class, 'hotmart']);
Route::post('/webhooks/eduzz',    [WebhookController::class, 'eduzz']);

// app/Http/Controllers/WebhookController.php
public function hotmart(Request $request): Response
{
    // Validar assinatura Hotmart
    // Encontrar user por email
    // Atualizar plano_id conforme produto comprado
    $user = User::where('email', $request->buyer_email)->first();
    if ($user) {
        $plano = Plano::where('slug', $this->mapHotmartProduct($request->product_id))->first();
        $user->update(['plano_id' => $plano?->id]);
    }
    return response('OK');
}
```
