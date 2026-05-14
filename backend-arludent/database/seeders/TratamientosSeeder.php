<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tratamiento;

/**
 * Seeder de Tratamientos
 *
 * Crea el catálogo de tratamientos odontológicos disponibles
 */
class TratamientosSeeder extends Seeder
{
    /**
     * Ejecuta el seeder
     */
    public function run(): void
    {
        $this->command->info('Iniciando seed de tratamientos...');

        $tratamientos = [
            // Consultas y diagnósticos
            [
                'categoria' => 'Consulta',
                'nombre' => 'Consulta inicial',
                'descripcion' => 'Primera consulta con evaluación completa del paciente',
                'precio_actual' => 120.00,
                'estado' => 'activo',
            ],
            [
                'categoria' => 'Consulta',
                'nombre' => 'Consulta de seguimiento',
                'descripcion' => 'Consulta de control post-tratamiento',
                'precio_actual' => 80.00,
                'estado' => 'activo',
            ],
            [
                'categoria' => 'Consulta',
                'nombre' => 'Consulta de urgencia',
                'descripcion' => 'Atención inmediata para dolor o emergencia dental',
                'precio_actual' => 150.00,
                'estado' => 'activo',
            ],

            // Higiene dental
            [
                'categoria' => 'Higiene',
                'nombre' => 'Limpieza dental',
                'descripcion' => 'Profilaxis dental completa con pulido',
                'precio_actual' => 80.00,
                'estado' => 'activo',
            ],
            [
                'categoria' => 'Higiene',
                'nombre' => 'Destartraje',
                'descripcion' => 'Eliminación de sarro y cálculo dental',
                'precio_actual' => 100.00,
                'estado' => 'activo',
            ],
            [
                'categoria' => 'Higiene',
                'nombre' => 'Blanqueamiento dental',
                'descripcion' => 'Tratamiento para aclarar el color de los dientes',
                'precio_actual' => 250.00,
                'estado' => 'activo',
            ],

            // Restaurativa
            [
                'categoria' => 'Restaurativa',
                'nombre' => 'Resina compuesta',
                'descripcion' => 'Restauración dental con resina',
                'precio_actual' => 150.00,
                'estado' => 'activo',
            ],
            [
                'categoria' => 'Restaurativa',
                'nombre' => 'Corona dental',
                'descripcion' => 'Corona de porcelana o metal-porcelana',
                'precio_actual' => 450.00,
                'estado' => 'activo',
            ],
            [
                'categoria' => 'Restaurativa',
                'nombre' => 'Puente dental',
                'descripcion' => 'Puente fijo para reemplazar dientes ausentes',
                'precio_actual' => 600.00,
                'estado' => 'activo',
            ],

            // Endodoncia
            [
                'categoria' => 'Endodoncia',
                'nombre' => 'Tratamiento de conducto',
                'descripcion' => 'Tratamiento endodóntico completo',
                'precio_actual' => 400.00,
                'estado' => 'activo',
            ],

            // Cirugía
            [
                'categoria' => 'Cirugía',
                'nombre' => 'Extracción simple',
                'descripcion' => 'Extracción de diente permanente',
                'precio_actual' => 80.00,
                'estado' => 'activo',
            ],
            [
                'categoria' => 'Cirugía',
                'nombre' => 'Extracción quirúrgica',
                'descripcion' => 'Extracción de cordal o diente incluido',
                'precio_actual' => 200.00,
                'estado' => 'activo',
            ],
            [
                'categoria' => 'Cirugía',
                'nombre' => 'Implante dental',
                'descripcion' => 'Colocación de implante osteointegrado',
                'precio_actual' => 800.00,
                'estado' => 'activo',
            ],

            // Ortodoncia
            [
                'categoria' => 'Ortodoncia',
                'nombre' => 'Ortodoncia metálica',
                'descripcion' => 'Tratamiento completo con brackets metálicos',
                'precio_actual' => 2500.00,
                'estado' => 'activo',
            ],
            [
                'categoria' => 'Ortodoncia',
                'nombre' => 'Ortodoncia estética',
                'descripcion' => 'Tratamiento con brackets de zafiro o cerámica',
                'precio_actual' => 3500.00,
                'estado' => 'activo',
            ],
            [
                'categoria' => 'Ortodoncia',
                'nombre' => 'Alineadores invisibles',
                'descripcion' => 'Sistema de alineadores transparentes',
                'precio_actual' => 3000.00,
                'estado' => 'activo',
            ],

            // Periodoncia
            [
                'categoria' => 'Periodoncia',
                'nombre' => 'Tratamiento de gingivitis',
                'descripcion' => 'Tratamiento de inflamación gingival',
                'precio_actual' => 120.00,
                'estado' => 'activo',
            ],
            [
                'categoria' => 'Periodoncia',
                'nombre' => 'Raspado y alisado radicular',
                'descripcion' => 'Tratamiento periodontal profundo',
                'precio_actual' => 300.00,
                'estado' => 'activo',
            ],
        ];

        foreach ($tratamientos as $tratamiento) {
            Tratamiento::firstOrCreate(
                ['nombre' => $tratamiento['nombre']],
                $tratamiento
            );
        }

        $this->command->info('✓ Catálogo de tratamientos creado exitosamente');
    }
}