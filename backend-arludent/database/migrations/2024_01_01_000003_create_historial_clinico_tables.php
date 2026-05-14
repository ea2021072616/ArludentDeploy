<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: Historial Clínico y Componentes
 *
 * Crea las tablas para gestionar historiales clínicos, tratamientos,
 * odontogramas, presupuestos y consentimientos
 */
return new class extends Migration
{
    /**
     * Ejecuta la migración
     */
    public function up(): void
    {
        // Tabla: historial_clinico (MODIFICADA - incluye anamnesis)
        Schema::create('historial_clinico', function (Blueprint $table) {
            $table->id('id_historial');
            $table->unsignedBigInteger('id_paciente');
            $table->unsignedBigInteger('id_medico_responsable')->nullable();
            $table->string('codigo_historial', 50)->nullable()->unique();

            // Motivo de consulta y diagnósticos
            $table->text('motivo_consulta')->nullable();
            $table->text('diagnostico_presuntivo')->nullable();
            $table->text('diagnostico_principal')->nullable();

            // Información clínica general
            $table->enum('higiene_bucal', ['Bueno', 'Regular', 'Malo'])->nullable();

            // CAMPOS DE ANAMNESIS INCORPORADOS
            $table->text('sintoma_principal')->nullable();
            $table->string('tiempo_inicio_sintomas', 100)->nullable();
            $table->text('tratamiento_previo')->nullable();
            $table->text('enfermedades_actuales')->nullable();
            $table->boolean('bajo_tratamiento_medico')->nullable()->default(false);
            $table->text('detalle_tratamiento_actual')->nullable();
            $table->text('alergias_paciente')->nullable();
            $table->boolean('intervenciones_quirurgicas_previas')->nullable()->default(false);
            $table->text('detalle_intervenciones')->nullable();
            $table->boolean('hemorragia_post_tratamiento')->nullable()->default(false);
            $table->boolean('problema_anestesia')->nullable()->default(false);
            $table->boolean('dificultad_abrir_masticar')->nullable()->default(false);
            $table->boolean('sensibilidad_dental')->nullable()->default(false);

            // Relaciones y metadatos
            $table->unsignedBigInteger('consentimiento_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('id_paciente')
                  ->references('id_paciente')
                  ->on('pacientes')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');

            $table->foreign('id_medico_responsable')
                  ->references('id_medico')
                  ->on('medicos')
                  ->onDelete('set null')
                  ->onUpdate('cascade');

            $table->foreign('created_by')
                  ->references('id_usuario')
                  ->on('usuarios')
                  ->onDelete('set null')
                  ->onUpdate('cascade');

            $table->index('id_paciente');
            // El índice 'estado' se elimina ya que el campo no existe en la nueva estructura
        });

        // Tabla: consentimientos
        Schema::create('consentimientos', function (Blueprint $table) {
            $table->id('id_consentimiento');
            $table->unsignedBigInteger('id_historial')->nullable();
            $table->text('texto_plantilla')->nullable();
            $table->string('firma_paciente_url', 255)->nullable();
            $table->string('firma_medico_url', 255)->nullable();
            $table->enum('metodo', ['presencial', 'firma_digital', 'pdf_subido'])->nullable();
            $table->datetime('fecha_firma')->nullable();
            $table->string('ip_origen', 50)->nullable();
            $table->timestamps();

            $table->foreign('id_historial')
                  ->references('id_historial')
                  ->on('historial_clinico')
                  ->onDelete('set null')
                  ->onUpdate('cascade');
        });

        // Añadir la clave foránea de historial_clinico a consentimientos para resolver dependencia circular
        Schema::table('historial_clinico', function (Blueprint $table) {
            $table->foreign('consentimiento_id')
                  ->references('id_consentimiento')
                  ->on('consentimientos')
                  ->onDelete('set null')
                  ->onUpdate('cascade');
        });

        // Tabla: detalle_historial
        Schema::create('detalle_historial', function (Blueprint $table) {
            $table->id('id_detalle');
            $table->unsignedBigInteger('id_historial');
            $table->unsignedBigInteger('id_cita')->nullable();
            $table->datetime('fecha')->useCurrent();
            $table->text('diagnostico')->nullable();
            $table->text('procedimiento_realizado')->nullable();
            $table->text('notas_medicas')->nullable();
            $table->unsignedBigInteger('realizado_por')->nullable();
            $table->timestamps();

            $table->foreign('id_historial')
                  ->references('id_historial')
                  ->on('historial_clinico')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');

            $table->foreign('id_cita')
                  ->references('id_cita')
                  ->on('citas')
                  ->onDelete('set null')
                  ->onUpdate('cascade');

            $table->foreign('realizado_por')
                  ->references('id_medico')
                  ->on('medicos')
                  ->onDelete('set null')
                  ->onUpdate('cascade');

            $table->index(['id_historial', 'fecha']);
        });

        // Tabla: odontograma
        Schema::create('odontograma', function (Blueprint $table) {
            $table->id('id_odontograma');
            $table->unsignedBigInteger('id_historial');
            $table->string('pieza', 10);
            $table->enum('estado_pieza', ['sano', 'cariado', 'restaurado', 'ausente', 'protesis', 'otros']);
            $table->string('tratamiento_asociado', 255)->nullable();
            $table->text('comentario')->nullable();
            $table->datetime('fecha_registro')->useCurrent();

            $table->foreign('id_historial')
                  ->references('id_historial')
                  ->on('historial_clinico')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');

            $table->index('id_historial');
            $table->index('pieza');
        });

        // Tabla: tratamientos (catálogo)
        Schema::create('tratamientos', function (Blueprint $table) {
            $table->id('id_tratamiento');
            $table->string('categoria', 50)->nullable();
            $table->string('nombre', 100);
            $table->text('descripcion')->nullable();
            $table->decimal('precio_actual', 10, 2)->nullable();
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            $table->timestamps();

            $table->index('nombre');
        });

        // Tabla: tratamientos_historial
        Schema::create('tratamientos_historial', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_historial');
            $table->unsignedBigInteger('id_detalle_historial')->nullable();
            $table->unsignedBigInteger('id_tratamiento');
            $table->text('descripcion')->nullable();
            $table->datetime('fecha_inicio')->nullable();
            $table->datetime('fecha_fin')->nullable();
            $table->enum('estado', ['sugerido', 'en_curso', 'completado', 'cancelado'])->default('sugerido');
            $table->decimal('precio', 10, 2)->nullable();
            $table->unsignedBigInteger('realizado_por')->nullable();
            $table->timestamps();

            $table->foreign('id_historial')
                  ->references('id_historial')
                  ->on('historial_clinico')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');

            $table->foreign('id_detalle_historial')
                  ->references('id_detalle')
                  ->on('detalle_historial')
                  ->onDelete('set null')
                  ->onUpdate('cascade');

            $table->foreign('id_tratamiento')
                  ->references('id_tratamiento')
                  ->on('tratamientos')
                  ->onDelete('restrict')
                  ->onUpdate('cascade');

            $table->foreign('realizado_por')
                  ->references('id_medico')
                  ->on('medicos')
                  ->onDelete('set null')
                  ->onUpdate('cascade');

            $table->index('id_historial');
        });

        // Tabla: seguimiento_tratamiento
        Schema::create('seguimiento_tratamiento', function (Blueprint $table) {
            $table->id('id_seguimiento');
            $table->unsignedBigInteger('id_historial');
            $table->unsignedBigInteger('id_tratamiento_historial');
            $table->datetime('fecha_registro')->useCurrent();
            $table->text('descripcion')->nullable();
            $table->integer('duracion_restante')->nullable();
            $table->unsignedBigInteger('registrado_por')->nullable();

            $table->foreign('id_historial')
                  ->references('id_historial')
                  ->on('historial_clinico')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');

            $table->foreign('id_tratamiento_historial')
                  ->references('id')
                  ->on('tratamientos_historial')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');

            $table->foreign('registrado_por')
                  ->references('id_medico')
                  ->on('medicos')
                  ->onDelete('set null')
                  ->onUpdate('cascade');

            $table->index('id_historial');
        });

        // Tabla: presupuestos
        Schema::create('presupuestos', function (Blueprint $table) {
            $table->id('id_presupuesto');
            $table->unsignedBigInteger('id_historial');
            $table->string('numero_presupuesto', 20)->nullable()->unique();
            $table->text('texto_resumen')->nullable();
            $table->decimal('total_estimado', 10, 2)->nullable();
            $table->enum('estado', ['pendiente', 'aprobado', 'rechazado'])->default('pendiente');
            $table->datetime('fecha_emision')->useCurrent();
            $table->datetime('fecha_aceptacion')->nullable();
            $table->string('firma_paciente_url', 255)->nullable();
            $table->string('firma_medico_url', 255)->nullable();

            $table->foreign('id_historial')
                  ->references('id_historial')
                  ->on('historial_clinico')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');

            $table->index('id_historial');
        });

        // Tabla: presupuesto_items
        Schema::create('presupuesto_items', function (Blueprint $table) {
            $table->id('id_item');
            $table->unsignedBigInteger('id_presupuesto');
            $table->unsignedBigInteger('id_tratamiento')->nullable();
            $table->text('descripcion')->nullable();
            $table->integer('cantidad')->default(1);
            $table->decimal('precio_unit', 10, 2)->default(0.00);
            $table->decimal('subtotal', 10, 2)->default(0.00);

            $table->foreign('id_presupuesto')
                  ->references('id_presupuesto')
                  ->on('presupuestos')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');

            $table->foreign('id_tratamiento')
                  ->references('id_tratamiento')
                  ->on('tratamientos')
                  ->onDelete('set null')
                  ->onUpdate('cascade');

            $table->index('id_presupuesto');
        });

        // Tabla: prescripciones
        Schema::create('prescripciones', function (Blueprint $table) {
            $table->id('id_prescripcion');
            $table->unsignedBigInteger('id_historial');
            $table->unsignedBigInteger('id_detalle_historial')->nullable();
            $table->string('medicamento', 100);
            $table->string('dosis', 100)->nullable();
            $table->string('frecuencia', 100)->nullable();
            $table->string('duracion', 100)->nullable();
            $table->text('indicaciones')->nullable();
            $table->unsignedBigInteger('prescrito_por')->nullable();
            $table->datetime('fecha_prescripcion')->useCurrent();

            $table->foreign('id_historial')
                  ->references('id_historial')
                  ->on('historial_clinico')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');

            $table->foreign('id_detalle_historial')
                  ->references('id_detalle')
                  ->on('detalle_historial')
                  ->onDelete('set null')
                  ->onUpdate('cascade');

            $table->foreign('prescrito_por')
                  ->references('id_medico')
                  ->on('medicos')
                  ->onDelete('set null')
                  ->onUpdate('cascade');

            $table->index('id_historial');
        });

        // Tabla: documentos_clinicos
        Schema::create('documentos_clinicos', function (Blueprint $table) {
            $table->id('id_documento');
            $table->unsignedBigInteger('id_historial')->nullable();
            $table->unsignedBigInteger('id_cita')->nullable();
            $table->string('tipo_documento', 50)->nullable();
            $table->string('nombre_archivo', 255)->nullable();
            $table->string('url_archivo', 255);
            $table->unsignedBigInteger('subido_por')->nullable();
            $table->datetime('fecha_subida')->useCurrent();

            $table->foreign('id_historial')
                  ->references('id_historial')
                  ->on('historial_clinico')
                  ->onDelete('set null')
                  ->onUpdate('cascade');

            $table->foreign('id_cita')
                  ->references('id_cita')
                  ->on('citas')
                  ->onDelete('set null')
                  ->onUpdate('cascade');

            $table->foreign('subido_por')
                  ->references('id_usuario')
                  ->on('usuarios')
                  ->onDelete('set null')
                  ->onUpdate('cascade');

            $table->index('id_historial');
            $table->index('id_cita');
        });
    }

    /**
     * Revierte la migración
     */
    public function down(): void
    {
        Schema::dropIfExists('documentos_clinicos');
        Schema::dropIfExists('prescripciones');
        Schema::dropIfExists('presupuesto_items');
        Schema::dropIfExists('presupuestos');
        Schema::dropIfExists('seguimiento_tratamiento');
        Schema::dropIfExists('tratamientos_historial');
        Schema::dropIfExists('tratamientos');
        Schema::dropIfExists('odontograma');

        Schema::table('historial_clinico', function (Blueprint $table) {
            if (Schema::hasColumn('historial_clinico', 'consentimiento_id')) {
                $table->dropForeign(['consentimiento_id']);
            }
        });

        Schema::dropIfExists('consentimientos');
        Schema::dropIfExists('detalle_historial');
        Schema::dropIfExists('historial_clinico');
    }
};
