<?php

namespace App\Observers;

use App\Models\Auditoria;
use Illuminate\Database\Eloquent\Model;

class AuditoriaObserver
{
    public function created(Model $model): void
    {
        $this->registrar('INSERT', $model, null, $model->getAttributes());
    }

    public function updated(Model $model): void
    {
        $this->registrar('UPDATE', $model, $model->getOriginal(), $model->getChanges());
    }

    public function deleted(Model $model): void
    {
        $this->registrar('DELETE', $model, $model->getOriginal(), null);
    }

    private function registrar(string $accion, Model $model, ?array $antes, ?array $despues): void
    {
        Auditoria::create([
            'user_id'        => auth()->id(),
            'tabla'          => $model->getTable(),
            'accion'         => $accion,
            'registro_id'    => $model->getKey(),
            'datos_antes'    => $antes,
            'datos_despues'  => $despues,
            'ip'             => request()->ip(),
            'user_agent'     => substr(request()->userAgent() ?? '', 0, 300),
        ]);
    }
}
