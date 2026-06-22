# 🚀 Construí un sistema de registro multi-país en Laravel desde cero

## ¿Qué hice hoy?

Partí de cero y en una sola sesión armé el **backend completo de onboarding** para un sistema POS (Punto de Venta) que funciona en 9 países de Latinoamérica. Todo conectado a **PostgreSQL en Neon** (cloud serverless).

---

## 🧱 Lo que se construyó

### 🔐 Sistema de Registro Multi-País (Onboarding en 4 pasos)

```
Paso 1 → El usuario crea su cuenta + se genera una tienda vacía + suscripción Trial automática
Paso 2 → Ingresa sus datos fiscales reales (RIF/NIT/RFC/RUC según su país)
          → El sistema siembra AUTOMÁTICAMENTE los impuestos y monedas de ese país
Paso 3 → Configura su tipo de negocio (bodega, farmacia, ferretería, motos, etc.)
          → Se crean solos: almacén, caja, categorías, métodos de pago locales
Paso 4 → Agrega su primer producto con inventario inicial
          → ¡Listo para vender!
```

### 🌎 9 países soportados desde el día 1

| País | Impuesto | Moneda | Método de pago local |
|------|----------|--------|----------------------|
| 🇻🇪 Venezuela | IVA 16% + IGTF 3% | USD + VES | Pago Móvil, Zelle, USDT |
| 🇨🇴 Colombia | IVA 19% | COP | Nequi, PSE |
| 🇲🇽 México | IVA 16% / 0% alimentos | MXN | SPEI, CoDi |
| 🇪🇨 Ecuador | IVA 15% / 0% medicamentos | USD | Deuna |
| 🇦🇷 Argentina | IVA 21% | ARS | Mercado Pago QR |
| 🇵🇪 Perú | IGV 18% | PEN | Yape, Plin |
| 🇨🇱 Chile | IVA 19% | CLP | Mach |
| 🇧🇴 Bolivia | IVA 13% | BOB | QR |
| 🇺🇾 Uruguay | IVA 22% | UYU | Tarjeta |

---

## 🔥 Lo más brutal del sistema

**Siembra fiscal automática por país** → cuando el usuario elige su país, el sistema configura SOLO los impuestos correctos, las monedas, y los métodos de pago locales. Cero configuración manual.

**Venezuela tiene lógica especial** → detecta si el comercio es Agente IGTF y aplica el 3% de recargo automáticamente cuando el cliente paga en dólares, Zelle o crypto. Lo hace línea por línea en la factura. Exactamente como lo pide el SENIAT.

**Índice único parcial en PostgreSQL** → solo UN impuesto puede ser el "default" al mismo tiempo. Si cambias de país, el sistema hace el swap correctamente sin romper la restricción de la base de datos.

**Token Sanctum en el Paso 1** → el frontend recibe el token de autenticación desde el primer endpoint. Los pasos 2, 3 y 4 ya van autenticados con ese token.

---

## 🧪 Probado con datos fiscales reales

```
🇻🇪 DISTRIBUIDORA ALIMENTOS CARACAS C.A.  — RIF J-31456789-0  (SENIAT)
🇨🇴 FERRETERÍA EL MAESTRO S.A.S.          — NIT 900.456.123-5 (DIAN)
🇲🇽 MISCELÁNEA DON MARCO S.A. DE C.V.     — RFC MDM850312HJ4  (SAT)
🇪🇨 FARMACIA SALUD INTEGRAL CIA. LTDA.    — RUC 0992567843001 (SRI)
```

Cada prueba crea la tienda, siembra impuestos, crea el negocio, registra un producto y verifica todo contra la base de datos en Neon. Todo verde ✅.

---

## 🛠️ Stack técnico

```
Backend   → Laravel 11 + PHP 8.3
Base de datos → PostgreSQL en Neon (serverless cloud)
Auth      → Laravel Sanctum (tokens por tienda)
Roles     → Spatie Permissions
Inventario → Motor propio con conversión de unidades (kg, lt, und, m, etc.)
Fiscal    → Motor de impuestos multi-alícuota con índice único PostgreSQL
```

---

## 📡 API lista para Next.js

```
POST /api/v1/onboarding/cuenta          ← público
POST /api/v1/onboarding/datos-fiscales  ← con token
POST /api/v1/onboarding/configurar-negocio
POST /api/v1/onboarding/primer-producto
GET  /api/v1/onboarding/etiquetas/{pais}  ← devuelve "RIF" o "NIT" o "RFC" según país
GET  /api/v1/onboarding/estado
```

El frontend solo llama 4 endpoints en orden y el negocio queda 100% configurado.

---

> **Base de datos:** Neon PostgreSQL — serverless, edge-ready, perfecto para SaaS  
> **Objetivo:** Un SaaS POS multi-tenant que funcione en toda Latinoamérica  
> **Estado:** Backend ✅ completo — Frontend Next.js → próximo paso

---

*TiendaPOS 2026 — Sistema POS + ERP para Latinoamérica*
