<?php

use App\Enums\TipoItem;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\FabricanteController;
use App\Http\Controllers\FamiliaController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PasswordResetController;
use App\Support\UrlListagem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

// Bibliotecas: /familias-revit, /texturas-revit-bim, /projetos-prontos-revit-bim
// Paginação SEO: /{biblioteca}/page/{n}/ (o infinite scroll atualiza a URL conforme a rolagem)
foreach (TipoItem::cases() as $tipo) {
    $segmento = $tipo->segmento();

    Route::get("/{$segmento}", [FamiliaController::class, 'index'])
        ->defaults('biblioteca', $tipo->value)
        ->name($tipo->rotaIndex());

    Route::get("/{$segmento}/page/{pagina}", [FamiliaController::class, 'index'])
        ->whereNumber('pagina')
        ->defaults('biblioteca', $tipo->value)
        ->name($tipo->rotaPagina());

    Route::get("/{$segmento}/{familia}", [FamiliaController::class, 'show'])
        ->defaults('biblioteca', $segmento)
        ->name($tipo->rotaShow());

    // URLs anteriores (/familias, /texturas, /projetos) → 301 mantendo a query
    $antigo = $tipo->segmentoAntigo();
    Route::get("/{$antigo}", fn (Request $request) => redirect()->to(
        UrlListagem::pagina(route($tipo->rotaIndex()), 1, $request->query()), 301
    ));
    Route::get("/{$antigo}/{slug}", fn (string $slug) => redirect()->to(route($tipo->rotaShow(), $slug), 301));
}

// Download (qualquer tipo de item; devolve o arquivo/link só se o plano permitir)
Route::post('/familias/{familia:id}/download', [DownloadController::class, 'download'])
    ->middleware('throttle:30,1')
    ->name('familias.download');

// Arquivos enviados pelo painel (link salvo na família; confere o plano)
Route::get('/arquivos/{path}', [DownloadController::class, 'arquivo'])
    ->where('path', '.*')
    ->middleware('throttle:30,1')
    ->name('arquivos.download');

// Fabricantes / marcas
Route::get('/fabricantes', [FabricanteController::class, 'index'])->name('fabricantes.index');
Route::get('/fabricantes/{fabricante}', [FabricanteController::class, 'show'])->name('fabricantes.show');
Route::get('/fabricantes/{fabricante}/page/{pagina}', [FabricanteController::class, 'show'])->whereNumber('pagina')->name('fabricantes.pagina');

// Auth
Route::middleware('guest')->group(function () {
    Route::get('/cadastro', [AuthController::class, 'showRegister'])->name('cadastro');
    Route::post('/cadastro', [AuthController::class, 'register'])->middleware('throttle:10,1');
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/esqueci-senha', [PasswordResetController::class, 'showForgot'])->name('password.request');
    Route::post('/esqueci-senha', [PasswordResetController::class, 'sendLink'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/redefinir-senha/{token}', [PasswordResetController::class, 'showReset'])->name('password.reset');
    Route::post('/redefinir-senha', [PasswordResetController::class, 'reset'])->name('password.update');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');
