<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PrecioMaterialSeeder extends Seeder
{
    /**
     * Seed las tablas de precios de material.
     *
     * Reglas de precios basadas en la especificación:
     * - Precio base: Ø16 a 12m
     * - Incrementos por diámetro
     * - Incrementos por formato (general vs Siderúrgica Sevillana)
     */
    public function run(): void
    {
        // Incrementos por diámetro (€/tonelada respecto a Ø16)
        $diametros = [
            ['diametro' => 6, 'incremento' => 70.00],
            ['diametro' => 8, 'incremento' => 50.00],
            ['diametro' => 10, 'incremento' => 20.00],
            ['diametro' => 12, 'incremento' => 5.00],
            ['diametro' => 14, 'incremento' => 0.00],
            ['diametro' => 16, 'incremento' => 0.00],  // Base
            ['diametro' => 20, 'incremento' => 0.00],
            ['diametro' => 25, 'incremento' => 8.00],
            ['diametro' => 32, 'incremento' => 28.00],
            ['diametro' => 40, 'incremento' => 50.00],
        ];

        foreach ($diametros as $d) {
            DB::table('precios_material_diametros')->updateOrInsert(
                ['diametro' => $d['diametro']],
                [
                    'incremento' => $d['incremento'],
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // Formatos con incrementos (valor base por defecto)
        $formatos = [
            [
                'codigo' => 'estandar_12m',
                'nombre' => 'Estándar 12m',
                'descripcion' => 'Barras estándar de 12 metros (referencia)',
                'longitud_min' => 11.00,
                'longitud_max' => 13.00,
                'es_encarretado' => false,
                'incremento' => 0.00,
            ],
            [
                'codigo' => 'largo_especial',
                'nombre' => 'Largo Especial (14-16m)',
                'descripcion' => 'Barras de largo especial entre 14 y 16 metros',
                'longitud_min' => 14.00,
                'longitud_max' => 16.00,
                'es_encarretado' => false,
                'incremento' => 5.00,
            ],
            [
                'codigo' => 'corto_6m',
                'nombre' => 'Corto 6m',
                'descripcion' => 'Barras cortas de 6 metros o menos',
                'longitud_min' => null,
                'longitud_max' => 6.00,
                'es_encarretado' => false,
                'incremento' => 10.00,
            ],
            [
                'codigo' => 'encarretado',
                'nombre' => 'Encarretado',
                'descripcion' => 'Material en carretes',
                'longitud_min' => null,
                'longitud_max' => null,
                'es_encarretado' => true,
                'incremento' => 30.00, // Valor por defecto, Siderúrgica tiene excepción de 20€
            ],
        ];

        foreach ($formatos as $f) {
            DB::table('precios_material_formatos')->updateOrInsert(
                ['codigo' => $f['codigo']],
                [
                    'nombre' => $f['nombre'],
                    'descripcion' => $f['descripcion'],
                    'longitud_min' => $f['longitud_min'],
                    'longitud_max' => $f['longitud_max'],
                    'es_encarretado' => $f['es_encarretado'],
                    'incremento' => $f['incremento'],
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // Crear excepción para Siderúrgica Sevillana en encarretado (20€ en vez de 30€)
        $siderurgica = DB::table('fabricantes')
            ->whereRaw('LOWER(nombre) LIKE ?', ['%siderurgica%'])
            ->first();

        if ($siderurgica) {
            DB::table('precios_material_excepciones')->updateOrInsert(
                [
                    'distribuidor_id' => null,
                    'fabricante_id' => $siderurgica->id,
                    'formato_codigo' => 'encarretado',
                ],
                [
                    'incremento' => 20.00,
                    'notas' => 'Precio especial Siderúrgica Sevillana',
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
