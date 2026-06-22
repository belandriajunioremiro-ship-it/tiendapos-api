# Sistema de Suscripciones SaaS - TiendaPOS API

## Descripción General

Se implementó un sistema completo de suscripciones SaaS para TiendaPOS API que permite:
- Gestionar períodos de prueba (Trial) de 14 días
- Controlar el acceso a la API según estado de suscripción
- Validar límites de plan (productos, usuarios, almacenes, cajas)
- Activar planes pagados
- Cancelar suscripciones

---

## Archivos Creados

### 1. Excepciones de Dominio

#### `app/Exceptions/Suscripcion/SuscripcionVencidaException.php`
Maneja errores cuando una suscripción está vencida o suspendida.

```php
// Métodos disponibles:
SuscripcionVencidaException::trialVencido($finTrial);  // Trial expirado
SuscripcionVencidaException::suspendada($motivo);       // Suscripción suspendida
```

#### `app/Exceptions/Suscripcion/LimitePlanExcedidoException.php`
Maneja errores cuando se exceden los límites del plan.

```php
// Métodos disponibles:
LimitePlanExcedidoException::productos($limite);   // Límite de productos
LimitePlanExcedidoException::usuarios($limite);    // Límite de usuarios
LimitePlanExcedidoException::almacenes($limite);   // Límite de almacenes
LimitePlanExcedidoException::cajas($limite);       // Límite de cajas
```

---

### 2. Servicio Principal

#### `app/Services/SuscripcionService.php`
Contiene toda la lógica de negocio para suscripciones.

**Métodos principales:**

| Método | Descripción |
|--------|-------------|
| `verificarActiva($tiendaId)` | Verifica si la suscripción está activa |
| `verificarAcceso($tiendaId)` | Usado por middleware para validar acceso |
| `marcarTrialsVencidos()` | Marca trials expirados como vencidos |
| `validarLimiteProductos($tiendaId)` | Valida límite de productos |
| `validarLimiteUsuarios($tiendaId)` | Valida límite de usuarios |
| `validarLimiteAlmacenes($tiendaId)` | Valida límite de almacenes |
| `validarLimiteCajas($tiendaId)` | Valida límite de cajas |
| `obtenerPlan($tiendaId)` | Obtiene el plan actual |
| `activarSuscripcion($tiendaId, $planId, $duracionMeses)` | Activa un plan pagado |
| `cancelarSuscripcion($tiendaId, $userId, $motivo)` | Cancela la suscripción |
| `estadoParaFrontend($tiendaId)` | Info consolidada para el frontend |

---

### 3. Middlewares

#### `app/Http/Middleware/CheckSuscripcionActiva.php`
Bloquea el acceso a la API si la suscripción está vencida o suspendida.

**Respuesta cuando está vencida (HTTP 402):**
```json
{
    "success": false,
    "error": "suscripcion_vencida",
    "message": "Tu período de prueba finalizó el 17/06/2026. Suscríbete a un plan para continuar usando el sistema.",
    "action": "actualizar_plan",
    "redirect": "/billing"
}
```

#### `app/Http/Middleware/EnforcePlanLimits.php`
Valida límites del plan antes de crear recursos.

**Uso en rutas:**
```php
Route::post('/productos', [ProductoController::class, 'store'])
    ->middleware('plan.limits:productos');

Route::post('/usuarios', [UsuarioController::class, 'store'])
    ->middleware('plan.limits:usuarios');

Route::post('/almacenes', [AlmacenController::class, 'store'])
    ->middleware('plan.limits:almacenes');

Route::post('/cajas', [CajaController::class, 'store'])
    ->middleware('plan.limits:cajas');
```

**Respuesta cuando se excede límite (HTTP 402):**
```json
{
    "success": false,
    "error": "limite_plan_excedido",
    "recurso": "productos",
    "message": "Has alcanzado el límite de 500 productos de tu plan. Actualiza tu plan para agregar más.",
    "action": "actualizar_plan"
}
```

---

### 4. Comando Programado

