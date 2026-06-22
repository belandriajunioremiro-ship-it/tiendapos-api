export default function Hero() {
  return (
    <section className="relative overflow-hidden bg-gradient-to-b from-[#fafafa] via-white to-gray-50/50 px-2 py-[clamp(5rem,8vw,7rem)] text-center">
      {/* Enhanced mesh gradient */}
      <div className="hero-mesh pointer-events-none absolute inset-0" />
      <div className="noise-overlay pointer-events-none absolute inset-0" />

      <div className="relative mx-auto max-w-[850px]">
        {/* Badge */}
        <div className="mx-auto mb-6 inline-flex items-center gap-1.5 rounded-full border border-violet-200 bg-violet-50 px-4 py-1.5 text-xs font-semibold text-violet-700">
          <svg width="14" height="14" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
          Multi-país &middot; Multi-moneda &middot; Multi-tenant
        </div>

        {/* Headline */}
        <h1 className="mb-5 text-[clamp(2rem,5.5vw,4rem)] font-extrabold leading-[1.08] tracking-tight text-[#1a1a2e]">
          El POS que entiende la{' '}
          <span className="text-shimmer">facturación fiscal</span>
          <br />de Latinoamérica
        </h1>

        {/* Subtitle */}
        <p className="mx-auto mb-8 max-w-[600px] text-[clamp(1rem,2vw,1.15rem)] leading-relaxed text-gray-500">
          Multi-tenant, multimoneda y preparado para los regímenes fiscales de 9 países.
          Vende, factura, controla inventario y gestiona créditos desde un solo sistema.
        </p>

        {/* CTAs */}
        <div className="flex flex-wrap items-center justify-center gap-4">
          <a
            href="https://wa.me/584247253544?text=Hola%2C%20quiero%20probar%20TiendaPOS"
            target="_blank"
            className="glow-hover inline-flex items-center gap-2.5 rounded-xl bg-gradient-to-r from-violet-600 to-violet-700 px-7 py-3.5 text-sm font-bold text-white no-underline shadow-lg shadow-violet-500/25 transition-all hover:-translate-y-0.5"
          >
            <svg width="18" height="18" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><path d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            Comenzar prueba gratis
          </a>
          <a
            href="https://wa.me/584247253544?text=Hola%2C%20quiero%20una%20demo%20de%20TiendaPOS"
            target="_blank"
            className="inline-flex items-center gap-2.5 rounded-xl border-2 border-gray-200 bg-white/60 px-7 py-3.5 text-sm font-bold text-gray-700 no-underline backdrop-blur-sm transition-all hover:border-violet-300 hover:text-violet-700 hover:shadow-sm"
          >
            <svg width="18" height="18" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><polygon points="5 3 19 12 5 21 5 3" /></svg>
            Ver demo
          </a>
        </div>

        {/* Social proof */}
        <div className="mx-auto mt-10 flex flex-wrap items-center justify-center gap-6 border-t border-gray-100 pt-8 text-sm text-gray-400">
          <div className="flex items-center gap-2">
            <span className="flex -space-x-2">
              <span className="flex h-7 w-7 items-center justify-center rounded-full border-2 border-white bg-violet-100 text-[9px] font-bold text-violet-700">VE</span>
              <span className="flex h-7 w-7 items-center justify-center rounded-full border-2 border-white bg-blue-100 text-[9px] font-bold text-blue-700">CO</span>
              <span className="flex h-7 w-7 items-center justify-center rounded-full border-2 border-white bg-green-100 text-[9px] font-bold text-green-700">MX</span>
              <span className="flex h-7 w-7 items-center justify-center rounded-full border-2 border-white bg-yellow-100 text-[9px] font-bold text-yellow-700">AR</span>
              <span className="flex h-7 w-7 items-center justify-center rounded-full border-2 border-white bg-rose-100 text-[9px] font-bold text-rose-700">+5</span>
            </span>
            <span className="text-gray-500"><strong className="text-gray-700">9 países</strong> cubiertos</span>
          </div>
          <span className="hidden text-gray-300 md:inline">|</span>
          <span className="text-gray-500"><strong className="text-gray-700">112</strong> endpoints API</span>
          <span className="hidden text-gray-300 md:inline">|</span>
          <span className="text-gray-500"><strong className="text-gray-700">4</strong> planes disponibles</span>
        </div>
      </div>

      {/* Mockup illustration */}
      <div className="relative mx-auto mt-14 max-w-[800px] px-4">
        <div className="relative overflow-hidden rounded-2xl border border-gray-200/60 bg-white shadow-xl shadow-gray-200/50">
          <div className="flex items-center gap-1.5 border-b border-gray-100 px-4 py-3">
            <span className="h-2.5 w-2.5 rounded-full bg-red-400" />
            <span className="h-2.5 w-2.5 rounded-full bg-yellow-400" />
            <span className="h-2.5 w-2.5 rounded-full bg-green-400" />
            <span className="ml-3 rounded-md bg-gray-100 px-3 py-1 text-[10px] font-medium text-gray-400">tiendapos.app</span>
          </div>
          <div className="grid grid-cols-[180px_1fr]">
            <div className="border-r border-gray-100 bg-gray-50/50 p-3 max-sm:hidden">
              <div className="mb-3 h-2 w-16 rounded bg-gray-200" />
              <div className="mb-2 h-2 w-20 rounded bg-violet-100" />
              <div className="mb-2 h-2 w-14 rounded bg-gray-200" />
              <div className="mb-2 h-2 w-18 rounded bg-gray-200" />
              <div className="mb-2 h-2 w-12 rounded bg-gray-200" />
            </div>
            <div className="p-4">
              <div className="mb-3 flex items-center justify-between">
                <div className="h-3 w-24 rounded bg-gray-200" />
                <div className="h-6 w-20 rounded-lg bg-violet-100" />
              </div>
              <div className="grid grid-cols-3 gap-2">
                <div className="rounded-lg border border-gray-100 p-2">
                  <div className="mb-1 h-2 w-12 rounded bg-gray-200" />
                  <div className="mb-1 text-base font-bold text-[#1a1a2e]">$1,280</div>
                  <div className="h-1.5 w-16 rounded bg-gray-100" />
                </div>
                <div className="rounded-lg border border-gray-100 p-2">
                  <div className="mb-1 h-2 w-12 rounded bg-gray-200" />
                  <div className="mb-1 text-base font-bold text-[#1a1a2e]">47</div>
                  <div className="h-1.5 w-16 rounded bg-gray-100" />
                </div>
                <div className="rounded-lg border border-gray-100 p-2">
                  <div className="mb-1 h-2 w-12 rounded bg-gray-200" />
                  <div className="mb-1 text-base font-bold text-[#1a1a2e]">12</div>
                  <div className="h-1.5 w-16 rounded bg-gray-100" />
                </div>
              </div>
              <div className="mt-3 grid grid-cols-4 gap-1.5">
                <div className="h-1.5 rounded bg-gray-100" />
                <div className="h-1.5 rounded bg-gray-100" />
                <div className="h-1.5 rounded bg-violet-100" />
                <div className="h-1.5 rounded bg-gray-100" />
              </div>
            </div>
          </div>
        </div>
        {/* Glow behind mockup */}
        <div className="pointer-events-none absolute -inset-4 rounded-3xl bg-violet-500/5 blur-3xl" />
      </div>

      {/* Disclaimer */}
      <div className="relative mx-auto mt-10 max-w-[640px] rounded-xl border border-gray-200/40 bg-white/40 px-4 py-2.5 text-center text-xs leading-relaxed text-gray-400 backdrop-blur-sm">
        Gestiona datos formales de facturación — <strong className="text-gray-500">RIF/NIT/RFC/RUC/CUIT</strong> — para cumplimiento contable. <strong className="text-red-400">No es un sistema fiscal certificado</strong>.
      </div>
    </section>
  );
}
