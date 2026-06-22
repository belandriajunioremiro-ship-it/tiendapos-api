<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TiendaPOS — Sistema de Punto de Venta para Latinoamérica</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700,800" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flag-icons/7.2.3/css/flag-icons.min.css" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: #fafafa;
            color: #1a1a2e;
            line-height: 1.6;
            overflow-x: hidden;
        }
        .container { max-width: 1140px; margin: 0 auto; padding: 0 1.5rem; }
        h1, h2, h3 { font-weight: 700; line-height: 1.2; }
        h2 { font-size: clamp(1.5rem, 3vw, 2rem); text-align: center; margin-bottom: 0.75rem; }
        h2 + p.section-sub {
            text-align: center; color: #64748b;
            max-width: 640px; margin: 0 auto 3rem; font-size: 1.05rem;
            padding: 0 1rem;
        }

        /* Nav */
        nav {
            position: sticky; top: 0; z-index: 100;
            background: rgba(250,250,250,0.94); backdrop-filter: blur(12px);
            border-bottom: 1px solid #e2e8f0;
        }
        nav .nav-inner {
            display: flex; align-items: center; justify-content: space-between;
            max-width: 1140px; margin: 0 auto; padding: 0.8rem 1.5rem;
        }
        nav .logo { font-size: 1.25rem; font-weight: 800; text-decoration: none; color: #1a1a2e; }
        nav .logo span { color: #7c3aed; }

        /* Hamburger */
        .hamburger {
            display: none; flex-direction: column; gap: 4px;
            background: none; border: none; cursor: pointer;
            padding: 6px; border-radius: 8px; transition: background 0.2s;
        }
        .hamburger:hover { background: rgba(124,58,237,0.06); }
        .hamburger span {
            display: block; width: 22px; height: 2.5px; border-radius: 2px;
            background: #1a1a2e; transition: all 0.3s;
        }
        .hamburger.active span:nth-child(1) { transform: translateY(6.5px) rotate(45deg); }
        .hamburger.active span:nth-child(2) { opacity: 0; }
        .hamburger.active span:nth-child(3) { transform: translateY(-6.5px) rotate(-45deg); }

        /* Desktop nav links */
        .nav-links { display: flex; gap: 1.75rem; align-items: center; }
        .nav-links a {
            font-size: 0.85rem; font-weight: 500; color: #475569;
            text-decoration: none; transition: color 0.2s;
        }
        .nav-links a:hover { color: #7c3aed; }

        /* Mobile overlay + menu */
        .nav-overlay {
            display: none; position: fixed; inset: 0; z-index: 90;
            background: rgba(0,0,0,0.3); opacity: 0; transition: opacity 0.3s;
        }
        .nav-overlay.open { display: block; opacity: 1; }
        .mobile-menu {
            position: fixed; top: 0; left: 0; bottom: 0; z-index: 95;
            width: 270px; background: #fff;
            transform: translateX(-100%); transition: transform 0.3s ease;
            padding: 5rem 2rem 2rem;
            box-shadow: 4px 0 24px rgba(0,0,0,0.08);
        }
        .mobile-menu.open { transform: translateX(0); }
        .mobile-menu a {
            display: block; font-size: 1rem; font-weight: 600; color: #1a1a2e;
            text-decoration: none; padding: 0.75rem 0;
            border-bottom: 1px solid #f1f5f9; transition: color 0.2s;
        }
        .mobile-menu a:hover { color: #7c3aed; }

        .hero {
            padding: clamp(3rem, 6vw, 5.5rem) 0 2.5rem; text-align: center;
        }
        .hero h1 {
            font-size: clamp(1.8rem, 5vw, 3.5rem);
            max-width: 780px; margin: 0 auto 1rem;
        }
        .hero h1 span { color: #7c3aed; }
        .hero .sub {
            font-size: clamp(0.95rem, 2vw, 1.1rem); color: #475569;
            max-width: 680px; margin: 0 auto 2rem; line-height: 1.7;
            padding: 0 0.5rem;
        }
        .hero-stats {
            display: flex; justify-content: center; gap: clamp(1.5rem, 4vw, 2.5rem);
            flex-wrap: wrap; margin-top: 2.5rem; padding-top: 2rem;
            border-top: 1px solid #e2e8f0;
        }
        .hero-stats .stat { text-align: center; min-width: 60px; }
        .hero-stats .num { font-size: clamp(1.2rem, 3vw, 1.5rem); font-weight: 800; color: #7c3aed; }
        .hero-stats .label { font-size: 0.8rem; color: #64748b; margin-top: 0.15rem; }
        section { scroll-margin-top: 4.5rem; }

        .vp-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(min(260px,100%), 1fr));
            gap: 1.25rem; padding: 0.5rem 0 2.5rem;
        }
        .vp-card {
            background: #fff; border: 1px solid #e2e8f0; border-radius: 16px;
            padding: 1.5rem; transition: all 0.25s;
        }
        .vp-card:hover {
            border-color: #c4b5fd; box-shadow: 0 8px 30px rgba(124,58,237,0.08);
            transform: translateY(-2px);
        }
        .vp-card .icon {
            width: 44px; height: 44px; border-radius: 12px;
            background: linear-gradient(135deg, rgba(124,58,237,0.1), rgba(124,58,237,0.04));
            display: flex; align-items: center; justify-content: center; margin-bottom: 0.75rem;
            color: #7c3aed;
        }
        .vp-card h3 { font-size: 1.05rem; margin-bottom: 0.35rem; }
        .vp-card p { font-size: 0.9rem; color: #64748b; line-height: 1.6; }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(min(300px,100%), 1fr));
            gap: 0.75rem; padding: 0 0 3rem;
        }
        .feature-card {
            display: flex; gap: 0.9rem;
            background: #fff; border: 1px solid #e2e8f0; border-radius: 12px;
            padding: 1.25rem; transition: all 0.25s;
        }
        .feature-card:hover { border-color: #c4b5fd; }
        .feature-card .icon {
            flex-shrink: 0; width: 40px; height: 40px; border-radius: 10px;
            background: rgba(124,58,237,0.08);
            display: flex; align-items: center; justify-content: center;
            color: #7c3aed;
        }
        .feature-card h4 { font-size: 0.95rem; margin-bottom: 0.2rem; }
        .feature-card p { font-size: 0.85rem; color: #64748b; line-height: 1.55; }

        .countries-section { padding: 1.5rem 0 3rem; }
        .country-chips {
            display: flex; justify-content: center; flex-wrap: wrap; gap: 0.65rem;
            margin-bottom: 0.75rem;
        }
        .chip {
            width: 52px; height: 52px; border-radius: 50%;
            background: #fff; border: 1px solid #e2e8f0;
            display: flex; align-items: center; justify-content: center;
            transition: all 0.25s; box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        .chip .fi { font-size: 1.4rem; display: block; line-height: 1; }
        .chip:hover {
            border-color: #c4b5fd; transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(124,58,237,0.1);
        }
        .country-names {
            text-align: center; font-size: 0.9rem; color: #334155;
            margin-bottom: 2rem; font-weight: 500; padding: 0 0.5rem;
        }
        .fiscal-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem; max-width: 680px; margin: 0 auto;
        }
        .fiscal-item {
            background: #fff; border: 1px solid #e2e8f0; border-radius: 12px;
            padding: 1.25rem; text-align: left; transition: all 0.25s;
            display: flex; gap: 0.85rem; align-items: flex-start;
        }
        .fiscal-item:hover { border-color: #c4b5fd; box-shadow: 0 4px 16px rgba(124,58,237,0.06); }
        .fiscal-item .fi-icon {
            flex-shrink: 0; width: 36px; height: 36px; border-radius: 9px;
            background: linear-gradient(135deg, rgba(124,58,237,0.1), rgba(124,58,237,0.04));
            display: flex; align-items: center; justify-content: center; color: #7c3aed;
        }
        .fiscal-item .fi-label {
            font-size: 0.65rem; font-weight: 600; text-transform: uppercase;
            letter-spacing: 0.06em; color: #7c3aed; margin-bottom: 0.15rem;
        }
        .fiscal-item .fi-value { font-size: 0.85rem; color: #334155; font-weight: 500; }
        .fiscal-item .fi-sub { font-size: 0.75rem; color: #94a3b8; margin-top: 0.1rem; }

        .plans-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(min(210px,100%), 1fr));
            gap: 1rem; padding: 0 0 3rem;
        }
        .plan-card {
            background: #fff; border: 1px solid #e2e8f0; border-radius: 14px;
            padding: 1.5rem; text-align: center; transition: all 0.25s;
        }
        .plan-card:hover { border-color: #c4b5fd; }
        .plan-card.highlight {
            border-color: #7c3aed; box-shadow: 0 8px 32px rgba(124,58,237,0.12);
        }
        .plan-card .plan-name {
            font-size: 0.75rem; font-weight: 600; color: #7c3aed;
            text-transform: uppercase; letter-spacing: 0.05em;
        }
        .plan-card .plan-price { font-size: 1.75rem; font-weight: 800; margin: 0.4rem 0 0.2rem; }
        .plan-card .plan-price span { font-size: 0.85rem; font-weight: 400; color: #94a3b8; }
        .plan-card .plan-desc { font-size: 0.8rem; color: #64748b; margin-bottom: 1rem; }
        .plan-card ul { list-style: none; font-size: 0.8rem; color: #475569; line-height: 2; }
        .plan-card ul li::before { content: '✓ '; color: #7c3aed; font-weight: 700; }

        footer {
            border-top: 1px solid #e2e8f0; padding: 1.5rem 0;
            font-size: 0.75rem; color: #94a3b8; text-align: center;
        }
        footer a { color: #64748b; text-decoration: none; }
        footer a:hover { color: #7c3aed; }

        /* Responsive */
        @media (max-width: 768px) {
            .hamburger { display: flex; }
            .nav-links { display: none; }
            .fiscal-grid { grid-template-columns: 1fr; max-width: 440px; }
        }
        @media (max-width: 480px) {
            .container { padding: 0 1rem; }
            .vp-grid { gap: 1rem; }
            .features-grid { gap: 0.65rem; }
            .feature-card { padding: 1rem; }
            .chip { width: 44px; height: 44px; }
            .chip .fi { font-size: 1.2rem; }
            .country-names { font-size: 0.8rem; }
            .fiscal-item { padding: 1rem; }
            .plan-card { padding: 1.25rem; }
            .hero-stats { gap: 1rem; }
            h2 + p.section-sub { font-size: 0.95rem; margin-bottom: 2rem; }
        }
        @media (min-width: 769px) {
            .mobile-menu, .nav-overlay { display: none !important; }
        }
    </style>
</head>
<body>

    <!-- Mobile overlay -->
    <div class="nav-overlay" id="navOverlay" onclick="closeMenu()"></div>

    <!-- Mobile menu -->
    <div class="mobile-menu" id="mobileMenu">
        <a href="#valor" onclick="closeMenu()">Valor</a>
        <a href="#features" onclick="closeMenu()">Funcionalidades</a>
        <a href="#paises" onclick="closeMenu()">Países</a>
        <a href="#planes" onclick="closeMenu()">Planes</a>
    </div>

    <nav>
        <div class="nav-inner">
            <a href="#" class="logo">Tienda<span>POS</span></a>
            <div class="nav-links">
                <a href="#valor">Valor</a>
                <a href="#features">Funcionalidades</a>
                <a href="#paises">Países</a>
                <a href="#planes">Planes</a>
            </div>
            <button class="hamburger" id="hamburgerBtn" aria-label="Menú" onclick="toggleMenu()">
                <span></span><span></span><span></span>
            </button>
        </div>
    </nav>

    <script>
        function toggleMenu() {
            document.getElementById('hamburgerBtn').classList.toggle('active');
            document.getElementById('mobileMenu').classList.toggle('open');
            document.getElementById('navOverlay').classList.toggle('open');
            document.body.style.overflow = document.body.style.overflow === 'hidden' ? '' : 'hidden';
        }
        function closeMenu() {
            document.getElementById('hamburgerBtn').classList.remove('active');
            document.getElementById('mobileMenu').classList.remove('open');
            document.getElementById('navOverlay').classList.remove('open');
            document.body.style.overflow = '';
        }
    </script>

    <div class="container">

        <section class="hero">
            <h1>Tu negocio merece un POS <span>sin fronteras</span></h1>
            <p class="sub">
                Multi-tenant, multimoneda y preparado para los regímenes fiscales de 9 países.
                Gestiona ventas, inventario, facturación y créditos desde un solo sistema.
            </p>
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
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                </div>
                <h3>Multi-tenant nativo</h3>
                <p>Una sola instancia, múltiples tiendas completamente aisladas. Ideal para cadenas, franquicias o negocios independientes.</p>
            </div>
            <div class="vp-card">
                <div class="icon">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                </div>
                <h3>Multimoneda real</h3>
                <p>USD, VES, COP, MXN, ARS y más. Tasas de cambio con historial, pagos mixtos y cálculo de IGTF para Venezuela.</p>
            </div>
            <div class="vp-card">
                <div class="icon">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                </div>
                <h3>Facturación regional</h3>
                <p>RIF, NIT, RFC, RUC, CUIT. IVA/IGV configurable por país. Facturas, cotizaciones, notas y devoluciones.</p>
            </div>
            <div class="vp-card">
                <div class="icon">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
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
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                </div>
                <div>
                    <h4>POS + Caja</h4>
                    <p>Pantalla de venta rápida. Sesiones de caja con apertura y cierre. Pagos mixtos y descuentos.</p>
                </div>
            </div>
            <div class="feature-card">
                <div class="icon">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                </div>
                <div>
                    <h4>Facturación</h4>
                    <p>Facturas, cotizaciones, notas de débito y devoluciones. Numeración correlativa por tipo.</p>
                </div>
            </div>
            <div class="feature-card">
                <div class="icon">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                </div>
                <div>
                    <h4>Inventario</h4>
                    <p>Stock en tiempo real. Múltiples almacenes, lotes con FEFO, traslados y alertas de stock bajo.</p>
                </div>
            </div>
            <div class="feature-card">
                <div class="icon">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                </div>
                <div>
                    <h4>Multimoneda</h4>
                    <p>USD, VES, COP, MXN, ARS, PEN, CLP, BOB, UYU. Tasas de cambio con historial e IGTF.</p>
                </div>
            </div>
            <div class="feature-card">
                <div class="icon">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <div>
                    <h4>Roles y permisos</h4>
                    <p>Administrador, supervisor y cajero. 69 permisos granulares para cada acción del sistema.</p>
                </div>
            </div>
            <div class="feature-card">
                <div class="icon">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                </div>
                <div>
                    <h4>Créditos</h4>
                    <p>Cuentas por cobrar, abonos y facturas a crédito con bloqueo para evitar sobreventa.</p>
                </div>
            </div>
            <div class="feature-card">
                <div class="icon">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21.21 15.89A10 10 0 1 1 8 2.83"/><path d="M22 12A10 10 0 0 0 12 2v10z"/></svg>
                </div>
                <div>
                    <h4>Reportes</h4>
                    <p>Ventas por período, rentabilidad, inventario y cuentas por cobrar. KPIs en tiempo real.</p>
                </div>
            </div>
            <div class="feature-card">
                <div class="icon">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="4 7 4 4 20 4 20 7"/><line x1="9" y1="20" x2="15" y2="20"/><line x1="12" y1="4" x2="12" y2="20"/></svg>
                </div>
                <div>
                    <h4>Suscripciones</h4>
                    <p>Trial, Básico, Pro, Premium. Límites por productos, usuarios, almacenes y cajas.</p>
                </div>
            </div>
            <div class="feature-card">
                <div class="icon">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </div>
                <div>
                    <h4>Onboarding</h4>
                    <p>Wizard de 4 pasos: cuenta, datos fiscales, configuración y primer producto.</p>
                </div>
            </div>
        </section>

        <h2 id="paises">Cobertura regional</h2>
        <p class="section-sub">Regímenes fiscales, monedas y métodos de pago nativos para cada país.</p>

        <section class="countries-section">
            <div class="country-chips">
                <div class="chip"><span class="fi fi-ve"></span></div>
                <div class="chip"><span class="fi fi-co"></span></div>
                <div class="chip"><span class="fi fi-mx"></span></div>
                <div class="chip"><span class="fi fi-ec"></span></div>
                <div class="chip"><span class="fi fi-ar"></span></div>
                <div class="chip"><span class="fi fi-pe"></span></div>
                <div class="chip"><span class="fi fi-cl"></span></div>
                <div class="chip"><span class="fi fi-bo"></span></div>
                <div class="chip"><span class="fi fi-uy"></span></div>
            </div>
            <div class="country-names">
                Venezuela &middot; Colombia &middot; México &middot; Ecuador &middot; Argentina
                &middot; Perú &middot; Chile &middot; Bolivia &middot; Uruguay
            </div>
            <div class="fiscal-grid">
                <div class="fiscal-item">
                    <div class="fi-icon">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/></svg>
                    </div>
                    <div>
                        <div class="fi-label">Identificación fiscal</div>
                        <div class="fi-value">RIF, NIT, RFC, RUC, CUIT</div>
                        <div class="fi-sub">Según el país</div>
                    </div>
                </div>
                <div class="fiscal-item">
                    <div class="fi-icon">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                    </div>
                    <div>
                        <div class="fi-label">Impuestos</div>
                        <div class="fi-value">IVA / IGV por país</div>
                        <div class="fi-sub">Tasas locales configurables</div>
                    </div>
                </div>
                <div class="fiscal-item">
                    <div class="fi-icon">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                    </div>
                    <div>
                        <div class="fi-label">Monedas</div>
                        <div class="fi-value">USD, VES, COP, MXN, ARS, PEN, CLP, BOB, UYU</div>
                        <div class="fi-sub">Multimoneda con tasas históricas</div>
                    </div>
                </div>
                <div class="fiscal-item">
                    <div class="fi-icon">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    </div>
                    <div>
                        <div class="fi-label">Estatus fiscal</div>
                        <div class="fi-value">Datos formales</div>
                        <div class="fi-sub">Sin certificación fiscal</div>
                    </div>
                </div>
            </div>
        </section>

        <h2 id="planes">Planes</h2>
        <p class="section-sub">Desde emprender hasta escalar. Elige el plan que mejor se ajuste a tu operación.</p>

        <section class="plans-grid">
            <div class="plan-card">
                <div class="plan-name">Trial</div>
                <div class="plan-price">$0 <span>/mes</span></div>
                <div class="plan-desc">14 días gratis</div>
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
                <div class="plan-desc">Para crecer</div>
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
                <div class="plan-desc">Lo más popular</div>
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
                <div class="plan-desc">Sin límites</div>
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