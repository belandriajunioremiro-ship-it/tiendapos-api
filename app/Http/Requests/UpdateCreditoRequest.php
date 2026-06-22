<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCreditoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasAnyPermission(['crear_credito']);
    }

    public function rules(): array
    {
        return [
            'limite' => 'sometimes|numeric|min:0',
            'estado' => 'sometimes|string|in:activa,suspendida,bloqueada,al_dia,vencida',
            'moneda' => 'sometimes|string|size:3|exists:monedas,codigo',
        ];
    }
}
