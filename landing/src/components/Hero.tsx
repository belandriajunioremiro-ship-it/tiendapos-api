export default function Hero() {
  return (
    <section className="relative overflow-hidden px-2 py-[clamp(4rem,8vw,7rem)] text-center">
      {/* Mesh gradient background */}
      <div className="hero-mesh pointer-events-none absolute inset-0" />

      {/* Content */}
      <div className="relative mx-auto max-w-[800px]">
        <h1 className="mb-5 text-[clamp(2rem,5.5vw,4rem)] font-extrabold leading-[1.1] tracking-tight text-[#1a1a2e]">
          Tu negocio merece un POS{' '}
          <span className="text-shimmer">sin fronteras</span>
        </h1>
        <p className="mx-auto mb-8 max-w-[640px] text-[clamp(1rem,2vw,1.15rem)] leading-relaxed text-gray-500">
          Multi-tenant, multimoneda y preparado para los regímenes fiscales de 9 países.
          Gestiona ventas, inventario, facturación y créditos desde un solo sistema.
        </p>
        <div className="flex flex-wrap items-center justify-center gap-4">
          <a
            href="https://wa.me/584247253544?text=Hola%2C%20quiero%20probar%20TiendaPOS"
            target="_blank"
            className="glow-hover inline-flex items-center gap-2.5 rounded-xl bg-gradient-to-r from-violet-600 to-violet-700 px-6 py-3.5 text-sm font-bold text-white no-underline shadow-lg shadow-violet-500/25 transition-all hover:-translate-y-0.5"
          >
            <svg width="18" height="18" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round"><path d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            Comenzar prueba gratis
          </a>
          <a
            href="https://wa.me/584247253544?text=Hola%2C%20quiero%20una%20demo%20de%20TiendaPOS"
            target="_blank"
            className="inline-flex items-center gap-2.5 rounded-xl border-2 border-gray-200 bg-white/60 px-6 py-3.5 text-sm font-bold text-gray-700 no-underline backdrop-blur-sm transition-all hover:border-violet-300 hover:text-violet-700 hover:shadow-sm"
          >
            <svg width="18" height="18" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><polygon points="5 3 19 12 5 21 5 3" /></svg>
            Ver demo
          </a>
        </div>
      </div>

      {/* Disclaimer moved below */}
      <div className="relative mx-auto mt-14 max-w-[680px] rounded-xl border border-gray-200/60 bg-white/40 px-4 py-3 text-center text-xs leading-relaxed text-gray-400 backdrop-blur-sm">
        Este sistema gestiona datos formales de facturación — <strong className="text-gray-500">RIF/NIT/RFC/RUC/CUIT</strong>, razones sociales e impuestos — para cumplir con requisitos contables y fiscales de cada país. <strong className="text-red-500">No es un sistema fiscal certificado</strong>.
      </div>
    </section>
  );
}
