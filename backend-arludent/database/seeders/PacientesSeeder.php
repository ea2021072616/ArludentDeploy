<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Paciente;
use Carbon\Carbon;

/**
 * Seeder de Pacientes - DEMOSTRACIÓN
 *
 * Crea los registros de pacientes demo con datos completos
 */
class PacientesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🔧 CREANDO REGISTROS DE PACIENTES DEMO...');

        // ========== PACIENTE PRINCIPAL ==========
        $usuarioPrincipal = User::where('correo', 'paciente@arludent.com')->first();

        if ($usuarioPrincipal) {
            Paciente::updateOrCreate(
                ['id_usuario' => $usuarioPrincipal->id_usuario],
                [
                    'apellidos' => 'Pérez Gonzales',
                    'nombres' => 'Juan Alberto',
                    'dni' => '72345678',
                    'fecha_nacimiento' => '1990-05-14',
                    'sexo' => 'M',
                    'domicilio' => 'Av. Los Olivos 456, San Borja, Lima',
                    'persona_responsable' => 'María Elena Pérez Vda. de Gonzales',
                    'telefono_responsable' => '+51987003003',
                    'grupo_sanguineo' => 'O+',
                    'alergias' => 'Ninguna alergia conocida',
                    'estado' => 'activo',
                    'fecha_registro' => Carbon::now()->subMonths(6),
                ]
            );

            $this->command->info('✓ Paciente Principal creado');
            $this->command->info('  └─ Nombre: Juan Alberto Pérez Gonzales');
            $this->command->info('  └─ DNI: 72345678 - Grupo: O+');
        }

        // ========== PACIENTE 2 ==========
        $usuario2 = User::where('correo', 'paciente2@arludent.com')->first();

        if ($usuario2) {
            Paciente::updateOrCreate(
                ['id_usuario' => $usuario2->id_usuario],
                [
                    'apellidos' => 'García López',
                    'nombres' => 'María del Carmen',
                    'dni' => '45678901',
                    'fecha_nacimiento' => '1985-08-22',
                    'sexo' => 'F',
                    'domicilio' => 'Jr. Las Flores 234, Miraflores, Lima',
                    'persona_responsable' => 'José García Martínez',
                    'telefono_responsable' => '+51987006006',
                    'grupo_sanguineo' => 'A+',
                    'alergias' => 'Alergia a la penicilina',
                    'estado' => 'activo',
                    'fecha_registro' => Carbon::now()->subMonths(3),
                ]
            );

            $this->command->info('✓ Paciente 2 creado');
            $this->command->info('  └─ Nombre: María del Carmen García López');
            $this->command->info('  └─ DNI: 45678901 - Grupo: A+');
        }

        // ========== PACIENTE 3 ==========
        $usuario3 = User::where('correo', 'paciente3@arludent.com')->first();

        if ($usuario3) {
            Paciente::updateOrCreate(
                ['id_usuario' => $usuario3->id_usuario],
                [
                    'apellidos' => 'Rodríguez Sánchez',
                    'nombres' => 'Carlos Alberto',
                    'dni' => '56789012',
                    'fecha_nacimiento' => '1995-03-10',
                    'sexo' => 'M',
                    'domicilio' => 'Av. Universitaria 890, Los Olivos, Lima',
                    'persona_responsable' => 'Ana Sánchez de Rodríguez',
                    'telefono_responsable' => '+51987007007',
                    'grupo_sanguineo' => 'B+',
                    'alergias' => 'Ninguna conocida',
                    'estado' => 'activo',
                    'fecha_registro' => Carbon::now()->subMonths(1),
                ]
            );

            $this->command->info('✓ Paciente 3 creado');
            $this->command->info('  └─ Nombre: Carlos Alberto Rodríguez Sánchez');
            $this->command->info('  └─ DNI: 56789012 - Grupo: B+');
        }

        $this->command->info('');
        $this->command->info('═══════════════════════════════════════════════════════════');
        $this->command->info('✅ 3 PACIENTES CREADOS (Principal con historial completo)');
        $this->command->info('═══════════════════════════════════════════════════════════');
        $this->command->info('');
    }
}
