<?php

namespace Tests\Feature;

use App\Models\LogActividad;
use App\Models\Rol;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CU-13: Visualizar Auditoría del Sistema
 *
 * Pruebas unitarias para la visualización de logs de actividad.
 */
class CU13_AuditoriaSistemaTest extends TestCase
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
            'username'          => 'admin_audit',
            'password_hash'     => bcrypt('Pass123@'),
            'correo'            => 'admin.audit@arludent.com',
            'estado'            => 'activo',
        ]);
        $this->userAdmin->roles()->attach($rolAdmin->id_rol, ['fecha_asignacion' => now()]);

        // Secretaria
        $this->userSecretaria = User::create([
            'username'          => 'sec_audit',
            'password_hash'     => bcrypt('Pass123@'),
            'correo'            => 'sec.audit@arludent.com',
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

    private function crearLog($accion = 'crear', $modulo = 'Citas', $descripcion = 'Se creó una cita'): LogActividad
    {
        return LogActividad::create([
            'id_usuario'        => $this->userSecretaria->id_usuario,
            'accion'            => $accion,
            'modulo_afectado'   => $modulo,
            'registro_afectado' => '1',
            'descripcion'       => $descripcion,
            'ip_usuario'        => '127.0.0.1',
            'fecha_hora'        => now()
        ]);
    }

    // =====================================================
    // FLUJO: AUDITORÍA (ADMINISTRADOR)
    // =====================================================

    /** @test */
    public function admin_consulta_historial_completo_de_auditoria_del_sistema()
    {
        $this->crearLog();
        $this->crearLog('actualizar', 'Pacientes', 'Se actualizó el paciente');

        $response = $this->actingAsAdmin()->getJson('/api/sistema/auditoria');

        $response->assertStatus(200)
                 ->assertJsonPath('success', true);
                 
        $data = $response->json('data.logs');
        $this->assertGreaterThanOrEqual(2, count($data));
    }

    /** @test */
    public function admin_inspecciona_el_detalle_exacto_de_un_log_de_actividad()
    {
        $log = $this->crearLog();

        $response = $this->actingAsAdmin()->getJson('/api/sistema/auditoria/' . $log->id_log);

        $response->assertStatus(200)
                 ->assertJsonPath('success', true)
                 ->assertJsonPath('data.log.id_log', $log->id_log);
    }

    /** @test */
    public function admin_obtiene_estadisticas_globales_de_las_acciones_de_usuarios()
    {
        $this->crearLog();

        $response = $this->actingAsAdmin()->getJson('/api/sistema/auditoria-estadisticas');

        $response->assertStatus(200)
                 ->assertJsonPath('success', true);
    }

    /** @test */
    public function admin_recupera_diccionario_de_acciones_registradas_en_el_sistema()
    {
        $response = $this->actingAsAdmin()->getJson('/api/sistema/auditoria-acciones');

        $response->assertStatus(200)
                 ->assertJsonPath('success', true);
    }

    /** @test */
    public function admin_lista_todos_los_modulos_monitoreados_por_el_log()
    {
        $response = $this->actingAsAdmin()->getJson('/api/sistema/auditoria-modulos');

        $response->assertStatus(200)
                 ->assertJsonPath('success', true);
    }

    /** @test */
    public function admin_aplica_filtros_parametrizados_para_buscar_auditoria_por_modulo()
    {
        $this->crearLog('crear', 'Citas', 'Cita 1');
        $this->crearLog('crear', 'Facturacion', 'Pago 1');

        $response = $this->actingAsAdmin()->getJson('/api/sistema/auditoria?modulo=Citas');

        $response->assertStatus(200)
                 ->assertJsonPath('success', true);

        // Verificamos que todos los devueltos sean de Citas
        $data = $response->json('data.logs');
        foreach ($data as $item) {
            $this->assertEquals('Citas', $item['modulo_afectado']);
        }
    }

    // =====================================================
    // FLUJO ALTERNO: PERMISOS
    // =====================================================

    /** @test */
    public function error_de_autorizacion_roles_operativos_no_tienen_acceso_a_auditoria()
    {
        $response = $this->actingAsSecretaria()->getJson('/api/sistema/auditoria');

        $response->assertStatus(403);
    }

    /** @test */
    public function error_al_consultar_un_registro_de_auditoria_inexistente()
    {
        $response = $this->actingAsAdmin()->getJson('/api/sistema/auditoria/999999');

        $this->assertContains($response->status(), [404, 422]);
    }

    /** @test */
    public function error_de_seguridad_usuario_anonimo_intenta_acceder_auditoria()
    {
        $response = $this->getJson('/api/sistema/auditoria');

        $response->assertStatus(401);
    }

    /** @test */
    public function error_de_regla_de_negocio_los_logs_de_auditoria_son_inmutables_y_no_se_pueden_eliminar()
    {
        $log = $this->crearLog();

        // En un sistema estricto, la API no expone un endpoint DELETE para auditoría.
        // Simulamos un intento de borrado. Si no existe la ruta, da 404 o 405 Method Not Allowed.
        $response = $this->actingAsAdmin()->deleteJson('/api/sistema/auditoria/' . $log->id_log);

        $this->assertContains($response->status(), [404, 405, 403]);
        
        $this->assertDatabaseHas('log_actividad', [
            'id_log' => $log->id_log
        ]);
    }
}
