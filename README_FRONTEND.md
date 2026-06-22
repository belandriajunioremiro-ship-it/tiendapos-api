# TiendaPOS v2.1 — Guía Maestra del Frontend

> Fuente única de verdad para construir el frontend desde cero.
> Backend: Laravel 11 API en `/api/v1/` con Sanctum + Spatie + Neon PostgreSQL.

---

## 1. Stack Tecnológico Obligatorio

| Componente | Tecnología | Versión |
|---|---|---|
| Framework | **Next.js** (App Router) | 15.x |
| UI | **React** | 19.x |
| Estilos | **Tailwind CSS** | 4.x |
| Componentes | **shadcn/ui** | último |
| Tipografía | **Inter** (next/font/google) | — |
| Iconos | **lucide-react** | último |
| Estado global | **Zustand** (con persist en localStorage) | 5.x |
| Data fetching | **TanStack Query** (React Query) | 5.x |
| Formularios | **React Hook Form** + **Zod** | último |
| Cliente HTTP | **Axios** | 1.x |
| Notificaciones | **Sonner** | último |

---

## 2. Comandos de Instalación

```bash
# Crear proyecto Next.js 15 (versión exacta, no latest que podría ser 16+)
npx create-next-app@15 tiendapos-frontend --typescript --tailwind --eslint --app --src-dir --import-alias "@/*"

cd tiendapos-frontend

# Dependencias principales (versiones compatibles con Next.js 15 + React 19)
npm install axios@1 zustand@5 @tanstack/react-query@5 react-hook-form @hookform/resolvers zod sonner lucide-react

# Inicializar shadcn/ui
npx shadcn@latest init

# Componentes shadcn/ui necesarios para el POS
npx shadcn@latest add button input card table dialog sheet form select dropdown-menu badge tooltip separator scroll-area tabs avatar command popover calendar label checkbox radio-group switch textarea toast skeleton
```

> **Nota de compatibilidad:** Next.js 15 usa React 19 por defecto. Zustand 5 y TanStack Query 5 son compatibles con React 19. Si `create-next-app@15` falla, usar `npx create-next-app@15.5 tiendapos-frontend` (la versión estable más reciente de la rama 15).

### Variables de entorno

```env
# .env.local
NEXT_PUBLIC_API_URL=http://localhost:8000/api/v1
```

---

## 3. Estructura de Carpetas

```
src/
├── app/
│   ├── layout.tsx                         # Raíz: Inter font + Toaster
│   ├── globals.css                        # Variables Tailwind + shadcn
│   ├── (publico)/
│   │   ├── layout.tsx                     # Sin sidebar, sin auth
│   │   ├── login/
│   │   │   └── pagina.tsx
│   │   ├── registro/
│   │   │   └── pagina.tsx                 # Onboarding paso 1 (público)
│   │   ├── recuperar-password/
│   │   │   └── pagina.tsx
│   │   └── restablecer-password/
│   │       └── pagina.tsx
│   ├── (auth)/
│   │   ├── layout.tsx                     # Verifica sesión, redirige si no hay token
│   │   ├── onboarding/
│   │   │   ├── layout.tsx                 # Wizard layout con pasos
│   │   │   ├── datos-fiscales/
│   │   │   │   └── pagina.tsx             # Paso 2
│   │   │   ├── configurar-negocio/
│   │   │   │   └── pagina.tsx             # Paso 3
│   │   │   └── primer-producto/
│   │   │       └── pagina.tsx             # Paso 4
│   │   └── suscripcion/
│   │       ├── pagina.tsx                 # Estado, planes, pagar
│   │       └── cancelar/
│   │           └── pagina.tsx
│   │
│   ├── (admin)/                           # ═══════════════════════════════
│   │   │                                  # DASHBOARD ADMINISTRADOR
│   │   │                                  # Solo rol: admin
│   │   │                                  # Redirige si no es admin
│   │   │                                  # ═══════════════════════════════
│   │   ├── layout.tsx                     # Sidebar admin + Header + Banner
│   │   ├── pagina.tsx                     # Dashboard admin (métricas, KPIs)
│   │   ├── ventas/
│   │   │   ├── pagina.tsx                 # Todas las ventas
│   │   │   └── [id]/
│   │   │       └── pagina.tsx             # Detalle venta
│   │   ├── productos/
│   │   │   ├── pagina.tsx                 # Catálogo completo
│   │   │   ├── nuevo/
│   │   │   │   └── pagina.tsx             # Crear (plan.limits:productos)
│   │   │   └── [id]/
│   │   │       └── pagina.tsx             # Editar
│   │   ├── clientes/
│   │   │   ├── pagina.tsx
│   │   │   ├── nuevo/
│   │   │   │   └── pagina.tsx
│   │   │   └── [id]/
│   │   │       └── pagina.tsx
│   │   ├── inventario/
│   │   │   ├── pagina.tsx                 # Stock global
│   │   │   ├── traslados/
│   │   │   │   ├── pagina.tsx
│   │   │   │   └── [id]/
│   │   │   │       └── pagina.tsx
│   │   │   ├── ajustes/
│   │   │   │   ├── pagina.tsx
│   │   │   │   └── [id]/
│   │   │   │       └── pagina.tsx
│   │   │   └── recepciones/
│   │   │       ├── pagina.tsx
│   │   │       └── [id]/
│   │   │           └── pagina.tsx
│   │   ├── compras/
│   │   │   ├── proveedores/
│   │   │   │   ├── pagina.tsx
│   │   │   │   ├── nuevo/
│   │   │   │   │   └── pagina.tsx
│   │   │   │   └── [id]/
│   │   │   │       └── pagina.tsx
│   │   │   └── ordenes/
│   │   │       ├── pagina.tsx
│   │   │       ├── nueva/
│   │   │       │   └── pagina.tsx
│   │   │       └── [id]/
│   │   │           └── pagina.tsx
│   │   ├── creditos/
│   │   │   ├── pagina.tsx                 # Cuentas de crédito
│   │   │   ├── [id]/
│   │   │   │   └── pagina.tsx             # Detalle + abonos
│   │   │   └── abonos/
│   │   │       └── pagina.tsx
│   │   ├── devoluciones/
│   │   │   ├── pagina.tsx
│   │   │   └── [id]/
│   │   │       └── pagina.tsx
│   │   ├── caja/
│   │   │   ├── pagina.tsx                 # Estado cajas
│   │   │   ├── sesiones/
│   │   │   │   └── pagina.tsx
│   │   │   └── movimientos/
│   │   │       └── pagina.tsx
│   │   ├── reportes/
│   │   │   ├── pagina.tsx                 # Hub reportes
│   │   │   ├── ventas/
│   │   │   │   └── pagina.tsx
│   │   │   ├── inventario/
│   │   │   │   └── pagina.tsx
│   │   │   ├── rentabilidad/
│   │   │   │   └── pagina.tsx
│   │   │   └── creditos/
│   │   │       └── pagina.tsx
│   │   └── configuracion/
│   │       ├── pagina.tsx                 # Hub configuración
│   │       ├── tienda/
│   │       │   └── pagina.tsx             # PUT /tienda (solo admin)
│   │       ├── usuarios/
│   │       │   ├── pagina.tsx             # CRUD (plan.limits:usuarios)
│   │       │   ├── nuevo/
│   │       │   │   └── pagina.tsx
│   │       │   └── [id]/
│   │       │       └── pagina.tsx
│   │       ├── categorias/
│   │       │   └── pagina.tsx
│   │       ├── impuestos/
│   │       │   └── pagina.tsx
│   │       ├── descuentos/
│   │       │   └── pagina.tsx
│   │       ├── metodos-pago/
│   │       │   └── pagina.tsx
│   │       ├── tasas-cambio/
│   │       │   └── pagina.tsx
│   │       ├── listas-precio/
│   │       │   └── pagina.tsx
│   │       ├── margenes/
│   │       │   └── pagina.tsx
│   │       ├── almacenes/
│   │       │   └── pagina.tsx             # plan.limits:almacenes al crear
│   │       └── cajas/
│   │           └── pagina.tsx             # plan.limits:cajas al crear
│   │
│   └── (pos)/                             # ═══════════════════════════════
│       │                                  # DASHBOARD VENDEDOR / CAJERO
│       │                                  # Roles: cajero, supervisor
│       │                                  # Interfaz optimizada para ventas
│       │                                  # ═══════════════════════════════
│       ├── layout.tsx                     # Layout POS compacto, sin sidebar pesado
│       ├── pagina.tsx                     # Dashboard vendedor (ventas hoy, caja abierta)
│       ├── cobrar/                        # Pantalla principal POS
│       │   └── pagina.tsx                 # Buscar producto + carrito + pagos
│       ├── ventas/
│       │   ├── pagina.tsx                 # Mis ventas del día
│       │   └── [id]/
│       │       └── pagina.tsx             # Detalle / recibo
│       ├── clientes/
│       │   ├── pagina.tsx                 # Buscar cliente rápido
│       │   └── [id]/
│       │       └── pagina.tsx
│       ├── caja/
│       │   ├── pagina.tsx                 # Abrir/cerrar mi caja
│       │   └── movimientos/
│       │       └── pagina.tsx             # Retiros, gastos
│       ├── cotizaciones/
│       │   └── pagina.tsx                 # Crear/ver cotizaciones
│       └── devoluciones/
│           └── pagina.tsx                 # Crear devolución (si tiene permiso)
│
├── componentes/
│   ├── ui/                                # Generados por shadcn/ui
│   ├── layout/
│   │   ├── barra-lateral.tsx             # Sidebar de navegación
│   │   ├── encabezado.tsx                # Header con user menu
│   │   └── banner-suscripcion.tsx        # Trial expirando / vencida
│   ├── guardas/
│   │   ├── guarda-rol.tsx                # RoleGuard (admin/supervisor/cajero)
│   │   ├── guarda-permiso.tsx            # PermissionGuard
│   │   └── guarda-suscripcion.tsx         # Bloquea si no hay suscripción activa
│   ├── pos/
│   │   ├── teclado-productos.tsx         # Grid de productos para POS
│   │   ├── carrito-venta.tsx             # Items + totales + IGTF
│   │   ├── panel-pagos.tsx               # Métodos de pago + multimoneda
│   │   └── recibo-venta.tsx              # Preview de factura
│   ├── formularios/
│   │   ├── campo-entrada.tsx             # Wrapper de input + label + error
│   │   ├── campo-seleccion.tsx           # Select con búsqueda
│   │   └── campo-busqueda.tsx             # Debounced search input
│   ├── tablas/
│   │   ├── tabla-datos.tsx               # DataTable genérica con paginación
│   │   └── columna-accion.tsx            # Botones editar/eliminar
│   └── modales/
│       ├── modal-limite-plan.tsx          # "Alcanzaste el límite de tu plan"
│       ├── modal-confirmar.tsx            # Confirmación genérica
│       └── modal-detalle.tsx             # Ver detalle de registro
│
├── almacen/                               # Zustand stores
│   ├── autenticacion.ts                   # Auth: user, token, tienda, roles
│   ├── suscripcion.ts                     # Plan, límites, días restantes
│   ├── onboarding.ts                      # Paso actual, completado
│   └── pos.ts                             # Carrito, items, pagos, totales
│
├── hooks/                                 # Custom hooks
│   ├── usar-api.ts                        # Wrapper React Query + Axios
│   ├── usar-autenticacion.ts
│   ├── usar-suscripcion.ts
│   ├── usar-onboarding.ts
│   ├── usar-roles.ts                      # tieneRol(), tienePermiso()
│   └── usar-paginacion.ts
│
├── lib/                                   # Utilidades y configuración
│   ├── api.ts                             # Cliente Axios con interceptores
│   ├── consultas.ts                       # React Query key factory
│   ├── esquemas.ts                        # Schemas Zod (validación forms)
│   ├── constantes.ts                      # Roles, permisos, estados, países
│   └── utilidades.ts                      # Formateo moneda, fecha, RIF
│
├── tipos/                                 # TypeScript types
│   ├── api.ts                             # Respuestas API genéricas
│   ├── modelos.ts                         # User, Tienda, Producto, Venta...
│   └── autenticacion.ts                   # AuthState, LoginForm...
│
├── proveedores/
│   ├── proveedor-consulta.tsx             # TanStack Query provider
│   └── proveedor-tema.tsx                # Theme provider (dark/light)
│
└── middleware.ts                          # Protección de rutas (Next.js)
```

