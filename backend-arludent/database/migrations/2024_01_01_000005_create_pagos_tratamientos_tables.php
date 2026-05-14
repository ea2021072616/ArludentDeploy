<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: Pagos y Tratamientos
 *
 * Crea las tablas faltantes para el sistema de pagos y tratamientos
 */
return new class extends Migration
{
    /**
     * Ejecuta la migración
     */
    public function up(): void
    {
        // Tabla: pagos (con campos para comprobantes electrónicos SUNAT)
        Schema::create('pagos', function (Blueprint $table) {
            $table->id('id_pago');
            $table->unsignedBigInteger('id_paciente');
            $table->unsignedBigInteger('id_cita')->nullable();
            $table->string('concepto', 200);
            $table->decimal('monto', 10, 2);
            $table->enum('metodo_pago', ['efectivo', 'tarjeta', 'transferencia', 'cheque', 'yape', 'plin', 'otros'])->default('efectivo');
            $table->enum('estado_pago', ['pendiente', 'pagado', 'cancelado', 'reembolsado'])->default('pendiente');
            $table->date('fecha_pago')->nullable();
            $table->text('notas')->nullable();
            $table->unsignedBigInteger('registrado_por')->nullable();

            // Campos para comprobantes electrónicos (Boletas/Facturas SUNAT)
            $table->enum('tipo_comprobante', ['boleta', 'factura', 'nota_credito', 'nota_debito', 'ninguno'])->default('ninguno');
            $table->string('serie_comprobante', 20)->nullable(); // Ej: B001, F001
            $table->string('numero_comprobante', 20)->nullable(); // Ej: 00000123
            $table->string('ruc_emisor', 11)->nullable(); // RUC de la clínica
            $table->string('razon_social_emisor')->nullable(); // Nombre de la clínica

            // Datos del cliente (para factura necesita RUC, para boleta DNI)
            $table->string('tipo_documento_cliente', 20)->nullable(); // DNI, RUC, CE
            $table->string('numero_documento_cliente', 20)->nullable();
            $table->string('nombre_cliente')->nullable();
            $table->string('direccion_cliente')->nullable();

            // Montos con IGV
            $table->decimal('subtotal', 10, 2)->nullable(); // Monto sin IGV
            $table->decimal('igv', 10, 2)->nullable(); // 18% del subtotal
            $table->decimal('total', 10, 2)->nullable(); // subtotal + igv (debe coincidir con 'monto')

            // Respuesta de API SUNAT
            $table->enum('estado_sunat', ['pendiente', 'aceptado', 'rechazado', 'anulado'])->default('pendiente');
            $table->text('respuesta_sunat')->nullable(); // JSON con respuesta completa
            $table->string('hash_comprobante')->nullable(); // Hash del XML
            $table->string('codigo_qr')->nullable(); // Para QR del comprobante
            $table->text('enlace_pdf')->nullable(); // URL del PDF generado
            $table->text('enlace_xml')->nullable(); // URL del XML generado
            $table->timestamp('fecha_emision_comprobante')->nullable();

            $table->timestamps();

            $table->foreign('id_paciente')
                  ->references('id_paciente')
                  ->on('pacientes')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');

            $table->foreign('id_cita')
                  ->references('id_cita')
                  ->on('citas')
                  ->onDelete('set null')
                  ->onUpdate('cascade');

            $table->foreign('registrado_por')
                  ->references('id_usuario')
                  ->on('usuarios')
                  ->onDelete('set null')
                  ->onUpdate('cascade');

            $table->index(['estado_pago', 'fecha_pago']);
            $table->index('id_paciente');
        });
    }

    /**
     * Revierte la migración
     */
    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
