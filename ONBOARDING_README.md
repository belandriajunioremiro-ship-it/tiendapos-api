# 📋 ONBOARDING API — TiendaPOS

> Sistema de incorporación multi-país para nuevas tiendas.  
> Crea usuario, tienda, suscripción Trial y toda la configuración fiscal en 4 pasos.

---

## 🗺️ Flujo Completo del Onboarding

```
POST /api/v1/onboarding/cuenta          ← PÚBLICO (sin token)
        ↓ devuelve TOKEN Sanctum
POST /api/v1/onboarding/datos-fiscales  ← requiere token
        ↓ siembra impuestos y monedas del país
POST /api/v1/onboarding/configurar-negocio ← requiere token
        ↓ crea almacén, caja, categorías, métodos de pago
POST /api/v1/onboarding/primer-producto    ← requiere token (opcional)
   ó POST /api/v1/onboarding/saltar-primer-producto
        ↓ onboarding_completado = true
```

---

## 🧱 Archivos Creados

### Servicio principal
| Archivo | Responsabilidad |
|---------|----------------|
| `app/Services/OnboardingService.php` | Motor de los 4 pasos + siembra automática por país |

### Controlador API
| Archivo | Ruta base |
|---------|-----------|
| `app/Http/Controllers/Api/V1/OnboardingController.php` | `/api/v1/onboarding/...` |

### Form Requests (Validación)
| Archivo | Paso |
|---------|------|
| `app/Http/Requests/Api/V1/Onboarding/CuentaRequest.php` | Paso 1 |
| `app/Http/Requests/Api/V1/Onboarding/DatosFiscalesRequest.php` | Paso 2 |
| `app/Http/Requests/Api/V1/Onboarding/ConfigurarNegocioRequest.php` | Paso 3 |
| `app/Http/Requests/Api/V1/Onboarding/PrimerProductoRequest.php` | Paso 4 |

### Modelos Eloquent nuevos
| Modelo | Tabla | Notas |
|--------|-------|-------|
| `TiendaOnboarding` | `tienda_onboarding` | Estado del embudo por tienda |
| `OnboardingPaso` | `onboarding_pasos` | Catálogo de pasos |
| `Plane` | `planes` | ⚠️ Nombre con 'e' (Plan es palabra PHP reservada) |
| `Suscripcion` | `suscripciones` | Trial / Pago / Cancelada |
| `TiendaMoneda` | `tienda_monedas` | Monedas activas por tienda |
| `PlantillasImpresion` | `plantillas_impresion` | Plantillas de ticket |
| `ConfiguracionImpresora` | `configuracion_impresora` | Config de impresora por caja |
| `Moneda` | `monedas` | Catálogo de monedas (PK = string codigo) |

### Migración
| Archivo | Descripción |
|---------|-------------|
| `database/migrations/2026_06_18_300000_add_tienda_id_to_users_table.php` | Agrega `tienda_id` y `activo` a `users` |

### Rutas en `routes/api.php`
```php
// Públicas
POST   /api/v1/onboarding/cuenta
GET    /api/v1/onboarding/etiquetas/{pais}

// Privadas (auth:sanctum)
GET    /api/v1/onboarding/estado
POST   /api/v1/onboarding/datos-fiscales
POST   /api/v1/onboarding/configurar-negocio
POST   /api/v1/onboarding/primer-producto
POST   /api/v1/onboarding/saltar-primer-producto
```

---

## 🌎 Países Soportados y su Configuración Automática

| País | Código | Moneda | IVA Principal | Impuesto Especial | Métodos de Pago |
|------|--------|--------|--------------|-------------------|-----------------|
| Venezuela | `VE` | USD + VES | 16% | IGTF 3% sobre divisas | Efectivo, Pago Móvil, Zelle, USDT |
| Colombia | `CO` | COP | 19% | — | Nequi, PSE, Tarjeta |
| México | `MX` | MXN | 16% | — | SPEI, QR, Tarjeta |
| Ecuador | `EC` | USD | 15% | — | Deuna, Tarjeta |
| Argentina | `AR` | ARS + USD | 21% | — | Mercado Pago QR |
| Perú | `PE` | PEN | IGV 18% | — | Yape, Plin |
| Chile | `CL` | CLP | 19% | — | Mach, Tarjeta |
| Bolivia | `BO` | BOB | 13% | — | QR |
| Uruguay | `UY` | UYU | 22% | — | Tarjeta |