---

## 4. Componentes Reutilizables (Sistema de Diseño)

> **Regla:** Si un componente se usa en 2+ páginas, va a `componentes/`. Si es específico de una página, va junto a la página.

### 4.1 Tabla de datos genérica

```tsx
// componentes/tablas/tabla-datos.tsx
"use client";

import { useState } from "react";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/componentes/ui/table";
import { Button } from "@/componentes/ui/button";
import { ChevronLeft, ChevronRight } from "lucide-react";

interface Columna<T> {
  clave: keyof T | string;
  titulo: string;
  render?: (fila: T) => React.ReactNode;
  ordenable?: boolean;
}

interface Props<T> {
  datos: T[];
  columnas: Columna<T>[];
  cargando?: boolean;
  pagina: number;
  totalPaginas: number;
  alCambiarPagina: (pagina: number) => void;
  alHacerClickFila?: (fila: T) => void;
  acciones?: (fila: T) => React.ReactNode;
}

export function TablaDatos<T extends Record<string, any>>({
  datos, columnas, cargando, pagina, totalPaginas,
  alCambiarPagina, alHacerClickFila, acciones,
}: Props<T>) {
  if (cargando) {
    return <div className="p-4 text-center text-muted-foreground">Cargando...</div>;
  }

  return (
    <div>
      <Table>
        <TableHeader>
          <TableRow>
            {columnas.map((col) => (
              <TableHead key={String(col.clave)}>{col.titulo}</TableHead>
            ))}
            {acciones && <TableHead className="w-20">Acciones</TableHead>}
          </TableRow>
        </TableHeader>
        <TableBody>
          {datos.length === 0 ? (
            <TableRow>
              <TableCell colSpan={columnas.length + (acciones ? 1 : 0)} className="text-center py-8">
                No hay registros
              </TableCell>
            </TableRow>
          ) : (
            datos.map((fila, i) => (
              <TableRow
                key={i}
                className={alHacerClickFila ? "cursor-pointer hover:bg-muted/50" : ""}
                onClick={() => alHacerClickFila?.(fila)}
              >
                {columnas.map((col) => (
                  <TableCell key={String(col.clave)}>
                    {col.render ? col.render(fila) : fila[col.clave]}
                  </TableCell>
                ))}
                {acciones && <TableCell>{acciones(fila)}</TableCell>}
              </TableRow>
            ))
          )}
        </TableBody>
      </Table>

      {totalPaginas > 1 && (
        <div className="flex items-center justify-between py-4">
          <p className="text-sm text-muted-foreground">
            Página {pagina} de {totalPaginas}
          </p>
          <div className="flex gap-2">
            <Button variant="outline" size="sm" disabled={pagina <= 1}
              onClick={() => alCambiarPagina(pagina - 1)}>
              <ChevronLeft className="h-4 w-4" />
            </Button>
            <Button variant="outline" size="sm" disabled={pagina >= totalPaginas}
              onClick={() => alCambiarPagina(pagina + 1)}>
              <ChevronRight className="h-4 w-4" />
            </Button>
          </div>
        </div>
      )}
    </div>
  );
}
```

