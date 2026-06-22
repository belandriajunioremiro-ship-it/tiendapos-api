<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tablas = [
        'productos',
        'variantes_producto',
        'clientes',
        'ventas',
        'items_venta',
        'pagos_venta',
        'devoluciones_venta',
        'items_devolucion',
        'cuentas_credito',
        'facturas_credito',
        'abonos_credito',
        'almacenes',
        'cajas',
        'sesiones_caja',
        'movimientos_caja',
        'categorias_productos',
        'margenes_ganancia',
        'listas_precio',
        'descuentos',
        'metodos_pago',
        'impuestos',
        'tasas_cambio',
        'ordenes_compra',
        'items_orden_compra',
        'recepciones_compra',
        'items_recepcion',
        'facturas_proveedor',
        'proveedores',
        'producto_proveedor',
        'ajustes_inventario',
        'items_ajuste',
        'traslados_stock',
        'items_traslado',
        'inventario',
        'inventario_lotes',
        'movimientos_inventario',
        'unidades',
        'definicion_atributos',
        'configuracion_impresora',
        'plantillas_impresion',
        'notificaciones',
    ];

    public function up(): void
    {
        $tiendaId = DB::table('tienda')->value('id');

        if (! $tiendaId) {
            return;
        }

        foreach ($this->tablas as $tabla) {
            if (! Schema::hasTable($tabla)) {
                continue;
            }

            if (! Schema::hasColumn($tabla, 'tienda_id')) {
                Schema::table($tabla, function (Blueprint $table) {
                    $table->unsignedBigInteger('tienda_id')->nullable()->after('id');
                    $table->index('tienda_id');
                });
            }
        }

        foreach ($this->tablas as $tabla) {
            if (Schema::hasTable($tabla) && Schema::hasColumn($tabla, 'tienda_id')) {
                DB::table($tabla)->whereNull('tienda_id')->update(['tienda_id' => $tiendaId]);
            }
        }

        foreach ($this->tablas as $tabla) {
            if (Schema::hasTable($tabla) && Schema::hasColumn($tabla, 'tienda_id')) {
                DB::statement("ALTER TABLE {$tabla} ALTER COLUMN tienda_id SET NOT NULL");

                $fkName = "{$tabla}_tienda_id_foreign";
                $fkExists = DB::selectOne("
                    SELECT 1 FROM information_schema.table_constraints
                    WHERE constraint_name = '{$fkName}' AND table_name = '{$tabla}'
                ");

                if (! $fkExists) {
                    DB::statement("ALTER TABLE {$tabla} ADD CONSTRAINT {$fkName} FOREIGN KEY (tienda_id) REFERENCES tienda(id) ON DELETE CASCADE");
                }
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tablas as $tabla) {
            if (Schema::hasTable($tabla) && Schema::hasColumn($tabla, 'tienda_id')) {
                $fkName = "{$tabla}_tienda_id_foreign";
                DB::statement("ALTER TABLE {$tabla} DROP CONSTRAINT IF EXISTS {$fkName}");

                Schema::table($tabla, function (Blueprint $table) use ($tabla) {
                    $table->dropIndex("{$tabla}_tienda_id_index");
                    $table->dropColumn('tienda_id');
                });
            }
        }
    }
};
