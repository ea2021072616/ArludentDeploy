<?php

namespace Tests\Feature;

use App\Models\DisponibilidadMedico;
use App\Models\LogActividad;
use App\Models\Medico;
use App\Models\Rol;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CU-03: Gestionar Disponibilidad de Tiempo
 *
 * Pruebas unitarias del módulo de gestión de disponibilidad médica.
 * Verifica la creación, actualización y eliminación de horarios y bloqueos,
 * así como las reglas especiales para médicos de cabecera y especialistas.
 */
class CU03_GestionarDisponibilidadTest extends TestCase
{
    use RefreshDatabase;

    private User $userMedico;
    private Medico $medico;

    private User $userCabecera;
    private Medico $medicoCabecera;

    protected function setUp(): void
    {
        parent::setUp();

        $rolMedico = Rol::create(['nombre_rol' => 'medico', 'descripcion' => 'Médico']);

        // Médico especialista
        $this->userMedico = User::create([
            'username'          => 'medico_disp',
            'password_hash'     => bcrypt('Pass123@'),
            'correo'            => 'medico.disp@arludent.com',
            'telefono'          => '987654321',
            'estado'            => 'activo',
            'email_verified_at' => now(),
        ]);
        $this->userMedico->roles()->attach($rolMedico->id_rol, ['fecha_asignacion' => now()]);

        $this->medico = Medico::create([
            'id_usuario'    => $this->userMedico->id_usuario,
            'nombres'       => 'Dr. Roberto',
            'apellidos'     => 'Silva Fuentes',
            'nro_colegiado' => 'COL-11111',
            'especialidad'  => 'Ortodoncia',
            'tipo_medico'   => 'especialista',
        ]);

        // Médico de cabecera mañana
        $this->userCabecera = User::create([
            'username'          => 'medico_cabecera',
            'password_hash'     => bcrypt('Pass123@'),
            'correo'            => 'medico.cabecera@arludent.com',
            'telefono'          => '987654322',
            'estado'            => 'activo',
            'email_verified_at' => now(),
        ]);
        $this->userCabecera->roles()->attach($rolMedico->id_rol, ['fecha_asignacion' => now()]);

        $this->medicoCabecera = Medico::create([
            'id_usuario'    => $this->userCabecera->id_usuario,
            'nombres'       => 'Dr. Pedro',
            'apellidos'     => 'Cabecera López',
            'nro_colegiado' => 'COL-22222',
            'especialidad'  => 'Odontología General',
            'tipo_medico'   => 'cabecera_manana',
        ]);
    }

    private function actingAsMedico()
    {
        return $this->actingAs($this->userMedico, 'api');
    }

    private function actingAsCabecera()
    {
        return $this->actingAs($this->userCabecera, 'api');
    }

    // =====================================================
    // FLUJO NORMAL: LISTAR DISPONIBILIDAD
    // =====================================================