**Uso — mismo componente en productos, clientes, ventas, proveedores, etc.:**

```tsx
// Ejemplo: app/(admin)/productos/pagina.tsx
const columnas = [
  { clave: "nombre", titulo: "Nombre" },
  { clave: "codigo_sku", titulo: "SKU" },
  { clave: "costo_promedio", titulo: "Costo", render: (f) => formatearMoneda(f.costo_promedio, "USD", pais) },
  { clave: "activo", titulo: "Estado", render: (f) => (
    <Badge variant={f.activo ? "default" : "secondary"}>{f.activo ? "Activo" : "Inactivo"}</Badge>
  )},
];

<TablaDatos
  datos={productos}
  columnas={columnas}
  cargando={cargando}
  pagina={pagina}
  totalPaginas={totalPaginas}
  alCambiarPagina={setPagina}
  alHacerClickFila={(f) => router.push(`/admin/productos/${f.id}`)}
  acciones={(f) => <ColumnaAccion id={f.id} rutaBase="/admin/productos" />}
/>
```

### 4.2 Hook genérico para listas con React Query

```ts
// hooks/usar-listado.ts
import { useQuery } from "@tanstack/react-query";
import api from "@/lib/api";

interface PropsListado {
  ruta: string;           // ej: "/productos"
  filtros?: Record<string, any>;
  claveQuery: any[];      // ej: claves.productos.todos()
  pagina?: number;
  porPagina?: number;
}

export function usarListado<T>({ ruta, filtros, claveQuery, pagina = 1, porPagina = 20 }: PropsListado) {
  const params = new URLSearchParams();
  params.set("page", String(pagina));
  params.set("per_page", String(porPagina));
  if (filtros) {
    Object.entries(filtros).forEach(([k, v]) => {
      if (v !== undefined && v !== null && v !== "") params.set(k, String(v));
    });
  }

  const { data, isLoading, error, refetch } = useQuery({
    queryKey: [...claveQuery, pagina, filtros],
    queryFn: async () => {
      const { data } = await api.get(`${ruta}?${params.toString()}`);
      return data;
    },
  });

  return {
    datos: (data?.data ?? []) as T[],
    meta: data?.meta ?? {},
    cargando: isLoading,
    error,
    refrescar: refetch,
  };
}
```

**Uso — mismo hook en todas las páginas de lista:**

```tsx
// Productos
const { datos, meta, cargando } = usarListado<Producto>({
  ruta: "/productos", claveQuery: claves.productos.todos(), filtros: { buscar }
});

// Clientes
const { datos, meta, cargando } = usarListado<Cliente>({
  ruta: "/clientes", claveQuery: claves.clientes.todos(), filtros: { buscar, tipo_cliente }
});

// Ventas
const { datos, meta, cargando } = usarListado<Venta>({
  ruta: "/ventas", claveQuery: claves.ventas.todos(), filtros: { estado, desde, hasta }
});
```

### 4.3 Hook genérico para crear/editar (mutation)

```ts
// hooks/usar-guardar.ts
import { useMutation, useQueryClient } from "@tanstack/react-query";
import api from "@/lib/api";
import { toast } from "sonner";

interface PropsGuardar {
  ruta: string;
  claveInvalidar: any[];
  mensajeExito?: string;
}

export function usarGuardar<T>({ ruta, claveInvalidar, mensajeExito = "Guardado correctamente" }: PropsGuardar) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async ({ id, datos }: { id?: number; datos: Partial<T> }) => {
      if (id) {
        const { data } = await api.put(`${ruta}/${id}`, datos);
        return data;
      }
      const { data } = await api.post(ruta, datos);
      return data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: claveInvalidar });
      toast.success(mensajeExito);
    },
    onError: (error: any) => {
      const msg = error.response?.data?.message || "Error al guardar";
      toast.error(msg);
    },
  });
}
```

**Uso — mismo hook en crear/editar productos, clientes, proveedores...:**

```tsx
const guardar = usarGuardar<Producto>({
  ruta: "/productos",
  claveInvalidar: claves.productos.todos(),
  mensajeExito: "Producto guardado",
});

// Crear
guardar.mutate({ datos: { nombre, codigo_sku, costo_promedio: costo } });

// Editar
guardar.mutate({ id: productoId, datos: { nombre, codigo_sku } });
```

### 4.4 Hook genérico para eliminar

```ts
// hooks/usar-eliminar.ts
import { useMutation, useQueryClient } from "@tanstack/react-query";
import api from "@/lib/api";
import { toast } from "sonner";

interface PropsEliminar {
  ruta: string;
  claveInvalidar: any[];
  mensajeExito?: string;
}

export function usarEliminar({ ruta, claveInvalidar, mensajeExito = "Eliminado correctamente" }: PropsEliminar) {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (id: number) => {
      const { data } = await api.delete(`${ruta}/${id}`);
      return data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: claveInvalidar });
      toast.success(mensajeExito);
    },
    onError: (error: any) => {
      const msg = error.response?.data?.message || "Error al eliminar";
      toast.error(msg);
    },
  });
}
```

### 4.5 Formulario genérico con React Hook Form + Zod

```tsx
// componentes/formularios/formulario-crud.tsx
"use client";

import { Form } from "@/componentes/ui/form";
import { Button } from "@/componentes/ui/button";
import { Loader2 } from "lucide-react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { type ZodSchema } from "zod";

interface PropsFormulario<T> {
  esquema: ZodSchema;
  valoresIniciales: Partial<T>;
  alGuardar: (datos: T) => void;
  cargando?: boolean;
  children: React.ReactNode;
}

export function FormularioCRUD<T>({ esquema, valoresIniciales, alGuardar, cargando, children }: PropsFormulario<T>) {
  const form = useForm({
    resolver: zodResolver(esquema),
    defaultValues: valoresIniciales,
  });

  return (
    <Form {...form}>
      <form onSubmit={form.handleSubmit(alGuardar)} className="space-y-4">
        {children}
        <Button type="submit" disabled={cargando}>
          {cargando && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
          Guardar
        </Button>
      </form>
    </Form>
  );
}
```

**Uso — mismo formulario en productos, clientes, proveedores...:**

```tsx
<FormularioCRUD
  esquema={esquemaProducto}
  valoresIniciales={producto ?? { nombre: "", codigo_sku: "" }}
  alGuardar={(datos) => guardar.mutate({ id: producto?.id, datos })}
  cargando={guardar.isPending}
>
  <CampoEntrada control={form.control} nombre="nombre" label="Nombre" />
  <CampoEntrada control={form.control} nombre="codigo_sku" label="SKU" />
  <CampoSeleccion control={form.control} nombre="categoria_id" label="Categoría" opciones={categorias} />
</FormularioCRUD>
```

### 4.6 Mapa de reutilización

