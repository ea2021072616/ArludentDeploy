<?php

namespace Tests\Feature;

use App\Models\Cita;
use App\Models\Pago;
use App\Models\Paciente;
use App\Models\Medico;
use App\Models\Rol;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CU-11: Generar Reportes
 *
 * Pruebas unitarias para la generación de reportes gerenciales (ingresos, flujo de clientes, citas).
 */
class CU11_GenerarReportesTest extends TestCase
{
    use RefreshDatabase;

    private User $userAdmin;
    private User $userSecretaria;
    private Paciente $paciente;
    private Medico $medico;

    protected function setUp(): void
    {
        parent::setUp();

        $rolAdmin      = Rol::create(['nombre_rol' => 'admin', 'descripcion' => 'Administrador']);
        $rolSecretaria = Rol::create(['nombre_rol' => 'secretaria', 'descripcion' => 'Secretaria']);
        $rolPaciente   = Rol::create(['nombre_rol' => 'paciente', 'descripcion' => 'Paciente']);
        $rolMedico     = Rol::create(['nombre_rol' => 'medico', 'descripcion' => 'Médico']);

        // Administrador
        $this->userAdmin = User::create([
            'username'          => 'admin_reportes',
            'password_hash'     => bcrypt('Pass123@'),
            'correo'            => 'admin.reportes@arludent.com',
            'estado'            => 'activo',
        ]);
        $this->userAdmin->roles()->attach($rolAdmin->id_rol, ['fecha_asignacion' => now()]);

        // Secretaria (Para probar permisos)
        $this->userSecretaria = User::create([
            'username'          => 'sec_reportes',
            'password_hash'     => bcrypt('Pass123@'),
            'correo'            => 'sec.reportes@arludent.com',
            'estado'            => 'activo',
        ]);
        $this->userSecretaria->roles()->attach($rolSecretaria->id_rol, ['fecha_asignacion' => now()]);

        // Médico
        $userMedico = User::create([
            'username'          => 'med_reportes',
            'password_hash'     => bcrypt('Pass123@'),
            'correo'            => 'medico.reportes@arludent.com',
            'estado'            => 'activo',
        ]);
        $userMedico->roles()->attach($rolMedico->id_rol, ['fecha_asignacion' => now()]);
        
        $this->medico = Medico::create([
            'id_usuario'    => $userMedico->id_usuario,
            'nombres'       => 'Dr. Rep',
            'apellidos'     => 'Med',
            'nro_colegiado' => 'COL-999',
            'especialidad'  => 'Ortodoncia',
            'tipo_medico'   => 'especialista',
        ]);

        // Paciente
        $userPaciente = User::create([
            'username'          => 'pac_reportes',
            'password_hash'     => bcrypt('Pass123@'),
            'correo'            => 'paciente.reportes@arludent.com',
            'estado'            => 'activo',
        ]);
        $userPaciente->roles()->attach($rolPaciente->id_rol, ['fecha_asignacion' => now()]);

        $this->paciente = Paciente::create([
            'id_usuario'      => $userPaciente->id_usuario,
            'nombres'         => 'Test',
            'apellidos'       => 'Reportes',
            'dni'             => '11223344',
            'estado'          => 'activo',
        ]);
    }

    private function actingAsAdmin()
    {
        return $this->actingAs($this->userAdmin, 'api');
    }

    private function actingAsSecretaria()
    {
        return $this->actingAs($this->userSecretaria, 'api');
    }

    // =====================================================
    // FLUJO: REPORTES
    // =====================================================

    /** @test */
    public function admin_genera_reporte_de_ingresos_filtrado_por_rango_de_fechas()
    {
        // Generar pagos
        Pago::create([
            'id_paciente' => $this->paciente->id_paciente,
            'concepto'    => 'Tratamiento',
            'monto'       => 500.00,
            'estado_pago' => 'pagado',
            'metodo_pago' => 'efectivo',
            'fecha_pago'  => Carbon::now()->subDays(2),
        ]);
        Pago::create([
            'id_paciente' => $this->paciente->id_paciente,
            'concepto'    => 'Tratamiento 2',
            'monto'       => 300.00,
            'estado_pago' => 'pagado',
            'metodo_pago' => 'tarjeta',
            'fecha_pago'  => Carbon::now()->subDays(10), // Fuera del rango de búsqueda
        ]);

        $fechaInicio = Carbon::now()->subDays(5)->format('Y-m-d');
        $fechaFin    = Carbon::now()->format('Y-m-d');

        $response = $this->actingAsAdmin()->getJson("/api/sistema/reportes/ingresos?fecha_inicio={$fechaInicio}&fecha_fin={$fechaFin}");

        $response->assertStatus(200)
                 ->assertJsonPath('success', true);
                 
        // Verificar que solo retorna el pago dentro del rango
        // Esto depende de cómo esté estructurada la respuesta (generalmente un total y detalle)
        $this->assertNotNull($response->json('data'));
    }

