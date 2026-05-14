<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo TratamientoHistorial
 *
 * Representa un tratamiento aplicado o sugerido en un historial clínico
 *
 * @property-read \App\Models\HistorialClinico|null $historial
 * @property-read \App\Models\Medico|null $realizadoPor
 * @property-read \App\Models\Tratamiento|null $tratamiento
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TratamientoHistorial newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TratamientoHistorial newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TratamientoHistorial query()
 * @mixin \Eloquent
 */
class TratamientoHistorial extends Model
{
    use HasFactory;

    protected $table = 'tratamientos_historial';

    protected $fillable = [
        'id_historial',
        'id_detalle_historial',
        'id_tratamiento',
        'descripcion',
        'fecha_inicio',
        'fecha_fin',
        'estado',
        'precio',
        'realizado_por',
    ];

            /**
             * Atributos que deben ser casteados
             *
             * @var array<string, string>
             */
            protected $casts = [
                'fecha_inicio' => 'datetime',
                'fecha_fin' => 'datetime',
                'precio' => 'decimal:2',
            ];

    public function historial(): BelongsTo
    {
        return $this->belongsTo(HistorialClinico::class, 'id_historial', 'id_historial');
    }

    public function tratamiento(): BelongsTo
    {
        return $this->belongsTo(Tratamiento::class, 'id_tratamiento', 'id_tratamiento');
    }

    public function realizadoPor(): BelongsTo
    {
        return $this->belongsTo(Medico::class, 'realizado_por', 'id_medico');
    }

    /**
     * Relación: Seguimientos del tratamiento
     */
    public function seguimientos(): HasMany
    {
        return $this->hasMany(SeguimientoTratamiento::class, 'id_tratamiento_historial', 'id')
                    ->orderBy('fecha_registro', 'desc');
    }
}
