<?php

namespace Tests\Feature;

use App\Models\Cita;
use App\Models\LogActividad;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\Rol;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CU-05: Gestionar Citas
 *
 * Pruebas unitarias del módulo de gestión de citas médicas.
 * Verifica confirmar, reprogramar, cancelar, calificar, listar y filtrar citas
 * desde la perspectiva del paciente y del médico.
 */
class CU05_GestionarCitasTest extends TestCase
{
    use RefreshDatabase;

    private User $userPaciente;
    private User $userMedico;
    private Paciente $paciente;
    private Medico $medico;

    protected function setUp(): void
    {
        parent::setUp();

        $rolPaciente = Rol::create(['nombre_rol' => 'paciente', 'descripcion' => 'Paciente']);
        $rolMedico = Rol::create(['nombre_rol' => 'medico', 'descripcion' => 'Médico']);

        $this->userPaciente = User::create([
            'username' => 'paciente_citas',
            'password_hash' => bcrypt('Pass123@'),
            'correo' => 'paciente.citas@arludent.com',
            'telefono' => '987654321',
            'estado' => 'activo',
            'email_verified_at' => now(),
        ]);
        $this->userPaciente->roles()->attach($rolPaciente->id_rol, ['fecha_asignacion' => now()]);

        $this->paciente = Paciente::create([
            'id_usuario' => $this->userPaciente->id_usuario,
            'nombres' => 'Juan',
            'apellidos' => 'Pérez García',
            'dni' => '74125836',
            'fecha_nacimiento' => '1990-05-15',
            'sexo' => 'M',
            'estado' => 'activo',
        ]);

        $this->userMedico = User::create([
            'username' => 'medico_citas',
            'password_hash' => bcrypt('Pass123@'),
            'correo' => 'medico.citas@arludent.com',
            'telefono' => '987654322',
            'estado' => 'activo',
            'email_verified_at' => now(),
        ]);
        $this->userMedico->roles()->attach($rolMedico->id_rol, ['fecha_asignacion' => now()]);

        $this->medico = Medico::create([
            'id_usuario' => $this->userMedico->id_usuario,
            'nombres' => 'Dra. María',
            'apellidos' => 'López Quispe',
            'nro_colegiado' => 'COL-12345',
            'especialidad' => 'Odontología General',
            'tipo_medico' => 'especialista',
        ]);
    }

    private function actingAsPaciente()
    {
        return $this->actingAs($this->userPaciente, 'api');
    }

    private function actingAsMedico()
    {
        return $this->actingAs($this->userMedico, 'api');
    }

    private function crearCita(string $estado = 'pendiente', ?Carbon $fecha = null): Cita
    {
        $fechaInicio = $fecha ?? Carbon::now()->addDays(3)->setHour(10);
        return Cita::create([
            'id_paciente' => $this->paciente->id_paciente,
            'id_medico' => $this->medico->id_medico,
            'fecha_hora_inicio' => $fechaInicio,
            'fecha_hora_fin' => $fechaInicio->copy()->addHour(),
            'motivo' => 'Consulta general de prueba',
            'estado' => $estado,
            'creado_por' => $this->userPaciente->id_usuario,
        ]);
    }

    // =====================================================
    // FLUJO NORMAL: LISTAR CITAS DEL PACIENTE
    // =====================================================

    /** @test */
    public function paciente_puede_listar_sus_citas(): void
    {
        $this->crearCita('pendiente');
        $this->crearCita('confirmado', Carbon::now()->addDays(5)->setHour(14));

        $response = $this->actingAsPaciente()
            ->getJson('/api/clinica/mis-citas');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertEquals(2, $response->json('data.total'));
    }