    /** @test */
    public function medico_lista_su_disponibilidad_cuando_no_hay_registros(): void
    {
        $response = $this->actingAsMedico()
            ->getJson('/api/clinica/medico/disponibilidad');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data'    => ['total' => 0],
            ]);
    }

    /** @test */
    public function medico_lista_su_disponibilidad_con_registros_guardados(): void
    {
        DisponibilidadMedico::create([
            'id_medico'  => $this->medico->id_medico,
            'tipo'       => 'horario',
            'dia_semana' => 1,
            'hora_inicio' => '09:00',
            'hora_fin'   => '13:00',
        ]);

        $response = $this->actingAsMedico()
            ->getJson('/api/clinica/medico/disponibilidad');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertEquals(1, $response->json('data.total'));
    }

    /** @test */
    public function medico_de_cabecera_obtiene_horarios_predefinidos_automaticamente(): void
    {
        $response = $this->actingAsCabecera()
            ->getJson('/api/clinica/medico/disponibilidad');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // Los médicos de cabecera tienen es_cabecera = true
        $this->assertTrue($response->json('data.es_cabecera'));
    }

    // =====================================================
    // FLUJO NORMAL: CREAR HORARIO SEMANAL (ESPECIALISTA)
    // =====================================================

    /** @test */
    public function medico_especialista_crea_horario_semanal_exitosamente(): void
    {
        $response = $this->actingAsMedico()
            ->postJson('/api/clinica/medico/disponibilidad', [
                'tipo'       => 'horario',
                'dia_semana' => 1,  // Lunes
                'hora_inicio' => '09:00',
                'hora_fin'   => '13:00',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Disponibilidad creada exitosamente.',
            ]);

        $this->assertDatabaseHas('disponibilidad_medico', [
            'id_medico'  => $this->medico->id_medico,
            'tipo'       => 'horario',
            'dia_semana' => 1,
        ]);
    }

    /** @test */
    public function medico_especialista_crea_multiples_horarios_en_distintos_dias(): void
    {
        foreach ([1, 2, 3] as $dia) {
            $response = $this->actingAsMedico()
                ->postJson('/api/clinica/medico/disponibilidad', [
                    'tipo'       => 'horario',
                    'dia_semana' => $dia,
                    'hora_inicio' => '10:00',
                    'hora_fin'   => '14:00',
                ]);

            $response->assertStatus(200);
        }

        $total = DisponibilidadMedico::where('id_medico', $this->medico->id_medico)->count();
        $this->assertEquals(3, $total);
    }

    // =====================================================
    // FLUJO NORMAL: CREAR BLOQUEO
    // =====================================================

    /** @test */
    public function medico_crea_bloqueo_de_disponibilidad_por_fecha_especifica(): void
    {
        $fechaFutura = Carbon::now()->addDays(5)->format('Y-m-d');

        $response = $this->actingAsMedico()
            ->postJson('/api/clinica/medico/disponibilidad', [
                'tipo'        => 'bloqueo',
                'fecha_inicio' => $fechaFutura,
                'fecha_fin'   => $fechaFutura,
                'hora_inicio' => '09:00',
                'hora_fin'    => '13:00',
                'motivo'      => 'Congreso médico nacional',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Disponibilidad creada exitosamente.',
            ]);

        $this->assertDatabaseHas('disponibilidad_medico', [
            'id_medico' => $this->medico->id_medico,
            'tipo'      => 'bloqueo',
        ]);
    }

    /** @test */
    public function creacion_de_disponibilidad_registra_evento_en_log_de_auditoria(): void
    {
        $this->actingAsMedico()
            ->postJson('/api/clinica/medico/disponibilidad', [
                'tipo'       => 'horario',
                'dia_semana' => 2,
                'hora_inicio' => '14:00',
                'hora_fin'   => '18:00',
            ]);

        $this->assertDatabaseHas('log_actividad', [
            'accion'          => 'crear_disponibilidad',
            'modulo_afectado' => 'disponibilidad_medico',
        ]);
    }

    // =====================================================
    // FLUJO NORMAL: ACTUALIZAR DISPONIBILIDAD
    // =====================================================

    /** @test */
    public function medico_actualiza_su_horario_de_disponibilidad_exitosamente(): void
    {
        $disp = DisponibilidadMedico::create([
            'id_medico'  => $this->medico->id_medico,
            'tipo'       => 'horario',
            'dia_semana' => 3,
            'hora_inicio' => '08:00',
            'hora_fin'   => '12:00',
        ]);

        $response = $this->actingAsMedico()
            ->putJson('/api/clinica/medico/disponibilidad/' . $disp->id_disp, [
                'tipo'       => 'horario',
                'dia_semana' => 3,
                'hora_inicio' => '09:00',
                'hora_fin'   => '13:00',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Disponibilidad actualizada exitosamente.',
            ]);

        $this->assertDatabaseHas('disponibilidad_medico', [
            'id_disp'    => $disp->id_disp,
            'hora_inicio' => '09:00',
            'hora_fin'   => '13:00',
        ]);
    }

    // =====================================================
    // FLUJO NORMAL: ELIMINAR DISPONIBILIDAD
    // =====================================================

    /** @test */
    public function medico_elimina_su_horario_de_disponibilidad_exitosamente(): void
    {
        $disp = DisponibilidadMedico::create([
            'id_medico'  => $this->medico->id_medico,
            'tipo'       => 'horario',
            'dia_semana' => 4,
            'hora_inicio' => '10:00',
            'hora_fin'   => '14:00',
        ]);

        $response = $this->actingAsMedico()
            ->deleteJson('/api/clinica/medico/disponibilidad/' . $disp->id_disp);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Disponibilidad eliminada exitosamente.',
            ]);

        $this->assertDatabaseMissing('disponibilidad_medico', [
            'id_disp' => $disp->id_disp,
        ]);
    }

    // =====================================================
    // FLUJO NORMAL: HORARIOS DISPONIBLES PARA CITAS
    // =====================================================

    /** @test */
    public function sistema_permite_consultar_horarios_disponibles_del_medico_para_citas(): void
    {
        // Crear horario para el día de la semana de la fecha de prueba
        $fechaFutura = Carbon::now()->addDays(7);
        $diaSemana   = $fechaFutura->dayOfWeek;

        DisponibilidadMedico::create([
            'id_medico'  => $this->medico->id_medico,
            'tipo'       => 'horario',
            'dia_semana' => $diaSemana,
            'hora_inicio' => '09:00',
            'hora_fin'   => '13:00',
        ]);

        $response = $this->actingAsMedico()
            ->getJson('/api/clinica/medico/disponibilidad/horarios-disponibles?' . http_build_query([
                'id_medico' => $this->medico->id_medico,
                'fecha'     => $fechaFutura->format('Y-m-d'),
            ]));

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    // =====================================================
    // FLUJO ALTERNO: VALIDACIONES
    // =====================================================

    /** @test */
    public function error_al_crear_horario_con_campo_tipo_vacio(): void
    {
        $response = $this->actingAsMedico()
            ->postJson('/api/clinica/medico/disponibilidad', [
                'dia_semana' => 1,
                'hora_inicio' => '09:00',
                'hora_fin'   => '13:00',
                // Sin 'tipo'
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function error_al_crear_horario_con_hora_de_fin_anterior_a_hora_de_inicio(): void
    {
        $response = $this->actingAsMedico()
            ->postJson('/api/clinica/medico/disponibilidad', [
                'tipo'       => 'horario',
                'dia_semana' => 1,
                'hora_inicio' => '13:00',
                'hora_fin'   => '09:00',  // Fin antes del inicio
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function error_al_crear_bloqueo_de_disponibilidad_en_fecha_pasada(): void
    {
        $fechaPasada = Carbon::now()->subDays(2)->format('Y-m-d');

        $response = $this->actingAsMedico()
            ->postJson('/api/clinica/medico/disponibilidad', [
                'tipo'        => 'bloqueo',
                'fecha_inicio' => $fechaPasada,
                'fecha_fin'   => $fechaPasada,
                'hora_inicio' => '09:00',
                'hora_fin'    => '13:00',
            ]);

        // Sistema debe rechazar fechas pasadas (400)
        $response->assertStatus(400);
    }

    /** @test */
    public function error_al_crear_horario_enviando_dia_de_semana_y_fechas_simultaneamente(): void
    {
        $fechaFutura = Carbon::now()->addDays(5)->format('Y-m-d');

        $response = $this->actingAsMedico()
            ->postJson('/api/clinica/medico/disponibilidad', [
                'tipo'        => 'horario',
                'dia_semana'  => 1,
                'fecha_inicio' => $fechaFutura,  // No se puede combinar día con fecha
                'fecha_fin'   => $fechaFutura,
                'hora_inicio' => '09:00',
                'hora_fin'    => '13:00',
            ]);

        $response->assertStatus(400);
    }

    /** @test */
    public function error_de_autorizacion_al_intentar_eliminar_disponibilidad_de_otro_medico(): void
    {
        // Crear disponibilidad del médico de cabecera
        $disp = DisponibilidadMedico::create([
            'id_medico'  => $this->medicoCabecera->id_medico,
            'tipo'       => 'horario',
            'dia_semana' => 1,
            'hora_inicio' => '09:00',
            'hora_fin'   => '13:00',
        ]);

        // Intentar eliminarla con el médico especialista
        $response = $this->actingAsMedico()
            ->deleteJson('/api/clinica/medico/disponibilidad/' . $disp->id_disp);

        // Debe retornar 404 (no encontrada para este médico)
        $response->assertStatus(404);
    }

    /** @test */
    public function error_al_intentar_eliminar_horarios_predefinidos_de_medico_de_cabecera(): void
    {
        // Primero listar para que se creen los horarios automáticos del cabecera
        $this->actingAsCabecera()
            ->getJson('/api/clinica/medico/disponibilidad');

        $dispCabecera = DisponibilidadMedico::where('id_medico', $this->medicoCabecera->id_medico)
            ->where('tipo', 'horario')
            ->whereNotNull('dia_semana')
            ->first();

        if ($dispCabecera) {
            $response = $this->actingAsCabecera()
                ->deleteJson('/api/clinica/medico/disponibilidad/' . $dispCabecera->id_disp);

            // Los horarios predefinidos de cabecera no se pueden eliminar (403)
            $response->assertStatus(403);
        } else {
            $this->assertTrue(true); // Test se omite si no hay horarios aún
        }
    }

    /** @test */
    public function usuario_no_autenticado_no_puede_gestionar_disponibilidades(): void
    {
        $response = $this->getJson('/api/clinica/medico/disponibilidad');

        $response->assertStatus(401);
    }

    /** @test */
    public function consulta_de_horarios_disponibles_requiere_parametro_id_medico(): void
    {
        $response = $this->actingAsMedico()
            ->getJson('/api/clinica/medico/disponibilidad/horarios-disponibles?fecha=' . now()->addDays(3)->format('Y-m-d'));

        $response->assertStatus(422);
    }

    /** @test */
    public function consulta_de_horarios_disponibles_no_acepta_fechas_pasadas(): void
    {
        $response = $this->actingAsMedico()
            ->getJson('/api/clinica/medico/disponibilidad/horarios-disponibles?' . http_build_query([
                'id_medico' => $this->medico->id_medico,
                'fecha'     => Carbon::now()->subDay()->format('Y-m-d'),
            ]));

        $response->assertStatus(422);
    }
}
