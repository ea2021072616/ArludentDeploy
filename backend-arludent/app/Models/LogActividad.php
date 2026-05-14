<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo LogActividad
 * 
 * Registra actividades del sistema para auditoría
 *
 * @property-read \App\Models\User|null $usuario
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LogActividad newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LogActividad newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LogActividad query()
 * @mixin \Eloquent
 */
class LogActividad extends Model
{
    use HasFactory;

    protected $table = 'log_actividad';
    protected $primaryKey = 'id_log';

    public $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'accion',
        'modulo_afectado',
        'registro_afectado',
        'descripcion',
        'ip_usuario',
    ];

    protected function casts(): array
    {
        return [
            'fecha_hora' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id_usuario');
    }
}
