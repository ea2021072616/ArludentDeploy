<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: Usuarios, Roles y Relación N:N
 *
 * Crea las tablas base del sistema de autenticación y autorización
 */
return new class extends Migration
{
    /**
     * Ejecuta la migración
     */
    public function up(): void
    {
        // Tabla: usuarios
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id('id_usuario');
            $table->string('username', 50)->nullable()->index();
            $table->string('password_hash', 255);
            $table->string('correo', 100)->unique();
            $table->string('telefono', 20)->nullable()->index();
            $table->enum('estado', ['pendiente', 'activo', 'inactivo'])->default('pendiente');
            $table->datetime('email_verified_at')->nullable();
            $table->string('verification_token', 255)->nullable();
            $table->boolean('mfa_enabled')->default(false);
            $table->string('mfa_secret', 255)->nullable();
            $table->datetime('mfa_last_verified')->nullable();
            $table->datetime('fecha_registro')->useCurrent();
            $table->datetime('last_login')->nullable();
            $table->timestamps();
        });

        // Tabla: roles
        Schema::create('roles', function (Blueprint $table) {
            $table->id('id_rol');
            $table->enum('nombre_rol', ['paciente', 'medico', 'admin', 'externo', 'secretaria']);
            $table->text('descripcion')->nullable();
        });

        // Tabla: roles_usuarios (N:N)
        Schema::create('roles_usuarios', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_usuario');
            $table->unsignedBigInteger('id_rol');
            $table->unsignedBigInteger('asignado_por')->nullable();
            $table->datetime('fecha_asignacion')->useCurrent();
            $table->timestamps();

            // Foreign Keys
            $table->foreign('id_usuario')
                  ->references('id_usuario')
                  ->on('usuarios')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');

            $table->foreign('id_rol')
                  ->references('id_rol')
                  ->on('roles')
                  ->onDelete('restrict')
                  ->onUpdate('cascade');

            $table->foreign('asignado_por')
                  ->references('id_usuario')
                  ->on('usuarios')
                  ->onDelete('set null')
                  ->onUpdate('cascade');

            // Índice único para evitar duplicados
            $table->unique(['id_usuario', 'id_rol'], 'ux_roles_usuario');
        });
    }

    /**
     * Revierte la migración
     */
    public function down(): void
    {
        Schema::dropIfExists('roles_usuarios');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('usuarios');
    }
};
