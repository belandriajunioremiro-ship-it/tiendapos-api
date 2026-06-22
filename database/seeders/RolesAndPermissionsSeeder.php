<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ── PERMISOS ────────────────────────────────────────────────────────
        $permisos = [
            // Tienda
            'ver_tienda', 'editar_tienda',

            // Dashboard / Reportes
            'ver_dashboard', 'ver_reportes',

            // Productos / Catálogos
            'ver_productos',    'crear_productos',   'editar_productos',   'eliminar_productos',
            'ver_categorias',   'crear_categorias',  'editar_categorias',  'eliminar_categorias',
            'ver_impuestos',    'crear_impuestos',   'editar_impuestos',
            'ver_margenes',     'editar_margenes',
            'ver_listas_precio','editar_listas_precio',

            // Clientes
            'ver_clientes',    'crear_clientes',    'editar_clientes',    'eliminar_clientes',

            // Proveedores
            'ver_proveedores', 'crear_proveedores', 'editar_proveedores', 'eliminar_proveedores',

            // Inventario
            'ver_inventario',    'ajustar_inventario',
            'ver_almacenes',     'crear_almacenes',    'editar_almacenes',
            'ver_traslados',     'crear_traslados',

            // Compras
            'ver_compras',   'crear_compras',   'aprobar_compras',
            'ver_recepciones','confirmar_recepciones',

            // Ventas / POS
            'ver_ventas',   'crear_venta',   'anular_venta',
            'ver_cotizaciones', 'crear_cotizacion',
            'aplicar_descuento',

            // Devoluciones
            'ver_devoluciones', 'crear_devolucion', 'aprobar_devolucion',

            // Caja
            'ver_caja',  'abrir_caja',  'cerrar_caja',
            'ver_movimientos_caja', 'registrar_retiro', 'registrar_gasto',

            // Créditos
            'ver_creditos', 'crear_credito', 'registrar_abono', 'suspender_credito',

            // Tasas de cambio
            'ver_tasas',   'crear_tasa',

            // Usuarios
            'ver_usuarios', 'crear_usuarios', 'editar_usuarios', 'eliminar_usuarios',

            // Métodos de pago
            'ver_metodos_pago', 'editar_metodos_pago',

            // Descuentos
            'ver_descuentos', 'crear_descuentos', 'editar_descuentos',
        ];

        foreach ($permisos as $permiso) {
            Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']);
        }

        // ── ROL: ADMIN (acceso total) ────────────────────────────────────────
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions(Permission::all());

        // ── ROL: SUPERVISOR (ventas + inventario + caja + reportes) ──────
        $supervisor = Role::firstOrCreate(['name' => 'supervisor', 'guard_name' => 'web']);
        $supervisor->syncPermissions([
            'ver_dashboard',

            // Productos (CRUD completo)
            'ver_productos', 'crear_productos', 'editar_productos',
            'ver_categorias', 'crear_categorias', 'editar_categorias',
            'ver_impuestos',
            'ver_margenes', 'editar_margenes',
            'ver_listas_precio', 'editar_listas_precio',

            // Clientes (CRUD completo)
            'ver_clientes', 'crear_clientes', 'editar_clientes', 'eliminar_clientes',

            // Proveedores (solo ver)
            'ver_proveedores',

            // Inventario (ver + ajustar)
            'ver_inventario', 'ajustar_inventario',
            'ver_almacenes', 'ver_traslados', 'crear_traslados',

            // Compras (ver + crear)
            'ver_compras', 'crear_compras',

            // Ventas / POS
            'ver_ventas', 'crear_venta', 'anular_venta', 'aplicar_descuento',
            'ver_cotizaciones', 'crear_cotizacion',

            // Devoluciones
            'ver_devoluciones', 'crear_devolucion', 'aprobar_devolucion',

            // Caja
            'ver_caja', 'abrir_caja', 'cerrar_caja',
            'ver_movimientos_caja', 'registrar_retiro', 'registrar_gasto',

            // Créditos
            'ver_creditos', 'crear_credito', 'registrar_abono',

            // Reportes
            'ver_reportes',

            // Tasas
            'ver_tasas', 'crear_tasa',

            // Métodos de pago y descuentos
            'ver_metodos_pago', 'ver_descuentos',
        ]);

        // ── ROL: CAJERO (ventas, caja, clientes, inventario, cotizaciones) ──
        $cajero = Role::firstOrCreate(['name' => 'cajero', 'guard_name' => 'web']);
        $cajero->syncPermissions([
            'ver_dashboard',

            'ver_productos', 'ver_categorias',
            'ver_inventario',

            'ver_clientes', 'crear_clientes', 'editar_clientes',

            'ver_ventas', 'crear_venta', 'aplicar_descuento',
            'ver_cotizaciones', 'crear_cotizacion',

            'ver_devoluciones', 'crear_devolucion',

            'ver_caja', 'abrir_caja', 'cerrar_caja',
            'ver_movimientos_caja', 'registrar_retiro', 'registrar_gasto',

            'ver_creditos', 'registrar_abono',

            'ver_tasas',
            'ver_metodos_pago',
            'ver_descuentos',
        ]);

        // ── USUARIO ADMIN POR DEFECTO ────────────────────────────────────────
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@tiendapos.com'],
            [
                'name'     => 'Administrador',
                'password' => bcrypt('password'),
                'activo'   => true,
            ]
        );
        $adminUser->assignRole('admin');

        $this->command->info('✅  Roles y permisos creados. Admin: admin@tiendapos.com / password');
    }
}
