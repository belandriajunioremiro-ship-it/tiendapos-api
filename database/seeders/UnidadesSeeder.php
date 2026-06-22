<?php

namespace Database\Seeders;

use App\Models\Unidad;
use Illuminate\Database\Seeder;

class UnidadesSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Sembrando unidades (V3.2 con es_vendible/es_logistica y base_id)...');

        // 1) Unidades BASE primero (base_id = NULL)
        $bases = [
            // abreviatura => [nombre, tipo, factor, es_vendible, es_logistica]
            'und' => ['Unidad',    'cantidad', 1,        true, true],
            'kg'  => ['Kilogramo', 'peso',     1,        true, true],
            'lt'  => ['Litro',     'volumen',  1,        true, true],
            'm'   => ['Metro',     'longitud', 1,        true, true],
        ];

        $baseIds = [];
        foreach ($bases as $abrev => $data) {
            $u = Unidad::updateOrCreate(
                ['abreviatura' => $abrev],
                [
                    'nombre'            => $data[0],
                    'tipo'              => $data[1],
                    'factor_conversion' => $data[2],
                    'base_id'           => null,
                    'es_vendible'       => $data[3],
                    'es_logistica'      => $data[4],
                    'activo'            => true,
                ]
            );
            $baseIds[$abrev] = $u->id;
        }

        // 2) Sub-unidades (referencian base_id)
        $subunidades = [
            // abreviatura => [nombre, tipo, factor, base, es_vendible, es_logistica]
            // — cantidad —
            'par'    => ['Par',           'cantidad', 2,         'und', true,  false],
            'bls10'  => ['Blister x10',   'cantidad', 10,        'und', true,  false],
            'doc'    => ['Docena',        'cantidad', 12,        'und', true,  false],
            'cja12'  => ['Caja x12',      'cantidad', 12,        'und', true,  true],
            'cja24'  => ['Caja x24',      'cantidad', 24,        'und', false, true],
            'cja6'   => ['Caja x6',       'cantidad', 6,         'und', true,  true],
            'cja48'  => ['Caja x48',      'cantidad', 48,        'und', false, true],
            'bul20'  => ['Bulto x20',     'cantidad', 20,        'und', false, true],
            // — peso —
            'g'      => ['Gramo',         'peso',     0.001,     'kg',  true,  false],
            'lb'     => ['Libra',         'peso',     0.453592,  'kg',  true,  false],
            'sac25'  => ['Saco x25kg',    'peso',     25,        'kg',  false, true],
            'ton'    => ['Tonelada',      'peso',     1000,      'kg',  false, true],
            // — volumen —
            'ml'     => ['Mililitro',     'volumen',  0.001,     'lt',  true,  false],
            'oz'     => ['Onza líquida',  'volumen',  0.0295735, 'lt',  true,  false],
            'gal'    => ['Galón',         'volumen',  3.785,     'lt',  true,  true],
            'pai19'  => ['Paila 19L',     'volumen',  19,        'lt',  false, true],
            'tam200' => ['Tambor 200L',   'volumen',  200,       'lt',  false, true],
            // — longitud —
            'mm'     => ['Milímetro',     'longitud', 0.001,     'm',   false, false],
            'cm'     => ['Centímetro',    'longitud', 0.01,      'm',   true,  false],
            'pulg'   => ['Pulgada',       'longitud', 0.0254,    'm',   true,  false],
            'rol100' => ['Rollo 100m',    'longitud', 100,       'm',   false, true],
        ];

        foreach ($subunidades as $abrev => $data) {
            Unidad::updateOrCreate(
                ['abreviatura' => $abrev],
                [
                    'nombre'            => $data[0],
                    'tipo'              => $data[1],
                    'factor_conversion' => $data[2],
                    'base_id'           => $baseIds[$data[3]] ?? null,
                    'es_vendible'       => $data[4],
                    'es_logistica'      => $data[5],
                    'activo'            => true,
                ]
            );
        }

        $this->command->info('Unidades sembradas correctamente.');
    }
}
