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
            background: rgba(0,0,0,0.4); opacity: 0; transition: opacity 0.35s;
        }
        .nav-overlay.open { display: block; opacity: 1; }
        .mobile-menu {
            position: fixed; inset: 0; z-index: 95;
            background: rgba(255,255,255,0.98); backdrop-filter: blur(16px);
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            gap: 0.5rem;
            opacity: 0; visibility: hidden; transition: all 0.35s ease;
        }
        .mobile-menu.open { opacity: 1; visibility: visible; }
        .mobile-menu .close-btn {
            position: absolute; top: 1rem; right: 1.25rem;
            background: none; border: none; cursor: pointer;
            padding: 0.5rem; border-radius: 10px; color: #94a3b8;
            transition: all 0.2s;
        }
        .mobile-menu .close-btn:hover { background: rgba(124,58,237,0.06); color: #7c3aed; }
        .mobile-menu a {
            font-size: 1.2rem; font-weight: 600; color: #1a1a2e;
            text-decoration: none; padding: 0.8rem 2rem; border-radius: 12px;
            transition: all 0.2s; letter-spacing: -0.01em;
        }
        .mobile-menu a:hover { background: rgba(124,58,237,0.06); color: #7c3aed; }
        .mobile-menu .wa-link {
            display: inline-flex; align-items: center; gap: 0.5rem;
            margin-top: 1.5rem; background: #25d366; color: #fff !important;
            padding: 0.8rem 1.5rem; border-radius: 50px; font-size: 0.95rem;
        }
        .mobile-menu .wa-link:hover { background: #1ebe5d; transform: scale(1.03); }

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
        .hero-disclaimer {
            max-width: 680px; margin: 1.5rem auto 0; padding: 0.75rem 1rem;
            font-size: 0.75rem; color: #94a3b8; line-height: 1.6;
            background: rgba(255,255,255,0.5); border: 1px solid #e2e8f0;
            border-radius: 10px; text-align: center;
        }
        .hero-disclaimer strong { color: #64748b; }
        section { scroll-margin-top: 4.5rem; }

        /* Modals */
        .modal-overlay {
            display: none; position: fixed; inset: 0; z-index: 200;
            background: rgba(0,0,0,0.4); backdrop-filter: blur(4px);
            align-items: center; justify-content: center; padding: 1.5rem;
        }
        .modal-overlay.open { display: flex; }
        .modal-box {
            background: #fff; border-radius: 20px; max-width: 600px; width: 100%;
            max-height: 80vh; overflow-y: auto; padding: 2rem;
            box-shadow: 0 24px 64px rgba(0,0,0,0.12);
            animation: modalIn 0.25s ease;
        }
        @keyframes modalIn { from { opacity: 0; transform: scale(0.95) translateY(10px); } to { opacity: 1; transform: scale(1) translateY(0); } }
        .modal-box .modal-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 1.25rem;
        }
        .modal-box .modal-header h3 { font-size: 1.15rem; }
        .modal-box .modal-header button {
            background: none; border: none; cursor: pointer;
            padding: 0.4rem; border-radius: 8px; color: #94a3b8; transition: all 0.2s;
        }
        .modal-box .modal-header button:hover { background: rgba(124,58,237,0.06); color: #7c3aed; }
        .modal-box .modal-body { font-size: 0.88rem; color: #475569; line-height: 1.7; }
        .modal-box .modal-body p { margin-bottom: 0.75rem; }

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
            grid-template-columns: repeat(auto-fit, minmax(min(230px,100%), 1fr));
            gap: 1.25rem; padding: 0 0 3rem;
            align-items: start;
        }
        .plan-card {
            background: #fff; border: 1px solid #e2e8f0; border-radius: 20px;
            padding: 2rem 1.5rem; text-align: center; transition: all 0.3s;
            position: relative; overflow: hidden;
        }
        .plan-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.06);
        }
        .plan-card.highlight {
            border-color: #7c3aed;
            box-shadow: 0 8px 32px rgba(124,58,237,0.15);
            transform: scale(1.03);
        }
        .plan-card.highlight:hover {
            transform: scale(1.03) translateY(-4px);
        }
        .plan-card .plan-badge {
            position: absolute; top: 12px; right: -28px;
            background: #7c3aed; color: #fff;
            font-size: 0.6rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 0.2rem 2.2rem;
            transform: rotate(45deg);
        }
        .plan-card .plan-icon {
            width: 48px; height: 48px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1rem;
        }
        .plan-card .plan-icon.trial { background: rgba(148,163,184,0.1); color: #64748b; }
        .plan-card .plan-icon.basico { background: rgba(99,102,241,0.1); color: #6366f1; }
        .plan-card .plan-icon.pro { background: rgba(124,58,237,0.12); color: #7c3aed; }
        .plan-card .plan-icon.premium { background: linear-gradient(135deg, rgba(245,158,11,0.15), rgba(245,158,11,0.05)); color: #f59e0b; }
        .plan-card .plan-name {
            font-size: 0.8rem; font-weight: 700; color: #7c3aed;
            text-transform: uppercase; letter-spacing: 0.06em;
        }
        .plan-card .plan-price {
            font-size: 2rem; font-weight: 800; margin: 0.5rem 0 0.15rem;
            color: #1a1a2e;
        }
        .plan-card .plan-price span { font-size: 0.85rem; font-weight: 400; color: #94a3b8; }
        .plan-card .plan-desc {
            font-size: 0.8rem; color: #64748b; margin-bottom: 1.25rem;
            padding-bottom: 1rem; border-bottom: 1px solid #f1f5f9;
        }
        .plan-card ul {
            list-style: none; font-size: 0.85rem; color: #475569;
            line-height: 2.2; text-align: left; max-width: 180px; margin: 0 auto;
        }
        .plan-card ul li { display: flex; align-items: center; gap: 0.4rem; }
        .plan-card ul li .check {
            flex-shrink: 0; width: 18px; height: 18px; border-radius: 50%;
            display: inline-flex; align-items: center; justify-content: center;
        }
        .plan-card ul li .check.trial { background: rgba(148,163,184,0.15); color: #64748b; }
        .plan-card ul li .check.basico { background: rgba(99,102,241,0.12); color: #6366f1; }
        .plan-card ul li .check.pro { background: rgba(124,58,237,0.12); color: #7c3aed; }
        .plan-card ul li .check.premium { background: rgba(245,158,11,0.12); color: #f59e0b; }
        .plan-card.premium-bg {
            background: linear-gradient(145deg, #1e1b2e, #2d1f3e);
            border-color: rgba(124,58,237,0.2);
        }
        .plan-card.premium-bg .plan-name { color: #a78bfa; }
        .plan-card.premium-bg .plan-price { color: #f8fafc; }
        .plan-card.premium-bg .plan-desc { color: #94a3b8; border-bottom-color: rgba(255,255,255,0.06); }
        .plan-card.premium-bg ul li { color: #cbd5e1; }
        .plan-card.premium-bg .plan-icon.premium { background: linear-gradient(135deg, rgba(167,139,250,0.15), rgba(167,139,250,0.05)); color: #a78bfa; }
        .plan-card.premium-bg ul li .check.premium { background: rgba(167,139,250,0.15); color: #a78bfa; }

        .wa-float {
            position: fixed; bottom: 1.5rem; right: 1.5rem; z-index: 60;
            width: 56px; height: 56px; border-radius: 50%;
            background: #25d366; color: #fff;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 20px rgba(37,211,102,0.35);
            transition: all 0.25s; text-decoration: none;
        }
        .wa-float:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 28px rgba(37,211,102,0.45);
        }
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
        @media (max-width: 380px) {
            .mobile-menu a { font-size: 1rem; padding: 0.7rem 1.5rem; }
        }
    </style>
</head>
<body>

    <!-- Mobile overlay -->
    <div class="nav-overlay" id="navOverlay" onclick="closeMenu()"></div>

    <!-- Mobile menu -->
    <div class="mobile-menu" id="mobileMenu">
        <button class="close-btn" onclick="closeMenu()" aria-label="Cerrar">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
        <a href="#por-que" onclick="closeMenu()">Por qué TiendaPOS</a>
        <a href="#caracteristicas" onclick="closeMenu()">Características</a>
        <a href="#paises" onclick="closeMenu()">Países</a>
        <a href="#planes" onclick="closeMenu()">Planes</a>
        <a href="https://wa.me/584247253544?text=Hola%2C%20quiero%20informaci%C3%B3n%20sobre%20TiendaPOS" target="_blank" class="wa-link" onclick="closeMenu()">
            <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
            Contáctanos
        </a>
    </div>

    <nav>
        <div class="nav-inner">
            <a href="#" class="logo">Tienda<span>POS</span></a>
            <div class="nav-links">
                <a href="#por-que">Por qué TiendaPOS</a>
                <a href="#caracteristicas">Características</a>
                <a href="#paises">Países</a>
                <a href="#planes">Planes</a>
                <a href="https://wa.me/584247253544?text=Hola%2C%20quiero%20informaci%C3%B3n%20sobre%20TiendaPOS" target="_blank" style="display:inline-flex;align-items:center;gap:0.4rem;background:#25d366;color:#fff!important;padding:0.45rem 1rem;border-radius:50px;font-size:0.8rem;font-weight:600">
                    <svg width="15" height="15" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    Contáctanos
                </a>
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
            <div class="hero-disclaimer">
                Este sistema gestiona datos formales de facturación — RIF/NIT/RFC/RUC/CUIT, razones sociales e impuestos — para cumplir con requisitos contables y fiscales de cada país. <strong>No es un sistema fiscal certificado</strong>. No realiza declaraciones de impuestos ni retenciones legales. Cada comercio es responsable de su propio cumplimiento fiscal local.
            </div>
        </section>

        <h2 id="por-que">Por qué TiendaPOS</h2>
        <p class="section-sub">Construido para negocios que necesitan un sistema robusto, flexible y adaptado a la realidad latinoamericana.</p>

        <section class="vp-grid">
            <div class="vp-card">
                <div class="icon">
                    <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                </div>
                <h3>Multi-tenant nativo</h3>
                <p>Una sola instancia, múltiples tiendas aisladas. Ideal para cadenas, franquicias o negocios independientes.</p>
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

        <h2 id="caracteristicas">Todo lo que necesitas para operar</h2>
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

        <h2 id="paises">Países que soporta TiendaPOS</h2>
        <p class="section-sub">Regímenes fiscales, monedas y métodos de pago nativos para cada país de la región.</p>

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
                <div class="plan-icon trial">
                    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                </div>
                <div class="plan-name">Trial</div>
                <div class="plan-price">$0 <span>/mes</span></div>
                <div class="plan-desc">14 días gratis. Sin compromiso.</div>
                <ul>
                    <li><span class="check trial"><svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="2 5 4 7 8 3"/></svg></span> 50 productos</li>
                    <li><span class="check trial"><svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="2 5 4 7 8 3"/></svg></span> 2 usuarios</li>
                    <li><span class="check trial"><svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="2 5 4 7 8 3"/></svg></span> 1 almacén</li>
                    <li><span class="check trial"><svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="2 5 4 7 8 3"/></svg></span> 1 caja</li>
                </ul>
            </div>
            <div class="plan-card">
                <div class="plan-icon basico">
                    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                </div>
                <div class="plan-name">Básico</div>
                <div class="plan-price">$19 <span>/mes</span></div>
                <div class="plan-desc">Para negocios en crecimiento.</div>
                <ul>
                    <li><span class="check basico"><svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="2 5 4 7 8 3"/></svg></span> 200 productos</li>
                    <li><span class="check basico"><svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="2 5 4 7 8 3"/></svg></span> 5 usuarios</li>
                    <li><span class="check basico"><svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="2 5 4 7 8 3"/></svg></span> 2 almacenes</li>
                    <li><span class="check basico"><svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="2 5 4 7 8 3"/></svg></span> 2 cajas</li>
                </ul>
            </div>
            <div class="plan-card highlight">
                <div class="plan-badge">Popular</div>
                <div class="plan-icon pro">
                    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                </div>
                <div class="plan-name">Pro</div>
                <div class="plan-price">$49 <span>/mes</span></div>
                <div class="plan-desc">La opción más balanceada.</div>
                <ul>
                    <li><span class="check pro"><svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="2 5 4 7 8 3"/></svg></span> 1000 productos</li>
                    <li><span class="check pro"><svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="2 5 4 7 8 3"/></svg></span> 15 usuarios</li>
                    <li><span class="check pro"><svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="2 5 4 7 8 3"/></svg></span> 5 almacenes</li>
                    <li><span class="check pro"><svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="2 5 4 7 8 3"/></svg></span> 5 cajas</li>
                </ul>
            </div>
            <div class="plan-card premium-bg">
                <div class="plan-icon premium">
                    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                </div>
                <div class="plan-name">Premium</div>
                <div class="plan-price">$99 <span>/mes</span></div>
                <div class="plan-desc">Sin límites, sin preocupaciones.</div>
                <ul>
                    <li><span class="check premium"><svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="2 5 4 7 8 3"/></svg></span> Productos ilimitados</li>
                    <li><span class="check premium"><svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="2 5 4 7 8 3"/></svg></span> Usuarios ilimitados</li>
                    <li><span class="check premium"><svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="2 5 4 7 8 3"/></svg></span> Almacenes ilimitados</li>
                    <li><span class="check premium"><svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="2 5 4 7 8 3"/></svg></span> Cajas ilimitadas</li>
                </ul>
            </div>
        </section>

        <a href="https://wa.me/584247253544?text=Hola%2C%20quiero%20informaci%C3%B3n%20sobre%20TiendaPOS" target="_blank" class="wa-float" aria-label="WhatsApp">
            <svg width="26" height="26" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
        </a>

        <!-- Modals -->
        <div class="modal-overlay" id="modalPrivacidad" onclick="if(event.target===this)closeModal('modalPrivacidad')">
            <div class="modal-box">
                <div class="modal-header">
                    <h3>Política de Privacidad</h3>
                    <button onclick="closeModal('modalPrivacidad')" aria-label="Cerrar">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                <div class="modal-body">
                    <p>En TiendaPOS, la protección de tus datos es una prioridad. Esta política describe cómo recopilamos, usamos y protegemos la información de nuestros usuarios.</p>
                    <p><strong>Información que recopilamos:</strong> Datos de registro (nombre, correo electrónico, contraseña), información de la tienda (RIF/NIT/RFC, razón social, dirección) y datos transaccionales generados durante el uso del sistema.</p>
                    <p><strong>Uso de la información:</strong> Utilizamos tus datos únicamente para proveer el servicio: procesar ventas, generar facturas, gestionar inventario y mantener tu cuenta activa. No compartimos tu información con terceros sin tu consentimiento explícito.</p>
                    <p><strong>Seguridad:</strong> Implementamos medidas técnicas y organizativas para proteger tus datos contra acceso no autorizado, pérdida o alteración. Las comunicaciones están cifradas vía HTTPS y los tokens de autenticación se almacenan de forma segura.</p>
                    <p><strong>Retención:</strong> Conservamos tus datos mientras tu cuenta esté activa. Al cancelar tu suscripción, puedes solicitar la eliminación completa de tus datos escribiendo a nuestro contacto.</p>
                    <p><strong>Contacto:</strong> Para cualquier consulta sobre privacidad, escríbenos vía WhatsApp.</p>
                </div>
            </div>
        </div>

        <div class="modal-overlay" id="modalTerminos" onclick="if(event.target===this)closeModal('modalTerminos')">
            <div class="modal-box">
                <div class="modal-header">
                    <h3>Términos del Servicio</h3>
                    <button onclick="closeModal('modalTerminos')" aria-label="Cerrar">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Al utilizar TiendaPOS, aceptas los siguientes términos y condiciones. Te recomendamos leerlos detenidamente antes de usar el sistema.</p>
                    <p><strong>Uso del servicio:</strong> TiendaPOS es un sistema de punto de venta diseñado para gestionar operaciones comerciales. El usuario se compromete a usar el sistema de forma ética y conforme a las leyes aplicables en su país.</p>
                    <p><strong>Responsabilidad fiscal:</strong> TiendaPOS facilita la gestión de datos formales de facturación, pero no es un sistema fiscal certificado. El usuario es el único responsable de cumplir con sus obligaciones tributarias y de asegurarse de que el uso del sistema cumple con la normativa local.</p>
                    <p><strong>Disponibilidad:</strong> Nos esforzamos por mantener el servicio operativo 24/7, pero no garantizamos disponibilidad ininterrumpida. El servicio se provee "tal cual" y podrían ocurrir interrupciones programadas o no programadas.</p>
                    <p><strong>Limitación de responsabilidad:</strong> TiendaPOS no se hace responsable por daños directos o indirectos derivados del uso del sistema, incluyendo pero no limitado a pérdida de datos, interrupción del negocio o incumplimientos fiscales.</p>
                    <p><strong>Modificaciones:</strong> Nos reservamos el derecho de modificar estos términos en cualquier momento. Los cambios serán notificados a través del sistema.</p>
                </div>
            </div>
        </div>

        <div class="modal-overlay" id="modalLegal" onclick="if(event.target===this)closeModal('modalLegal')">
            <div class="modal-box">
                <div class="modal-header">
                    <h3>Aviso Legal</h3>
                    <button onclick="closeModal('modalLegal')" aria-label="Cerrar">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                <div class="modal-body">
                    <p><strong>Propiedad intelectual:</strong> TiendaPOS es un software desarrollado de forma independiente. Todos los derechos sobre el código, la marca y la documentación están reservados.</p>
                    <p><strong>Exención de responsabilidad fiscal:</strong> Este sistema no está certificado por ninguna autoridad tributaria. Los datos fiscales ingresados son responsabilidad exclusiva del usuario. Recomendamos consultar con un contador o asesor fiscal para garantizar el cumplimiento de las obligaciones tributarias locales.</p>
                    <p><strong>Protección de datos:</strong> Cumplimos con los estándares básicos de protección de datos. Los datos se almacenan en servidores de terceros (Neon.tech para base de datos, Render para la aplicación) que cuentan con sus propias certificaciones de seguridad.</p>
                    <p><strong>Jurisdicción:</strong> Este aviso legal se rige por las leyes de la República Bolivariana de Venezuela. Cualquier controversia será resuelta ante los tribunales competentes de Caracas.</p>
                </div>
            </div>
        </div>

        <script>
            function openModal(id) { document.getElementById(id).classList.add('open'); document.body.style.overflow = 'hidden'; }
            function closeModal(id) { document.getElementById(id).classList.remove('open'); document.body.style.overflow = ''; }
        </script>

        <footer>
            &copy; {{ date('Y') }} TiendaPOS &mdash; 
            <a href="#" onclick="event.preventDefault();openModal('modalPrivacidad')">Privacidad</a> &middot; 
            <a href="#" onclick="event.preventDefault();openModal('modalTerminos')">Términos</a> &middot; 
            <a href="#" onclick="event.preventDefault();openModal('modalLegal')">Aviso legal</a>
        </footer>

    </div>
</body>
</html>