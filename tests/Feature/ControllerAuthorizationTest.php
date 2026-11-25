<?php

namespace Tests\Feature;

use App\Http\Controllers\AlmacenController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SucursalController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VentaController;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use ReflectionParameter;
use Tests\TestCase;

class ControllerAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesYPermisosSeeder::class);
    }

    public function test_las_acciones_de_los_controladores_rechazan_a_quien_no_tiene_permisos(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $esperadas = [];
        $denegadas = [];

        foreach (self::accionesProtegidas() as [$controlador, $acciones]) {
            foreach ($acciones as $accion) {
                $identificador = "$controlador@$accion";
                $esperadas[] = $identificador;

                try {
                    $this->invocarAccion(app($controlador), $accion);
                } catch (AuthorizationException) {
                    $denegadas[] = $identificador;
                }
            }
        }

        $this->assertSame($esperadas, $denegadas);
    }

    #[DataProvider('rutasProtegidas')]
    public function test_las_rutas_protegidas_devuelven_401_sin_token(string $metodo, string $uri): void
    {
        $this->json($metodo, $uri)->assertUnauthorized();
    }

    #[DataProvider('rutasProtegidas')]
    public function test_las_rutas_protegidas_devuelven_403_sin_permisos(string $metodo, string $uri): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->json($metodo, $uri)->assertForbidden();
    }

    public function test_las_rutas_publicas_siguen_siendo_accesibles(): void
    {
        $this->getJson('/api/saludo')->assertOk();
        $this->getJson('/api/no-autorizado')->assertOk();
    }

    public function test_el_perfil_y_el_logout_requieren_autenticacion(): void
    {
        $this->getJson('/api/v1/auth/profile')->assertUnauthorized();
        $this->postJson('/api/v1/auth/logout')->assertUnauthorized();
    }

    /**
     * @return array<int, array{0: class-string, 1: array<int, string>}>
     */
    public static function accionesProtegidas(): array
    {
        return [
            [AlmacenController::class, ['index', 'store', 'show', 'update', 'destroy']],
            [CategoriaController::class, ['index', 'store', 'show', 'update', 'destroy']],
            [ClienteController::class, ['index', 'store', 'show', 'update', 'destroy']],
            [CompraController::class, ['index', 'store', 'show', 'update', 'destroy', 'generarReciboPDF', 'exportarPDF']],
            [PermissionController::class, ['index', 'store', 'show', 'update', 'destroy']],
            [ProductoController::class, ['index', 'store', 'actualizarImagen', 'show', 'update', 'destroy', 'exportarExcel']],
            [ProveedorController::class, ['index', 'store', 'show', 'update', 'destroy']],
            [RoleController::class, ['index', 'store', 'show', 'update', 'destroy', 'actualizarPermisos', 'asignarPermiso', 'quitarPermiso']],
            [SucursalController::class, ['index', 'store', 'show', 'update', 'destroy']],
            [UserController::class, ['index', 'store', 'show', 'update', 'destroy']],
            [VentaController::class, ['index', 'store', 'show', 'update', 'destroy', 'generarReciboPDF', 'exportarPDF']],
        ];
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function rutasProtegidas(): array
    {
        return [
            'index almacen' => ['GET', '/api/almacen'],
            'index categoria' => ['GET', '/api/categoria'],
            'store categoria' => ['POST', '/api/categoria'],
            'index cliente' => ['GET', '/api/cliente'],
            'index compra' => ['GET', '/api/compra'],
            'reporte compras' => ['GET', '/api/compra/reporte-pdf'],
            'recibo compra' => ['GET', '/api/compra/1/generar-pdf'],
            'index permiso' => ['GET', '/api/permiso'],
            'show permiso' => ['GET', '/api/permiso/1'],
            'index producto' => ['GET', '/api/producto'],
            'exportar productos' => ['GET', '/api/producto/generar-excel'],
            'subir imagen' => ['POST', '/api/producto/1/subir-imagen'],
            'index proveedor' => ['GET', '/api/proveedor'],
            'index rol' => ['GET', '/api/rol'],
            'permisos del rol' => ['PUT', '/api/rol/1/permisos'],
            'asignar permiso' => ['POST', '/api/rol/1/permisos/1'],
            'quitar permiso' => ['DELETE', '/api/rol/1/permisos/1'],
            'index sucursal' => ['GET', '/api/sucursal'],
            'index usuario' => ['GET', '/api/usuario'],
            'index venta' => ['GET', '/api/venta'],
            'reporte ventas' => ['GET', '/api/venta/reporte-pdf'],
            'recibo venta' => ['GET', '/api/venta/1/generar-pdf'],
        ];
    }

    /**
     * @param  class-string  $controlador
     */
    private function invocarAccion(object $controlador, string $accion): void
    {
        $metodo = new ReflectionMethod($controlador, $accion);
        $argumentos = array_map(
            fn (ReflectionParameter $parametro): mixed => $this->valorPara($parametro),
            $metodo->getParameters()
        );

        $metodo->invokeArgs($controlador, $argumentos);
    }

    private function valorPara(ReflectionParameter $parametro): mixed
    {
        return match ($parametro->getType()?->getName()) {
            Request::class => Request::create('/api'),
            Role::class => Role::firstOrFail(),
            Permission::class => Permission::firstOrFail(),
            default => 1,
        };
    }
}
