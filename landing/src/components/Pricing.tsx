import SectionHeader from './SectionHeader';

const plans = [
  {
    name: 'Trial',
    price: '$0',
    period: '/mes',
    subtitle: '14 días gratis. Sin compromiso.',
    variant: 'light',
    limits: [
      '50 productos',
      '2 usuarios',
      '1 almacén',
      '1 caja',
    ],
  },
  {
    name: 'Básico',
    price: '$19',
    period: '/mes',
    subtitle: 'Para negocios en crecimiento.',
    variant: 'light',
    limits: [
      '200 productos',
      '5 usuarios',
      '2 almacenes',
      '2 cajas',
    ],
  },
  {
    name: 'Pro',
    price: '$49',
    period: '/mes',
    subtitle: 'La opción más balanceada.',
    variant: 'popular',
    limits: [
      '1,000 productos',
      '15 usuarios',
      '5 almacenes',
      '5 cajas',
    ],
  },
  {
    name: 'Premium',
    price: '$99',
    period: '/mes',
    subtitle: 'Sin límites, sin preocupaciones.',
    variant: 'premium',
    limits: [
      'Productos ilimitados',
      'Usuarios ilimitados',
      'Almacenes ilimitados',
      'Cajas ilimitadas',
    ],
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
                p.variant === 'premium'
                  ? 'border-violet-900/60 bg-gradient-to-br from-gray-950 via-violet-950 to-gray-900 shadow-xl shadow-violet-900/20'
                  : p.variant === 'popular'
                    ? 'border-violet-300 bg-white shadow-lg shadow-violet-500/10'
                    : 'border-gray-200 bg-white shadow-sm'
              }`}
            >
              {/* Premium gold corner badge */}
              {p.variant === 'premium' && (
                <span className="absolute -top-px -right-px inline-flex items-center gap-1.5 rounded-bl-xl rounded-tr-2xl bg-gradient-to-br from-amber-300 to-amber-400 px-4 py-1.5 text-xs font-extrabold text-black shadow-sm tracking-wider">
                  <svg className="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                  </svg>
                  PREMIUM
                </span>
              )}

              {/* Pro popular badge */}
              {p.variant === 'popular' && (
                <span className="absolute -top-px -right-px inline-flex items-center gap-1.5 rounded-bl-xl rounded-tr-2xl bg-gradient-to-r from-violet-500 to-violet-700 px-4 py-1.5 text-xs font-extrabold text-white shadow-sm tracking-wider">
                  <svg className="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                  </svg>
                  POPULAR
                </span>
              )}

              {/* Name */}
              <h3 className={`text-sm font-semibold uppercase tracking-widest ${
                p.variant === 'premium' ? 'text-amber-300/80' : p.variant === 'popular' ? 'text-violet-600' : 'text-gray-500'
              }`}>
                {p.name}
              </h3>

              {/* Price */}
              <div className={`mt-3 border-t pt-4 ${
                p.variant === 'premium' ? 'border-violet-800/40' : 'border-gray-100'
              }`}>
                <div className="flex items-end gap-0.5">
                  <span className={`font-black tracking-tight ${
                    p.variant === 'premium' ? 'text-4xl text-white' : 'text-4xl text-gray-900'
                  }`}>
                    {p.price}
                  </span>
                  <span className={`mb-1 text-sm ${
                    p.variant === 'premium' ? 'text-gray-400' : p.variant === 'popular' ? 'text-gray-500' : 'text-gray-400'
                  }`}>
                    {p.period}
                  </span>
                </div>
                <p className={`mt-1.5 text-sm leading-relaxed ${
                  p.variant === 'premium' ? 'text-gray-400' : p.variant === 'popular' ? 'text-gray-500' : 'text-gray-400'
                }`}>
                  {p.subtitle}
                </p>
              </div>

              {/* Limits with checkmark */}
              <ul className="mt-5 mb-6 flex-1 space-y-3">
                {p.limits.map((item) => (
                  <li key={item} className="flex items-center gap-3">
                    <svg
                      className={`h-4 w-4 flex-shrink-0 ${
                        p.variant === 'premium' ? 'text-amber-400' : p.variant === 'popular' ? 'text-violet-500' : 'text-emerald-500'
                      }`}
                      fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}
                    >
                      <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                    <span className={`text-sm font-medium ${
                      p.variant === 'premium' ? 'text-gray-200' : p.variant === 'popular' ? 'text-gray-700' : 'text-gray-700'
                    }`}>
                      {item}
                    </span>
                  </li>
                ))}
              </ul>

              {/* CTA */}
              <a
                href={`https://wa.me/584247253544?text=Hola%2C%20quiero%20el%20plan%20${encodeURIComponent(p.name)}%20de%20TiendaPOS`}
                target="_blank"
                rel="noopener"
                className={`inline-flex items-center justify-center gap-2 rounded-xl py-3 text-sm font-bold no-underline transition-all ${
                  p.variant === 'premium'
                    ? 'bg-gradient-to-r from-amber-400 to-amber-500 text-black shadow-sm hover:shadow-lg hover:shadow-amber-400/30'
                    : p.variant === 'popular'
                      ? 'bg-gradient-to-r from-violet-500 to-violet-700 text-white shadow-sm hover:shadow-md hover:shadow-violet-500/30'
                      : 'border-2 border-gray-200 text-gray-600 hover:border-violet-300 hover:text-violet-700'
                }`}
              >
                {p.name === 'Trial' ? (
                  <span className="flex items-center gap-2">
                    Comenzar prueba
                    <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                      <path strokeLinecap="round" strokeLinejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                    </svg>
                  </span>
                ) : (
                  <span className="flex items-center gap-2">
                    Elegir plan
                    <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                      <path strokeLinecap="round" strokeLinejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                    </svg>
                  </span>
                )}
              </a>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}
