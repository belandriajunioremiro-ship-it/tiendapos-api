# 🚀 Sistema de Suscripciones SaaS para TiendaPOS

## Guía Rápida para TikTok

---

## ¿Qué es esto?

Un sistema completo de suscripciones para tu API de Point of Sale (POS) que convierte tu aplicación en un modelo SaaS (Software as a Service).

---

## ✨ ¿Qué hace?

### 1. **Período de Prueba (Trial)**
- 14 días gratis para nuevos usuarios
- Acceso completo a todas las funcionalidades
- Se vence automáticamente si no pagan

### 2. **Control de Acceso**
- Bloquea la API cuando el trial vence
- Código de error 402 (Payment Required)
- Guía al usuario a la página de pago

### 3. **Límites por Plan**
- Productos máximos según el plan
- Usuarios máximos según el plan
- Almacenes máximos según el plan
- Cajas máximas según el plan

### 4. **Planes Disponibles**

| Plan | Precio | Productos | Usuarios | Trial |
|------|--------|-----------|----------|-------|
| 🆓 Trial | $0 | 50 | 3 | 14 días |
| 💼 Básico | $19/mes | 500 | 5 | 14 días |
| ⭐ Pro | $39/mes | 5,000 | 10 | 14 días |
| 👑 Premium | $99/mes | ∞ | ∞ | 14 días |

---

## 🛠️ ¿Cómo funciona?

### Flujo del Usuario

```
1. Usuario se registra
   ↓
2. Recibe 14 días de Trial GRATIS
   ↓
3. Usa el sistema normalmente
   ↓
4. ¿Trial vencido?
   ├── NO → Sigue usando el sistema
   └── SÍ → Acceso bloqueado (Error 402)
       ↓
5. Usuario paga un plan
   ↓
6. Acceso restaurado ✅
```

### Flujo Técnico

```
3:00 AM todos los días
   ↓
Comando automático verifica trials
   ↓
Si fin_trial < hoy
   ↓
Estado = "vencida"
   ↓
Middleware bloquea acceso
```

---

## 📁 Archivos Creados

### Excepciones (2 archivos)
- `SuscripcionVencidaException.php` - Error cuando el trial vence
- `LimitePlanExcedidoException.php` - Error cuando excedes límites

### Servicio (1 archivo)
- `SuscripcionService.php` - Toda la lógica de negocio

### Middlewares (2 archivos)
- `CheckSuscripcionActiva.php` - Bloquea acceso si está vencido
- `EnforcePlanLimits.php` - Valida límites del plan

### Comando (1 archivo)
- `VerificarSuscripcionesVencidas.php` - Marca trials vencidos

### Controlador (1 archivo)
- `SuscripcionController.php` - Endpoints de la API

### Request (1 archivo)
- `ActivarRequest.php` - Validación de datos

**Total: 8 archivos nuevos**

---

## 🎯 Endpoints de la API

### Ver Estado de Suscripción
```
GET /api/v1/suscripcion/estado
```

### Ver Planes Disponibles
```
GET /api/v1/suscripcion/planes
```

### Activar un Plan (Pagar)
```
POST /api/v1/suscripcion/activar
{
    "plan_id": 2,
    "duracion_meses": 1,
    "metodo_pago": "transferencia",
    "referencia_pago": "PAY-123"
}
```

### Cancelar Suscripción
```
POST /api/v1/suscripcion/cancelar
{
    "motivo": "Cambio de proveedor"
}
```

---

## ⚡ Respuestas de la API

### Cuando el Trial está vencido (402)
```json
{
    "success": false,
    "error": "suscripcion_vencida",
    "message": "Tu período de prueba finalizó el 17/06/2026.",
    "action": "actualizar_plan",
    "redirect": "/billing"
}
```

### Cuando excedes límites (402)
```json
{
    "success": false,
    "error": "limite_plan_excedido",
    "recurso": "productos",
    "message": "Has alcanzado el límite de 500 productos.",
    "action": "actualizar_plan"
}
```