    /** @test */
    public function paciente_sin_citas_recibe_lista_vacia(): void
    {
        $response = $this->actingAsPaciente()
            ->getJson('/api/clinica/mis-citas');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['total' => 0],
            ]);
    }

    // =====================================================
    // FLUJO NORMAL: CONFIRMAR CITA
    // =====================================================

    /** @test */
    public function paciente_puede_confirmar_cita_pendiente(): void
    {
        $cita = $this->crearCita('pendiente');

        $response = $this->actingAsPaciente()
            ->patchJson('/api/clinica/mis-citas/' . $cita->id_cita . '/confirmar');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Cita confirmada exitosamente.',
            ]);

        $this->assertDatabaseHas('citas', [
            'id_cita' => $cita->id_cita,
            'estado' => 'confirmado',
        ]);
    }

    /** @test */
    public function confirmar_cita_genera_log_auditoria(): void
    {
        $cita = $this->crearCita('pendiente');

        $this->actingAsPaciente()
            ->patchJson('/api/clinica/mis-citas/' . $cita->id_cita . '/confirmar');

        $this->assertDatabaseHas('log_actividad', [
            'accion' => 'confirmar_cita',
            'modulo_afectado' => 'citas',
        ]);
    }

    // =====================================================
    // FLUJO ALTERNO: CONFIRMAR – ESTADOS NO VÁLIDOS
    // =====================================================

    /** @test */
    public function error_al_confirmar_cita_ya_confirmada(): void
    {
        $cita = $this->crearCita('confirmado');

        $response = $this->actingAsPaciente()
            ->patchJson('/api/clinica/mis-citas/' . $cita->id_cita . '/confirmar');

        $response->assertStatus(400);
    }

    /** @test */
    public function error_al_confirmar_cita_completada(): void
    {
        $cita = $this->crearCita('completado');

        $response = $this->actingAsPaciente()
            ->patchJson('/api/clinica/mis-citas/' . $cita->id_cita . '/confirmar');

        $response->assertStatus(400);
    }

    /** @test */
    public function error_al_confirmar_cita_inexistente(): void
    {
        $response = $this->actingAsPaciente()
            ->patchJson('/api/clinica/mis-citas/99999/confirmar');

        $response->assertStatus(404);
    }

    // =====================================================
    // FLUJO NORMAL: CANCELAR CITA
    // =====================================================

    /** @test */
    public function paciente_puede_cancelar_cita_pendiente(): void
    {
        $cita = $this->crearCita('pendiente');

        $response = $this->actingAsPaciente()
            ->patchJson('/api/clinica/mis-citas/' . $cita->id_cita . '/cancelar', [
                'motivo_cancelacion' => 'No podré asistir por motivos laborales',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Cita cancelada exitosamente.',
            ]);

        $this->assertDatabaseHas('citas', [
            'id_cita' => $cita->id_cita,
            'estado' => 'cancelado',
        ]);
    }

    /** @test */
    public function paciente_puede_cancelar_cita_confirmada(): void
    {
        $cita = $this->crearCita('confirmado');

        $response = $this->actingAsPaciente()
            ->patchJson('/api/clinica/mis-citas/' . $cita->id_cita . '/cancelar');

        $response->assertStatus(200);

        $this->assertDatabaseHas('citas', [
            'id_cita' => $cita->id_cita,
            'estado' => 'cancelado',
        ]);
    }

    /** @test */
    public function cancelar_cita_incluye_motivo_en_notas(): void
    {
        $cita = $this->crearCita('pendiente');

        $this->actingAsPaciente()
            ->patchJson('/api/clinica/mis-citas/' . $cita->id_cita . '/cancelar', [
                'motivo_cancelacion' => 'Emergencia familiar',
            ]);

        $citaActualizada = Cita::find($cita->id_cita);
        $this->assertStringContainsString('Cancelada por paciente', $citaActualizada->notas);
    }

    // =====================================================
    // FLUJO ALTERNO: CANCELAR – ESTADOS NO VÁLIDOS
    // =====================================================

    /** @test */
    public function error_al_cancelar_cita_ya_completada(): void
    {
        $cita = $this->crearCita('completado');

        $response = $this->actingAsPaciente()
            ->patchJson('/api/clinica/mis-citas/' . $cita->id_cita . '/cancelar');

        $response->assertStatus(400);
    }

    /** @test */
    public function error_al_cancelar_cita_ya_cancelada(): void
    {
        $cita = $this->crearCita('cancelado');

        $response = $this->actingAsPaciente()
            ->patchJson('/api/clinica/mis-citas/' . $cita->id_cita . '/cancelar');

        $response->assertStatus(400);
    }

    // =====================================================
    // FLUJO NORMAL: REPROGRAMAR CITA
    // =====================================================

    /** @test */
    public function paciente_puede_reprogramar_cita_pendiente(): void
    {
        $cita = $this->crearCita('pendiente');
        $nuevaFecha = Carbon::now()->addDays(7)->setHour(15)->format('Y-m-d H:i:s');

        $response = $this->actingAsPaciente()
            ->patchJson('/api/clinica/mis-citas/' . $cita->id_cita . '/reprogramar', [
                'fecha_hora_inicio' => $nuevaFecha,
                'motivo_reprogramacion' => 'Cambio de horario laboral',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Cita reprogramada exitosamente.',
            ]);
    }

    // =====================================================
    // FLUJO ALTERNO: REPROGRAMAR – VALIDACIONES
    // =====================================================

    /** @test */
    public function error_al_reprogramar_sin_fecha(): void
    {
        $cita = $this->crearCita('pendiente');

        $response = $this->actingAsPaciente()
            ->patchJson('/api/clinica/mis-citas/' . $cita->id_cita . '/reprogramar', []);

        $response->assertStatus(422);
    }

    /** @test */
    public function error_al_reprogramar_con_fecha_pasada(): void
    {
        $cita = $this->crearCita('pendiente');

        $response = $this->actingAsPaciente()
            ->patchJson('/api/clinica/mis-citas/' . $cita->id_cita . '/reprogramar', [
                'fecha_hora_inicio' => Carbon::now()->subDay()->format('Y-m-d H:i:s'),
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function error_al_reprogramar_cita_completada(): void
    {
        $cita = $this->crearCita('completado');
        $nuevaFecha = Carbon::now()->addDays(7)->setHour(15)->format('Y-m-d H:i:s');

        $response = $this->actingAsPaciente()
            ->patchJson('/api/clinica/mis-citas/' . $cita->id_cita . '/reprogramar', [
                'fecha_hora_inicio' => $nuevaFecha,
            ]);

        $response->assertStatus(400);
    }

    // =====================================================
    // FLUJO NORMAL: MÉDICO COMPLETA CITA
    // =====================================================

    /** @test */
    public function medico_puede_completar_cita_confirmada(): void
    {
        $cita = $this->crearCita('confirmado');

        $response = $this->actingAsMedico()
            ->patchJson('/api/clinica/medico/citas/' . $cita->id_cita . '/completar', [
                'notas' => 'Procedimiento realizado sin complicaciones',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Cita marcada como completada exitosamente.',
            ]);

        $this->assertDatabaseHas('citas', [
            'id_cita' => $cita->id_cita,
            'estado' => 'completado',
        ]);
    }

    /** @test */
    public function error_al_completar_cita_pendiente(): void
    {
        $cita = $this->crearCita('pendiente');

        $response = $this->actingAsMedico()
            ->patchJson('/api/clinica/medico/citas/' . $cita->id_cita . '/completar');

        $response->assertStatus(400);
    }

    // =====================================================
    // FLUJO NORMAL: MÉDICO CANCELA CITA
    // =====================================================

    /** @test */
    public function medico_puede_cancelar_cita_pendiente(): void
    {
        $cita = $this->crearCita('pendiente');

        $response = $this->actingAsMedico()
            ->patchJson('/api/clinica/medico/citas/' . $cita->id_cita . '/cancelar', [
                'motivo_cancelacion' => 'Médico no disponible',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('citas', [
            'id_cita' => $cita->id_cita,
            'estado' => 'cancelado',
        ]);
    }

    // =====================================================
    // FLUJO NORMAL: MÉDICO AGREGA NOTAS
    // =====================================================

    /** @test */
    public function medico_puede_agregar_notas_a_cita_confirmada(): void
    {
        $cita = $this->crearCita('confirmado');

        $response = $this->actingAsMedico()
            ->patchJson('/api/clinica/medico/citas/' . $cita->id_cita . '/notas', [
                'notas' => 'Paciente reporta dolor en molar inferior derecho',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Notas agregadas exitosamente.',
            ]);
    }

    /** @test */
    public function error_al_agregar_notas_sin_texto(): void
    {
        $cita = $this->crearCita('confirmado');

        $response = $this->actingAsMedico()
            ->patchJson('/api/clinica/medico/citas/' . $cita->id_cita . '/notas', []);

        $response->assertStatus(422);
    }

    // =====================================================
    // FLUJO NORMAL: CALIFICAR CITA COMPLETADA
    // =====================================================

    /** @test */
    public function paciente_puede_calificar_cita_completada(): void
    {
        $cita = $this->crearCita('completado');

        $response = $this->actingAsPaciente()
            ->postJson('/api/clinica/mis-citas/' . $cita->id_cita . '/calificar', [
                'puntuacion' => 5,
                'comentario' => 'Excelente atención, muy profesional',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Calificación registrada exitosamente.',
            ]);

        $this->assertDatabaseHas('calificaciones', [
            'id_cita' => $cita->id_cita,
            'puntuacion' => 5,
        ]);
    }

    /** @test */
    public function error_al_calificar_cita_no_completada(): void
    {
        $cita = $this->crearCita('confirmado');

        $response = $this->actingAsPaciente()
            ->postJson('/api/clinica/mis-citas/' . $cita->id_cita . '/calificar', [
                'puntuacion' => 4,
            ]);

        $response->assertStatus(400);
    }

    /** @test */
    public function error_al_calificar_con_puntuacion_fuera_de_rango(): void
    {
        $cita = $this->crearCita('completado');

        $response = $this->actingAsPaciente()
            ->postJson('/api/clinica/mis-citas/' . $cita->id_cita . '/calificar', [
                'puntuacion' => 6,
            ]);

        $response->assertStatus(422);
    }
}
