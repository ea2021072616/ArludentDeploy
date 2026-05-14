<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: Pacientes, Médicos, Citas y Disponibilidad
 *
 * Crea las tablas principales del módulo clínico
 */
return new class extends Migration
{
    /**
     * Ejecuta la migración
     */
    public function up(): void
    {
        // Tabla: pacientes
        Schema::create('pacientes', function (Blueprint $table) {
            $table->id('id_paciente');
            $table->unsignedBigInteger('id_usuario')->nullable();
            $table->string('apellidos', 100);
            $table->string('nombres', 100);
            $table->string('dni', 20)->nullable()->index();
            $table->date('fecha_nacimiento')->nullable();
            $table->enum('sexo', ['M', 'F', 'Otro'])->nullable();
            $table->string('domicilio', 200)->nullable();
            $table->string('persona_responsable', 100)->nullable();
            $table->string('telefono_responsable', 20)->nullable();
            $table->string('grupo_sanguineo', 5)->nullable();
            $table->text('alergias')->nullable();
            $table->datetime('fecha_registro')->useCurrent();
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            $table->timestamps();

            $table->foreign('id_usuario')
                  ->references('id_usuario')
                  ->on('usuarios')
                  ->onDelete('set null')
                  ->onUpdate('cascade');

            $table->index('apellidos');
        });

        // Tabla: medicos
        Schema::create('medicos', function (Blueprint $table) {
            $table->id('id_medico');
            $table->unsignedBigInteger('id_usuario')->nullable();
            $table->string('nombres', 100)->nullable();
            $table->string('apellidos', 100)->nullable();
            $table->string('nro_colegiado', 50)->nullable()->index();
            $table->string('especialidad', 100)->nullable()->index();
            $table->enum('tipo_medico', ['especialista', 'cabecera_manana', 'cabecera_tarde'])->default('especialista');
            $table->integer('anios_experiencia')->nullable();
            $table->string('foto_url', 255)->nullable();
            $table->enum('estado_disponibilidad', ['disponible', 'no_disponible'])->default('disponible');
            $table->timestamps();

            $table->foreign('id_usuario')
                  ->references('id_usuario')
                  ->on('usuarios')
                  ->onDelete('set null')
                  ->onUpdate('cascade');
        });

        // Tabla: disponibilidad_medico
        Schema::create('disponibilidad_medico', function (Blueprint $table) {
            $table->id('id_disp');
            $table->unsignedBigInteger('id_medico');
            $table->enum('tipo', ['horario', 'bloqueo']);
            $table->tinyInteger('dia_semana')->nullable()->comment('0=Dom,1=Lun,...6=Sab');
            $table->datetime('fecha_inicio')->nullable();
            $table->datetime('fecha_fin')->nullable();
            $table->time('hora_inicio')->nullable();
            $table->time('hora_fin')->nullable();
            $table->text('motivo')->nullable();
            $table->timestamps();

            $table->foreign('id_medico')
                  ->references('id_medico')
                  ->on('medicos')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');

            $table->index('id_medico');
            $table->index('tipo');
        });

        // Tabla: citas
        Schema::create('citas', function (Blueprint $table) {
            $table->id('id_cita');
            $table->unsignedBigInteger('id_usuario_externo')->nullable();
            $table->unsignedBigInteger('id_paciente')->nullable();
            $table->unsignedBigInteger('id_medico');
            $table->datetime('fecha_hora_inicio');
            $table->datetime('fecha_hora_fin')->nullable();
            $table->text('motivo')->nullable();
            $table->enum('estado', ['pendiente', 'confirmado', 'en_espera', 'siendo_atendido', 'completado', 'cancelado', 'no_asistio'])->default('pendiente');
            $table->unsignedBigInteger('creado_por')->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->foreign('id_usuario_externo')
                  ->references('id_usuario')
                  ->on('usuarios')
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
                  ->onDelete('restrict')
                  ->onUpdate('cascade');

            $table->foreign('creado_por')
                  ->references('id_usuario')
                  ->on('usuarios')
                  ->onDelete('set null')
                  ->onUpdate('cascade');

            $table->index(['id_medico', 'fecha_hora_inicio']);
            $table->index('id_paciente');
        });
    }

    /**
     * Revierte la migración
     */
    public function down(): void
    {
        Schema::dropIfExists('citas');
        Schema::dropIfExists('disponibilidad_medico');
        Schema::dropIfExists('medicos');
        Schema::dropIfExists('pacientes');
    }
};
