<?php

namespace App\Http\Controllers\GestionClinica;

use App\Http\Controllers\Controller;
use App\Models\SeguimientoPostTratamiento;
use App\Models\Paciente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SeguimientoPostTratamientoController extends Controller
{
    /**
     * Crear un nuevo seguimiento
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_paciente' => 'required|exists:pacientes,id_paciente',
            'id_cita' => 'nullable|exists:citas,id_cita',
            'id_historial' => 'nullable|exists:historial_clinico,id_historial',
            'fecha_seguimiento' => 'required|date',
            'tipo_seguimiento' => 'required|in:postoperatorio,revision,medicacion,general',
            'metodo_contacto' => 'nullable|in:llamada,whatsapp,email,portal,presencial,otro',
            'prioridad' => 'nullable|in:baja,media,alta,urgente',
            'notas_secretaria' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $paciente = Paciente::find($request->id_paciente);

        // Determinar método de contacto automáticamente si no se especifica
        $metodoContacto = $request->metodo_contacto ?? $this->determinarMetodoContacto($paciente);

        // Generar token único para respuesta del paciente
        $token = Str::random(64);

        $seguimiento = SeguimientoPostTratamiento::create([
            'id_paciente' => $request->id_paciente,
            'id_cita' => $request->id_cita,
            'id_historial' => $request->id_historial,
            'fecha_seguimiento' => $request->fecha_seguimiento,
            'tipo_seguimiento' => $request->tipo_seguimiento,
            'metodo_contacto' => $metodoContacto,
            'prioridad' => $request->prioridad ?? 'media',
            'estado' => 'pendiente',
            'notas_secretaria' => $request->notas_secretaria,
            'gestionado_por_ia' => $metodoContacto === 'email' || $metodoContacto === 'portal',
            'token_respuesta' => $token,
            'realizado_por' => Auth::id(),
        ]);

        return response()->json([
            'message' => 'Seguimiento creado correctamente',
            'seguimiento' => $seguimiento->load(['paciente', 'cita'])
        ], 201);
    }

    /**
     * Actualizar un seguimiento
     */
    public function update(Request $request, $id)
    {
        $seguimiento = SeguimientoPostTratamiento::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'fecha_seguimiento' => 'sometimes|date',
            'tipo_seguimiento' => 'sometimes|in:postoperatorio,revision,medicacion,general',
            'metodo_contacto' => 'sometimes|in:llamada,whatsapp,email,portal,presencial,otro',
            'prioridad' => 'sometimes|in:baja,media,alta,urgente',
            'estado' => 'sometimes|in:pendiente,enviado,respondido,realizado,requiere_revision,cancelado',
            'notas_secretaria' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $seguimiento->update($request->only([
            'fecha_seguimiento',
            'tipo_seguimiento',
            'metodo_contacto',
            'prioridad',
            'estado',
            'notas_secretaria',
        ]));

        return response()->json([
            'message' => 'Seguimiento actualizado correctamente',
            'seguimiento' => $seguimiento->load(['paciente', 'cita'])
        ]);
    }

    /**
     * Eliminar un seguimiento
     */
    public function destroy($id)
    {
        $seguimiento = SeguimientoPostTratamiento::findOrFail($id);
        $seguimiento->delete();

        return response()->json(['message' => 'Seguimiento eliminado correctamente']);
    }

    /**
     * Registrar contacto manual (cuando secretaria llama)
     */
    public function registrarContacto(Request $request, $id)
    {
        $seguimiento = SeguimientoPostTratamiento::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'respuesta_paciente' => 'required|string',
            'tiene_problema' => 'required|boolean',
            'descripcion_problema' => 'nullable|required_if:tiene_problema,true|string',
            'sintomas' => 'nullable|string',
            'requiere_cita_urgente' => 'nullable|boolean',
            'notas_secretaria' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $seguimiento->update([
            'respuesta_paciente' => $request->respuesta_paciente,
            'tiene_problema' => $request->tiene_problema,
            'descripcion_problema' => $request->descripcion_problema,
            'sintomas' => $request->sintomas,
            'requiere_cita_urgente' => $request->requiere_cita_urgente ?? false,
            'notas_secretaria' => $request->notas_secretaria,
            'fecha_realizado' => now(),
            'estado' => $request->tiene_problema ? 'requiere_revision' : 'realizado',
            'prioridad' => $request->tiene_problema ? 'alta' : $seguimiento->prioridad,
        ]);

        return response()->json([
            'message' => 'Contacto registrado correctamente',
            'seguimiento' => $seguimiento->load(['paciente'])
        ]);
    }

    /**
     * Respuesta del paciente desde el portal
     */
    public function responderPaciente(Request $request, $token)
    {
        $seguimiento = SeguimientoPostTratamiento::where('token_respuesta', $token)
            ->firstOrFail();

        // Validar que no haya sido respondido
        if ($seguimiento->estado === 'respondido' || $seguimiento->estado === 'realizado') {
            return response()->json([
                'message' => 'Este seguimiento ya ha sido respondido'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'estado_paciente' => 'required|in:muy_bien,bien,regular,mal',
            'descripcion' => 'required|string|max:1000',
            'sintomas' => 'nullable|array',
            'sintomas.*' => 'string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Guardar respuesta
        $seguimiento->update([
            'respuesta_paciente' => $request->descripcion,
            'sintomas' => json_encode($request->sintomas ?? []),
            'estado' => 'respondido',
            'respondido_paciente_at' => now(),
        ]);

        // Si el paciente está mal, marcar como requiere revisión
        if ($request->estado_paciente === 'mal') {
            $seguimiento->update([
                'tiene_problema' => true,
                'prioridad' => 'urgente',
                'requiere_cita_urgente' => true,
            ]);
        }

        // Enviar al microservicio IA para análisis si está habilitado
        if ($seguimiento->gestionado_por_ia) {
            $this->enviarAnalisisIA($seguimiento, $request->all());
        }

        return response()->json([
            'message' => 'Gracias por tu respuesta. Hemos registrado tu información.',
            'requiere_atencion' => $request->estado_paciente === 'mal'
        ]);
    }

    /**
     * Enviar datos al microservicio de IA para análisis
     */
    private function enviarAnalisisIA(SeguimientoPostTratamiento $seguimiento, array $requestData)
    {
        try {
            $aiUrl = env('AI_SERVICE_URL', 'http://127.0.0.1:8001') . '/api/v1/seguimiento/analizar-respuesta';
            
            $payload = [
                'seguimiento_id' => $seguimiento->id_seguimiento,
                'paciente_nombre' => $seguimiento->paciente->nombres . ' ' . $seguimiento->paciente->apellidos,
                'tipo_tratamiento' => $seguimiento->cita ? ($seguimiento->cita->motivo_consulta ?? 'Tratamiento Odontológico') : 'Tratamiento Odontológico',
                'dias_desde_tratamiento' => max(1, \Carbon\Carbon::parse($seguimiento->fecha_seguimiento)->diffInDays(now())),
                'respuesta' => [
                    'estado_paciente' => $requestData['estado_paciente'] ?? 'regular',
                    'sintomas_reportados' => implode(', ', $requestData['sintomas'] ?? []),
                    'observaciones_paciente' => $requestData['descripcion'] ?? '',
                    'necesita_revision' => in_array('El paciente solicita agendar una cita de revisión.', $requestData['sintomas'] ?? []) || ($requestData['estado_paciente'] ?? '') === 'mal'
                ]
            ];

            \Illuminate\Support\Facades\Http::timeout(30)->post($aiUrl, $payload);
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error al enviar análisis a IA: ' . $e->getMessage());
        }
    }

    /**
     * Webhook para recibir análisis de IA
     */
    public function webhookIA(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'seguimiento_id' => 'required|exists:seguimientos_post_tratamiento,id_seguimiento',
            'analisis' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $seguimiento = SeguimientoPostTratamiento::find($request->seguimiento_id);
        $analisis = $request->analisis;

        $seguimiento->update([
            'analisis_ia' => $analisis,
            'tiene_problema' => $analisis['requiere_atencion'] ?? false,
            'prioridad' => $analisis['urgencia'] ?? 'media',
            'requiere_cita_urgente' => $analisis['requiere_cita_urgente'] ?? false,
            'estado' => ($analisis['requiere_atencion'] ?? false) ? 'requiere_revision' : 'realizado',
            'descripcion_problema' => $analisis['recomendacion'] ?? null,
        ]);

        // Si requiere atención, notificar a secretaria
        if ($analisis['requiere_atencion'] ?? false) {
            // TODO: Implementar notificación en tiempo real
            // event(new SeguimientoUrgenteEvent($seguimiento));

            Log::info('Seguimiento urgente detectado', [
                'id_seguimiento' => $seguimiento->id_seguimiento,
                'paciente' => $seguimiento->paciente->nombres . ' ' . $seguimiento->paciente->apellidos,
                'analisis' => $analisis
            ]);
        }

        return response()->json([
            'message' => 'Análisis procesado correctamente',
            'seguimiento' => $seguimiento
        ]);
    }

    /**
     * Determinar el mejor método de contacto para el paciente
     */
    private function determinarMetodoContacto(Paciente $paciente): string
    {
        // Prioridad 1: Email (para IA)
        if ($paciente->correo) {
            return 'email';
        }

        // Prioridad 2: Portal (si tiene usuario)
        if ($paciente->usuario) {
            return 'portal';
        }

        // Prioridad 3: WhatsApp
        if ($paciente->telefono) {
            return 'whatsapp';
        }

        // Prioridad 4: Llamada
        if ($paciente->telefono_responsable) {
            return 'llamada';
        }

        // Fallback
        return 'otro';
    }

    /**
     * Obtener seguimiento por token (para el paciente)
     */
    public function obtenerPorToken($token)
    {
        $seguimiento = SeguimientoPostTratamiento::where('token_respuesta', $token)
            ->with(['paciente', 'cita'])
            ->firstOrFail();

        return response()->json([
            'seguimiento' => $seguimiento
        ]);
    }
}
