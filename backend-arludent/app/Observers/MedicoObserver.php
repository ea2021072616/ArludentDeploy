<?php

namespace App\Observers;

use App\Models\Medico;
use App\Models\DisponibilidadMedico;

/**
 * Observer para el modelo Medico
 * 
 * Gestiona la creación automática de horarios para médicos de cabecera
 */
class MedicoObserver
{
    /**
     * Manejar el evento "created" del modelo Medico.
     * 
     * Cuando se crea un médico de cabecera, se generan automáticamente
     * sus horarios de Lunes a Viernes según su turno
     */
    public function created(Medico $medico): void
    {
        $this->inicializarHorariosCabecera($medico);
    }

    /**
     * Manejar el evento "updated" del modelo Medico.
     * 
     * Si se cambia el tipo de médico a cabecera, se generan los horarios
     */
    public function updated(Medico $medico): void
    {
        // Solo si cambió el tipo_medico y ahora es cabecera
        if ($medico->isDirty('tipo_medico') && in_array($medico->tipo_medico, ['cabecera_manana', 'cabecera_tarde'])) {
            // Eliminar horarios anteriores si existen
            DisponibilidadMedico::where('id_medico', $medico->id_medico)
                ->where('tipo', 'horario')
                ->whereNotNull('dia_semana')
                ->delete();

            // Crear nuevos horarios según el tipo
            $this->inicializarHorariosCabecera($medico);
        }
    }

    /**
     * Inicializar horarios de cabecera (Lunes a Viernes)
     */
    private function inicializarHorariosCabecera(Medico $medico): void
    {
        // Solo para médicos de cabecera
        if (!in_array($medico->tipo_medico, ['cabecera_manana', 'cabecera_tarde'])) {
            return;
        }

        // Determinar horarios según el tipo
        $horarios = $this->getHorariosPredefinidos($medico->tipo_medico);
        
        if (!$horarios) {
            return;
        }

        // Crear horarios de Lunes (1) a Sábado (6)
        for ($dia = 1; $dia <= 6; $dia++) {
            DisponibilidadMedico::firstOrCreate(
                [
                    'id_medico' => $medico->id_medico,
                    'tipo' => 'horario',
                    'dia_semana' => $dia,
                ],
                [
                    'hora_inicio' => $horarios['hora_inicio'],
                    'hora_fin' => $horarios['hora_fin'],
                    'fecha_inicio' => null,
                    'fecha_fin' => null,
                    'motivo' => 'Horario predefinido de ' . ($medico->tipo_medico === 'cabecera_manana' ? 'mañana' : 'tarde'),
                ]
            );
        }
    }

    /**
     * Obtener horarios predefinidos según tipo de médico
     */
    private function getHorariosPredefinidos(string $tipoMedico): ?array
    {
        switch ($tipoMedico) {
            case 'cabecera_manana':
                return ['hora_inicio' => '09:00', 'hora_fin' => '13:00'];
            case 'cabecera_tarde':
                return ['hora_inicio' => '13:00', 'hora_fin' => '20:00'];
            default:
                return null;
        }
    }
}
