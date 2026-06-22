<?php

namespace Tests\Feature;

use App\Models\Almacen;
use App\Models\Caja;
use App\Models\CategoriaProducto;
use App\Models\Cliente;
use App\Models\Inventario;
use App\Models\MetodoPago;
use App\Models\Moneda;
use App\Models\Plane;
use App\Models\Producto;
use App\Models\Suscripcion;
use App\Models\Tienda;
use App\Models\Unidad;
use App\Models\User;
use App\Models\VarianteProducto;
use Spatie\Permission\Models\Role;

trait CreatesTestData
{
    private ?Tienda $testTienda = null;
    private ?User $testUser = null;
    private ?User $testAdmin = null;
    private ?string $testToken = null;

    protected function crearTienda(array $data = []): Tienda
    {
        $tienda = Tienda::create(array_merge([
            'rif'                => 'J-12345678-9',
            'razon_social'       => 'Test Store S.A.S.',
            'nombre_comercial'   => 'Test Store',
            'direccion'          => 'Test Address 123',
            'telefono'           => '+584141234567',
            'email'              => 'store@test.com',
            'moneda_base'        => 'USD',
            'zona_horaria'       => 'America/Caracas',
            'pais'               => 'VE',
            'prefijo_factura'    => 'TST',
            'siguiente_numero'   => 1,
            'decimales_precio'   => 2,
            'activo'             => true,
            'es_agente_igtf'     => true,
            'alicuota_igtf'      => 3.0,
        ], $data));

        $this->testTienda = $tienda;
        return $tienda;
    }

    private int $userCounter = 0;

    protected function crearUsuario(string $role = 'admin', ?Tienda $tienda = null): User
    {
        $tienda ??= $this->testTienda ?? $this->crearTienda();
        $this->userCounter++;

        $user = User::create([
            'tienda_id' => $tienda->id,
            'name'      => 'Test ' . ucfirst($role),
            'email'     => strtolower($role) . '+' . $this->userCounter . '@test.com',
            'password'  => 'testpassword',
            'activo'    => true,
        ]);

        $roleModel = Role::where('name', $role)->first();
        if ($roleModel) {
            $user->assignRole($roleModel);
        }

        if ($role === 'admin') {
            $this->testAdmin = $user;
        }
        $this->testUser = $user;

        return $user;
    }

    protected function autenticar(?User $user = null): string
    {
        $user ??= $this->testUser ?? $this->crearUsuario();
        $this->actingAs($user);
        $this->testToken = $user->createToken('test-token', ['*'])->plainTextToken;
        return $this->testToken;
    }

    protected function headers(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->testToken,
            'Accept'        => 'application/json',
        ];
    }

    protected function crearSuscripcion(string $estado = 'trial', ?Tienda $tienda = null): Suscripcion
    {
        $tienda ??= $this->testTienda;
        return Suscripcion::create([
            'tienda_id'   => $tienda->id,
            'plan_id'     => 1,
            'estado'      => $estado,
            'inicio_trial'=> now()->subDays(5),
            'fin_trial'   => $estado === 'trial' ? now()->addDays(25) : now()->subDays(5),
        ]);
    }

    protected function crearMoneda(string $codigo = 'USD'): Moneda
    {
        return Moneda::firstOrCreate(
            ['codigo' => $codigo],
            ['nombre' => $codigo === 'USD' ? 'Dólar USA' : 'Bolívar', 'simbolo' => $codigo === 'USD' ? '$' : 'Bs', 'activa' => true]
        );
    }

    protected function crearUnidad(): Unidad
    {
        return Unidad::firstOrCreate(
            ['abreviatura' => 'und'],
            ['nombre' => 'Unidad', 'tipo' => 'cantidad', 'factor_conversion' => 1, 'es_vendible' => true, 'es_logistica' => true]
        );
    }

    protected function crearCategoria(): CategoriaProducto
    {
        $slug = 'test-category-' . uniqid();
        return CategoriaProducto::create([
            'tienda_id' => $this->testTienda->id,
            'nombre'    => 'Test Category',
            'slug'      => $slug,
            'activo'    => true,
        ]);
    }

    protected function crearProducto(CategoriaProducto $cat, Unidad $und): Producto
    {
        return Producto::create([
            'tienda_id'      => $this->testTienda->id,
            'codigo_sku'     => 'SKU-' . uniqid(),
            'nombre'         => 'Test Product',
            'categoria_id'   => $cat->id,
            'unidad_id'      => $und->id,
            'moneda_precio'  => 'USD',
            'activo'         => true,
            'controla_lotes' => false,
        ]);
    }

    protected function crearVariante(Producto $producto): VarianteProducto
    {
        return VarianteProducto::create([
            'tienda_id'     => $this->testTienda->id,
            'producto_id'   => $producto->id,
            'codigo'        => 'VAR-TEST-001',
            'nombre'        => 'Default',
            'precio_defecto'=> 100.00,
            'costo_promedio'=> 50.00,
            'activo'        => true,
        ]);
    }

    protected function crearAlmacen(?Tienda $tienda = null): Almacen
    {
        $tienda ??= $this->testTienda;
        return Almacen::create([
            'tienda_id'    => $tienda->id,
            'nombre'       => 'Main Warehouse',
            'activo'       => true,
        ]);
    }

    protected function crearCaja(?Tienda $tienda = null): Caja
    {
        $tienda ??= $this->testTienda;
        return Caja::create([
            'tienda_id'    => $tienda->id,
            'nombre'       => 'Caja Principal',
            'tipo'         => 'principal',
            'activo'       => true,
        ]);
    }

    protected function crearCliente(?Tienda $tienda = null): Cliente
    {
        $tienda ??= $this->testTienda;
        return Cliente::create([
            'tienda_id'     => $tienda->id,
            'nombre'        => 'Cliente Test',
            'documento'     => 'V-12345678',
            'tipo_documento'=> 'V',
            'email'         => 'cliente@test.com',
            'activo'        => true,
        ]);
    }

    protected function crearInventario(VarianteProducto $variante, Almacen $almacen, float $cantidad = 100): Inventario
    {
        return Inventario::create([
            'tienda_id'            => $almacen->tienda_id,
            'variante_id'          => $variante->id,
            'almacen_id'           => $almacen->id,
            'cantidad_disponible'  => $cantidad,
            'cantidad_reservada'   => 0,
            'stock_minimo'         => 0,
            'stock_maximo'         => 1000,
        ]);
    }

    protected function crearMetodoPago(): MetodoPago
    {
        return MetodoPago::create([
            'tienda_id'           => $this->testTienda->id,
            'nombre'              => 'Efectivo',
            'tipo'                => 'cash',
            'moneda'              => 'USD',
            'grava_igtf'          => false,
            'activo'              => true,
        ]);
    }

    protected function setUpTestData(): void
    {
        $this->crearTienda();
        $this->crearSuscripcion();
        $this->crearUsuario('admin');
        $this->autenticar();
    }

    protected function setUpVentaData(): array
    {
        $und = $this->crearUnidad();
        $cat = $this->crearCategoria();
        $prod = $this->crearProducto($cat, $und);
        $variante = $this->crearVariante($prod);
        $almacen = $this->crearAlmacen();
        $caja = $this->crearCaja();
        $cliente = $this->crearCliente();
        $this->crearInventario($variante, $almacen, 50);
        $metodoPago = $this->crearMetodoPago();

        return compact('und', 'cat', 'prod', 'variante', 'almacen', 'caja', 'cliente', 'metodoPago');
    }
}