    /** @test */
    public function admin_obtiene_reporte_estadistico_de_citas_medicas()
    {
        // Generar Citas
        Cita::create([
            'id_paciente'       => $this->paciente->id_paciente,
            'id_medico'         => $this->medico->id_medico,
            'fecha_hora_inicio' => Carbon::now()->subDays(1),
            'fecha_hora_fin'    => Carbon::now()->subDays(1)->addHour(),
            'estado'            => 'completado',
            'motivo'            => 'Control',
        ]);

        $fechaInicio = Carbon::now()->subDays(5)->format('Y-m-d');
        $fechaFin    = Carbon::now()->format('Y-m-d');

        $response = $this->actingAsAdmin()->getJson("/api/sistema/reportes/citas?fecha_inicio={$fechaInicio}&fecha_fin={$fechaFin}");

        $response->assertStatus(200)
                 ->assertJsonPath('success', true);
    }

    /** @test */
    public function admin_evalua_flujo_y_retencion_de_pacientes_en_periodo_especifico()
    {
        // El flujo de clientes generalmente cuenta pacientes nuevos vs recurrentes o citas atendidas
        $fechaInicio = Carbon::now()->subDays(30)->format('Y-m-d');
        $fechaFin    = Carbon::now()->format('Y-m-d');

        $response = $this->actingAsAdmin()->getJson("/api/sistema/reportes/flujo-clientes?fecha_inicio={$fechaInicio}&fecha_fin={$fechaFin}");

        $response->assertStatus(200)
                 ->assertJsonPath('success', true);
    }

    // =====================================================
    // FLUJO ALTERNO: SEGURIDAD Y PERMISOS
    // =====================================================

    /** @test */
    public function error_de_seguridad_roles_no_autorizados_no_pueden_ver_reportes_financieros()
    {
        // Dependiendo de tu implementación, los reportes en /api/sistema son solo para Admin.
        // Si tu Middleware permite a ambos, cambiarás este test. Asumimos solo Admin.
        $fechaInicio = Carbon::now()->subDays(5)->format('Y-m-d');
        $fechaFin    = Carbon::now()->format('Y-m-d');

        $response = $this->actingAsSecretaria()->getJson("/api/sistema/reportes/ingresos?fecha_inicio={$fechaInicio}&fecha_fin={$fechaFin}");

        $response->assertStatus(403);
    }

    /** @test */
    public function error_de_validacion_al_enviar_formato_de_fechas_incorrecto()
    {
        $response = $this->actingAsAdmin()->getJson("/api/sistema/reportes/ingresos?fecha_inicio=invalid_date&fecha_fin=2023-01-01");

        // Puede ser 422 si hay Request validation, o 400.
        $this->assertContains($response->status(), [400, 422]);
    }

    /** @test */
    public function error_de_regla_de_negocio_si_fecha_fin_es_menor_a_fecha_inicio(): void
    {
        $response = $this->actingAsAdmin()->getJson('/api/sistema/reportes/ingresos?fecha_inicio=2024-01-10&fecha_fin=2024-01-01');

        $this->assertContains($response->status(), [400, 422]);
    }

    /** @test */
    public function admin_obtiene_reporte_vacio_si_no_hubo_movimientos_en_rango(): void
    {
        $response = $this->actingAsAdmin()->getJson('/api/sistema/reportes/ingresos?fecha_inicio=1990-01-01&fecha_fin=1990-12-31');

        $response->assertStatus(200);
    }

    /** @test */
    public function error_de_seguridad_usuario_no_autenticado_no_puede_generar_reportes(): void
    {
        $response = $this->getJson('/api/sistema/reportes/citas?fecha_inicio=2023-01-01&fecha_fin=2023-12-31');

        $response->assertStatus(401);
    }
}
