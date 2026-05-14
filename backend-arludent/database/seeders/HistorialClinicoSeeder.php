<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\HistorialClinico;
use App\Models\Paciente;
use App\Models\Medico;
use App\Models\DetalleHistorial;
use App\Models\TratamientoHistorial;
use App\Models\SeguimientoTratamiento;
use App\Models\Tratamiento;
use App\Models\Odontograma;
use App\Models\Cita;
use Carbon\Carbon;

/**
 * Seeder de Historial Clínico Completo
 *
 * Crea un historial clínico completo para el paciente de demostración
 */
class HistorialClinicoSeeder extends Seeder
{
    /**
     * Ejecuta el seeder
     */
    public function run(): void
    {
        $this->command->info('Iniciando seed de historial clínico completo...');

        // Obtener paciente demo
        $paciente = Paciente::whereHas('usuario', function ($q) {
            $q->where('correo', 'paciente@arludent.com');
        })->first();

        if (!$paciente) {
            $this->command->warn('No se encontró el paciente demo. Ejecuta PacientesSeeder primero.');
            return;
        }

        // Obtener médicos
        $medico1 = Medico::whereHas('usuario', function ($q) {
            $q->where('correo', 'medico@arludent.com');
        })->first();

        $medico2 = Medico::whereHas('usuario', function ($q) {
            $q->where('correo', 'medico2@arludent.com');
        })->first();

        if (!$medico1 || !$medico2) {
            $this->command->warn('No se encontraron los médicos. Ejecuta UserSeeder primero.');
            return;
        }

        // Limpiar datos existentes del paciente
        HistorialClinico::where('id_paciente', $paciente->id_paciente)->delete();

        // Crear historial clínico principal
        $historial = HistorialClinico::create([
            'id_paciente' => $paciente->id_paciente,
            'id_medico_responsable' => $medico1->id_medico,
            'codigo_historial' => 'HC-' . str_pad($paciente->id_paciente, 4, '0', STR_PAD_LEFT) . '-2024',
            'motivo_consulta' => 'Paciente refiere dolor dental y desea mejorar la estética de su sonrisa',
            'diagnostico_presuntivo' => 'Caries dental múltiple, apiñamiento dental leve',
            'diagnostico_principal' => 'Caries dental en piezas 14, 16, 26, 36, 46. Necesidad de ortodoncia correctiva.',
            'higiene_bucal' => 'Regular',

            // Anamnesis completa
            'sintoma_principal' => 'Dolor dental intermitente en molares superiores',
            'tiempo_inicio_sintomas' => '3 meses',
            'tratamiento_previo' => 'Analgésicos ocasionales',
            'enfermedades_actuales' => 'Ninguna',
            'bajo_tratamiento_medico' => false,
            'detalle_tratamiento_actual' => null,
            'alergias_paciente' => 'Ninguna conocida',
            'intervenciones_quirurgicas_previas' => false,
            'detalle_intervenciones' => null,
            'hemorragia_post_tratamiento' => false,
            'problema_anestesia' => false,
            'dificultad_abrir_masticar' => false,
            'sensibilidad_dental' => true,

            'created_by' => $medico1->id_usuario,
        ]);

        $this->command->info('✓ Historial clínico principal creado');

        // Crear detalles del historial (consultas realizadas)
        $this->crearDetallesHistorial($historial, $medico1, $medico2);

        // Crear odontograma
        $this->crearOdontograma($historial);

        // Crear tratamientos del historial
        $this->crearTratamientosHistorial($historial, $medico1, $medico2);

        $this->command->info('✓ Historial clínico completo creado para el paciente demo');
    }

