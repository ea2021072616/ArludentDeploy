<?php

namespace App\Http\Controllers\Administracion;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Rol;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\LogActividad;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controlador de Administración de Usuarios.
 *
 * Gestiona operaciones CRUD sobre usuarios del sistema,
 * incluyendo la creación de perfiles asociados (médico/paciente)
 * y el registro de actividad administrativa.
 */
class UsuarioAdminController extends Controller
{
    /** Número de resultados por página por defecto en listados. */
    private const DEFAULT_PER_PAGE = 15;

    /** Relaciones del modelo User cargadas por defecto en las respuestas. */
    private const USER_RELATIONS = ['roles', 'medico', 'paciente'];

    /**
     * Expresión regular para validación de contraseñas seguras.
     * Requiere al menos: una minúscula, una mayúscula, un dígito y un símbolo.
     */
    private const PASSWORD_REGEX = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/';

    /**
     * Listar todos los usuarios con filtros opcionales de rol, estado y término de búsqueda.
     */
    public function index(Request $request)
    {
        $query = User::with(self::USER_RELATIONS);

        if ($request->has('rol')) {
            $query->whereHas('roles', function ($queryBuilder) use ($request) {
                $queryBuilder->where('nombre_rol', $request->rol);
            });
        }

        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->has('buscar')) {
            $terminoBusqueda = $request->buscar;
            $query->where(function ($queryBuilder) use ($terminoBusqueda) {
                $queryBuilder->where('username', 'like', "%{$terminoBusqueda}%")
                    ->orWhere('correo', 'like', "%{$terminoBusqueda}%")
                    ->orWhere('telefono', 'like', "%{$terminoBusqueda}%");
            });
        }

