<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo Calificacion
 *
 * Gestiona las calificaciones de los pacientes hacia médicos/citas
 *
 * @property-read \App\Models\Cita|null $cita
 * @property-read \App\Models\Medico|null $medico
 * @property-read \App\Models\Paciente|null $paciente
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Calificacion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Calificacion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Calificacion query()
 * @mixin \Eloquent
 */
class Calificacion extends Model
{
    use HasFactory;

    protected $table = 'calificaciones';
    protected $primaryKey = 'id_calificacion';

    public $timestamps = false;

    protected $fillable = [
        'id_cita',
        'id_paciente',
        'id_medico',
        'puntuacion',
        'comentario',
    ];

    /**
     * Atributos que deben ser casteados
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fecha' => 'datetime',
        'puntuacion' => 'integer',
    ];

    /**
     * Cita relacionada
     */
    public function cita(): BelongsTo
    {
        return $this->belongsTo(Cita::class, 'id_cita', 'id_cita');
    }

    /**
     * Paciente que calificó
     */
    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'id_paciente', 'id_paciente');
    }

    /**
     * Médico calificado
     */
    public function medico(): BelongsTo
    {
        return $this->belongsTo(Medico::class, 'id_medico', 'id_medico');
    }
}
