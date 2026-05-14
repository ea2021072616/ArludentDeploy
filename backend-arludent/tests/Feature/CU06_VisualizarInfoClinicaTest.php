<?php

namespace Tests\Feature;

use App\Models\HistorialClinico;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CU-06: Visualizar Información Clínica
 *
 * Pruebas unitarias del módulo de historiales clínicos.
 * Verifica creación, consulta, actualización de historiales,
 * y la visualización del historial propio del paciente.
 */
class CU06_VisualizarInfoClinicaTest extends TestCase
{
    use RefreshDatabase;

    private User $userMedico;
    private User $userPaciente;
    private Medico $medico;
    private Paciente $paciente;

    protected function setUp(): void
    {
        parent::setUp();

        $rolMedico = Rol::create(['nombre_rol' => 'medico', 'descripcion' => 'Médico']);
        $rolPaciente = Rol::create(['nombre_rol' => 'paciente', 'descripcion' => 'Paciente']);

        $this->userMedico = User::create([
            'username' => 'medico_historial',
            'password_hash' => bcrypt('Pass123@'),
            'correo' => 'medico.historial@arludent.com',
            'telefono' => '987654321',
            'estado' => 'activo',
            'email_verified_at' => now(),
        ]);
        $this->userMedico->roles()->attach($rolMedico->id_rol, ['fecha_asignacion' => now()]);

        $this->medico = Medico::create([
            'id_usuario' => $this->userMedico->id_usuario,
            'nombres' => 'Dr. Roberto',
            'apellidos' => 'Mendoza Ticona',
            'nro_colegiado' => 'COL-11111',
            'especialidad' => 'Odontología General',
            'tipo_medico' => 'cabecera_manana',
        ]);

        $this->userPaciente = User::create([
            'username' => 'paciente_historial',
            'password_hash' => bcrypt('Pass123@'),
            'correo' => 'paciente.historial@arludent.com',
            'telefono' => '987654322',
            'estado' => 'activo',
            'email_verified_at' => now(),
        ]);
        $this->userPaciente->roles()->attach($rolPaciente->id_rol, ['fecha_asignacion' => now()]);

        $this->paciente = Paciente::create([
            'id_usuario' => $this->userPaciente->id_usuario,
            'nombres' => 'María Elena',
            'apellidos' => 'Quispe Chambi',
            'dni' => '74125836',
            'fecha_nacimiento' => '1993-07-15',
            'sexo' => 'F',
            'estado' => 'activo',
        ]);
    }

    private function actingAsMedico()
    {
        return $this->actingAs($this->userMedico, 'api');
    }

    private function actingAsPaciente()
    {
        return $this->actingAs($this->userPaciente, 'api');
    }

    private function crearHistorial(array $override = []): HistorialClinico
    {
        return HistorialClinico::create(array_merge([
            'id_paciente' => $this->paciente->id_paciente,
            'id_medico_responsable' => $this->medico->id_medico,
            'codigo_historial' => 'HC-' . strtoupper(uniqid()),
            'motivo_consulta' => 'Dolor en molar superior derecho',
            'diagnostico_presuntivo' => 'Posible caries profunda',
            'higiene_bucal' => 'Regular',
            'created_by' => $this->userMedico->id_usuario,
        ], $override));
    }

    // =====================================================
    // FLUJO NORMAL: CREAR HISTORIAL CLÍNICO
    // =====================================================

    /** @test */
    public function medico_puede_crear_historial_clinico(): void
    {
        $datos = [
            'id_paciente' => $this->paciente->id_paciente,
            'motivo_consulta' => 'Dolor al masticar alimentos fríos',
            'diagnostico_presuntivo' => 'Hipersensibilidad dental',
            'higiene_bucal' => 'Bueno',
            'sintoma_principal' => 'Dolor agudo al frío',
            'tiempo_inicio_sintomas' => '2 semanas',
        ];

        $response = $this->actingAsMedico()
            ->postJson('/api/clinica/historiales', $datos);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('historial_clinico', [
            'id_paciente' => $this->paciente->id_paciente,
            'motivo_consulta' => 'Dolor al masticar alimentos fríos',
        ]);
    }

    /** @test */
    public function historial_genera_codigo_unico(): void
    {
        $response = $this->actingAsMedico()
            ->postJson('/api/clinica/historiales', [
                'id_paciente' => $this->paciente->id_paciente,
                'motivo_consulta' => 'Control rutinario',
            ]);

        $response->assertStatus(201);

        $historial = HistorialClinico::where('id_paciente', $this->paciente->id_paciente)->first();
        $this->assertNotNull($historial->codigo_historial);
        $this->assertStringStartsWith('HC-', $historial->codigo_historial);
    }

