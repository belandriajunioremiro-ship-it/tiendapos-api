<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'tienda_id')) {
                $table->foreignId('tienda_id')->nullable()->after('id')->constrained('tienda')->nullOnDelete();
            }
            if (!Schema::hasColumn('users', 'activo')) {
                $table->boolean('activo')->default(true)->after('password');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'tienda_id')) {
                // Para Laravel, el nombre de la FK suele ser {table}_{column}_foreign
                // Hacemos el dropForeign por nombre de columna (array syntax) o string si falla.
                $table->dropForeign(['tienda_id']);
                $table->dropColumn('tienda_id');
            }
            if (Schema::hasColumn('users', 'activo')) {
                $table->dropColumn('activo');
            }
        });
    }
};
