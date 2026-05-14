<?php

namespace App\Services;

use App\Models\User;
use App\Models\Rol;
use App\Models\LogActividad;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * Servicio de Autenticación
 *
 * Centraliza toda la lógica de autenticación, registro, login y logout
 * Incluye manejo de tokens JWT y validación de MFA
 */
class AuthService
{
    /**
     * Registra un nuevo usuario en el sistema
     *
     * @param array $datos Datos del usuario (correo, password, username, telefono)
     * @param string $rolInicial Rol a asignar por defecto (default: 'paciente')
     * @return array ['success' => bool, 'user' => User|null, 'message' => string]
     */
    public function registrarUsuario(array $datos, string $rolInicial = 'paciente'): array
    {
        DB::beginTransaction();

        try {
            // Generar token de verificación
            $verificationToken = Str::random(60);

            // Crear usuario
            $usuario = User::create([
                'username' => $datos['username'] ?? null,
                'correo' => $datos['correo'],
                'password_hash' => Hash::make($datos['password']),
                'telefono' => $datos['telefono'] ?? null,
                'estado' => 'pendiente', // Estado inicial hasta verificar correo
                'verification_token' => $verificationToken,
            ]);

            // Asignar rol inicial
            $rol = Rol::where('nombre_rol', $rolInicial)->first();
            if ($rol) {
                $usuario->roles()->attach($rol->id_rol, [
                    'fecha_asignacion' => now(),
                ]);
            }

            // Registrar actividad
            LogActividad::create([
                'id_usuario' => $usuario->id_usuario,
                'accion' => 'registro',
                'modulo_afectado' => 'autenticacion',
                'descripcion' => 'Usuario registrado exitosamente',
                'ip_usuario' => request()->ip(),
            ]);

            DB::commit();

            return [
                'success' => true,
                'user' => $usuario,
                'verification_token' => $verificationToken,
                'message' => 'Usuario registrado exitosamente. Por favor, verifica tu correo electrónico.',
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'success' => false,
                'user' => null,
                'message' => 'Error al registrar usuario: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Autentica un usuario y genera un token JWT
     *
     * @param string $correo Correo electrónico o nombre de usuario
     * @param string $password
     * @return array ['success' => bool, 'token' => string|null, 'user' => User|null, 'requires_mfa' => bool]
     */
    public function login(string $correo, string $password): array
    {
        // Buscar usuario por correo o username
        $usuario = User::where('correo', $correo)
                       ->orWhere('username', $correo)
                       ->first();

        if (!$usuario) {
            return [
                'success' => false,
                'token' => null,
                'user' => null,
                'requires_mfa' => false,
                'message' => 'Credenciales inválidas.',
            ];
        }

        // Verificar contraseña
        if (!Hash::check($password, $usuario->password_hash)) {
            return [
                'success' => false,
                'token' => null,
                'user' => null,
                'requires_mfa' => false,
                'message' => 'Credenciales inválidas.',
            ];
        }

        // Verificar si el correo está verificado
        if (!$usuario->hasVerifiedEmail()) {
            return [
                'success' => false,
                'token' => null,
                'user' => null,
                'requires_mfa' => false,
                'message' => 'Debes verificar tu correo electrónico antes de iniciar sesión.',
            ];
        }

        // Verificar si el usuario está activo
        if ($usuario->estado !== 'activo') {
            return [
                'success' => false,
                'token' => null,
                'user' => null,
                'requires_mfa' => false,
                'message' => 'Tu cuenta no está activa. Contacta al administrador.',
            ];
        }

        // Si tiene MFA activado, no generar token todavía
        if ($usuario->mfa_enabled) {
            return [
                'success' => true,
                'token' => null,
                'user' => $usuario,
                'user_id' => $usuario->id_usuario, // Agregar user_id para el frontend
                'requires_mfa' => true,
                'message' => 'Se requiere código MFA para completar el inicio de sesión.',
            ];
        }

        // Generar token JWT
        $token = JWTAuth::fromUser($usuario);

        // Actualizar último login
        $usuario->update(['last_login' => now()]);

        // Registrar actividad
        LogActividad::create([
            'id_usuario' => $usuario->id_usuario,
            'accion' => 'login',
            'modulo_afectado' => 'autenticacion',
            'descripcion' => 'Inicio de sesión exitoso',
            'ip_usuario' => request()->ip(),
        ]);

        return [
            'success' => true,
            'token' => $token,
            'user' => $usuario->load('roles'),
            'requires_mfa' => false,
            'message' => 'Inicio de sesión exitoso.',
        ];
    }

    /**
     * Cierra sesión invalidando el token JWT actual
     *
     * @return array ['success' => bool, 'message' => string]
     */
    public function logout(): array
    {
        try {
            // Invalidar token actual
            JWTAuth::invalidate(JWTAuth::getToken());

            // Registrar actividad
            $usuario = $this->usuarioAutenticado();
            if ($usuario) {
                LogActividad::create([
                    'id_usuario' => $usuario->id_usuario,
                    'accion' => 'logout',
                    'modulo_afectado' => 'autenticacion',
                    'descripcion' => 'Cierre de sesión',
                    'ip_usuario' => request()->ip(),
                ]);
            }

            return [
                'success' => true,
                'message' => 'Sesión cerrada exitosamente.',
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al cerrar sesión: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Refresca el token JWT actual
     *
     * @return array ['success' => bool, 'token' => string|null]
     */
    public function refreshToken(): array
    {
        try {
            $nuevoToken = JWTAuth::refresh(JWTAuth::getToken());

            return [
                'success' => true,
                'token' => $nuevoToken,
                'message' => 'Token refrescado exitosamente.',
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'token' => null,
                'message' => 'Error al refrescar token: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Obtiene el usuario autenticado actualmente
     *
     * @return User|null
     */
    public function usuarioAutenticado(): ?User
    {
        try {
            return JWTAuth::parseToken()->authenticate();
        } catch (\Exception $e) {
            return null;
        }
    }
}
