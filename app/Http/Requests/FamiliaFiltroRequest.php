<?php

namespace App\Http\Requests;

use App\Enums\TipoItem;
use App\Models\Taxonomia;
use App\Services\FamiliaBuscaService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Filtros da listagem. Além dos fixos, qualquer parâmetro simples (?cat=, ?cor=…)
 * é aceito como slug de taxonomia; os que não existirem na biblioteca são ignorados.
 */
class FamiliaFiltroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page'       => ['nullable', 'integer', 'min:1'],
            'per_page'   => ['nullable', 'integer', 'min:1', 'max:'.FamiliaBuscaService::MAX_PER_PAGE],
            'biblioteca' => ['nullable', Rule::in(array_column(TipoItem::cases(), 'value'))],
            'gratis'     => ['nullable', 'boolean'],
            'recurso'    => ['nullable', 'string', 'max:30'],
            'incluir'    => ['nullable', 'string', 'max:300'],
            'excluir'    => ['nullable', 'string', 'max:300'],
            'marca'      => ['nullable', 'string', 'max:600'],
            'busca'      => ['nullable', 'string', 'max:100'],
            'orderby'    => ['nullable', Rule::in(FamiliaBuscaService::ORDENS)],
        ];
    }

    /** @return array<string, mixed> */
    public function filtros(): array
    {
        $filtros = $this->safe()->only(['biblioteca', 'gratis', 'recurso', 'incluir', 'excluir', 'marca', 'busca', 'orderby']);

        // Demais parâmetros = slugs de taxonomia (só strings curtas, nome restrito)
        foreach ($this->query() as $chave => $valor) {
            if (isset($filtros[$chave]) || in_array($chave, Taxonomia::RESERVADOS, true)) {
                continue;
            }

            if (is_string($valor) && preg_match('/^[a-z0-9_-]{1,50}$/', $chave) && mb_strlen($valor) <= 200) {
                $filtros[$chave] = $valor;
            }
        }

        return $filtros;
    }

    public function perPage(): int
    {
        return (int) ($this->validated('per_page') ?? FamiliaBuscaService::PER_PAGE);
    }
}
