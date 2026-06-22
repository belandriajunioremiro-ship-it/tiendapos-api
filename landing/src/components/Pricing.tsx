import SectionHeader from './SectionHeader';

const plans = [
  {
    name: 'Trial',
    price: '$0',
    period: '/mes',
    subtitle: '14 días gratis. Sin compromiso.',
    popular: false,
    gold: false,
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
    popular: false,
    gold: false,
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
    popular: true,
    gold: false,
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
    popular: false,
    gold: true,
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
                p.popular
                  ? 'border-violet-300 bg-white shadow-lg shadow-violet-500/10'
                  : p.gold
                    ? 'border-amber-300 bg-gradient-to-br from-amber-50 to-white shadow-lg shadow-amber-200/40'
                    : 'border-gray-200 bg-white shadow-sm'
              }`}
            >
              {/* Gold corner badge */}
              {p.gold && (
                <span className="absolute -top-px -right-px inline-flex items-center gap-1 rounded-bl-xl rounded-tr-2xl bg-gradient-to-br from-amber-400 to-amber-500 px-3.5 py-1.5 text-[11px] font-bold text-black shadow-sm">
                  <svg className="h-3 w-3 text-black" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                  </svg>
                  PREMIUM
                </span>
              )}

              {/* Popular badge */}
              {p.popular && (
                <span className="absolute -top-px -right-px inline-flex items-center gap-1 rounded-bl-xl rounded-tr-2xl bg-gradient-to-r from-violet-500 to-violet-700 px-3.5 py-1.5 text-[11px] font-bold text-white shadow-sm">
                  <svg className="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                  </svg>
                  POPULAR
                </span>
              )}

              {/* Name + Price */}
              <div className="mb-4">
                <h3 className={`text-base font-bold ${p.gold ? 'text-amber-950' : 'text-gray-900'}`}>
                  {p.name}
                </h3>
                <div className="mt-1.5 flex items-baseline gap-0.5">
                  <span className={`text-3xl font-black tracking-tight ${p.gold ? 'text-amber-950' : 'text-gray-900'}`}>
                    {p.price}
                  </span>
                  <span className={`text-sm ${p.gold ? 'text-amber-700' : 'text-gray-400'}`}>
                    {p.period}
                  </span>
                </div>
                <p className={`mt-1 text-xs leading-relaxed ${p.gold ? 'text-amber-800' : 'text-gray-400'}`}>
                  {p.subtitle}
                </p>
              </div>

              {/* Limits with checkmark */}
              <ul className="mb-6 flex-1 space-y-3">
                {p.limits.map((item) => (
                  <li key={item} className="flex items-center gap-3 text-sm">
                    <svg
                      className={`h-4 w-4 flex-shrink-0 ${p.gold ? 'text-amber-500' : 'text-emerald-500'}`}
                      fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}
                    >
                      <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                    <span className={`font-medium ${p.gold ? 'text-amber-900' : 'text-gray-700'}`}>
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
                className={`inline-flex items-center justify-center gap-2 rounded-xl py-3 text-sm font-semibold no-underline transition-all ${
                  p.popular
                    ? 'bg-gradient-to-r from-violet-500 to-violet-700 text-white shadow-sm hover:shadow-md'
                    : p.gold
                      ? 'border-2 border-amber-400 bg-gradient-to-r from-amber-400 to-amber-500 text-black hover:shadow-md'
                      : 'border-2 border-gray-200 text-gray-600 hover:border-gray-300'
                }`}
              >
                {p.price === '$0' ? (
                  <>Comenzar prueba <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}><path strokeLinecap="round" strokeLinejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg></>
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
