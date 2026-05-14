<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo Paciente
 *
 * Representa los datos clínicos y personales de un paciente
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Cita> $citas
 * @property-read int|null $citas_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\HistorialClinico> $historialesClinico
 * @property-read int|null $historiales_clinico_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Pago> $pagos
 * @property-read int|null $pagos_count
 * @property-read \App\Models\User|null $usuario
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Paciente newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Paciente newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Paciente query()
 * @mixin \Eloquent
 */
class Paciente extends Model
{
    use HasFactory;

    protected $table = 'pacientes';
    protected $primaryKey = 'id_paciente';

    protected $fillable = [
        'id_usuario',
        'apellidos',
        'nombres',
        'dni',
        'fecha_nacimiento',
        'sexo',
        'domicilio',
        'persona_responsable',
        'telefono_responsable',
        'grupo_sanguineo',
        'alergias',
        'estado',
    ];

    /**
     * Atributos que deben ser casteados
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fecha_nacimiento' => 'date',
        'fecha_registro' => 'datetime',
    ];

    /**
     * Atributos que se deben agregar al array/JSON
     *
     * @var array
     */
    protected $appends = ['telefono', 'correo'];

    /**
     * Usuario asociado
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id_usuario');
    }

    /**
     * Citas del paciente
     */
    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class, 'id_paciente', 'id_paciente');
    }

    /**
     * Historiales clínicos
     */
    public function historialesClinico(): HasMany
    {
        return $this->hasMany(HistorialClinico::class, 'id_paciente', 'id_paciente');
    }

    /**
     * Pagos realizados
     */
    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'id_paciente', 'id_paciente');
    }

    /**
     * Accessor para obtener el teléfono principal del usuario
     */
    public function getTelefonoAttribute()
    {
        return $this->usuario ? $this->usuario->telefono : null;
    }

    /**
     * Accessor para obtener el correo del usuario
     */
    public function getCorreoAttribute()
    {
        return $this->usuario ? $this->usuario->correo : null;
    }
}