#### `app/Console/Commands/VerificarSuscripcionesVencidas.php`
Verifica diariamente las suscripciones trial vencidas y las marca como `vencida`.

**Ejecución manual:**
```bash
php artisan suscripciones:verificar-vencidas
```

**Programación automática:**
- Se ejecuta todos los días a las 3:00 AM
- Configurado en `routes/console.php`

---

### 5. Controlador

#### `app/Http/Controllers/Api/V1/SuscripcionController.php`
Maneja los endpoints de la API para suscripciones.

**Endpoints:**

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/api/v1/suscripcion/estado` | Estado actual de la suscripción |
| GET | `/api/v1/suscripcion/planes` | Lista de planes disponibles |
| POST | `/api/v1/suscripcion/activar` | Activa un plan pagado |
| POST | `/api/v1/suscripcion/cancelar` | Cancela la suscripción |

---

### 6. Form Request

#### `app/Http/Requests/Api/V1/Suscripcion/ActivarRequest.php`
Valida los datos para activar una suscripción.

**Campos validados:**
- `plan_id` (requerido) - ID del plan a activar
- `duracion_meses` (opcional) - De 1 a 12 meses
- `metodo_pago` (opcional) - tarjeta, transferencia, cripto, manual
- `referencia_pago` (opcional) - Referencia del pago

---

### 7. Archivos de Configuración Modificados

#### `bootstrap/app.php`
Middlewares registrados:
```php
$middleware->alias([
    'activo'       => \App\Http\Middleware\EnsureUserIsActive::class,
    'suscripcion'  => \App\Http\Middleware\CheckSuscripcionActiva::class,
    'plan.limits'  => \App\Http\Middleware\EnforcePlanLimits::class,
]);
```

#### `routes/console.php`
Scheduler configurado:
```php
Schedule::command('suscripciones:verificar-vencidas')
    ->dailyAt('03:00')
    ->withoutOverlapping()
    ->runInBackground();
```

#### `routes/api.php`
Rutas organizadas con middlewares:
- Onboarding público y privado (sin verificación de suscripción)
- Suscripción (accesible con trial vencido para pagar)
- Rutas protegidas por `suscripcion` middleware
- POST de recursos con `plan.limits` middleware

---

## Planes Disponibles

| Plan | Precio | Productos | Usuarios | Almacenes | Cajas | Trial |
|------|--------|-----------|----------|-----------|-------|-------|
| Trial | $0 | 50 | 3 | 1 | 1 | 14 días |
| Básico | $19/mes | 500 | 5 | 2 | 2 | 14 días |
| Pro | $39/mes | 5,000 | 10 | 5 | 5 | 14 días |
| Premium | $99/mes | ∞ | ∞ | ∞ | ∞ | 14 días |

---

## Estados de Suscripción

| Estado | Descripción |
|--------|-------------|
| `trial` | Período de prueba activo (14 días) |
| `activa` | Plan pagado y activo |
| `vencida` | Trial expirado sin pago |
| `suspendida` | Suspendida por incumplimiento |
| `cancelada` | Cancelada por el usuario |

---

## Flujo de Uso

### 1. Registro de Usuario
```
Usuario se registra → Se crea tienda → Se crea suscripción Trial (14 días)
```

### 2. Durante el Trial
```
Usuario puede usar todas las funcionalidades dentro de los límites del plan
```

### 3. Vencimiento del Trial
```
Scheduler ejecuta a las 3:00 AM → Marca trials vencidos → Estado = "vencida"
Usuario intenta acceder → Middleware bloquea → Error 402
```

### 4. Pago de Plan
```
Usuario accede a /v1/suscripcion/planes → Selecciona plan → POST /v1/suscripcion/activar
Estado = "activa" → Acceso restaurado
```

### 5. Creación de Recursos
```
Usuario crea producto → Middleware valida límite → Si excede → Error 402
```

---

## Ejemplos de Uso

### Verificar Estado de Suscripción
```bash
curl http://localhost:8000/api/v1/suscripcion/estado \
  -H "Authorization: Bearer TU_TOKEN"
