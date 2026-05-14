<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Cita;
use App\Models\Calificacion;
use App\Models\Paciente;
use App\Models\Medico;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Seeder especial para tests de Cypress (E2E).
 *
 * Resetea y crea datos frescos en la tabla `citas` para que los tests
 * de Cypress SIEMPRE encuentren citas en los estados necesarios:
 * - pendiente   → para confirmar, cancelar, reprogramar
 * - confirmado  → para completar (médico), reprogramar (paciente)
 * - completado  → para calificar (paciente), agregar notas (médico)
 *
 * Uso: php artisan db:seed --class=CypressTestDataSeeder
 */
class CypressTestDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('═══════════════════════════════════════════════════════════');
        $this->command->info('🧪 CYPRESS TEST DATA SEEDER — Preparando datos de prueba');
        $this->command->info('═══════════════════════════════════════════════════════════');

        // Obtener paciente y médicos demo
        $paciente = Paciente::whereHas('usuario', function ($q) {
            $q->where('correo', 'paciente@arludent.com');
        })->first();

        $medico1 = Medico::whereHas('usuario', function ($q) {
            $q->where('correo', 'medico@arludent.com');
        })->first();

        $medico2 = Medico::whereHas('usuario', function ($q) {
            $q->where('correo', 'medico2@arludent.com');
        })->first();

        if (!$paciente || !$medico1) {
            $this->command->error('❌ Faltan registros base. Ejecuta UserSeeder y PacientesSeeder primero.');
            return;
        }

        // Usar medico2 o medico1 si no existe medico2
        $medicoSecundario = $medico2 ?? $medico1;

        $adminUser = User::where('correo', 'admin@arludent.com')->first();
        $creadoPor = $adminUser ? $adminUser->id_usuario : null;

        // ============================================================
        // PASO 1: Limpiar citas y calificaciones del paciente demo
        // ============================================================
        $idsCitas = Cita::where('id_paciente', $paciente->id_paciente)->pluck('id_cita');
        Calificacion::whereIn('id_cita', $idsCitas)->delete();
        Cita::where('id_paciente', $paciente->id_paciente)->delete();

        $this->command->info('✓ Datos anteriores limpiados');

        $now = Carbon::now();

        // ============================================================
        // PASO 2: Crear citas COMPLETADAS (pasadas) — para calificar
        // ============================================================
        $completadas = [
            [
                'medico' => $medico1,
                'inicio' => $now->copy()->subMonths(3)->setTime(10, 0, 0),
                'motivo' => 'Evaluación general y limpieza dental',
                'notas'  => 'Paciente acude por primera vez. Evaluación completa realizada.',
            ],
            [
                'medico' => $medico1,
                'inicio' => $now->copy()->subMonths(2)->setTime(11, 0, 0),
                'motivo' => 'Restauración con resina - Pieza 16',
                'notas'  => 'Restauración con resina compuesta exitosa.',
            ],
            [
                'medico' => $medicoSecundario,
                'inicio' => $now->copy()->subMonths(1)->setTime(15, 0, 0),
                'motivo' => 'Control mensual de ortodoncia',
                'notas'  => 'Ajuste de arcos. Progreso normal del tratamiento.',
            ],
            // Esta cita completada SIN calificación — para que FP-10 la califique
            [
                'medico' => $medico1,
                'inicio' => $now->copy()->subDays(3)->setTime(9, 30, 0),
                'motivo' => 'Limpieza dental profunda',
                'notas'  => 'Limpieza dental completa y pulido. Paciente satisfecho.',
            ],
        ];

        foreach ($completadas as $data) {
            Cita::create([
                'id_paciente'       => $paciente->id_paciente,
                'id_medico'         => $data['medico']->id_medico,
                'fecha_hora_inicio' => $data['inicio'],
                'fecha_hora_fin'    => $data['inicio']->copy()->addMinutes(30),
                'motivo'            => $data['motivo'],
                'estado'            => 'completado',
                'notas'             => $data['notas'],
                'creado_por'        => $creadoPor,
            ]);
        }

        $this->command->info('✓ 4 citas COMPLETADAS creadas (1 sin calificación para test FP-10)');

        // ============================================================
        // PASO 3: Crear citas CONFIRMADAS — para completar (médico)
        // ============================================================
        $confirmadas = [
            [
                'medico' => $medico1,
                'inicio' => $now->copy()->addDays(1)->setTime(9, 0, 0),
                'motivo' => 'Control de restauraciones previas',
                'notas'  => 'Revisión programada de restauraciones.',
            ],
            [
                'medico' => $medicoSecundario,
                'inicio' => $now->copy()->addDays(2)->setTime(16, 0, 0),
                'motivo' => 'Control mensual de ortodoncia - Ajuste',
                'notas'  => 'Control de rutina mensual.',
            ],
        ];

        foreach ($confirmadas as $data) {
            Cita::create([
                'id_paciente'       => $paciente->id_paciente,
                'id_medico'         => $data['medico']->id_medico,
                'fecha_hora_inicio' => $data['inicio'],
                'fecha_hora_fin'    => $data['inicio']->copy()->addMinutes(30),
                'motivo'            => $data['motivo'],
                'estado'            => 'confirmado',
                'notas'             => $data['notas'],
                'creado_por'        => $creadoPor,
            ]);
        }

        $this->command->info('✓ 2 citas CONFIRMADAS creadas (para completar/notas del médico)');

        // ============================================================
        // PASO 4: Crear citas PENDIENTES — para confirmar, cancelar, reprogramar
        // ============================================================
        $pendientes = [
            [
                'medico' => $medico1,
                'inicio' => $now->copy()->addDays(5)->setTime(10, 30, 0),
                'motivo' => 'Restauración con resina - Piezas 36 y 46',
                'notas'  => null,
            ],
            [
                'medico' => $medicoSecundario,
                'inicio' => $now->copy()->addDays(10)->setTime(16, 30, 0),
                'motivo' => 'Evaluación para brackets - Arcada inferior',
                'notas'  => null,
            ],
            [
                'medico' => $medico1,
                'inicio' => $now->copy()->addDays(15)->setTime(11, 0, 0),
                'motivo' => 'Extracción de tercer molar',
                'notas'  => null,
            ],
        ];

        foreach ($pendientes as $data) {
            Cita::create([
                'id_paciente'       => $paciente->id_paciente,
                'id_medico'         => $data['medico']->id_medico,
                'fecha_hora_inicio' => $data['inicio'],
                'fecha_hora_fin'    => $data['inicio']->copy()->addMinutes(60),
                'motivo'            => $data['motivo'],
                'estado'            => 'pendiente',
                'notas'             => $data['notas'],
                'creado_por'        => $creadoPor,
            ]);
        }

        $this->command->info('✓ 3 citas PENDIENTES creadas (para confirmar/cancelar/reprogramar)');

        // ============================================================
        // PASO 5: Crear 1 cita CANCELADA — para verificar filtros
        // ============================================================
        Cita::create([
            'id_paciente'       => $paciente->id_paciente,
            'id_medico'         => $medico1->id_medico,
            'fecha_hora_inicio' => $now->copy()->subWeeks(2)->setTime(14, 0, 0),
            'fecha_hora_fin'    => $now->copy()->subWeeks(2)->setTime(14, 30, 0),
            'motivo'            => 'Consulta de emergencia',
            'estado'            => 'cancelado',
            'notas'             => '[Cancelada por paciente] No podré asistir por viaje de trabajo.',
            'creado_por'        => $creadoPor,
        ]);

        $this->command->info('✓ 1 cita CANCELADA creada (para verificar filtros)');

        // ============================================================
        // RESUMEN
        // ============================================================
        $this->command->info('');
        $this->command->info('═══════════════════════════════════════════════════════════');
        $this->command->info('✅ DATOS DE PRUEBA LISTOS — RESUMEN:');
        $this->command->info('═══════════════════════════════════════════════════════════');
        $this->command->info('  • 4 Completadas (1 sin calificación → test FP-10)');
        $this->command->info('  • 2 Confirmadas (→ tests FP-8 completar, FP-9 notas)');
        $this->command->info('  • 3 Pendientes  (→ tests FP-3 confirmar, FP-4 cancelar, FP-5 reprogramar)');
        $this->command->info('  • 1 Cancelada   (→ tests FA-5 filtro cancelado)');
        $this->command->info('  TOTAL: 10 citas');
        $this->command->info('═══════════════════════════════════════════════════════════');
        $this->command->info('');
        $this->command->info('📋 Ejecuta en HeidiSQL ANTES de correr Cypress:');
        $this->command->info('   SELECT id_cita, estado, motivo, notas, updated_at');
        $this->command->info('   FROM citas WHERE id_paciente = ' . $paciente->id_paciente);
        $this->command->info('   ORDER BY id_cita DESC;');
        $this->command->info('');
    }
}
