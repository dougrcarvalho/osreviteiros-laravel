<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

/** GET /api/categorias/opcoes?biblioteca=familia&taxonomia=sub&cat=automoveis */
class CategoriaOpcoesRequest extends FamiliaFiltroRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'biblioteca' => ['required', Rule::in(array_column(\App\Enums\TipoItem::cases(), 'value'))],
            'taxonomia'  => ['required', 'string', 'max:50'],
        ]);
    }
}
