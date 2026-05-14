<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo DetalleHistorial
 *
 * Registra cada entrada o consulta dentro de un historial clínico
 *
 * @property-read \App\Models\Cita|null $cita
 * @property-read \App\Models\HistorialClinico|null $historial
 * @property-read \App\Models\Medico|null $realizadoPor
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DetalleHistorial newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DetalleHistorial newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DetalleHistorial query()
 * @mixin \Eloquent
 */
class DetalleHistorial extends Model
{
    use HasFactory;

    protected $table = 'detalle_historial';
    protected $primaryKey = 'id_detalle';

    protected $fillable = [
        'id_historial',
        'id_cita',
        'fecha',
        'diagnostico',
        'procedimiento_realizado',
        'notas_medicas',
        'realizado_por',
    ];

        /**
         * Atributos que deben ser casteados
         *
         * @var array<string, string>
         */
        protected $casts = [
            'fecha' => 'datetime',
        ];

    public function historial(): BelongsTo
    {
        return $this->belongsTo(HistorialClinico::class, 'id_historial', 'id_historial');
    }

    public function cita(): BelongsTo
    {
        return $this->belongsTo(Cita::class, 'id_cita', 'id_cita');
    }

    public function realizadoPor(): BelongsTo
    {
        return $this->belongsTo(Medico::class, 'realizado_por', 'id_medico');
    }
}
