# 🧪 TESTS DE ONBOARDING MULTI-PAÍS — TiendaPOS

> Guía completa para ejecutar las 4 pruebas fiscales y visualizar los resultados.  
> Cada país tiene su test individual con datos fiscales reales y un comando para inspeccionar el detalle.

---

## 📦 Archivos Disponibles

| Script PHP | País | Empresa Simulada |
|------------|------|-----------------|
| `test_onboarding_VE.php` | 🇻🇪 Venezuela | DISTRIBUIDORA ALIMENTOS CARACAS C.A. |
| `test_onboarding_CO.php` | 🇨🇴 Colombia | FERRETERÍA EL MAESTRO S.A.S. |
| `test_onboarding_MX.php` | 🇲🇽 México | MISCELÁNEA DON MARCO S.A. DE C.V. |
| `test_onboarding_EC.php` | 🇪🇨 Ecuador | FARMACIA SALUD INTEGRAL CIA. LTDA. |

---

## 🚀 Cómo Ejecutar los Tests

### 1. Ejecutar el test de un país (desde la raíz del proyecto)

```bash
# Venezuela — Bodega, IVA 16%, IGTF 3%, USD + VES
php test_onboarding_VE.php

# Colombia — Ferretería, IVA 19%, COP
php test_onboarding_CO.php

# México — Abarrotes, IVA 16% / 0% alimentos, MXN
php test_onboarding_MX.php

# Ecuador — Farmacia, IVA 15% / 0% medicamentos, USD
php test_onboarding_EC.php
```

> ✅ Cada script limpia sus datos anteriores automáticamente.  
> Puedes ejecutarlos tantas veces como quieras.

---

## 🔍 Cómo Ver el Detalle de la Tienda Creada

Después de ejecutar cada test, usa el Artisan command para ver el resumen completo y hermoso:

```bash
php artisan onboarding:show VE
php artisan onboarding:show CO
php artisan onboarding:show MX
php artisan onboarding:show EC
```

Sin argumento, te pregunta interactivamente:
```bash
php artisan onboarding:show
```

---

## 📋 Qué Hace Cada Test (Paso a Paso)

Todos los tests siguen el mismo flujo de 4 pasos:

```
PASO 1 → Crear cuenta de usuario + tienda + suscripción Trial
PASO 2 → Datos fiscales reales (RIF/NIT/RFC/RUC) + siembra impuestos + monedas
PASO 3 → Configurar negocio (almacén, caja, categorías, métodos de pago)
PASO 4 → Crear primer producto con inventario inicial
```

---

## 🌎 Datos Fiscales Reales por País

### 🇻🇪 Venezuela — `test_onboarding_VE.php`
| Campo | Valor |
|-------|-------|
| **RIF** | J-31456789-0 |
| **Razón Social** | DISTRIBUIDORA ALIMENTOS CARACAS C.A. |
| **Comercial** | DISTRALIMENTOS |
| **Dirección** | Av. Baralt, Local C-12, Parroquia La Candelaria, Caracas |
| **Teléfono** | +58 212-8641230 |
| **Autoridad** | SENIAT |
| **Régimen** | Contribuyente Ordinario |
| **Actividad** | Distribución de Alimentos al Mayor y Detal (CIIU G5120) |
| **IVA** | 16% general + 8% reducido + Exento (Cesta Básica) |
| **IGTF** | 3% sobre pagos en USD/Zelle/USDT |
| **Monedas** | USD (principal) + VES (secundario) |
| **Tipo negocio** | Bodega |
| **Primer producto** | Harina PAN Maíz Blanco 1kg — **Exento** |

### 🇨🇴 Colombia — `test_onboarding_CO.php`
| Campo | Valor |
|-------|-------|
| **NIT** | 900.456.123-5 |
| **Razón Social** | FERRETERÍA EL MAESTRO S.A.S. |
| **Dirección** | Carrera 68 #22A-15, Barrio Puente Aranda, Bogotá D.C. |
| **Teléfono** | +57 601-4123456 |
| **Autoridad** | DIAN |
| **Régimen** | Régimen Común — Responsable de IVA |
| **Actividad** | Comercio ferretería, pinturas y vidrios (CIIU 4752) |
| **IVA** | 19% general + 5% diferencial |
| **Moneda** | COP — Peso Colombiano |
| **Tipo negocio** | Ferretería |
| **Primer producto** | Cemento Argos Gris 50kg — **IVA 19%** |

