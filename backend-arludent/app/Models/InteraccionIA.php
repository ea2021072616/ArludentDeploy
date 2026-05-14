<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo InteraccionIA
 *
 * Representa las interacciones del usuario con el asistente de IA
 *
 * @property int $id_interaccion
 * @property int|null $id_usuario
 * @property string|null $tipo_intencion
 * @property string|null $entrada_usuario
 * @property string|null $respuesta_ia
 * @property string|null $estado_resultado
 * @property array|null $contexto
 * @property \Illuminate\Support\Carbon $fecha_interaccion
 * @property-read \App\Models\User|null $usuario
 */
class InteraccionIA extends Model
{
    use HasFactory;

    protected $table = 'interacciones_ia';
    protected $primaryKey = 'id_interaccion';
    public $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'tipo_intencion',
        'entrada_usuario',
        'respuesta_ia',
        'estado_resultado',
        'contexto',
    ];

    /**
     * Atributos que deben ser casteados
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fecha_interaccion' => 'datetime',
        'contexto' => 'array',
    ];

    /**
     * Usuario que realizó la interacción
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id_usuario');
    }

    /**
     * Tipos de intención válidos
     */
    public const TIPOS_INTENCION = [
        'agendar_cita',
        'consultar_cita',
        'cancelar_cita',
        'confirmar_cita',
        'consultar_disponibilidad',
        'consultar_historial',
        'consultar_paciente',
        'informacion_general',
        'otro',
    ];

    /**
     * Estados de resultado válidos
     */
    public const ESTADOS_RESULTADO = [
        'exitosa',
        'fallida',
        'requiere_revision',
    ];
}
