<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Paciente;
use App\Models\Cita;

/**
 * Modelo Pago
 *
 * Gestiona los pagos realizados por pacientes y comprobantes electrónicos SUNAT
 *
 * @property-read \App\Models\Cita|null $cita
 * @property-read \App\Models\Paciente|null $paciente
 * @property-read \App\Models\Usuario|null $registradoPor
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pago newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pago newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pago query()
 * @mixin \Eloquent
 */
class Pago extends Model
{
    use HasFactory;

    protected $table = 'pagos';
    protected $primaryKey = 'id_pago';

    protected $fillable = [
        'id_paciente',
        'id_cita',
        'concepto',
        'monto',
        'metodo_pago',
        'estado_pago',
        'fecha_pago',
        'notas',
        'registrado_por',
        // Campos de comprobantes electrónicos SUNAT
        'tipo_comprobante',
        'serie_comprobante',
        'numero_comprobante',
        'ruc_emisor',
        'razon_social_emisor',
        'tipo_documento_cliente',
        'numero_documento_cliente',
        'nombre_cliente',
        'direccion_cliente',
        'subtotal',
        'igv',
        'total',
        'estado_sunat',
        'respuesta_sunat',
        'hash_comprobante',
        'codigo_qr',
        'enlace_pdf',
        'enlace_xml',
        'fecha_emision_comprobante',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'igv' => 'decimal:2',
            'total' => 'decimal:2',
            'fecha_pago' => 'date',
            'fecha_emision_comprobante' => 'datetime',
            'respuesta_sunat' => 'array',
        ];
    }

    /**
     * Relaciones
     */
    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'id_paciente', 'id_paciente');
    }

    public function cita(): BelongsTo
    {
        return $this->belongsTo(Cita::class, 'id_cita', 'id_cita');
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por', 'id_usuario');
    }

    /**
     * Scopes
     */
    public function scopePagados($query)
    {
        return $query->where('estado_pago', 'pagado');
    }

    public function scopePendientes($query)
    {
        return $query->where('estado_pago', 'pendiente');
    }

    public function scopeConComprobante($query)
    {
        return $query->whereIn('tipo_comprobante', ['boleta', 'factura']);
    }

    public function scopeHoy($query)
    {
        return $query->whereDate('fecha_pago', Carbon::today());
    }

    public function scopeEntreFechas($query, $desde, $hasta)
    {
        return $query->whereBetween('fecha_pago', [$desde, $hasta]);
    }

    /**
     * Métodos auxiliares
     */

    /**
     * Calcula el IGV (18%) sobre el monto total
     */
    public static function calcularIGV($monto)
    {
        $subtotal = round($monto / 1.18, 2);
        $igv = round($monto - $subtotal, 2);

        return [
            'subtotal' => $subtotal,
            'igv' => $igv,
            'total' => $monto
        ];
    }

    /**
     * Genera el siguiente número de comprobante
     */
    public static function siguienteNumero($tipo, $serie)
    {
        $ultimo = self::where('tipo_comprobante', $tipo)
            ->where('serie_comprobante', $serie)
            ->orderBy('numero_comprobante', 'desc')
            ->first();

        if (!$ultimo) {
            return '00000001';
        }

        $numero = intval($ultimo->numero_comprobante) + 1;
        return str_pad($numero, 8, '0', STR_PAD_LEFT);
    }

    /**
     * Obtiene el comprobante completo formateado
     */
    public function getComprobanteCompletoAttribute()
    {
        if ($this->tipo_comprobante === 'ninguno' || !$this->serie_comprobante) {
            return null;
        }

        return "{$this->serie_comprobante}-{$this->numero_comprobante}";
    }

    /**
     * Verifica si el comprobante fue aceptado por SUNAT
     */
    public function getAceptadoSunatAttribute()
    {
        return $this->estado_sunat === 'aceptado';
    }

    /**
     * Verifica si tiene comprobante electrónico
     */
    public function getTieneComprobanteAttribute()
    {
        return in_array($this->tipo_comprobante, ['boleta', 'factura', 'nota_credito', 'nota_debito'])
            && !empty($this->serie_comprobante)
            && !empty($this->numero_comprobante);
    }
}