| Componente/Hook | Se reutiliza en | Veces |
|---|---|---|
| `TablaDatos` | Todas las páginas de lista (productos, clientes, ventas, proveedores, órdenes, créditos, devoluciones, usuarios, inventario, traslados, ajustes, recepciones, abonos, sesiones, movimientos, categorías, impuestos, descuentos, métodos de pago, tasas, listas precio, márgenes) | **20+** |
| `usarListado` | Todas las páginas de lista | **20+** |
| `usarGuardar` | Todos los formularios crear/editar | **15+** |
| `usarEliminar` | Todos los botones eliminar | **15+** |
| `FormularioCRUD` | Todos los formularios | **15+** |
| `CampoEntrada` | Todos los formularios | **50+** |
| `CampoSeleccion` | Formularios con selects | **20+** |
| `CampoBusqueda` | Todas las páginas con buscador | **10+** |
| `ModalLimitePlan` | Productos, usuarios, almacenes, cajas (plan.limits) | **4** |
| `GuardaRol` | Cualquier sección que cambie por rol | **6+** |
| `BannerSuscripcion` | Layout admin + layout POS | **2** |
| `ReciboVenta` | Admin detalle venta + POS recibo | **2** |

### 4.7 Esquemas Zod compartidos

```ts
// lib/esquemas.ts
import { z } from "zod";

export const esquemaProducto = z.object({
  nombre: z.string().min(2, "Mínimo 2 caracteres"),
  codigo_sku: z.string().min(1, "SKU requerido"),
  categoria_id: z.number().positive("Seleccione categoría"),
  unidad_id: z.number().positive("Seleccione unidad"),
  impuesto_id: z.number().optional(),
  costo_promedio: z.number().min(0, "Costo debe ser positivo"),
  margen_pct: z.number().min(0).max(100).optional(),
  moneda_precio: z.string().default("USD"),
  activo: z.boolean().default(true),
});

export const esquemaCliente = z.object({
  tipo_documento: z.enum(["V", "E", "J", "G", "P"]),
  numero_documento: z.string().min(6, "Mínimo 6 caracteres"),
  nombre: z.string().min(2, "Nombre requerido"),
  telefono: z.string().optional(),
  email: z.string().email().optional().or(z.literal("")),
  tipo_cliente: z.enum(["natural", "juridico"]).default("natural"),
  limite_credito: z.number().min(0).optional(),
  activo: z.boolean().default(true),
});

export const esquemaVenta = z.object({
  cliente_id: z.number().positive(),
  caja_id: z.number().positive(),
  almacen_id: z.number().positive(),
  tipo_documento: z.enum(["FAC", "NE", "COT"]),
  tipo_pago: z.enum(["contado", "credito"]),
  moneda_factura: z.string().default("USD"),
});

export const esquemaLogin = z.object({
  email: z.string().email("Email inválido"),
  password: z.string().min(6, "Mínimo 6 caracteres"),
});

export const esquemaCuenta = z.object({
  name: z.string().min(2, "Nombre requerido"),
  email: z.string().email("Email inválido"),
  password: z.string().min(8, "Mínimo 8 caracteres"),
  password_confirmation: z.string(),
  pais: z.enum(["VE", "CO", "MX", "EC", "AR", "PE", "CL", "BO", "UY"]),
}).refine((d) => d.password === d.password_confirmation, {
  message: "Las contraseñas no coinciden",
  path: ["password_confirmation"],
});

export const esquemaDatosFiscales = z.object({
  identificacion_fiscal: z.string().min(5, "Identificación requerida"),
  razon_social: z.string().min(3, "Razón social requerida"),
  nombre_comercial: z.string().optional(),
  direccion: z.string().optional(),
  telefono: z.string().optional(),
});

export const esquemaConfigurarNegocio = z.object({
  tipo_negocio: z.enum(["farmacia", "ferreteria", "bodega", "restaurante", "licoreria", "abarrotes", "ropa", "motos", "general"]),
  nombre_almacen: z.string().min(2, "Nombre del almacén requerido"),
  nombre_caja: z.string().min(2, "Nombre de la caja requerido"),
  tipo_impresora: z.enum(["termica_58mm", "termica_80mm", "a4", "pdf", "ninguno"]).default("termica_80mm"),
});

export const esquemaPrimerProducto = z.object({
  nombre: z.string().min(2, "Nombre requerido"),
  sku: z.string().min(1, "SKU requerido"),
  costo: z.number().min(0, "Costo debe ser positivo"),
  aplica_iva: z.boolean().default(true),
  stock_inicial: z.number().min(0).default(0),
});
```

---

## 5. Código de Configuración Inicial

### `src/app/layout.tsx`

```tsx
import type { Metadata } from "next";
import { Inter } from "next/font/google";
import { Toaster } from "sonner";
import { ProveedorConsulta } from "@/proveedores/proveedor-consulta";
import "./globals.css";

const inter = Inter({ subsets: ["latin"], variable: "--font-inter" });

export const metadata: Metadata = {
  title: "TiendaPOS — Sistema POS Multimoneda",
  description: "Punto de venta SaaS para 9 países de Latinoamérica",
};

export default function RaizLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="es" suppressHydrationWarning>
      <body className={`${inter.variable} font-sans antialiased`}>
        <ProveedorConsulta>
          {children}
          <Toaster richColors position="top-right" />
        </ProveedorConsulta>
      </body>
    </html>
  );
}
```

### `src/app/globals.css`

```css
@tailwind base;
@tailwind components;
@tailwind utilities;

@layer base {
  :root {
    --background: 0 0% 100%;
    --foreground: 222.2 84% 4.9%;
    --card: 0 0% 100%;
    --card-foreground: 222.2 84% 4.9%;
    --popover: 0 0% 100%;
    --popover-foreground: 222.2 84% 4.9%;
    --primary: 221.2 83.2% 53.3%;
    --primary-foreground: 210 40% 98%;
    --secondary: 210 40% 96.1%;
    --secondary-foreground: 222.2 47.4% 11.2%;
    --muted: 210 40% 96.1%;
    --muted-foreground: 215.4 16.3% 46.9%;
    --accent: 210 40% 96.1%;
    --accent-foreground: 222.2 47.4% 11.2%;
    --destructive: 0 84.2% 60.2%;
    --destructive-foreground: 210 40% 98%;
    --border: 214.3 31.8% 91.4%;
    --input: 214.3 31.8% 91.4%;
    --ring: 221.2 83.2% 53.3%;
    --radius: 0.5rem;
    --chart-1: 221.2 83.2% 53.3%;
    --chart-2: 160 60% 45%;
    --chart-3: 30 80% 55%;
    --chart-4: 280 65% 60%;
    --chart-5: 340 75% 55%;
  }

  .dark {
    --background: 222.2 84% 4.9%;
    --foreground: 210 40% 98%;
    --card: 222.2 84% 4.9%;
    --card-foreground: 210 40% 98%;
    --popover: 222.2 84% 4.9%;
    --popover-foreground: 210 40% 98%;
    --primary: 217.2 91.2% 59.8%;
    --primary-foreground: 222.2 47.4% 11.2%;
    --secondary: 217.2 32.6% 17.5%;
    --secondary-foreground: 210 40% 98%;
    --muted: 217.2 32.6% 17.5%;
    --muted-foreground: 215 20.2% 65.1%;
    --accent: 217.2 32.6% 17.5%;
    --accent-foreground: 210 40% 98%;
    --destructive: 0 62.8% 30.6%;
    --destructive-foreground: 210 40% 98%;
    --border: 217.2 32.6% 17.5%;
    --input: 217.2 32.6% 17.5%;
    --ring: 224.3 76.3% 48%;
  }
}

@layer base {
  * {
    @apply border-border;
  }
  body {
    @apply bg-background text-foreground;
  }
}
```

### `src/lib/api.ts`

