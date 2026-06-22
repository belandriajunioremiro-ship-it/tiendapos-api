<?php

namespace Tests\Feature;

use App\Models\CategoriaProducto;
use App\Models\Suscripcion;
use App\Models\Tienda;
use App\Models\Unidad;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ProductoControllerTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;
    private Tienda $tienda;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->tienda = Tienda::create([
            'rif' => 'J-99999999-9',
            'razon_social' => 'Product Test Store',
            'nombre_comercial' => 'Product Test Store',
            'moneda_base' => 'USD',
            'prefijo_factura' => 'PRT',
            'siguiente_numero' => 1,
            'zona_horaria' => 'America/Caracas',
            'pais' => 'VE',
            'activo' => true,
        ]);
        Suscripcion::create([
            'tienda_id'   => $this->tienda->id,
            'plan_id'     => 1,
            'estado'      => 'trial',
            'inicio_trial'=> now()->subDays(5),
            'fin_trial'   => now()->addDays(25),
        ]);

        $this->user = User::factory()->create([
            'tienda_id' => $this->tienda->id,
            'activo' => true,
        ]);

        $this->user->assignRole('admin');
    }

    public function test_index_lista_productos(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/v1/productos');

        $response->assertOk();
    }

    public function test_store_crea_producto(): void
    {
        $categoria = CategoriaProducto::create([
            'nombre' => 'General',
            'slug' => 'general',
            'tienda_id' => $this->tienda->id,
        ]);

        $unidad = Unidad::create([
            'nombre' => 'Unidad',
            'abreviatura' => 'UND',
            'tipo' => 'cantidad',
            'tienda_id' => $this->tienda->id,
        ]);

        $payload = [
            'categoria_id' => $categoria->id,
            'unidad_id' => $unidad->id,
            'moneda_precio' => 'USD',
            'codigo_sku' => 'SKU-001',
            'nombre' => 'Producto Test',
            'costo_promedio' => 10.00,
            'margen_pct' => 50,
        ];

        $response = $this->actingAs($this->user)->postJson('/api/v1/productos', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.nombre', 'Producto Test');
    }

    public function test_store_valida_campos_requeridos(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/productos', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['codigo_sku', 'nombre', 'categoria_id', 'unidad_id', 'costo_promedio', 'margen_pct']);
    }

    public function test_show_retorna_producto(): void
    {
        $categoria = CategoriaProducto::create([
            'nombre' => 'General',
            'slug' => 'general',
            'tienda_id' => $this->tienda->id,
        ]);

        $unidad = Unidad::create([
            'nombre' => 'Unidad',
            'abreviatura' => 'UND',
            'tipo' => 'cantidad',
            'tienda_id' => $this->tienda->id,
        ]);

        $producto = \App\Models\Producto::create([
            'tienda_id' => $this->tienda->id,
            'categoria_id' => $categoria->id,
            'unidad_id' => $unidad->id,
            'moneda_precio' => 'USD',
            'codigo_sku' => 'SKU-SHOW',
            'nombre' => 'Producto Show',
            'costo_promedio' => 15.00,
            'margen_pct' => 50,
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/productos/{$producto->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $producto->id);
    }

    public function test_destroy_desactiva_producto(): void
    {
        $categoria = CategoriaProducto::create([
            'nombre' => 'General',
            'slug' => 'general-del',
            'tienda_id' => $this->tienda->id,
        ]);

        $unidad = Unidad::create([
            'nombre' => 'Unidad',
            'abreviatura' => 'UND',
            'tipo' => 'cantidad',
            'tienda_id' => $this->tienda->id,
        ]);

        $producto = \App\Models\Producto::create([
            'tienda_id' => $this->tienda->id,
            'categoria_id' => $categoria->id,
            'unidad_id' => $unidad->id,
            'moneda_precio' => 'USD',
            'codigo_sku' => 'SKU-DEL',
            'nombre' => 'Producto Delete',
            'costo_promedio' => 5.00,
            'margen_pct' => 30,
        ]);

        $response = $this->actingAs($this->user)->deleteJson("/api/v1/productos/{$producto->id}");

        $response->assertOk();
        $this->assertFalse($producto->fresh()->activo);
    }
}
