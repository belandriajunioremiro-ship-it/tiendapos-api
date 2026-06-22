import SectionHeader from './SectionHeader';

const plans = [
  {
    name: 'Trial',
    price: '$0',
    period: '',
    popular: false,
    highlight: false,
    badge: '14 días gratis',
    icon: (
      <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
        <path strokeLinecap="round" strokeLinejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
    ),
    limits: ['50 productos', '3 usuarios', '1 almacén', '1 caja'],
    features: ['Facturación básica', 'Reportes simples', 'Soporte email', 'Multi-país'],
    notIncluded: ['API REST', 'Multimoneda real', 'Roles y permisos', 'Créditos y cobranzas'],
  },
  {
    name: 'Básico',
    price: '$19',
    period: '/mes',
    popular: false,
    highlight: false,
    badge: null,
    icon: (
      <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
        <path strokeLinecap="round" strokeLinejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5" />
      </svg>
    ),
    limits: ['500 productos', '5 usuarios', '2 almacenes', '2 cajas'],
    features: ['Facturación avanzada', 'Inventario completo', 'Reportes detallados', 'Soporte prioritario', 'API REST'],
    notIncluded: ['Multimoneda real', 'Roles y permisos', 'Créditos y cobranzas'],
  },
  {
    name: 'Pro',
    price: '$39',
    period: '/mes',
    popular: true,
    highlight: false,
    badge: 'Más popular',
    icon: (
      <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
        <path strokeLinecap="round" strokeLinejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />
      </svg>
    ),
    limits: ['5,000 productos', '10 usuarios', '5 almacenes', '5 cajas'],
    features: ['Todo lo de Básico', 'Multimoneda real', 'Roles y permisos', 'Créditos y cobranzas', 'Suscripciones', 'Soporte 24/7'],
    notIncluded: [],
  },
  {
    name: 'Premium',
    price: '$99',
    period: '/mes',
    popular: false,
    highlight: true,
    badge: null,
    icon: (
      <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
        <path strokeLinecap="round" strokeLinejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.504-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 01-.982-3.172M9.497 14.25a7.454 7.454 0 00.981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 007.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M18.75 4.236c.982.143 1.954.317 2.916.52A6.003 6.003 0 0016.27 9.728M18.75 4.236V4.5c0 2.108-.966 3.99-2.48 5.228m0 0a6.023 6.023 0 01-2.77.896m0 0a6.012 6.012 0 01-2.77-.896" />
      </svg>
    ),
    limits: ['Ilimitado', 'Ilimitado', 'Ilimitado', 'Ilimitado'],
    features: ['Todo lo de Pro', 'Onboarding dedicado', 'SLA garantizado', 'Infraestructura dedicada', 'Capacitación presencial'],
    notIncluded: [],
  },
];

