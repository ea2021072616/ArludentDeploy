<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo Médico
 *
 * Representa los datos profesionales de un médico
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Cita> $citas
 * @property-read int|null $citas_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\DisponibilidadMedico> $disponibilidades
 * @property-read int|null $disponibilidades_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\HistorialClinico> $historialesClinicosResponsable
 * @property-read int|null $historiales_clinicos_responsable_count
 * @property-read \App\Models\User|null $usuario
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Medico newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Medico newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Medico query()
 * @mixin \Eloquent
 */
class Medico extends Model
{
    use HasFactory;

    protected $table = 'medicos';
    protected $primaryKey = 'id_medico';

    protected $fillable = [
        'id_usuario',
        'nombres',
        'apellidos',
        'nro_colegiado',
        'especialidad',
        'tipo_medico',
        'anios_experiencia',
        'foto_url',
        'estado_disponibilidad',
    ];

    /**
     * Usuario asociado
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id_usuario');
    }

    /**
     * Disponibilidad del médico
     */
    public function disponibilidades(): HasMany
    {
        return $this->hasMany(DisponibilidadMedico::class, 'id_medico', 'id_medico');
    }

    /**
     * Citas del médico
     */
    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class, 'id_medico', 'id_medico');
    }

    /**
     * Historiales clínicos como médico responsable
     */
    public function historialesClinicosResponsable(): HasMany
    {
        return $this->hasMany(HistorialClinico::class, 'id_medico_responsable', 'id_medico');
    }
}
