<?php

namespace Tests\Feature;

use App\Models\LogActividad;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CU-01: Gestionar Usuarios
 *
 * Pruebas unitarias del módulo de administración de usuarios.
 * Verifica CRUD de usuarios, asignación de roles, validaciones
 * y restricciones de seguridad desde la perspectiva del administrador.
 */
class CU01_GestionarUsuariosTest extends TestCase
{
    use RefreshDatabase;

    private User $userAdmin;
    private Rol $rolAdmin;
    private Rol $rolMedico;
    private Rol $rolPaciente;
    private Rol $rolExterno;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rolAdmin    = Rol::create(['nombre_rol' => 'admin',    'descripcion' => 'Administrador']);
        $this->rolMedico   = Rol::create(['nombre_rol' => 'medico',   'descripcion' => 'Médico']);
        $this->rolPaciente = Rol::create(['nombre_rol' => 'paciente', 'descripcion' => 'Paciente']);
        $this->rolExterno  = Rol::create(['nombre_rol' => 'externo',  'descripcion' => 'Externo']);
        Rol::create(['nombre_rol' => 'secretaria', 'descripcion' => 'Secretaria']);

        $this->userAdmin = User::create([
            'username'          => 'admin_pruebas',
            'password_hash'     => bcrypt('Admin123@'),
            'correo'            => 'admin.pruebas@arludent.com',
            'telefono'          => '987000001',
            'estado'            => 'activo',
            'email_verified_at' => now(),
        ]);
        $this->userAdmin->roles()->attach($this->rolAdmin->id_rol, ['fecha_asignacion' => now()]);
    }

    private function actingAsAdmin()
    {
        return $this->actingAs($this->userAdmin, 'api');
    }

    private function crearUsuarioConRol(string $rol, array $override = []): User
    {
        $usuario = User::create(array_merge([
            'username'          => 'user_' . uniqid(),
            'password_hash'     => bcrypt('Pass123@'),
            'correo'            => uniqid() . '@arludent.com',
            'telefono'          => '987654321',
            'estado'            => 'activo',
            'email_verified_at' => now(),
        ], $override));

        $rolObj = Rol::where('nombre_rol', $rol)->first();
        if ($rolObj) {
            $usuario->roles()->attach($rolObj->id_rol, ['fecha_asignacion' => now()]);
        }

        return $usuario;
    }

    // =====================================================
    // FLUJO NORMAL: LISTAR USUARIOS
    // =====================================================

    /** @test */
    public function admin_lista_todos_los_usuarios_del_sistema(): void
    {
        $this->crearUsuarioConRol('medico');
        $this->crearUsuarioConRol('paciente');

        $response = $this->actingAsAdmin()
            ->getJson('/api/admin/usuarios');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertGreaterThanOrEqual(3, count($response->json('data.usuarios.data')));
    }

    /** @test */
    public function admin_filtra_usuarios_por_rol_especifico(): void
    {
        $this->crearUsuarioConRol('medico');
        $this->crearUsuarioConRol('medico');

        $response = $this->actingAsAdmin()
            ->getJson('/api/admin/usuarios?rol=medico');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $usuarios = $response->json('data.usuarios.data');
        foreach ($usuarios as $usuario) {
            $roles = collect($usuario['roles'])->pluck('nombre_rol');
            $this->assertTrue($roles->contains('medico'));
        }
    }

    /** @test */
    public function admin_puede_filtrar_usuarios_por_estado(): void
    {
        $this->crearUsuarioConRol('externo', ['estado' => 'inactivo']);

        $response = $this->actingAsAdmin()
            ->getJson('/api/admin/usuarios?estado=inactivo');

        $response->assertStatus(200);

        $usuarios = $response->json('data.usuarios.data');
        foreach ($usuarios as $usuario) {
            $this->assertEquals('inactivo', $usuario['estado']);
        }
    }

    /** @test */
    public function admin_puede_buscar_usuario_por_correo(): void
    {
        $this->crearUsuarioConRol('externo', ['correo' => 'buscame.especial@arludent.com']);

        $response = $this->actingAsAdmin()
            ->getJson('/api/admin/usuarios?buscar=buscame.especial');

        $response->assertStatus(200);

        $usuarios = $response->json('data.usuarios.data');
        $correos  = collect($usuarios)->pluck('correo')->toArray();
        $this->assertContains('buscame.especial@arludent.com', $correos);
    }

    // =====================================================
    // FLUJO NORMAL: CREAR USUARIO
    // =====================================================

    /** @test */
    public function admin_puede_crear_usuario_con_rol_externo(): void
    {
        $response = $this->actingAsAdmin()
            ->postJson('/api/admin/usuarios', [
                'username' => 'nuevo_externo',
                'correo'   => 'nuevo.externo@arludent.com',
                'password' => 'Nuevo123@',
                'telefono' => '987654310',
                'rol'      => 'externo',
                'estado'   => 'activo',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Usuario creado exitosamente.',
            ]);

        $this->assertDatabaseHas('usuarios', [
            'correo'  => 'nuevo.externo@arludent.com',
            'estado'  => 'activo',
        ]);
    }

    /** @test */
    public function crear_usuario_nuevo_con_datos_de_perfil_validos(): void
    {
        $response = $this->actingAsAdmin()
            ->postJson('/api/admin/usuarios', [
                'username' => 'nuevo_medico',
                'correo'   => 'nuevo.medico@arludent.com',
                'password' => 'Medico123@',
                'rol'      => 'medico',
                'medico'   => [
                    'nro_colegiado' => 'COL-99999',
                    'especialidad'  => 'Ortodoncia',
                    'tipo_medico'   => 'especialista',
                ],
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('medicos', [
            'nro_colegiado' => 'COL-99999',
        ]);
    }

    /** @test */
    public function admin_puede_crear_usuario_con_rol_paciente_y_perfil(): void
    {
        $response = $this->actingAsAdmin()
            ->postJson('/api/admin/usuarios', [
                'username' => 'nuevo_paciente',
                'correo'   => 'nuevo.paciente@arludent.com',
                'password' => 'Paciente123@',
                'rol'      => 'paciente',
                'paciente' => [
                    'nombres'          => 'Luis',
                    'apellidos'        => 'Torres Vega',
                    'dni'              => '87654321',
                    'fecha_nacimiento' => '2000-01-15',
                    'sexo'             => 'M',
                ],
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('pacientes', [
            'dni'  => '87654321',
        ]);
    }

    /** @test */
    public function crear_usuario_registra_log_de_auditoria(): void
    {
        $this->actingAsAdmin()
            ->postJson('/api/admin/usuarios', [
                'username' => 'usuario_audit',
                'correo'   => 'usuario.audit@arludent.com',
                'password' => 'Audit123@',
                'rol'      => 'externo',
            ]);

        $this->assertDatabaseHas('log_actividad', [
            'accion'          => 'crear_usuario',
            'modulo_afectado' => 'usuarios',
        ]);
    }

    // =====================================================
    // FLUJO NORMAL: VER DETALLE DE USUARIO
    // =====================================================

    /** @test */
    public function admin_puede_ver_detalle_de_usuario(): void
    {
        $usuario = $this->crearUsuarioConRol('externo');

        $response = $this->actingAsAdmin()
            ->getJson('/api/admin/usuarios/' . $usuario->id_usuario);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data'    => ['user' => ['correo' => $usuario->correo]],
            ]);
    }

    /** @test */
    public function error_al_ver_usuario_inexistente(): void
    {
        $response = $this->actingAsAdmin()
            ->getJson('/api/admin/usuarios/999999');

        $response->assertStatus(404);
    }

    // =====================================================
    // FLUJO NORMAL: ACTUALIZAR USUARIO
    // =====================================================

    /** @test */
    public function admin_desactiva_logicamente_un_usuario_activo(): void
    {
        $usuario = $this->crearUsuarioConRol('externo');

        $response = $this->actingAsAdmin()
            ->putJson('/api/admin/usuarios/' . $usuario->id_usuario, [
                'telefono' => '999111222',
                'estado'   => 'inactivo',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Usuario actualizado exitosamente.',
            ]);

        $this->assertDatabaseHas('usuarios', [
            'id_usuario' => $usuario->id_usuario,
            'telefono'   => '999111222',
            'estado'     => 'inactivo',
        ]);
    }

    /** @test */
    public function generacion_de_log_de_auditoria_al_editar_un_usuario(): void
    {
        $usuario = $this->crearUsuarioConRol('externo');

        $this->actingAsAdmin()
            ->putJson('/api/admin/usuarios/' . $usuario->id_usuario, [
                'estado' => 'inactivo',
            ]);

        $this->assertDatabaseHas('log_actividad', [
            'accion'          => 'actualizar_usuario',
            'modulo_afectado' => 'usuarios',
        ]);
    }

    // =====================================================
    // FLUJO NORMAL: ELIMINAR USUARIO
    // =====================================================

    /** @test */
    public function admin_puede_eliminar_usuario_externo(): void
    {
        $usuario = $this->crearUsuarioConRol('externo');

        $response = $this->actingAsAdmin()
            ->deleteJson('/api/admin/usuarios/' . $usuario->id_usuario);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Usuario eliminado exitosamente.',
            ]);

        $this->assertDatabaseMissing('usuarios', [
            'id_usuario' => $usuario->id_usuario,
        ]);
    }

    // =====================================================
    // FLUJO NORMAL: CAMBIAR ROL
    // =====================================================

    /** @test */
    public function admin_edita_rol_de_un_usuario_existente_a_especialista(): void
    {
        $usuario = $this->crearUsuarioConRol('externo');

        $response = $this->actingAsAdmin()
            ->postJson('/api/admin/usuarios/' . $usuario->id_usuario . '/cambiar-rol', [
                'rol' => 'medico',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Rol actualizado exitosamente.',
            ]);

        $roles = $usuario->fresh()->roles->pluck('nombre_rol')->toArray();
        $this->assertContains('medico', $roles);
    }

    /** @test */
    public function cambiar_rol_registra_log_de_auditoria(): void
    {
        $usuario = $this->crearUsuarioConRol('externo');

        $this->actingAsAdmin()
            ->postJson('/api/admin/usuarios/' . $usuario->id_usuario . '/cambiar-rol', [
                'rol' => 'paciente',
            ]);

        $this->assertDatabaseHas('log_actividad', [
            'accion'          => 'cambiar_rol',
            'modulo_afectado' => 'usuarios',
        ]);
    }

    // =====================================================
    // FLUJO ALTERNO: VALIDACIONES
    // =====================================================

    /** @test */
    public function error_al_crear_usuario_con_correo_electronico_duplicado(): void
    {
        $this->crearUsuarioConRol('externo', ['correo' => 'duplicado@arludent.com']);

        $response = $this->actingAsAdmin()
            ->postJson('/api/admin/usuarios', [
                'username' => 'otro_usuario',
                'correo'   => 'duplicado@arludent.com',
                'password' => 'Pass123@',
                'rol'      => 'externo',
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function error_al_crear_usuario_con_username_duplicado(): void
    {
        $this->crearUsuarioConRol('externo', ['username' => 'user_duplicado']);

        $response = $this->actingAsAdmin()
            ->postJson('/api/admin/usuarios', [
                'username' => 'user_duplicado',
                'correo'   => 'otro.correo@arludent.com',
                'password' => 'Pass123@',
                'rol'      => 'externo',
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function error_al_crear_usuario_con_contrasena_debil(): void
    {
        $response = $this->actingAsAdmin()
            ->postJson('/api/admin/usuarios', [
                'username' => 'user_debil',
                'correo'   => 'user.debil@arludent.com',
                'password' => '12345678',  // Sin mayúsculas ni símbolos
                'rol'      => 'externo',
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function error_al_crear_medico_sin_nro_colegiado(): void
    {
        $response = $this->actingAsAdmin()
            ->postJson('/api/admin/usuarios', [
                'username' => 'medico_sin_col',
                'correo'   => 'medico.sincol@arludent.com',
                'password' => 'Medico123@',
                'rol'      => 'medico',
                // Falta nro_colegiado
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function error_admin_no_puede_eliminarse_a_si_mismo(): void
    {
        $response = $this->actingAsAdmin()
            ->deleteJson('/api/admin/usuarios/' . $this->userAdmin->id_usuario);

        $response->assertStatus(400)
            ->assertJson(['success' => false]);
    }

    /** @test */
    public function error_al_cambiar_a_rol_invalido(): void
    {
        $usuario = $this->crearUsuarioConRol('externo');

        $response = $this->actingAsAdmin()
            ->postJson('/api/admin/usuarios/' . $usuario->id_usuario . '/cambiar-rol', [
                'rol' => 'superadmin',  // Rol no permitido
            ]);

        $response->assertStatus(422);
    }

    // =====================================================
    // FLUJO ALTERNO: AUTORIZACIÓN
    // =====================================================

    /** @test */
    public function usuario_sin_autenticacion_no_puede_listar_usuarios(): void
    {
        $response = $this->getJson('/api/admin/usuarios');

        $response->assertStatus(401);
    }

    /** @test */
    public function usuario_con_rol_medico_no_puede_acceder_al_panel_admin(): void
    {
        $userMedico = $this->crearUsuarioConRol('medico');

        $response = $this->actingAs($userMedico, 'api')
            ->getJson('/api/admin/usuarios');

        $response->assertStatus(403);
    }

    /** @test */
    public function acceso_denegado_a_listar_usuarios_por_perfil_paciente(): void
    {
        $userPaciente = $this->crearUsuarioConRol('paciente');

        $response = $this->actingAs($userPaciente, 'api')
            ->getJson('/api/admin/usuarios');

        $response->assertStatus(403);
    }
}