```ts
import axios, { AxiosError, InternalAxiosRequestConfig } from "axios";
import { useAlmacenAutenticacion } from "@/almacen/autenticacion";

const api = axios.create({
  baseURL: process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000/api/v1",
  headers: {
    Accept: "application/json",
    "Content-Type": "application/json",
  },
  timeout: 30000,
});

api.interceptors.request.use(
  (config: InternalAxiosRequestConfig) => {
    if (typeof window !== "undefined") {
      const token = localStorage.getItem("tiendapos_token");
      if (token && config.headers) {
        config.headers.Authorization = `Bearer ${token}`;
      }
    }
    return config;
  },
  (error) => Promise.reject(error)
);

api.interceptors.response.use(
  (response) => response,
  async (error: AxiosError<any>) => {
    const status = error.response?.status;
    const datos = error.response?.data as any;

    // 401: Sesión expirada — intentar refresh, si falla redirigir a login
    if (status === 401) {
      const token = localStorage.getItem("tiendapos_token");
      if (token) {
        try {
          const res = await axios.post(
            `${process.env.NEXT_PUBLIC_API_URL}/auth/refresh`,
            {},
            { headers: { Authorization: `Bearer ${token}`, Accept: "application/json" } }
          );
          if (res.data?.success) {
            localStorage.setItem("tiendapos_token", res.data.data.token);
            error.config!.headers!.Authorization = `Bearer ${res.data.data.token}`;
            return api.request(error.config!);
          }
        } catch {
          // refresh falló
        }
      }
      localStorage.removeItem("tiendapos_token");
      if (typeof window !== "undefined") {
        window.location.href = "/login";
      }
      return Promise.reject(error);
    }

    // 402: Suscripción vencida o límite de plan excedido
    if (status === 402) {
      if (datos?.error === "suscripcion_vencida") {
        if (typeof window !== "undefined") {
          window.location.href = "/suscripcion";
        }
      }
      if (datos?.error === "limite_plan_excedido") {
        // Emitir evento personalizado para mostrar modal de upgrade
        if (typeof window !== "undefined") {
          window.dispatchEvent(
            new CustomEvent("tiendapos:limite-plan", {
              detail: { recurso: datos?.recurso, mensaje: datos?.message },
            })
          );
        }
      }
      return Promise.reject(error);
    }

    // 403: Sin permisos (rol/permission insuficiente)
    if (status === 403) {
      if (typeof window !== "undefined") {
        window.dispatchEvent(
          new CustomEvent("tiendapos:acceso-denegado", {
            detail: { mensaje: datos?.message || "Sin permisos" },
          })
        );
      }
      return Promise.reject(error);
    }

    // 429: Rate limit excedido
    if (status === 429) {
      if (typeof window !== "undefined") {
        window.dispatchEvent(
          new CustomEvent("tiendapos:rate-limit", {
            detail: { mensaje: "Demasiadas solicitudes. Espera un momento." },
          })
        );
      }
      return Promise.reject(error);
    }

    return Promise.reject(error);
  }
);

export default api;
```

### `src/almacen/autenticacion.ts`

```ts
import { create } from "zustand";
import { persist } from "zustand/middleware";
import api from "@/lib/api";

interface Usuario {
  id: number;
  name: string;
  email: string;
  activo: boolean;
  roles: string[];
  tienda_id: number;
}

interface EstadoAutenticacion {
  usuario: Usuario | null;
  token: string | null;
  tiendaId: number | null;
  cargando: boolean;

  iniciarSesion: (email: string, password: string) => Promise<boolean>;
  cerrarSesion: () => Promise<void>;
  refrescarUsuario: () => Promise<void>;
  establecerToken: (token: string) => void;
  limpiarToken: () => void;

  // Helpers de roles y permisos
  tieneRol: (rol: string) => boolean;
  esAdmin: () => boolean;
  esSupervisor: () => boolean;
  esCajero: () => boolean;
}

export const useAlmacenAutenticacion = create<EstadoAutenticacion>()(
  persist(
    (set, get) => ({
      usuario: null,
      token: null,
      tiendaId: null,
      cargando: true,

      iniciarSesion: async (email, password) => {
        try {
          const { data } = await api.post("/auth/login", {
            email,
            password,
            device_name: "pos-web",
          });
          if (data.success) {
            const token = data.data.token;
            const usuario = data.data.user;
            localStorage.setItem("tiendapos_token", token);
            set({ token, usuario, tiendaId: usuario.tienda_id, cargando: false });
            return true;
          }
          return false;
        } catch {
          return false;
        }
      },

      cerrarSesion: async () => {
        try {
          await api.post("/auth/logout");
        } catch {
          // ignorar
        }
        localStorage.removeItem("tiendapos_token");
        set({ token: null, usuario: null, tiendaId: null });
      },

      refrescarUsuario: async () => {
        try {
          const { data } = await api.get("/auth/me");
          if (data.success) {
            set({ usuario: data.data, tiendaId: data.data.tienda_id, cargando: false });
          }
        } catch {
          set({ usuario: null, token: null, cargando: false });
          localStorage.removeItem("tiendapos_token");
        }
      },

      establecerToken: (token) => {
        localStorage.setItem("tiendapos_token", token);
        set({ token });
      },

      limpiarToken: () => {
        localStorage.removeItem("tiendapos_token");
        set({ token: null, usuario: null, tiendaId: null });
      },

      tieneRol: (rol) => {
        const { usuario } = get();
        return usuario?.roles?.includes(rol) ?? false;
      },

      esAdmin: () => get().tieneRol("admin"),
      esSupervisor: () => get().tieneRol("supervisor"),
      esCajero: () => get().tieneRol("cajero"),
    }),
    {
      name: "tiendapos-auth",
      partialize: (estado) => ({
        token: estado.token,
        usuario: estado.usuario,
        tiendaId: estado.tiendaId,
      }),
    }
  )
);
```

### `src/middleware.ts`

```ts
import { NextResponse } from "next/server";
import type { NextRequest } from "next/server";

const RUTAS_PUBLICAS = ["/login", "/registro", "/recuperar-password", "/restablecer-password"];
const RUTAS_ONBOARDING = ["/onboarding"];
const RUTAS_SUSCRIPCION = ["/suscripcion"];

export function middleware(solicitud: NextRequest) {
  const token = solicitud.cookies.get("tiendapos_token")?.value;
  const { pathname } = solicitud.nextUrl;

  // Archivos estáticos e internos: permitir siempre
  if (
    pathname.startsWith("/_next") ||
    pathname.startsWith("/api") ||
    pathname.includes(".") // archivos con extensión
  ) {
    return NextResponse.next();
  }

  // Rutas públicas: permitir siempre
  if (RUTAS_PUBLICAS.some((ruta) => pathname.startsWith(ruta))) {
    return NextResponse.next();
  }

  // Sin token: redirigir a login
  if (!token) {
    const urlLogin = new URL("/login", solicitud.url);
    urlLogin.searchParams.set("redirect", pathname);
    return NextResponse.redirect(urlLogin);
  }

  return NextResponse.next();
}

export const config = {
  matcher: ["/((?!_next/static|_next/image|favicon.ico|sw.js).*)"],
};
```

### `src/proveedores/proveedor-consulta.tsx`

```tsx
"use client";

import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { useState } from "react";

export function ProveedorConsulta({ children }: { children: React.ReactNode }) {
  const [clienteConsulta] = useState(
    () =>
      new QueryClient({
        defaultOptions: {
          queries: {
            staleTime: 60 * 1000, // 1 minuto
            retry: (conteoFallos, error: any) => {
              // No reintentar si es 402 (suscripción) o 403 (permisos)
              if (error?.response?.status === 402 || error?.response?.status === 403) return false;
              return conteoFallos < 2;
            },
            refetchOnWindowFocus: false,
          },
        },
      })
  );

  return <QueryClientProvider client={clienteConsulta}>{children}</QueryClientProvider>;
}
```

