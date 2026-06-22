# TiendaPOS API — Documentación Técnica v2.1

> Sistema POS SaaS Multimoneda + IGTF construido con Laravel 11, PostgreSQL (Neon.tech), Sanctum y Spatie Permissions.

---

## Tabla de Contenidos

- [Arquitectura General](#arquitectura-general)
- [Stack Tecnológico](#stack-tecnológico)
- [Modelo SaaS y Multitenencia](#modelo-saas-y-multitenencia)
- [Autenticación y Autorización](#autenticación-y-autorización)
- [Onboarding (4 Pasos)](#onboarding-4-pasos)
- [Suscripciones y Planes](#suscripciones-y-planes)
- [Middlewares (Capa de Protección)](#middlewares-capa-de-protección)
- [Catálogo Completo de Endpoints](#catálogo-completo-de-endpoints)
- [Integración con Next.js](#integración-con-nextjs)
- [Servicios y Lógica de Negocio](#servicios-y-lógica-de-negocio)
- [Jobs Programados](#jobs-programados)
- [Base de Datos](#base-de-datos)
- [Instalación y Configuración](#instalación-y-configuración)
- [Testing](#testing)

---

## Arquitectura General

```
┌─────────────────────────────────────────────────────────────┐
│                    NEXT.JS (Frontend)                        │
│         App Router + Zustand + React Query                  │
└────────────────────────┬────────────────────────────────────┘
                         │ HTTP + Bearer Token
                         ▼
┌─────────────────────────────────────────────────────────────┐
│                  LARAVEL 11 API (Backend)                    │
│                                                              │
│  ┌─────────┐  ┌──────────┐  ┌───────────┐  ┌───────────┐  │
│  │  Auth    │  │Onboarding│  │Suscripción│  │   CRUD    │  │
│  │Sanctum  │  │  4 pasos │  │  + Planes  │  │ Recursos  │  │
│  └────┬────┘  └────┬─────┘  └─────┬─────┘  └─────┬─────┘  │
│       │            │              │               │         │
│  ┌────▼────────────▼──────────────▼───────────────▼─────┐  │
│  │              MIDDLEWARE CHAIN                         │  │
│  │  auth:sanctum → activo → suscripcion → plan.limits  │  │
│  │                            → role → permission       │  │
│  └────────────────────┬─────────────────────────────────┘  │
│                       │                                     │
│  ┌────────────────────▼─────────────────────────────────┐  │
│  │              SERVICES LAYER                          │  │
│  │  OnboardingService · SuscripcionService              │  │
│  │  PosBusinessRulesService · InventoryService          │  │
│  └────────────────────┬─────────────────────────────────┘  │
│                       │                                     │
│  ┌────────────────────▼─────────────────────────────────┐  │
│  │         POSTGRESQL (Neon.tech) — 80 tablas           │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
```

---

## Stack Tecnológico

| Componente | Tecnología | Versión |
|---|---|---|
| Backend | Laravel | 11.x |
| Base de datos | PostgreSQL (Neon.tech) | 16+ |
| Autenticación | Laravel Sanctum | API Tokens |
| Roles/Permisos | Spatie laravel-permission | 6.x |
| Colas | database driver | — |
| Caché | database driver | — |
| Sesiones | cookie (stateless API) | — |
| Frontend (recomendado) | Next.js 14+ App Router | — |

---

## Modelo SaaS y Multitenencia

### Aislamiento por tienda (`tienda_id`)

Cada registro pertenece a una tienda. El trait `BelongsToTienda` aplica automáticamente `WHERE tienda_id = X` en todas las consultas del modelo.

```
Tienda (1) ──→ (N) Usuarios
Tienda (1) ──→ (1) Suscripción
Tienda (1) ──→ (1) TiendaOnboarding
Tienda (1) ──→ (N) Almacenes, Cajas, Productos, Clientes...
```

### Planes y Límites

| Plan | Productos | Usuarios | Almacenes | Cajas | Precio |
|---|---|---|---|---|---|
| Trial | 50 | 2 | 1 | 1 | Gratis (14 días) |
| Básico | 200 | 5 | 2 | 2 | $19/mes |
| Pro | 1000 | 15 | 5 | 5 | $49/mes |
| Premium | ∞ | ∞ | ∞ | ∞ | $99/mes |

### Países Soportados (9)

`VE` `CO` `MX` `EC` `AR` `PE` `CL` `BO` `UY`

Cada país tiene configuración automática de:
- Impuestos (IVA/IGV según tasa local)
- Monedas (base + secundarias)
- Métodos de pago (locales: PSE, Yape, Plin, Nequi, Zelle, Pago Móvil, etc.)
- Etiquetas fiscales (RIF/NIT/RFC/RUC)
- Tasa de cambio inicial (para VE: USD→VES BCV)

---

## Autenticación y Autorización

### Flujo de Autenticación (Sanctum Token)

```
1. POST /api/login → { token, user, expires_in }
2. GET  /api/user  → Authorization: Bearer {token}
3. POST /api/auth/refresh → nuevo token (rotación)
4. POST /api/logout → token eliminado
```

### Token Abilities por Rol

| Rol | Abilities | Descripción |
|---|---|---|
| admin | `["*"]` | Acceso total |
| supervisor | `["ventas","inventario","caja","creditos","reportes","devoluciones"]` | Operación + reportes |
| cajero | `["ventas","caja","clientes"]` | Ventas, caja, clientes, inventario, cotizaciones |

### Recuperación de Contraseña

```
1. POST /api/v1/auth/forgot-password  → envía código 6 chars al email
2. POST /api/v1/auth/verify-token    → verifica si el token es válido
3. POST /api/v1/auth/reset-password  → restablece la contraseña (expira 60 min)
```

**Throttle:** 3 intentos por minuto en endpoints de password reset, 5/min en login.

### Cambio de Contraseña (usuario autenticado)

```
POST /api/auth/cambiar-password
{
  "password_actual": "oldpass",
  "password_nueva": "newpass",
  "password_nueva_confirmation": "newpass"
}
```

---

## Onboarding (4 Pasos)

El onboarding es un wizard que configura toda la tienda nueva:

```
Paso 1: Crear Cuenta (PÚBLICO)
   POST /api/v1/onboarding/cuenta
   → Crea: User + Tienda + Suscripción Trial + Token Sanctum
   → Asigna rol admin automáticamente

Paso 2: Datos Fiscales (AUTENTICADO)
   POST /api/v1/onboarding/datos-fiscales
   → Guarda: RIF/NIT/RFC, razón social, dirección
   → Auto-siembra: Impuestos del país + Monedas + Tasa inicial

Paso 3: Configurar Negocio (AUTENTICADO)
   POST /api/v1/onboarding/configurar-negocio
   → Crea: Almacén + Caja + Categorías + Métodos de pago + Cliente default
   → Crea: Margen estándar 20% + Lista de precio + Config impresora

Paso 4: Primer Producto (AUTENTICADO, OPCIONAL)
   POST /api/v1/onboarding/primer-producto
   → Crea: Producto + Variante + Inventario inicial
   — o —
   POST /api/v1/onboarding/saltar-primer-producto
   → Marca onboarding como completado sin producto
```

### Consultar estado del onboarding

```
GET /api/v1/onboarding/estado
→ { paso_actual: 2, completado: false, pasos: [...] }
```

### Etiquetas fiscales por país (PÚBLICO)

```
GET /api/v1/onboarding/etiquetas/VE
→ { "identificacion_fiscal": { "etiqueta": "RIF", "placeholder": "J-12345678-9" }, ... }
```

---

## Suscripciones y Planes

### Flujo de Suscripción

```
┌─────────┐    14 días     ┌─────────┐    pago     ┌─────────┐
│  TRIAL  │───────────────→│ VENCIDA │───────────→│  ACTIVA  │
└─────────┘                └─────────┘             └─────────┘
                                │                       │
                                │ no paga               │ cancela
                                ▼                       ▼
                          ┌──────────┐           ┌───────────┐
                          │ SUSPEND. │           │ CANCELADA  │
                          └──────────┘           └───────────┘
```

### Endpoints de Suscripción

| Método | URI | Descripción |
|---|---|---|
| GET | `/api/v1/suscripcion/estado` | Estado actual + días restantes + límites del plan |
| GET | `/api/v1/suscripcion/planes` | Lista planes disponibles |
| POST | `/api/v1/suscripcion/activar` | Activa un plan pagado |
| POST | `/api/v1/suscripcion/cancelar` | Cancela suscripción (acceso hasta fin de período) |

### Respuesta de estado (ejemplo)

```json
{
  "success": true,
  "data": {
    "estado": "trial",
    "plan": "Trial",
    "plan_id": 1,
    "es_trial": true,
    "dias_restantes": 12,
    "fin_trial": "2026-07-02T00:00:00.000000Z",
    "limites": {
      "productos": 50,
      "usuarios": 2,
      "almacenes": 1,
      "cajas": 1
    }
  }
}
```

---

## Middlewares (Capa de Protección)

Las rutas están protegidas por una cadena de middlewares en capas:

```
Petición → auth:sanctum → activo → suscripcion → plan.limits → role → permission → Controller
```

| Middleware | Alias | Descripción | Código HTTP |
|---|---|---|---|
| `EnsureUserIsActive` | `activo` | Usuario debe estar activo (`activo=true`) | 403 |
| `CheckSuscripcionActiva` | `suscripcion` | Suscripción debe ser trial/activa (no vencida/suspendida) | 402 |
| `EnforcePlanLimits` | `plan.limits:recurso` | No exceder límites del plan (productos, usuarios, almacenes, cajas) | 402 |
| `CheckRole` | `role:admin` | Usuario debe tener uno de los roles especificados | 403 |
| `CheckPermission` | `permission:anular_venta` | Usuario debe tener uno de los permisos especificados | 403 |

### Ejemplo: Respuesta 402 (suscripción vencida)

```json
{
  "success": false,
  "error": "suscripcion_vencida",
  "message": "Tu período de prueba finalizó el 15/06/2026. Suscríbete a un plan para continuar.",
  "action": "actualizar_plan"
}
```

### Ejemplo: Respuesta 402 (límite excedido)

```json
{
  "success": false,
  "error": "limite_plan_excedido",
  "recurso": "productos",
  "message": "Has alcanzado el límite de 50 productos de tu plan. Actualiza tu plan para agregar más.",
  "action": "actualizar_plan"
}
```

---

## Catálogo Completo de Endpoints

### Rutas Públicas (sin autenticación)

| Método | URI | Descripción |
|---|---|---|
| POST | `/api/login` | Login (throttle: 5/min) |
| POST | `/api/v1/onboarding/cuenta` | Crear cuenta nueva |
| GET | `/api/v1/onboarding/etiquetas/{pais}` | Etiquetas fiscales por país |
| POST | `/api/v1/auth/forgot-password` | Solicitar reset de contraseña (throttle: 3/min) |
| POST | `/api/v1/auth/reset-password` | Restablecer contraseña |
| POST | `/api/v1/auth/verify-token` | Verificar token de reset |

### Rutas Autenticadas (auth:sanctum + activo)

| Método | URI | Descripción |
|---|---|---|
| POST | `/api/logout` | Cerrar sesión |
| GET | `/api/user` | Usuario actual |
| POST | `/api/auth/refresh` | Rotar token |
| POST | `/api/auth/cambiar-password` | Cambiar contraseña |
| GET | `/api/tienda` | Configuración de la tienda |

### Onboarding Privado (no requiere suscripción activa)

| Método | URI | Descripción |
|---|---|---|
| GET | `/api/v1/onboarding/estado` | Estado del onboarding |
| POST | `/api/v1/onboarding/datos-fiscales` | Paso 2: datos fiscales |
| POST | `/api/v1/onboarding/configurar-negocio` | Paso 3: configurar negocio |
| POST | `/api/v1/onboarding/primer-producto` | Paso 4: primer producto |
| POST | `/api/v1/onboarding/saltar-primer-producto` | Saltar paso 4 |

### Suscripción (accesible con trial vencido para pagar)

| Método | URI | Descripción |
|---|---|---|
| GET | `/api/v1/suscripcion/estado` | Estado de suscripción |
| GET | `/api/v1/suscripcion/planes` | Planes disponibles |
| POST | `/api/v1/suscripcion/activar` | Activar plan |
| POST | `/api/v1/suscripcion/cancelar` | Cancelar suscripción |

### Rutas Protegidas por Suscripción (requieren suscripción activa)

#### Productos

| Método | URI | Middleware Extra |
|---|---|---|
| GET | `/api/productos` | — |
| POST | `/api/productos` | `plan.limits:productos` |
| GET | `/api/productos/{id}` | — |
| PUT/PATCH | `/api/productos/{id}` | — |
| DELETE | `/api/productos/{id}` | — |

**Filtros disponibles:** `categoria_id`, `buscar`, `activo`

#### Clientes

| Método | URI | Middleware Extra |
|---|---|---|
| GET | `/api/clientes` | — |
| POST | `/api/clientes` | — |
| GET | `/api/clientes/{id}` | — |
| PUT/PATCH | `/api/clientes/{id}` | — |
| DELETE | `/api/clientes/{id}` | — |

**Filtros disponibles:** `nombre`, `documento`, `telefono`, `tipo_cliente`, `activo`

#### Ventas / POS

| Método | URI | Middleware Extra |
|---|---|---|
| GET | `/api/ventas` | — |
| POST | `/api/ventas` | — |
| GET | `/api/ventas/{id}` | — |
| PUT/PATCH | `/api/ventas/{id}` | — |
| POST | `/api/ventas/{id}/anular` | `permission:anular_venta` |

**Filtros disponibles:** `estado`, `tipo_documento`, `cliente_id`, `desde`, `hasta`

**Proceso de venta en 4 pasos:**
1. Crear cabecera (cliente, caja, almacen, tipo_documento)
2. Agregar items (producto, cantidad, precio, impuesto, descuento)
3. Registrar pagos (método, monto, moneda, referencia)
4. Cerrar venta (validación de totales, actualización de inventario)

#### Caja

| Método | URI | Middleware Extra |
|---|---|---|
| GET | `/api/cajas` | — |
| POST | `/api/cajas` | `plan.limits:cajas` |
| GET | `/api/cajas/{id}` | — |
| PUT/PATCH | `/api/cajas/{id}` | — |
| DELETE | `/api/cajas/{id}` | — |
| POST | `/api/cajas/{caja}/abrir` | — |
| POST | `/api/cajas/{caja}/cerrar` | — |

#### Almacenes

| Método | URI | Middleware Extra |
|---|---|---|
| GET | `/api/almacenes` | — |
| POST | `/api/almacenes` | `plan.limits:almacenes` |
| GET | `/api/almacenes/{id}` | — |
| PUT/PATCH | `/api/almacenes/{id}` | — |
| DELETE | `/api/almacenes/{id}` | — |

#### Inventario

| Método | URI | Descripción |
|---|---|---|
| GET | `/api/inventario` | Listar inventario (filtros: almacen_id, stock_bajo, buscar) |
| POST | `/api/inventario` | Crear/actualizar registro |
| GET | `/api/inventario/{id}` | Detalle |
| PUT/PATCH | `/api/inventario/{id}` | Actualizar |
| DELETE | `/api/inventario/{id}` | Bloqueado (usar ajustes) |

#### Proveedores

| Método | URI |
|---|---|
| GET/POST | `/api/proveedores` |
| GET/PUT/DELETE | `/api/proveedores/{id}` |

#### Órdenes de Compra

| Método | URI |
|---|---|
| GET/POST | `/api/ordenes-compra` |
| GET/PUT/DELETE | `/api/ordenes-compra/{id}` |

#### Catálogos

| Recurso | Endpoints |
|---|---|
| Categorías | `/api/categorias` (CRUD + árbol) |
| Impuestos | `/api/impuestos` (CRUD, 1 solo default) |
| Descuentos | `/api/descuentos` (CRUD) |
| Métodos de Pago | `/api/metodos-pago` (CRUD) |
| Tasas de Cambio | `/api/tasas-cambio` (CRUD, auto-desactiva anterior) |

#### Créditos y Abonos

| Método | URI | Descripción |
|---|---|---|
| GET/POST | `/api/creditos` | Cuentas de crédito (1 por cliente) |
| GET/PUT/DELETE | `/api/creditos/{id}` | Detalle / actualizar / eliminar |
| GET/POST | `/api/abonos` | Abonos a facturas (con bloqueo pesimista) |

#### Devoluciones

| Método | URI | Descripción |
|---|---|---|
| GET/POST | `/api/devoluciones` | Listar / crear devolución |
| GET | `/api/devoluciones/{id}` | Detalle |

### Rutas Solo Admin (role:admin)

| Método | URI | Middleware Extra |
|---|---|---|
| GET | `/api/usuarios` | — |
| POST | `/api/usuarios` | `plan.limits:usuarios` |
| GET/PUT/DELETE | `/api/usuarios/{id}` | — |
| GET | `/api/reportes/ventas` | — |
| GET | `/api/reportes/inventario` | — |
| GET | `/api/reportes/rentabilidad` | — |
| GET | `/api/reportes/creditos` | — |
| PUT | `/api/tienda` | — |

---

## Integración con Next.js

### Configuración del Cliente HTTP

Crear `lib/api.ts`:

```typescript
const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api'

interface ApiResponse<T> {
  success: boolean
  data?: T
  message?: string
  error?: string
  action?: string
}

class ApiClient {
  private token: string | null = null

  constructor() {
    this.token = typeof window !== 'undefined'
      ? localStorage.getItem('tiendapos_token')
      : null
  }

  setToken(token: string) {
    this.token = token
    localStorage.setItem('tiendapos_token', token)
  }

  clearToken() {
    this.token = null
    localStorage.removeItem('tiendapos_token')
  }

  private async request<T>(
    method: string,
    path: string,
    body?: unknown
  ): Promise<ApiResponse<T>> {
    const headers: Record<string, string> = {
      'Content-Type': 'application/json',
      Accept: 'application/json',
    }

    if (this.token) {
      headers.Authorization = `Bearer ${this.token}`
    }

    const res = await fetch(`${API_URL}${path}`, {
      method,
      headers,
      body: body ? JSON.stringify(body) : undefined,
    })

    if (res.status === 401) {
      this.clearToken()
      window.location.href = '/login'
      return { success: false, message: 'Sesión expirada' }
    }

    if (res.status === 402) {
      const data = await res.json()
      if (data.error === 'suscripcion_vencida') {
        window.location.href = '/billing'
      }
      if (data.error === 'limite_plan_excedido') {
        // Mostrar modal de upgrade
      }
      return data
    }

    return res.json()
  }

  get<T>(path: string) { return this.request<T>('GET', path) }
  post<T>(path: string, body: unknown) { return this.request<T>('POST', path, body) }
  put<T>(path: string, body: unknown) { return this.request<T>('PUT', path, body) }
  delete<T>(path: string) { return this.request<T>('DELETE', path) }
}

export const api = new ApiClient()
```

### Hook de Autenticación

Crear `hooks/useAuth.ts`:

```typescript
'use client'

import { create } from 'zustand'
import { api } from '@/lib/api'

interface User {
  id: number
  name: string
  email: string
  activo: boolean
  roles: string[]
  tienda_id: number
}

interface AuthState {
  user: User | null
  loading: boolean
  login: (email: string, password: string) => Promise<boolean>
  logout: () => Promise<void>
  refreshUser: () => Promise<void>
}

export const useAuth = create<AuthState>((set) => ({
  user: null,
  loading: true,

  login: async (email, password) => {
    const res = await api.post<{ token: string; user: User }>('/login', {
      email,
      password,
      device_name: 'nextjs-web',
    })

    if (res.success && res.data) {
      api.setToken(res.data.token)
      set({ user: res.data.user, loading: false })
      return true
    }
    return false
  },

  logout: async () => {
    await api.post('/logout', {})
    api.clearToken()
    set({ user: null })
  },

  refreshUser: async () => {
    const res = await api.get<User>('/user')
    if (res.success && res.data) {
      set({ user: res.data, loading: false })
    } else {
      set({ user: null, loading: false })
    }
  },
}))
```

### Middleware de Next.js (Protección de Rutas)

Crear `middleware.ts`:

```typescript
import { NextResponse } from 'next/server'
import type { NextRequest } from 'next/server'

const PUBLIC_ROUTES = ['/login', '/register', '/forgot-password', '/reset-password']

export function middleware(request: NextRequest) {
  const token = request.cookies.get('tiendapos_token')?.value
  const { pathname } = request.nextUrl

  // Rutas públicas: permitir siempre
  if (PUBLIC_ROUTES.some(route => pathname.startsWith(route))) {
    return NextResponse.next()
  }

  // Sin token: redirigir a login
  if (!token) {
    const loginUrl = new URL('/login', request.url)
    loginUrl.searchParams.set('redirect', pathname)
    return NextResponse.redirect(loginUrl)
  }

  return NextResponse.next()
}

export const config = {
  matcher: ['/((?!_next/static|_next/image|favicon.ico).*)'],
}
```

### Flujo de Onboarding en Next.js

```typescript
// app/onboarding/page.tsx
'use client'

import { useState } from 'react'
import { api } from '@/lib/api'
import { useRouter } from 'next/navigation'

const STEPS = ['cuenta', 'datos-fiscales', 'configurar-negocio', 'primer-producto']

export default function OnboardingPage() {
  const [step, setStep] = useState(0)
  const router = useRouter()

  // Paso 1: Crear cuenta
  const crearCuenta = async (formData: FormData) => {
    const res = await api.post('/v1/onboarding/cuenta', {
      name: formData.get('name'),
      email: formData.get('email'),
      password: formData.get('password'),
      pais: formData.get('pais'),
    })

    if (res.success && res.data) {
      api.setToken(res.data.token)
      setStep(1) // Ir a datos fiscales
    }
  }

  // Paso 2: Datos fiscales
  const guardarDatosFiscales = async (formData: FormData) => {
    const res = await api.post('/v1/onboarding/datos-fiscales', {
      identificacion_fiscal: formData.get('rif'),
      razon_social: formData.get('razon_social'),
      nombre_comercial: formData.get('nombre_comercial'),
      direccion: formData.get('direccion'),
    })

    if (res.success) setStep(2) // Ir a configurar negocio
  }

  // Paso 3: Configurar negocio
  const configurarNegocio = async (formData: FormData) => {
    const res = await api.post('/v1/onboarding/configurar-negocio', {
      tipo_negocio: formData.get('tipo_negocio'), // farmacia, ferreteria, licoreria, etc.
      nombre_almacen: formData.get('nombre_almacen'),
      nombre_caja: formData.get('nombre_caja'),
    })

    if (res.success) setStep(3) // Ir a primer producto
  }

  // Paso 4: Primer producto (opcional)
  const crearPrimerProducto = async (formData: FormData) => {
    const res = await api.post('/v1/onboarding/primer-producto', {
      nombre: formData.get('nombre'),
      sku: formData.get('sku'),
      costo: formData.get('costo'),
      stock_inicial: formData.get('stock_inicial'),
    })

    if (res.success) router.push('/dashboard')
  }

  const saltarProducto = async () => {
    await api.post('/v1/onboarding/saltar-primer-producto', {})
    router.push('/dashboard')
  }

  return (
    <div>
      {/* Renderizar formulario según step */}
    </div>
  )
}
```

### Hook de Suscripción

Crear `hooks/useSuscripcion.ts`:

```typescript
'use client'

import { create } from 'zustand'
import { api } from '@/lib/api'

interface SuscripcionState {
  estado: string | null
  plan: string | null
  diasRestantes: number | null
  limites: { productos: number; usuarios: number; almacenes: number; cajas: number } | null
  loading: boolean
  fetchEstado: () => Promise<void>
}

export const useSuscripcion = create<SuscripcionState>((set) => ({
  estado: null,
  plan: null,
  diasRestantes: null,
  limites: null,
  loading: true,

  fetchEstado: async () => {
    const res = await api.get('/v1/suscripcion/estado')
    if (res.success && res.data) {
      set({
        estado: res.data.estado,
        plan: res.data.plan,
        diasRestantes: res.data.dias_restantes,
        limites: res.data.limites,
        loading: false,
      })
    }
  },
}))
```

### Componente de Banner de Suscripción

```typescript
// components/SuscripcionBanner.tsx
'use client'

import { useSuscripcion } from '@/hooks/useSuscripcion'

export function SuscripcionBanner() {
  const { estado, diasRestantes, plan } = useSuscripcion()

  if (estado === 'trial' && diasRestantes !== null && diasRestantes <= 5) {
    return (
      <div className="bg-yellow-500 text-black px-4 py-2 text-center">
        Tu trial expira en {diasRestantes} días.{' '}
        <a href="/billing" className="underline font-bold">Actualiza tu plan</a>
      </div>
    )
  }

  if (estado === 'vencida') {
    return (
      <div className="bg-red-600 text-white px-4 py-2 text-center">
        Tu suscripción ha vencido.{' '}
        <a href="/billing" className="underline font-bold">Renueva ahora</a>
      </div>
    )
  }

  return null
}
```

### Manejo de Errores 402 en React Query

```typescript
// lib/queryClient.ts
import { QueryClient } from '@tanstack/react-query'

export const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      retry: (failureCount, error: any) => {
        // No reintentar si es 402 (suscripción) o 403 (permisos)
        if (error?.status === 402 || error?.status === 403) return false
        return failureCount < 3
      },
    },
  },
})
```

### Interceptor para Renovación de Token

```typescript
// lib/api.ts (añadir al ApiClient)

private async request<T>(method: string, path: string, body?: unknown): Promise<ApiResponse<T>> {
  // ... headers ...

  const res = await fetch(url, options)

  // Si el token expiró, intentar renovar automáticamente
  if (res.status === 401 && this.token) {
    const refreshed = await this.tryRefresh()
    if (refreshed) {
      // Reintentar la petición original con el nuevo token
      return this.request<T>(method, path, body)
    }
  }

  return res.json()
}

private async tryRefresh(): Promise<boolean> {
  try {
    const res = await fetch(`${API_URL}/auth/refresh`, {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${this.token}`,
        Accept: 'application/json',
      },
    })

    if (res.ok) {
      const data = await res.json()
      if (data.success) {
        this.setToken(data.data.token)
        return true
      }
    }
  } catch {
    // refresh falló
  }

  this.clearToken()
  return false
}
```

### Estructura de Carpetas Recomendada para Next.js

```
src/
├── app/
│   ├── (auth)/
│   │   ├── login/page.tsx
│   │   ├── forgot-password/page.tsx
│   │   └── reset-password/page.tsx
│   ├── (onboarding)/
│   │   └── onboarding/page.tsx
│   ├── (dashboard)/
│   │   ├── layout.tsx          ← Verifica auth + suscripción
│   │   ├── page.tsx            ← Dashboard principal
│   │   ├── productos/
│   │   ├── ventas/
│   │   ├── clientes/
│   │   ├── caja/
│   │   ├── inventario/
│   │   ├── creditos/
│   │   └── configuracion/
│   │       ├── usuarios/
│   │       ├── tienda/
│   │       └── reportes/
│   └── billing/
│       └── page.tsx            ← Gestión de suscripción
├── components/
│   ├── SuscripcionBanner.tsx
│   ├── PlanLimitsModal.tsx
│   └── RoleGuard.tsx
├── hooks/
│   ├── useAuth.ts
│   └── useSuscripcion.ts
├── lib/
│   ├── api.ts
│   └── queryClient.ts
└── middleware.ts
```

### Componente RoleGuard (Control de UI por Rol)

```typescript
// components/RoleGuard.tsx
'use client'

import { useAuth } from '@/hooks/useAuth'

interface Props {
  roles: string[]
  children: React.ReactNode
  fallback?: React.ReactNode
}

export function RoleGuard({ roles, children, fallback = null }: Props) {
  const { user } = useAuth()

  if (!user) return null

  const hasRole = user.roles.some(role => roles.includes(role))

  return hasRole ? <>{children}</> : <>{fallback}</>
}

// Uso:
// <RoleGuard roles={['admin']}>
//   <AdminPanel />
// </RoleGuard>
```

### Ejemplo: Página de Productos

```typescript
// app/(dashboard)/productos/page.tsx
'use client'

import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { api } from '@/lib/api'

export default function ProductosPage() {
  const queryClient = useQueryClient()

  const { data, isLoading } = useQuery({
    queryKey: ['productos'],
    queryFn: () => api.get('/productos'),
  })

  const crearProducto = useMutation({
    mutationFn: (producto: any) => api.post('/productos', producto),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['productos'] }),
  })

  // Manejar error 402 (límite de plan)
  if (crearProducto.isError && (crearProducto.error as any)?.status === 402) {
    // Mostrar modal de upgrade
  }

  return (
    <div>
      <h1>Productos</h1>
      {/* Lista de productos */}
    </div>
  )
}
```

### Variables de Entorno (.env.local)

```env
NEXT_PUBLIC_API_URL=http://localhost:8000/api
```

---

## Servicios y Lógica de Negocio

### OnboardingService

Responsable del wizard de configuración inicial:

| Método | Descripción |
|---|---|
| `crearCuenta()` | Crea User + Tienda + TiendaOnboarding + Suscripción Trial + Token |
| `guardarDatosFiscales()` | Guarda RIF/NIT/RFC + siembra impuestos y monedas del país |
| `configurarNegocio()` | Crea almacén, caja, categorías, métodos de pago, cliente default, margen, lista precio, config impresora |
| `crearPrimerProducto()` | Crea producto + variante + inventario |
| `saltarPrimerProducto()` | Marca onboarding completo sin producto |
| `obtenerEstado()` | Retorna paso actual y progreso |

**Auto-siembra por país:**
- `sembrarImpuestosPais()` — IVA/IGV según tasa local
- `sembrarMonedasPais()` — Moneda base + secundarias (VE: USD+VES)
- `sembrarMetodosPagoPais()` — Efectivo, tarjeta, PSE, Yape, Zelle, Pago Móvil, etc.
- `sembrarTasaInicialVES()` — Tasa BCV inicial para Venezuela
- `sembrarCategoriasNegocio()` — Categorías según tipo de negocio (farmacia, ferretería, licorería, etc.)

### SuscripcionService

| Método | Descripción |
|---|---|
| `verificarActiva()` | Lanza excepción si suscripción no está activa |
| `verificarAcceso()` | Usado por middleware CheckSuscripcionActiva |
| `marcarTrialsVencidos()` | Marca trials expirados como vencidos (batch) |
| `cancelarSuscripcion()` | Cancela suscripción |
| `validarLimiteProductos()` | Valida límite de productos del plan |
| `validarLimiteUsuarios()` | Valida límite de usuarios del plan |
| `validarLimiteAlmacenes()` | Valida límite de almacenes del plan |
| `validarLimiteCajas()` | Valida límite de cajas del plan |
| `activarSuscripcion()` | Activa plan pagado (cancela anterior, crea nueva) |
| `estadoParaFrontend()` | Info consolidada para UI |

### PosBusinessRulesService

| Método | Descripción |
|---|---|
| `registrarPagoConIgtf()` | Calcula y registra IGTF (3% en Venezuela para pagos en divisa) |
| `procesarCotizacionAFactura()` | Convierte cotización a factura |
| `procesarCobroFactura()` | Valida totales de pago y marca como pagada |
| `agregarItemVenta()` | Agrega línea a la venta con snapshot multimoneda |

### InventoryService

| Método | Descripción |
|---|---|
| `recibir()` | Recibe inventario (compras, devoluciones, ajustes+) con FEFO y PPS |
| `vender()` | Vende inventario con consumo FEFO de lotes o PPS |
| `trasladar()` | Transfiere stock entre almacenes con replicación de lotes |

**Excepciones del inventario:**
- `StockInsuficienteException` — No hay suficiente stock
- `CoherenciaDimensionalException` — Unidad de medida incompatible
- `LoteRequeridoException` — Lote requerido/prohibido según config
- `ConfiguracionInventarioException` — Error de configuración

---

## Jobs Programados

| Horario | Job | Descripción |
|---|---|---|
| Diario 03:00 | `MarcarTrialsVencidos` | Marca trials expirados como vencidos |
| Diario 04:00 | `LimpiarTokensExpirados` | Elimina tokens Sanctum expirados |
| Diario 06:00 | `NotificarStockBajo` | Crea notificaciones de stock bajo/mínimo |

**Comando para correr el scheduler:**
```bash
php artisan schedule:work    # Desarrollo
* * * * * php artisan schedule:run   # Producción (cron)
```

---

## Base de Datos

### Conexión

```env
DB_CONNECTION=pgsql
DB_HOST=<neon-host>
DB_PORT=5432
DB_DATABASE=<neon-db>
DB_USERNAME=<neon-user>
DB_PASSWORD=<neon-password>
DB_SSLMODE=require
```

### Tablas Principales (80 total)

| Tabla | Descripción |
|---|---|
| `users` | Usuarios con tienda_id, activo, ultimo_login |
| `tienda` | Configuración de tienda (multimoneda, IGTF, zona horaria) |
| `suscripciones` | Suscripciones SaaS (trial/activa/vencida/cancelada/suspendida) |
| `planes` | Planes con límites (productos, usuarios, almacenes, cajas) |
| `productos` | Catálogo de productos |
| `variantes_producto` | Variantes de productos (código barra, atributos) |
| `inventario` | Stock por variante + almacén |
| `inventario_lotes` | Lotes con FEFO (fecha vencimiento) |
| `ventas` | Cabecera de ventas |
| `items_venta` | Líneas de venta |
| `pagos_venta` | Pagos con snapshot multimoneda |
| `clientes` | Clientes con tipo (natural/jurídico) |
| `cajas` | Cajas registradoras |
| `sesiones_caja` | Sesiones de caja (apertura/cierre) |
| `movimientos_caja` | Movimientos de caja (ventas, retiros, gastos) |
| `almacenes` | Almacenes |
| `categorias_productos` | Categorías con árbol (padre_id) |
| `impuestos` | Impuestos (IVA/IGV/Exento) |
| `descuentos` | Descuentos |
| `metodos_pago` | Métodos de pago (con grava_igtf para VE) |
| `tasas_cambio` | Tasas de cambio entre monedas |
| `proveedores` | Proveedores |
| `ordenes_compra` | Órdenes de compra |
| `cuentas_credito` | Cuentas de crédito (1 por cliente) |
| `facturas_credito` | Facturas a crédito |
| `abonos_credito` | Abonos a facturas |
| `devoluciones_venta` | Devoluciones |
| `movimientos_inventario` | Auditoría de movimientos (entradas, salidas, ajustes, traslados) |
| `personal_access_tokens` | Tokens Sanctum |
| `roles` / `permissions` | Spatie roles y permisos |
| `password_reset_tokens` | Tokens de reset de contraseña |
| `tienda_onboarding` | Estado del onboarding por tienda |
| `etiquetas_fiscales_pais` | Etiquetas fiscales por país |
| `tienda_monedas` | Monedas aceptadas por tienda |
| `auditoria` | Log de auditoría |

---

## Instalación y Configuración

### Requisitos

- PHP 8.2+
- Composer
- PostgreSQL (o conexión Neon.tech)
- Node.js 18+ (para frontend)

### Instalación

```bash
# Clonar repositorio
git clone <repo-url> tiendapos-api
cd tiendapos-api

# Instalar dependencias
composer install

# Configurar entorno
cp .env.example .env
php artisan key:generate

# Configurar .env con datos de Neon.tech
# DB_HOST=...
# DB_PASSWORD=...

# Ejecutar migraciones
php artisan migrate --force

# Ejecutar seeders
php artisan db:seed --class=RolesAndPermissionsSeeder --force
php artisan db:seed --class=UnidadesSeeder --force

# Iniciar servidor
php artisan serve
```

### Usuarios de Prueba

| Email | Password | Rol |
|---|---|---|
| `admin@tiendapos.com` | `password` | admin |


### Comandos Útiles

```bash
php artisan pos:status              # Verificar estado del sistema
php artisan route:list --path=api   # Listar rutas API
php artisan schedule:work           # Correr scheduler en desarrollo
php artisan queue:work              # Procesar colas
```

---

## Testing

### Probar Login

```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"admin@tiendapos.com","password":"password","device_name":"test"}'
```

### Probar Endpoints Protegidos

```bash
TOKEN="tu_token_aqui"

curl http://localhost:8000/api/user \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"
```

### Probar Onboarding

```bash
curl -X POST http://localhost:8000/api/v1/onboarding/cuenta \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Mi Tienda",
    "email": "nueva@tienda.com",
    "password": "password123",
    "pais": "VE"
  }'
```

---

## Resumen de Lo Construido Hoy

| Componente | Archivos | Descripción |
|---|---|---|
| Auth completo | `AuthController`, `LoginRequest`, `CambiarPasswordRequest` | Login, logout, refresh, cambio de contraseña |
| Password Reset | `PasswordResetController`, `ForgotPasswordRequest`, `ResetPasswordRequest` | Envío de código, verificación, reset |
| Email de reset | `resources/views/emails/reset-password.blade.php` | Blade template con código + link |
| Middleware de roles | `CheckRole.php` | Verifica roles (admin, supervisor, cajero) |
| Middleware de permisos | `CheckPermission.php` | Verifica permisos granulares (anular_venta, etc.) |
| Middleware de suscripción | `CheckSuscripcionActiva.php` | Bloquea acceso si suscripción vencida |
| Middleware de límites | `EnforcePlanLimits.php` | Valida límites del plan antes de crear recursos |
| Middleware de usuario activo | `EnsureUserIsActive.php` | Verifica que el usuario esté activo |
| Excepciones de suscripción | `SuscripcionVencidaException`, `LimitePlanExcedidoException` | Respuestas JSON 402 personalizadas |
| Manejo de excepciones | `bootstrap/app.php` | Renderizado global de excepciones de suscripción |
| Seeder de roles | `RolesAndPermissionsSeeder.php` | 4 roles + 69 permisos + 2 usuarios de prueba |
| Rate limiters | `AppServiceProvider.php` | Throttle para login (5/min) y password-reset (3/min) |
| Rutas completas | `routes/api.php` | 112 rutas organizadas en 4 capas de protección |
| Config Sanctum | `config/sanctum.php` | Expiración 10080 min (7 días) |
| TiendaController | `TiendaController.php` | Usa tienda del usuario autenticado |
| Migración ultimo_login | `add_ultimo_login_to_users_table` | Registro de último acceso |
| Throttle password reset | `routes/api.php` | Protección contra abuso en reset de contraseña |
