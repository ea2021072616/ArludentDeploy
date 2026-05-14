<?php

namespace Tests\Feature;

use App\Models\Cita;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CU-07: Gestionar Pacientes
 *
 * Pruebas unitarias del módulo de gestión de pacientes.
 * Verifica CRUD, validaciones, autorización por tipo de médico y auditoría.
 */
class CU07_GestionarPacientesTest extends TestCase
{
    use RefreshDatabase;

    private User $userMedico;
    private Medico $medico;

    protected function setUp(): void
    {
        parent::setUp();

        $rolMedico = Rol::create(['nombre_rol' => 'medico', 'descripcion' => 'Médico']);

        $this->userMedico = User::create([
            'username' => 'medico_pacientes',
            'password_hash' => bcrypt('Pass123@'),
            'correo' => 'medico.pacientes@arludent.com',
            'telefono' => '987654321',
            'estado' => 'activo',
            'email_verified_at' => now(),
        ]);
        $this->userMedico->roles()->attach($rolMedico->id_rol, ['fecha_asignacion' => now()]);

        $this->medico = Medico::create([
            'id_usuario' => $this->userMedico->id_usuario,
            'nombres' => 'Dr. Carlos',
            'apellidos' => 'Ramírez Torres',
            'nro_colegiado' => 'COL-67890',
            'especialidad' => 'Odontología General',
            'tipo_medico' => 'cabecera_manana',
        ]);
    }

    private function actingAsMedico()
    {
        return $this->actingAs($this->userMedico, 'api');
    }

    private function crearPaciente(array $override = []): Paciente
    {
        $userPaciente = User::create([
            'username' => 'pac_' . uniqid(),
            'password_hash' => bcrypt('Pass123@'),
            'correo' => uniqid() . '@arludent.com',
            'estado' => 'activo',
            'email_verified_at' => now(),
        ]);

        return Paciente::create(array_merge([
            'id_usuario' => $userPaciente->id_usuario,
            'nombres' => 'Ana María',
            'apellidos' => 'González Flores',
            'dni' => '7412583' . rand(0, 9),
            'fecha_nacimiento' => '1985-03-20',
            'sexo' => 'F',
            'domicilio' => 'Av. Bolognesi 123, Tacna',
            'persona_responsable' => 'Pedro González',
            'telefono_responsable' => '952741852',
            'grupo_sanguineo' => 'O+',
            'alergias' => 'Penicilina',
            'estado' => 'activo',
        ], $override));
    }

    // =====================================================
    // FLUJO NORMAL: LISTAR PACIENTES
    // =====================================================

