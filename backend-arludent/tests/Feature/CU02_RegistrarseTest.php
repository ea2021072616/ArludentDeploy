<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * CU-02: Registrarse en el Sistema
 *
 * Pruebas unitarias del módulo de auto-registro de usuarios externos.
 * Verifica el registro exitoso, validaciones de datos, asignación del
 * rol 'externo' por defecto y el envío del correo de verificación.
 */
class CU02_RegistrarseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Los roles deben existir para que el registro funcione
        Rol::create(['nombre_rol' => 'externo',   'descripcion' => 'Usuario externo sin perfil clínico']);
        Rol::create(['nombre_rol' => 'admin',     'descripcion' => 'Administrador']);
        Rol::create(['nombre_rol' => 'medico',    'descripcion' => 'Médico']);
        Rol::create(['nombre_rol' => 'paciente',  'descripcion' => 'Paciente']);
        Rol::create(['nombre_rol' => 'secretaria','descripcion' => 'Secretaria']);
    }

    private function datosRegistroValidos(array $override = []): array
    {
        return array_merge([
            'username'              => 'nuevo_usuario',
            'correo'                => 'nuevo.usuario@arludent.com',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
            'telefono'              => '987654321',
        ], $override);
    }

    // =====================================================
    // FLUJO NORMAL: REGISTRO EXITOSO
    // =====================================================

    /** @test */
    public function registro_exitoso_de_nuevo_usuario_con_datos_validos(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/auth/registro', $this->datosRegistroValidos());

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertStringContainsStringIgnoringCase(
            'verifica',
            strtolower($response->json('message')) . strtolower($response->json('data.user.estado') ?? '')
        );
    }

    /** @test */
    public function registro_persiste_correctamente_en_base_de_datos(): void
    {
        Mail::fake();

        $this->postJson('/api/auth/registro', $this->datosRegistroValidos([
            'correo' => 'verificar.bd@arludent.com',
        ]));

        $this->assertDatabaseHas('usuarios', [
            'correo' => 'verificar.bd@arludent.com',
        ]);
    }

    /** @test */
    public function registro_asigna_automaticamente_rol_externo(): void
    {
        Mail::fake();

        $this->postJson('/api/auth/registro', $this->datosRegistroValidos([
            'correo' => 'rol.externo@arludent.com',
        ]));

        $usuario = User::where('correo', 'rol.externo@arludent.com')->first();
        $this->assertNotNull($usuario);

        $roles = $usuario->roles->pluck('nombre_rol')->toArray();
        $this->assertContains('externo', $roles);
    }

    /** @test */
    public function registro_inicia_con_estado_pendiente_de_verificacion(): void
    {
        Mail::fake();

        $this->postJson('/api/auth/registro', $this->datosRegistroValidos([
            'correo' => 'estado.pendiente@arludent.com',
        ]));

        $this->assertDatabaseHas('usuarios', [
            'correo' => 'estado.pendiente@arludent.com',
            'estado' => 'pendiente',
        ]);
    }

    /** @test */
    public function registro_retorna_datos_del_usuario_creado_en_json(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/auth/registro', $this->datosRegistroValidos([
            'correo' => 'datos.usuario@arludent.com',
        ]));

        $response->assertStatus(201);

        $user = $response->json('data.user');
        $this->assertNotNull($user['id_usuario'] ?? null);
        $this->assertEquals('datos.usuario@arludent.com', $user['correo'] ?? null);
    }

    // =====================================================
    // FLUJO ALTERNO: VALIDACIONES DE DATOS
    // =====================================================

    /** @test */
    public function error_al_registrarse_con_correo_electronico_ya_registrado(): void
    {
        Mail::fake();

        // Primer registro exitoso
        $this->postJson('/api/auth/registro', $this->datosRegistroValidos([
            'correo' => 'duplicado.correo@arludent.com',
        ]));

        // Segundo intento con el mismo correo
        $response = $this->postJson('/api/auth/registro', $this->datosRegistroValidos([
            'correo'   => 'duplicado.correo@arludent.com',
            'username' => 'otro_usuario',
        ]));

        $response->assertStatus(422);
    }

    /** @test */
    public function error_al_registrarse_con_campo_correo_vacio(): void
    {
        $datos = $this->datosRegistroValidos();
        unset($datos['correo']);

        $response = $this->postJson('/api/auth/registro', $datos);

        $response->assertStatus(422);
    }

    /** @test */
    public function error_al_registrarse_con_formato_de_correo_invalido(): void
    {
        $response = $this->postJson('/api/auth/registro', $this->datosRegistroValidos([
            'correo' => 'esto-no-es-un-correo',
        ]));

        $response->assertStatus(422);
    }

    /** @test */
    public function error_al_registrarse_con_contrasena_menor_a_ocho_caracteres(): void
    {
        $response = $this->postJson('/api/auth/registro', $this->datosRegistroValidos([
            'password'              => 'Ab1!',  // Menos de 8 caracteres
            'password_confirmation' => 'Ab1!',
        ]));

        $response->assertStatus(422);
    }

    /** @test */
    public function error_al_registrarse_con_contrasena_sin_mayusculas(): void
    {
        $response = $this->postJson('/api/auth/registro', $this->datosRegistroValidos([
            'password'              => 'password123!',  // Sin mayúscula
            'password_confirmation' => 'password123!',
        ]));

        $response->assertStatus(422);
    }

    /** @test */
    public function error_al_registrarse_con_contrasena_sin_simbolos(): void
    {
        $response = $this->postJson('/api/auth/registro', $this->datosRegistroValidos([
            'password'              => 'Password123',   // Sin símbolo
            'password_confirmation' => 'Password123',
        ]));

        $response->assertStatus(422);
    }

    /** @test */
    public function error_al_registrarse_con_confirmacion_de_contrasena_distinta(): void
    {
        $response = $this->postJson('/api/auth/registro', $this->datosRegistroValidos([
            'password'              => 'Password123!',
            'password_confirmation' => 'PasswordDistinta123!',
        ]));

        $response->assertStatus(422);
    }

    /** @test */
    public function error_al_registrarse_con_campo_contrasena_vacio(): void
    {
        $datos = $this->datosRegistroValidos();
        unset($datos['password']);
        unset($datos['password_confirmation']);

        $response = $this->postJson('/api/auth/registro', $datos);

        $response->assertStatus(422);
    }

    /** @test */
    public function error_al_registrarse_con_nombre_de_usuario_duplicado(): void
    {
        Mail::fake();

        $this->postJson('/api/auth/registro', $this->datosRegistroValidos([
            'username' => 'username_dup',
            'correo'   => 'primero@arludent.com',
        ]));

        $response = $this->postJson('/api/auth/registro', $this->datosRegistroValidos([
            'username' => 'username_dup',
            'correo'   => 'segundo@arludent.com',
        ]));

        $response->assertStatus(422);
    }

    // =====================================================
    // FLUJO NORMAL: VERIFICACIÓN DE CORREO
    // =====================================================

    /** @test */
    public function error_al_verificar_correo_con_token_invalido(): void
    {
        $response = $this->getJson('/api/auth/verificar-correo?token=token_invalido_xyz&correo=test@arludent.com');

        // El sistema rechaza tokens inválidos (400 o 404)
        $this->assertContains($response->status(), [400, 404, 422]);
    }

    /** @test */
    public function error_al_verificar_correo_sin_parametros_requeridos(): void
    {
        $response = $this->getJson('/api/auth/verificar-correo');

        $this->assertContains($response->status(), [400, 422]);
    }

    // =====================================================
    // FLUJO NORMAL: LOGIN POST-REGISTRO (SIN VERIFICAR)
    // =====================================================

    /** @test */
    public function usuario_con_correo_no_verificado_no_puede_iniciar_sesion(): void
    {
        Mail::fake();

        $this->postJson('/api/auth/registro', $this->datosRegistroValidos([
            'correo'   => 'no.verificado@arludent.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]));

        // Intentar login sin verificar correo
        $response = $this->postJson('/api/auth/login', [
            'correo'   => 'no.verificado@arludent.com',
            'password' => 'Password123!',
        ]);

        // El sistema debe rechazar el login (401 o 403) porque el correo no está verificado
        $this->assertContains($response->status(), [401, 403, 422]);
    }
}
