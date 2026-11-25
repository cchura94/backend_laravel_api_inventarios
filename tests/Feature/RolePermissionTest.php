<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    private function autenticar(): User
    {
        $usuario = User::factory()->create();

        // el rol administrador omite los Gates (ver AppServiceProvider)
        $usuario->roles()->attach(
            Role::firstOrCreate(['nombre' => User::ROL_ADMINISTRADOR])
        );

        Sanctum::actingAs($usuario);

        return $usuario;
    }

    public function test_las_rutas_de_roles_y_permisos_requieren_autenticacion(): void
    {
        $this->getJson('/api/rol')->assertUnauthorized();
        $this->getJson('/api/permiso')->assertUnauthorized();
    }

    public function test_lista_roles_con_permisos_y_busqueda(): void
    {
        $this->autenticar();

        $rol = Role::create(['nombre' => 'Almacenero', 'descripcion' => 'Gestiona almacenes']);
        $rol->permisos()->attach(Permission::create(['nombre' => 'producto.ver'])->id);
        Role::create(['nombre' => 'Vendedor', 'descripcion' => 'Atiende ventas']);

        $this->getJson('/api/rol')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.nombre', 'Vendedor')
            ->assertJsonPath('data.1.permisos.0.nombre', 'producto.ver');

        $this->getJson('/api/rol?search=almacen')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nombre', 'Almacenero');
    }

    public function test_crea_un_rol_con_permisos(): void
    {
        $this->autenticar();

        $permiso = Permission::create(['nombre' => 'venta.crear']);

        $this->postJson('/api/rol', [
            'nombre' => 'Cajero',
            'descripcion' => 'Registra ventas',
            'permisos' => [$permiso->id],
        ])
            ->assertCreated()
            ->assertJsonPath('rol.nombre', 'Cajero')
            ->assertJsonPath('rol.permisos.0.nombre', 'venta.crear');

        $this->assertDatabaseHas('roles', ['nombre' => 'Cajero']);
        $this->assertDatabaseHas('permission_role', [
            'role_id' => Role::where('nombre', 'Cajero')->value('id'),
            'permission_id' => $permiso->id,
        ]);
    }

    public function test_no_permite_roles_duplicados_ni_permisos_inexistentes(): void
    {
        $this->autenticar();

        Role::create(['nombre' => 'Admin']);

        $this->postJson('/api/rol', ['nombre' => 'Admin'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('nombre');

        $this->postJson('/api/rol', ['nombre' => 'Otro', 'permisos' => [999]])
            ->assertStatus(422)
            ->assertJsonValidationErrors('permisos.0');
    }

    public function test_muestra_un_rol(): void
    {
        $this->autenticar();

        $rol = Role::create(['nombre' => 'Supervisor']);
        $rol->permisos()->attach(Permission::create(['nombre' => 'reporte.ver'])->id);

        $this->getJson("/api/rol/{$rol->id}")
            ->assertOk()
            ->assertJsonPath('nombre', 'Supervisor')
            ->assertJsonPath('permisos.0.nombre', 'reporte.ver');
    }

    public function test_actualiza_un_rol_y_reemplaza_sus_permisos(): void
    {
        $this->autenticar();

        $rol = Role::create(['nombre' => 'Auxiliar']);
        $viejo = Permission::create(['nombre' => 'producto.ver']);
        $nuevo = Permission::create(['nombre' => 'producto.editar']);
        $rol->permisos()->attach($viejo->id);

        $this->putJson("/api/rol/{$rol->id}", [
            'nombre' => 'Auxiliar de inventario',
            'descripcion' => 'Actualizado',
            'permisos' => [$nuevo->id],
        ])
            ->assertOk()
            ->assertJsonPath('rol.nombre', 'Auxiliar de inventario')
            ->assertJsonPath('rol.permisos.0.nombre', 'producto.editar');

        $this->assertDatabaseMissing('permission_role', [
            'role_id' => $rol->id,
            'permission_id' => $viejo->id,
        ]);
    }

    public function test_elimina_un_rol_y_sus_relaciones(): void
    {
        $usuario = $this->autenticar();

        $rol = Role::create(['nombre' => 'Temporal']);
        $rol->permisos()->attach(Permission::create(['nombre' => 'temp.ver'])->id);
        $rol->users()->attach($usuario->id);

        $this->deleteJson("/api/rol/{$rol->id}")->assertOk();

        $this->assertDatabaseMissing('roles', ['id' => $rol->id]);
        $this->assertDatabaseMissing('permission_role', ['role_id' => $rol->id]);
        $this->assertDatabaseMissing('role_user', ['role_id' => $rol->id]);
    }

    public function test_asigna_reemplaza_y_quita_permisos_de_un_rol(): void
    {
        $this->autenticar();

        $rol = Role::create(['nombre' => 'Encargado']);
        $ver = Permission::create(['nombre' => 'cliente.ver']);
        $editar = Permission::create(['nombre' => 'cliente.editar']);

        // asignar uno sin quitar los existentes
        $this->postJson("/api/rol/{$rol->id}/permisos/{$ver->id}")
            ->assertOk()
            ->assertJsonPath('rol.permisos.0.nombre', 'cliente.ver');

        $this->postJson("/api/rol/{$rol->id}/permisos/{$editar->id}")->assertOk();
        $this->assertCount(2, $rol->permisos()->get());

        // reemplazar el conjunto completo
        $this->putJson("/api/rol/{$rol->id}/permisos", ['permisos' => [$editar->id]])
            ->assertOk();
        $this->assertDatabaseMissing('permission_role', [
            'role_id' => $rol->id,
            'permission_id' => $ver->id,
        ]);

        // quitar uno
        $this->deleteJson("/api/rol/{$rol->id}/permisos/{$editar->id}")->assertOk();
        $this->assertCount(0, $rol->permisos()->get());
    }

    public function test_crud_de_permisos(): void
    {
        $this->autenticar();

        $this->postJson('/api/permiso', [
            'nombre' => 'compra.ver',
            'descripcion' => 'Ver compras',
        ])
            ->assertCreated()
            ->assertJsonPath('permiso.nombre', 'compra.ver');

        $permiso = Permission::where('nombre', 'compra.ver')->firstOrFail();

        $this->postJson('/api/permiso', ['nombre' => 'compra.ver'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('nombre');

        $this->getJson('/api/permiso?search=COMPRA')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nombre', 'compra.ver');

        $this->getJson("/api/permiso/{$permiso->id}")
            ->assertOk()
            ->assertJsonPath('nombre', 'compra.ver');

        $this->putJson("/api/permiso/{$permiso->id}", ['nombre' => 'compra.listar'])
            ->assertOk()
            ->assertJsonPath('permiso.nombre', 'compra.listar');

        $rol = Role::create(['nombre' => 'Compras']);
        $rol->permisos()->attach($permiso->id);

        $this->deleteJson("/api/permiso/{$permiso->id}")->assertOk();

        $this->assertDatabaseMissing('permissions', ['id' => $permiso->id]);
        $this->assertDatabaseMissing('permission_role', ['permission_id' => $permiso->id]);
    }
}
