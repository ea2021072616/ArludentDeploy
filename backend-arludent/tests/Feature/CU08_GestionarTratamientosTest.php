<?php

namespace Tests\Feature;

use App\Models\LogActividad;
use App\Models\Rol;
use App\Models\Tratamiento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CU-08: Gestionar Tratamientos
 *
 * Pruebas unitarias del módulo de gestión del catálogo de tratamientos.
 * Verifica CRUD completo, validaciones, filtros y auditoría.
 */
class CU08_GestionarTratamientosTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private string $token;

    /**
     * Configuración inicial: crea usuario admin autenticado con JWT
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Crear rol admin
        $rol = Rol::create([
            'nombre_rol' => 'admin',
            'descripcion' => 'Administrador del sistema',
        ]);

        // Crear usuario admin
        $this->admin = User::create([
            'username' => 'admin_test',
            'password_hash' => bcrypt('Admin123@'),
            'correo' => 'admin@arludent.com',
            'telefono' => '987654321',
            'estado' => 'activo',
            'email_verified_at' => now(),
        ]);

        // Asignar rol
        $this->admin->roles()->attach($rol->id_rol, [
            'fecha_asignacion' => now(),
        ]);

        // Obtener token JWT
        $this->token = auth('api')->login($this->admin);
    }

    /**
     * Helper: headers de autenticación
     */
    private function authHeaders(): array
    {
        return ['Authorization' => 'Bearer ' . $this->token];
    }

    // =====================================================
    // FLUJO NORMAL: CREAR TRATAMIENTO
    // =====================================================

    /** @test */
    public function puede_crear_tratamiento_con_datos_validos(): void
    {
        $datos = [
            'nombre' => 'Limpieza Dental Profunda',
            'categoria' => 'Preventivo',
            'descripcion' => 'Limpieza profesional con ultrasonido',
            'precio_actual' => 150.00,
        ];

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/admin/tratamientos', $datos);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Tratamiento creado exitosamente.',
            ]);

        $this->assertDatabaseHas('tratamientos', [
            'nombre' => 'Limpieza Dental Profunda',
            'categoria' => 'Preventivo',
            'estado' => 'activo',
        ]);
    }

    /** @test */
    public function tratamiento_creado_tiene_estado_activo_por_defecto(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/admin/tratamientos', [
                'nombre' => 'Blanqueamiento LED',
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('tratamientos', [
            'nombre' => 'Blanqueamiento LED',
            'estado' => 'activo',
        ]);
    }

    /** @test */
    public function crear_tratamiento_genera_log_de_auditoria(): void
    {
        $this->withHeaders($this->authHeaders())
            ->postJson('/api/admin/tratamientos', [
                'nombre' => 'Ortodoncia Convencional',
                'categoria' => 'Ortodoncia',
                'precio_actual' => 2500.00,
            ]);

        $this->assertDatabaseHas('log_actividad', [
            'accion' => 'crear_tratamiento',
            'modulo_afectado' => 'tratamientos',
        ]);
    }

    // =====================================================
    // FLUJO NORMAL: LISTAR Y FILTRAR TRATAMIENTOS
    // =====================================================

    /** @test */
    public function puede_listar_todos_los_tratamientos(): void
    {
        Tratamiento::create(['nombre' => 'Limpieza', 'categoria' => 'Preventivo', 'estado' => 'activo']);
        Tratamiento::create(['nombre' => 'Endodoncia', 'categoria' => 'Restaurador', 'estado' => 'activo']);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/admin/tratamientos?per_page=all');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $tratamientos = $response->json('data.tratamientos');
        $this->assertCount(2, $tratamientos);
    }

    /** @test */
    public function puede_filtrar_tratamientos_por_categoria(): void
    {
        Tratamiento::create(['nombre' => 'Limpieza', 'categoria' => 'Preventivo', 'estado' => 'activo']);
        Tratamiento::create(['nombre' => 'Endodoncia', 'categoria' => 'Restaurador', 'estado' => 'activo']);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/admin/tratamientos?categoria=Preventivo&per_page=all');

        $response->assertStatus(200);
        $tratamientos = $response->json('data.tratamientos');
        $this->assertCount(1, $tratamientos);
        $this->assertEquals('Preventivo', $tratamientos[0]['categoria']);
    }

    /** @test */
    public function puede_filtrar_tratamientos_por_estado(): void
    {
        Tratamiento::create(['nombre' => 'Activo', 'estado' => 'activo']);
        Tratamiento::create(['nombre' => 'Inactivo', 'estado' => 'inactivo']);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/admin/tratamientos?estado=inactivo&per_page=all');

        $response->assertStatus(200);
        $tratamientos = $response->json('data.tratamientos');
        $this->assertCount(1, $tratamientos);
        $this->assertEquals('inactivo', $tratamientos[0]['estado']);
    }

    /** @test */
    public function puede_buscar_tratamientos_por_texto(): void
    {
        Tratamiento::create(['nombre' => 'Limpieza Dental', 'categoria' => 'Preventivo', 'estado' => 'activo']);
        Tratamiento::create(['nombre' => 'Endodoncia', 'categoria' => 'Restaurador', 'estado' => 'activo']);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/admin/tratamientos?busqueda=Limpieza&per_page=all');

        $response->assertStatus(200);
        $tratamientos = $response->json('data.tratamientos');
        $this->assertCount(1, $tratamientos);
        $this->assertStringContainsString('Limpieza', $tratamientos[0]['nombre']);
    }

    // =====================================================
    // FLUJO NORMAL: OBTENER TRATAMIENTO ESPECÍFICO
    // =====================================================

    /** @test */
    public function puede_obtener_un_tratamiento_especifico(): void
    {
        $tratamiento = Tratamiento::create([
            'nombre' => 'Extracción Simple',
            'categoria' => 'Cirugía',
            'precio_actual' => 80.00,
            'estado' => 'activo',
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/admin/tratamientos/' . $tratamiento->id_tratamiento);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'tratamiento' => [
                        'nombre' => 'Extracción Simple',
                        'categoria' => 'Cirugía',
                    ],
                ],
            ]);
    }

    // =====================================================
    // FLUJO NORMAL: ACTUALIZAR TRATAMIENTO
    // =====================================================

    /** @test */
    public function puede_actualizar_tratamiento_existente(): void
    {
        $tratamiento = Tratamiento::create([
            'nombre' => 'Limpieza',
            'precio_actual' => 150.00,
            'estado' => 'activo',
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->putJson('/api/admin/tratamientos/' . $tratamiento->id_tratamiento, [
                'precio_actual' => 180.00,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Tratamiento actualizado exitosamente.',
            ]);

        $this->assertDatabaseHas('tratamientos', [
            'id_tratamiento' => $tratamiento->id_tratamiento,
            'precio_actual' => 180.00,
        ]);
    }

    /** @test */
    public function actualizar_tratamiento_genera_log_de_auditoria(): void
    {
        $tratamiento = Tratamiento::create([
            'nombre' => 'Limpieza',
            'precio_actual' => 150.00,
            'estado' => 'activo',
        ]);

        $this->withHeaders($this->authHeaders())
            ->putJson('/api/admin/tratamientos/' . $tratamiento->id_tratamiento, [
                'precio_actual' => 200.00,
            ]);

        $this->assertDatabaseHas('log_actividad', [
            'accion' => 'actualizar_tratamiento',
            'modulo_afectado' => 'tratamientos',
        ]);
    }

    // =====================================================
    // FLUJO NORMAL: CAMBIAR ESTADO
    // =====================================================

    /** @test */
    public function puede_cambiar_estado_de_activo_a_inactivo(): void
    {
        $tratamiento = Tratamiento::create([
            'nombre' => 'Blanqueamiento',
            'estado' => 'activo',
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/admin/tratamientos/' . $tratamiento->id_tratamiento . '/cambiar-estado', [
                'estado' => 'inactivo',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('tratamientos', [
            'id_tratamiento' => $tratamiento->id_tratamiento,
            'estado' => 'inactivo',
        ]);
    }

    /** @test */
    public function puede_cambiar_estado_de_inactivo_a_activo(): void
    {
        $tratamiento = Tratamiento::create([
            'nombre' => 'Blanqueamiento',
            'estado' => 'inactivo',
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/admin/tratamientos/' . $tratamiento->id_tratamiento . '/cambiar-estado', [
                'estado' => 'activo',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('tratamientos', [
            'id_tratamiento' => $tratamiento->id_tratamiento,
            'estado' => 'activo',
        ]);
    }

    /** @test */
    public function cambiar_estado_genera_log_de_auditoria(): void
    {
        $tratamiento = Tratamiento::create([
            'nombre' => 'Blanqueamiento',
            'estado' => 'activo',
        ]);

        $this->withHeaders($this->authHeaders())
            ->postJson('/api/admin/tratamientos/' . $tratamiento->id_tratamiento . '/cambiar-estado', [
                'estado' => 'inactivo',
            ]);

        $this->assertDatabaseHas('log_actividad', [
            'accion' => 'cambiar_estado_tratamiento',
            'modulo_afectado' => 'tratamientos',
        ]);
    }

    // =====================================================
    // FLUJO NORMAL: OBTENER CATEGORÍAS
    // =====================================================

    /** @test */
    public function puede_obtener_categorias_unicas(): void
    {
        Tratamiento::create(['nombre' => 'T1', 'categoria' => 'Preventivo', 'estado' => 'activo']);
        Tratamiento::create(['nombre' => 'T2', 'categoria' => 'Preventivo', 'estado' => 'activo']);
        Tratamiento::create(['nombre' => 'T3', 'categoria' => 'Restaurador', 'estado' => 'activo']);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/admin/tratamientos/categorias');

        $response->assertStatus(200);
        $categorias = $response->json('data.categorias');
        $this->assertCount(2, $categorias);
        $this->assertContains('Preventivo', $categorias);
        $this->assertContains('Restaurador', $categorias);
    }

    // =====================================================
    // FLUJO ALTERNO: VALIDACIONES NEGATIVAS
    // =====================================================

    /** @test */
    public function error_al_crear_tratamiento_sin_nombre(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/admin/tratamientos', [
                'categoria' => 'Preventivo',
                'precio_actual' => 100.00,
            ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('tratamientos', 0);
    }

    /** @test */
    public function error_al_crear_tratamiento_con_nombre_mayor_100_caracteres(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/admin/tratamientos', [
                'nombre' => str_repeat('A', 101),
            ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('tratamientos', 0);
    }

    /** @test */
    public function error_al_crear_tratamiento_con_precio_negativo(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/admin/tratamientos', [
                'nombre' => 'Test',
                'precio_actual' => -50,
            ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('tratamientos', 0);
    }

    /** @test */
    public function error_al_crear_tratamiento_con_estado_invalido(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/admin/tratamientos', [
                'nombre' => 'Test',
                'estado' => 'eliminado',
            ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('tratamientos', 0);
    }

    /** @test */
    public function error_al_cambiar_estado_con_valor_invalido(): void
    {
        $tratamiento = Tratamiento::create([
            'nombre' => 'Test',
            'estado' => 'activo',
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/admin/tratamientos/' . $tratamiento->id_tratamiento . '/cambiar-estado', [
                'estado' => 'eliminado',
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function error_al_acceder_sin_rol_admin(): void
    {
        // Crear usuario sin rol admin
        $rolPaciente = Rol::create([
            'nombre_rol' => 'paciente',
            'descripcion' => 'Paciente del sistema',
        ]);

        $userPaciente = User::create([
            'username' => 'paciente_test',
            'password_hash' => bcrypt('Pass123@'),
            'correo' => 'paciente@arludent.com',
            'estado' => 'activo',
            'email_verified_at' => now(),
        ]);

        $userPaciente->roles()->attach($rolPaciente->id_rol, [
            'fecha_asignacion' => now(),
        ]);

        $tokenPaciente = auth('api')->login($userPaciente);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $tokenPaciente])
            ->getJson('/api/admin/tratamientos');

        // Un paciente no debería poder acceder al módulo admin
        $response->assertStatus(403);
    }
}
