<?php

namespace App\Http\Requests\Api\V1\Suscripcion;

use Illuminate\Foundation\Http\FormRequest;

class ActivarRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'plan_id'          => 'required|exists:planes,id',
            'duracion_meses'   => 'nullable|integer|min:1|max:12',
            'metodo_pago'      => 'nullable|string|in:tarjeta,transferencia,cripto,manual',
            'referencia_pago'  => 'nullable|string|max:200',
        ];
    }
}