### `src/lib/constantes.ts`

```ts
// Roles del sistema (Spatie)
export const ROLES = {
  ADMIN: "admin",
  SUPERVISOR: "supervisor",
  CAJERO: "cajero",
} as const;

// Permisos críticos usados en el frontend
export const PERMISOS = {
  ANULAR_VENTA: "anular_venta",
  CREAR_DEVOLUCION: "crear_devolucion",
  CREAR_CREDITO: "crear_credito",
  REGISTRAR_ABONO: "registrar_abono",
  APLICAR_DESCUENTO: "aplicar_descuento",
  VER_REPORTES: "ver_reportes",
  CREAR_PRODUCTOS: "crear_productos",
  CREAR_USUARIOS: "crear_usuarios",
} as const;

// Países soportados (9 LatAm)
export const PAISES = {
  VE: { nombre: "Venezuela", moneda: "USD", monedaLocal: "VES", iva: 16, igtf: 3, idFiscal: "RIF" },
  CO: { nombre: "Colombia", moneda: "COP", monedaLocal: "COP", iva: 19, igtf: 0, idFiscal: "NIT" },
  MX: { nombre: "México", moneda: "MXN", monedaLocal: "MXN", iva: 16, igtf: 0, idFiscal: "RFC" },
  EC: { nombre: "Ecuador", moneda: "USD", monedaLocal: "USD", iva: 15, igtf: 0, idFiscal: "RUC" },
  AR: { nombre: "Argentina", moneda: "ARS", monedaLocal: "ARS", iva: 21, igtf: 0, idFiscal: "CUIT" },
  PE: { nombre: "Perú", moneda: "PEN", monedaLocal: "PEN", iva: 18, igtf: 0, idFiscal: "RUC" },
  CL: { nombre: "Chile", moneda: "CLP", monedaLocal: "CLP", iva: 19, igtf: 0, idFiscal: "RUT" },
  BO: { nombre: "Bolivia", moneda: "BOB", monedaLocal: "BOB", iva: 13, igtf: 0, idFiscal: "NIT" },
  UY: { nombre: "Uruguay", moneda: "UYU", monedaLocal: "UYU", iva: 22, igtf: 0, idFiscal: "RUT" },
} as const;

// Estados de venta
export const ESTADOS_VENTA = {
  BORRADOR: "borrador",
  PENDIENTE: "pendiente",
  PAGADA: "pagada",
  PARCIAL: "parcial",
  ANULADA: "anulada",
} as const;

// Tipos de documento fiscal (SENIAT Venezuela)
export const TIPOS_DOCUMENTO = {
  FAC: "Factura",
  NE: "Nota de Entrega",
  NC: "Nota de Crédito",
  ND: "Nota de Débito",
  COT: "Cotización",
} as const;

// Estados de suscripción
export const ESTADOS_SUSCRIPCION = {
  TRIAL: "trial",
  ACTIVA: "activa",
  VENCIDA: "vencida",
  SUSPENDIDA: "suspendida",
  CANCELADA: "cancelada",
} as const;

// Tipos de negocio para onboarding
export const TIPOS_NEGOCIO = [
  "farmacia",
  "ferreteria",
  "bodega",
  "restaurante",
  "licoreria",
  "abarrotes",
  "ropa",
  "motos",
  "general",
] as const;
```

### `src/lib/utilidades.ts`

```ts
import { PAISES } from "./constantes";

// Formatear moneda según país
export function formatearMoneda(monto: number, moneda: string, pais: string): string {
  const paisData = PAISES[pais as keyof typeof PAISES];
  const locale = paisData ? `es-${pais}` : "es-VE";

  return new Intl.NumberFormat(locale, {
    style: "currency",
    currency: moneda,
    minimumFractionDigits: 2,
    maximumFractionDigits: moneda === "COP" || moneda === "CLP" ? 0 : 2,
  }).format(monto);
}

// Formatear fecha según zona horaria del país
export function formatearFecha(fecha: string, pais: string): string {
  const locale = pais ? `es-${pais}` : "es-VE";
  return new Date(fecha).toLocaleDateString(locale, {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
  });
}

// Formatear identificación fiscal (RIF, NIT, RFC, RUC)
export function formatearIdFiscal(id: string, pais: string): string {
  const paisData = PAISES[pais as keyof typeof PAISES];
  if (!paisData) return id;
  return `${paisData.idFiscal}: ${id}`;
}

// Calcular IGTF (3% solo en Venezuela para pagos en divisa)
export function calcularIGTF(monto: number, pais: string, esPagoEnDivisa: boolean): number {
  if (pais !== "VE" || !esPagoEnDivisa) return 0;
  return monto * 0.03;
}

// Calcular IVA
export function calcularIVA(subtotal: number, porcentajeIVA: number): number {
  return subtotal * (porcentajeIVA / 100);
}

// Construir número de factura
export function construirNumeroFactura(prefijo: string, numero: number): string {
  return `${prefijo}-${String(numero).padStart(6, "0")}`;
}
```

### `src/lib/consultas.ts`

```ts
// Factory de query keys para TanStack Query
export const claves = {
  auth: {
    yo: ["auth", "yo"] as const,
  },
  productos: {
    todos: (filtros?: Record<string, any>) => ["productos", filtros] as const,
    detalle: (id: number) => ["productos", id] as const,
  },
  clientes: {
    todos: (filtros?: Record<string, any>) => ["clientes", filtros] as const,
    detalle: (id: number) => ["clientes", id] as const,
  },
  ventas: {
    todos: (filtros?: Record<string, any>) => ["ventas", filtros] as const,
    detalle: (id: number) => ["ventas", id] as const,
  },
  inventario: {
    todos: (filtros?: Record<string, any>) => ["inventario", filtros] as const,
    detalle: (id: number) => ["inventario", id] as const,
  },
  cajas: {
    todas: ["cajas"] as const,
    sesiones: (filtros?: Record<string, any>) => ["cajas", "sesiones", filtros] as const,
  },
  almacenes: {
    todos: ["almacenes"] as const,
  },
  traslados: {
    todos: (filtros?: Record<string, any>) => ["traslados", filtros] as const,
    detalle: (id: number) => ["traslados", id] as const,
  },
  ajustes: {
    todos: (filtros?: Record<string, any>) => ["ajustes", filtros] as const,
    detalle: (id: number) => ["ajustes", id] as const,
  },
  proveedores: {
    todos: (filtros?: Record<string, any>) => ["proveedores", filtros] as const,
    detalle: (id: number) => ["proveedores", id] as const,
  },
  ordenesCompra: {
    todas: (filtros?: Record<string, any>) => ["ordenes-compra", filtros] as const,
    detalle: (id: number) => ["ordenes-compra", id] as const,
  },
  recepciones: {
    todas: (filtros?: Record<string, any>) => ["recepciones", filtros] as const,
    detalle: (id: number) => ["recepciones", id] as const,
  },
  creditos: {
    todos: (filtros?: Record<string, any>) => ["creditos", filtros] as const,
    detalle: (id: number) => ["creditos", id] as const,
  },
  abonos: {
    todos: (filtros?: Record<string, any>) => ["abonos", filtros] as const,
  },
  devoluciones: {
    todas: (filtros?: Record<string, any>) => ["devoluciones", filtros] as const,
    detalle: (id: number) => ["devoluciones", id] as const,
  },
  usuarios: {
    todos: (filtros?: Record<string, any>) => ["usuarios", filtros] as const,
    detalle: (id: number) => ["usuarios", id] as const,
  },
  categorias: {
    todas: ["categorias"] as const,
  },
  impuestos: {
    todos: ["impuestos"] as const,
  },
  descuentos: {
    todos: ["descuentos"] as const,
  },
  metodosPago: {
    todos: ["metodos-pago"] as const,
  },
  tasasCambio: {
    todas: ["tasas-cambio"] as const,
  },
  listasPrecio: {
    todas: ["listas-precio"] as const,
  },
  margenes: {
    todos: ["margenes"] as const,
  },
  suscripcion: {
    estado: ["suscripcion", "estado"] as const,
    planes: ["suscripcion", "planes"] as const,
  },
  onboarding: {
    estado: ["onboarding", "estado"] as const,
    etiquetas: (pais: string) => ["onboarding", "etiquetas", pais] as const,
  },
  dashboard: {
    datos: ["dashboard"] as const,
  },
  tienda: {
    datos: ["tienda"] as const,
  },
  reportes: {
    ventas: (filtros?: Record<string, any>) => ["reportes", "ventas", filtros] as const,
    inventario: (filtros?: Record<string, any>) => ["reportes", "inventario", filtros] as const,
    rentabilidad: (filtros?: Record<string, any>) => ["reportes", "rentabilidad", filtros] as const,
    creditos: (filtros?: Record<string, any>) => ["reportes", "creditos", filtros] as const,
  },
};
```

