<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pago;
use App\Models\Paciente;
use App\Models\Cita;
use App\Models\User;
use Carbon\Carbon;

/**
 * Seeder de Pagos - DEMOSTRACIÓN
 *
 * Crea pagos completos con comprobantes SUNAT para la demostración
 */
class PagosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🔧 CREANDO PAGOS Y COMPROBANTES DEMO...');

        // Paciente demo
        $paciente = Paciente::whereHas('usuario', function ($q) {
            $q->where('correo', 'paciente@arludent.com');
        })->first();

        if (!$paciente) {
            $this->command->warn('⚠️  No se encontró el paciente demo. Salteando seed de pagos.');
            return;
        }

        // Obtener usuario registrador
        $adminUser = User::where('correo', 'admin@arludent.com')->first();
        $registradoPor = $adminUser ? $adminUser->id_usuario : null;

        // Limpiar pagos previos
        Pago::where('id_paciente', $paciente->id_paciente)->delete();

        $now = Carbon::now();

        // ========== PAGOS COMPLETADOS CON COMPROBANTES ==========
        $pagosCompletados = [
            // Pago 1: Primera consulta (Boleta)
            [
                'concepto' => 'Consulta inicial y diagnóstico completo',
                'monto' => 120.00,
                'metodo' => 'efectivo',
                'fecha' => $now->copy()->subMonths(6),
                'tipo_comprobante' => 'boleta',
                'serie' => 'B001',
                'numero' => '00000234',
            ],
            // Pago 2: Limpieza dental (Factura)
            [
                'concepto' => 'Limpieza dental completa y destartraje',
                'monto' => 200.00,
                'metodo' => 'tarjeta',
                'fecha' => $now->copy()->subMonths(5),
                'tipo_comprobante' => 'factura',
                'serie' => 'F001',
                'numero' => '00000089',
            ],
            // Pago 3: Restauración pieza 16 (Boleta)
            [
                'concepto' => 'Restauración con resina - Pieza 16',
                'monto' => 150.00,
                'metodo' => 'efectivo',
                'fecha' => $now->copy()->subMonths(4),
                'tipo_comprobante' => 'boleta',
                'serie' => 'B001',
                'numero' => '00000235',
            ],
            // Pago 4: Restauraciones piezas 14 y 26 (Boleta)
            [
                'concepto' => 'Restauraciones con resina - Piezas 14 y 26',
                'monto' => 300.00,
                'metodo' => 'yape',
                'fecha' => $now->copy()->subMonths(3),
                'tipo_comprobante' => 'boleta',
                'serie' => 'B001',
                'numero' => '00000236',
            ],
            // Pago 5: Evaluación de ortodoncia (Boleta)
            [
                'concepto' => 'Evaluación ortodóntica completa',
                'monto' => 150.00,
                'metodo' => 'transferencia',
                'fecha' => $now->copy()->subMonths(3),
                'tipo_comprobante' => 'boleta',
                'serie' => 'B001',
                'numero' => '00000237',
            ],
            // Pago 6: Inicial de ortodoncia (Factura)
            [
                'concepto' => 'Tratamiento de ortodoncia - Pago inicial',
                'monto' => 1500.00,
                'metodo' => 'transferencia',
                'fecha' => $now->copy()->subMonths(3)->addDays(7),
                'tipo_comprobante' => 'factura',
                'serie' => 'F001',
                'numero' => '00000090',
            ],
            // Pago 7: Cuota ortodoncia mes 1 (Boleta)
            [
                'concepto' => 'Tratamiento de ortodoncia - Cuota mes 1',
                'monto' => 200.00,
                'metodo' => 'yape',
                'fecha' => $now->copy()->subMonths(2),
                'tipo_comprobante' => 'boleta',
                'serie' => 'B001',
                'numero' => '00000238',
            ],
            // Pago 8: Cuota ortodoncia mes 2 (Boleta)
            [
                'concepto' => 'Tratamiento de ortodoncia - Cuota mes 2',
                'monto' => 200.00,
                'metodo' => 'tarjeta',
                'fecha' => $now->copy()->subMonths(1),
                'tipo_comprobante' => 'boleta',
                'serie' => 'B001',
                'numero' => '00000239',
            ],
        ];

        foreach ($pagosCompletados as $pagoData) {
            $this->crearPago(
                $paciente->id_paciente,
                $pagoData,
                $registradoPor,
                'pagado',
                'aceptado'
            );
        }

        $this->command->info('✓ 8 pagos completados creados (con comprobantes SUNAT)');

        // ========== PAGOS PENDIENTES ==========
        $pagosPendientes = [
            // Pago 9: Cuota ortodoncia mes 3 (Pendiente - para hoy)
            [
                'concepto' => 'Tratamiento de ortodoncia - Cuota mes 3',
                'monto' => 200.00,
                'metodo' => 'efectivo',
                'fecha' => $now->copy(),
                'tipo_comprobante' => 'ninguno',
                'serie' => null,
                'numero' => null,
            ],
            // Pago 10: Restauraciones pendientes (Pendiente - futuro)
            [
                'concepto' => 'Restauraciones con resina - Piezas 36 y 46',
                'monto' => 300.00,
                'metodo' => 'efectivo',
                'fecha' => $now->copy()->addDays(7),
                'tipo_comprobante' => 'ninguno',
                'serie' => null,
                'numero' => null,
            ],
        ];

        foreach ($pagosPendientes as $pagoData) {
            $this->crearPago(
                $paciente->id_paciente,
                $pagoData,
                $registradoPor,
                'pendiente',
                'pendiente'
            );
        }

        $this->command->info('✓ 2 pagos pendientes creados (sin comprobante aún)');

        // ========== RESUMEN ==========
        $this->command->info('');
        $this->command->info('═══════════════════════════════════════════════════════════');
        $this->command->info('✅ TOTAL: 10 PAGOS CREADOS PARA DEMO');
        $this->command->info('═══════════════════════════════════════════════════════════');
        $this->command->info('  • 8 Pagos completados (con comprobantes SUNAT)');
        $this->command->info('    └─ 5 Boletas electrónicas');
        $this->command->info('    └─ 2 Facturas electrónicas');
        $this->command->info('    └─ 1 Sin comprobante');
        $this->command->info('  • 2 Pagos pendientes');
        $this->command->info('');
        $this->command->info('💰 RESUMEN FINANCIERO:');
        $this->command->info('  • Total pagado: S/. 2,820.00');
        $this->command->info('  • Total pendiente: S/. 500.00');
        $this->command->info('  • Total general: S/. 3,320.00');
        $this->command->info('═══════════════════════════════════════════════════════════');
        $this->command->info('');
    }

    /**
     * Crea un pago con sus datos de comprobante
     */
    private function crearPago(
        int $idPaciente,
        array $data,
        ?int $registradoPor,
        string $estadoPago,
        string $estadoSunat
    ): void {
        $subtotal = null;
        $igv = null;
        $total = null;

        if ($data['tipo_comprobante'] !== 'ninguno') {
            $subtotal = round($data['monto'] / 1.18, 2);
            $igv = round($data['monto'] - $subtotal, 2);
            $total = $data['monto'];
        }

        Pago::create([
            'id_paciente' => $idPaciente,
            'id_cita' => null,
            'concepto' => $data['concepto'],
            'monto' => $data['monto'],
            'metodo_pago' => $data['metodo'],
            'estado_pago' => $estadoPago,
            'fecha_pago' => $data['fecha'],
            'notas' => $estadoPago === 'pagado' ? 'Pago recibido correctamente' : 'Pendiente de pago',
            'registrado_por' => $registradoPor,

            // Comprobante electrónico
            'tipo_comprobante' => $data['tipo_comprobante'],
            'serie_comprobante' => $data['serie'],
            'numero_comprobante' => $data['numero'],
            'ruc_emisor' => $data['tipo_comprobante'] !== 'ninguno' ? '20123456789' : null,
            'razon_social_emisor' => $data['tipo_comprobante'] !== 'ninguno' ? 'CLINICA DENTAL ARLUDENT S.A.C.' : null,

            // Datos del cliente
            'tipo_documento_cliente' => $data['tipo_comprobante'] === 'factura' ? 'RUC' : ($data['tipo_comprobante'] === 'boleta' ? 'DNI' : null),
            'numero_documento_cliente' => $data['tipo_comprobante'] === 'factura' ? '20987654321' : ($data['tipo_comprobante'] === 'boleta' ? '72345678' : null),
            'nombre_cliente' => $data['tipo_comprobante'] === 'factura' ? 'EMPRESA DEMO S.A.C.' : ($data['tipo_comprobante'] === 'boleta' ? 'Juan Alberto Pérez Gonzales' : null),
            'direccion_cliente' => $data['tipo_comprobante'] !== 'ninguno' ? 'Av. Los Olivos 456, San Borja, Lima' : null,

            // Montos con IGV
            'subtotal' => $subtotal,
            'igv' => $igv,
            'total' => $total,

            // Respuesta SUNAT
            'estado_sunat' => $estadoSunat,
            'respuesta_sunat' => $estadoPago === 'pagado' && $data['tipo_comprobante'] !== 'ninguno'
                ? json_encode(['mensaje' => 'Comprobante aceptado por SUNAT', 'codigo' => '0'])
                : null,
            'hash_comprobante' => $estadoPago === 'pagado' && $data['tipo_comprobante'] !== 'ninguno'
                ? 'hash_' . $data['serie'] . '_' . $data['numero']
                : null,
            'codigo_qr' => $estadoPago === 'pagado' && $data['tipo_comprobante'] !== 'ninguno'
                ? 'https://apisunat.com/qr/' . $data['serie'] . '-' . $data['numero']
                : null,
            'enlace_pdf' => $estadoPago === 'pagado' && $data['tipo_comprobante'] !== 'ninguno'
                ? 'https://apisunat.com/pdf/' . $data['serie'] . '-' . $data['numero'] . '.pdf'
                : null,
            'enlace_xml' => $estadoPago === 'pagado' && $data['tipo_comprobante'] !== 'ninguno'
                ? 'https://apisunat.com/xml/' . $data['serie'] . '-' . $data['numero'] . '.xml'
                : null,
            'fecha_emision_comprobante' => $estadoPago === 'pagado' && $data['tipo_comprobante'] !== 'ninguno'
                ? $data['fecha']
                : null,
        ]);
    }
}
