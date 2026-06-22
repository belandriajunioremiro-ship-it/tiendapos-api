# TiendaPOS — Sistema POS SaaS Multimoneda

> El primer sistema POS pensado para Latinoamérica. Factura en dólares, bolívares, pesos, soles... todo al mismo tiempo.

---

## Que es TiendaPOS?

TiendaPOS es un sistema de punto de venta (POS) en la nube que permite a cualquier negocio de Latinoamérica facturar en multiples monedas simultaneamente. Esta construido como SaaS: cada tienda tiene su propia base de datos aislada, su suscripcion y sus limites.

### El problema que resolvemos

En Latinoamérica, los negocios enfrentan un caos monetario:

- Venezuela: cobras en USD pero el cliente paga en bolívares con Pago Móvil
- Argentina: facturas en pesos pero tus proveedores te cobran en dólares
- Colombia: necesitas registrar el IVA del 19% pero algunos productos son excluidos
- Ecuador: operas en dólares pero tus clientes te pagan por Deuna o transferencia

TiendaPOS resuelve todo eso. Una sola pantalla, multiples monedas, impuestos locales y calculo automatico de IGTF.

---

## Funcionalidades Principales

### Multimoneda en tiempo real

- Factura en la moneda que quieras: USD, VES, COP, MXN, ARS, PEN, CLP, BOB, UYU
- Calculo automatico con tasas de cambio actualizables
- Soporte completo para IGTF (Venezuela): 3% automatico en pagos con divisa extranjera
- Cada pago se registra con snapshot de tasa para auditoría

### 9 paises de LATAM configurados

| Pais | Moneda | IVA | Metodos de pago |
|---|---|---|---|
| Venezuela | USD + VES | 16% | Efectivo, Pago Móvil, Transferencia, Zelle, USDT |
| Colombia | COP | 19% | Efectivo, PSE, Nequi, Tarjeta |
| México | MXN | 16% | Efectivo, SPEI, QR, Tarjeta |
| Ecuador | USD | 15% | Efectivo, Deuna, Transferencia |
| Argentina | ARS + USD | 21% | Efectivo, Mercado Pago QR, Transferencia |
| Perú | PEN | 18% | Efectivo, Yape, Plin, Tarjeta |
| Chile | CLP | 19% | Efectivo, Mach, Transferencia |
| Bolivia | BOB | 13% | Efectivo, QR, Tarjeta |
| Uruguay | UYU | 22% | Efectivo, Transferencia, Tarjeta |

### Onboarding en 4 pasos

1. Creas tu cuenta (30 segundos)
2. Ingresas tu RIF / NIT / RFC (datos fiscales automaticos para tu pais)
3. Configuras tu negocio (se crean almacen, caja, categorias, metodos de pago, todo automatico)
4. Agregas tu primer producto (o lo saltas)

En 2 minutos estas facturando. Sin manual. Sin soporte tecnico.

### Ventas tipo POS

- Interfaz rapida para punto de venta
- Busqueda de productos por nombre, SKU o código de barras
- Carrito con descuentos, impuestos automaticos y totales en multiples monedas
- Multiples metodos de pago en una misma venta
- Cotizaciones que se convierten en factura con un clic
- Anulacion con permisos (solo admin o supervisor)

### Inventario inteligente

- Control por almacen (multialmacen)
- Lotes con vencimiento (FEFO - First Expired First Out)
- Costo promedio ponderado (PPS) automatico
- Ajustes de inventario con auditoría
- Traslados entre almacenes
- Alertas de stock bajo

### Creditos y cobros

- Cuentas de credito por cliente con límite personalizado
- Facturas a credito con vencimiento automatico
- Registro de abonos con bloqueo pesimista (evita concurrencia)
- Reporte de cartera: vencidas, por vencer, riesgo

### Devoluciones

- Notas de credito automaticas
- Restablecimiento de inventario
- Trazabilidad completa (que venta, que items, quien autorizo)

### Caja

- Apertura y cierre de caja con recuento
- Movimientos: ventas, retiros, gastos, ingresos
- Sesion activa por vendedor/cajero

### Reportes

- Ventas por dia, moneda y metodo de pago
- Inventario: stock actual vs mínimo
- Rentabilidad por producto (margen vs costo)
- Cartera de creditos: vencidas + por vencer

### SaaS con planes

| Plan | Productos | Usuarios | Precio |
|---|---|---|---|
| Trial | 50 | 2 | Gratis (14 dias) |
| Basico | 200 | 5 | $19/mes |
| Pro | 1,000 | 15 | $49/mes |
| Premium | Ilimitado | Ilimitado | $99/mes |

Cuando se alcanza un límite del plan, el sistema muestra un mensaje claro con opcion de upgrade. Sin errores raros. Sin crashes.

---

## Arquitectura

```
Frontend (Next.js)  →  API (Laravel 11)  →  PostgreSQL (Neon.tech)
                          │
                          ├── Sanctum (tokens)
                          ├── Spatie (roles + permisos)
                          ├── 5 middlewares en cadena
                          └── 4 servicios de negocio
```

