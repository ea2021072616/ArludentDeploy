<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migración: Sistema, IA y Auditoría
 *
 * Crea las tablas para logs de actividad, interacciones con IA,
 * notificaciones y calificaciones
 */
return new class extends Migration
{
    /**
     * Ejecuta la migración
     */
    public function up(): void
    {
        // Tabla: log_actividad
        Schema::create('log_actividad', function (Blueprint $table) {
            $table->id('id_log');
            $table->unsignedBigInteger('id_usuario')->nullable();
            $table->string('accion', 50);
            $table->string('modulo_afectado', 50)->nullable();
            $table->string('registro_afectado', 100)->nullable();
            $table->text('descripcion')->nullable();
            $table->string('ip_usuario', 50)->nullable();
            $table->datetime('fecha_hora')->useCurrent();

            $table->foreign('id_usuario')
                  ->references('id_usuario')
                  ->on('usuarios')
                  ->onDelete('set null')
                  ->onUpdate('cascade');

            $table->index('id_usuario');
            $table->index('fecha_hora');
        });

        // Tabla: interacciones_ia
        Schema::create('interacciones_ia', function (Blueprint $table) {
            $table->id('id_interaccion');
            $table->unsignedBigInteger('id_usuario')->nullable();
            $table->string('tipo_intencion', 50)->nullable();
            $table->text('entrada_usuario')->nullable();
            $table->text('respuesta_ia')->nullable();
            $table->enum('estado_resultado', ['exitosa', 'fallida', 'requiere_revision'])->nullable();
            $table->json('contexto')->nullable();
            $table->datetime('fecha_interaccion')->useCurrent();

            $table->foreign('id_usuario')
                  ->references('id_usuario')
                  ->on('usuarios')
                  ->onDelete('set null')
                  ->onUpdate('cascade');

            $table->index('id_usuario');
            $table->index('fecha_interaccion');
        });

        // Tabla: notificaciones
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id('id_notificacion');
            $table->unsignedBigInteger('id_usuario'); // A quién va dirigida
            $table->string('titulo', 150); // Breve título
            $table->text('mensaje'); // Contenido de la notificación
            $table->enum('tipo', ['info', 'alerta', 'cita', 'pago', 'tratamiento'])->default('info');
            $table->string('referencia_tipo', 50)->nullable(); // Ej: 'cita', 'pago', 'tratamiento'
            $table->unsignedInteger('referencia_id')->nullable(); // ID relacionado con la notificación
            $table->boolean('leida')->default(false); // Si el usuario ya la vio
            $table->timestamp('fecha_creacion')->useCurrent();

            $table->foreign('id_usuario')
                  ->references('id_usuario')
                  ->on('usuarios')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');

            $table->index('id_usuario', 'idx_notif_usuario');
        });

        // Tabla: calificaciones
        Schema::create('calificaciones', function (Blueprint $table) {
            $table->id('id_calificacion');
            $table->unsignedBigInteger('id_cita')->nullable();
            $table->unsignedBigInteger('id_paciente')->nullable();
            $table->unsignedBigInteger('id_medico')->nullable();
            $table->integer('puntuacion');
            $table->text('comentario')->nullable();
            $table->datetime('fecha')->useCurrent();

            $table->foreign('id_cita')
                  ->references('id_cita')
                  ->on('citas')
                  ->onDelete('set null')
                  ->onUpdate('cascade');

            $table->foreign('id_paciente')
                  ->references('id_paciente')
                  ->on('pacientes')
                  ->onDelete('set null')
                  ->onUpdate('cascade');

            $table->foreign('id_medico')
                  ->references('id_medico')
                  ->on('medicos')
                  ->onDelete('set null')
                  ->onUpdate('cascade');

            $table->index('id_medico');
        });

        // Agregar constraint CHECK para puntuación (MySQL 8.0.16+)
        DB::statement('ALTER TABLE calificaciones ADD CONSTRAINT chk_puntuacion CHECK (puntuacion BETWEEN 1 AND 5)');
    }

    /**
     * Revierte la migración
     */
    public function down(): void
    {
        // 1. Eliminar la tabla que tiene la restricción CHECK
        // Al eliminar la tabla, la restricción se va con ella.
        // Pero si solo quisiéramos eliminar la restricción, necesitaríamos un DB::statement.
        // Por seguridad y claridad, lo dejamos así, pero es importante saberlo.
        Schema::dropIfExists('calificaciones');

        // 2. Eliminar el resto de las tablas en orden inverso a su creación
        Schema::dropIfExists('notificaciones');
        Schema::dropIfExists('interacciones_ia');
        Schema::dropIfExists('log_actividad');
    }
};
