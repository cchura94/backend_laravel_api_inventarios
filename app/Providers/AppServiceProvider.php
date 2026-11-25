<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registrarGates();
    }

    /**
     * Autoriza cualquier habilidad contra los permisos que el usuario tiene
     * a través de sus roles.
     *
     * Los permisos viven en la tabla "permissions" (p. ej. "categoria.ver"),
     * por eso se usa Gate::before en lugar de registrar un Gate::define por
     * cada fila: así un permiso nuevo en la BD funciona sin reiniciar ni
     * tocar código, y no se consulta la BD al arrancar la aplicación.
     *
     * Uso: `$user->can('categoria.ver')`, `Gate::allows('venta.crear')` o
     * middleware `can:categoria.editar` en las rutas.
     */
    private function registrarGates(): void
    {
        Gate::before(function (User $usuario, string $habilidad): ?bool {
            if ($usuario->esAdministrador()) {
                return true;
            }

            // null deja que sigan evaluándose los Gates/Policies definidos.
            return $usuario->tienePermiso($habilidad) ? true : null;
        });
    }
}
