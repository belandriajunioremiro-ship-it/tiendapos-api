# TiendaPOS — Guía de Instalación y Estructura

**Stack:** Laravel 11 · Sanctum · Spatie Permission · PostgreSQL (Neon) · Next.js 15 · Tailwind CSS · shadcn/ui  
**Modelo:** Una sola tienda · 3 roles: `admin`, `supervisor` y `cajero`

---

## Índice

1. [Requisitos previos](#1-requisitos-previos)
2. [Base de datos (PostgreSQL / Neon)](#2-base-de-datos-postgresql--neon)
3. [Backend — Laravel 11](#3-backend--laravel-11)
4. [Frontend — Next.js 15](#4-frontend--nextjs-15)
5. [Roles y permisos (Spatie)](#5-roles-y-permisos-spatie)
6. [Estructura de carpetas Backend](#6-estructura-de-carpetas-backend)
7. [Estructura de carpetas Frontend](#7-estructura-de-carpetas-frontend)
8. [Variables de entorno](#8-variables-de-entorno)
9. [Comandos útiles](#9-comandos-útiles)

---

## 1. Requisitos previos

| Herramienta | Versión mínima |
|-------------|----------------|
| PHP         | 8.2+           |
| Composer    | 2.x            |
| Node.js     | 20+            |
| npm / pnpm  | npm 10+ / pnpm 9+ |
| PostgreSQL  | 15+ (Neon OK) |

---

## 2. Base de datos (PostgreSQL / Neon)

### 2.1 Crear la base de datos en Neon

1. Entrar a [neon.tech](https://neon.tech) → crear proyecto → anotar el **connection string**.
2. Abrir la consola SQL de Neon (o cualquier cliente PostgreSQL):

```sql
-- Ejecutar una sola vez el schema completo:
\i tiendapos_v1.sql
```

> El script ya incluye `CREATE EXTENSION IF NOT EXISTS "ltree"` y `"pg_trgm"`.  
> Neon los soporta nativamente, no requiere acción adicional.

### 2.2 Tablas creadas por el script

El script `tiendapos_v1.sql` crea **36 tablas de negocio** (sin tenancy):

```
tienda                  ← configuración única de la tienda
unidades                ← unidades de medida con conversión
impuestos               ← catálogo de impuestos (is_defecto = TRUE para herencia)
tasas_cambio            ← historial diario USD/VES
categorias_productos    ← árbol jerárquico con ltree
definicion_atributos    ← campos JSONB dinámicos por categoría
margenes_ganancia       ← márgenes configurables
listas_precio           ← listas por segmento (detal, mayor, empleado)
productos               ← catálogo maestro (precio_base = columna generada)
variantes_producto      ← presentaciones, lotes, tallas
clientes                ← con condiciones de crédito
proveedores             ← con datos fiscales
producto_proveedor      ← relación producto ↔ proveedor
almacenes               ← sin sucursal_id
inventario              ← stock en tiempo real
movimientos_inventario  ← bitácora particionada (2025-2027)
ajustes_inventario      ← conteos físicos, mermas
items_ajuste
traslados_stock         ← entre almacenes
items_traslado
ordenes_compra
items_orden_compra
recepciones_compra
items_recepcion
facturas_proveedor
metodos_pago
cajas                   ← sin sucursal_id
sesiones_caja           ← turno por cajero
movimientos_caja
descuentos
ventas
items_venta
pagos_venta             ← soporte pago mixto
devoluciones_venta
items_devolucion
cuentas_credito         ← sin sucursal_id
facturas_credito
abonos_credito
auditoria               ← particionada 2025-2028
notificaciones
plantillas_impresion
```

> **Las tablas de usuarios y roles las crea Laravel/Spatie** con `php artisan migrate`.

---

## 3. Backend — Laravel 11

### 3.1 Instalación

```bash
# Clonar o crear el proyecto
composer create-project laravel/laravel tiendapos-api "^11.0"
cd tiendapos-api

# Instalar dependencias
composer require laravel/sanctum
composer require spatie/laravel-permission
```

### 3.2 Publicar configs y correr migraciones

```bash
# Sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"

# Spatie Permission
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"

# Correr SOLO las migraciones de Laravel (users, roles, permissions, etc.)
# El schema de negocio ya está en PostgreSQL desde el .sql
php artisan migrate
```

### 3.3 Configurar `config/auth.php`

```php
'guards' => [
    'web' => [
        'driver'   => 'session',
        'provider' => 'users',
    ],
    'api' => [
        'driver'   => 'sanctum',
        'provider' => 'users',
    ],
],
```

### 3.4 Modelo User — agregar traits

```php
// app/Models/User.php
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $guard_name = 'web'; // importante para Spatie con Sanctum

    protected $fillable = [
        'name', 'email', 'password', 'activo',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'activo'            => 'boolean',
        ];
    }
}
```

### 3.5 Correr el Seeder de roles y permisos

```bash
php artisan db:seed --class=RolesAndPermissionsSeeder
```

Ver sección [5. Roles y permisos](#5-roles-y-permisos-spatie) para el código del seeder.

### 3.6 Agregar columna `activo` a users

Crear la migración:

```bash
php artisan make:migration add_activo_to_users_table --table=users
```

```php
// database/migrations/xxxx_add_activo_to_users_table.php
public function up(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->boolean('activo')->default(true)->after('email');
    });
}
```

```bash
php artisan migrate
```

### 3.7 Configurar CORS (`config/cors.php`)

```php
return [
    'paths'                => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods'      => ['*'],
    'allowed_origins'      => ['http://localhost:3000', 'https://tu-dominio.vercel.app'],
    'allowed_headers'      => ['*'],
    'exposed_headers'      => [],
    'max_age'              => 0,
    'supports_credentials' => true,
];
```

### 3.8 Rutas API esenciales (`routes/api.php`)

```php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TiendaController;

// Público
Route::post('/login',  [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// Protegidas — cualquier usuario autenticado
Route::middleware(['auth:sanctum', 'activo'])->group(function () {

    Route::get('/user',   [AuthController::class, 'me']);
    Route::get('/tienda', [TiendaController::class, 'show']);

    // Recursos de la tienda
    Route::apiResource('productos',   ProductoController::class);
    Route::apiResource('clientes',    ClienteController::class);
    Route::apiResource('ventas',      VentaController::class);
    Route::apiResource('cajas',       CajaController::class);
    Route::apiResource('inventario',  InventarioController::class);
    Route::apiResource('proveedores', ProveedorController::class);
    Route::apiResource('ordenes-compra', OrdenCompraController::class);
    Route::apiResource('categorias',  CategoriaController::class);
    Route::apiResource('impuestos',   ImpuestoController::class);
    Route::apiResource('descuentos',  DescuentoController::class);
    Route::apiResource('metodos-pago', MetodoPagoController::class);
    Route::apiResource('tasas-cambio', TasaCambioController::class);

    // Rutas de caja
    Route::post('/cajas/{caja}/abrir',  [CajaController::class, 'abrir']);
    Route::post('/cajas/{caja}/cerrar', [CajaController::class, 'cerrar']);

    // Solo admin
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('usuarios', UsuarioController::class);
        Route::get('/reportes/ventas',      [ReporteController::class, 'ventas']);
        Route::get('/reportes/inventario',  [ReporteController::class, 'inventario']);
        Route::get('/reportes/rentabilidad',[ReporteController::class, 'rentabilidad']);
        Route::get('/reportes/creditos',    [ReporteController::class, 'creditos']);
        Route::put('/tienda',               [TiendaController::class, 'update']);
    });
});
```

### 3.9 Middleware `activo`

```bash
php artisan make:middleware EnsureUserIsActive
```

```php
// app/Http/Middleware/EnsureUserIsActive.php
public function handle(Request $request, Closure $next): Response
{
    if (auth()->check() && ! auth()->user()->activo) {
        return response()->json(['message' => 'Usuario desactivado.'], 403);
    }
    return $next($request);
}
```

Registrar en `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'activo' => \App\Http\Middleware\EnsureUserIsActive::class,
    ]);
})
```

---

## 4. Frontend — Next.js 15

### 4.1 Crear el proyecto

```bash
npx create-next-app@latest tiendapos-frontend \
  --typescript \
  --tailwind \
  --eslint \
  --app \
  --src-dir \
  --import-alias "@/*"

cd tiendapos-frontend
```

### 4.2 Instalar dependencias

```bash
# UI
npx shadcn@latest init
npx shadcn@latest add button input label card table badge dialog \
  dropdown-menu select sheet sidebar tooltip toast form

# Estado y data fetching
npm install @tanstack/react-query axios zustand

# Formularios y validación
npm install react-hook-form zod @hookform/resolvers

# Utilidades
npm install sonner date-fns clsx tailwind-merge lucide-react

# Tipos
npm install -D @types/node
```

### 4.3 Configurar Axios (`src/lib/axios.ts`)

```typescript
import axios from 'axios'

const api = axios.create({
  baseURL: process.env.NEXT_PUBLIC_API_URL,
  withCredentials: true,
  headers: {
    'Accept':       'application/json',
    'Content-Type': 'application/json',
  },
})

// Inyectar token automáticamente
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

// Redirigir a login si la sesión expira
api.interceptors.response.use(
  (res) => res,
  (err) => {
    if (err.response?.status === 401) {
      localStorage.removeItem('token')
      window.location.href = '/login'
    }
    return Promise.reject(err)
  }
)

export default api
```

### 4.4 Zustand Auth Store (`src/stores/auth-store.ts`)

```typescript
import { create } from 'zustand'
import { persist } from 'zustand/middleware'

interface Tienda {
  id: number
  nombre_comercial: string
  rif: string
  moneda_base: string
  logo_url?: string
}

interface User {
  id: number
  name: string
  email: string
  roles: string[]
  permissions: string[]
}

interface AuthState {
  user:   User | null
  token:  string | null
  tienda: Tienda | null
  setAuth:  (user: User, token: string, tienda: Tienda) => void
  clearAuth: () => void
  isAdmin:   () => boolean
  hasPermission: (permission: string) => boolean
}

export const useAuthStore = create<AuthState>()(
  persist(
    (set, get) => ({
      user:   null,
      token:  null,
      tienda: null,

      setAuth: (user, token, tienda) => {
        localStorage.setItem('token', token)
        set({ user, token, tienda })
      },

      clearAuth: () => {
        localStorage.removeItem('token')
        set({ user: null, token: null, tienda: null })
      },

      isAdmin: () => get().user?.roles.includes('admin') ?? false,

      hasPermission: (permission) =>
        get().user?.permissions.includes(permission) ?? false,
    }),
    { name: 'tiendapos-auth', partialize: (s) => ({ user: s.user, token: s.token, tienda: s.tienda }) }
  )
)
```

### 4.5 Middleware de rutas (`src/middleware.ts`)

```typescript
import { NextResponse } from 'next/server'
import type { NextRequest } from 'next/server'

const PUBLIC_ROUTES = ['/login']

export function middleware(request: NextRequest) {
  const token = request.cookies.get('token')?.value
  const { pathname } = request.nextUrl

  const isPublic = PUBLIC_ROUTES.some((r) => pathname.startsWith(r))

  if (!token && !isPublic) {
    return NextResponse.redirect(new URL('/login', request.url))
  }

  if (token && isPublic) {
    return NextResponse.redirect(new URL('/dashboard', request.url))
  }

  return NextResponse.next()
}

export const config = {
  matcher: ['/((?!_next/static|_next/image|favicon.ico|api).*)'],
}
```

### 4.6 TanStack Query Provider (`src/providers/query-provider.tsx`)

```typescript
'use client'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { useState } from 'react'

export function QueryProvider({ children }: { children: React.ReactNode }) {
  const [client] = useState(() => new QueryClient({
    defaultOptions: {
      queries: { staleTime: 1000 * 60, retry: 1 },
    },
  }))
  return <QueryClientProvider client={client}>{children}</QueryClientProvider>
}
```

---

## 5. Roles y permisos (Spatie)

### Seeder (`database/seeders/RolesAndPermissionsSeeder.php`)

```php
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
```

Registrar en `database/seeders/DatabaseSeeder.php`:

```php
public function run(): void
{
    $this->call(RolesAndPermissionsSeeder::class);
}
```

---

## 6. Estructura de carpetas Backend

```
tiendapos-api/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/
│   │   │       ├── AuthController.php
│   │   │       ├── TiendaController.php
│   │   │       ├── ProductoController.php
│   │   │       ├── VarianteController.php
│   │   │       ├── CategoriaController.php
│   │   │       ├── ImpuestoController.php
│   │   │       ├── MargenController.php
│   │   │       ├── ListaPrecioController.php
│   │   │       ├── ClienteController.php
│   │   │       ├── ProveedorController.php
│   │   │       ├── AlmacenController.php
│   │   │       ├── InventarioController.php
│   │   │       ├── AjusteInventarioController.php
│   │   │       ├── TrasladoStockController.php
│   │   │       ├── OrdenCompraController.php
│   │   │       ├── RecepcionCompraController.php
│   │   │       ├── MetodoPagoController.php
│   │   │       ├── TasaCambioController.php
│   │   │       ├── CajaController.php
│   │   │       ├── SesionCajaController.php
│   │   │       ├── MovimientoCajaController.php
│   │   │       ├── DescuentoController.php
│   │   │       ├── VentaController.php
│   │   │       ├── DevolucionController.php
│   │   │       ├── CreditoController.php
│   │   │       ├── AbonoController.php
│   │   │       ├── ReporteController.php
│   │   │       ├── DashboardController.php
│   │   │       └── UsuarioController.php
│   │   ├── Middleware/
│   │   │   └── EnsureUserIsActive.php
│   │   └── Requests/
│   │       ├── StoreVentaRequest.php
│   │       ├── StoreProductoRequest.php
│   │       └── ...
│   ├── Models/
│   │   ├── User.php
│   │   ├── Tienda.php
│   │   ├── Producto.php
│   │   ├── VarianteProducto.php
│   │   ├── CategoriaProducto.php
│   │   ├── Impuesto.php
│   │   ├── MargenGanancia.php
│   │   ├── ListaPrecio.php
│   │   ├── Unidad.php
│   │   ├── DefinicionAtributo.php
│   │   ├── Cliente.php
│   │   ├── Proveedor.php
│   │   ├── ProductoProveedor.php
│   │   ├── Almacen.php
│   │   ├── Inventario.php
│   │   ├── MovimientoInventario.php
│   │   ├── AjusteInventario.php
│   │   ├── ItemAjuste.php
│   │   ├── TrasladoStock.php
│   │   ├── ItemTraslado.php
│   │   ├── OrdenCompra.php
│   │   ├── ItemOrdenCompra.php
│   │   ├── RecepcionCompra.php
│   │   ├── ItemRecepcion.php
│   │   ├── FacturaProveedor.php
│   │   ├── MetodoPago.php
│   │   ├── TasaCambio.php
│   │   ├── Caja.php
│   │   ├── SesionCaja.php
│   │   ├── MovimientoCaja.php
│   │   ├── Descuento.php
│   │   ├── Venta.php
│   │   ├── ItemVenta.php
│   │   ├── PagoVenta.php
│   │   ├── DevolucionVenta.php
│   │   ├── ItemDevolucion.php
│   │   ├── CuentaCredito.php
│   │   ├── FacturaCredito.php
│   │   ├── AbonoCredito.php
│   │   ├── Auditoria.php
│   │   └── Notificacion.php
│   └── Policies/           ← Autorización por recurso (opcional)
│       ├── VentaPolicy.php
│       └── ProductoPolicy.php
├── database/
│   ├── migrations/         ← SOLO tablas Laravel: users, roles, permissions
│   └── seeders/
│       ├── DatabaseSeeder.php
│       └── RolesAndPermissionsSeeder.php
├── routes/
│   └── api.php
└── config/
    ├── cors.php
    └── sanctum.php
```

---

## 7. Estructura de carpetas Frontend

```
tiendapos-frontend/
└── src/
    ├── app/
    │   ├── layout.tsx                  ← Root layout (QueryProvider, Toaster)
    │   ├── (auth)/
    │   │   ├── layout.tsx              ← Redirige a /dashboard si hay token
    │   │   └── login/
    │   │       └── page.tsx
    │   └── (dashboard)/
    │       ├── layout.tsx              ← Verifica auth + carga Sidebar
    │       ├── dashboard/
    │       │   └── page.tsx            ← KPIs: ventas del día, caja, stock bajo
    │       ├── pos/
    │       │   └── page.tsx            ← Pantalla de venta (caja registradora)
    │       ├── ventas/
    │       │   ├── page.tsx            ← Listado de ventas
    │       │   └── [id]/page.tsx       ← Detalle de venta / factura
    │       ├── caja/
    │       │   ├── page.tsx            ← Sesión de caja actual
    │       │   └── historial/page.tsx  ← Historial de sesiones
    │       ├── clientes/
    │       │   ├── page.tsx
    │       │   └── [id]/page.tsx
    │       ├── creditos/
    │       │   ├── page.tsx            ← Cartera de créditos
    │       │   └── [id]/page.tsx       ← Cuenta individual
    │       ├── productos/
    │       │   ├── page.tsx
    │       │   └── [id]/page.tsx
    │       ├── inventario/
    │       │   ├── page.tsx            ← Stock en tiempo real
    │       │   ├── ajustes/page.tsx
    │       │   └── traslados/page.tsx
    │       ├── compras/
    │       │   ├── page.tsx
    │       │   └── [id]/page.tsx
    │       ├── proveedores/
    │       │   └── page.tsx
    │       └── admin/                  ← Solo rol admin
    │           ├── usuarios/page.tsx
    │           ├── categorias/page.tsx
    │           ├── impuestos/page.tsx
    │           ├── margenes/page.tsx
    │           ├── descuentos/page.tsx
    │           ├── metodos-pago/page.tsx
    │           ├── tasas-cambio/page.tsx
    │           └── tienda/page.tsx     ← Config de la tienda
    │
    ├── components/
    │   ├── layout/
    │   │   ├── sidebar.tsx             ← UN SOLO SIDEBAR (admin ve todo, cajero ve lo suyo)
    │   │   ├── sidebar-nav-item.tsx
    │   │   ├── topbar.tsx
    │   │   └── user-menu.tsx
    │   ├── pos/
    │   │   ├── product-search.tsx
    │   │   ├── cart.tsx
    │   │   ├── cart-item.tsx
    │   │   ├── payment-modal.tsx
    │   │   └── receipt-preview.tsx
    │   ├── ventas/
    │   │   ├── venta-table.tsx
    │   │   └── venta-detail.tsx
    │   ├── productos/
    │   │   ├── producto-form.tsx
    │   │   └── producto-table.tsx
    │   ├── inventario/
    │   │   ├── stock-table.tsx
    │   │   └── ajuste-form.tsx
    │   ├── clientes/
    │   │   ├── cliente-form.tsx
    │   │   └── cliente-table.tsx
    │   ├── caja/
    │   │   ├── apertura-modal.tsx
    │   │   └── cierre-modal.tsx
    │   ├── creditos/
    │   │   ├── cartera-table.tsx
    │   │   └── abono-modal.tsx
    │   ├── dashboard/
    │   │   ├── kpi-card.tsx
    │   │   ├── ventas-chart.tsx
    │   │   └── stock-alerts.tsx
    │   └── ui/                         ← Componentes shadcn/ui (auto-generados)
    │
    ├── hooks/
    │   ├── use-auth.ts
    │   ├── use-tienda.ts
    │   ├── use-productos.ts
    │   ├── use-ventas.ts
    │   ├── use-clientes.ts
    │   ├── use-caja.ts
    │   ├── use-inventario.ts
    │   ├── use-creditos.ts
    │   ├── use-dashboard.ts
    │   └── use-permission.ts           ← Hook para verificar permisos Spatie
    │
    ├── lib/
    │   ├── axios.ts                    ← Cliente HTTP con interceptors
    │   ├── utils.ts                    ← cn(), formatMoney(), formatDate()
    │   └── validations/
    │       ├── venta.ts
    │       ├── producto.ts
    │       └── cliente.ts
    │
    ├── stores/
    │   ├── auth-store.ts               ← Zustand: user, token, tienda, roles
    │   └── pos-store.ts                ← Zustand: carrito de la venta activa
    │
    ├── providers/
    │   ├── query-provider.tsx
    │   └── toast-provider.tsx
    │
    ├── types/
    │   ├── auth.ts
    │   ├── tienda.ts
    │   ├── producto.ts
    │   ├── venta.ts
    │   ├── cliente.ts
    │   ├── inventario.ts
    │   └── api.ts                      ← Tipos de respuesta de la API
    │
    └── middleware.ts                   ← Protección de rutas
```

---

## 8. Variables de entorno

### Backend (`.env`)

```env
APP_NAME="TiendaPOS"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=<neon-host>
DB_PORT=5432
DB_DATABASE=<neon-db>
DB_USERNAME=<neon-user>
DB_PASSWORD=<neon-password>
DB_SSLMODE=require

SANCTUM_STATEFUL_DOMAINS=localhost:3000
SESSION_DRIVER=cookie
SESSION_DOMAIN=localhost
```

### Frontend (`.env.local`)

```env
NEXT_PUBLIC_API_URL=http://localhost:8000/api
```

---

## 9. Comandos útiles

```bash
# ── BACKEND ──────────────────────────────────────────────────────────────────

# Correr servidor de desarrollo
php artisan serve

# Limpiar cachés (después de cambios en config o rutas)
php artisan optimize:clear

# Re-correr seeders (si ya hay datos: borra y re-crea)
php artisan migrate:fresh --seed

# Ver rutas registradas
php artisan route:list --path=api

# Crear un controller de API
php artisan make:controller Api/ProductoController --api

# Crear un modelo con migration
php artisan make:model NombreModelo -m


# ── FRONTEND ─────────────────────────────────────────────────────────────────

# Servidor de desarrollo
npm run dev

# Build de producción
npm run build

# Agregar componente shadcn
npx shadcn@latest add <componente>

# Linting
npm run lint
```

---

## Decisiones de diseño

**¿Por qué el SQL está separado de las migraciones de Laravel?**  
El schema de negocio (36 tablas) usa características avanzadas de PostgreSQL que Laravel no puede expresar limpiamente: columnas generadas `GENERATED ALWAYS AS`, tablas particionadas, índices únicos parciales con `WHERE`, y extensiones `ltree`/`pg_trgm`. Es más seguro correrlo directamente en Neon una vez, y dejar que Laravel gestione únicamente sus propias tablas (`users`, `personal_access_tokens`, `roles`, `permissions`, etc.).

**¿Por qué 3 roles?**  
`admin` control total, `supervisor` gestión operativa (inventario, compras, reportes, anular ventas), `cajero` ventas y caja. Se eliminó `vendedor` porque en la práctica un vendedor y un cajero son la misma persona.

**¿Por qué Zustand y no solo TanStack Query?**  
TanStack Query maneja el estado del servidor (caché, refetch, mutaciones). Zustand maneja estado de cliente puro: sesión del usuario logueado, carrito del POS en curso, preferencias de UI. Son complementarios, no alternativos.
