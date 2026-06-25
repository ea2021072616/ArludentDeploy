<?php

namespace Tests\Feature;

use App\Models\Cita;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\Rol;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CU-04: Visualizar Agenda
 *
 * Pruebas unitarias del módulo de visualización de agenda.
 * Verifica que la secretaria puede ver TODAS las citas de todos los médicos,
 * que el médico solo ve sus propias citas, y que ambos tienen acceso
 * a la vista de calendario y a filtros por estado y fechas.
 */
class CU04_VisualizarAgendaTest extends TestCase
{
    use RefreshDatabase;

    private User $userSecretaria;
    private User $userMedico;
    private User $userMedico2;
    private Medico $medico;
    private Medico $medico2;
    private Paciente $paciente;

    protected function setUp(): void
    {
        parent::setUp();

        $rolSecretaria = Rol::create(['nombre_rol' => 'secretaria', 'descripcion' => 'Secretaria']);
        $rolMedico     = Rol::create(['nombre_rol' => 'medico',     'descripcion' => 'Médico']);
        $rolPaciente   = Rol::create(['nombre_rol' => 'paciente',   'descripcion' => 'Paciente']);
        Rol::create(['nombre_rol' => 'admin',   'descripcion' => 'Administrador']);
        Rol::create(['nombre_rol' => 'externo', 'descripcion' => 'Externo']);

        // Secretaria
        $this->userSecretaria = User::create([
            'username'          => 'secretaria_agenda',
            'password_hash'     => bcrypt('Pass123@'),
            'correo'            => 'secretaria.agenda@arludent.com',
            'telefono'          => '987000010',
            'estado'            => 'activo',
            'email_verified_at' => now(),
        ]);
        $this->userSecretaria->roles()->attach($rolSecretaria->id_rol, ['fecha_asignacion' => now()]);

        // Médico 1
        $this->userMedico = User::create([
            'username'          => 'medico_agenda1',
            'password_hash'     => bcrypt('Pass123@'),
            'correo'            => 'medico.agenda1@arludent.com',
            'telefono'          => '987000011',
            'estado'            => 'activo',
            'email_verified_at' => now(),
        ]);
        $this->userMedico->roles()->attach($rolMedico->id_rol, ['fecha_asignacion' => now()]);

        $this->medico = Medico::create([
            'id_usuario'    => $this->userMedico->id_usuario,
            'nombres'       => 'Dr. Ana',
            'apellidos'     => 'Vargas Ríos',
            'nro_colegiado' => 'COL-55551',
            'especialidad'  => 'Endodoncia',
            'tipo_medico'   => 'especialista',
        ]);

        // Médico 2
        $this->userMedico2 = User::create([
            'username'          => 'medico_agenda2',
            'password_hash'     => bcrypt('Pass123@'),
            'correo'            => 'medico.agenda2@arludent.com',
            'telefono'          => '987000012',
            'estado'            => 'activo',
            'email_verified_at' => now(),
        ]);
        $this->userMedico2->roles()->attach($rolMedico->id_rol, ['fecha_asignacion' => now()]);

        $this->medico2 = Medico::create([
            'id_usuario'    => $this->userMedico2->id_usuario,
            'nombres'       => 'Dr. Carlos',
            'apellidos'     => 'Salas Mendoza',
            'nro_colegiado' => 'COL-55552',
            'especialidad'  => 'Periodoncia',
            'tipo_medico'   => 'cabecera_tarde',
        ]);

        // Paciente
        $userPaciente = User::create([
            'username'          => 'pac_agenda',
            'password_hash'     => bcrypt('Pass123@'),
            'correo'            => 'pac.agenda@arludent.com',
            'estado'            => 'activo',
            'email_verified_at' => now(),
        ]);
        $userPaciente->roles()->attach($rolPaciente->id_rol, ['fecha_asignacion' => now()]);

        $this->paciente = Paciente::create([
            'id_usuario'      => $userPaciente->id_usuario,
            'nombres'         => 'Elena',
            'apellidos'       => 'Paredes Flores',
            'dni'             => '44556677',
            'fecha_nacimiento' => '1992-08-10',
            'sexo'            => 'F',
            'estado'          => 'activo',
        ]);
    }

    private function actingAsSecretaria()
    {
        return $this->actingAs($this->userSecretaria, 'api');
    }

    private function actingAsMedico1()
    {
        return $this->actingAs($this->userMedico, 'api');
    }

    private function actingAsMedico2()
    {
        return $this->actingAs($this->userMedico2, 'api');
    }

    private function crearCita(int $idMedico, string $estado = 'pendiente', ?Carbon $fecha = null): Cita
    {
        $fechaInicio = $fecha ?? Carbon::now()->addDays(2)->setHour(10);
        return Cita::create([
            'id_paciente'      => $this->paciente->id_paciente,
            'id_medico'        => $idMedico,
            'fecha_hora_inicio' => $fechaInicio,
            'fecha_hora_fin'   => $fechaInicio->copy()->addHour(),
            'motivo'           => 'Consulta de prueba para agenda',
            'estado'           => $estado,
            'creado_por'       => $this->userSecretaria->id_usuario,
        ]);
    }

