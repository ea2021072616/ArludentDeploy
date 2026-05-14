<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Modelo Rol
 * 
 * Define los roles disponibles en el sistema (paciente, médico, admin, externo)
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $usuarios
 * @property-read int|null $usuarios_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rol newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rol newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Rol query()
 * @mixin \Eloquent
 */
class Rol extends Model
{
    use HasFactory;

    protected $table = 'roles';
    protected $primaryKey = 'id_rol';

    protected $fillable = [
        'nombre_rol',
        'descripcion',
    ];

    public $timestamps = false;

    /**
     * Usuarios que tienen este rol
     */
    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'roles_usuarios',
            'id_rol',
            'id_usuario'
        )->withPivot('fecha_asignacion', 'asignado_por')
          ->withTimestamps();
    }
}
