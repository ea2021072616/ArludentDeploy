<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Rol;

/**
 * Seeder de Roles
 *
 * Crea los 4 roles principales del sistema
 */
class RoleSeeder extends Seeder
{
    /**
     * Ejecuta el seeder
     */
    public function run(): void
    {
        $roles = [
            [
                'nombre_rol' => 'admin',
                'descripcion' => 'Administrador del sistema con acceso completo',
            ],
            [
                'nombre_rol' => 'medico',
                'descripcion' => 'Médico con acceso a gestión clínica',
            ],
            [
                'nombre_rol' => 'secretaria',
                'descripcion' => 'Secretaria con acceso a gestión de citas y pacientes',
            ],
            [
                'nombre_rol' => 'paciente',
                'descripcion' => 'Paciente con acceso limitado a su información',
            ],
            [
                'nombre_rol' => 'externo',
                'descripcion' => 'Usuario externo con permisos específicos',
            ],
        ];

        foreach ($roles as $rol) {
            Rol::firstOrCreate(
                ['nombre_rol' => $rol['nombre_rol']],
                ['descripcion' => $rol['descripcion']]
            );
        }

        $this->command->info('✓ Roles creados exitosamente');
    }
}
