<?php

use App\Models\Tienda;
use App\Models\User;
use App\Models\Producto;
use App\Models\CategoriaProducto;
use App\Models\Unidad;
use Illuminate\Support\Facades\Auth;

echo PHP_EOL . '=== PREPARANDO TEST DE MULTI-TENANCY ===' . PHP_EOL;

$tiendaA = Tienda::first();

if (!$tiendaA) {
    echo "ERROR: No existe ninguna tienda. Corre el seeder primero." . PHP_EOL;
    exit(1);
}

$tiendaB = Tienda::create([
    'pais' => 'CO',
    'rif' => 'NIT-TEST-99',
    'razon_social' => 'Tienda B Test',
    'moneda_base' => 'COP',
    'zona_horaria' => 'America/Bogota',
    'es_agente_igtf' => false,
    'activo' => true,
]);

echo 'Tienda A (ID ' . $tiendaA->id . '): ' . $tiendaA->razon_social . PHP_EOL;
echo 'Tienda B (ID ' . $tiendaB->id . '): ' . $tiendaB->razon_social . PHP_EOL;

$userB = User::firstOrCreate(
    ['email' => 'test.tiendaB@demo.com'],
    [
        'tienda_id' => $tiendaB->id,
        'name' => 'Admin Tienda B',
        'password' => bcrypt('Test1234'),
        'activo' => true,
    ]
);
$userB->assignRole('admin');

Auth::login($userB);
echo PHP_EOL . 'Logueado como: ' . Auth::user()->email . ' (Tienda ID ' . Auth::user()->tienda_id . ')' . PHP_EOL;

echo PHP_EOL . '=== CREANDO DATOS PARA TIENDA B ===' . PHP_EOL;

$catB = CategoriaProducto::create([
    'nombre' => 'Categoria Tienda B',
    'slug' => 'cat-tienda-b',
]);
echo 'Categoria B creada (ID ' . $catB->id . ', tienda_id ' . $catB->tienda_id . ')' . PHP_EOL;

$unidadB = Unidad::create([
    'nombre' => 'Unidad B',
    'abreviatura' => 'UND',
    'tipo' => 'cantidad',
]);
echo 'Unidad B creada (ID ' . $unidadB->id . ', tienda_id ' . $unidadB->tienda_id . ')' . PHP_EOL;

echo PHP_EOL . '=== CREANDO PRODUCTO COMO TIENDA B ===' . PHP_EOL;

$prodB = Producto::create([
    'categoria_id' => $catB->id,
    'unidad_id' => $unidadB->id,
    'moneda_precio' => 'COP',
    'codigo_sku' => 'TEST-B-' . time(),
    'nombre' => 'Producto Secreto Tienda B',
    'costo_promedio' => 5000,
    'margen_pct' => 20,
]);

echo 'Producto creado con ID: ' . $prodB->id . PHP_EOL;
echo 'tienda_id del producto: ' . $prodB->tienda_id . PHP_EOL;

$userA = User::where('tienda_id', $tiendaA->id)->first();
Auth::login($userA);
echo PHP_EOL . 'Logueado como: ' . Auth::user()->email . ' (Tienda ID ' . Auth::user()->tienda_id . ')' . PHP_EOL;

echo PHP_EOL . '=== RESULTADO DEL TEST DE AISLAMIENTO ===' . PHP_EOL;

$visiblesA = Producto::count();
$totalReal = Producto::withoutGlobalScope('tienda')->count();
$prodBuscado = Producto::where('nombre', 'Producto Secreto Tienda B')->first();
$prodBuscadoSinScope = Producto::withoutGlobalScope('tienda')->where('nombre', 'Producto Secreto Tienda B')->first();

echo PHP_EOL;
echo 'Total productos visibles por Tienda A: ' . $visiblesA . PHP_EOL;
echo 'Total productos reales en DB:          ' . $totalReal . PHP_EOL;
echo PHP_EOL;

if ($prodBuscado === null) {
    echo '✅ PASO: Tienda A NO puede ver el producto de Tienda B' . PHP_EOL;
} else {
    echo '❌ FALLO: Tienda A puede ver el producto de Tienda B (Fuga de datos!)' . PHP_EOL;
}

if ($prodBuscadoSinScope) {
    echo '✅ PASO: El producto SI existe en la DB (sin scope se ve)' . PHP_EOL;
} else {
    echo '❌ FALLO: El producto no se creo' . PHP_EOL;
}

echo PHP_EOL . '=== LIMPIEZA ===' . PHP_EOL;
Producto::withoutGlobalScope('tienda')->where('id', $prodB->id)->delete();
CategoriaProducto::withoutGlobalScope('tienda')->where('id', $catB->id)->delete();
Unidad::withoutGlobalScope('tienda')->where('id', $unidadB->id)->delete();
$userB->delete();
$tiendaB->delete();
Auth::logout();

echo '✓ Datos de prueba eliminados' . PHP_EOL;
echo PHP_EOL;