        $perPage = $request->get('per_page', self::DEFAULT_PER_PAGE);
        $usuarios = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return $this->successResponse(['usuarios' => $usuarios]);
    }

    /**
     * Crear un nuevo usuario desde el panel de administración.
     *
     * Los usuarios creados por admin se marcan como verificados y activos por defecto.
     * Si el rol es 'medico' o 'paciente', se crea automáticamente el perfil asociado.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:50|unique:usuarios,username',
            'correo'   => 'required|email|max:100|unique:usuarios,correo',
            'password' => ['required', 'string', 'min:8', 'regex:' . self::PASSWORD_REGEX],
            'telefono' => 'nullable|string|max:20',
            'rol'      => 'required|in:admin,medico,secretaria,paciente,externo',
            'estado'   => 'nullable|in:activo,inactivo',

            'medico.nro_colegiado'     => 'required_if:rol,medico|string|max:50',
            'medico.especialidad'      => 'nullable|string|max:100',
            'medico.tipo_medico'       => 'nullable|in:especialista,cabecera_manana,cabecera_tarde',
            'medico.anios_experiencia' => 'nullable|integer|min:0',

            'paciente.apellidos'        => 'required_if:rol,paciente|string|max:100',
            'paciente.nombres'          => 'required_if:rol,paciente|string|max:100',
            'paciente.dni'              => 'nullable|string|max:20',
            'paciente.fecha_nacimiento' => 'nullable|date',
            'paciente.sexo'             => 'nullable|in:M,F,Otro',
            'paciente.domicilio'        => 'nullable|string|max:200',
        ], [
            'username.unique'                   => 'El nombre de usuario ya está en uso.',
            'correo.unique'                     => 'El correo ya está registrado.',
            'password.regex'                    => 'La contraseña debe incluir mayúscula, minúscula, número y símbolo.',
            'rol.required'                      => 'Debe seleccionar un rol.',
            'medico.nro_colegiado.required_if'  => 'El número de colegiado es obligatorio para médicos.',
            'paciente.apellidos.required_if'    => 'Los apellidos son obligatorios para pacientes.',
            'paciente.nombres.required_if'      => 'Los nombres son obligatorios para pacientes.',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        DB::beginTransaction();
        try {
            $usuario = User::create([
                'username'          => $request->username,
                'password_hash'     => Hash::make($request->password),
                'correo'            => $request->correo,
                'telefono'          => $request->telefono,
                'estado'            => $request->get('estado', 'activo'),
                'email_verified_at' => now(), // Admin crea usuarios ya verificados
            ]);

            $this->asignarRolAUsuario($usuario, $request->rol);
            $this->crearPerfilAsociado($usuario, $request);

            $this->registrarActividad(
                'crear_usuario',
                "Usuario creado: {$usuario->username} ({$request->rol})",
                $request->ip()
            );

            DB::commit();

            return $this->successResponse(
                ['user' => $usuario->load(self::USER_RELATIONS)],
                'Usuario creado exitosamente.',
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse(
                'Error al crear usuario: ' . $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Ver detalles de un usuario específico.
     */
    public function show($id)
    {
        $usuario = User::with(self::USER_RELATIONS)->findOrFail($id);

        return $this->successResponse(['user' => $usuario]);
    }

    /**
     * Actualizar los datos de un usuario existente desde el panel de administración.
     *
     * Permite actualizar datos básicos del usuario y, opcionalmente,
     * los datos de su perfil de médico o paciente asociado.
     */
    public function update(Request $request, $id)
    {
        $usuario = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'username' => 'sometimes|string|max:50|unique:usuarios,username,' . $id . ',id_usuario',
            'correo'   => 'sometimes|email|max:100|unique:usuarios,correo,' . $id . ',id_usuario',
            'telefono' => 'nullable|string|max:20',
            'estado'   => 'sometimes|in:activo,inactivo,pendiente',
            'password' => ['sometimes', 'string', 'min:8', 'regex:' . self::PASSWORD_REGEX],

            'medico.nro_colegiado'          => 'sometimes|string|max:50',
            'medico.especialidad'           => 'sometimes|string|max:100',
            'medico.tipo_medico'            => 'sometimes|in:especialista,cabecera_manana,cabecera_tarde',
            'medico.estado_disponibilidad'  => 'sometimes|in:disponible,no_disponible',
            'medico.anios_experiencia'      => 'sometimes|integer|min:0',

            'paciente.nombres'          => 'sometimes|string|max:100',
            'paciente.apellidos'        => 'sometimes|string|max:100',
            'paciente.dni'              => 'sometimes|string|max:20',
            'paciente.fecha_nacimiento' => 'sometimes|date',
            'paciente.sexo'             => 'sometimes|in:M,F,Otro',
            'paciente.domicilio'        => 'sometimes|string|max:200',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        DB::beginTransaction();
        try {
            $camposUsuarioActualizados = $request->only(['username', 'correo', 'telefono', 'estado']);

            if ($request->has('password')) {
                $camposUsuarioActualizados['password_hash'] = Hash::make($request->password);
            }

            $usuario->update($camposUsuarioActualizados);

            $this->actualizarPerfilMedico($usuario, $request);
            $this->actualizarPerfilPaciente($usuario, $request);

            $this->registrarActividad(
                'actualizar_usuario',
                "Usuario actualizado: {$usuario->username}",
                $request->ip()
            );

            DB::commit();

            return $this->successResponse(
                ['user' => $usuario->fresh()->load(self::USER_RELATIONS)],
                'Usuario actualizado exitosamente.'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse(
                'Error al actualizar usuario: ' . $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Eliminar un usuario del sistema.
     *
     * No permite que un administrador elimine su propia cuenta.
     */
    public function destroy($id)
    {
        $usuario = User::findOrFail($id);

        $usuarioAutenticado = $this->obtenerUsuarioAutenticado();
        if ($usuarioAutenticado && $usuario->id_usuario === $usuarioAutenticado->id_usuario) {
            return $this->errorResponse(
                'No puedes eliminar tu propio usuario.',
                Response::HTTP_BAD_REQUEST
            );
        }

        $this->registrarActividad(
            'eliminar_usuario',
            "Usuario eliminado: {$usuario->username}",
            request()->ip()
        );

        $usuario->delete();

        return $this->successResponse(null, 'Usuario eliminado exitosamente.');
    }

    /**
     * Reemplazar el rol actual de un usuario por uno nuevo.
     */
    public function cambiarRol(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'rol' => 'required|in:admin,medico,paciente,externo',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors()->toArray());
        }

        $usuario = User::findOrFail($id);
        $nuevoRol = Rol::where('nombre_rol', $request->rol)->first();

        if (!$nuevoRol) {
            return $this->errorResponse('Rol no encontrado.', Response::HTTP_NOT_FOUND);
        }

        $this->reasignarRol($usuario, $nuevoRol);

        $this->registrarActividad(
            'cambiar_rol',
            "Rol cambiado a {$request->rol} para usuario: {$usuario->username}",
            $request->ip()
        );

        return $this->successResponse(
            ['user' => $usuario->fresh()->load('roles')],
            'Rol actualizado exitosamente.'
        );
    }

    // ────────────────────────────────────────────────────────────────────────────
    // Métodos privados auxiliares
    // ────────────────────────────────────────────────────────────────────────────

    /**
     * Obtiene el usuario actualmente autenticado con type-hint seguro.
     */
    private function obtenerUsuarioAutenticado(): ?User
    {
        /** @var User|null */
        return Auth::user();
    }

    /**
     * Registra una entrada en el log de actividad administrativa.
     */
    private function registrarActividad(string $accion, string $descripcion, ?string $ipUsuario): void
    {
        $usuarioAutenticado = $this->obtenerUsuarioAutenticado();

        LogActividad::create([
            'id_usuario'      => $usuarioAutenticado ? $usuarioAutenticado->id_usuario : null,
            'accion'          => $accion,
            'modulo_afectado' => 'usuarios',
            'descripcion'     => $descripcion,
            'ip_usuario'      => $ipUsuario,
        ]);
    }

    /**
     * Asigna un rol a un usuario recién creado.
     */
    private function asignarRolAUsuario(User $usuario, string $nombreRol): void
    {
        $rol = Rol::where('nombre_rol', $nombreRol)->first();
        if (!$rol) {
            return;
        }

        $usuarioAutenticado = $this->obtenerUsuarioAutenticado();
        $usuario->roles()->attach($rol->id_rol, [
            'asignado_por'     => $usuarioAutenticado ? $usuarioAutenticado->id_usuario : null,
            'fecha_asignacion' => now(),
        ]);
    }

    /**
     * Elimina todos los roles actuales de un usuario y asigna uno nuevo.
     */
    private function reasignarRol(User $usuario, Rol $nuevoRol): void
    {
        $usuarioAutenticado = $this->obtenerUsuarioAutenticado();
        $usuario->roles()->detach();
        $usuario->roles()->attach($nuevoRol->id_rol, [
            'asignado_por'     => $usuarioAutenticado ? $usuarioAutenticado->id_usuario : null,
            'fecha_asignacion' => now(),
        ]);
    }

    /**
     * Crea el perfil de médico o paciente asociado al usuario según su rol.
     */
    private function crearPerfilAsociado(User $usuario, Request $request): void
    {
        if ($request->rol === 'medico' && $request->has('medico')) {
            Medico::create([
                'id_usuario'            => $usuario->id_usuario,
                'nro_colegiado'         => $request->input('medico.nro_colegiado'),
                'especialidad'          => $request->input('medico.especialidad'),
                'tipo_medico'           => $request->input('medico.tipo_medico', 'especialista'),
                'anios_experiencia'     => $request->input('medico.anios_experiencia'),
                'estado_disponibilidad' => 'disponible',
            ]);
        }

        if ($request->rol === 'paciente' && $request->has('paciente')) {
            Paciente::create([
                'id_usuario'       => $usuario->id_usuario,
                'apellidos'        => $request->input('paciente.apellidos'),
                'nombres'          => $request->input('paciente.nombres'),
                'dni'              => $request->input('paciente.dni'),
                'fecha_nacimiento' => $request->input('paciente.fecha_nacimiento'),
                'sexo'             => $request->input('paciente.sexo'),
                'domicilio'        => $request->input('paciente.domicilio'),
                'estado'           => 'activo',
            ]);
        }
    }

    /**
     * Filtra un arreglo eliminando las entradas con valor null.
     */
    private function filtrarCamposNoNulos(array $campos): array
    {
        return array_filter($campos, function ($valor) {
            return $valor !== null;
        });
    }

    /**
     * Actualiza los datos del perfil médico asociado al usuario, si existe.
     */
    private function actualizarPerfilMedico(User $usuario, Request $request): void
    {
        if (!$usuario->medico || !$request->has('medico')) {
            return;
        }

        $camposMedicoActualizados = $this->filtrarCamposNoNulos([
            'nro_colegiado'         => $request->input('medico.nro_colegiado'),
            'especialidad'          => $request->input('medico.especialidad'),
            'tipo_medico'           => $request->input('medico.tipo_medico'),
            'estado_disponibilidad' => $request->input('medico.estado_disponibilidad'),
            'anios_experiencia'     => $request->input('medico.anios_experiencia'),
        ]);

        if (!empty($camposMedicoActualizados)) {
            $usuario->medico->update($camposMedicoActualizados);
        }
    }

    /**
     * Actualiza los datos del perfil de paciente asociado al usuario, si existe.
     */
    private function actualizarPerfilPaciente(User $usuario, Request $request): void
    {
        if (!$usuario->paciente || !$request->has('paciente')) {
            return;
        }

        $camposPacienteActualizados = $this->filtrarCamposNoNulos([
            'nombres'          => $request->input('paciente.nombres'),
            'apellidos'        => $request->input('paciente.apellidos'),
            'dni'              => $request->input('paciente.dni'),
            'fecha_nacimiento' => $request->input('paciente.fecha_nacimiento'),
            'sexo'             => $request->input('paciente.sexo'),
            'domicilio'        => $request->input('paciente.domicilio'),
        ]);

        if (!empty($camposPacienteActualizados)) {
            $usuario->paciente->update($camposPacienteActualizados);
        }
    }
}