    /** @test */
    public function medico_puede_listar_pacientes(): void
    {
        $this->crearPaciente();
        $this->crearPaciente(['nombres' => 'Pedro', 'apellidos' => 'Mamani']);

        $response = $this->actingAsMedico()
            ->getJson('/api/clinica/pacientes');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function puede_buscar_pacientes_por_nombre(): void
    {
        $this->crearPaciente(['nombres' => 'Ana María', 'apellidos' => 'González']);
        $this->crearPaciente(['nombres' => 'Pedro', 'apellidos' => 'Mamani']);

        $response = $this->actingAsMedico()
            ->getJson('/api/clinica/pacientes?busqueda=Ana');

        $response->assertStatus(200);
    }

    /** @test */
    public function puede_buscar_pacientes_por_dni(): void
    {
        $this->crearPaciente(['dni' => '74125800']);

        $response = $this->actingAsMedico()
            ->getJson('/api/clinica/pacientes?busqueda=74125800');

        $response->assertStatus(200);
    }

    // =====================================================
    // FLUJO NORMAL: OBTENER DETALLE DEL PACIENTE
    // =====================================================

    /** @test */
    public function medico_puede_ver_detalle_de_paciente(): void
    {
        $paciente = $this->crearPaciente();

        $response = $this->actingAsMedico()
            ->getJson('/api/clinica/pacientes/' . $paciente->id_paciente);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function error_al_ver_paciente_inexistente(): void
    {
        $response = $this->actingAsMedico()
            ->getJson('/api/clinica/pacientes/99999');

        $response->assertStatus(404);
    }

    // =====================================================
    // FLUJO NORMAL: ACTUALIZAR PACIENTE
    // =====================================================

    /** @test */
    public function medico_puede_actualizar_datos_de_paciente(): void
    {
        $paciente = $this->crearPaciente();

        $response = $this->actingAsMedico()
            ->putJson('/api/clinica/pacientes/' . $paciente->id_paciente, [
                'domicilio' => 'Nueva dirección: Av. Grau 789',
                'telefono_responsable' => '999888777',
                'alergias' => 'Penicilina, Aspirina',
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('pacientes', [
            'id_paciente' => $paciente->id_paciente,
            'domicilio' => 'Nueva dirección: Av. Grau 789',
        ]);
    }

    // =====================================================
    // FLUJO NORMAL: RESUMEN DEL HISTORIAL
    // =====================================================

    /** @test */
    public function medico_puede_ver_resumen_historial_de_paciente(): void
    {
        $paciente = $this->crearPaciente();

        $response = $this->actingAsMedico()
            ->getJson('/api/clinica/pacientes/' . $paciente->id_paciente . '/historial-resumen');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    // =====================================================
    // FLUJO NORMAL: VERIFICACIÓN DE DATOS
    // =====================================================

    /** @test */
    public function paciente_creado_tiene_estado_activo(): void
    {
        $paciente = $this->crearPaciente();

        $this->assertDatabaseHas('pacientes', [
            'id_paciente' => $paciente->id_paciente,
            'estado' => 'activo',
        ]);
    }

    /** @test */
    public function paciente_tiene_datos_clinicos_completos(): void
    {
        $paciente = $this->crearPaciente([
            'grupo_sanguineo' => 'AB+',
            'alergias' => 'Látex, Penicilina',
        ]);

        $this->assertDatabaseHas('pacientes', [
            'id_paciente' => $paciente->id_paciente,
            'grupo_sanguineo' => 'AB+',
            'alergias' => 'Látex, Penicilina',
        ]);
    }

    /** @test */
    public function paciente_esta_vinculado_a_usuario(): void
    {
        $paciente = $this->crearPaciente();

        $this->assertNotNull($paciente->id_usuario);
        $this->assertNotNull($paciente->usuario);
    }

    // =====================================================
    // FLUJO ALTERNO: RESTRICCIÓN POR TIPO DE MÉDICO
    // =====================================================

    /** @test */
    public function medico_especialista_solo_ve_pacientes_con_citas(): void
    {
        $userEsp = User::create([
            'username' => 'medico_esp',
            'password_hash' => bcrypt('Pass123@'),
            'correo' => 'especialista@arludent.com',
            'estado' => 'activo',
            'email_verified_at' => now(),
        ]);
        $rolMedico = Rol::where('nombre_rol', 'medico')->first();
        $userEsp->roles()->attach($rolMedico->id_rol, ['fecha_asignacion' => now()]);

        $medicoEsp = Medico::create([
            'id_usuario' => $userEsp->id_usuario,
            'nombres' => 'Dr. Especialista',
            'apellidos' => 'Test',
            'tipo_medico' => 'especialista',
        ]);

        // Paciente sin cita con especialista
        $pacienteSin = $this->crearPaciente(['nombres' => 'SinCita']);

        // Paciente con cita con especialista
        $pacienteCon = $this->crearPaciente(['nombres' => 'ConCita']);
        Cita::create([
            'id_paciente' => $pacienteCon->id_paciente,
            'id_medico' => $medicoEsp->id_medico,
            'fecha_hora_inicio' => now()->addDay(),
            'fecha_hora_fin' => now()->addDay()->addHour(),
            'estado' => 'pendiente',
        ]);

        $response = $this->actingAs($userEsp, 'api')
            ->getJson('/api/clinica/pacientes');

        $response->assertStatus(200);

        $pacientes = $response->json('data.pacientes');
        $nombres = collect($pacientes)->pluck('nombres')->toArray();

        $this->assertContains('ConCita', $nombres);
        $this->assertNotContains('SinCita', $nombres);
    }

    /** @test */
    public function medico_cabecera_puede_ver_todos_los_pacientes(): void
    {
        $this->crearPaciente(['nombres' => 'Paciente1']);
        $this->crearPaciente(['nombres' => 'Paciente2']);

        $response = $this->actingAsMedico()
            ->getJson('/api/clinica/pacientes');

        $response->assertStatus(200);

        $pacientes = $response->json('data.pacientes');
        $this->assertGreaterThanOrEqual(2, count($pacientes));
    }

    // =====================================================
    // FLUJO NORMAL: FILTRAR POR ESTADO
    // =====================================================

    /** @test */
    public function puede_filtrar_pacientes_por_estado_activo(): void
    {
        $this->crearPaciente(['nombres' => 'Activo1', 'estado' => 'activo']);
        $this->crearPaciente(['nombres' => 'Inactivo1', 'estado' => 'inactivo']);

        $response = $this->actingAsMedico()
            ->getJson('/api/clinica/pacientes?estado=activo');

        $response->assertStatus(200);

        $pacientes = $response->json('data.pacientes');
        foreach ($pacientes as $p) {
            $this->assertEquals('activo', $p['estado']);
        }
    }
}
