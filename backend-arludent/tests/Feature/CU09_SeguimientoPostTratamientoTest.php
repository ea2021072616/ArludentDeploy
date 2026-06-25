<?php

namespace Tests\Feature;

use App\Models\Cita;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\Rol;
use App\Models\User;
use App\Models\SeguimientoPostTratamiento;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CU-09: Hacer seguimiento post tratamiento
 *
 * Pruebas unitarias del módulo de seguimiento post tratamiento.
 */
class CU09_SeguimientoPostTratamientoTest extends TestCase
{
    use RefreshDatabase;

    private User $userSecretaria;
    private User $userMedico;
    private Paciente $paciente;
    private Cita $cita;

    protected function setUp(): void
    {
        parent::setUp();

        $rolSecretaria = Rol::create(['nombre_rol' => 'secretaria', 'descripcion' => 'Secretaria']);
        $rolMedico     = Rol::create(['nombre_rol' => 'medico',     'descripcion' => 'Médico']);
        $rolPaciente   = Rol::create(['nombre_rol' => 'paciente',   'descripcion' => 'Paciente']);

        // Secretaria
        $this->userSecretaria = User::create([
            'username'          => 'secretaria_seg',
            'password_hash'     => bcrypt('Pass123@'),
            'correo'            => 'secretaria.seg@arludent.com',
            'estado'            => 'activo',
        ]);
        $this->userSecretaria->roles()->attach($rolSecretaria->id_rol, ['fecha_asignacion' => now()]);

        // Médico
        $this->userMedico = User::create([
            'username'          => 'medico_seg',
            'password_hash'     => bcrypt('Pass123@'),
            'correo'            => 'medico.seg@arludent.com',
            'estado'            => 'activo',
        ]);
        $this->userMedico->roles()->attach($rolMedico->id_rol, ['fecha_asignacion' => now()]);

        $medico = Medico::create([
            'id_usuario'    => $this->userMedico->id_usuario,
            'nombres'       => 'Dr. Seg',
            'apellidos'     => 'Med',
            'nro_colegiado' => 'COL-12345',
            'especialidad'  => 'General',
            'tipo_medico'   => 'especialista',
        ]);

        // Paciente
        $userPaciente = User::create([
            'username'          => 'pac_seg',
            'password_hash'     => bcrypt('Pass123@'),
            'correo'            => 'paciente.seg@arludent.com',
            'estado'            => 'activo',
        ]);
        $userPaciente->roles()->attach($rolPaciente->id_rol, ['fecha_asignacion' => now()]);

        $this->paciente = Paciente::create([
            'id_usuario'      => $userPaciente->id_usuario,
            'nombres'         => 'Juan',
            'apellidos'       => 'Pérez',
            'dni'             => '12345678',
            'correo'          => 'paciente.seg@arludent.com',
            'fecha_nacimiento'=> '1990-01-01',
            'estado'          => 'activo',
        ]);

        // Cita
        $this->cita = Cita::create([
            'id_paciente'      => $this->paciente->id_paciente,
            'id_medico'        => $medico->id_medico,
            'fecha_hora_inicio' => Carbon::now()->subDays(2),
            'fecha_hora_fin'   => Carbon::now()->subDays(2)->addHour(),
            'motivo'           => 'Extracción',
            'estado'           => 'completado',
            'creado_por'       => $this->userSecretaria->id_usuario,
        ]);
    }

    private function actingAsSecretaria()
    {
        return $this->actingAs($this->userSecretaria, 'api');
    }

    // =====================================================
    // FLUJO: CREAR, EDITAR Y ELIMINAR SEGUIMIENTO (SECRETARIA)
    // =====================================================

