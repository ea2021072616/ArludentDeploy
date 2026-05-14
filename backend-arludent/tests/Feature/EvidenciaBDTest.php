<?php

namespace Tests\Feature;

use App\Models\Cita;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\Rol;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * TEST ESPECIAL DE EVIDENCIA — SIN RefreshDatabase
 *
 * Este test NO usa RefreshDatabase, así los cambios
 * QUEDAN PERSISTENTES en la BD `consultorio_testing`.
 *
 * Después de ejecutar, el profesor puede abrir HeidiSQL,
 * conectarse a `consultorio_testing` y verificar los datos.
 *
 * Uso: php artisan test --filter=EvidenciaBDTest
 */
class EvidenciaBDTest extends TestCase
{
    // ⚠️ NO usa RefreshDatabase — los datos QUEDAN en la BD

    /** @test */
    public function evidencia_01_migrar_y_crear_datos_en_bd_testing(): void
    {
        // Mostrar qué BD estamos usando
        $dbName = DB::connection()->getDatabaseName();
        fwrite(STDERR, "\n");
        fwrite(STDERR, "╔════════════════════════════════════════════════════╗\n");
        fwrite(STDERR, "║  BD CONECTADA: {$dbName}        \n");
        fwrite(STDERR, "╚════════════════════════════════════════════════════╝\n");

        $this->assertEquals('consultorio_testing', $dbName,
            'Este test DEBE ejecutarse contra consultorio_testing');

        // Ejecutar migraciones para crear las tablas
        $this->artisan('migrate', ['--force' => true]);

        // Crear rol
        $rol = Rol::firstOrCreate(
            ['nombre_rol' => 'paciente'],
            ['descripcion' => 'Paciente del sistema']
        );
        $rolMedico = Rol::firstOrCreate(
            ['nombre_rol' => 'medico'],
            ['descripcion' => 'Médico del sistema']
        );

        // Crear usuario paciente
        $userPaciente = User::create([
            'username' => 'evidencia_paciente',
            'password_hash' => bcrypt('Test123!'),
            'correo' => 'evidencia.paciente@arludent.com',
            'telefono' => '999111222',
            'estado' => 'activo',
            'email_verified_at' => now(),
        ]);
        $userPaciente->roles()->attach($rol->id_rol, ['fecha_asignacion' => now()]);

        $paciente = Paciente::create([
            'id_usuario' => $userPaciente->id_usuario,
            'nombres' => 'EVIDENCIA_TEST',
            'apellidos' => 'PHPUNIT_BD_REAL',
            'dni' => '99999901',
            'fecha_nacimiento' => '1995-01-15',
            'sexo' => 'M',
            'estado' => 'activo',
        ]);

        // Crear usuario médico
        $userMedico = User::create([
            'username' => 'evidencia_medico',
            'password_hash' => bcrypt('Test123!'),
            'correo' => 'evidencia.medico@arludent.com',
            'telefono' => '999333444',
            'estado' => 'activo',
            'email_verified_at' => now(),
        ]);
        $userMedico->roles()->attach($rolMedico->id_rol, ['fecha_asignacion' => now()]);

        $medico = Medico::create([
            'id_usuario' => $userMedico->id_usuario,
            'nombres' => 'Dr. EVIDENCIA',
            'apellidos' => 'TEST_PHPUNIT',
            'nro_colegiado' => 'COL-EVID-001',
            'especialidad' => 'Odontología General',
            'tipo_medico' => 'cabecera_manana',
        ]);

        // ══════════════════════════════════════════════
        // Verificar INSERTs con assertDatabaseHas
        // ══════════════════════════════════════════════
        $this->assertDatabaseHas('pacientes', [
            'nombres' => 'EVIDENCIA_TEST',
            'apellidos' => 'PHPUNIT_BD_REAL',
            'dni' => '99999901',
        ]);

        fwrite(STDERR, "✓ INSERT paciente 'EVIDENCIA_TEST' en consultorio_testing\n");

        $this->assertDatabaseHas('medicos', [
            'nombres' => 'Dr. EVIDENCIA',
            'apellidos' => 'TEST_PHPUNIT',
        ]);

        fwrite(STDERR, "✓ INSERT médico 'Dr. EVIDENCIA' en consultorio_testing\n");

        // Crear cita
        $cita = Cita::create([
            'id_paciente' => $paciente->id_paciente,
            'id_medico' => $medico->id_medico,
            'fecha_hora_inicio' => Carbon::now()->addDays(3)->setHour(10),
            'fecha_hora_fin' => Carbon::now()->addDays(3)->setHour(11),
            'motivo' => '[EVIDENCIA] Cita creada por PHPUnit test',
            'estado' => 'pendiente',
            'creado_por' => $userPaciente->id_usuario,
        ]);

        $this->assertDatabaseHas('citas', [
            'id_cita' => $cita->id_cita,
            'estado' => 'pendiente',
        ]);

        fwrite(STDERR, "✓ INSERT cita #{$cita->id_cita} estado='pendiente'\n");

        // ══════════════════════════════════════════════
        // Simular confirmar cita (como lo hace el Controller)
        // ══════════════════════════════════════════════
        $response = $this->actingAs($userPaciente, 'api')
            ->patchJson("/api/clinica/mis-citas/{$cita->id_cita}/confirmar");

        $response->assertStatus(200);

        $this->assertDatabaseHas('citas', [
            'id_cita' => $cita->id_cita,
            'estado' => 'confirmado',
        ]);

        fwrite(STDERR, "✓ UPDATE cita #{$cita->id_cita} estado='confirmado' (via API)\n");

        // ══════════════════════════════════════════════
        // Simular completar cita (como médico)
        // ══════════════════════════════════════════════
        $response = $this->actingAs($userMedico, 'api')
            ->patchJson("/api/clinica/medico/citas/{$cita->id_cita}/completar", [
                'notas' => '[EVIDENCIA] Consulta completada por test PHPUnit',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('citas', [
            'id_cita' => $cita->id_cita,
            'estado' => 'completado',
        ]);

        fwrite(STDERR, "✓ UPDATE cita #{$cita->id_cita} estado='completado' (via API médico)\n");

        // ══════════════════════════════════════════════
        // Mensaje final
        // ══════════════════════════════════════════════
        fwrite(STDERR, "\n");
        fwrite(STDERR, "╔════════════════════════════════════════════════════╗\n");
        fwrite(STDERR, "║  ✅ DATOS PERSISTENTES EN: consultorio_testing    ║\n");
        fwrite(STDERR, "║                                                    ║\n");
        fwrite(STDERR, "║  Abre HeidiSQL → conecta a consultorio_testing    ║\n");
        fwrite(STDERR, "║  y ejecuta:                                        ║\n");
        fwrite(STDERR, "║                                                    ║\n");
        fwrite(STDERR, "║  SELECT * FROM pacientes                           ║\n");
        fwrite(STDERR, "║    WHERE nombres = 'EVIDENCIA_TEST';               ║\n");
        fwrite(STDERR, "║                                                    ║\n");
        fwrite(STDERR, "║  SELECT * FROM citas                               ║\n");
        fwrite(STDERR, "║    WHERE motivo LIKE '%EVIDENCIA%';                ║\n");
        fwrite(STDERR, "╚════════════════════════════════════════════════════╝\n");
    }
}
