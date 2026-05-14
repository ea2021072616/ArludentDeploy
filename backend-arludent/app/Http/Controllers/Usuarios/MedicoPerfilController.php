<?php

namespace App\Http\Controllers\Usuarios;

use App\Http\Controllers\Controller;
use App\Models\LogActividad;
use App\Models\Medico;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controlador de Perfil Médico.
 *
 * Permite al médico autenticado consultar y actualizar su información
 * profesional, incluyendo la gestión de su foto de perfil.
 */
class MedicoPerfilController extends Controller
{
    /** Tamaño máximo permitido para la foto de perfil en KB. */
    private const MAX_PHOTO_SIZE_KB = 2048;

    /** Máximo de años de experiencia permitidos. */
    private const MAX_EXPERIENCE_YEARS = 60;

    /** Disco de almacenamiento para fotos de médicos. */
    private const STORAGE_DISK = 'public';

    /** Ruta de almacenamiento para fotos de médicos. */
    private const PHOTOS_PATH = 'medicos/fotos';

    /** Campos del perfil médico actualizables por el propio médico. */
    private const UPDATABLE_FIELDS = [
        'nombres', 'apellidos', 'nro_colegiado',
        'especialidad', 'anios_experiencia', 'estado_disponibilidad',
    ];

    /**
     * Obtener perfil médico del usuario autenticado.
     */
    public function getPerfil(): JsonResponse
    {
        $medico = $this->obtenerMedicoAutenticado();

        if (!$medico) {
            return $this->errorResponse('No se encontró el perfil médico.', Response::HTTP_NOT_FOUND);
        }

        return $this->successResponse([
            'medico' => $this->formatearRespuestaMedico($medico),
        ]);
    }

    /**
     * Actualizar perfil médico.
     *
     * El médico puede editar: nro_colegiado, especialidad, anios_experiencia, foto.
     * El tipo_medico solo lo edita el admin (en UsuarioAdminController).
     */
    public function updatePerfil(Request $request): JsonResponse
    {
        $medico = $this->obtenerMedicoAutenticado();

        if (!$medico) {
            /** @var User $usuario */
            $usuario = Auth::user();
            $medico = new Medico(['id_usuario' => $usuario->id_usuario]);
        }

        $validator = Validator::make($request->all(), [
            'nombres'                => 'sometimes|string|max:100',
            'apellidos'              => 'sometimes|string|max:100',
            'nro_colegiado'          => 'sometimes|string|max:50',
            'especialidad'           => 'nullable|string|max:100',
            'anios_experiencia'      => 'nullable|integer|min:0|max:' . self::MAX_EXPERIENCE_YEARS,
            'foto'                   => 'nullable|image|mimes:jpeg,png,jpg|max:' . self::MAX_PHOTO_SIZE_KB,
            'estado_disponibilidad'  => 'sometimes|in:disponible,no_disponible',
        ], [
            'nombres.max'               => 'Los nombres no pueden tener más de 100 caracteres.',
            'apellidos.max'             => 'Los apellidos no pueden tener más de 100 caracteres.',
            'nro_colegiado.max'         => 'El número de colegiado no puede tener más de 50 caracteres.',
            'especialidad.max'          => 'La especialidad no puede tener más de 100 caracteres.',
            'anios_experiencia.integer' => 'Los años de experiencia deben ser un número.',
            'anios_experiencia.min'     => 'Los años de experiencia no pueden ser negativos.',
            'anios_experiencia.max'     => 'Los años de experiencia no pueden exceder ' . self::MAX_EXPERIENCE_YEARS . '.',
            'foto.image'                => 'El archivo debe ser una imagen.',
            'foto.mimes'                => 'La foto debe ser JPEG, PNG o JPG.',
            'foto.max'                  => 'La foto no puede pesar más de 2MB.',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $camposActualizados = array_filter(
            $request->only(self::UPDATABLE_FIELDS),
            fn ($valor) => $valor !== null
        );

        if ($request->hasFile('foto')) {
            $this->eliminarFotoAnterior($medico);
            $camposActualizados['foto_url'] = $request->file('foto')->store(self::PHOTOS_PATH, self::STORAGE_DISK);
        }

        if ($medico->exists) {
            $medico->update($camposActualizados);
        } else {
            $medico->fill($camposActualizados);
            $medico->save();
        }

        /** @var User $usuario */
        $usuario = Auth::user();
        LogActividad::create([
            'id_usuario'      => $usuario->id_usuario,
            'accion'          => 'actualizar_perfil_medico',
            'modulo_afectado' => 'medicos',
            'descripcion'     => 'Perfil médico actualizado',
            'ip_usuario'      => $request->ip(),
        ]);

        $medicoActualizado = $medico->fresh()->load('usuario');

        return $this->successResponse(
            ['medico' => $this->formatearRespuestaMedico($medicoActualizado)],
            'Perfil médico actualizado exitosamente.'
        );
    }

    /**
     * Eliminar la foto de perfil del médico autenticado.
     */
    public function eliminarFoto(): JsonResponse
    {
        $medico = $this->obtenerMedicoAutenticado();

        if (!$medico || !$medico->foto_url) {
            return $this->errorResponse('No hay foto para eliminar.', Response::HTTP_NOT_FOUND);
        }

        $this->eliminarFotoAnterior($medico);
        $medico->update(['foto_url' => null]);

        return $this->successResponse(null, 'Foto eliminada exitosamente.');
    }

    // ────────────────────────────────────────────────────────────────────────────
    // Métodos privados auxiliares
    // ────────────────────────────────────────────────────────────────────────────

    /**
     * Obtiene el perfil de médico del usuario autenticado, o null si no existe.
     */
    private function obtenerMedicoAutenticado(): ?Medico
    {
        /** @var User $usuario */
        $usuario = Auth::user();

        return Medico::with('usuario')->where('id_usuario', $usuario->id_usuario)->first();
    }

    /**
     * Formatea la respuesta del médico incluyendo datos básicos del usuario.
     */
    private function formatearRespuestaMedico(Medico $medico): array
    {
        $datos = $medico->toArray();

        if ($medico->usuario) {
            $datos['usuario'] = [
                'id_usuario' => $medico->usuario->id_usuario,
                'username'   => $medico->usuario->username,
                'correo'     => $medico->usuario->correo,
                'telefono'   => $medico->usuario->telefono ?? null,
                'estado'     => $medico->usuario->estado ?? null,
            ];
        }

        return $datos;
    }

    /**
     * Elimina la foto anterior del médico del almacenamiento si existe.
     */
    private function eliminarFotoAnterior(Medico $medico): void
    {
        if ($medico->foto_url && Storage::disk(self::STORAGE_DISK)->exists($medico->foto_url)) {
            Storage::disk(self::STORAGE_DISK)->delete($medico->foto_url);
        }
    }
}
