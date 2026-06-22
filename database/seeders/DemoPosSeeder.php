<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Tienda;
use App\Models\TasaCambio;
use App\Models\MetodoPago;
use App\Models\Almacen;
use App\Models\Caja;
use App\Models\Cliente;
use App\Models\Proveedor;
use App\Models\Unidad;
use App\Models\CategoriaProducto;
use App\Models\Producto;
use App\Models\VarianteProducto;
use App\Models\Inventario;
use App\Models\User;

class DemoPosSeeder extends Seeder
{
    public function run()
    {
        $this->command->info('Borrando datos antiguos de forma segura...');
        
        $tables = [
            'items_devolucion', 'devoluciones_venta', 'pagos_venta', 'items_venta',
            'abonos_credito', 'facturas_credito', 'cuentas_credito', 'ventas',
            'items_recepcion', 'recepciones_compra', 'facturas_proveedor',
            'items_orden_compra', 'ordenes_compra', 'items_ajuste', 'ajustes_inventario',
            'items_traslado', 'traslados_stock', 'movimientos_inventario', 'inventario',
            'movimientos_caja', 'sesiones_caja', 'cajas', 'variantes_producto',
            'producto_proveedor', 'productos', 'margenes_ganancia', 'definicion_atributos',
            'categorias_productos', 'unidades', 'clientes', 'proveedores',
            'metodos_pago', 'tasas_cambio', 'almacenes'
        ];

        foreach ($tables as $table) {
            DB::table($table)->delete();
        }

        $this->command->info('Configurando Tienda y Tasas de Cambio...');
        
        $tienda = Tienda::first();
        if (!$tienda) {
            $tienda = Tienda::create([
                'nombre' => 'TIENDAPOS MEGA STORE',
                'tipo_documento' => 'J',
                'numero_documento' => '123456789',
                'moneda_base' => 'USD',
                'prefijo_factura' => 'POS1'
            ]);
        }
        $tienda->update([
            'es_agente_igtf' => true,
            'alicuota_igtf'  => 3.00,
            'moneda_base'    => 'USD'
        ]);

        TasaCambio::create([
            'moneda_base' => 'USD',
            'moneda_destino' => 'VES',
            'tasa' => 592.51,
            'fecha' => now()->toDateString(),
            'activa' => true
        ]);

        $this->command->info('Creando Métodos de Pago...');
        MetodoPago::create(['nombre' => 'Efectivo USD', 'tipo' => 'efectivo', 'moneda' => 'USD', 'requiere_referencia' => false, 'grava_igtf' => true]);
        MetodoPago::create(['nombre' => 'Zelle', 'tipo' => 'transferencia', 'moneda' => 'USD', 'requiere_referencia' => true, 'grava_igtf' => true]);
        MetodoPago::create(['nombre' => 'Efectivo VES', 'tipo' => 'efectivo', 'moneda' => 'VES', 'requiere_referencia' => false, 'grava_igtf' => false]);
        $pagoMovil = MetodoPago::create(['nombre' => 'Pago Móvil VES', 'tipo' => 'pago_movil', 'moneda' => 'VES', 'requiere_referencia' => true, 'grava_igtf' => false]);

        $almacen = Almacen::create(['nombre' => 'Depósito Principal', 'tipo' => 'principal']);
        Caja::create(['nombre' => 'Caja Principal USD']);

        $this->command->info('Creando Clientes y Proveedores...');
        $cliente1 = Cliente::create(['nombre' => 'Consumidor Final', 'numero_documento' => '00000000', 'tipo_documento' => 'V', 'moneda_credito' => 'USD']);
        Cliente::create(['nombre' => 'Corporación X', 'numero_documento' => '123123123', 'tipo_documento' => 'J', 'moneda_credito' => 'USD']);
        Proveedor::create(['razon_social' => 'Distribuidora Farmacéutica C.A.', 'tipo_documento' => 'J', 'moneda_compra' => 'USD']);
        Proveedor::create(['razon_social' => 'Ferretería Industrial', 'tipo_documento' => 'J', 'moneda_compra' => 'USD']);

        $this->command->info('Creando Unidades...');
        $uUnd = Unidad::create(['nombre' => 'Unidad', 'abreviatura' => 'Und', 'tipo' => 'unidad']);
        $uCaja10 = Unidad::create(['nombre' => 'Caja x10', 'abreviatura' => 'Cjx10', 'tipo' => 'paquete', 'factor_conversion' => 10, 'base_id' => $uUnd->id]);
        $uCaja12 = Unidad::create(['nombre' => 'Caja x12', 'abreviatura' => 'Cjx12', 'tipo' => 'paquete', 'factor_conversion' => 12, 'base_id' => $uUnd->id]);
        $uMetro = Unidad::create(['nombre' => 'Metro', 'abreviatura' => 'm', 'tipo' => 'longitud']);
        $uLitro = Unidad::create(['nombre' => 'Litro', 'abreviatura' => 'L', 'tipo' => 'volumen']);

        $this->command->info('Creando Categorías y Productos Ultra-Realistas...');
        
        $catFarma = CategoriaProducto::create(['nombre' => 'Farmacia', 'slug' => 'farmacia', 'nivel' => 1, 'ruta' => '1']);
        $catFerre = CategoriaProducto::create(['nombre' => 'Ferretería', 'slug' => 'ferreteria', 'nivel' => 1, 'ruta' => '2']);
        $catLico = CategoriaProducto::create(['nombre' => 'Licorería', 'slug' => 'licoreria', 'nivel' => 1, 'ruta' => '3']);
        $catSuper = CategoriaProducto::create(['nombre' => 'Supermercado', 'slug' => 'supermercado', 'nivel' => 1, 'ruta' => '4']);
        $catMotos = CategoriaProducto::create(['nombre' => 'Repuestos Motos', 'slug' => 'repuestos-motos', 'nivel' => 1, 'ruta' => '5']);

        $productos = [
            // Repuestos Motos (Precios 2026 Venezuela)
            ['cat' => $catMotos, 'nombre' => 'Caucho Trasero 18" Bera SBR', 'sku' => 'MOT-001', 'precio' => 40.00, 'costo' => 25.00, 'unidad' => $uUnd, 'stock' => 50],
            ['cat' => $catMotos, 'nombre' => 'Batería Seca 12V 7Ah', 'sku' => 'MOT-002', 'precio' => 25.00, 'costo' => 15.00, 'unidad' => $uUnd, 'stock' => 80],
            ['cat' => $catMotos, 'nombre' => 'Kit de Arrastre Bera/Horse', 'sku' => 'MOT-003', 'precio' => 20.00, 'costo' => 12.00, 'unidad' => $uUnd, 'stock' => 100],
            ['cat' => $catMotos, 'nombre' => 'Aceite Mineral Motul 20W50 4T 1L', 'sku' => 'MOT-004', 'precio' => 12.00, 'costo' => 7.50, 'unidad' => $uLitro, 'stock' => 200],
            ['cat' => $catMotos, 'nombre' => 'Bujía NGK Original', 'sku' => 'MOT-005', 'precio' => 4.00, 'costo' => 2.00, 'unidad' => $uUnd, 'stock' => 300],
            ['cat' => $catMotos, 'nombre' => 'Pastillas de Freno Delantero', 'sku' => 'MOT-006', 'precio' => 8.00, 'costo' => 4.50, 'unidad' => $uUnd, 'stock' => 150],
            ['cat' => $catMotos, 'nombre' => 'Tripa 18" Reforzada', 'sku' => 'MOT-007', 'precio' => 6.00, 'costo' => 3.50, 'unidad' => $uUnd, 'stock' => 120],

            // Supermercado (Precios 2026 Venezuela)
            ['cat' => $catSuper, 'nombre' => 'Harina P.A.N. Mezcla Maíz 1kg', 'sku' => 'SUP-001', 'precio' => 1.20, 'costo' => 0.85, 'unidad' => $uUnd, 'stock' => 500],
            ['cat' => $catSuper, 'nombre' => 'Arroz Blanco Mary 1kg', 'sku' => 'SUP-002', 'precio' => 1.10, 'costo' => 0.70, 'unidad' => $uUnd, 'stock' => 400],
            ['cat' => $catSuper, 'nombre' => 'Queso Blanco Llanero 1kg', 'sku' => 'SUP-003', 'precio' => 4.50, 'costo' => 3.00, 'unidad' => $uUnd, 'stock' => 100],
            ['cat' => $catSuper, 'nombre' => 'Cartón de Huevos (30 und)', 'sku' => 'SUP-004', 'precio' => 4.00, 'costo' => 2.80, 'unidad' => $uUnd, 'stock' => 150],
            ['cat' => $catSuper, 'nombre' => 'Café Amanecer 500g', 'sku' => 'SUP-005', 'precio' => 4.80, 'costo' => 3.20, 'unidad' => $uUnd, 'stock' => 200],
            ['cat' => $catSuper, 'nombre' => 'Aceite de Maíz Mazeite 1L', 'sku' => 'SUP-006', 'precio' => 2.80, 'costo' => 1.90, 'unidad' => $uLitro, 'stock' => 300],
            ['cat' => $catSuper, 'nombre' => 'Pasta Mary 500g', 'sku' => 'SUP-007', 'precio' => 0.90, 'costo' => 0.50, 'unidad' => $uUnd, 'stock' => 600],
            ['cat' => $catSuper, 'nombre' => 'Mantequilla Mavesa 500g', 'sku' => 'SUP-008', 'precio' => 2.20, 'costo' => 1.50, 'unidad' => $uUnd, 'stock' => 250],
            
            // Farmacia
            ['cat' => $catFarma, 'nombre' => 'Amoxicilina 500mg', 'sku' => 'FAR-001', 'precio' => 5.00, 'costo' => 2.00, 'unidad' => $uCaja10, 'stock' => 100],
            ['cat' => $catFarma, 'nombre' => 'Ibuprofeno 400mg', 'sku' => 'FAR-002', 'precio' => 3.50, 'costo' => 1.50, 'unidad' => $uUnd, 'stock' => 200],
            ['cat' => $catFarma, 'nombre' => 'Losartán Potásico', 'sku' => 'FAR-003', 'precio' => 8.00, 'costo' => 4.00, 'unidad' => $uCaja10, 'stock' => 50],
            ['cat' => $catFarma, 'nombre' => 'Jarabe para Tos', 'sku' => 'FAR-004', 'precio' => 4.50, 'costo' => 2.20, 'unidad' => $uUnd, 'stock' => 80],
            ['cat' => $catFarma, 'nombre' => 'Vitamina C', 'sku' => 'FAR-005', 'precio' => 6.00, 'costo' => 3.00, 'unidad' => $uUnd, 'stock' => 150],
            // Ferretería
            ['cat' => $catFerre, 'nombre' => 'Cable THW #12', 'sku' => 'FER-001', 'precio' => 1.20, 'costo' => 0.60, 'unidad' => $uMetro, 'stock' => 1000],
            ['cat' => $catFerre, 'nombre' => 'Martillo Truper', 'sku' => 'FER-002', 'precio' => 12.00, 'costo' => 7.00, 'unidad' => $uUnd, 'stock' => 30],
            ['cat' => $catFerre, 'nombre' => 'Clavos 2 Pulgadas', 'sku' => 'FER-003', 'precio' => 2.50, 'costo' => 1.00, 'unidad' => $uUnd, 'stock' => 500],
            ['cat' => $catFerre, 'nombre' => 'Cemento Portland', 'sku' => 'FER-004', 'precio' => 8.50, 'costo' => 6.00, 'unidad' => $uUnd, 'stock' => 200],
            ['cat' => $catFerre, 'nombre' => 'Tubo PVC 1/2', 'sku' => 'FER-005', 'precio' => 3.00, 'costo' => 1.50, 'unidad' => $uUnd, 'stock' => 300],
            // Licorería
            ['cat' => $catLico, 'nombre' => 'Ron Santa Teresa 1796', 'sku' => 'LIC-001', 'precio' => 35.00, 'costo' => 22.00, 'unidad' => $uUnd, 'stock' => 40],
            ['cat' => $catLico, 'nombre' => 'Cerveza Polar Pilsen (Caja)', 'sku' => 'LIC-002', 'precio' => 15.00, 'costo' => 10.00, 'unidad' => $uCaja12, 'stock' => 60],
            ['cat' => $catLico, 'nombre' => 'Whisky Buchanan 12 Años', 'sku' => 'LIC-003', 'precio' => 45.00, 'costo' => 30.00, 'unidad' => $uUnd, 'stock' => 25],
            ['cat' => $catLico, 'nombre' => 'Vodka Gordon', 'sku' => 'LIC-004', 'precio' => 12.00, 'costo' => 8.00, 'unidad' => $uUnd, 'stock' => 50],
            ['cat' => $catLico, 'nombre' => 'Vino Tinto Casillero', 'sku' => 'LIC-005', 'precio' => 14.00, 'costo' => 9.00, 'unidad' => $uUnd, 'stock' => 45],
        ];

        foreach ($productos as $p) {
            $margen_pct = (($p['precio'] / $p['costo']) - 1) * 100;
            $prod = Producto::create([
                'codigo_sku' => $p['sku'],
                'nombre' => $p['nombre'],
                'categoria_id' => $p['cat']->id,
                'unidad_id' => $p['unidad']->id,
                'moneda_precio' => 'USD',
                'costo_promedio' => $p['costo'],
                'margen_pct' => $margen_pct,
            ]);

            $var = VarianteProducto::create([
                'producto_id' => $prod->id,
                'codigo_barra' => $p['sku'] . '-BAR',
                'descripcion' => $p['nombre'] . ' (Default)',
                'factor_unidad' => 1,
            ]);

            Inventario::create([
                'variante_id' => $var->id,
                'almacen_id' => $almacen->id,
                'cantidad_disponible' => $p['stock'],
                'costo_promedio' => $p['costo'],
            ]);
        }

        $this->command->info('✅ MEGA-SEEDER COMPLETADO CON ÉXITO.');
    }
}
