<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_impuesto_defecto');
        DB::statement('CREATE UNIQUE INDEX idx_impuesto_defecto ON impuestos(tienda_id) WHERE es_defecto = TRUE');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_impuesto_defecto');
        DB::statement('CREATE UNIQUE INDEX idx_impuesto_defecto ON impuestos(es_defecto) WHERE es_defecto = TRUE');
    }
};
