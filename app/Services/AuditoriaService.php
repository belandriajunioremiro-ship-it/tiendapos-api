<?php

namespace App\Services;

use App\Models\Auditoria;
use Illuminate\Database\Eloquent\Model;

class AuditoriaService
{
    /**
     * Registra una acción crítica en la bitácora de auditoría (particionada por año).
     *
     * @param  string       $accion      Ej: 'anular_venta', 'ajustar_inventario', 'crear_usuario'
     * @param  string       $tabla       Nombre de la tabla afectada
     * @param  int          $registroId  ID del registro afectado
     * @param  array|null   $datosAntes  Snapshot del estado anterior (para updates/delete)
     * @param  array|null   $datosDespues Snapshot del nuevo estado (para create/update)
     */
    public function registrar(
        string $accion,
        string $tabla,
        int    $registroId,
        ?array $datosAntes = null,
        ?array $datosDespues = null,
    ): Auditoria {
        return Auditoria::create([
            'user_id'      => auth()->id(),
            'accion'       => $accion,
            'tabla'        => $tabla,
            'registro_id'  => $registroId,
            'datos_antes'  => $datosAntes,
            'datos_despues' => $datosDespues,
            'ip'           => request()->ip(),
            'user_agent'   => request()->userAgent(),
        ]);
    }

    /**
     * Helper: extrae datos relevantes de un modelo Eloquent para el snapshot.
     */
    public static function snapshot(Model $model, array $only = []): array
    {
        $data = $model->toArray();
        unset($data['created_at'], $data['updated_at'], $data['creado_en'], $data['actualizado_en']);

        if (!empty($only)) {
            return array_intersect_key($data, array_flip($only));
        }

        return $data;
    }
}
