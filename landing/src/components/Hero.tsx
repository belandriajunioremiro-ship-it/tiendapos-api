import { LuStar, LuClock, LuPlay } from 'react-icons/lu';

export default function Hero() {
  return (
    <section className="relative overflow-hidden bg-gradient-to-b from-[#fafafa] via-white to-gray-50/50 px-2 py-[clamp(5rem,8vw,7rem)] text-center">
      <div className="hero-mesh pointer-events-none absolute inset-0" />
      <div className="noise-overlay pointer-events-none absolute inset-0" />

      <div className="relative mx-auto max-w-[850px]">
        <div className="mx-auto mb-6 inline-flex items-center gap-1.5 rounded-full border border-violet-200 bg-violet-50 px-4 py-1.5 text-xs font-semibold text-violet-700">
          <LuStar size={14} />
          Multi-país &middot; Multi-moneda &middot; Multi-tenant
        </div>

        <h1 className="mb-5 text-[clamp(2rem,5.5vw,4rem)] font-extrabold leading-[1.08] tracking-tight text-[#1a1a2e]">
          El POS que entiende la{' '}
          <span className="text-violet-600">facturación fiscal</span>
          <br />de Latinoamérica
        </h1>

        <p className="mx-auto mb-8 max-w-[600px] text-[clamp(1rem,2vw,1.15rem)] leading-relaxed text-gray-500">
          Multi-tenant, multimoneda y preparado para los regímenes fiscales de 9 países.
          Vende, factura, controla inventario y gestiona créditos desde un solo sistema.
        </p>

        <div className="flex flex-wrap items-center justify-center gap-4">
          <a
            href="https://wa.me/584247253544?text=Hola%2C%20quiero%20probar%20TiendaPOS"
            target="_blank"
            className="glow-hover inline-flex items-center gap-2.5 rounded-xl bg-gradient-to-r from-violet-600 to-violet-700 px-7 py-3.5 text-sm font-bold text-white no-underline shadow-lg shadow-violet-500/25 transition-all hover:-translate-y-0.5"
          >
            <LuClock size={18} />
            Comenzar prueba gratis
          </a>
          <a
            href="https://wa.me/584247253544?text=Hola%2C%20quiero%20una%20demo%20de%20TiendaPOS"
            target="_blank"
            className="inline-flex items-center gap-2.5 rounded-xl border-2 border-gray-200 bg-white/60 px-7 py-3.5 text-sm font-bold text-gray-700 no-underline backdrop-blur-sm transition-all hover:border-violet-300 hover:text-violet-700 hover:shadow-sm"
          >
            <LuPlay size={18} />
            Ver demo
          </a>
        </div>

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

      <div className="relative mx-auto mt-10 max-w-[640px] rounded-xl border border-gray-200/40 bg-white/40 px-4 py-2.5 text-center text-xs leading-relaxed text-gray-400 backdrop-blur-sm">
        Gestiona datos formales de facturación — <strong className="text-gray-500">RIF/NIT/RFC/RUC/CUIT</strong> — para cumplimiento contable. <strong className="text-red-400">No es un sistema fiscal certificado</strong>.
      </div>
    </section>
  );
}
