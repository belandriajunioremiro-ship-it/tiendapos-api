import SectionHeader from './SectionHeader';

const plans = [
  {
    name: 'Emprendedor',
    price: '$9',
    period: '/mes',
    popular: false,
    premium: false,
    icon: (
      <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
        <path strokeLinecap="round" strokeLinejoin="round" d="M15.59 14.37a6 6 0 01-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 006.16-12.12A14.98 14.98 0 009.631 8.41m5.96 5.96a14.926 14.926 0 01-5.841 2.58m-.119-8.54a6 6 0 00-7.381 5.84h4.8m2.581-5.84a14.927 14.927 0 00-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 01-2.448-2.448 14.9 14.9 0 01.06-.312m-2.24 2.39a4.493 4.493 0 00-1.757 4.306 4.493 4.493 0 004.306-1.758M16.5 9a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
      </svg>
    ),
    features: ['1 usuario', '1 negocio', 'Facturación básica', 'Reportes simples', 'Soporte email'],
    notIncluded: ['API REST', 'Multimoneda', 'Roles y permisos'],
  },
  {
    name: 'Pro',
    price: '$19',
    period: '/mes',
    popular: true,
    premium: false,
    icon: (
      <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
        <path strokeLinecap="round" strokeLinejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />
      </svg>
    ),
    features: ['3 usuarios', '3 negocios', 'Facturación avanzada', 'Inventario completo', 'Reportes detallados', 'Soporte prioritario', 'API REST'],
    notIncluded: ['Multimoneda real', 'Créditos y cobranzas'],
  },
  {
    name: 'Premium',
    price: '$39',
    period: '/mes',
    popular: false,
    premium: true,
    icon: (
      <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
        <path strokeLinecap="round" strokeLinejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.504-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 01-.982-3.172M9.497 14.25a7.454 7.454 0 00.981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 007.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M18.75 4.236c.982.143 1.954.317 2.916.52A6.003 6.003 0 0016.27 9.728M18.75 4.236V4.5c0 2.108-.966 3.99-2.48 5.228m0 0a6.023 6.023 0 01-2.77.896m0 0a6.012 6.012 0 01-2.77-.896" />
      </svg>
    ),
    features: ['10 usuarios', '10 negocios', 'Todo lo de Pro', 'Multimoneda real', 'Roles y permisos', 'Créditos y cobranzas', 'Suscripciones', 'Soporte 24/7'],
    notIncluded: [],
  },
  {
    name: 'Enterprise',
    price: 'A medida',
    period: '',
    popular: false,
    premium: false,
    icon: (
      <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
        <path strokeLinecap="round" strokeLinejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
      </svg>
    ),
    features: ['Usuarios ilimitados', 'Negocios ilimitados', 'Todo lo de Premium', 'Onboarding dedicado', 'SLA garantizado', 'Infraestructura dedicada', 'Soporte 24/7', 'Capacitación presencial'],
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
          subtitle="Elige el plan que mejor se ajuste a tu negocio. Cambia de plan en cualquier momento."
        />

        <div className="grid gap-6 lg:grid-cols-4 md:grid-cols-2">
          {plans.map((p) => (
            <div
              key={p.name}
              className={`relative flex flex-col rounded-2xl border p-6 transition-all hover:-translate-y-0.5 ${
                p.popular
                  ? 'border-violet-300 bg-white shadow-lg shadow-violet-500/10'
                  : p.premium
                    ? 'border-gray-700 bg-gradient-to-br from-gray-900 to-gray-950 text-white shadow-md'
                    : 'border-gray-200 bg-white shadow-sm'
              }`}
            >
              {/* Corner badge */}
              {p.popular && (
                <span className="absolute -top-px -right-px inline-flex items-center gap-1 rounded-bl-xl rounded-tr-2xl bg-gradient-to-r from-violet-500 to-violet-700 px-3 py-1.5 text-[11px] font-bold text-white shadow-sm">
                  <svg className="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                  </svg>
                  Más popular
                </span>
              )}

              {/* Icon + Name */}
              <div className={`mb-4 flex items-center gap-3 ${p.popular || !p.premium ? '' : ''}`}>
                <div className={`flex h-10 w-10 items-center justify-center rounded-xl ${
                  p.popular
                    ? 'bg-violet-100 text-violet-600'
                    : p.premium
                      ? 'bg-white/10 text-violet-300'
                      : 'bg-gray-100 text-gray-500'
                }`}>
                  {p.icon}
                </div>
                <div>
                  <h3 className={`text-base font-bold ${p.premium ? 'text-white' : 'text-gray-900'}`}>{p.name}</h3>
                  <p className={`text-xs ${p.premium ? 'text-gray-400' : 'text-gray-400'}`}>{p.desc}</p>
                </div>
              </div>

              {/* Price */}
              <div className="mb-6 border-t pt-4" style={{borderColor: p.premium ? 'rgba(255,255,255,0.08)' : '#f3f4f6'}}>
                {p.price === 'A medida' ? (
                  <span className={`text-3xl font-black tracking-tight ${p.premium ? 'text-white' : 'text-gray-900'}`}>A medida</span>
                ) : (
                  <div className="flex items-baseline gap-0.5">
                    <span className={`text-3xl font-black tracking-tight ${p.premium ? 'text-white' : 'text-gray-900'}`}>{p.price}</span>
                    <span className={`text-sm ${p.premium ? 'text-gray-400' : 'text-gray-400'}`}>{p.period}</span>
                  </div>
                )}
              </div>

              {/* Features */}
              <ul className="mb-6 flex-1 space-y-3">
                {p.features.map((f) => (
                  <li key={f} className="flex items-start gap-3 text-sm">
                    <svg className={`mt-0.5 h-4 w-4 flex-shrink-0 ${p.premium ? 'text-emerald-400' : 'text-emerald-500'}`} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
                      <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                    <span className={p.premium ? 'text-gray-300' : 'text-gray-600'}>{f}</span>
                  </li>
                ))}
                {p.notIncluded.map((f) => (
                  <li key={f} className="flex items-start gap-3 text-sm">
                    <svg className={`mt-0.5 h-4 w-4 flex-shrink-0 ${p.premium ? 'text-gray-600' : 'text-gray-300'}`} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                      <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    <span className={p.premium ? 'text-gray-500' : 'text-gray-400'}>{f}</span>
                  </li>
                ))}
              </ul>

              {/* CTA Button */}
              <a
                href={`https://wa.me/584247253544?text=Hola%2C%20quiero%20el%20plan%20${encodeURIComponent(p.name)}%20de%20TiendaPOS`}
                target="_blank"
                rel="noopener"
                className={`inline-flex items-center justify-center gap-2 rounded-xl py-3 text-sm font-semibold no-underline transition-all ${
                  p.popular
                    ? 'bg-gradient-to-r from-violet-500 to-violet-700 text-white shadow-sm hover:shadow-md'
                    : p.premium
                      ? 'border border-white/20 bg-white/10 text-white hover:bg-white/20'
                      : 'border-2 border-violet-200 text-violet-700 hover:border-violet-500 hover:bg-violet-50'
                }`}
              >
                {p.price === 'A medida' ? (
                  <>
                    Contáctanos
                    <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                      <path strokeLinecap="round" strokeLinejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                    </svg>
                  </>
                ) : (
                  <>
                    Elegir plan
                    <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                      <path strokeLinecap="round" strokeLinejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                    </svg>
                  </>
                )}
              </a>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}
