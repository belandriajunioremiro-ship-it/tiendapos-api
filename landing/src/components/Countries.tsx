import SectionHeader from './SectionHeader';

const countries = [
  {
    code: 've', name: 'Venezuela',
    entity: 'SENIAT',
    tax: 'IVA 16%',
    docs: ['Factura', 'ISLR', 'Retenciones'],
  },
  {
    code: 'co', name: 'Colombia',
    entity: 'DIAN',
    tax: 'IVA 19%',
    docs: ['Factura Electrónica', 'RUT', 'Documento Soporte'],
  },
  {
    code: 'ec', name: 'Ecuador',
    entity: 'SRI',
    tax: 'IVA 15%',
    docs: ['Factura Electrónica', 'Comprobantes', 'Retenciones'],
  },
  {
    code: 'pe', name: 'Perú',
    entity: 'SUNAT',
    tax: 'IGV 18%',
    docs: ['Factura Electrónica', 'Boleta', 'Detracciones'],
  },
  {
    code: 'cl', name: 'Chile',
    entity: 'SII',
    tax: 'IVA 19%',
    docs: ['DTE', 'Factura Electrónica', 'Boleta'],
  },
  {
    code: 'ar', name: 'Argentina',
    entity: 'AFIP',
    tax: 'IVA 21%',
    docs: ['Factura Electrónica', 'IIBB', 'Monotributo'],
  },
  {
    code: 'mx', name: 'México',
    entity: 'SAT',
    tax: 'IVA 16%',
    docs: ['CFDI 4.0', 'Factura', 'Carta Porte'],
  },
  {
    code: 'pa', name: 'Panamá',
    entity: 'DGI',
    tax: 'ITBMS 7%',
    docs: ['Factura', 'F-75', 'Aviso de Operación'],
  },
  {
    code: 'do', name: 'Rep. Dominicana',
    entity: 'DGII',
    tax: 'ITBIS 18%',
    docs: ['NCF', 'Factura Electrónica', 'RNC'],
  },
];

export default function Countries() {
  return (
    <section id="paises" className="bg-gray-50/80 px-4 py-20 md:py-32">
      <div className="mx-auto max-w-7xl">
        <SectionHeader
          badge="Cobertura regional"
          title="Países que soporta TiendaPOS"
          subtitle="Regímenes fiscales, monedas y métodos de pago nativos para cada país de la región."
        />

        <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
          {countries.map((c) => (
            <a
              key={c.code}
              href={`https://wa.me/584247253544?text=Hola%2C%20quiero%20informaci%C3%B3n%20sobre%20TiendaPOS%20en%20${encodeURIComponent(c.name)}`}
              target="_blank"
              rel="noopener"
              className="group flex flex-col rounded-2xl border border-gray-200 bg-white p-5 no-underline shadow-sm transition-all hover:-translate-y-0.5 hover:border-violet-200 hover:shadow-md"
            >
              {/* Flag + Entity row */}
              <div className="flex items-start gap-4">
                <span className={`fi fi-${c.code} h-10 w-10 flex-shrink-0 rounded-full shadow-sm ring-1 ring-gray-200`} />
                <div className="min-w-0 flex-1">
                  <div className="flex items-baseline justify-between gap-2">
                    <h3 className="text-base font-bold text-gray-900">{c.name}</h3>
                    <span className="whitespace-nowrap rounded-md bg-gray-100 px-2 py-0.5 text-[11px] font-semibold text-gray-600">
                      {c.tax}
                    </span>
                  </div>
                  <p className="mt-0.5 text-sm text-gray-500">{c.entity}</p>
                </div>
              </div>

              {/* Divider */}
              <div className="my-3 border-t border-gray-100" />

              {/* Document tags */}
              <div className="flex flex-wrap gap-1.5">
                {c.docs.map((d) => (
                  <span
                    key={d}
                    className="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-gray-50 px-2.5 py-1 text-[12px] font-medium text-gray-600 transition-colors group-hover:border-violet-200 group-hover:bg-violet-50 group-hover:text-violet-700"
                  >
                    {d}
                  </span>
                ))}
              </div>

              {/* CTA */}
              <div className="mt-3 flex items-center gap-1 text-[12px] font-medium text-gray-400 transition-colors group-hover:text-violet-600">
                Consultar disponibilidad
                <svg className="h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
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