export default function Pricing() {
  return (
    <section id="planes" className="px-4 py-20 md:py-32">
      <div className="mx-auto max-w-7xl">
        <SectionHeader
          badge="Planes flexibles"
          title="Inversión que se adapta a ti"
          subtitle="Todos los planes incluyen 14 días de prueba gratis. Cambia o cancela cuando quieras."
        />

        <div className="grid gap-6 lg:grid-cols-4 md:grid-cols-2">
          {plans.map((p) => (
            <div
              key={p.name}
              className={`relative flex flex-col rounded-2xl border p-6 transition-all hover:-translate-y-0.5 ${
                p.popular
                  ? 'border-violet-300 bg-white shadow-lg shadow-violet-500/10'
                  : p.highlight
                    ? 'border-gray-700 bg-gradient-to-br from-gray-900 to-gray-950 text-white shadow-md'
                    : 'border-gray-200 bg-white shadow-sm'
              }`}
            >
              {/* Corner badge */}
              {p.badge && (
                <span className="absolute -top-px -right-px inline-flex items-center gap-1 rounded-bl-xl rounded-tr-2xl bg-gradient-to-r from-violet-500 to-violet-700 px-3 py-1.5 text-[11px] font-bold text-white shadow-sm">
                  <svg className="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                  </svg>
                  {p.badge}
                </span>
              )}

              {/* Trial badge */}
              {p.price === '$0' && !p.badge && (
                <span className="absolute -top-px -right-px inline-flex items-center gap-1 rounded-bl-xl rounded-tr-2xl bg-emerald-500 px-3 py-1.5 text-[11px] font-bold text-white shadow-sm">
                  <svg className="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
                    <path strokeLinecap="round" strokeLinejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                  </svg>
                  Prueba gratis
                </span>
              )}

              {/* Icon + Name */}
              <div className="mb-4 flex items-center gap-3">
                <div className={`flex h-10 w-10 items-center justify-center rounded-xl ${
                  p.popular
                    ? 'bg-violet-100 text-violet-600'
                    : p.highlight
                      ? 'bg-white/10 text-violet-300'
                      : 'bg-gray-100 text-gray-500'
                }`}>
                  {p.icon}
                </div>
                <div>
                  <h3 className={`text-base font-bold ${p.highlight ? 'text-white' : 'text-gray-900'}`}>{p.name}</h3>
                  <p className={`text-xs ${p.highlight ? 'text-gray-400' : 'text-gray-400'}`}>
                    {p.price === '$0' ? 'Gratis' : `Desde $${p.price}/mes`}
                  </p>
                </div>
              </div>

              {/* Price */}
              <div className="mb-4 border-t pt-4" style={{borderColor: p.highlight ? 'rgba(255,255,255,0.08)' : '#f3f4f6'}}>
                <div className="flex items-baseline gap-0.5">
                  <span className={`text-3xl font-black tracking-tight ${p.highlight ? 'text-white' : 'text-gray-900'}`}>{p.price}</span>
                  {p.period && <span className={`text-sm ${p.highlight ? 'text-gray-400' : 'text-gray-400'}`}>{p.period}</span>}
                  {p.price === '$0' && <span className={`ml-1.5 text-xs ${p.highlight ? 'text-gray-400' : 'text-gray-400'}`}>/ 14 días</span>}
                </div>
              </div>

              {/* Limits */}
              <div className={`mb-4 grid grid-cols-2 gap-1.5 rounded-xl p-3 ${
                p.highlight ? 'bg-white/5' : 'bg-gray-50'
              }`}>
                <div className="text-center">
                  <p className={`text-xs font-bold ${p.highlight ? 'text-white' : 'text-gray-900'}`}>{p.limits[0].split(' ')[0]}</p>
                  <p className={`text-[10px] ${p.highlight ? 'text-gray-400' : 'text-gray-400'}`}>productos</p>
                </div>
                <div className="text-center">
                  <p className={`text-xs font-bold ${p.highlight ? 'text-white' : 'text-gray-900'}`}>{p.limits[1].split(' ')[0]}</p>
                  <p className={`text-[10px] ${p.highlight ? 'text-gray-400' : 'text-gray-400'}`}>usuarios</p>
                </div>
                <div className="text-center">
                  <p className={`text-xs font-bold ${p.highlight ? 'text-white' : 'text-gray-900'}`}>{p.limits[2].split(' ')[0]}</p>
                  <p className={`text-[10px] ${p.highlight ? 'text-gray-400' : 'text-gray-400'}`}>almacenes</p>
                </div>
                <div className="text-center">
                  <p className={`text-xs font-bold ${p.highlight ? 'text-white' : 'text-gray-900'}`}>{p.limits[3].split(' ')[0]}</p>
                  <p className={`text-[10px] ${p.highlight ? 'text-gray-400' : 'text-gray-400'}`}>cajas</p>
                </div>
              </div>

              {/* Features */}
              <ul className="mb-6 flex-1 space-y-2.5">
                {p.features.map((f) => (
                  <li key={f} className="flex items-start gap-3 text-sm">
                    <svg className={`mt-0.5 h-4 w-4 flex-shrink-0 ${p.highlight ? 'text-emerald-400' : 'text-emerald-500'}`} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
                      <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                    <span className={p.highlight ? 'text-gray-300' : 'text-gray-600'}>{f}</span>
                  </li>
                ))}
                {p.notIncluded.map((f) => (
                  <li key={f} className="flex items-start gap-3 text-sm">
                    <svg className={`mt-0.5 h-4 w-4 flex-shrink-0 ${p.highlight ? 'text-gray-600' : 'text-gray-300'}`} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                      <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    <span className={p.highlight ? 'text-gray-500' : 'text-gray-400'}>{f}</span>
                  </li>
                ))}
              </ul>

              {/* CTA */}
              <a
                href={`https://wa.me/584247253544?text=Hola%2C%20quiero%20el%20plan%20${encodeURIComponent(p.name)}%20de%20TiendaPOS`}
                target="_blank"
                rel="noopener"
                className={`inline-flex items-center justify-center gap-2 rounded-xl py-3 text-sm font-semibold no-underline transition-all ${
                  p.popular
                    ? 'bg-gradient-to-r from-violet-500 to-violet-700 text-white shadow-sm hover:shadow-md'
                    : p.highlight
                      ? 'border border-white/20 bg-white/10 text-white hover:bg-white/20'
                      : 'border-2 border-violet-200 text-violet-700 hover:border-violet-500 hover:bg-violet-50'
                }`}
              >
                {p.price === '$0' ? (
                  <>Comenzar prueba <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}><path strokeLinecap="round" strokeLinejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg></>
                ) : p.price === 'A medida' ? (
                  <>Contáctanos <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}><path strokeLinecap="round" strokeLinejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg></>
                ) : (
                  <>Elegir plan <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}><path strokeLinecap="round" strokeLinejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg></>
                )}
              </a>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}