    /** @test */
    public function secretaria_crea_seguimiento_post_tratamiento_exitosamente(): void
    {
        $response = $this->actingAsSecretaria()->postJson('/api/secretaria/seguimiento', [
            'id_paciente'       => $this->paciente->id_paciente,
            'id_cita'           => $this->cita->id_cita,
            'fecha_seguimiento' => Carbon::tomorrow()->format('Y-m-d H:i:s'),
            'tipo_seguimiento'  => 'postoperatorio',
            'prioridad'         => 'alta'
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure(['message', 'seguimiento' => ['id_seguimiento', 'token_respuesta']]);

        $this->assertDatabaseHas('seguimientos_post_tratamiento', [
            'id_paciente'      => $this->paciente->id_paciente,
            'tipo_seguimiento' => 'postoperatorio',
            'prioridad'        => 'alta',
            'estado'           => 'pendiente',
        ]);
    }

    /** @test */
    public function secretaria_actualiza_estado_y_prioridad_de_seguimiento(): void
    {
        $seguimiento = SeguimientoPostTratamiento::create([
            'id_paciente'       => $this->paciente->id_paciente,
            'fecha_seguimiento' => Carbon::tomorrow(),
            'tipo_seguimiento'  => 'revision',
            'estado'            => 'pendiente',
            'token_respuesta'   => 'token123'
        ]);

        $response = $this->actingAsSecretaria()->putJson('/api/secretaria/seguimiento/' . $seguimiento->id_seguimiento, [
            'prioridad' => 'urgente',
            'estado'    => 'requiere_revision'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('seguimientos_post_tratamiento', [
            'id_seguimiento' => $seguimiento->id_seguimiento,
            'prioridad'      => 'urgente',
            'estado'         => 'requiere_revision',
        ]);
    }

    /** @test */
    public function secretaria_elimina_registro_de_seguimiento_del_sistema(): void
    {
        $seguimiento = SeguimientoPostTratamiento::create([
            'id_paciente'       => $this->paciente->id_paciente,
            'fecha_seguimiento' => Carbon::tomorrow(),
            'tipo_seguimiento'  => 'revision',
            'estado'            => 'pendiente',
            'token_respuesta'   => 'token123'
        ]);

        $response = $this->actingAsSecretaria()->deleteJson('/api/secretaria/seguimiento/' . $seguimiento->id_seguimiento);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('seguimientos_post_tratamiento', [
            'id_seguimiento' => $seguimiento->id_seguimiento,
        ]);
    }

    // =====================================================
    // FLUJO: REGISTRAR CONTACTO MANUAL
    // =====================================================

    /** @test */
    public function secretaria_registra_contacto_telefonico_exitoso_sin_complicaciones(): void
    {
        $seguimiento = SeguimientoPostTratamiento::create([
            'id_paciente'       => $this->paciente->id_paciente,
            'fecha_seguimiento' => Carbon::today(),
            'tipo_seguimiento'  => 'postoperatorio',
            'estado'            => 'pendiente',
            'token_respuesta'   => 'token123'
        ]);

        $response = $this->actingAsSecretaria()->postJson('/api/secretaria/seguimiento/' . $seguimiento->id_seguimiento . '/registrar-contacto', [
            'respuesta_paciente' => 'El paciente indica sentirse bien.',
            'tiene_problema'     => false
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('seguimientos_post_tratamiento', [
            'id_seguimiento' => $seguimiento->id_seguimiento,
            'estado'         => 'realizado',
            'tiene_problema' => false,
        ]);
    }

    /** @test */
    public function secretaria_registra_complicacion_medica_elevando_prioridad_a_urgente(): void
    {
        $seguimiento = SeguimientoPostTratamiento::create([
            'id_paciente'       => $this->paciente->id_paciente,
            'fecha_seguimiento' => Carbon::today(),
            'tipo_seguimiento'  => 'postoperatorio',
            'estado'            => 'pendiente',
            'token_respuesta'   => 'token123'
        ]);

        $response = $this->actingAsSecretaria()->postJson('/api/secretaria/seguimiento/' . $seguimiento->id_seguimiento . '/registrar-contacto', [
            'respuesta_paciente'    => 'El paciente indica dolor fuerte.',
            'tiene_problema'        => true,
            'descripcion_problema'  => 'Dolor no cesa con analgésicos',
            'requiere_cita_urgente' => true
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('seguimientos_post_tratamiento', [
            'id_seguimiento'        => $seguimiento->id_seguimiento,
            'estado'                => 'requiere_revision',
            'prioridad'             => 'alta',
            'tiene_problema'        => true,
            'requiere_cita_urgente' => true,
        ]);
    }

    // =====================================================
    // FLUJO: RESPUESTA DEL PACIENTE (PÚBLICO)
    // =====================================================

    /** @test */
    public function paciente_accede_a_formulario_web_mediante_token_seguro(): void
    {
        $token = 'random_token_123';
        $seguimiento = SeguimientoPostTratamiento::create([
            'id_paciente'       => $this->paciente->id_paciente,
            'fecha_seguimiento' => Carbon::today(),
            'tipo_seguimiento'  => 'revision',
            'estado'            => 'pendiente',
            'token_respuesta'   => $token
        ]);

        $response = $this->getJson('/seguimiento/' . $token);

        $response->assertStatus(200)
                 ->assertJsonPath('seguimiento.id_seguimiento', $seguimiento->id_seguimiento);
    }

    /** @test */
    public function paciente_envia_respuesta_favorable_cerrando_el_seguimiento(): void
    {
        $token = 'random_token_123';
        $seguimiento = SeguimientoPostTratamiento::create([
            'id_paciente'       => $this->paciente->id_paciente,
            'fecha_seguimiento' => Carbon::today(),
            'tipo_seguimiento'  => 'revision',
            'estado'            => 'pendiente',
            'token_respuesta'   => $token
        ]);

        $response = $this->postJson('/seguimiento/' . $token . '/responder', [
            'estado_paciente' => 'muy_bien',
            'descripcion'     => 'Todo excelente, sin dolor.'
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('requiere_atencion', false);

        $this->assertDatabaseHas('seguimientos_post_tratamiento', [
            'id_seguimiento' => $seguimiento->id_seguimiento,
            'estado'         => 'respondido',
            'tiene_problema' => false,
        ]);
    }

    /** @test */
    public function paciente_reporta_dolor_y_sistema_genera_alerta_para_cita_urgente(): void
    {
        $token = 'random_token_123';
        $seguimiento = SeguimientoPostTratamiento::create([
            'id_paciente'       => $this->paciente->id_paciente,
            'fecha_seguimiento' => Carbon::today(),
            'tipo_seguimiento'  => 'revision',
            'estado'            => 'pendiente',
            'token_respuesta'   => $token
        ]);

        $response = $this->postJson('/seguimiento/' . $token . '/responder', [
            'estado_paciente' => 'mal',
            'descripcion'     => 'Me duele mucho al masticar.'
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('requiere_atencion', true);

        $this->assertDatabaseHas('seguimientos_post_tratamiento', [
            'id_seguimiento'        => $seguimiento->id_seguimiento,
            'estado'                => 'respondido',
            'tiene_problema'        => true,
            'prioridad'             => 'urgente',
            'requiere_cita_urgente' => true,
        ]);
    }

    // =====================================================
    // FLUJO: WEBHOOK IA
    // =====================================================

    /** @test */
    public function webhook_externo_de_ia_analiza_respuesta_y_actualiza_severidad_automaticamente(): void
    {
        $seguimiento = SeguimientoPostTratamiento::create([
            'id_paciente'       => $this->paciente->id_paciente,
            'fecha_seguimiento' => Carbon::today(),
            'tipo_seguimiento'  => 'postoperatorio',
            'estado'            => 'pendiente',
            'token_respuesta'   => 'token123'
        ]);

        $response = $this->postJson('/api/seguimiento/webhook-ia', [
            'id_seguimiento' => $seguimiento->id_seguimiento,
            'analisis'       => [
                'requiere_atencion'     => true,
                'urgencia'              => 'alta',
                'requiere_cita_urgente' => true,
                'recomendacion'         => 'El paciente describe síntomas de infección.'
            ]
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('seguimientos_post_tratamiento', [
            'id_seguimiento'        => $seguimiento->id_seguimiento,
            'estado'                => 'requiere_revision',
            'tiene_problema'        => true,
            'prioridad'             => 'alta',
            'requiere_cita_urgente' => true,
        ]);
    }

    // =====================================================
    // FLUJO ALTERNO: VALIDACIONES Y ERRORES
    // =====================================================

    /** @test */
    public function error_de_validacion_al_intentar_crear_seguimiento_con_campos_vacios(): void
    {
        $response = $this->actingAsSecretaria()->postJson('/api/secretaria/seguimiento', []);
        $this->assertContains($response->status(), [400, 422]);
    }

    /** @test */
    public function error_al_intentar_acceder_a_formulario_de_paciente_con_token_adulterado(): void
    {
        $response = $this->getJson('/seguimiento/token_falso_999');
        $this->assertContains($response->status(), [400, 404]);
    }

    /** @test */
    public function error_de_regla_de_negocio_paciente_intenta_responder_seguimiento_ya_procesado(): void
    {
        $token = 'random_token_123';
        $seguimiento = SeguimientoPostTratamiento::create([
            'id_paciente'       => $this->paciente->id_paciente,
            'fecha_seguimiento' => Carbon::today(),
            'tipo_seguimiento'  => 'revision',
            'estado'            => 'respondido',
            'token_respuesta'   => $token
        ]);

        $response = $this->postJson('/seguimiento/' . $token . '/responder', [
            'estado_paciente' => 'muy_bien',
            'descripcion'     => 'Todo excelente'
        ]);

        // Regla de negocio: un token ya usado no puede re-usarse
        $this->assertContains($response->status(), [400, 403, 422]);
    }

    /** @test */
    public function error_de_seguridad_al_intentar_crear_seguimiento_sin_sesion_activa(): void
    {
        $response = $this->postJson('/api/secretaria/seguimiento', [
            'id_paciente'       => $this->paciente->id_paciente,
            'id_cita'           => $this->cita->id_cita,
            'fecha_seguimiento' => Carbon::tomorrow()->format('Y-m-d H:i:s'),
            'tipo_seguimiento'  => 'postoperatorio',
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function error_en_webhook_de_ia_por_payload_incompleto_o_malformado(): void
    {
        $response = $this->postJson('/api/seguimiento/webhook-ia', [
            'id_seguimiento' => 999,
        ]);
        $this->assertContains($response->status(), [400, 422, 404]);
    }
}