### 🇲🇽 México — `test_onboarding_MX.php`
| Campo | Valor |
|-------|-------|
| **RFC** | MDM850312HJ4 |
| **Razón Social** | MISCELÁNEA DON MARCO S.A. DE C.V. |
| **Dirección** | Insurgentes Sur 1602, Col. Crédito Constructor, CDMX, C.P. 03940 |
| **Teléfono** | +52 55-5512-4567 |
| **Autoridad** | SAT |
| **Régimen** | Régimen General de Ley Personas Morales |
| **Actividad** | Comercio tiendas de abarrotes (SCIAN 461110) |
| **IVA** | 16% procesados / 0% alimentos básicos |
| **Moneda** | MXN — Peso Mexicano |
| **Tipo negocio** | Abarrotes |
| **Primer producto** | Tortillas Tía Rosa 1kg — **IVA 0%** (alimento básico SAT) |

### 🇪🇨 Ecuador — `test_onboarding_EC.php`
| Campo | Valor |
|-------|-------|
| **RUC** | 0992567843001 |
| **Razón Social** | FARMACIA SALUD INTEGRAL CIA. LTDA. |
| **Dirección** | Av. 9 de Octubre 2416, Guayaquil, Guayas, C.P. 090112 |
| **Teléfono** | +593 4-2451678 |
| **Autoridad** | SRI |
| **Régimen** | Contribuyente Especial |
| **Actividad** | Farmacias y boticas (CIIU 4773) |
| **IVA** | 15% general / 0% medicamentos |
| **Moneda** | USD (dolarizado desde 2000) |
| **Tipo negocio** | Farmacia |
| **Primer producto** | Acetaminofén 500mg x10 tab — **IVA 0%** (medicamento SRI) |

---

## 🎯 Flujo Completo Recomendado

```bash
# 1. Correr los 4 tests
php test_onboarding_VE.php
php test_onboarding_CO.php
php test_onboarding_MX.php
php test_onboarding_EC.php

# 2. Inspeccionar cada tienda creada
php artisan onboarding:show VE
php artisan onboarding:show CO
php artisan onboarding:show MX
php artisan onboarding:show EC
```

---

## 📊 Qué Muestra el Comando `onboarding:show`

Al ejecutar `php artisan onboarding:show VE` verás:

```
================================================================================
         PRUEBA DE CONEXIÓN TIENDA POS DIRECTAMENTE DESDE NEON TCH              
================================================================================
 🇻🇪 ONBOARDING DETALLE — VE | DISTRALIMENTOS

🏢  DATOS FISCALES
   RIF: J-31456789-0
   Razón Social: DISTRIBUIDORA ALIMENTOS CARACAS C.A.
   IGTF: ✅ Agente IGTF 3%
   ...

👤  USUARIO ADMIN
   Carlos Hernández | ventas@distralimentos.com.ve | ✅ Activo

📦  SUSCRIPCIÓN  →  Trial activo
🧾  IMPUESTOS    →  IVA 16% (DEFAULT), IVA 8%, Exento
💱  MONEDAS      →  USD ✅  |  VES ✅
💳  MÉTODOS PAGO →  Efectivo USD [IGTF], Pago Móvil, Zelle [IGTF], USDT [IGTF]...
🏭  ALMACÉN      →  Depósito Candelaria
📦  PRODUCTO     →  Harina PAN 1kg | Exento | 500 und
✅  Onboarding   →  Paso 4 / 4 — COMPLETADO
================================================================================
```

---

## 🗂️ Archivos del Sistema de Onboarding

```
app/
├── Services/
│   └── OnboardingService.php              ← Motor principal (4 pasos)
├── Http/
│   ├── Controllers/Api/V1/
│   │   └── OnboardingController.php       ← Endpoints REST
│   └── Requests/Api/V1/Onboarding/
│       ├── CuentaRequest.php
│       ├── DatosFiscalesRequest.php
│       ├── ConfigurarNegocioRequest.php
│       └── PrimerProductoRequest.php
├── Models/
│   ├── TiendaOnboarding.php
│   ├── Plane.php                          ← ⚠️ 'Plane' no 'Plan' (palabra reservada PHP)
│   ├── Suscripcion.php
│   ├── TiendaMoneda.php
│   ├── PlantillasImpresion.php
│   ├── ConfiguracionImpresora.php
│   └── Moneda.php
└── Console/Commands/
    └── OnboardingShowCommand.php          ← php artisan onboarding:show {pais}

routes/
└── api.php                                ← /api/v1/onboarding/...

database/migrations/
└── 2026_06_18_300000_add_tienda_id_to_users_table.php

test_onboarding_VE.php                     ← Test Venezuela
test_onboarding_CO.php                     ← Test Colombia
test_onboarding_MX.php                     ← Test México
test_onboarding_EC.php                     ← Test Ecuador
test_onboarding_completo.php               ← Test genérico (cualquier país)
ONBOARDING_README.md                       ← Documentación general del sistema
ONBOARDING_TESTS_README.md                 ← Este archivo (tests multi-país)
```

---

*TiendaPOS API 2026 — Pruebas fiscales multi-LATAM sobre Neon TCH*