---

## 5. Mapeo de Integración Backend → Frontend

### 5.1 Flujo del Wizard de Onboarding (4 Pasos)

```
┌──────────────────────────────────────────────────────────────────────┐
│  PASO 1: Crear Cuenta (PÚBLICO, sin token)                           │
│  POST /api/v1/onboarding/cuenta                                      │
│  Body: { name, email, password, password_confirmation, pais }        │
│  Respuesta: { token, user, tienda }                                  │
│  → Guardar token en localStorage + Zustand                            │
│  → Redirigir a /onboarding/datos-fiscales                            │
├──────────────────────────────────────────────────────────────────────┤
│  PASO 2: Datos Fiscales (AUTENTICADO)                                 │
│  POST /api/v1/onboarding/datos-fiscales                               │
│  Body: { identificacion_fiscal, razon_social, nombre_comercial,      │
│          direccion, telefono, regimen_fiscal }                        │
│  → Auto-siembra: impuestos del país + monedas + tasa inicial          │
│  → El endpoint GET /api/v1/onboarding/etiquetas/{pais}               │
│    devuelve las etiquetas dinámicas (RIF/NIT/RFC)                    │
│  → Redirigir a /onboarding/configurar-negocio                        │
├──────────────────────────────────────────────────────────────────────┤
│  PASO 3: Configurar Negocio (AUTENTICADO)                             │
│  POST /api/v1/onboarding/configurar-negocio                           │
│  Body: { tipo_negocio, nombre_almacen, nombre_caja, tipo_impresora }  │
│  → Auto-siembra: almacén + caja + categorías + métodos de pago       │
│    + cliente CONSUMIDOR FINAL + margen 20% + lista precio            │
│  → Redirigir a /onboarding/primer-producto                           │
├──────────────────────────────────────────────────────────────────────┤
│  PASO 4: Primer Producto (AUTENTICADO, OPCIONAL)                      │
│  POST /api/v1/onboarding/primer-producto                              │
│  Body: { nombre, sku, costo, aplica_iva, stock_inicial }              │
│  —o—                                                                  │
│  POST /api/v1/onboarding/saltar-primer-producto                       │
│  → onboarding_completado = true                                      │
│  → Redirigir a /dashboard                                            │
└──────────────────────────────────────────────────────────────────────┘
```

**Consultar estado del onboarding en cualquier momento:**
```
GET /api/v1/onboarding/estado
→ { paso_actual: 2, completado: false, pasos: [...] }
```
El frontend debe usar esto para redirigir al usuario al paso correcto si cierra el wizard a medias.

### 5.2 Verificación de Límites del Plan (`plan.limits`)

El middleware `EnforcePlanLimits` se ejecuta **antes** de crear un recurso. Los endpoints afectados son:

| Endpoint | Middleware | Código 402 si excede |
|---|---|---|
| `POST /api/v1/productos` | `plan.limits:productos` | `limite_plan_excedido`, recurso: `productos` |
| `POST /api/v1/almacenes` | `plan.limits:almacenes` | `limite_plan_excedido`, recurso: `almacenes` |
| `POST /api/v1/cajas` | `plan.limits:cajas` | `limite_plan_excedido`, recurso: `cajas` |
| `POST /api/v1/usuarios` | `plan.limits:usuarios` | `limite_plan_excedido`, recurso: `usuarios` |

**Respuesta 402 del backend:**
```json
{
  "success": false,
  "error": "limite_plan_excedido",
  "recurso": "productos",
  "message": "Has alcanzado el límite de 50 productos de tu plan.",
  "action": "actualizar_plan"
}
```

**Qué hacer en el frontend:**
1. El interceptor de Axios (en `api.ts`) captura el 402 y emite un `CustomEvent` `tiendapos:limite-plan`
2. Un listener global muestra el `ModalLimitePlan` con el recurso y mensaje
3. El modal tiene un botón "Actualizar Plan" que redirige a `/suscripcion`

**Consulta de límites actuales:**
```
GET /api/v1/suscripcion/estado
→ { limites: { productos: 50, usuarios: 2, almacenes: 1, cajas: 1 } }
```

### 5.3 POS Multimoneda y Cálculo de IGTF

#### Flujo de venta en el POS

```
1. Buscar producto (GET /api/v1/productos?buscar=harina)
2. Agregar al carrito (front-end state en Zustand)
3. Seleccionar método de pago
4. Si el método de pago tiene grava_igtf=true Y el país es VE:
   → Calcular IGTF = monto_pago × 3%
   → Mostrar línea separada de IGTF en el recibo
5. Registrar pago(s) - puede haber múltiples pagos en diferentes monedas
6. Cerrar venta (POST /api/v1/ventas)
```

#### Cálculo del IGTF (solo Venezuela)

```
IGTF solo aplica cuando:
  - tienda.pais === 'VE'
  - metodo_pago.grava_igtf === true (ej: Zelle, USDT, tarjeta USD)
  - El pago se hace en divisa extranjera

IGTF NO aplica cuando:
  - Pago en efectivo VES
  - Pago Móvil VES
  - País no es Venezuela

Fórmula: IGTF = monto_pago_en_divisa × 0.03
```

#### Estructura de una venta (POST /api/v1/ventas)

```json
{
  "cliente_id": 1,
  "caja_id": 1,
  "almacen_id": 1,
  "tipo_documento": "FAC",
  "tipo_pago": "contado",
  "moneda_factura": "USD",
  "items": [
    {
      "variante_id": 1,
      "cantidad": 2,
      "precio_unitario": 5.00,
      "descuento_pct": 0,
      "impuesto_monto": 1.60
    }
  ],
  "pagos": [
    {
      "metodo_pago_id": 1,
      "monto": 11.60,
      "moneda": "USD",
      "tasa_usada": 1,
      "monto_igtf": 0.35
    }
  ]
}
```

#### Manejo de tasas de cambio

```
GET /api/v1/tasas-cambio
→ Lista de tasas activas

Ejemplo VE: tasa BCV USD→VES = 36.50
- Si la factura es en USD y el pago es en VES:
  monto_en_moneda_pago = monto × tasa
- Si la factura es en VES y el pago es en USD:
  monto_en_moneda_pago = monto / tasa
```

