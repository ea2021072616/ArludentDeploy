<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Modelo Cita
 *
 * Representa una cita médica programada
 *
 * @property-read \App\Models\User|null $creadoPor
 * @property-read \App\Models\Medico|null $medico
 * @property-read \App\Models\Paciente|null $paciente
 * @property-read \App\Models\User|null $usuarioExterno
 * @property-read \App\Models\Calificacion|null $calificacion
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Cita query()
 * @mixin \Eloquent
 */
class Cita extends Model
{
    use HasFactory;

    protected $table = 'citas';
    protected $primaryKey = 'id_cita';

    protected $fillable = [
        'id_usuario_externo',
        'id_paciente',
        'id_medico',
        'fecha_hora_inicio',
        'fecha_hora_fin',
        'motivo',
        'estado',
        'creado_por',
        'notas',
    ];

    /**
     * Atributos que deben ser casteados
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fecha_hora_inicio' => 'datetime',
        'fecha_hora_fin' => 'datetime',
        'estado' => 'string',
    ];

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'id_paciente', 'id_paciente');
    }

    public function medico(): BelongsTo
    {
        return $this->belongsTo(Medico::class, 'id_medico', 'id_medico');
    }

    public function usuarioExterno(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario_externo', 'id_usuario');
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por', 'id_usuario');
    }

    /**
     * Calificación de la cita (si existe)
     */
    public function calificacion(): HasOne
    {
        return $this->hasOne(Calificacion::class, 'id_cita', 'id_cita');
    }
}
