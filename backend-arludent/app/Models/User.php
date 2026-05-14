<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Modelo Usuario
 *
 * Representa a los usuarios del sistema con autenticación JWT
 * Incluye soporte para MFA, verificación de correo y roles
 *
 * @property-read \App\Models\Medico|null $medico
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \App\Models\Paciente|null $paciente
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Rol> $roles
 * @property-read int|null $roles_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @mixin \Eloquent
 */
class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $table = 'usuarios';
    protected $primaryKey = 'id_usuario';

    /**
     * Atributos asignables masivamente
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'password_hash',
        'correo',
        'telefono',
        'estado',
        'email_verified_at',
        'verification_token',
        'mfa_enabled',
        'mfa_secret',
        'mfa_last_verified',
        'last_login',
    ];

    /**
     * Atributos ocultos en serialización
     *
     * @var list<string>
     */
    protected $hidden = [
        'password_hash',
        'mfa_secret',
        'verification_token',
        'remember_token',
    ];

    /**
     * Atributos que deben ser casteados
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'mfa_last_verified' => 'datetime',
        'last_login' => 'datetime',
        'fecha_registro' => 'datetime',
        'mfa_enabled' => 'boolean',
    ];

    /**
     * Obtiene el identificador del JWT
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Claims personalizados del JWT
     */
    public function getJWTCustomClaims(): array
    {
        return [
            'correo' => $this->correo,
            'username' => $this->username,
            'roles' => $this->roles->pluck('nombre_rol')->toArray(),
        ];
    }

    /**
     * Obtiene la contraseña para autenticación
     */
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    /**
     * Verifica si el correo está verificado
     */
    public function hasVerifiedEmail(): bool
    {
        return !is_null($this->email_verified_at);
    }

    /**
     * Marca el correo como verificado
     */
    public function markEmailAsVerified(): bool
    {
        return $this->forceFill([
            'email_verified_at' => $this->freshTimestamp(),
            'verification_token' => null,
        ])->save();
    }

    // Relaciones

    /**
     * Roles del usuario (relación N:N)
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Rol::class,
            'roles_usuarios',
            'id_usuario',
            'id_rol'
        )->withPivot('fecha_asignacion', 'asignado_por')
          ->withTimestamps();
    }

    /**
     * Datos como paciente
     */
    public function paciente(): HasOne
    {
        return $this->hasOne(Paciente::class, 'id_usuario', 'id_usuario');
    }

    /**
     * Datos como médico
     */
    public function medico(): HasOne
    {
        return $this->hasOne(Medico::class, 'id_usuario', 'id_usuario');
    }

    /**
     * Verifica si el usuario tiene un rol específico
     */
    public function hasRole(string $roleName): bool
    {
        return $this->roles()->where('nombre_rol', $roleName)->exists();
    }

    /**
     * Verifica si el usuario tiene alguno de los roles dados
     */
    public function hasAnyRole(array $roles): bool
    {
        return $this->roles()->whereIn('nombre_rol', $roles)->exists();
    }
}