### 5.4 Dos Dashboards: Admin vs Vendedor

**No se necesita programar nada en el backend.** El backend ya devuelve `roles` en `GET /auth/me`. La separación es 100% frontend.

#### Lógica de redirección después del login

```ts
// En el componente de login o en el layout (auth)
const redirigirSegunRol = (roles: string[]) => {
  if (roles.includes("admin")) {
    router.push("/admin");       // Dashboard administrador
  } else {
    router.push("/pos");         // Dashboard vendedor/cajero/supervisor
  }
};
```

#### Dashboard Admin (`/admin/*`)

```
Interfaz completa de gestión:
├── Métricas: ventas del día, productos bajos, créditos pendientes
├── CRUD completo: productos, clientes, proveedores, usuarios
├── Inventario: traslados, ajustes, recepciones, órdenes de compra
├── Créditos: cuentas, abonos, devoluciones
├── Reportes: ventas, inventario, rentabilidad, créditos
├── Configuración: tienda, usuarios, categorías, impuestos, tasas, etc.
└── Sidebar completo con todos los módulos
```

#### Dashboard Vendedor (`/pos/*`)

```
Interfaz optimizada para vender rápido:
├── Cobrar: grid productos + carrito + pagos multimoneda
├── Mis ventas del día (sin acceso a todas las ventas)
├── Clientes: buscar rápido (sin gestión completa)
├── Caja: abrir/cerrar mi caja, retiros
├── Cotizaciones: crear/ver
├── Devoluciones: solo si tiene permiso crear_devolucion
└── Sin sidebar pesado — barra superior compacta con navegación mínima
```

#### ¿Por qué route groups separados?

| Aspecto | `(admin)` | `(pos)` |
|---|---|---|
| **Layout** | Sidebar completo con colapsables | Barra superior compacta |
| **Sidebar items** | Todos los módulos | Solo cobrar, ventas, clientes, caja |
| **Redirección** | Si no es admin → `/pos` | Si es admin puede entrar pero no es su default |
| **Velocidad** | Carga módulos pesados (reportes, gráficos) | Carga solo lo necesario para vender |
| **UX** | Gestión y análisis | Velocidad de cobro |

### 5.5 Matriz de Roles vs Funcionalidad

| Módulo | admin | supervisor | cajero |
|---|---|---|---|
| Dashboard | Completo | Completo | Básico |
| Ventas | CRUD + anular | CRUD + anular | Crear + ver |
| Productos | CRUD | Ver + editar | Solo ver |
| Clientes | CRUD | CRUD | Ver + crear |
| Inventario | Todo | Ajustes + traslados | Solo ver |
| Caja | Abrir/cerrar | Abrir/cerrar | Abrir/cerrar |
| Créditos | Todo | Ver + abonos | Solo ver |
| Devoluciones | Crear + aprobar | Crear | — |
| Configuración | Todo | Solo ver | — |
| Usuarios | CRUD | — | — |
| Reportes | Todo | Todo | — |
| Tienda | Editar | — | — |

### 5.6 Middleware Chain — Respuestas HTTP del Backend

```
Petición HTTP → auth:sanctum → activo → suscripcion → plan.limits → role → permission → Controller

401 → Token inválido/expirado     → Interceptor refresca token o redirige a /login
403 → Usuario inactivo            → "Esta cuenta está desactivada"
403 → Sin rol/permiso             → RoleGuard/PermissionGuard oculta la UI
402 → Suscripción vencida        → Redirigir a /suscripcion
402 → Límite de plan excedido    → Mostrar ModalLimitePlan
422 → Validación fallida          → React Hook Form muestra errores en campos
429 → Rate limit excedido         → Mostrar toast "Espera un momento"
500 → Error del servidor          → Toast genérico de error
```

---

## 6. Endpoints Completos del Backend por Módulo

### Auth (Público + Autenticado)

| Método | Ruta | Descripción | Auth |
|---|---|---|---|
| POST | `/auth/login` | Login → token | No |
| POST | `/auth/forgot-password` | Enviar código reset | No |
| POST | `/auth/verify-token` | Verificar código | No |
| POST | `/auth/reset-password` | Nueva contraseña | No |
| GET | `/auth/me` | Perfil usuario | Sí |
| POST | `/auth/refresh` | Rotar token | Sí |
| POST | `/auth/logout` | Cerrar sesión | Sí |
| POST | `/auth/cambiar-password` | Cambiar contraseña | Sí |

### Onboarding

| Método | Ruta | Descripción | Auth |
|---|---|---|---|
| POST | `/onboarding/cuenta` | Paso 1: Crear cuenta | No |
| GET | `/onboarding/etiquetas/{pais}` | Etiquetas fiscales | No |
| GET | `/onboarding/estado` | Estado actual | Sí |
| POST | `/onboarding/datos-fiscales` | Paso 2 | Sí |
| POST | `/onboarding/configurar-negocio` | Paso 3 | Sí |
| POST | `/onboarding/primer-producto` | Paso 4 | Sí |
| POST | `/onboarding/saltar-primer-producto` | Saltar paso 4 | Sí |

### Suscripción

| Método | Ruta | Descripción | Auth |
|---|---|---|---|
| GET | `/suscripcion/planes` | Planes disponibles | No |
| GET | `/suscripcion/estado` | Estado + límites | Sí |
| POST | `/suscripcion/activar` | Activar plan pagado | Sí |
| POST | `/suscripcion/cancelar` | Cancelar | Sí |

### Negocio (requiere suscripción activa)

| Módulo | Ruta base | Notas |
|---|---|---|
| Productos | `/productos` | `plan.limits:productos` al crear |
| Variantes | `/variantes` | — |
| Clientes | `/clientes` | Filtros: buscar, tipo_cliente, activo |
| Ventas | `/ventas` | `permission:anular_venta` para anular |
| Cajas | `/cajas` | `plan.limits:cajas` al crear; abrir/cerrar |
| Sesiones Caja | `/sesiones-caja` | — |
| Movimientos Caja | `/movimientos-caja` | Retiros, gastos |
| Inventario | `/inventario` | Filtros: almacen_id, stock_bajo |
| Almacenes | `/almacenes` | `plan.limits:almacenes` al crear |
| Traslados | `/traslados` | + confirmar |
| Ajustes | `/ajustes` | + confirmar |
| Proveedores | `/proveedores` | — |
| Órdenes Compra | `/ordenes-compra` | — |
| Recepciones | `/recepciones` | — |
| Créditos | `/creditos` | `permission:crear_credito` al crear |
| Abonos | `/abonos` | `permission:registrar_abono` al crear |
| Devoluciones | `/devoluciones` | `permission:crear_devolucion` al crear |
| Categorías | `/categorias` | — |
| Impuestos | `/impuestos` | — |
| Descuentos | `/descuentos` | — |
| Métodos Pago | `/metodos-pago` | campo `grava_igtf` para VE |
| Tasas Cambio | `/tasas-cambio` | Auto-desactiva tasa anterior |
| Listas Precio | `/listas-precio` | — |
| Márgenes | `/margenes` | — |
| Dashboard | `/dashboard` | — |
| Tienda | `/tienda` | PUT solo admin |

### Admin (role:admin)

| Módulo | Ruta base | Notas |
|---|---|---|
| Usuarios | `/usuarios` | `plan.limits:usuarios` al crear |
| Reportes | `/reportes/ventas`, `/inventario`, `/rentabilidad`, `/creditos` | Solo admin |
| Tienda (escritura) | `PUT /tienda` | Solo admin |

---

*TiendaPOS v2.1 — Guía Frontend — Generada 2026*
