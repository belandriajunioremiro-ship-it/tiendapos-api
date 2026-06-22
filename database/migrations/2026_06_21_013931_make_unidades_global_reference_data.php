<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP INDEX IF EXISTS unidades_tienda_abreviatura_unique');
        DB::statement('ALTER TABLE unidades ALTER COLUMN tienda_id DROP NOT NULL');
        DB::statement('ALTER TABLE unidades DROP CONSTRAINT IF EXISTS unidades_tienda_id_foreign');
        DB::statement('CREATE UNIQUE INDEX unidades_abreviatura_unique ON unidades(abreviatura)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS unidades_abreviatura_unique');
        DB::statement('ALTER TABLE unidades ADD CONSTRAINT unidades_tienda_id_foreign FOREIGN KEY (tienda_id) REFERENCES tienda(id) ON DELETE CASCADE');
        DB::statement('ALTER TABLE unidades ALTER COLUMN tienda_id SET NOT NULL');
        DB::statement('CREATE UNIQUE INDEX unidades_tienda_abreviatura_unique ON unidades(tienda_id, abreviatura)');
    }
};
