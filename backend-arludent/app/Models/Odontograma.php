<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo Odontograma
 * 
 * Representa el estado dental por pieza en un historial clínico
 *
 * @property-read \App\Models\HistorialClinico|null $historial
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Odontograma newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Odontograma newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Odontograma query()
 * @mixin \Eloquent
 */
class Odontograma extends Model
{
    use HasFactory;

    protected $table = 'odontograma';
    protected $primaryKey = 'id_odontograma';

    public $timestamps = false;

    protected $fillable = [
        'id_historial',
        'pieza',
        'estado_pieza',
        'tratamiento_asociado',
        'comentario',
    ];

    protected function casts(): array
    {
        return [
            'fecha_registro' => 'datetime',
        ];
    }

    public function historial(): BelongsTo
    {
        return $this->belongsTo(HistorialClinico::class, 'id_historial', 'id_historial');
    }
}
