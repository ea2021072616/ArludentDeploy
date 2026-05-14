<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo DisponibilidadMedico
 *
 * Gestiona los horarios y bloqueos de disponibilidad de los médicos
 *
 * @property-read \App\Models\Medico|null $medico
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DisponibilidadMedico newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DisponibilidadMedico newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DisponibilidadMedico query()
 * @mixin \Eloquent
 */
class DisponibilidadMedico extends Model
{
    use HasFactory;

    protected $table = 'disponibilidad_medico';
    protected $primaryKey = 'id_disp';

    protected $fillable = [
        'id_medico',
        'tipo',
        'dia_semana',
        'fecha_inicio',
        'fecha_fin',
        'hora_inicio',
        'hora_fin',
        'motivo',
    ];

        /**
         * Atributos que deben ser casteados
         *
         * @var array<string, string>
         */
        protected $casts = [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'dia_semana' => 'integer',
        ];

    public function medico(): BelongsTo
    {
        return $this->belongsTo(Medico::class, 'id_medico', 'id_medico');
    }
}
