import { motion } from 'framer-motion';
import SectionHeader from './SectionHeader';
import { StaggerContainer, StaggerItem } from './StaggerGrid';

const countries = [
  { code: 've', name: 'Venezuela' },
  { code: 'co', name: 'Colombia' },
  { code: 'ec', name: 'Ecuador' },
  { code: 'pe', name: 'Perú' },
  { code: 'cl', name: 'Chile' },
  { code: 'ar', name: 'Argentina' },
  { code: 'mx', name: 'México' },
  { code: 'pa', name: 'Panamá' },
  { code: 'do', name: 'Rep. Dominicana' },
];

const fiscalInfo: Record<string, string[]> = {
  ve: ['Facturación electrónica', 'IVA 16%', 'ISLR', 'SENIAT'],
  co: ['Factura electrónica', 'DIAN', 'RUT', 'Régimen Simple'],
  ec: ['Facturación electrónica', 'SRI', 'IVA 15%', 'Comprobantes Electrónicos'],
  pe: ['Facturación electrónica', 'SUNAT', 'IGV 18%', 'Detracciones'],
  cl: ['Facturación electrónica', 'SII', 'IVA 19%', 'DTE'],
  ar: ['Factura electrónica AFIP', 'IVA 21%', 'IIBB', 'RG 3685'],
  mx: ['CFDI 4.0', 'SAT', 'IVA 16%', 'Carta Porte'],
  pa: ['Facturación electrónica', 'DGI', 'ITBMS 7%', 'F-75'],
  do: ['NCF', 'DGII', 'ITBIS 18%', 'e-CF'],
};

export default function Countries() {
  return (
    <section id="paises" className="relative overflow-hidden bg-gray-50/80 px-4 py-20 md:py-32">
      <div className="pointer-events-none absolute inset-0 opacity-[0.03]">
        <svg viewBox="0 0 1200 800" className="h-full w-full">
          <path d="M400 100 L800 100 L1000 400 L800 700 L400 700 L200 400 Z" fill="none" stroke="#7c3aed" strokeWidth="2" />
          <path d="M100 200 L500 200 L700 500 L500 800 L100 800 L-100 500 Z" fill="none" stroke="#7c3aed" strokeWidth="1.5" opacity="0.6" />
          <circle cx="600" cy="400" r="300" fill="none" stroke="#7c3aed" strokeWidth="1" opacity="0.4" />
          <circle cx="600" cy="400" r="200" fill="none" stroke="#7c3aed" strokeWidth="0.5" opacity="0.3" />
        </svg>
      </div>

      <div className="relative z-10 mx-auto max-w-7xl">
        <SectionHeader
          badge="Cobertura regional"
          title="Disponible en toda Latinoamérica"
          subtitle="Cobertura fiscal actualizada constantemente para cumplir con las regulaciones de cada país hispanoamericano."
        />

        <StaggerContainer className="mb-14 flex flex-wrap justify-center gap-6 md:gap-10">
          {countries.map((c) => (
            <StaggerItem key={c.code}>
              <div className="flex flex-col items-center gap-2">
                <span className={`fi fi-${c.code} h-12 w-12 rounded-full shadow-sm ring-2 ring-white`} />
                <span className="text-xs font-medium text-gray-500">{c.name}</span>
              </div>
            </StaggerItem>
          ))}
        </StaggerContainer>

        <StaggerContainer className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
          {countries.map((c) => (
            <StaggerItem key={c.code}>
              <motion.div
                whileHover={{ y: -4, boxShadow: '0 16px 32px -8px rgba(124, 58, 237, 0.1)' }}
                className="flex items-start gap-4 rounded-2xl border border-gray-100 bg-white p-5 transition-colors hover:border-violet-200/50"
              >
                <span className={`fi fi-${c.code} h-9 w-9 flex-shrink-0 rounded-full shadow-sm`} />
                <div className="min-w-0">
                  <h4 className="mb-2.5 text-sm font-bold text-gray-900">{c.name}</h4>
                  <div className="flex flex-wrap gap-1.5">
                    {fiscalInfo[c.code]?.map((tag) => (
                      <span
                        key={tag}
                        className="inline-flex items-center rounded-full bg-violet-50 px-2.5 py-0.5 text-[11px] font-medium text-violet-700 ring-1 ring-violet-100"
                      >
                        {tag}
                      </span>
                    ))}
                  </div>
                </div>
              </motion.div>
            </StaggerItem>
          ))}
        </StaggerContainer>
      </div>
    </section>
  );
}
