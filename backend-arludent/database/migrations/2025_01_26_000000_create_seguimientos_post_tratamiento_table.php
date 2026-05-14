<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('seguimientos_post_tratamiento', function (Blueprint $table) {
            $table->id('id_seguimiento');

            // RELACIONES
            $table->unsignedBigInteger('id_paciente');
            $table->unsignedBigInteger('id_cita')->nullable();
            $table->unsignedBigInteger('id_historial')->nullable();

            // FECHAS Y PROGRAMACIÓN
            $table->date('fecha_seguimiento'); // Cuándo se debe hacer
            $table->dateTime('fecha_realizado')->nullable(); // Cuándo se hizo realmente
            $table->enum('metodo_contacto', ['llamada', 'whatsapp', 'email', 'portal', 'presencial', 'otro'])->default('email');

            // ESTADO Y CLASIFICACIÓN
            $table->enum('tipo_seguimiento', ['postoperatorio', 'revision', 'medicacion', 'general'])->default('general');
            $table->enum('estado', ['pendiente', 'enviado', 'respondido', 'realizado', 'requiere_revision', 'cancelado'])->default('pendiente');
            $table->enum('prioridad', ['baja', 'media', 'alta', 'urgente'])->default('media');

            // RESPUESTA DEL PACIENTE
            $table->text('respuesta_paciente')->nullable();
            $table->boolean('tiene_problema')->default(false);

            // DETALLES DE PROBLEMAS (si los hay)
            $table->text('descripcion_problema')->nullable();
            $table->text('sintomas')->nullable();
            $table->boolean('requiere_cita_urgente')->default(false);

            // SEGUIMIENTO DE SEGUIMIENTOS (para cadenas de seguimiento)
            $table->unsignedBigInteger('seguimiento_padre_id')->nullable();
            $table->date('proxima_fecha_seguimiento')->nullable();

            // OBSERVACIONES Y NOTAS
            $table->text('notas_secretaria')->nullable();
            $table->text('notas_medico')->nullable();

            // ============ CAMPOS PARA IA ============
            $table->boolean('gestionado_por_ia')->default(false);
            $table->timestamp('enviado_ia_at')->nullable();
            $table->json('analisis_ia')->nullable();
            $table->string('token_respuesta', 64)->unique()->nullable();
            $table->timestamp('respondido_paciente_at')->nullable();

            // AUDITORÍA
            $table->unsignedBigInteger('realizado_por')->nullable(); // ID del usuario
            $table->timestamps();

            // ÍNDICES
            $table->index('id_paciente');
            $table->index('fecha_seguimiento');
            $table->index('estado');
            $table->index('prioridad');
            $table->index(['estado', 'fecha_seguimiento']); // Para buscar pendientes por fecha
            $table->index('tiene_problema'); // Para filtrar rápido los problemas
            $table->index(['gestionado_por_ia', 'estado']); // Para filtrar por IA
            $table->index('token_respuesta'); // Para buscar por token único

            // FOREIGN KEYS
            $table->foreign('id_paciente')
                  ->references('id_paciente')
                  ->on('pacientes')
                  ->onDelete('cascade');

            $table->foreign('id_cita')
                  ->references('id_cita')
                  ->on('citas')
                  ->onDelete('set null');

            $table->foreign('id_historial')
                  ->references('id_historial')
                  ->on('historial_clinico')
                  ->onDelete('set null');

            $table->foreign('seguimiento_padre_id')
                  ->references('id_seguimiento')
                  ->on('seguimientos_post_tratamiento')
                  ->onDelete('set null');

            $table->foreign('realizado_por')
                  ->references('id_usuario')
                  ->on('usuarios')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seguimientos_post_tratamiento');
    }
};
