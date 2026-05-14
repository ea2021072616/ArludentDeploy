<?php

namespace App\Services;

use App\Models\User;
use App\Models\Paciente;
use App\Models\HistorialClinico;
use App\Models\Rol;
use App\Models\LogActividad;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Servicio de Historial Clínico
 *
 * Gestiona la lógica de negocio relacionada con historiales clínicos
 * y la conversión de usuarios externos a pacientes
 */
class HistorialClinicoService
{
    /**
     * Crea un historial clínico para un usuario
     * Si el usuario es externo, lo convierte a paciente automáticamente
     *
     * @param User $usuario Usuario para el cual crear el historial
     * @param int $idMedico ID del médico responsable
     * @param array $datosHistorial Datos del historial clínico
     * @param array|null $datosPaciente Datos del paciente (si es nuevo)
     * @return array ['success' => bool, 'historial' => HistorialClinico|null, 'message' => string]
     */
    public function crearHistorialYConvertirAPaciente(
        User $usuario,
        int $idMedico,
        array $datosHistorial,
        ?array $datosPaciente = null
    ): array {
        DB::beginTransaction();

        try {
            // 1. Verificar si ya tiene historial clínico
            $historialExistente = HistorialClinico::whereHas('paciente', function ($query) use ($usuario) {
                $query->where('id_usuario', $usuario->id_usuario);
            })->first();

            if ($historialExistente) {
                DB::rollBack();
                return [
                    'success' => false,
                    'historial' => null,
                    'message' => 'El usuario ya tiene un historial clínico registrado.',
                ];
            }

            // 2. Buscar o crear registro en tabla pacientes
            $paciente = Paciente::where('id_usuario', $usuario->id_usuario)->first();

            if (!$paciente) {
                // Validar que se enviaron los datos del paciente
                if (!$datosPaciente || empty($datosPaciente)) {
                    DB::rollBack();
                    return [
                        'success' => false,
                        'historial' => null,
                        'message' => 'Se requieren los datos del paciente para crear el historial clínico.',
                    ];
                }

                // Crear registro de paciente
                $paciente = $this->crearRegistroPaciente($usuario, $datosPaciente);

                if (!$paciente) {
                    DB::rollBack();
                    return [
                        'success' => false,
                        'historial' => null,
                        'message' => 'Error al crear el registro del paciente.',
                    ];
                }

                Log::info("Registro de paciente creado para usuario ID: {$usuario->id_usuario}");
            }

            // 3. Crear el historial clínico
            $codigoHistorial = 'HC-' . strtoupper(uniqid());

            $historial = HistorialClinico::create([
                'id_paciente' => $paciente->id_paciente,
                'id_medico_responsable' => $idMedico,
                'codigo_historial' => $codigoHistorial,
                'created_by' => $usuario->id_usuario,
                ...$datosHistorial // Spread operator para agregar todos los campos
            ]);

            // 4. Cambiar rol de 'externo' a 'paciente' si corresponde
            if ($usuario->hasRole('externo')) {
                $this->convertirExternoAPaciente($usuario);
                Log::info("Usuario ID {$usuario->id_usuario} convertido de 'externo' a 'paciente'");
            }

            // 5. Registrar actividad en el log
            LogActividad::create([
                'id_usuario' => $usuario->id_usuario,
                'accion' => 'crear_historial_clinico',
                'modulo_afectado' => 'historial_clinico',
                'registro_afectado' => $historial->id_historial,
                'descripcion' => "Historial clínico creado. Código: {$codigoHistorial}. Usuario convertido a paciente.",
                'ip_usuario' => request()->ip(),
            ]);

            DB::commit();

            return [
                'success' => true,
                'historial' => $historial->load('paciente', 'medicoResponsable'),
                'paciente' => $paciente,
                'message' => 'Historial clínico creado exitosamente. El usuario ahora es un paciente registrado.',
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear historial clínico: ' . $e->getMessage(), [
                'usuario_id' => $usuario->id_usuario,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'historial' => null,
                'message' => 'Error al crear el historial clínico: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Crea un registro en la tabla pacientes para un usuario
     *
     * @param User $usuario
     * @param array $datos Datos del paciente
     * @return Paciente|null
     */
    private function crearRegistroPaciente(User $usuario, array $datos): ?Paciente
    {
        try {
            return Paciente::create([
                'id_usuario' => $usuario->id_usuario,
                'apellidos' => $datos['apellidos'] ?? '',
                'nombres' => $datos['nombres'] ?? '',
                'dni' => $datos['dni'] ?? null,
                'fecha_nacimiento' => $datos['fecha_nacimiento'] ?? null,
                'sexo' => $datos['sexo'] ?? null,
                'domicilio' => $datos['domicilio'] ?? null,
                'persona_responsable' => $datos['persona_responsable'] ?? null,
                'telefono_responsable' => $datos['telefono_responsable'] ?? $usuario->telefono,
                'grupo_sanguineo' => $datos['grupo_sanguineo'] ?? null,
                'alergias' => $datos['alergias'] ?? null,
                'estado' => 'activo',
            ]);
        } catch (\Exception $e) {
            Log::error('Error al crear registro de paciente: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Convierte un usuario con rol 'externo' a 'paciente'
     *
     * @param User $usuario
     * @return bool
     */
    private function convertirExternoAPaciente(User $usuario): bool
    {
        try {
            // Obtener los roles
            $rolExterno = Rol::where('nombre_rol', 'externo')->first();
            $rolPaciente = Rol::where('nombre_rol', 'paciente')->first();

            if (!$rolExterno || !$rolPaciente) {
                Log::error('No se encontraron los roles externo o paciente');
                return false;
            }

            // Remover rol externo
            $usuario->roles()->detach($rolExterno->id_rol);

            // Asignar rol paciente
            $usuario->roles()->attach($rolPaciente->id_rol, [
                'fecha_asignacion' => now(),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Error al convertir externo a paciente: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Verifica si un usuario puede crear un historial clínico
     *
     * @param User $usuario
     * @return array ['puede_crear' => bool, 'razon' => string]
     */
    public function puedeCrearHistorial(User $usuario): array
    {
        // Verificar si ya tiene historial
        $tieneHistorial = HistorialClinico::whereHas('paciente', function ($query) use ($usuario) {
            $query->where('id_usuario', $usuario->id_usuario);
        })->exists();

        if ($tieneHistorial) {
            return [
                'puede_crear' => false,
                'razon' => 'Ya tiene un historial clínico registrado',
            ];
        }

        return [
            'puede_crear' => true,
            'razon' => 'Puede crear historial clínico',
        ];
    }

    /**
     * Obtiene el estado de registro de un usuario
     *
     * @param User $usuario
     * @return array Información sobre el estado del usuario
     */
    public function obtenerEstadoRegistro(User $usuario): array
    {
        $paciente = Paciente::where('id_usuario', $usuario->id_usuario)->first();
        $tieneHistorial = false;
        $historial = null;

        if ($paciente) {
            $historial = HistorialClinico::where('id_paciente', $paciente->id_paciente)->first();
            $tieneHistorial = $historial !== null;
        }

        return [
            'es_externo' => $usuario->hasRole('externo'),
            'es_paciente' => $usuario->hasRole('paciente'),
            'tiene_registro_paciente' => $paciente !== null,
            'tiene_historial' => $tieneHistorial,
            'id_paciente' => $paciente ? $paciente->id_paciente : null,
            'id_historial' => $historial ? $historial->id_historial : null,
            'puede_crear_historial' => !$tieneHistorial,
        ];
    }
}
