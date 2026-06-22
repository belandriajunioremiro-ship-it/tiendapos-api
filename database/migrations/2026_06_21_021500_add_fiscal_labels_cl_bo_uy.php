<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $etiquetas = [
            // Chile 🇨🇱
            ['CL', 'identificacion',     'RUT',           'Ej: 12.345.678-9'],
            ['CL', 'impuesto_general',  'IVA',           'Impuesto al Valor Agregado'],
            ['CL', 'impuesto_adicional', 'Impuesto Adicional','Impuesto a bebidas alcohólicas, tabaco, etc.'],
            ['CL', 'factura',            'Factura',       'Electrónica / Boleta'],
            ['CL', 'nota_credito',       'Nota de Crédito',''],
            ['CL', 'documento_cliente',  'RUT/DNI',       ''],
            ['CL', 'regimen_fiscal',     'Régimen',       'General / PYME / Renta Presunta'],

            // Bolivia 🇧🇴
            ['BO', 'identificacion',     'NIT',           'Ej: 1234567890'],
            ['BO', 'impuesto_general',  'IVA',           'Impuesto al Valor Agregado'],
            ['BO', 'impuesto_adicional', 'IT',           'Impuesto a las Transacciones'],
            ['BO', 'factura',            'Factura',       'Fiscal / Dosificación'],
            ['BO', 'nota_credito',       'Nota de Crédito',''],
            ['BO', 'documento_cliente',  'NIT/CI',        ''],
            ['BO', 'regimen_fiscal',     'Régimen',       'General / Simplificado'],
        ];

        foreach ($etiquetas as $e) {
            DB::table('etiquetas_fiscales_pais')->updateOrInsert(
                ['pais' => $e[0], 'clave' => $e[1]],
                ['etiqueta' => $e[2], 'placeholder' => $e[3]]
            );
        }

        // Uruguay 🇺🇾 tiene régimen especial: IRIC vs PAÍS
        $etiquetasUy = [
            ['UY', 'identificacion',     'RUT',           'Ej: 123456789012'],
            ['UY', 'impuesto_general',  'IVA',           'Impuesto al Valor Agregado'],
            ['UY', 'impuesto_adicional', 'IMESI',        'Impuesto Específico Interno'],
            ['UY', 'factura',            'Factura',       'Electrónica / Física'],
            ['UY', 'nota_credito',       'Nota de Crédito',''],
            ['UY', 'documento_cliente',  'RUT/CI',        ''],
            ['UY', 'regimen_fiscal',     'Régimen',       'IVA General / Mínimo / Exportador'],
        ];

        foreach ($etiquetasUy as $e) {
            DB::table('etiquetas_fiscales_pais')->updateOrInsert(
                ['pais' => $e[0], 'clave' => $e[1]],
                ['etiqueta' => $e[2], 'placeholder' => $e[3]]
            );
        }
    }

    public function down(): void
    {
        DB::table('etiquetas_fiscales_pais')
            ->whereIn('pais', ['CL', 'BO', 'UY'])
            ->delete();
    }
};