```

**Respuesta:**
```json
{
    "success": true,
    "data": {
        "estado": "activa",
        "plan": "Básico",
        "plan_id": 2,
        "precio": "19.00",
        "moneda": "USD",
        "es_trial": false,
        "fin_periodo": "2026-07-19",
        "limites": {
            "productos": 500,
            "usuarios": 5,
            "almacenes": 2,
            "cajas": 2
        }
    }
}
```

### Activar un Plan
```bash
curl -X POST http://localhost:8000/api/v1/suscripcion/activar \
  -H "Authorization: Bearer TU_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "plan_id": 2,
    "duracion_meses": 1,
    "metodo_pago": "transferencia",
    "referencia_pago": "PAY-12345"
  }'
```

### Cancelar Suscripción
```bash
curl -X POST http://localhost:8000/api/v1/suscripcion/cancelar \
  -H "Authorization: Bearer TU_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"motivo": "Cambio de proveedor"}'
```

---

## Comandos Útiles

### Verificar suscripciones vencidas manualmente
```bash
php artisan suscripciones:verificar-vencidas
```

### Ver rutas de suscripción
```bash
php artisan route:list --path=suscripcion
```

### Ver estado del sistema
```bash
php artisan pos:status
```

---

## Estructura de la Base de Datos

### Tabla `planes`
```sql
- id (PK)
- nombre (Trial, Básico, Pro, Premium)
- precio_mensual
- moneda (USD)
- limite_productos
- limite_usuarios
- limite_almacenes
- limite_cajas
- dias_trial
- caracteristicas (JSON)
```

### Tabla `suscripciones`
```sql
- id (PK)
- tienda_id (FK)
- plan_id (FK)
- estado (trial, activa, vencida, suspendida, cancelada)
- inicio_trial
- fin_trial
- inicio_pago
- fin_periodo
- metodo_pago
- referencia_pago
- auto_renovar
- cancelado_en
- cancelado_por
- motivo_cancelacion
```

---

## Archivos de Prueba

### `test_suscripcion.php`
Script de prueba completo que:
1. Muestra la suscripción actual
2. Simula un trial vencido
3. Ejecuta la verificación
4. Activa un plan pagado
5. Muestra el estado final

**Ejecutar:**
```bash
php test_suscripcion.php
```

---

## Notas Importantes

1. **Scheduler en producción**: Configurar cron job en el servidor:
   ```bash
   * * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
   ```

2. **Webhooks de pago**: Integrar con pasarelas de pago (Stripe, PayPal, etc.) para actualizar suscripciones automáticamente.

3. **Notificaciones**: Considerar enviar emails cuando:
   - El trial está por vencer (3 días antes)
   - El trial ha vencido
   - El período de pago está por terminar

4. **Límites**: Los límites NULL significan "ilimitado" (plan Premium).

---

## Resumen de Archivos

| Archivo | Tipo | Propósito |
|---------|------|-----------|
| `SuscripcionVencidaException.php` | Excepción | Error de suscripción vencida |
| `LimitePlanExcedidoException.php` | Excepción | Error de límite excedido |
| `SuscripcionService.php` | Servicio | Lógica de negocio |
| `CheckSuscripcionActiva.php` | Middleware | Bloquear acceso vencidos |
| `EnforcePlanLimits.php` | Middleware | Validar límites |
| `VerificarSuscripcionesVencidas.php` | Comando | Marcar trials vencidos |
| `SuscripcionController.php` | Controlador | Endpoints de API |
| `ActivarRequest.php` | Form Request | Validación de activación |
| `bootstrap/app.php` | Configuración | Middlewares |
| `routes/console.php` | Configuración | Scheduler |
| `routes/api.php` | Configuración | Rutas |

---

**Total: 7 archivos nuevos + 3 archivos modificados**

---

## Soporte

Para dudas o problemas, revisar:
- Logs en `storage/logs/laravel.log`
- Estado de la base de datos con `php artisan pos:status`
- Rutas disponibles con `php artisan route:list`
