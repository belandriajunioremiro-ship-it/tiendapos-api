import SectionHeader from './SectionHeader';

const countries = [
  {
    code: 've', name: 'Venezuela',
    entity: 'SENIAT',
    tax: 'IVA 16%',
    docs: ['Factura', 'ISLR', 'Retenciones'],
    color: 'from-yellow-500 to-blue-600',
  },
  {
    code: 'co', name: 'Colombia',
    entity: 'DIAN',
    tax: 'IVA 19%',
    docs: ['Factura Electrónica', 'RUT', 'Documento Soporte'],
    color: 'from-yellow-500 to-blue-700',
  },
  {
    code: 'ec', name: 'Ecuador',
    entity: 'SRI',
    tax: 'IVA 15%',
    docs: ['Factura Electrónica', 'Comprobantes', 'Retenciones'],
    color: 'from-yellow-400 to-blue-500',
  },
  {
    code: 'pe', name: 'Perú',
    entity: 'SUNAT',
    tax: 'IGV 18%',
    docs: ['Factura Electrónica', 'Boleta', 'Detracciones'],
    color: 'from-red-500 to-red-700',
  },
  {
    code: 'cl', name: 'Chile',
    entity: 'SII',
    tax: 'IVA 19%',
    docs: ['DTE', 'Factura Electrónica', 'Boleta'],
    color: 'from-blue-500 to-red-500',
  },
  {
    code: 'ar', name: 'Argentina',
    entity: 'AFIP',
    tax: 'IVA 21%',
    docs: ['Factura Electrónica', 'IIBB', 'Monotributo'],
    color: 'from-blue-400 to-white',
  },
  {
    code: 'mx', name: 'México',
    entity: 'SAT',
    tax: 'IVA 16%',
    docs: ['CFDI 4.0', 'Factura', 'Carta Porte'],
    color: 'from-green-600 to-red-600',
  },
  {
    code: 'pa', name: 'Panamá',
    entity: 'DGI',
    tax: 'ITBMS 7%',
    docs: ['Factura', 'F-75', 'Aviso de Operación'],
    color: 'from-blue-400 to-red-400',
  },
  {
    code: 'do', name: 'Rep. Dominicana',
    entity: 'DGII',
    tax: 'ITBIS 18%',
    docs: ['NCF', 'Factura Electrónica', 'RNC'],
    color: 'from-blue-400 to-red-500',
  },
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

        <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-3">
          {countries.map((c) => (
            <a
              key={c.code}
              href={`https://wa.me/584247253544?text=Hola%2C%20quiero%20informaci%C3%B3n%20sobre%20TiendaPOS%20en%20${encodeURIComponent(c.name)}`}
              target="_blank"
              rel="noopener"
              className="group flex flex-col rounded-2xl border border-gray-100 bg-white p-5 no-underline shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md hover:border-transparent"
            >
              {/* Flag + Name row */}
              <div className="mb-4 flex items-center gap-3">
                <span className={`fi fi-${c.code} h-9 w-9 flex-shrink-0 rounded-full shadow-sm`} />
                <div>
                  <h3 className="text-sm font-bold text-gray-900">{c.name}</h3>
                  <span className="text-xs text-gray-500">{c.entity}</span>
                </div>
              </div>

              {/* Tax badge */}
              <div className="mb-3 inline-flex w-fit items-center gap-1.5 rounded-full border border-gray-200 bg-gray-50/80 px-3 py-1">
                <svg className="h-3 w-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                  <path strokeLinecap="round" strokeLinejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span className="text-xs font-semibold text-gray-700">{c.tax}</span>
              </div>

              {/* Doc tags */}
              <div className="flex flex-wrap gap-1.5">
                {c.docs.map((d) => (
                  <span
                    key={d}
                    className="inline-flex items-center gap-1 rounded-md bg-violet-50 px-2 py-1 text-[11px] font-medium text-violet-700"
                  >
                    <svg className="h-2.5 w-2.5" fill="currentColor" viewBox="0 0 20 20">
                      <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                    </svg>
                    {d}
                  </span>
                ))}
              </div>

              {/* Hover indicator */}
              <div className="mt-4 flex items-center gap-1 text-[11px] font-medium text-violet-500 opacity-0 transition-opacity group-hover:opacity-100">
                Consultar disponibilidad
                <svg className="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                  <path strokeLinecap="round" strokeLinejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                </svg>
              </div>
            </a>
          ))}
        </div>
      </div>
    </section>
  );
}
