<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo SeguimientoPostTratamiento
 *
 * Gestiona los seguimientos posteriores a tratamientos dentales
 * para verificar la evolución y satisfacción del paciente
 */
class SeguimientoPostTratamiento extends Model
{
    use HasFactory;

    protected $table = 'seguimientos_post_tratamiento';
    protected $primaryKey = 'id_seguimiento';

    protected $fillable = [
        'id_paciente',
        'id_cita',
        'id_historial',
        'fecha_seguimiento',
        'fecha_realizado',
        'metodo_contacto',
        'tipo_seguimiento',
        'estado',
        'prioridad',
        'respuesta_paciente',
        'tiene_problema',
        'descripcion_problema',
        'sintomas',
        'requiere_cita_urgente',
        'seguimiento_padre_id',
        'proxima_fecha_seguimiento',
        'notas_secretaria',
        'notas_medico',
        'realizado_por',
        // Campos IA
        'gestionado_por_ia',
        'enviado_ia_at',
        'analisis_ia',
        'token_respuesta',
        'respondido_paciente_at',
    ];

    protected $casts = [
        'fecha_seguimiento' => 'date',
        'fecha_realizado' => 'datetime',
        'proxima_fecha_seguimiento' => 'date',
        'tiene_problema' => 'boolean',
        'requiere_cita_urgente' => 'boolean',
        // Casts IA
        'gestionado_por_ia' => 'boolean',
        'enviado_ia_at' => 'datetime',
        'respondido_paciente_at' => 'datetime',
        'analisis_ia' => 'array',
    ];

    /**
     * Relación con Paciente
     */
    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'id_paciente', 'id_paciente');
    }

    /**
     * Relación con Cita
     */
    public function cita(): BelongsTo
    {
        return $this->belongsTo(Cita::class, 'id_cita', 'id_cita');
    }

    /**
     * Relación con Historial Clínico
     */
    public function historialClinico(): BelongsTo
    {
        return $this->belongsTo(HistorialClinico::class, 'id_historial', 'id_historial');
    }

    /**
     * Seguimiento padre (si es un seguimiento de otro seguimiento)
     */
    public function seguimientoPadre(): BelongsTo
    {
        return $this->belongsTo(SeguimientoPostTratamiento::class, 'seguimiento_padre_id', 'id_seguimiento');
    }

    /**
     * Seguimientos hijos (seguimientos derivados de este)
     */
    public function seguimientosHijos()
    {
        return $this->hasMany(SeguimientoPostTratamiento::class, 'seguimiento_padre_id', 'id_seguimiento');
    }

    /**
     * Usuario que realizó el seguimiento
     */
    public function realizadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'realizado_por', 'id_usuario');
    }

    /**
     * Scope para seguimientos pendientes
     */
    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    /**
     * Scope para seguimientos vencidos
     */
    public function scopeVencidos($query)
    {
        return $query->where('estado', 'pendiente')
                     ->where('fecha_seguimiento', '<', now());
    }

    /**
     * Scope para seguimientos con problemas
     */
    public function scopeConProblemas($query)
    {
        return $query->where('tiene_problema', true);
    }

    /**
     * Scope para seguimientos urgentes
     */
    public function scopeUrgentes($query)
    {
        return $query->where('prioridad', 'urgente')
                     ->orWhere('requiere_cita_urgente', true);
    }

    /**
     * Scope para seguimientos gestionados por IA
     */
    public function scopeGestionadosPorIA($query)
    {
        return $query->where('gestionado_por_ia', true);
    }

    /**
     * Scope para seguimientos esperando respuesta
     */
    public function scopeEsperandoRespuesta($query)
    {
        return $query->whereIn('estado', ['enviado', 'respondido']);
    }

    /**
     * Verificar si el seguimiento está vencido
     */
    public function getEstaVencidoAttribute(): bool
    {
        return $this->estado === 'pendiente' &&
               $this->fecha_seguimiento < now();
    }

    /**
     * Obtener días de retraso si está vencido
     */
    public function getDiasRetrasoAttribute(): int
    {
        if (!$this->esta_vencido) {
            return 0;
        }

        return now()->diffInDays($this->fecha_seguimiento);
    }
}