---

## 🧪 Cómo Ejecutar la Prueba Backend (PHP)

El script `test_onboarding_completo.php` prueba los 4 pasos completos contra la base de datos real en Neon.

```bash
# Desde la raíz del proyecto:
php test_onboarding_completo.php
```

**Qué hace el script:**
1. Limpia registros previos del email `test@onboarding.com`
2. Crea una cuenta nueva para Venezuela (`VE`)
3. Guarda datos fiscales con RIF real
4. Configura una bodega con categorías, métodos de pago e impresora
5. Crea el primer producto (Harina PAN — Exento de IVA)
6. Imprime el token Sanctum y el resumen completo

**Resultado esperado:**
```
✅ PRUEBA EXITOSA
  Tienda ID     : #8
  Token (guarda): 5|SChVG57DEhWvUzHT...
```

---

## 🌐 Cómo Integrarlo en Next.js (Frontend)

### 1. Instalar dependencias

```bash
npm install axios
```

### 2. Crear el cliente HTTP

```js
// lib/api.js
import axios from 'axios';

const api = axios.create({
  baseURL: process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api',
  headers: { 'Accept': 'application/json' },
});

// Inyectar token automáticamente si existe
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token');
  if (token) config.headers.Authorization = `Bearer ${token}`;
  return config;
});

export default api;
```

### 3. Variables de entorno

```env
# .env.local
NEXT_PUBLIC_API_URL=http://localhost:8000/api
```

### 4. Paso 1 — Crear Cuenta (Registro)

```js
// pages/onboarding/registro.jsx
import api from '@/lib/api';

async function crearCuenta(formData) {
  const { data } = await api.post('/v1/onboarding/cuenta', {
    name:                  formData.name,
    email:                 formData.email,
    password:              formData.password,
    password_confirmation: formData.passwordConfirm,
    pais:                  formData.pais,   // 'VE', 'CO', 'MX', 'EC', etc.
  });

  // Guardar token para los pasos siguientes
  localStorage.setItem('token', data.data.token);
  localStorage.setItem('tienda_id', data.data.tienda.id);

  return data;
}
```

### 5. Paso 2 — Datos Fiscales

```js
async function guardarDatosFiscales(formData) {
  const { data } = await api.post('/v1/onboarding/datos-fiscales', {
    identificacion_fiscal: formData.rif,       // RIF / NIT / RFC / RUC
    razon_social:          formData.razonSocial,
    nombre_comercial:      formData.nombreComercial,
    direccion:             formData.direccion,
    telefono:              formData.telefono,
    regimen_fiscal:        formData.regimenFiscal,
  });
  return data;
}
```

### 6. Paso 3 — Configurar Negocio

```js
async function configurarNegocio(formData) {
  const { data } = await api.post('/v1/onboarding/configurar-negocio', {
    tipo_negocio:   formData.tipoNegocio,   // 'bodega', 'farmacia', 'motos', etc.
    nombre_almacen: formData.nombreAlmacen,
    nombre_caja:    formData.nombreCaja,
    tipo_impresora: formData.tipoImpresora, // 'termica_80mm', 'a4', etc.
  });
  return data;
}
```

### 7. Paso 4 — Primer Producto (opcional)

```js
async function crearPrimerProducto(formData) {
  const { data } = await api.post('/v1/onboarding/primer-producto', {
    nombre:        formData.nombre,
    sku:           formData.sku,
    costo:         formData.costo,
    aplica_iva:    formData.aplicaIva,     // true / false
    stock_inicial: formData.stock,
  });
  return data;
}

// Si el usuario prefiere saltar este paso:
async function saltarProducto() {
  const { data } = await api.post('/v1/onboarding/saltar-primer-producto');
  return data;
}
```

