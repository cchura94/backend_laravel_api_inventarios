<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RolesYPermisosSeeder extends Seeder
{
    /**
     * Recursos del sistema y las acciones que se pueden autorizar sobre ellos.
     *
     * @var array<string, string>
     */
    private array $recursos = [
        'categoria' => 'Categorías',
        'producto' => 'Productos',
        'sucursal' => 'Sucursales',
        'almacen' => 'Almacenes',
        'cliente' => 'Clientes',
        'proveedor' => 'Proveedores',
        'compra' => 'Compras',
        'venta' => 'Ventas',
        'usuario' => 'Usuarios',
        'rol' => 'Roles',
        'permiso' => 'Permisos',
    ];

    /**
     * @var array<string, string>
     */
    private array $acciones = [
        'ver' => 'Ver',
        'crear' => 'Crear',
        'editar' => 'Editar',
        'eliminar' => 'Eliminar',
    ];

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $permisos = $this->crearPermisos();

        $roles = [
            'administrador' => [
                'descripcion' => 'Acceso total al sistema',
                'permisos' => ['*'],
            ],
            'vendedor' => [
                'descripcion' => 'Registra ventas y administra clientes',
                'permisos' => [
                    'venta.*',
                    'cliente.*',
                    'producto.ver',
                    'categoria.ver',
                    'sucursal.ver',
                    'almacen.ver',
                ],
            ],
            'almacenero' => [
                'descripcion' => 'Administra productos, almacenes y compras',
                'permisos' => [
                    'producto.*',
                    'categoria.*',
                    'almacen.*',
                    'compra.*',
                    'proveedor.*',
                    'sucursal.ver',
                ],
            ],
        ];

        foreach ($roles as $nombre => $definicion) {
            $rol = Role::firstOrCreate(
                ['nombre' => $nombre],
                ['descripcion' => $definicion['descripcion']]
            );

            $rol->permisos()->syncWithoutDetaching(
                $this->resolverPermisos($definicion['permisos'], $permisos)
            );
        }

        // El usuario administrador del seeder principal recibe el rol "administrador".
        $administrador = Role::where('nombre', 'administrador')->firstOrFail();

        User::where('email', 'admin@mail.com')
            ->first()
            ?->roles()
            ->syncWithoutDetaching([$administrador->id]);
    }

    /**
     * Crea (o reutiliza) los permisos "recurso.accion" y devuelve sus ids.
     *
     * @return array<string, int>
     */
    private function crearPermisos(): array
    {
        $permisos = [];

        foreach ($this->recursos as $recurso => $etiqueta) {
            foreach ($this->acciones as $accion => $verbo) {
                $nombre = "$recurso.$accion";

                $permisos[$nombre] = Permission::firstOrCreate(
                    ['nombre' => $nombre],
                    ['descripcion' => "$verbo $etiqueta"]
                )->id;
            }
        }

        return $permisos;
    }

    /**
     * Expande comodines ("venta.*") a los ids de permisos existentes.
     *
     * @param  array<int, string>  $solicitados
     * @param  array<string, int>  $permisos
     * @return array<int, int>
     */
    private function resolverPermisos(array $solicitados, array $permisos): array
    {
        $ids = [];

        foreach ($solicitados as $patron) {
            if ($patron === '*') {
                $ids = array_merge($ids, array_values($permisos));

                continue;
            }

            if (str_ends_with($patron, '.*')) {
                $prefijo = substr($patron, 0, -2);
                $ids = array_merge($ids, array_values(
                    array_filter(
                        $permisos,
                        fn (string $nombre): bool => str_starts_with($nombre, "$prefijo."),
                        ARRAY_FILTER_USE_KEY
                    )
                ));

                continue;
            }

            if (isset($permisos[$patron])) {
                $ids[] = $permisos[$patron];
            }
        }

        return array_values(array_unique($ids));
    }
}