    // =====================================================
    // FLUJO NORMAL: SECRETARIA — VER TODAS LAS CITAS
    // =====================================================

    /** @test */
    public function secretaria_lista_todas_las_citas_agendadas_en_la_clinica(): void
    {
        $this->crearCita($this->medico->id_medico, 'pendiente');
        $this->crearCita($this->medico2->id_medico, 'confirmado');

        $response = $this->actingAsSecretaria()
            ->getJson('/api/secretaria/citas');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $citas = $response->json('data.data');
        $this->assertGreaterThanOrEqual(2, count($citas));
    }

    /** @test */
    public function secretaria_visualiza_citas_multiples_de_diferentes_medicos(): void
    {
        $citaMedico1 = $this->crearCita($this->medico->id_medico, 'pendiente', Carbon::now()->addDays(3)->setHour(10));
        $citaMedico2 = $this->crearCita($this->medico2->id_medico, 'confirmado', Carbon::now()->addDays(4)->setHour(14));

        $response = $this->actingAsSecretaria()
            ->getJson('/api/secretaria/citas');

        $response->assertStatus(200);

        $data    = $response->json('data.data');
        $ids     = collect($data)->pluck('id_cita')->toArray();

        $this->assertContains($citaMedico1->id_cita, $ids);
        $this->assertContains($citaMedico2->id_cita, $ids);
    }

    /** @test */
    public function secretaria_obtiene_datos_formateados_para_la_vista_de_calendario(): void
    {
        $this->crearCita($this->medico->id_medico, 'confirmado');

        $response = $this->actingAsSecretaria()
            ->getJson('/api/secretaria/citas/calendario');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $eventos = $response->json('data.eventos');
        $this->assertIsArray($eventos);
    }

