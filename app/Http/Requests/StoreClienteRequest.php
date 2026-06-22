<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasAnyPermission(['crear_clientes']);
    }

    public function rules(): array
    {
        return [
            'tipo_documento'    => 'required|string|max:5',
            'numero_documento'  => 'nullable|string|max:20',
            'nombre'            => 'required|string|max:200',
            'nombre_comercial'  => 'nullable|string|max:200',
            'telefono'          => 'nullable|string|max:20',
            'telefono2'         => 'nullable|string|max:20',
            'email'             => 'nullable|email|max:150',
            'direccion'         => 'nullable|string',
            'fecha_nacimiento'  => 'nullable|date',
            'tipo_cliente'      => 'required|string|in:natural,juridico,mayorista,empleado,vip',
            'lista_precio_id'   => 'nullable|integer|exists:listas_precio,id',
            'moneda_credito'    => 'nullable|string|size:3',
            'limite_credito'    => 'sometimes|numeric|min:0',
            'dias_credito'      => 'sometimes|integer|min:0',
            'notas'             => 'nullable|string',
            'activo'            => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required'       => 'El nombre del cliente es obligatorio.',
            'tipo_documento.required' => 'El tipo de documento es obligatorio.',
            'tipo_cliente.required' => 'Debe seleccionar el tipo de cliente.',
            'email.email'          => 'El correo electrónico no es válido.',
        ];
    }
}