    /**
     * Crea los detalles del historial clínico
     */
    private function crearDetallesHistorial($historial, $medico1, $medico2)
    {
        $now = Carbon::now();

        // Obtener citas del paciente para vincular
        $citas = Cita::where('id_paciente', $historial->id_paciente)
            ->where('estado', 'completado')
            ->orderBy('fecha_hora_inicio', 'asc')
            ->get();

        $detalles = [
            [
                'cita_motivo' => 'Primera consulta',
                'fecha' => $now->copy()->subMonths(6),
                'diagnostico' => 'Paciente presenta caries dental en piezas 14, 16, 26, 36, 46. Higiene bucal regular. Apiñamiento dental leve.',
                'procedimiento' => 'Examen clínico completo, radiografías periapicales, evaluación de oclusión, odontograma inicial.',
                'notas' => 'Paciente acude por primera vez refiriendo dolor en molares superiores. Se explica plan de tratamiento: limpieza + restauraciones + evaluación de ortodoncia.',
                'medico' => $medico1,
            ],
            [
                'cita_motivo' => 'Limpieza dental',
                'fecha' => $now->copy()->subMonths(5),
                'diagnostico' => 'Caries dental confirmada en piezas 14, 16, 26, 36, 46. Higiene bucal mejorable.',
                'procedimiento' => 'Limpieza dental completa, destartraje supragingival y subgingival, pulido, aplicación de flúor.',
                'notas' => 'Procedimiento realizado sin complicaciones. Se instruye en técnica de cepillado. Se programa inicio de restauraciones.',
                'medico' => $medico1,
            ],
            [
                'cita_motivo' => 'Restauración con resina - Pieza 16',
                'fecha' => $now->copy()->subMonths(4),
                'diagnostico' => 'Caries dental oclusal en pieza 16 (molar superior derecho).',
                'procedimiento' => 'Restauración con resina compuesta en pieza 16. Anestesia local infiltrativa, remoción de tejido cariado, restauración con resina A2.',
                'notas' => 'Procedimiento exitoso. Paciente sin molestias. Se dan indicaciones post-operatorias.',
                'medico' => $medico1,
            ],
            [
                'cita_motivo' => 'Restauración con resina - Piezas 14 y 26',
                'fecha' => $now->copy()->subMonths(3),
                'diagnostico' => 'Caries dental en piezas 14 y 26.',
                'procedimiento' => 'Restauraciones con resina compuesta en piezas 14 y 26. Dos restauraciones clase II realizadas.',
                'notas' => 'Ambas restauraciones completadas exitosamente en una sola sesión. Paciente toleró bien el procedimiento.',
                'medico' => $medico1,
            ],
            [
                'cita_motivo' => 'Evaluación inicial de ortodoncia',
                'fecha' => $now->copy()->subMonths(3),
                'diagnostico' => 'Apiñamiento dental leve en sector anteroinferior. Overjet y overbite normales. Clase I molar.',
                'procedimiento' => 'Evaluación ortodóntica completa: toma de impresiones, fotografías intra y extraorales, radiografía panorámica, cefalométrica lateral.',
                'notas' => 'Se explica plan de tratamiento con brackets metálicos, duración aproximada 18 meses. Paciente acepta y firma consentimiento.',
                'medico' => $medico2,
            ],
            [
                'cita_motivo' => 'Colocación de brackets - Arcada superior',
                'fecha' => $now->copy()->subMonths(3)->addDays(7),
                'diagnostico' => 'Inicio de tratamiento ortodóntico.',
                'procedimiento' => 'Cementado de brackets metálicos en arcada superior, colocación de arco inicial NiTi 0.014, ligado con ligaduras elásticas.',
                'notas' => 'Paciente bien informado sobre cuidados de higiene y alimentación. Se programa colocación de arcada inferior.',
                'medico' => $medico2,
            ],
            [
                'cita_motivo' => 'Colocación de brackets - Arcada inferior',
                'fecha' => $now->copy()->subMonths(3)->addDays(14),
                'diagnostico' => 'Continuación de tratamiento ortodóntico.',
                'procedimiento' => 'Cementado de brackets metálicos en arcada inferior, colocación de arco inicial NiTi 0.014, ligado con ligaduras elásticas.',
                'notas' => 'Tratamiento ortodóntico activo iniciado completamente. Se programa control mensual.',
                'medico' => $medico2,
            ],
        ];

        foreach ($detalles as $detalle) {
            // Buscar cita correspondiente
            $cita = $citas->first(function ($c) use ($detalle) {
                return str_contains($c->motivo, explode(' -', $detalle['cita_motivo'])[0]);
            });

            DetalleHistorial::create([
                'id_historial' => $historial->id_historial,
                'id_cita' => $cita ? $cita->id_cita : null,
                'fecha' => $detalle['fecha'],
                'diagnostico' => $detalle['diagnostico'],
                'procedimiento_realizado' => $detalle['procedimiento'],
                'notas_medicas' => $detalle['notas'],
                'realizado_por' => $detalle['medico']->id_medico,
            ]);
        }

        $this->command->info('✓ Detalles del historial creados (7 consultas documentadas)');
    }

