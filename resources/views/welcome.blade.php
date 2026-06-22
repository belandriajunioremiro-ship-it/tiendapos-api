<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TiendaPOS API</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800" rel="stylesheet" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: #000;
            background: radial-gradient(ellipse at 50% 0%, rgba(180, 0, 0, 0.12) 0%, transparent 60%),
                        radial-gradient(ellipse at 80% 50%, rgba(220, 20, 20, 0.06) 0%, transparent 50%),
                        radial-gradient(ellipse at 20% 80%, rgba(140, 0, 0, 0.04) 0%, transparent 40%),
                        #000;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #e2e8f0;
            padding: 1rem;
        }
        .container {
            max-width: 800px;
            width: 100%;
            text-align: center;
        }
        .badge {
            display: inline-block;
            padding: 0.35rem 1rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            background: rgba(34, 197, 94, 0.12);
            color: #4ade80;
            border: 1px solid rgba(34, 197, 94, 0.25);
            margin-bottom: 1.5rem;
        }
        .badge::before {
            content: '';
            display: inline-block;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #4ade80;
            margin-right: 0.5rem;
            vertical-align: middle;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }
        h1 {
            font-size: clamp(2rem, 6vw, 3.75rem);
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 0.75rem;
            background: linear-gradient(to right, #f8fafc, #94a3b8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        h1 span {
            background: linear-gradient(to right, #ef4444, #dc2626);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .subtitle {
            font-size: clamp(1rem, 2vw, 1.25rem);
            color: #64748b;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2.5rem;
            text-align: left;
        }
        .feature-item {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 10px;
            padding: 1rem 1.25rem;
            transition: border-color 0.2s;
        }
        .feature-item:hover {
            border-color: rgba(239, 68, 68, 0.2);
        }
        .feature-item .label {
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #ef4444;
            margin-bottom: 0.3rem;
        }
        .feature-item .desc {
            font-size: 0.85rem;
            color: #94a3b8;
            line-height: 1.5;
        }
        .disclaimer {
            font-size: 0.8rem;
            color: #475569;
            margin-bottom: 2rem;
            padding: 0.75rem 1rem;
            border: 1px solid rgba(255, 255, 255, 0.04);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.01);
        }
        .disclaimer strong {
            color: #64748b;
        }
        .url-card {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1.5rem;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            font-size: 0.9rem;
            color: #e2e8f0;
            backdrop-filter: blur(8px);
            transition: border-color 0.2s;
        }
        .url-card:hover {
            border-color: rgba(239, 68, 68, 0.3);
        }
        .url-card svg {
            flex-shrink: 0;
        }
        .url-card code {
            font-family: 'Inter', monospace;
            color: #f87171;
        }
        .url-card .copy-btn {
            background: none;
            border: none;
            color: #64748b;
            cursor: pointer;
            padding: 0.25rem;
            display: flex;
            align-items: center;
            transition: color 0.2s;
        }
        .url-card .copy-btn:hover {
            color: #e2e8f0;
        }
        .footer {
            margin-top: 3rem;
            font-size: 0.8rem;
            color: #475569;
        }
        .footer a {
            color: #64748b;
            text-decoration: none;
        }
        .footer a:hover {
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="badge">Operativo</div>
        <h1>Tienda<span>POS</span></h1>
        <p class="subtitle">
            Sistema de punto de venta multi-tenant con soporte para facturación electrónica,<br>
            múltiples regímenes fiscales y control de inventario en tiempo real.
        </p>

        <div class="features">
            <div class="feature-item">
                <div class="label">Multimoneda</div>
                <div class="desc">USD, VES, COP, MXN, ARS — tasas de cambio actualizables</div>
            </div>
            <div class="feature-item">
                <div class="label">Regímenes Fiscales</div>
                <div class="desc">RIF, NIT, RFC, RUC, CUIT — IVA/IGV por país (VE, CO, MX, EC, AR, PE, CL, BO, UY)</div>
            </div>
            <div class="feature-item">
                <div class="label">Documentos</div>
                <div class="desc">Facturas, cotizaciones, créditos, notas de débito, devoluciones</div>
            </div>
            <div class="feature-item">
                <div class="label">Inventario</div>
                <div class="desc">Stock en tiempo real, múltiples almacenes, lotes con FEFO, traslados</div>
            </div>
            <div class="feature-item">
                <div class="label">POS + Caja</div>
                <div class="desc">Pantalla de venta rápida, sesiones de caja, pagos mixtos, descuentos</div>
            </div>
            <div class="feature-item">
                <div class="label">Suscripciones</div>
                <div class="desc">Planes Trial, Básico, Pro, Premium con límites configurables</div>
            </div>
        </div>

        <div class="disclaimer">
            <strong>Nota:</strong> Este sistema gestiona datos formales de facturación (RIF/NIT/RFC, razones sociales, impuestos) para cumplir con requisitos contables y fiscales de cada país, pero <strong>no es un sistema fiscal certificado</strong>. No realiza declaraciones de impuestos ni retenciones legales.
        </div>

        <div class="url-card">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#f87171" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
            </svg>
            <code>https://tiendapos-api.onrender.com</code>
            <button class="copy-btn" onclick="navigator.clipboard.writeText('https://tiendapos-api.onrender.com')" title="Copiar URL">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                </svg>
            </button>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} TiendaPOS &mdash; Backend en <a href="https://render.com" target="_blank">Render</a> &middot; Base de datos en <a href="https://neon.tech" target="_blank">Neon</a>
        </div>
    </div>
</body>
</html>