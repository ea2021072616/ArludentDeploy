<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo Tratamiento (Catálogo)
 * 
 * Define los tratamientos disponibles en el consultorio
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tratamiento newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tratamiento newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tratamiento query()
 * @mixin \Eloquent
 */
class Tratamiento extends Model
{
    use HasFactory;

    protected $table = 'tratamientos';
    protected $primaryKey = 'id_tratamiento';

    protected $fillable = [
        'categoria',
        'nombre',
        'descripcion',
        'precio_actual',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'precio_actual' => 'decimal:2',
        ];
    }
}