### 8. Etiquetas por País (Mostrar RIF/NIT/RFC dinámicamente)

```js
async function obtenerEtiquetas(pais) {
  const { data } = await api.get(`/v1/onboarding/etiquetas/${pais}`);
  // data.data = { "id_fiscal": { "etiqueta": "RIF", "placeholder": "J-12345678-9" } }
  return data.data;
}
```

### 9. Verificar estado del onboarding

```js
async function obtenerEstado() {
  const { data } = await api.get('/v1/onboarding/estado');
  // data.data.paso_actual  → número del paso actual
  // data.data.completado   → true/false
  return data.data;
}
```

---

## 📬 Prueba con Postman / cURL

### Paso 1 — Crear Cuenta
```bash
curl -X POST http://localhost:8000/api/v1/onboarding/cuenta \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Juan Perez",
    "email": "juan@mitienda.com",
    "password": "12345678",
    "password_confirmation": "12345678",
    "pais": "VE"
  }'
```

### Paso 2 — Datos Fiscales (reemplaza TU_TOKEN)
```bash
curl -X POST http://localhost:8000/api/v1/onboarding/datos-fiscales \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer TU_TOKEN" \
  -d '{
    "identificacion_fiscal": "J-12345678-9",
    "razon_social": "Mi Tienda C.A.",
    "direccion": "Av. Principal, Caracas",
    "telefono": "+58 212-5551234"
  }'
```

### Paso 3 — Configurar Negocio
```bash
curl -X POST http://localhost:8000/api/v1/onboarding/configurar-negocio \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer TU_TOKEN" \
  -d '{
    "tipo_negocio": "bodega",
    "nombre_almacen": "Deposito Principal",
    "nombre_caja": "Caja 1",
    "tipo_impresora": "termica_80mm"
  }'
```

### Paso 4 — Primer Producto
```bash
curl -X POST http://localhost:8000/api/v1/onboarding/primer-producto \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer TU_TOKEN" \
  -d '{
    "nombre": "Harina PAN 1kg",
    "sku": "HPAN-001",
    "costo": 1.20,
    "aplica_iva": false,
    "stock_inicial": 100
  }'
```

### Tipos de negocio disponibles
`farmacia` · `ferreteria` · `bodega` · `restaurante` · `licoreria` · `abarrotes` · `ropa` · `motos` · `general`

### Tipos de impresora disponibles
`termica_58mm` · `termica_80mm` · `a4` · `pdf` · `ninguno`

---

## ✅ Verificación de la Base de Datos

Tras completar el flujo completo se espera:

| Tabla | Registros creados |
|-------|------------------|
| `users` | 1 usuario admin |
| `tienda` | 1 tienda configurada |
| `tienda_onboarding` | `paso_actual=4`, `completado=true` |
| `suscripciones` | 1 trial activo |
| `impuestos` | 2-3 impuestos según país |
| `tienda_monedas` | 1-2 monedas habilitadas |
| `tasas_cambio` | 1 tasa BCV (solo VE) |
| `almacenes` | 1 depósito |
| `cajas` | 1 caja |
| `categorias_productos` | 4-6 categorías del negocio |
| `metodos_pago` | 6-8 métodos del país |
| `clientes` | CONSUMIDOR FINAL |
| `margenes_ganancia` | 1 margen 20% default |
| `listas_precio` | 1 lista "Precio detal" |
| `plantillas_impresion` | 1 plantilla térmica |
| `configuracion_impresora` | 1 impresora principal |
| `productos` | 1 producto (si no saltó Paso 4) |
| `variantes_producto` | 1 variante del producto |
| `inventario` | 1 registro con stock inicial |

---

*Generado automaticamente — TiendaPOS API 2026*