### Estado actual (200)
```json
{
    "success": true,
    "data": {
        "estado": "activa",
        "plan": "Básico",
        "precio": "19.00",
        "limites": {
            "productos": 500,
            "usuarios": 5
        }
    }
}
```

---

## 🔧 Comandos Útiles

### Verificar trials vencidos manualmente
```bash
php artisan suscripciones:verificar-vencidas
```

### Ver rutas de suscripción
```bash
php artisan route:list --path=suscripcion
```

### Probar el sistema
```bash
php test_suscripcion.php
```

---

## 📊 Estados de Suscripción

| Estado | Significado | Acceso |
|--------|-------------|--------|
| `trial` | Período de prueba | ✅ Permitido |
| `activa` | Plan pagado | ✅ Permitido |
| `vencida` | Trial expirado | ❌ Bloqueado |
| `suspendida` | Incumplimiento | ❌ Bloqueado |
| `cancelada` | Usuario canceló | ❌ Bloqueado |

---

## 💡 Características Clave

### 1. Automático
- Se ejecuta solo todos los días a las 3:00 AM
- No necesitas verificar manualmente

### 2. Inteligente
- Valida límites en tiempo real
- Bloquea creación de recursos si excedes el plan

### 3. Flexible
- Permite pagar aun con trial vencido
- Usuario puede cancelar cuando quiera

### 4. Multi-moneda
- Precios en USD
- Soporta múltiples pasarelas de pago

---

## 🎬 Demo Rápido

### Paso 1: Usuario se registra
```bash
# Automáticamente recibe 14 días de Trial
```

### Paso 2: Verificar estado
```bash
GET /api/v1/suscripcion/estado
# Estado: trial, días restantes: 14
```

### Paso 3: Trial vence
```bash
# A las 3:00 AM el scheduler marca como "vencida"
# Usuario intenta acceder → Error 402
```

### Paso 4: Usuario paga
```bash
POST /api/v1/suscripcion/activar
# Estado: activa, acceso restaurado
```

---

## 🏗️ Arquitectura

```
Usuario
   ↓
Middleware: CheckSuscripcionActiva
   ↓
¿Está activa?
   ├── SÍ → Continúa
   └── NO → Error 402

Middleware: EnforcePlanLimits
   ↓
¿Excede límite?
   ├── NO → Crea recurso
   └── SÍ → Error 402
```

---

## 📈 Escalabilidad

- Fácil agregar nuevos planes
- Límites configurables por plan
- Soporte para múltiples pasarelas de pago
- Webhooks de pago (Stripe, PayPal, etc.)

---

## 🔐 Seguridad

- Middleware protege todas las rutas
- Validación en cada request
- Control de acceso por tienda
- Auditoría de cambios de plan

---

## 📱 Integración con Frontend

### React/Next.js
```javascript
// Verificar estado al cargar la app
const { data } = await fetch('/api/v1/suscripcion/estado');

if (data.error === 'suscripcion_vencida') {
    router.push('/billing');
}
```

### Mostrar límites en UI
```javascript
const limites = data.limites;
// productos: 500, usuarios: 5, etc.
```

---

## 🎯 Beneficios

✅ Monetiza tu API  
✅ Control de acceso automático  
✅ Límites por plan  
✅ Trial para nuevos usuarios  
✅ Fácil de mantener  
✅ Escalable  

---

## 📞 Soporte

- Revisa `README-SUSCRIPCIONES.md` para documentación completa
- Ejecuta `php test_suscripcion.php` para probar
- Logs en `storage/logs/laravel.log`

---

## 🚀 ¡Listo para Producción!

1. Configura el cron job en tu servidor
2. Integra con pasarela de pago (Stripe, PayPal)
3. Personaliza los planes según tu negocio
4. ¡Empieza a monetizar tu API!

---

**Hecho con ❤️ para TiendaPOS API**

#TiendaPOS #SaaS #Laravel #API #PHP #DesarrolloWeb #Monetizacion #PuntoDeVenta