    /** @test */
    public function secretaria_consulta_informacion_detallada_de_una_cita_especifica(): void
    {
        $cita = $this->crearCita($this->medico->id_medico, 'pendiente');

        $response = $this->actingAsSecretaria()
            ->getJson('/api/secretaria/citas/' . $cita->id_cita);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function secretaria_filtra_citas_del_calendario_por_medico_especifico(): void
    {
        $this->crearCita($this->medico->id_medico, 'pendiente');
        $this->crearCita($this->medico2->id_medico, 'confirmado');

        $response = $this->actingAsSecretaria()
            ->getJson('/api/secretaria/citas/calendario?medico_id=' . $this->medico->id_medico);

        $response->assertStatus(200);

        $eventos = $response->json('data.eventos');
        // Todos los eventos deben ser del médico filtrado
        foreach ($eventos as $evento) {
            $idCita = $evento['id'] ?? $evento['extendedProps']['id_cita'] ?? null;
            if ($idCita) {
                $cita = Cita::find($idCita);
                if ($cita) {
                    $this->assertEquals($this->medico->id_medico, $cita->id_medico);
                }
            }
        }
    }

    // =====================================================
    // FLUJO NORMAL: MÉDICO — VER SUS CITAS
    // =====================================================

    /** @test */
    public function medico_lista_exclusivamente_sus_citas_asignadas(): void
    {
        $this->crearCita($this->medico->id_medico, 'pendiente');
        $this->crearCita($this->medico->id_medico, 'confirmado', Carbon::now()->addDays(5)->setHour(14));

        $response = $this->actingAsMedico1()
            ->getJson('/api/clinica/medico/citas');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertEquals(2, $response->json('data.total'));
    }

    /** @test */
    public function medico_no_tiene_visibilidad_de_citas_de_otros_profesionales(): void
    {
        // Cita del médico 1
        $this->crearCita($this->medico->id_medico, 'pendiente');

        // Cita del médico 2 (no debe aparecer para médico 1)
        $this->crearCita($this->medico2->id_medico, 'confirmado');

        $response = $this->actingAsMedico1()
            ->getJson('/api/clinica/medico/citas');

        $response->assertStatus(200);

        $total = $response->json('data.total');
        $this->assertEquals(1, $total);
    }

    /** @test */
    public function medico_sin_citas_programadas_recibe_respuesta_exitosa_vacia(): void
    {
        $response = $this->actingAsMedico1()
            ->getJson('/api/clinica/medico/citas');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data'    => ['total' => 0],
            ]);
    }

    /** @test */
    public function medico_obtiene_sus_citas_formateadas_para_vista_calendario(): void
    {
        $this->crearCita($this->medico->id_medico, 'confirmado');

        $response = $this->actingAsMedico1()
            ->getJson('/api/clinica/medico/citas/calendario');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $eventos = $response->json('data.eventos');
        $this->assertIsArray($eventos);
    }

    /** @test */
    public function medico_consulta_el_detalle_completo_de_su_cita_asignada(): void
    {
        $cita = $this->crearCita($this->medico->id_medico, 'confirmado');

        $response = $this->actingAsMedico1()
            ->getJson('/api/clinica/medico/citas/' . $cita->id_cita);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function medico_consulta_estadisticas_generales_de_su_agenda_de_citas(): void
    {
        $this->crearCita($this->medico->id_medico, 'pendiente');
        $this->crearCita($this->medico->id_medico, 'confirmado', Carbon::now()->addDays(3)->setHour(11));
        $this->crearCita($this->medico->id_medico, 'completado', Carbon::now()->subDay()->setHour(10));

        $response = $this->actingAsMedico1()
            ->getJson('/api/clinica/medico/citas/estadisticas/general');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $stats = $response->json('data.estadisticas');
        $this->assertGreaterThanOrEqual(3, $stats['total']);
        $this->assertArrayHasKey('pendientes', $stats);
        $this->assertArrayHasKey('confirmadas', $stats);
        $this->assertArrayHasKey('completadas', $stats);
    }

    // =====================================================
    // FLUJO NORMAL: SECRETARIA — CREAR CITA PARA AGENDA
    // =====================================================

    /** @test */
    public function secretaria_crea_nueva_cita_medica_exitosamente(): void
    {
        $fechaFutura = Carbon::now()->addDays(10)->setHour(9)->setMinute(0)->setSecond(0);

        $response = $this->actingAsSecretaria()
            ->postJson('/api/secretaria/citas', [
                'id_paciente'      => $this->paciente->id_paciente,
                'id_medico'        => $this->medico->id_medico,
                'fecha_hora_inicio' => $fechaFutura->format('Y-m-d H:i:s'),
                'motivo'           => 'Revisión programada',
                'duracion'         => 30,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Cita creada exitosamente.',
            ]);

        $this->assertDatabaseHas('citas', [
            'id_paciente' => $this->paciente->id_paciente,
            'id_medico'   => $this->medico->id_medico,
            'estado'      => 'confirmado',
        ]);
    }

    /** @test */
    public function secretaria_actualiza_estado_de_cita_pendiente_a_confirmada(): void
    {
        $cita = $this->crearCita($this->medico->id_medico, 'pendiente');

        $response = $this->actingAsSecretaria()
            ->patchJson('/api/secretaria/citas/' . $cita->id_cita . '/confirmar');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('citas', [
            'id_cita' => $cita->id_cita,
            'estado'  => 'confirmado',
        ]);
    }

    /** @test */
    public function secretaria_cancela_cita_medica_registrando_motivo_en_notas(): void
    {
        $cita = $this->crearCita($this->medico->id_medico, 'pendiente');

        $response = $this->actingAsSecretaria()
            ->patchJson('/api/secretaria/citas/' . $cita->id_cita . '/cancelar', [
                'notas' => 'Paciente solicitó cancelación',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('citas', [
            'id_cita' => $cita->id_cita,
            'estado'  => 'cancelado',
        ]);
    }

    // =====================================================
    // FLUJO ALTERNO: AUTORIZACIÓN
    // =====================================================

    /** @test */
    public function error_de_autenticacion_al_acceder_a_la_agenda_sin_sesion(): void
    {
        $response = $this->getJson('/api/secretaria/citas');

        $response->assertStatus(401);
    }

    /** @test */
    public function error_de_autorizacion_medico_no_puede_acceder_a_ruta_de_secretaria(): void
    {
        $response = $this->actingAsMedico1()
            ->getJson('/api/secretaria/citas');

        $response->assertStatus(403);
    }

    /** @test */
    public function error_al_consultar_cita_con_identificador_inexistente(): void
    {
        $response = $this->actingAsSecretaria()
            ->getJson('/api/secretaria/citas/999999');

        $response->assertStatus(404);
    }

    /** @test */
    public function error_de_autorizacion_medico_consulta_detalle_de_cita_ajena(): void
    {
        $citaMedico2 = $this->crearCita($this->medico2->id_medico, 'pendiente');

        $response = $this->actingAsMedico1()
            ->getJson('/api/clinica/medico/citas/' . $citaMedico2->id_cita);

        $response->assertStatus(404);
    }

    /** @test */
    public function error_al_intentar_crear_cita_con_fecha_en_el_pasado(): void
    {
        $fechaPasada = Carbon::now()->subDay()->format('Y-m-d H:i:s');

        $response = $this->actingAsSecretaria()
            ->postJson('/api/secretaria/citas', [
                'id_paciente'      => $this->paciente->id_paciente,
                'id_medico'        => $this->medico->id_medico,
                'fecha_hora_inicio' => $fechaPasada,
            ]);

        $response->assertStatus(400);
    }

    /** @test */
    public function secretaria_actualiza_estado_de_cita_a_en_espera_en_clinica(): void
    {
        $cita = $this->crearCita($this->medico->id_medico, 'pendiente');

        $response = $this->actingAsSecretaria()
            ->patchJson('/api/secretaria/citas/' . $cita->id_cita . '/estado', [
                'estado' => 'en_espera',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('citas', [
            'id_cita' => $cita->id_cita,
            'estado'  => 'en_espera',
        ]);
    }
}
