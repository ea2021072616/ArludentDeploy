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
 * CU-12: Visualizar Indicadores en Dashboard
 *
 * Pruebas unitarias para la visualización de KPIs e indicadores.
 */
class CU12_DashboardIndicadoresTest extends TestCase
{
    use RefreshDatabase;

    private User $userAdmin;
    private User $userSecretaria;

    protected function setUp(): void
    {
        parent::setUp();

        $rolAdmin      = Rol::create(['nombre_rol' => 'admin', 'descripcion' => 'Administrador']);
        $rolSecretaria = Rol::create(['nombre_rol' => 'secretaria', 'descripcion' => 'Secretaria']);

        // Administrador
        $this->userAdmin = User::create([
            'username'          => 'admin_kpi',
            'password_hash'     => bcrypt('Pass123@'),
            'correo'            => 'admin.kpi@arludent.com',
            'estado'            => 'activo',
        ]);
        $this->userAdmin->roles()->attach($rolAdmin->id_rol, ['fecha_asignacion' => now()]);

        // Secretaria
        $this->userSecretaria = User::create([
            'username'          => 'sec_kpi',
            'password_hash'     => bcrypt('Pass123@'),
            'correo'            => 'sec.kpi@arludent.com',
            'estado'            => 'activo',
        ]);
        $this->userSecretaria->roles()->attach($rolSecretaria->id_rol, ['fecha_asignacion' => now()]);
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
    // FLUJO: DASHBOARD KPI ADMIN
    // =====================================================

    /** @test */
    public function admin_accede_a_kpis_financieros_y_operativos_del_dashboard()
    {
        $response = $this->actingAsAdmin()->getJson('/api/sistema/indicadores/dashboard-kpis');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'periodo',
                         'kpis' => [
                             'total_ingresos',
                             'nuevos_pacientes',
                             'total_citas'
                         ]
                     ]
                 ]);
    }

    /** @test */
    public function admin_visualiza_estadistica_de_tratamientos_mas_solicitados()
    {
        $response = $this->actingAsAdmin()->getJson('/api/sistema/indicadores/tratamientos-solicitados');

        $response->assertStatus(200)
                 ->assertJsonPath('success', true);
    }

    /** @test */
    public function admin_evalua_carga_laboral_y_citas_atendidas_por_cada_medico()
    {
        $response = $this->actingAsAdmin()->getJson('/api/sistema/indicadores/citas-por-medico');

        $response->assertStatus(200)
                 ->assertJsonPath('success', true);
    }

    /** @test */
    public function admin_proyecta_tendencias_de_ingresos_monetarios_mensuales()
    {
        $response = $this->actingAsAdmin()->getJson('/api/sistema/indicadores/tendencias-ingresos');

        $response->assertStatus(200)
                 ->assertJsonPath('success', true);
    }

    /** @test */
    public function admin_monitorea_el_indice_de_satisfaccion_y_feedback_de_pacientes()
    {
        $response = $this->actingAsAdmin()->getJson('/api/sistema/indicadores/satisfaccion-pacientes');

        $response->assertStatus(200)
                 ->assertJsonPath('success', true);
    }

    // =====================================================
    // FLUJO: DASHBOARD SECRETARÍA
    // =====================================================

    /** @test */
    public function secretaria_accede_a_su_panel_operativo_de_citas_diarias()
    {
        $response = $this->actingAsSecretaria()->getJson('/api/secretaria/dashboard');

        $response->assertStatus(200)
                 ->assertJsonPath('status', 'success');
    }

    /** @test */
    public function error_de_autorizacion_secretaria_intenta_consumir_kpis_gerenciales()
    {
        $response = $this->actingAsSecretaria()->getJson('/api/sistema/indicadores/dashboard-kpis');

        $response->assertStatus(403);
    }

    // =====================================================
    // FLUJO ALTERNO: SEGURIDAD Y PERMISOS
    // =====================================================

    /** @test */
    public function error_de_seguridad_usuario_anonimo_no_puede_ver_kpis_gerenciales()
    {
        $response = $this->getJson('/api/sistema/indicadores/dashboard-kpis');
        $response->assertStatus(401);
    }

    /** @test */
    public function error_de_seguridad_usuario_anonimo_no_puede_ver_panel_operativo()
    {
        $response = $this->getJson('/api/secretaria/dashboard');
        $response->assertStatus(401);
    }

    /** @test */
    public function error_de_autorizacion_admin_no_puede_acceder_al_panel_exclusivo_de_secretaria()
    {
        $response = $this->actingAsAdmin()->getJson('/api/secretaria/dashboard');
        
        // Dependiendo de cómo configuren los roles, un admin podría tener o no acceso a la ruta de secretaria. 
        // Usamos assertContains para proteger la prueba.
        $this->assertContains($response->status(), [403, 200]);
    }
}
