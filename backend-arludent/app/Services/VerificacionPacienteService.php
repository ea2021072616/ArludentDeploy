<?php

namespace App\Services;

use App\Models\Paciente;
use App\Models\Cita;
use Illuminate\Support\Str;

/**
 * Servicio de Verificación de Identidad de Pacientes
 *
 * Valida la identidad del paciente mediante DNI y coincidencia parcial del nombre
 * para operaciones críticas como cancelar, reprogramar o cambiar médico de citas.
 */
class VerificacionPacienteService
{
    /**
     * Busca un paciente por DNI (solo DNI, sin verificación de nombre)
     *
     * @param string $dni DNI del paciente
     * @return array ['success' => bool, 'paciente' => Paciente|null, 'message' => string]
     */
    public function buscarPacientePorDni(string $dni): array
    {
        // Buscar paciente por DNI
        $paciente = Paciente::where('dni', $dni)->first();

        if (!$paciente) {
            return [
                'success' => false,
                'paciente' => null,
                'message' => 'No se encontró ningún paciente con el DNI proporcionado.'
            ];
        }

        // Paciente encontrado
        return [
            'success' => true,
            'paciente' => $paciente,
            'message' => 'Paciente encontrado correctamente.'
        ];
    }

    /**
     * Verifica la identidad de un paciente mediante DNI y nombre parcial
     *
     * @param string $dni DNI del paciente
     * @param string $nombreParcial Al menos 2 caracteres del nombre del paciente
     * @return array ['success' => bool, 'paciente' => Paciente|null, 'message' => string]
     */
    public function verificarIdentidadPaciente(string $dni, string $nombreParcial): array
    {
        // Validar longitud mínima del nombre parcial
        if (strlen($nombreParcial) < 2) {
            return [
                'success' => false,
                'paciente' => null,
                'message' => 'El nombre parcial debe tener al menos 2 caracteres para verificar la identidad.'
            ];
        }

        // Buscar paciente por DNI
        $paciente = Paciente::where('dni', $dni)->first();

        if (!$paciente) {
            return [
                'success' => false,
                'paciente' => null,
                'message' => 'No se encontró ningún paciente con el DNI proporcionado.'
            ];
        }

        // Concatenar nombres y apellidos para búsqueda flexible
        $nombreCompleto = strtolower($paciente->nombres . ' ' . $paciente->apellidos);
        $nombreBuscar = strtolower($nombreParcial);

        // Verificar si el nombre parcial está contenido en el nombre completo
        if (!Str::contains($nombreCompleto, $nombreBuscar)) {
            return [
                'success' => false,
                'paciente' => null,
                'message' => 'El nombre proporcionado no coincide con el paciente registrado con ese DNI.'
            ];
        }

        // Verificación exitosa
        return [
            'success' => true,
            'paciente' => $paciente,
            'message' => 'Identidad verificada correctamente.'
        ];
    }

    /**
     * Busca una cita y valida que pertenece al paciente especificado
     *
     * @param int $citaId ID de la cita
     * @param int $pacienteId ID del paciente
     * @return array ['success' => bool, 'cita' => Cita|null, 'message' => string]
     */
    public function buscarCitaYValidarPaciente(int $citaId, int $pacienteId): array
    {
        $cita = Cita::with(['medico.usuario', 'paciente'])->find($citaId);

        if (!$cita) {
            return [
                'success' => false,
                'cita' => null,
                'message' => 'No se encontró la cita especificada.'
            ];
        }

        // Verificar que la cita pertenece al paciente
        if ($cita->id_paciente != $pacienteId) {
            return [
                'success' => false,
                'cita' => null,
                'message' => 'La cita no pertenece al paciente verificado.'
            ];
        }

        return [
            'success' => true,
            'cita' => $cita,
            'message' => 'Cita encontrada y validada correctamente.'
        ];
    }

    /**
     * Verifica que una cita puede ser cancelada o reprogramada (valida su estado)
     *
     * @param Cita $cita La cita a verificar
     * @return array ['success' => bool, 'message' => string]
     */
    public function validarEstadoParaModificacion(Cita $cita): array
    {
        $estadosModificables = ['pendiente', 'confirmado', 'en_espera'];

        if (!in_array($cita->estado, $estadosModificables)) {
            $mensajesEstado = [
                'completado' => 'La cita ya fue completada y no puede ser modificada.',
                'cancelado' => 'La cita ya está cancelada.',
                'no_asistio' => 'La cita está marcada como no asistida y no puede ser modificada.',
                'siendo_atendido' => 'La cita está en atención actualmente y no puede ser modificada.'
            ];

            $mensaje = $mensajesEstado[$cita->estado] ?? 'La cita no puede ser modificada en su estado actual.';

            return [
                'success' => false,
                'message' => $mensaje
            ];
        }

        return [
            'success' => true,
            'message' => 'La cita puede ser modificada.'
        ];
    }

    /**
     * Formatea la información de una cita para respuesta
     *
     * @param Cita $cita
     * @return array
     */
    public function formatearInfoCita(Cita $cita): array
    {
        return [
            'id_cita' => $cita->id_cita,
            'fecha_hora' => $cita->fecha_hora_inicio->format('Y-m-d H:i'),
            'motivo' => $cita->motivo,
            'estado' => $cita->estado,
            'medico' => [
                'id' => $cita->medico->id_medico,
                'nombre' => $cita->medico->usuario->nombres . ' ' . $cita->medico->usuario->apellidos,
                'especialidad' => $cita->medico->especialidad
            ],
            'paciente' => [
                'id' => $cita->paciente->id_paciente,
                'nombre' => $cita->paciente->nombres . ' ' . $cita->paciente->apellidos,
                'dni' => $cita->paciente->dni
            ]
        ];
    }
}
