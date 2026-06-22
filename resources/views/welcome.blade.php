<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TiendaPOS — Sistema de Punto de Venta para Latinoamérica</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700,800" rel="stylesheet" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: #fafafa;
            color: #1a1a2e;
            line-height: 1.6;
        }
        .container { max-width: 1140px; margin: 0 auto; padding: 0 1.5rem; }
        h1, h2, h3 { font-weight: 700; line-height: 1.2; }
        h2 { font-size: 2rem; text-align: center; margin-bottom: 0.75rem; }
        h2 + p.section-sub { text-align: center; color: #64748b; max-width: 640px; margin: 0 auto 3rem; font-size: 1.05rem; }

        nav {
            position: sticky; top: 0; z-index: 50;
            background: rgba(250,250,250,0.92); backdrop-filter: blur(12px);
            border-bottom: 1px solid #e2e8f0;
            display: flex; align-items: center; justify-content: space-between;
            padding: 0.85rem 1.5rem; max-width: 1140px; margin: 0 auto;
            flex-wrap: wrap; gap: 0.75rem;
        }
        nav .logo { font-size: 1.25rem; font-weight: 800; text-decoration: none; color: #1a1a2e; }
        nav .logo span { color: #7c3aed; }
        nav .nav-links { display: flex; gap: 1.75rem; align-items: center; flex-wrap: wrap; }
        nav .nav-links a {
            font-size: 0.85rem; font-weight: 500; color: #475569;
            text-decoration: none; transition: color 0.2s;
        }
        nav .nav-links a:hover { color: #7c3aed; }
        nav .badge {
            font-size: 0.65rem; padding: 0.25rem 0.65rem; border-radius: 9999px;
            background: rgba(34,197,94,0.1); color: #16a34a; font-weight: 600;
            border: 1px solid rgba(34,197,94,0.2); display: inline-flex; align-items: center; gap: 0.35rem;
            margin-left: 0.5rem;
        }
        nav .badge::before {
            content: ''; width: 5px; height: 5px; border-radius: 50%;
            background: #16a34a; animation: pulse 2s infinite;
        }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.4; } }

        .hero {
            padding: 5.5rem 0 3.5rem; text-align: center;
        }
        .hero h1 {
            font-size: clamp(2.2rem, 5vw, 3.5rem);
            max-width: 780px; margin: 0 auto 1rem;
        }
        .hero h1 span { color: #7c3aed; }
        .hero .sub {
            font-size: 1.1rem; color: #475569; max-width: 680px;
            margin: 0 auto 2rem; line-height: 1.7;
        }
        .hero-actions { display: flex; gap: 0.75rem; justify-content: center; flex-wrap: wrap; }
        .btn {
            display: inline-flex; align-items: center; gap: 0.5rem;
            padding: 0.8rem 1.75rem; border-radius: 10px;
            font-size: 0.95rem; font-weight: 600; text-decoration: none;
            transition: all 0.2s;
        }
        .btn-primary { background: #7c3aed; color: #fff; }
        .btn-primary:hover { background: #6d28d9; transform: translateY(-1px); }
        .btn-outline { background: transparent; color: #1a1a2e; border: 1.5px solid #e2e8f0; }
        .btn-outline:hover { border-color: #7c3aed; color: #7c3aed; }
        .hero-stats {
            display: flex; justify-content: center; gap: 2.5rem; flex-wrap: wrap;
            margin-top: 3rem; padding-top: 2.5rem; border-top: 1px solid #e2e8f0;
        }
        .hero-stats .stat { text-align: center; }
        .hero-stats .num { font-size: 1.5rem; font-weight: 800; color: #7c3aed; }
        .hero-stats .label { font-size: 0.8rem; color: #64748b; margin-top: 0.15rem; }

        section { scroll-margin-top: 5rem; }

        .vp-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 1.5rem; padding: 1rem 0 3rem;
        }
        .vp-card {
            background: #fff; border: 1px solid #e2e8f0; border-radius: 16px;
            padding: 2rem; transition: all 0.25s;
        }
        .vp-card:hover {
            border-color: #c4b5fd; box-shadow: 0 8px 30px rgba(124,58,237,0.08);
            transform: translateY(-2px);
        }
        .vp-card .icon {
            width: 48px; height: 48px; border-radius: 14px;
            background: linear-gradient(135deg, rgba(124,58,237,0.1), rgba(124,58,237,0.04));
            display: flex; align-items: center; justify-content: center; margin-bottom: 1rem;
            color: #7c3aed;
        }
        .vp-card h3 { font-size: 1.05rem; margin-bottom: 0.4rem; }
        .vp-card p { font-size: 0.9rem; color: #64748b; line-height: 1.65; }

        .features-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
            gap: 1rem; padding: 0 0 4rem;
        }
        .feature-card {
            display: flex; gap: 1rem;
            background: #fff; border: 1px solid #e2e8f0; border-radius: 14px;
            padding: 1.5rem; transition: all 0.25s;
        }
        .feature-card:hover { border-color: #c4b5fd; }
        .feature-card .icon {
            flex-shrink: 0; width: 42px; height: 42px; border-radius: 12px;
            background: rgba(124,58,237,0.08);
            display: flex; align-items: center; justify-content: center;
            color: #7c3aed;
        }
        .feature-card h4 { font-size: 0.95rem; margin-bottom: 0.25rem; }
        .feature-card p { font-size: 0.85rem; color: #64748b; line-height: 1.6; }

        .countries-section { padding: 2rem 0 4rem; }
        .country-chips {
            display: flex; justify-content: center; flex-wrap: wrap; gap: 0.6rem;
            margin-bottom: 0.75rem;
        }
        .chip {
            width: 52px; height: 52px; border-radius: 50%;
            background: #fff; border: 1px solid #e2e8f0;
            font-size: 1.5rem;
            display: flex; align-items: center; justify-content: center;
            transition: all 0.2s;
        }
        .chip:hover { border-color: #c4b5fd; transform: translateY(-2px); }
        .country-names {
            text-align: center; font-size: 0.95rem; color: #475569;
            margin-bottom: 2rem; font-weight: 500;
        }
        .fiscal-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 0.75rem; max-width: 860px; margin: 0 auto;
        }
        .fiscal-item {
            background: #fff; border: 1px solid #e2e8f0; border-radius: 10px;
            padding: 1rem 1.25rem; text-align: center;
            transition: border-color 0.2s;
        }
        .fiscal-item:hover { border-color: #c4b5fd; }
        .fiscal-item .fiscal-label {
            display: block; font-size: 0.7rem; font-weight: 600;
            text-transform: uppercase; letter-spacing: 0.05em;
            color: #7c3aed; margin-bottom: 0.25rem;
        }
        .fiscal-item span:last-child {
            font-size: 0.85rem; color: #475569;
        }

        .plans-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 1.25rem; padding: 0 0 4rem;
        }
        .plan-card {
            background: #fff; border: 1px solid #e2e8f0; border-radius: 16px;
            padding: 2rem 1.5rem; text-align: center; transition: all 0.25s;
        }
        .plan-card:hover { border-color: #c4b5fd; }
        .plan-card.highlight {
            border-color: #7c3aed; box-shadow: 0 8px 32px rgba(124,58,237,0.12);
            transform: scale(1.03);
        }
        .plan-card .plan-name {
            font-size: 0.8rem; font-weight: 600; color: #7c3aed;
            text-transform: uppercase; letter-spacing: 0.05em;
        }
        .plan-card .plan-price { font-size: 2rem; font-weight: 800; margin: 0.5rem 0 0.25rem; }
        .plan-card .plan-price span { font-size: 0.9rem; font-weight: 400; color: #94a3b8; }
        .plan-card .plan-desc { font-size: 0.85rem; color: #64748b; margin-bottom: 1.25rem; }
        .plan-card ul { list-style: none; font-size: 0.85rem; color: #475569; line-height: 2.2; }
        .plan-card ul li::before { content: '✓ '; color: #7c3aed; font-weight: 700; }

        footer {
            border-top: 1px solid #e2e8f0; padding: 2rem 0;
            font-size: 0.8rem; color: #94a3b8; text-align: center;
        }
        footer a { color: #64748b; text-decoration: none; }
        footer a:hover { color: #7c3aed; }

        @media (max-width: 640px) {
            nav { flex-direction: column; align-items: flex-start; }
            nav .nav-links { gap: 1rem; }
            .hero { padding: 3.5rem 0 2rem; }
            h2 { font-size: 1.5rem; }
            .features-grid { grid-template-columns: 1fr; }
            .plans-grid { grid-template-columns: 1fr; max-width: 320px; margin: 0 auto; }
        }
    </style>
</head>
<body>

    <nav>
        <a href="#" class="logo">Tienda<span>POS</span></a>
        <div class="nav-links">
            <a href="#valor">Valor</a>
            <a href="#features">Funcionalidades</a>
            <a href="#paises">Países</a>
            <a href="#planes">Planes</a>
            <span class="badge">Operativo</span>
        </div>
    </nav>

    <div class="container">

        <section class="hero">
            <h1>Tu negocio merece un POS <span>sin fronteras</span></h1>
            <p class="sub">
                Multi-tenant, multimoneda y preparado para los regímenes fiscales de 9 países.
                Gestiona ventas, inventario, facturación y créditos desde un solo sistema.
            </p>
            <div class="hero-actions">
                <a href="https://github.com/belandriajunioremiro-ship-it/tiendapos-api" target="_blank" class="btn btn-outline">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/></svg>
                    GitHub
                </a>
                <a href="https://dashboard.render.com/web/srv-d8scgfojs32c73cv3sgg" target="_blank" class="btn btn-primary">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                    Dashboard
                </a>
            </div>
            <div class="hero-stats">
                <div class="stat"><div class="num">9</div><div class="label">Países</div></div>
                <div class="stat"><div class="num">80+</div><div class="label">Tablas</div></div>
                <div class="stat"><div class="num">112</div><div class="label">Endpoints</div></div>
                <div class="stat"><div class="num">3</div><div class="label">Roles</div></div>
            </div>
        </section>

        <h2 id="valor">Diseñado para crecer</h2>
        <p class="section-sub">Una arquitectura moderna que se adapta a la operación de tu negocio, sin importar el tamaño o el país.</p>

        <section class="vp-grid">
            <div class="vp-card">
                <div class="icon">
                    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                </div>
                <h3>Multi-tenant nativo</h3>
                <p>Una sola instancia, múltiples tiendas completamente aisladas. Ideal para cadenas, franquicias o negocios independientes.</p>
            </div>
            <div class="vp-card">
                <div class="icon">
                    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                </div>
                <h3>Multimoneda real</h3>
                <p>USD, VES, COP, MXN, ARS y más. Tasas de cambio con historial, pagos mixtos y cálculo de IGTF para Venezuela.</p>
            </div>
            <div class="vp-card">
                <div class="icon">
                    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                </div>
                <h3>Facturación regional</h3>
                <p>RIF, NIT, RFC, RUC, CUIT. IVA/IGV configurable por país. Facturas, cotizaciones, notas y devoluciones.</p>
            </div>
            <div class="vp-card">
                <div class="icon">
                    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                </div>
                <h3>API-first</h3>
                <p>Backend Laravel con 112 endpoints protegidos con Sanctum. Consume desde cualquier frontend con total flexibilidad.</p>
            </div>
        </section>

        <h2 id="features">Todo lo que necesitas para operar</h2>
        <p class="section-sub">Módulos completos que cubren cada aspecto del día a día de tu punto de venta.</p>

        <section class="features-grid">
            <div class="feature-card">
                <div class="icon">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                </div>
                <div>
                    <h4>POS + Caja</h4>
                    <p>Pantalla de venta rápida. Sesiones de caja con apertura y cierre. Pagos mixtos y descuentos por línea y globales.</p>
                </div>
            </div>
            <div class="feature-card">
                <div class="icon">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                </div>
                <div>
                    <h4>Facturación</h4>
                    <p>Facturas, cotizaciones, notas de débito y devoluciones. Numeración correlativa por tipo de documento.</p>
                </div>
            </div>
            <div class="feature-card">
                <div class="icon">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                </div>
                <div>
                    <h4>Inventario</h4>
                    <p>Stock en tiempo real. Múltiples almacenes, lotes con FEFO, traslados y alertas automáticas de stock bajo.</p>
                </div>
            </div>
            <div class="feature-card">
                <div class="icon">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                </div>
                <div>
                    <h4>Multimoneda</h4>
                    <p>USD, VES, COP, MXN, ARS, PEN, CLP, BOB, UYU. Tasas de cambio con historial diario y cálculo automático de IGTF.</p>
                </div>
            </div>
            <div class="feature-card">
                <div class="icon">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <div>
                    <h4>Roles y permisos</h4>
                    <p>Administrador, supervisor y cajero. 69 permisos granulares para controlar cada acción dentro del sistema.</p>
                </div>
            </div>
            <div class="feature-card">
                <div class="icon">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                </div>
                <div>
                    <h4>Créditos</h4>
                    <p>Cuentas por cobrar, abonos y facturas a crédito con bloqueo pesimista para evitar sobreventa.</p>
                </div>
            </div>
            <div class="feature-card">
                <div class="icon">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21.21 15.89A10 10 0 1 1 8 2.83"/><path d="M22 12A10 10 0 0 0 12 2v10z"/></svg>
                </div>
                <div>
                    <h4>Reportes</h4>
                    <p>Ventas por período, rentabilidad, inventario y cuentas por cobrar. Dashboard con KPIs actualizados en tiempo real.</p>
                </div>
            </div>
            <div class="feature-card">
                <div class="icon">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="4 7 4 4 20 4 20 7"/><line x1="9" y1="20" x2="15" y2="20"/><line x1="12" y1="4" x2="12" y2="20"/></svg>
                </div>
                <div>
                    <h4>Suscripciones</h4>
                    <p>Planes Trial, Básico, Pro y Premium con límites configurables por recurso: productos, usuarios, almacenes y cajas.</p>
                </div>
            </div>
            <div class="feature-card">
                <div class="icon">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </div>
                <div>
                    <h4>Onboarding</h4>
                    <p>Wizard de 4 pasos: cuenta, datos fiscales, configuración del negocio y primer producto. Listo en minutos.</p>
                </div>
            </div>
        </section>

        <h2 id="paises">Cobertura regional</h2>
        <p class="section-sub">Regímenes fiscales, monedas y métodos de pago nativos para cada país.</p>

        <section class="countries-section">
            <div class="country-chips">
                <div class="chip">🇻🇪</div>
                <div class="chip">🇨🇴</div>
                <div class="chip">🇲🇽</div>
                <div class="chip">🇪🇨</div>
                <div class="chip">🇦🇷</div>
                <div class="chip">🇵🇪</div>
                <div class="chip">🇨🇱</div>
                <div class="chip">🇧🇴</div>
                <div class="chip">🇺🇾</div>
            </div>
            <div class="country-names">
                Venezuela &middot; Colombia &middot; México &middot; Ecuador &middot; Argentina
                &middot; Perú &middot; Chile &middot; Bolivia &middot; Uruguay
            </div>
            <div class="fiscal-grid">
                <div class="fiscal-item">
                    <span class="fiscal-label">Identificación fiscal</span>
                    <span>RIF, NIT, RFC, RUC, CUIT</span>
                </div>
                <div class="fiscal-item">
                    <span class="fiscal-label">Impuestos</span>
                    <span>IVA / IGV por país</span>
                </div>
                <div class="fiscal-item">
                    <span class="fiscal-label">Monedas</span>
                    <span>USD, VES, COP, MXN, ARS, PEN, CLP, BOB, UYU</span>
                </div>
                <div class="fiscal-item">
                    <span class="fiscal-label">Estatus fiscal</span>
                    <span>Datos formales — sin certificación fiscal</span>
                </div>
            </div>
        </section>

        <h2 id="planes">Planes</h2>
        <p class="section-sub">Desde emprender hasta escalar. Elige el plan que mejor se ajuste a tu operación.</p>

        <section class="plans-grid">
            <div class="plan-card">
                <div class="plan-name">Trial</div>
                <div class="plan-price">$0 <span>/mes</span></div>
                <div class="plan-desc">14 días gratis. Sin compromiso.</div>
                <ul>
                    <li>50 productos</li>
                    <li>2 usuarios</li>
                    <li>1 almacén</li>
                    <li>1 caja</li>
                </ul>
            </div>
            <div class="plan-card">
                <div class="plan-name">Básico</div>
                <div class="plan-price">$19 <span>/mes</span></div>
                <div class="plan-desc">Para negocios en crecimiento.</div>
                <ul>
                    <li>200 productos</li>
                    <li>5 usuarios</li>
                    <li>2 almacenes</li>
                    <li>2 cajas</li>
                </ul>
            </div>
            <div class="plan-card highlight">
                <div class="plan-name">Pro</div>
                <div class="plan-price">$49 <span>/mes</span></div>
                <div class="plan-desc">La opción más balanceada.</div>
                <ul>
                    <li>1000 productos</li>
                    <li>15 usuarios</li>
                    <li>5 almacenes</li>
                    <li>5 cajas</li>
                </ul>
            </div>
            <div class="plan-card">
                <div class="plan-name">Premium</div>
                <div class="plan-price">$99 <span>/mes</span></div>
                <div class="plan-desc">Sin límites.</div>
                <ul>
                    <li>Productos ilimitados</li>
                    <li>Usuarios ilimitados</li>
                    <li>Almacenes ilimitados</li>
                    <li>Cajas ilimitadas</li>
                </ul>
            </div>
        </section>

        <footer>
            &copy; {{ date('Y') }} TiendaPOS &mdash; 
            Backend en <a href="https://render.com" target="_blank">Render</a> &middot; 
            Base de datos en <a href="https://neon.tech" target="_blank">Neon</a> &middot; 
            <a href="https://github.com/belandriajunioremiro-ship-it/tiendapos-api" target="_blank">GitHub</a>
        </footer>

    </div>
</body>
</html>