<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SolicitarCancelamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'confirmar' => ['accepted'],
            'motivo'    => ['nullable', 'string', 'max:1000'],
        ];
    }
}
