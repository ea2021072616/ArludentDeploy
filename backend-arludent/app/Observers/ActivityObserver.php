<?php

namespace App\Observers;

use App\Models\User;
use App\Models\LogActividad;

/**
 * Observer de Actividad
 *
 * Registra automáticamente las acciones críticas sobre usuarios en la auditoría
 */
class ActivityObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        LogActividad::create([
            'id_usuario' => $user->id_usuario,
            'accion' => 'usuario_creado',
            'modulo_afectado' => 'usuarios',
            'registro_afectado' => $user->id_usuario,
            'descripcion' => "Usuario {$user->correo} creado",
            'ip_usuario' => request()->ip(),
        ]);
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        // Solo registrar cambios significativos
        $cambiosImportantes = $user->getDirty();

        if (isset($cambiosImportantes['password_hash'])) {
            LogActividad::create([
                'id_usuario' => $user->id_usuario,
                'accion' => 'password_actualizado',
                'modulo_afectado' => 'usuarios',
                'registro_afectado' => $user->id_usuario,
                'descripcion' => "Contraseña actualizada para {$user->correo}",
                'ip_usuario' => request()->ip(),
            ]);
        }

        if (isset($cambiosImportantes['estado'])) {
            LogActividad::create([
                'id_usuario' => $user->id_usuario,
                'accion' => 'estado_cambiado',
                'modulo_afectado' => 'usuarios',
                'registro_afectado' => $user->id_usuario,
                'descripcion' => "Estado cambiado a: {$user->estado}",
                'ip_usuario' => request()->ip(),
            ]);
        }

        if (isset($cambiosImportantes['mfa_enabled'])) {
            $accion = $user->mfa_enabled ? 'mfa_activado' : 'mfa_desactivado';
            LogActividad::create([
                'id_usuario' => $user->id_usuario,
                'accion' => $accion,
                'modulo_afectado' => 'usuarios',
                'registro_afectado' => $user->id_usuario,
                'descripcion' => "MFA " . ($user->mfa_enabled ? 'activado' : 'desactivado'),
                'ip_usuario' => request()->ip(),
            ]);
        }
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        LogActividad::create([
            'id_usuario' => $user->id_usuario,
            'accion' => 'usuario_eliminado',
            'modulo_afectado' => 'usuarios',
            'registro_afectado' => $user->id_usuario,
            'descripcion' => "Usuario {$user->correo} eliminado",
            'ip_usuario' => request()->ip(),
        ]);
    }
}
