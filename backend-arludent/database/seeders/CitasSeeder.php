<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Cita;
use App\Models\Paciente;
use App\Models\Medico;
use App\Models\User;
use Carbon\Carbon;

/**
 * Seeder de Citas - DEMOSTRACIÓN
 *
 * Crea citas pasadas, de hoy y futuras para la demostración
 */
class CitasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🔧 CREANDO CITAS DEMO (HOY: ' . Carbon::now()->format('d/m/Y') . ')...');

        // Obtener paciente demo y médicos
        $paciente = Paciente::whereHas('usuario', function ($q) {
            $q->where('correo', 'paciente@arludent.com');
        })->first();

        $medico1 = Medico::whereHas('usuario', function ($q) {
            $q->where('correo', 'medico@arludent.com');
        })->first();

        $medico2 = Medico::whereHas('usuario', function ($q) {
            $q->where('correo', 'medico2@arludent.com');
        })->first();

        if (!$paciente || !$medico1 || !$medico2) {
            $this->command->warn('⚠️  Faltan registros. Ejecuta UserSeeder y PacientesSeeder primero.');
            return;
        }

        // Obtener usuario admin para creado_por
        $adminUser = User::where('correo', 'admin@arludent.com')->first();
        $creadoPor = $adminUser ? $adminUser->id_usuario : null;

        // Limpiar citas previas del paciente demo
        Cita::where('id_paciente', $paciente->id_paciente)->delete();

        $now = Carbon::now();

        // ========== CITAS PASADAS ==========
        $citasPasadas = [
            [
                'medico' => $medico1,
                'inicio' => $now->copy()->subMonths(6)->setTime(10, 0, 0),
                'fin' => $now->copy()->subMonths(6)->setTime(10, 30, 0),
                'motivo' => 'Primera consulta - Evaluación general',
                'estado' => 'completado',
                'notas' => 'Paciente acude por primera vez. Se realiza evaluación completa. Se detectan caries y se recomienda limpieza dental.',
            ],
            [
                'medico' => $medico1,
                'inicio' => $now->copy()->subMonths(5)->setTime(11, 0, 0),
                'fin' => $now->copy()->subMonths(5)->setTime(11, 45, 0),
                'motivo' => 'Limpieza dental y destartraje',
                'estado' => 'completado',
                'notas' => 'Limpieza completa realizada. Paciente toleró bien el procedimiento. Se programan restauraciones.',
            ],
            [
                'medico' => $medico1,
                'inicio' => $now->copy()->subMonths(4)->setTime(9, 30, 0),
                'fin' => $now->copy()->subMonths(4)->setTime(10, 30, 0),
                'motivo' => 'Restauración con resina - Pieza 16',
                'estado' => 'completado',
                'notas' => 'Restauración con resina compuesta en molar superior derecho. Sin complicaciones.',
            ],
            [
                'medico' => $medico1,
                'inicio' => $now->copy()->subMonths(3)->setTime(10, 0, 0),
                'fin' => $now->copy()->subMonths(3)->setTime(11, 0, 0),
                'motivo' => 'Restauración con resina - Piezas 14 y 26',
                'estado' => 'completado',
                'notas' => 'Dos restauraciones realizadas exitosamente.',
            ],
            [
                'medico' => $medico2,
                'inicio' => $now->copy()->subMonths(3)->setTime(15, 0, 0),
                'fin' => $now->copy()->subMonths(3)->setTime(16, 0, 0),
                'motivo' => 'Evaluación inicial de ortodoncia',
                'estado' => 'completado',
                'notas' => 'Se evalúa apiñamiento dental. Se recomienda tratamiento de ortodoncia con brackets.',
            ],
            [
                'medico' => $medico2,
                'inicio' => $now->copy()->subMonths(3)->addDays(7)->setTime(16, 0, 0),
                'fin' => $now->copy()->subMonths(3)->addDays(7)->setTime(17, 30, 0),
                'motivo' => 'Colocación de brackets - Arcada superior',
                'estado' => 'completado',
                'notas' => 'Se colocan brackets metálicos en arcada superior. Paciente bien informado sobre cuidados.',
            ],
            [
                'medico' => $medico2,
                'inicio' => $now->copy()->subMonths(3)->addDays(14)->setTime(15, 30, 0),
                'fin' => $now->copy()->subMonths(3)->addDays(14)->setTime(17, 0, 0),
                'motivo' => 'Colocación de brackets - Arcada inferior',
                'estado' => 'completado',
                'notas' => 'Se completa tratamiento de ortodoncia con brackets en arcada inferior.',
            ],
            [
                'medico' => $medico2,
                'inicio' => $now->copy()->subMonths(2)->setTime(16, 0, 0),
                'fin' => $now->copy()->subMonths(2)->setTime(16, 30, 0),
                'motivo' => 'Control mensual de ortodoncia 1',
                'estado' => 'completado',
                'notas' => 'Ajuste de arcos. Se observa movimiento dental inicial según lo planificado.',
            ],
            [
                'medico' => $medico2,
                'inicio' => $now->copy()->subMonths(1)->setTime(17, 0, 0),
                'fin' => $now->copy()->subMonths(1)->setTime(17, 30, 0),
                'motivo' => 'Control mensual de ortodoncia 2',
                'estado' => 'completado',
                'notas' => 'Cambio de ligaduras elásticas. Paciente refiere leve molestia que es normal.',
            ],
            [
                'medico' => $medico2,
                'inicio' => $now->copy()->subDays(5)->setTime(15, 30, 0),
                'fin' => $now->copy()->subDays(5)->setTime(16, 0, 0),
                'motivo' => 'Control mensual de ortodoncia 3',
                'estado' => 'completado',
                'notas' => 'Cambio de arcos a calibre superior. Excelente progreso del tratamiento.',
            ],
        ];

        foreach ($citasPasadas as $cita) {
            Cita::create([
                'id_usuario_externo' => null,
                'id_paciente' => $paciente->id_paciente,
                'id_medico' => $cita['medico']->id_medico,
                'fecha_hora_inicio' => $cita['inicio'],
                'fecha_hora_fin' => $cita['fin'],
                'motivo' => $cita['motivo'],
                'estado' => $cita['estado'],
                'creado_por' => $creadoPor,
                'notas' => $cita['notas'],
            ]);
        }

        $this->command->info('✓ 10 citas pasadas creadas (completadas)');

        // ========== CITAS DE HOY ==========
        $citasHoy = [
            [
                'medico' => $medico1,
                'inicio' => $now->copy()->setTime(9, 0, 0),
                'fin' => $now->copy()->setTime(9, 30, 0),
                'motivo' => 'Control de restauraciones',
                'estado' => 'confirmado',
                'notas' => 'Revisión de las restauraciones realizadas hace 3 meses.',
            ],
            [
                'medico' => $medico2,
                'inicio' => $now->copy()->setTime(16, 0, 0),
                'fin' => $now->copy()->setTime(16, 30, 0),
                'motivo' => 'Control mensual de ortodoncia 4',
                'estado' => 'confirmado',
                'notas' => 'Control de rutina mensual. Ajuste de arcos según progreso.',
            ],
        ];

        foreach ($citasHoy as $cita) {
            Cita::create([
                'id_usuario_externo' => null,
                'id_paciente' => $paciente->id_paciente,
                'id_medico' => $cita['medico']->id_medico,
                'fecha_hora_inicio' => $cita['inicio'],
                'fecha_hora_fin' => $cita['fin'],
                'motivo' => $cita['motivo'],
                'estado' => $cita['estado'],
                'creado_por' => $creadoPor,
                'notas' => $cita['notas'],
            ]);
        }

        $this->command->info('✓ 2 citas para HOY creadas (confirmadas) ⚡');

        // ========== CITAS FUTURAS ==========
        $citasFuturas = [
            [
                'medico' => $medico1,
                'inicio' => $now->copy()->addDays(7)->setTime(10, 30, 0),
                'fin' => $now->copy()->addDays(7)->setTime(11, 30, 0),
                'motivo' => 'Restauración con resina - Piezas 36 y 46',
                'estado' => 'pendiente',
                'notas' => 'Completar restauraciones de las piezas con caries restantes.',
            ],
            [
                'medico' => $medico2,
                'inicio' => $now->copy()->addMonths(1)->setTime(16, 30, 0),
                'fin' => $now->copy()->addMonths(1)->setTime(17, 0, 0),
                'motivo' => 'Control mensual de ortodoncia 5',
                'estado' => 'pendiente',
                'notas' => 'Control mensual de rutina del tratamiento de ortodoncia.',
            ],
            [
                'medico' => $medico2,
                'inicio' => $now->copy()->addMonths(2)->setTime(15, 0, 0),
                'fin' => $now->copy()->addMonths(2)->setTime(15, 30, 0),
                'motivo' => 'Control mensual de ortodoncia 6',
                'estado' => 'pendiente',
                'notas' => 'Continuar con el seguimiento del tratamiento ortodóntico.',
            ],
        ];

        foreach ($citasFuturas as $cita) {
            Cita::create([
                'id_usuario_externo' => null,
                'id_paciente' => $paciente->id_paciente,
                'id_medico' => $cita['medico']->id_medico,
                'fecha_hora_inicio' => $cita['inicio'],
                'fecha_hora_fin' => $cita['fin'],
                'motivo' => $cita['motivo'],
                'estado' => $cita['estado'],
                'creado_por' => $creadoPor,
                'notas' => $cita['notas'],
            ]);
        }

        $this->command->info('✓ 3 citas futuras creadas (pendientes)');

        // ========== RESUMEN ==========
        $this->command->info('');
        $this->command->info('═══════════════════════════════════════════════════════════');
        $this->command->info('✅ TOTAL: 15 CITAS CREADAS PARA DEMO');
        $this->command->info('═══════════════════════════════════════════════════════════');
        $this->command->info('  • 10 Citas pasadas (completadas)');
        $this->command->info('  • 2 Citas para HOY (' . $now->format('d/m/Y') . ') ⚡');
        $this->command->info('  • 3 Citas futuras (pendientes)');
        $this->command->info('');
        $this->command->info('CITAS DE HOY:');
        $this->command->info('  • 09:00 - Dr. Ramírez: Control de restauraciones');
        $this->command->info('  • 16:00 - Dra. Gómez: Control mensual de ortodoncia 4');
        $this->command->info('═══════════════════════════════════════════════════════════');
        $this->command->info('');
    }
}
