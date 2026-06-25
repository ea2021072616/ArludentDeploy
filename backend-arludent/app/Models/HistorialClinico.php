<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo HistorialClinico
 *
 * Gestiona el historial clínico completo de un paciente
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\DetalleHistorial> $detalles
 * @property-read int|null $detalles_count
 * @property-read \App\Models\Medico|null $medicoResponsable
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Odontograma> $odontograma
 * @property-read int|null $odontograma_count
 * @property-read \App\Models\Paciente|null $paciente
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TratamientoHistorial> $tratamientos
 * @property-read int|null $tratamientos_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialClinico newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialClinico newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HistorialClinico query()
 * @mixin \Eloquent
 */
class HistorialClinico extends Model
{
    use HasFactory;

    protected $table = 'historial_clinico';
    protected $primaryKey = 'id_historial';

    protected $fillable = [
        'id_paciente',
        'id_medico_responsable',
        'codigo_historial',
        'motivo_consulta',
        'diagnostico_presuntivo',
        'diagnostico_principal',
        'higiene_bucal',
        'consentimiento_id',
        'created_by',
        // Campos de anamnesis
        'sintoma_principal',
        'tiempo_inicio_sintomas',
        'tratamiento_previo',
        'enfermedades_actuales',
        'bajo_tratamiento_medico',
        'detalle_tratamiento_actual',
        'alergias_paciente',
        'intervenciones_quirurgicas_previas',
        'detalle_intervenciones',
        'hemorragia_post_tratamiento',
        'problema_anestesia',
        'dificultad_abrir_masticar',
        'sensibilidad_dental',
        'odontograma_state',
        'odontograma_image',
    ];

    /**
     * Atributos que deben ser casteados
     *
     * @var array<string, string>
     */
    protected $casts = [
        'bajo_tratamiento_medico' => 'boolean',
        'intervenciones_quirurgicas_previas' => 'boolean',
        'hemorragia_post_tratamiento' => 'boolean',
        'problema_anestesia' => 'boolean',
        'dificultad_abrir_masticar' => 'boolean',
        'sensibilidad_dental' => 'boolean',
        'odontograma_state' => 'array',
    ];

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'id_paciente', 'id_paciente');
    }

    public function medicoResponsable(): BelongsTo
    {
        return $this->belongsTo(Medico::class, 'id_medico_responsable', 'id_medico');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleHistorial::class, 'id_historial', 'id_historial');
    }

    public function tratamientos(): HasMany
    {
        return $this->hasMany(TratamientoHistorial::class, 'id_historial', 'id_historial');
    }

    public function odontograma(): HasMany
    {
        return $this->hasMany(Odontograma::class, 'id_historial', 'id_historial');
    }

    public function prescripciones(): HasMany
    {
        return $this->hasMany(Prescripcion::class, 'id_historial', 'id_historial');
    }
}
