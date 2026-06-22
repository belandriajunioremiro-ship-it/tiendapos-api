const plans = [
  {
    name: 'Trial', price: '$0', period: '/mes',
    subtitle: '14 días gratis. Sin compromiso.',
    variant: 'light',
    limits: ['50 productos', '2 usuarios', '1 almacén', '1 caja'],
    cta: 'Comenzar prueba',
    icon: <svg width="22" height="22" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>,
  },
  {
    name: 'Básico', price: '$19', period: '/mes',
    subtitle: 'Para negocios en crecimiento.',
    variant: 'light',
    limits: ['200 productos', '5 usuarios', '2 almacenes', '2 cajas'],
    cta: 'Elegir Básico',
    icon: <svg width="22" height="22" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>,
  },
  {
    name: 'Pro', price: '$49', period: '/mes',
    subtitle: 'La opción más balanceada.',
    variant: 'popular',
    limits: ['1,000 productos', '15 usuarios', '5 almacenes', '5 cajas'],
    cta: 'Elegir Pro',
    icon: <svg width="22" height="22" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>,
  },
  {
    name: 'Premium', price: '$99', period: '/mes',
    subtitle: 'Sin límites, sin preocupaciones.',
    variant: 'premium',
    limits: ['Productos ilimitados', 'Usuarios ilimitados', 'Almacenes ilimitados', 'Cajas ilimitadas'],
    cta: 'Elegir Premium',
    icon: <svg width="22" height="22" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>,
  },
];

export default function Pricing() {
  return (
    <>
      <h2 id="planes" class="scroll-mt-24 text-center text-[clamp(1.5rem,3vw,2rem)] font-bold text-[#1a1a2e] reveal">
        Un plan para cada <span class="text-violet-600">realidad</span>
      </h2>
      <p className="mx-auto mb-14 max-w-[580px] px-4 text-center text-base leading-relaxed text-gray-500 reveal">
        Desde emprender hasta escalar. Todos incluyen 14 días de prueba gratis.
      </p>

      <section className="mb-0 grid grid-cols-[repeat(auto-fit,minmax(min(240px,100%),1fr))] items-start gap-5 reveal">
        {plans.map((p) => (
          <div
            key={p.name}
            className={`group relative flex flex-col overflow-hidden rounded-2xl px-6 py-8 text-center transition-all duration-300 hover:-translate-y-1 ${
              p.variant === 'premium'
                ? 'border border-violet-500/20 bg-gradient-to-br from-[#1e1b2e] to-[#2d1f3e] shadow-lg shadow-violet-500/10 hover:shadow-xl hover:shadow-violet-500/20'
                : p.variant === 'popular'
                  ? 'border-2 border-violet-500 bg-white shadow-lg shadow-violet-500/15 scale-[1.03] hover:shadow-xl hover:shadow-violet-500/25'
                  : 'border border-gray-200 bg-white shadow-sm hover:shadow-lg'
            }`}
          >
            {/* Corner badge */}
            {p.variant === 'popular' && (
              <div className="absolute right-[-28px] top-3 rotate-45 bg-violet-600 px-8 py-0.5 text-[10px] font-bold uppercase tracking-wider text-white shadow-sm">Popular</div>
            )}
            {p.variant === 'premium' && (
              <div className="absolute right-[-28px] top-3 rotate-45 bg-gradient-to-r from-amber-400 to-amber-500 px-8 py-0.5 text-[10px] font-bold uppercase tracking-wider text-black shadow-sm">Premium</div>
            )}

            {/* Icon */}
            <div className={`mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-xl transition-all group-hover:scale-110 ${
              p.variant === 'premium' ? 'bg-white/10 text-violet-300' : p.variant === 'popular' ? 'bg-violet-100 text-violet-600' : 'bg-gray-100 text-gray-500'
            }`}>
              {p.icon}
            </div>

            {/* Name */}
            <div className={`mb-1 text-xs font-bold uppercase tracking-widest ${
              p.variant === 'premium' ? 'text-violet-300' : 'text-violet-600'
            }`}>{p.name}</div>

            {/* Price */}
            <div className={`mb-1 text-5xl font-black tracking-tight ${p.variant === 'premium' ? 'text-gray-50' : 'text-[#1a1a2e]'}`}>
              {p.price} <span className="text-lg font-normal text-gray-400">{p.period}</span>
            </div>
            <div className={`mx-auto mb-5 max-w-[200px] border-b pb-4 text-sm ${
              p.variant === 'premium' ? 'border-white/10 text-gray-400' : 'border-gray-100 text-gray-500'
            }`}>{p.subtitle}</div>

            {/* Limits */}
            <ul className="mx-auto mb-6 max-w-[200px] list-none text-left text-sm leading-8">
              {p.limits.map((item) => (
                <li key={item} className="flex items-center gap-2">
                  <span className={`flex h-[18px] w-[18px] flex-shrink-0 items-center justify-center rounded-full ${
                    p.variant === 'premium' ? 'bg-violet-300/20 text-violet-300' : p.variant === 'popular' ? 'bg-violet-100 text-violet-600' : 'bg-gray-200/60 text-gray-500'
                  }`}>
                    <svg width="10" height="10" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><polyline points="2 5 4 7 8 3" /></svg>
                  </span>
                  <span className={`font-medium ${p.variant === 'premium' ? 'text-gray-300' : 'text-gray-700'}`}>{item}</span>
                </li>
              ))}
            </ul>

            {/* CTA Button */}
            <a
              href={`https://wa.me/584247253544?text=Hola%2C%20quiero%20el%20plan%20${encodeURIComponent(p.name)}%20de%20TiendaPOS`}
              target="_blank"
              className={`mt-auto inline-flex items-center justify-center gap-2 rounded-xl py-3 text-sm font-bold no-underline transition-all ${
                p.variant === 'premium'
                  ? 'bg-gradient-to-r from-amber-400 to-amber-500 text-black shadow-sm hover:shadow-md hover:shadow-amber-400/30'
                  : p.variant === 'popular'
                    ? 'bg-gradient-to-r from-violet-500 to-violet-700 text-white shadow-sm hover:shadow-md hover:shadow-violet-500/30'
                    : 'border-2 border-gray-200 text-gray-600 hover:border-violet-300 hover:text-violet-700'
              }`}
            >
              {p.cta}
              <svg width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
            </a>
          </div>
        ))}
      </section>
    </>
  );
}
