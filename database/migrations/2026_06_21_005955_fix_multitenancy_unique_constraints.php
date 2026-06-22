<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. categorias_productos: slug UNIQUE -> (tienda_id, slug) unique
        DB::statement('ALTER TABLE categorias_productos DROP CONSTRAINT IF EXISTS categorias_productos_slug_key');
        DB::statement('CREATE UNIQUE INDEX categorias_productos_tienda_slug_unique ON categorias_productos(tienda_id, slug)');

        // 2. margenes_ganancia: idx_margen_defecto global -> per-tienda
        DB::statement('DROP INDEX IF EXISTS idx_margen_defecto');
        DB::statement('CREATE UNIQUE INDEX idx_margen_defecto ON margenes_ganancia(tienda_id) WHERE es_defecto = TRUE');

        // 3. tasas_cambio: idx_tasa_activa global -> per-tienda
        DB::statement('DROP INDEX IF EXISTS idx_tasa_activa');
        DB::statement('CREATE UNIQUE INDEX idx_tasa_activa ON tasas_cambio(tienda_id, moneda_base, moneda_destino, fuente) WHERE activa = TRUE');

        // 4. productos: codigo_sku UNIQUE global -> per-tienda
        DB::statement('ALTER TABLE productos DROP CONSTRAINT IF EXISTS productos_codigo_sku_key');
        DB::statement('CREATE UNIQUE INDEX productos_tienda_sku_unique ON productos(tienda_id, codigo_sku)');

        // 5. variantes_producto: codigo_barra UNIQUE global -> per-tienda
        DB::statement('ALTER TABLE variantes_producto DROP CONSTRAINT IF EXISTS variantes_producto_codigo_barra_key');
        DB::statement('CREATE UNIQUE INDEX variantes_tienda_barra_unique ON variantes_producto(tienda_id, codigo_barra) WHERE codigo_barra IS NOT NULL');

        // 6. unidades: abreviatura UNIQUE global -> per-tienda
        DB::statement('ALTER TABLE unidades DROP CONSTRAINT IF EXISTS unidades_abreviatura_key');
        DB::statement('CREATE UNIQUE INDEX unidades_tienda_abreviatura_unique ON unidades(tienda_id, abreviatura)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS categorias_productos_tienda_slug_unique');
        DB::statement('ALTER TABLE categorias_productos ADD UNIQUE (slug)');

        DB::statement('DROP INDEX IF EXISTS idx_margen_defecto');
        DB::statement('CREATE UNIQUE INDEX idx_margen_defecto ON margenes_ganancia(es_defecto) WHERE es_defecto = TRUE');

        DB::statement('DROP INDEX IF EXISTS idx_tasa_activa');
        DB::statement('CREATE UNIQUE INDEX idx_tasa_activa ON tasas_cambio(moneda_base, moneda_destino, fuente) WHERE activa = TRUE');

        DB::statement('DROP INDEX IF EXISTS productos_tienda_sku_unique');
        DB::statement('ALTER TABLE productos ADD UNIQUE (codigo_sku)');

        DB::statement('DROP INDEX IF EXISTS variantes_tienda_barra_unique');
        DB::statement('ALTER TABLE variantes_producto ADD UNIQUE (codigo_barra)');

        DB::statement('DROP INDEX IF EXISTS unidades_tienda_abreviatura_unique');
        DB::statement('ALTER TABLE unidades ADD UNIQUE (abreviatura)');
    }
};