    /**
     * Crea el odontograma del paciente
     */
    private function crearOdontograma($historial)
    {
        $odontogramaData = [
            // Arcada superior derecha
            ['pieza' => '18', 'estado' => 'sano', 'comentario' => 'Cordal superior derecho'],
            ['pieza' => '17', 'estado' => 'sano', 'comentario' => 'Molar superior derecho'],
            ['pieza' => '16', 'estado' => 'cariado', 'comentario' => 'Molar superior derecho con caries'],
            ['pieza' => '15', 'estado' => 'sano', 'comentario' => 'Premolar superior derecho'],
            ['pieza' => '14', 'estado' => 'cariado', 'comentario' => 'Premolar superior derecho con caries'],
            ['pieza' => '13', 'estado' => 'sano', 'comentario' => 'Canino superior derecho'],
            ['pieza' => '12', 'estado' => 'sano', 'comentario' => 'Incisivo lateral superior derecho'],
            ['pieza' => '11', 'estado' => 'sano', 'comentario' => 'Incisivo central superior derecho'],

            // Arcada superior izquierda
            ['pieza' => '21', 'estado' => 'sano', 'comentario' => 'Incisivo central superior izquierdo'],
            ['pieza' => '22', 'estado' => 'sano', 'comentario' => 'Incisivo lateral superior izquierdo'],
            ['pieza' => '23', 'estado' => 'sano', 'comentario' => 'Canino superior izquierdo'],
            ['pieza' => '24', 'estado' => 'sano', 'comentario' => 'Premolar superior izquierdo'],
            ['pieza' => '25', 'estado' => 'sano', 'comentario' => 'Premolar superior izquierdo'],
            ['pieza' => '26', 'estado' => 'cariado', 'comentario' => 'Molar superior izquierdo con caries'],
            ['pieza' => '27', 'estado' => 'sano', 'comentario' => 'Molar superior izquierdo'],
            ['pieza' => '28', 'estado' => 'sano', 'comentario' => 'Cordal superior izquierdo'],

            // Arcada inferior derecha
            ['pieza' => '48', 'estado' => 'sano', 'comentario' => 'Cordal inferior derecho'],
            ['pieza' => '47', 'estado' => 'sano', 'comentario' => 'Molar inferior derecho'],
            ['pieza' => '46', 'estado' => 'cariado', 'comentario' => 'Molar inferior derecho con caries'],
            ['pieza' => '45', 'estado' => 'sano', 'comentario' => 'Premolar inferior derecho'],
            ['pieza' => '44', 'estado' => 'sano', 'comentario' => 'Premolar inferior derecho'],
            ['pieza' => '43', 'estado' => 'sano', 'comentario' => 'Canino inferior derecho'],
            ['pieza' => '42', 'estado' => 'sano', 'comentario' => 'Incisivo lateral inferior derecho'],
            ['pieza' => '41', 'estado' => 'sano', 'comentario' => 'Incisivo central inferior derecho'],

            // Arcada inferior izquierda
            ['pieza' => '31', 'estado' => 'sano', 'comentario' => 'Incisivo central inferior izquierdo'],
            ['pieza' => '32', 'estado' => 'sano', 'comentario' => 'Incisivo lateral inferior izquierdo'],
            ['pieza' => '33', 'estado' => 'sano', 'comentario' => 'Canino inferior izquierdo'],
            ['pieza' => '34', 'estado' => 'sano', 'comentario' => 'Premolar inferior izquierdo'],
            ['pieza' => '35', 'estado' => 'sano', 'comentario' => 'Premolar inferior izquierdo'],
            ['pieza' => '36', 'estado' => 'cariado', 'comentario' => 'Molar inferior izquierdo con caries'],
            ['pieza' => '37', 'estado' => 'sano', 'comentario' => 'Molar inferior izquierdo'],
            ['pieza' => '38', 'estado' => 'sano', 'comentario' => 'Cordal inferior izquierdo'],
        ];

        foreach ($odontogramaData as $pieza) {
            Odontograma::create([
                'id_historial' => $historial->id_historial,
                'pieza' => $pieza['pieza'],
                'estado_pieza' => $pieza['estado'],
                'comentario' => $pieza['comentario'],
                'fecha_registro' => Carbon::now()->subDays(10),
            ]);
        }

        $this->command->info('✓ Odontograma creado (32 piezas dentales)');
    }

