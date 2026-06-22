<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE auditoria ALTER COLUMN accion TYPE VARCHAR(50)');
        DB::statement("COMMENT ON COLUMN auditoria.accion IS 'anular_venta | ajustar_inventario | crear_devolucion | crear_usuario | desactivar_usuario | cambiar_password'");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE auditoria ALTER COLUMN accion TYPE VARCHAR(10)');
    }
};
