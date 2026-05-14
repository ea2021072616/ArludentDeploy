<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prescripcion extends Model
{
    use HasFactory;

    protected $table = 'prescripciones';
    protected $primaryKey = 'id_prescripcion';
    public $timestamps = false;

    protected $fillable = [
        'id_historial',
        'id_detalle_historial',
        'medicamento',
        'dosis',
        'frecuencia',
        'duracion',
        'indicaciones',
        'prescrito_por',
        'fecha_prescripcion',
    ];

    protected $casts = [
        'fecha_prescripcion' => 'datetime',
    ];

    /**
     * Relación con historial clínico
     */
    public function historial(): BelongsTo
    {
        return $this->belongsTo(HistorialClinico::class, 'id_historial', 'id_historial');
    }

    /**
     * Relación con detalle de historial
     */
    public function detalleHistorial(): BelongsTo
    {
        return $this->belongsTo(DetalleHistorial::class, 'id_detalle_historial', 'id_detalle');
    }

    /**
     * Relación con médico que prescribió
     */
    public function medico(): BelongsTo
    {
        return $this->belongsTo(Medico::class, 'prescrito_por', 'id_medico');
    }

    /**
     * Alias para la relación con médico (para el frontend)
     */
    public function prescritoPor(): BelongsTo
    {
        return $this->belongsTo(Medico::class, 'prescrito_por', 'id_medico');
    }
}
