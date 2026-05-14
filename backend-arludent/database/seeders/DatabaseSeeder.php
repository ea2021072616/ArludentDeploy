<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * DatabaseSeeder - DEMOSTRACIÓN ARLUDENT
 *
 * Ejecuta todos los seeders en el orden correcto para inicializar
 * la base de datos con datos de demostración completos
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Ejecuta los seeders de la base de datos
     */
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('╔═══════════════════════════════════════════════════════════╗');
        $this->command->info('║                                                           ║');
        $this->command->info('║          🦷 ARLUDENT - SISTEMA DENTAL                    ║');
        $this->command->info('║          Inicialización de Base de Datos Demo            ║');
        $this->command->info('║                                                           ║');
        $this->command->info('╚═══════════════════════════════════════════════════════════╝');
        $this->command->info('');

        $this->call([
            // 1. Roles del sistema
            RoleSeeder::class,

            // 2. Catálogo de tratamientos
            TratamientosSeeder::class,

            // 3. Usuarios (Admin, Médicos, Paciente, Externo)
            UserSeeder::class,

            // 4. Registro de paciente
            PacientesSeeder::class,

            // 5. Citas (pasadas, hoy y futuras)
            CitasSeeder::class,

            // 6. Historial clínico completo
            HistorialClinicoSeeder::class,

            // 7. Pagos y comprobantes
            PagosSeeder::class,
        ]);

        $this->command->info('');
        $this->command->info('╔═══════════════════════════════════════════════════════════╗');
        $this->command->info('║                                                           ║');
        $this->command->info('║          ✅ BASE DE DATOS INICIALIZADA                    ║');
        $this->command->info('║                                                           ║');
        $this->command->info('╚═══════════════════════════════════════════════════════════╝');
        $this->command->info('');
        $this->command->info('📊 RESUMEN DE DATOS CREADOS:');
        $this->command->info('');
        $this->command->info('  👥 USUARIOS:');
        $this->command->info('     • 1 Administrador');
        $this->command->info('     • 3 Médicos (cabecera mañana, tarde y especialista)');
        $this->command->info('     • 3 Pacientes (principal con historial completo)');
        $this->command->info('     • 1 Usuario externo (sin historial)');
        $this->command->info('');
        $this->command->info('  📅 CITAS:');
        $this->command->info('     • 10 Citas pasadas (completadas)');
        $this->command->info('     • 2 Citas para HOY (confirmadas)');
        $this->command->info('     • 3 Citas futuras (pendientes)');
        $this->command->info('');
        $this->command->info('  📋 HISTORIAL CLÍNICO:');
        $this->command->info('     • 1 Historial completo con anamnesis');
        $this->command->info('     • 7 Consultas documentadas');
        $this->command->info('     • 32 Piezas dentales en odontograma');
        $this->command->info('     • 4 Tratamientos completados');
        $this->command->info('     • 1 Tratamiento en curso (ortodoncia)');
        $this->command->info('     • 4 Seguimientos de tratamiento');
        $this->command->info('     • 2 Tratamientos sugeridos');
        $this->command->info('');
        $this->command->info('  💰 PAGOS:');
        $this->command->info('     • 8 Pagos completados (S/. 2,820.00)');
        $this->command->info('     • 2 Pagos pendientes (S/. 500.00)');
        $this->command->info('     • 7 Comprobantes SUNAT emitidos');
        $this->command->info('');
        $this->command->info('  🏥 CATÁLOGO:');
        $this->command->info('     • 17 Tratamientos odontológicos disponibles');
        $this->command->info('');
        $this->command->info('═══════════════════════════════════════════════════════════');
        $this->command->info('🎯 SISTEMA LISTO PARA DEMOSTRACIÓN');
        $this->command->info('═══════════════════════════════════════════════════════════');
        $this->command->info('');
    }
}
