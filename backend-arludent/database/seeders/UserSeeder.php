<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Rol;
use App\Models\Medico;
use App\Models\DisponibilidadMedico;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

/**
 * Seeder de Usuarios - DEMOSTRACIÓN
 *
 * Crea usuarios completos para demostración del sistema
 */
class UserSeeder extends Seeder
{
    /**
     * Ejecuta el seeder
     */
    public function run(): void
    {
        $this->command->info('═══════════════════════════════════════════════════════════');
        $this->command->info('🔧 INICIANDO SEED DE USUARIOS - DEMO ARLUDENT');
        $this->command->info('═══════════════════════════════════════════════════════════');

        // Obtener roles
        $rolAdmin = Rol::where('nombre_rol', 'admin')->first();
        $rolMedico = Rol::where('nombre_rol', 'medico')->first();
        $rolSecretaria = Rol::where('nombre_rol', 'secretaria')->first();
        $rolPaciente = Rol::where('nombre_rol', 'paciente')->first();
        $rolExterno = Rol::where('nombre_rol', 'externo')->first();

        // ========== ADMIN ==========
        $admin = User::updateOrCreate(
            ['correo' => 'admin@arludent.com'],
            [
                'username' => 'admin_arludent',
                'password_hash' => Hash::make('Admin123!'),
                'telefono' => '+51987654321',
                'estado' => 'activo',
                'email_verified_at' => now(),
            ]
        );

        if (!$admin->roles()->where('roles.id_rol', $rolAdmin->id_rol)->exists()) {
            $admin->roles()->attach($rolAdmin->id_rol, ['fecha_asignacion' => now()]);
        }
        $this->command->info('✓ Admin creado: admin@arludent.com / Admin123!');

        // ========== SECRETARIA ==========
        $secretaria = User::updateOrCreate(
            ['correo' => 'secretaria@arludent.com'],
            [
                'username' => 'ana.lopez',
                'password_hash' => Hash::make('Secretaria123!'),
                'telefono' => '+51987008008',
                'estado' => 'activo',
                'email_verified_at' => now(),
            ]
        );

        if (!$secretaria->roles()->where('roles.id_rol', $rolSecretaria->id_rol)->exists()) {
            $secretaria->roles()->attach($rolSecretaria->id_rol, ['fecha_asignacion' => now()]);
        }
        $this->command->info('✓ Secretaria creada: secretaria@arludent.com / Secretaria123!');

        // ========== MÉDICO 1 - CABECERA MAÑANA ==========
        $medico1 = User::updateOrCreate(
            ['correo' => 'medico@arludent.com'],
            [
                'username' => 'dr.ramirez',
                'password_hash' => Hash::make('Medico123!'),
                'telefono' => '+51987001001',
                'estado' => 'activo',
                'email_verified_at' => now(),
            ]
        );

        if (!$medico1->roles()->where('roles.id_rol', $rolMedico->id_rol)->exists()) {
            $medico1->roles()->attach($rolMedico->id_rol, ['fecha_asignacion' => now()]);
        }

        $perfilMedico1 = Medico::updateOrCreate(
            ['id_usuario' => $medico1->id_usuario],
            [
                'nombres' => 'Carlos Eduardo',
                'apellidos' => 'Ramírez Soto',
                'nro_colegiado' => 'COP-12345',
                'especialidad' => 'Odontología General',
                'tipo_medico' => 'cabecera_manana',
                'anios_experiencia' => 12,
                'estado_disponibilidad' => 'disponible',
            ]
        );

        // Disponibilidad Médico 1 (Lunes a Viernes 8:00-14:00)
        DisponibilidadMedico::where('id_medico', $perfilMedico1->id_medico)->delete();
        foreach ([1, 2, 3, 4, 5] as $dia) { // Lun-Vie
            DisponibilidadMedico::create([
                'id_medico' => $perfilMedico1->id_medico,
                'tipo' => 'horario',
                'dia_semana' => $dia,
                'hora_inicio' => '08:00:00',
                'hora_fin' => '14:00:00',
            ]);
        }

        $this->command->info('✓ Médico Cabecera Mañana: Dr. Carlos Ramírez');
        $this->command->info('  └─ Correo: medico@arludent.com / Medico123!');
        $this->command->info('  └─ Horario: Lun-Vie 8:00-14:00');

        // ========== MÉDICO 2 - CABECERA TARDE ==========
        $medico2 = User::updateOrCreate(
            ['correo' => 'medico2@arludent.com'],
            [
                'username' => 'dra.gomez',
                'password_hash' => Hash::make('Medico123!'),
                'telefono' => '+51987002002',
                'estado' => 'activo',
                'email_verified_at' => now(),
            ]
        );

        if (!$medico2->roles()->where('roles.id_rol', $rolMedico->id_rol)->exists()) {
            $medico2->roles()->attach($rolMedico->id_rol, ['fecha_asignacion' => now()]);
        }

        $perfilMedico2 = Medico::updateOrCreate(
            ['id_usuario' => $medico2->id_usuario],
            [
                'nombres' => 'Lucía Patricia',
                'apellidos' => 'Gómez Vargas',
                'nro_colegiado' => 'COP-67890',
                'especialidad' => 'Ortodoncia y Estética Dental',
                'tipo_medico' => 'cabecera_tarde',
                'anios_experiencia' => 8,
                'estado_disponibilidad' => 'disponible',
            ]
        );

        // Disponibilidad Médico 2 (Lunes a Viernes 14:00-20:00)
        DisponibilidadMedico::where('id_medico', $perfilMedico2->id_medico)->delete();
        foreach ([1, 2, 3, 4, 5] as $dia) { // Lun-Vie
            DisponibilidadMedico::create([
                'id_medico' => $perfilMedico2->id_medico,
                'tipo' => 'horario',
                'dia_semana' => $dia,
                'hora_inicio' => '14:00:00',
                'hora_fin' => '20:00:00',
            ]);
        }

        $this->command->info('✓ Médico Cabecera Tarde: Dra. Lucía Gómez');
        $this->command->info('  └─ Correo: medico2@arludent.com / Medico123!');
        $this->command->info('  └─ Horario: Lun-Vie 14:00-20:00');

        // ========== MÉDICO ESPECIALISTA - ENDODONCIA ==========
        $medicoEspecialista = User::updateOrCreate(
            ['correo' => 'especialista@arludent.com'],
            [
                'username' => 'dr.vargas',
                'password_hash' => Hash::make('Medico123!'),
                'telefono' => '+51987005005',
                'estado' => 'activo',
                'email_verified_at' => now(),
            ]
        );

        if (!$medicoEspecialista->roles()->where('roles.id_rol', $rolMedico->id_rol)->exists()) {
            $medicoEspecialista->roles()->attach($rolMedico->id_rol, ['fecha_asignacion' => now()]);
        }

        $perfilEspecialista = Medico::updateOrCreate(
            ['id_usuario' => $medicoEspecialista->id_usuario],
            [
                'nombres' => 'Roberto',
                'apellidos' => 'Vargas Mendoza',
                'nro_colegiado' => 'COP-45678',
                'especialidad' => 'Endodoncia y Cirugía Oral',
                'tipo_medico' => 'especialista',
                'anios_experiencia' => 15,
                'estado_disponibilidad' => 'disponible',
            ]
        );

        // Disponibilidad Médico Especialista (Martes y Jueves 9:00-17:00)
        DisponibilidadMedico::where('id_medico', $perfilEspecialista->id_medico)->delete();
        foreach ([2, 4] as $dia) { // Mar y Jue
            DisponibilidadMedico::create([
                'id_medico' => $perfilEspecialista->id_medico,
                'tipo' => 'horario',
                'dia_semana' => $dia,
                'hora_inicio' => '09:00:00',
                'hora_fin' => '17:00:00',
            ]);
        }

        $this->command->info('✓ Médico Especialista: Dr. Roberto Vargas');
        $this->command->info('  └─ Correo: especialista@arludent.com / Medico123!');
        $this->command->info('  └─ Horario: Mar-Jue 9:00-17:00');

        // ========== PACIENTE PRINCIPAL ==========
        $paciente = User::updateOrCreate(
            ['correo' => 'paciente@arludent.com'],
            [
                'username' => 'juan.perez',
                'password_hash' => Hash::make('Paciente123!'),
                'telefono' => '+51987003003',
                'estado' => 'activo',
                'email_verified_at' => now(),
            ]
        );

        if (!$paciente->roles()->where('roles.id_rol', $rolPaciente->id_rol)->exists()) {
            $paciente->roles()->attach($rolPaciente->id_rol, ['fecha_asignacion' => now()]);
        }

        $this->command->info('✓ Paciente Principal: Juan Alberto Pérez Gonzales');
        $this->command->info('  └─ Correo: paciente@arludent.com / Paciente123!');

        // ========== PACIENTE 2 ==========
        $paciente2 = User::updateOrCreate(
            ['correo' => 'paciente2@arludent.com'],
            [
                'username' => 'maria.garcia',
                'password_hash' => Hash::make('Paciente123!'),
                'telefono' => '+51987006006',
                'estado' => 'activo',
                'email_verified_at' => now(),
            ]
        );

        if (!$paciente2->roles()->where('roles.id_rol', $rolPaciente->id_rol)->exists()) {
            $paciente2->roles()->attach($rolPaciente->id_rol, ['fecha_asignacion' => now()]);
        }

        $this->command->info('✓ Paciente 2: María García López');
        $this->command->info('  └─ Correo: paciente2@arludent.com / Paciente123!');

        // ========== PACIENTE 3 ==========
        $paciente3 = User::updateOrCreate(
            ['correo' => 'paciente3@arludent.com'],
            [
                'username' => 'carlos.rodriguez',
                'password_hash' => Hash::make('Paciente123!'),
                'telefono' => '+51987007007',
                'estado' => 'activo',
                'email_verified_at' => now(),
            ]
        );

        if (!$paciente3->roles()->where('roles.id_rol', $rolPaciente->id_rol)->exists()) {
            $paciente3->roles()->attach($rolPaciente->id_rol, ['fecha_asignacion' => now()]);
        }

        $this->command->info('✓ Paciente 3: Carlos Rodríguez Sánchez');
        $this->command->info('  └─ Correo: paciente3@arludent.com / Paciente123!');

        // ========== EXTERNO ==========
        $externo = User::updateOrCreate(
            ['correo' => 'externo@arludent.com'],
            [
                'username' => 'maria.torres',
                'password_hash' => Hash::make('Externo123!'),
                'telefono' => '+51987004004',
                'estado' => 'activo',
                'email_verified_at' => now(),
            ]
        );

        if (!$externo->roles()->where('roles.id_rol', $rolExterno->id_rol)->exists()) {
            $externo->roles()->attach($rolExterno->id_rol, ['fecha_asignacion' => now()]);
        }

        $this->command->info('✓ Usuario Externo: María Torres (sin historial clínico)');
        $this->command->info('  └─ Correo: externo@arludent.com / Externo123!');

        // ========== RESUMEN ==========
        $this->command->info('');
        $this->command->info('═══════════════════════════════════════════════════════════');
        $this->command->info('✅ USUARIOS CREADOS EXITOSAMENTE');
        $this->command->info('═══════════════════════════════════════════════════════════');
        $this->command->info('');
        $this->command->info('📋 CREDENCIALES DE ACCESO:');
        $this->command->info('');
        $this->command->info('👤 Admin:');
        $this->command->info('   Correo: admin@arludent.com');
        $this->command->info('   Contraseña: Admin123!');
        $this->command->info('');
        $this->command->info('👩‍💼 Secretaria:');
        $this->command->info('   Correo: secretaria@arludent.com');
        $this->command->info('   Contraseña: Secretaria123!');
        $this->command->info('   Ana López - Gestión de citas y pacientes');
        $this->command->info('');
        $this->command->info('👨‍⚕️ Médico Cabecera Mañana:');
        $this->command->info('   Correo: medico@arludent.com');
        $this->command->info('   Contraseña: Medico123!');
        $this->command->info('   Dr. Carlos Ramírez - Turno: 8:00-14:00');
        $this->command->info('');
        $this->command->info('👩‍⚕️ Médico Cabecera Tarde:');
        $this->command->info('   Correo: medico2@arludent.com');
        $this->command->info('   Contraseña: Medico123!');
        $this->command->info('   Dra. Lucía Gómez - Turno: 14:00-20:00');
        $this->command->info('');
        $this->command->info('�‍⚕️ Médico Especialista:');
        $this->command->info('   Correo: especialista@arludent.com');
        $this->command->info('   Contraseña: Medico123!');
        $this->command->info('   Dr. Roberto Vargas - Turno: Mar-Jue 9:00-17:00');
        $this->command->info('');
        $this->command->info('�👤 Paciente Principal (con historial completo):');
        $this->command->info('   Correo: paciente@arludent.com');
        $this->command->info('   Contraseña: Paciente123!');
        $this->command->info('');
        $this->command->info('👤 Paciente 2:');
        $this->command->info('   Correo: paciente2@arludent.com');
        $this->command->info('   Contraseña: Paciente123!');
        $this->command->info('');
        $this->command->info('👤 Paciente 3:');
        $this->command->info('   Correo: paciente3@arludent.com');
        $this->command->info('   Contraseña: Paciente123!');
        $this->command->info('');
        $this->command->info('🆕 Externo:');
        $this->command->info('   Correo: externo@arludent.com');
        $this->command->info('   Contraseña: Externo123!');
        $this->command->info('');
        $this->command->info('═══════════════════════════════════════════════════════════');
    }
}
