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
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
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
            background: rgba(34, 197, 94, 0.15);
            color: #4ade80;
            border: 1px solid rgba(34, 197, 94, 0.3);
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
            background: linear-gradient(to right, #38bdf8, #818cf8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .subtitle {
            font-size: clamp(1rem, 2vw, 1.25rem);
            color: #94a3b8;
            margin-bottom: 2.5rem;
            line-height: 1.6;
        }
        .url-card {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1.5rem;
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 12px;
            font-size: 0.95rem;
            color: #e2e8f0;
            margin-bottom: 3rem;
            backdrop-filter: blur(8px);
            transition: border-color 0.2s;
        }
        .url-card:hover {
            border-color: rgba(56, 189, 248, 0.4);
        }
        .url-card svg {
            flex-shrink: 0;
        }
        .url-card code {
            font-family: 'Inter', monospace;
            color: #38bdf8;
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
        .links {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 1rem;
        }
        .links a {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-size: 0.875rem;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s;
            border: 1px solid transparent;
        }
        .links a.primary {
            background: #38bdf8;
            color: #0f172a;
        }
        .links a.primary:hover {
            background: #7dd3fc;
            transform: translateY(-1px);
        }
        .links a.secondary {
            background: rgba(30, 41, 59, 0.6);
            color: #cbd5e1;
            border-color: rgba(148, 163, 184, 0.2);
        }
        .links a.secondary:hover {
            border-color: rgba(148, 163, 184, 0.4);
            background: rgba(30, 41, 59, 0.8);
            transform: translateY(-1px);
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
        <div class="badge">En línea</div>
        <h1>Tienda<span>POS</span> API</h1>
        <p class="subtitle">
            API robusta y escalable para la gestión de puntos de venta.<br>
            Multi-tenant · Multi-país · Tiempo real
        </p>

        <div class="url-card">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#38bdf8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
            </svg>
            <code>https://tiendapos-api.onrender.com</code>
            <button class="copy-btn" onclick="navigator.clipboard.writeText('https://tiendapos-api.onrender.com')" title="Copiar URL">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                </svg>
            </button>
        </div>

        <div class="links">
            <a href="https://github.com/belandriajunioremiro-ship-it/tiendapos-api" target="_blank" class="secondary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/></svg>
                Código fuente
            </a>
            <a href="https://dashboard.render.com/web/srv-d8scgfojs32c73cv3sgg" target="_blank" class="secondary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                Dashboard
            </a>
            <a href="/docs" target="_blank" class="primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                Documentación
            </a>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} TiendaPOS API &mdash; Desplegado en <a href="https://render.com" target="_blank">Render</a> &middot; Base de datos en <a href="https://neon.tech" target="_blank">Neon</a>
        </div>
    </div>
</body>
</html>