    /** @test */
    public function historial_registra_campos_de_anamnesis(): void
    {
        $datos = [
            'id_paciente' => $this->paciente->id_paciente,
            'motivo_consulta' => 'Dolor molar',
            'sintoma_principal' => 'Dolor pulsátil',
            'bajo_tratamiento_medico' => true,
            'detalle_tratamiento_actual' => 'Ibuprofeno 400mg cada 8 horas',
            'alergias_paciente' => 'Penicilina',
            'hemorragia_post_tratamiento' => false,
            'problema_anestesia' => false,
        ];

        $response = $this->actingAsMedico()
            ->postJson('/api/clinica/historiales', $datos);

        $response->assertStatus(201);

        $this->assertDatabaseHas('historial_clinico', [
            'sintoma_principal' => 'Dolor pulsátil',
            'alergias_paciente' => 'Penicilina',
        ]);
    }

    // =====================================================
    // FLUJO NORMAL: LISTAR HISTORIALES
    // =====================================================

    /** @test */
    public function medico_puede_listar_historiales(): void
    {
        $this->crearHistorial();

        $response = $this->actingAsMedico()
            ->getJson('/api/clinica/historiales');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    // =====================================================
    // FLUJO NORMAL: VER DETALLE DE HISTORIAL
    // =====================================================

    /** @test */
    public function medico_puede_ver_detalle_historial(): void
    {
        $historial = $this->crearHistorial();

        $response = $this->actingAsMedico()
            ->getJson('/api/clinica/historiales/' . $historial->id_historial);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function error_al_ver_historial_inexistente(): void
    {
        $response = $this->actingAsMedico()
            ->getJson('/api/clinica/historiales/99999');

        $response->assertStatus(404);
    }

    // =====================================================
    // FLUJO NORMAL: ACTUALIZAR HISTORIAL
    // =====================================================

    /** @test */
    public function medico_puede_actualizar_historial(): void
    {
        $historial = $this->crearHistorial();

        $response = $this->actingAsMedico()
            ->putJson('/api/clinica/historiales/' . $historial->id_historial, [
                'diagnostico_principal' => 'Caries profunda en pieza 16',
                'higiene_bucal' => 'Malo',
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('historial_clinico', [
            'id_historial' => $historial->id_historial,
            'diagnostico_principal' => 'Caries profunda en pieza 16',
            'higiene_bucal' => 'Malo',
        ]);
    }

    // =====================================================
    // FLUJO NORMAL: PACIENTE VE SU HISTORIAL
    // =====================================================

    /** @test */
    public function paciente_puede_ver_su_propio_historial(): void
    {
        $this->crearHistorial();

        $response = $this->actingAsPaciente()
            ->getJson('/api/clinica/mi-historial');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function paciente_sin_historial_recibe_respuesta_vacia(): void
    {
        $response = $this->actingAsPaciente()
            ->getJson('/api/clinica/mi-historial');

        $response->assertStatus(200);
    }

    // =====================================================
    // FLUJO ALTERNO: VALIDACIONES
    // =====================================================

    /** @test */
    public function error_al_crear_historial_sin_paciente(): void
    {
        $response = $this->actingAsMedico()
            ->postJson('/api/clinica/historiales', [
                'motivo_consulta' => 'Dolor dental',
            ]);

        // El controller retorna error al no encontrar paciente
        $response->assertJson(['success' => false]);
    }

    /** @test */
    public function error_al_crear_historial_con_paciente_inexistente(): void
    {
        $response = $this->actingAsMedico()
            ->postJson('/api/clinica/historiales', [
                'id_paciente' => 99999,
                'motivo_consulta' => 'Dolor dental',
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function error_al_crear_historial_con_higiene_invalida(): void
    {
        $response = $this->actingAsMedico()
            ->postJson('/api/clinica/historiales', [
                'id_paciente' => $this->paciente->id_paciente,
                'motivo_consulta' => 'Control',
                'higiene_bucal' => 'Excelente',
            ]);

        $response->assertStatus(422);
    }

    // =====================================================
    // FLUJO NORMAL: RESUMEN DEL PACIENTE
    // =====================================================

    /** @test */
    public function medico_puede_ver_resumen_historial_paciente(): void
    {
        $this->crearHistorial();

        $response = $this->actingAsMedico()
            ->getJson('/api/clinica/pacientes/' . $this->paciente->id_paciente . '/historial-resumen');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }
}
