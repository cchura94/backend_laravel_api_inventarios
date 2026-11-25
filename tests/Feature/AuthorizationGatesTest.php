<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthorizationGatesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesYPermisosSeeder::class);
    }

    private function usuarioConRol(string $rol): User
    {
        $usuario = User::factory()->create();
        $usuario->roles()->attach(Role::where('nombre', $rol)->firstOrFail());

        return $usuario;
    }

    public function test_el_seeder_crea_permisos_y_roles_base(): void
    {
        $this->assertDatabaseCount('permissions', 44);

        $this->assertDatabaseHas('roles', ['nombre' => 'administrador']);
        $this->assertDatabaseHas('roles', ['nombre' => 'vendedor']);
        $this->assertDatabaseHas('roles', ['nombre' => 'almacenero']);

        $administrador = Role::where('nombre', 'administrador')->firstOrFail();
        $this->assertCount(44, $administrador->permisos);
    }

    public function test_el_administrador_accede_a_cualquier_recurso(): void
    {
        Sanctum::actingAs($this->usuarioConRol('administrador'));

        $this->getJson('/api/rol')->assertOk();
        $this->getJson('/api/permiso')->assertOk();
        $this->getJson('/api/usuario')->assertOk();
        $this->getJson('/api/categoria')->assertOk();
        $this->deleteJson('/api/permiso/1')->assertOk();
    }

    public function test_un_usuario_sin_permisos_recibe_403(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/categoria')->assertForbidden();
        $this->getJson('/api/venta')->assertForbidden();
        $this->postJson('/api/cliente', ['nombre_completo' => 'X'])->assertForbidden();
    }

    public function test_un_permiso_habilita_solo_su_accion(): void
    {
        $usuario = User::factory()->create();
        $rol = Role::create(['nombre' => 'lector de categorias']);
        $rol->permisos()->attach(
            Permission::where('nombre', 'categoria.ver')->value('id')
        );
        $usuario->roles()->attach($rol->id);

        Sanctum::actingAs($usuario);

        $this->getJson('/api/categoria')->assertOk();
        $this->postJson('/api/categoria', ['nombre' => 'Nueva'])->assertForbidden();
        $this->deleteJson('/api/categoria/1')->assertForbidden();
        $this->getJson('/api/venta')->assertForbidden();
    }

    public function test_los_permisos_se_acumulan_entre_roles(): void
    {
        $usuario = User::factory()->create();

        $lector = Role::create(['nombre' => 'lector']);
        $lector->permisos()->attach(Permission::where('nombre', 'producto.ver')->value('id'));

        $editor = Role::create(['nombre' => 'editor']);
        $editor->permisos()->attach(Permission::where('nombre', 'producto.editar')->value('id'));

        $usuario->roles()->attach([$lector->id, $editor->id]);

        $this->assertTrue($usuario->tienePermiso('producto.ver'));
        $this->assertTrue($usuario->tienePermiso('producto.editar'));
        $this->assertFalse($usuario->tienePermiso('producto.eliminar'));
        $this->assertTrue($usuario->tieneAlgunPermiso(['producto.eliminar', 'producto.ver']));
        $this->assertFalse($usuario->tieneTodosLosPermisos(['producto.ver', 'producto.eliminar']));
    }

    public function test_los_gates_evaluan_permisos_y_deniegan_habilidades_inexistentes(): void
    {
        $vendedor = $this->usuarioConRol('vendedor');

        $this->assertTrue(Gate::forUser($vendedor)->allows('venta.ver'));
        $this->assertTrue(Gate::forUser($vendedor)->allows('venta.crear'));
        $this->assertTrue(Gate::forUser($vendedor)->denies('compra.crear'));
        $this->assertTrue(Gate::forUser($vendedor)->denies('habilidad.inexistente'));
    }

    public function test_las_rutas_de_reporte_estan_protegidas_por_permiso(): void
    {
        // sin permiso: el middleware corta antes de llegar al controlador
        Sanctum::actingAs(User::factory()->create());
        $this->getJson('/api/venta/reporte-pdf')->assertForbidden();
        $this->getJson('/api/compra/reporte-pdf')->assertForbidden();

        // la ruta exige el permiso y sigue dentro de auth:sanctum
        $ruta = collect(Route::getRoutes()->getRoutes())
            ->first(fn ($ruta): bool => $ruta->uri() === 'api/venta/reporte-pdf');

        $this->assertNotNull($ruta);
        $this->assertContains('can:venta.ver', $ruta->gatherMiddleware());
        $this->assertContains('auth:sanctum', $ruta->gatherMiddleware());
    }

    public function test_el_perfil_devuelve_roles_y_permisos(): void
    {
        $usuario = $this->usuarioConRol('vendedor');
        Sanctum::actingAs($usuario);

        $respuesta = $this->getJson('/api/v1/auth/profile')
            ->assertOk()
            ->assertJsonPath('name', $usuario->name)
            ->assertJsonPath('roles.0.nombre', 'vendedor');

        $this->assertContains('venta.ver', $respuesta->json('permisos'));
        $this->assertNotContains('compra.crear', $respuesta->json('permisos'));
    }
}