    /**
     * Crea los tratamientos del historial
     */
    private function crearTratamientosHistorial($historial, $medico1, $medico2)
    {
        $now = Carbon::now();

        // Obtener tratamientos del catálogo
        $limpieza = Tratamiento::where('nombre', 'Limpieza dental')->first();
        $resina = Tratamiento::where('nombre', 'Resina compuesta')->first();
        $tratamientoOrtodoncia = Tratamiento::where('nombre', 'like', '%ortodoncia%')->orWhere('nombre', 'like', '%brackets%')->first();

        // Tratamiento completado: Limpieza dental
        if ($limpieza) {
            TratamientoHistorial::create([
                'id_historial' => $historial->id_historial,
                'id_tratamiento' => $limpieza->id_tratamiento,
                'descripcion' => 'Limpieza dental completa con destartraje supragingival',
                'fecha_inicio' => $now->copy()->subMonths(5),
                'fecha_fin' => $now->copy()->subMonths(5),
                'estado' => 'completado',
                'precio' => 200.00,
                'realizado_por' => $medico1->id_medico,
            ]);
        }

        // Tratamientos completados: Resinas (3 piezas)
        if ($resina) {
            $piezasRestauradas = [
                ['pieza' => '16', 'fecha' => $now->copy()->subMonths(4)],
                ['pieza' => '14', 'fecha' => $now->copy()->subMonths(3)],
                ['pieza' => '26', 'fecha' => $now->copy()->subMonths(3)],
            ];

            foreach ($piezasRestauradas as $data) {
                TratamientoHistorial::create([
                    'id_historial' => $historial->id_historial,
                    'id_tratamiento' => $resina->id_tratamiento,
                    'descripcion' => "Restauración con resina compuesta en pieza dental {$data['pieza']} - Caries dental",
                    'fecha_inicio' => $data['fecha'],
                    'fecha_fin' => $data['fecha'],
                    'estado' => 'completado',
                    'precio' => 150.00,
                    'realizado_por' => $medico1->id_medico,
                ]);
            }
        }

        // Tratamiento EN CURSO: Ortodoncia con brackets
        if ($tratamientoOrtodoncia) {
            $tratamientoBrackets = TratamientoHistorial::create([
                'id_historial' => $historial->id_historial,
                'id_tratamiento' => $tratamientoOrtodoncia->id_tratamiento,
                'descripcion' => 'Tratamiento de ortodoncia con brackets metálicos superiores e inferiores para corrección de apiñamiento dental y mejora de oclusión',
                'fecha_inicio' => $now->copy()->subMonths(3),
                'estado' => 'en_curso',
                'precio' => 3500.00,
                'realizado_por' => $medico2->id_medico,
            ]);

            // Crear seguimientos del tratamiento de brackets (4 controles ya realizados)
            $seguimientos = [
                [
                    'fecha' => $now->copy()->subMonths(2),
                    'descripcion' => 'Control mensual 1: Ajuste inicial de arcos ortodónticos. Paciente tolerando bien el tratamiento. Se explican cuidados y limpieza.',
                    'duracion' => 17,
                ],
                [
                    'fecha' => $now->copy()->subMonths(1),
                    'descripcion' => 'Control mensual 2: Cambio de ligaduras elásticas. Se observa movimiento dental inicial según planificación. Paciente refiere leve molestia, se indica analgésico.',
                    'duracion' => 16,
                ],
                [
                    'fecha' => $now->copy()->subDays(5),
                    'descripcion' => 'Control mensual 3: Cambio de arcos a calibre superior. Excelente progreso del tratamiento. Paciente muy colaborador con higiene.',
                    'duracion' => 15,
                ],
                [
                    'fecha' => $now->copy(),
                    'descripcion' => 'Control mensual 4: Ajuste de arcos y cambio de ligaduras. Se mantiene el progreso esperado. Próximo control en 30 días.',
                    'duracion' => 14,
                ],
            ];

            foreach ($seguimientos as $seg) {
                SeguimientoTratamiento::create([
                    'id_historial' => $historial->id_historial,
                    'id_tratamiento_historial' => $tratamientoBrackets->id,
                    'fecha_registro' => $seg['fecha'],
                    'descripcion' => $seg['descripcion'],
                    'duracion_restante' => $seg['duracion'],
                    'registrado_por' => $medico2->id_medico,
                ]);
            }
        }

        // Tratamientos sugeridos: Resinas pendientes (2 piezas con caries)
        if ($resina) {
            $piezasPendientes = ['36', '46'];
            foreach ($piezasPendientes as $pieza) {
                TratamientoHistorial::create([
                    'id_historial' => $historial->id_historial,
                    'id_tratamiento' => $resina->id_tratamiento,
                    'descripcion' => "Restauración con resina compuesta en pieza dental {$pieza} - Caries dental detectada",
                    'estado' => 'sugerido',
                    'precio' => 150.00,
                    'realizado_por' => $medico1->id_medico,
                ]);
            }
        }

        $this->command->info('✓ Tratamientos del historial creados:');
        $this->command->info('  • 1 Limpieza dental (completado)');
        $this->command->info('  • 3 Resinas (completadas)');
        $this->command->info('  • 1 Ortodoncia (en curso con 4 seguimientos)');
        $this->command->info('  • 2 Resinas (sugeridas)');
    }
}
