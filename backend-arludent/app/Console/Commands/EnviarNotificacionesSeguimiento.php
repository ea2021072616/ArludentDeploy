<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SeguimientoPostTratamiento;
use App\Mail\SeguimientoPostTratamientoMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class EnviarNotificacionesSeguimiento extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seguimiento:enviar-notificaciones
                            {--fecha= : Fecha específica para procesar (formato: Y-m-d)}
                            {--dry-run : Ejecutar sin enviar emails reales}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envía notificaciones de seguimiento post-tratamiento por email';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $fecha = $this->option('fecha')
            ? Carbon::parse($this->option('fecha'))
            : Carbon::today();

        $dryRun = $this->option('dry-run');

        $this->info("🔍 Buscando seguimientos para: " . $fecha->format('d/m/Y'));

        // Buscar seguimientos pendientes para hoy que sean gestionados por IA
        $seguimientos = SeguimientoPostTratamiento::with(['paciente.usuario'])
            ->where('estado', 'pendiente')
            ->whereDate('fecha_seguimiento', $fecha)
            ->where('gestionado_por_ia', true)
            ->whereIn('metodo_contacto', ['email', 'portal'])
            ->get();

        if ($seguimientos->isEmpty()) {
            $this->warn('⚠️  No se encontraron seguimientos pendientes para procesar.');
            return 0;
        }

        $this->info("📧 Encontrados {$seguimientos->count()} seguimientos para procesar\n");

        $enviados = 0;
        $errores = 0;

        foreach ($seguimientos as $seguimiento) {
            $paciente = $seguimiento->paciente;

            // Validar que el paciente tenga email
            $email = $paciente->usuario->correo ?? $paciente->correo;

            if (!$email) {
                $this->warn("⚠️  Paciente {$paciente->nombres} {$paciente->apellidos} no tiene email registrado");
                $errores++;
                continue;
            }

            $this->line("📨 Procesando: {$paciente->nombres} {$paciente->apellidos} ({$email})");

            try {
                if (!$dryRun) {
                    // Enviar email
                    Mail::to($email)->send(new SeguimientoPostTratamientoMail($seguimiento));

                    // Actualizar estado del seguimiento
                    $seguimiento->update([
                        'estado' => 'enviado',
                        'enviado_ia_at' => now(),
                    ]);

                    $this->info("   ✅ Email enviado correctamente");
                } else {
                    $this->comment("   🔍 [DRY RUN] Email NO enviado (modo prueba)");
                }

                $enviados++;

                // Log del evento
                Log::info('Seguimiento enviado', [
                    'id_seguimiento' => $seguimiento->id_seguimiento,
                    'paciente' => $paciente->nombres . ' ' . $paciente->apellidos,
                    'email' => $email,
                    'tipo' => $seguimiento->tipo_seguimiento,
                    'dry_run' => $dryRun,
                ]);

            } catch (\Exception $e) {
                $this->error("   ❌ Error al enviar: " . $e->getMessage());
                $errores++;

                Log::error('Error al enviar seguimiento', [
                    'id_seguimiento' => $seguimiento->id_seguimiento,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            // Pequeña pausa para evitar rate limiting
            if (!$dryRun) {
                usleep(500000); // 0.5 segundos
            }
        }

        // Resumen
        $this->newLine();
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("📊 RESUMEN:");
        $this->info("   ✅ Emails enviados: {$enviados}");
        if ($errores > 0) {
            $this->error("   ❌ Errores: {$errores}");
        }
        $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        return 0;
    }
}
