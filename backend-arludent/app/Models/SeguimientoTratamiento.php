<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo SeguimientoTratamiento
 *
 * Representa un seguimiento o control de un tratamiento en curso
 *
 * @property int $id_seguimiento
 * @property int $id_historial
 * @property int $id_tratamiento_historial
 * @property \Illuminate\Support\Carbon $fecha_registro
 * @property string|null $descripcion
 * @property int|null $duracion_restante
 * @property int|null $registrado_por
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\HistorialClinico|null $historial
 * @property-read \App\Models\TratamientoHistorial|null $tratamientoHistorial
 * @property-read \App\Models\Medico|null $registradoPor
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeguimientoTratamiento newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeguimientoTratamiento newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SeguimientoTratamiento query()
 * @mixin \Eloquent
 */
class SeguimientoTratamiento extends Model
{
    use HasFactory;

    protected $table = 'seguimiento_tratamiento';
    protected $primaryKey = 'id_seguimiento';
    public $timestamps = false;

    protected $fillable = [
        'id_historial',
        'id_tratamiento_historial',
        'fecha_registro',
        'descripcion',
        'duracion_restante',
        'registrado_por',
    ];

    /**
     * Atributos que deben ser casteados
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fecha_registro' => 'datetime',
        'duracion_restante' => 'integer',
    ];

    /**
     * Relación: Historial Clínico
     */
    public function historial(): BelongsTo
    {
        return $this->belongsTo(HistorialClinico::class, 'id_historial', 'id_historial');
    }

    /**
     * Relación: Tratamiento del Historial
     */
    public function tratamientoHistorial(): BelongsTo
    {
        return $this->belongsTo(TratamientoHistorial::class, 'id_tratamiento_historial', 'id');
    }

    /**
     * Relación: Médico que registró el seguimiento
     */
    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(Medico::class, 'registrado_por', 'id_medico');
    }
}
