import { motion } from 'framer-motion';
import SectionHeader from './SectionHeader';

const countries = [
  { code: 've', name: 'Venezuela', entities: 'SENIAT · IVA 16% · ISLR' },
  { code: 'co', name: 'Colombia', entities: 'DIAN · Factura Electrónica · RUT' },
  { code: 'ec', name: 'Ecuador', entities: 'SRI · IVA 15% · Comprobantes Electrónicos' },
  { code: 'pe', name: 'Perú', entities: 'SUNAT · IGV 18% · Detracciones' },
  { code: 'cl', name: 'Chile', entities: 'SII · IVA 19% · DTE' },
  { code: 'ar', name: 'Argentina', entities: 'AFIP · IVA 21% · IIBB' },
  { code: 'mx', name: 'México', entities: 'SAT · CFDI 4.0 · IVA 16%' },
  { code: 'pa', name: 'Panamá', entities: 'DGI · ITBMS 7% · F-75' },
  { code: 'do', name: 'Rep. Dominicana', entities: 'DGII · ITBIS 18% · NCF' },
];

export default function Countries() {
  return (
    <section id="paises" className="bg-gray-50/80 px-4 py-20 md:py-32">
      <div className="mx-auto max-w-7xl">
        <SectionHeader
          badge="Cobertura regional"
          title="Disponible en toda Latinoamérica"
          subtitle="Cobertura fiscal actualizada para cumplir con las regulaciones de cada país."
        />

        <div className="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
          {countries.map((c, i) => (
            <motion.a
              key={c.code}
              href={`https://wa.me/584247253544?text=Hola%2C%20quiero%20informaci%C3%B3n%20sobre%20TiendaPOS%20en%20${encodeURIComponent(c.name)}`}
              target="_blank"
              rel="noopener"
              initial={{ opacity: 0, y: 8 }}
              whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true }}
              transition={{ duration: 0.3, delay: i * 0.04 }}
              className={`flex items-center gap-4 px-5 py-4 no-underline transition-colors hover:bg-violet-50/50 ${
                i < countries.length - 1 ? 'border-b border-gray-100' : ''
              }`}
            >
              <span className={`fi fi-${c.code} h-7 w-7 flex-shrink-0 rounded-full shadow-sm`} />
              <span className="min-w-0 flex-1 text-sm font-semibold text-gray-900">{c.name}</span>
              <span className="hidden text-right text-xs text-gray-400 sm:block">{c.entities}</span>
              <svg className="h-4 w-4 flex-shrink-0 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                <path strokeLinecap="round" strokeLinejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
              </svg>
            </motion.a>
          ))}
        </div>
      </div>
    </section>
  );
}
