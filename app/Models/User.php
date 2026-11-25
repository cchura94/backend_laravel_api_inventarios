<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Rol con acceso total al sistema (ver AppServiceProvider).
     */
    public const ROL_ADMINISTRADOR = 'administrador';

    /**
     * Permisos ya resueltos en esta petición (evita repetir consultas).
     *
     * @var array<int, string>|null
     */
    private ?array $permisosResueltos = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * @return BelongsToMany<Sucursal, $this>
     */
    public function sucursales(): BelongsToMany
    {
        return $this->belongsToMany(Sucursal::class)
            ->withPivot(['role_id'])
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    /**
     * Nombres de los permisos que el usuario tiene a través de sus roles.
     *
     * @return array<int, string>
     */
    public function nombresDePermisos(): array
    {
        return $this->permisosResueltos ??= $this->roles()
            ->with('permisos')
            ->get()
            ->flatMap(fn (Role $rol): array => $rol->permisos->pluck('nombre')->all())
            ->unique()
            ->values()
            ->all();
    }

    public function esAdministrador(): bool
    {
        return $this->tieneRol(self::ROL_ADMINISTRADOR);
    }

    public function tieneRol(string ...$roles): bool
    {
        return $this->roles()->whereIn('nombre', $roles)->exists();
    }

    public function tienePermiso(string $permiso): bool
    {
        return in_array($permiso, $this->nombresDePermisos(), true);
    }

    /**
     * @param  array<int, string>  $permisos
     */
    public function tieneAlgunPermiso(array $permisos): bool
    {
        return array_intersect($permisos, $this->nombresDePermisos()) !== [];
    }

    /**
     * @param  array<int, string>  $permisos
     */
    public function tieneTodosLosPermisos(array $permisos): bool
    {
        return array_diff($permisos, $this->nombresDePermisos()) === [];
    }
}