### Stack

- **Backend:** Laravel 11, PHP 8.2+, PostgreSQL
- **Base de datos:** Neon.tech (serverless PostgreSQL en la nube)
- **Auth:** Sanctum con tokens + abilities por rol
- **Permisos:** Spatie (4 roles, 69 permisos)
- **Frontend:** Next.js 14+ (App Router)
- **Colas:** database driver
- **80 tablas** en la base de datos

### Seguridad en capas

Cada peticion pasa por 5 capas de proteccion:

```
auth:sanctum → activo → suscripcion → plan.limits → role/permission
```

1. Estas autenticado?
2. Tu usuario esta activo?
3. Tu suscripcion esta vigente?
4. No superaste los limites de tu plan?
5. Tienes el rol/permiso para esta accion?

---

## Lo que construimos hoy

### Sistema de autenticacion completo

- Login con token Sanctum + abilities por rol
- Logout con invalidacion de token
- Refresh de token (rotacion)
- Cambio de contraseña
- Recuperacion de contraseña por email (codigo de 6 caracteres)
- Throttle: 5 intentos/min en login, 3/min en password reset

### Onboarding wizard

- 4 pasos que configuran toda la tienda automaticamente
- Auto-siembra de impuestos, monedas, metodos de pago y categorias segun pais
- Estado persistido (puedes retomar donde quedaste)
- Etiquetas fiscales dinamicas por pais (RIF, NIT, RFC, RUC)

### Sistema de suscripciones SaaS

- Trial de 14 dias con limites
- Activacion de plan pago
- Cancelacion con acceso hasta fin de periodo
- Jobs programados: marcar trials vencidos, limpiar tokens expirados
- Middleware que bloquea acceso si suscripcion vencida (HTTP 402)
- Middleware que bloquea creacion si limite de plan alcanzado (HTTP 402)

### Roles y permisos granulares

4 roles con permisos especificos:

- **Admin:** acceso total
- **Supervisor:** ventas + inventario + caja + creditos + reportes
- **Cajero:** solo ventas y caja
- **Vendedor:** solo ventas y caja

69 permisos individuales para control fino.

### 112 rutas API organizadas

- 6 rutas publicas (login, onboarding, password reset)
- 5 rutas autenticadas sin suscripcion (me, tienda, onboarding privado)
- 4 rutas de suscripcion (accesibles con trial vencido para pagar)
- 85+ rutas protegidas por suscripcion (CRUD completo)
- 10+ rutas solo admin (usuarios, reportes, config tienda)

### Excepciones personalizadas

- Suscripcion vencida → JSON 402 con `action: actualizar_plan`
- Limite de plan excedido → JSON 402 con `recurso` y `action: actualizar_plan`
- Sin permiso → JSON 403
- Cuenta desactivada → JSON 403

---

## Para desarrolladores

### Inicio rapido

```bash
git clone <repo> && cd tiendapos-api
composer install
cp .env.example .env
php artisan key:generate
# Configurar DB en .env
php artisan migrate --force
php artisan db:seed --class=RolesAndPermissionsSeeder --force
php artisan serve
```

### Probar la API

```bash
# Login
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"admin@tiendapos.com","password":"password","device_name":"test"}'

# Crear cuenta nueva
curl -X POST http://localhost:8000/api/v1/onboarding/cuenta \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"name":"Mi Negocio","email":"nuevo@email.com","password":"password123","pais":"VE"}'
```

### Ver estado del sistema

```bash
php artisan pos:status
```

### Documentacion tecnica completa

Ver `README_TECNICO.md` para:
- Catalogo completo de 112 endpoints
- Integracion paso a paso con Next.js
- Hooks de React (useAuth, useSuscripcion)
- Componentes (RoleGuard, SuscripcionBanner)
- Manejo de errores 402
- Renovacion automatica de tokens
- Estructura de carpetas recomendada

---

## Numeros

- **80** tablas en PostgreSQL
- **112** rutas API
- **4** roles con permisos granulares
- **69** permisos individuales
- **9** paises de LATAM configurados
- **4** planes SaaS
- **4** pasos de onboarding
- **5** capas de middleware
- **3** jobs programados
- **10080** minutos de duracion del token (7 dias)

---

## Que viene despues?

- Dashboard en tiempo real (KPIs, graficos)
- Impresion de tickets (termica 80mm, 58mm, A4)
- Notificaciones push (stock bajo, credito vencido)
- App movil (React Native)
- Integracion con SUNAT, DIAN, SAT, SENIAT
- Facturacion electronica
- Catalogo de productos online (para clientes)
- Multi-sucursal

---

## Contacto

Si quieres usar TiendaPOS en tu negocio, contribuir al proyecto o simplemente charlar sobre POS y multimoneda, escribeme.

---

*TiendaPOS — Hecho en Latinoamérica, para Latinoamérica.*
