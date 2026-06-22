import { useState } from 'react';

const modals = {
  privacidad: {
    title: 'Política de Privacidad',
    content: [
      'En TiendaPOS, nos tomamos muy en serio la privacidad de tus datos. Esta política describe cómo recopilamos, usamos y protegemos tu información personal.',
      { label: '1. Datos que recopilamos:', text: 'Información de registro (nombre, email, empresa), datos transaccionales, configuraciones de negocio y preferencias del sistema.' },
      { label: '2. Uso de datos:', text: 'Utilizamos tus datos únicamente para operar la plataforma, procesar transacciones, cumplir obligaciones fiscales y mejorar el servicio.' },
      { label: '3. Protección:', text: 'Implementamos cifrado SSL, autenticación segura y copias de seguridad diarias. Tus datos nunca serán compartidos con terceros sin tu consentimiento explícito.' },
      { label: '4. Retención:', text: 'Conservamos tus datos mientras mantengas una cuenta activa. Al cancelar, tienes derecho a solicitar la exportación o eliminación de tus datos.' },
      { label: '5. Contacto:', text: 'Para cualquier consulta sobre privacidad, escríbenos a privacidad@tiendapos.com' },
    ],
  },
  terminos: {
    title: 'Términos del Servicio',
    content: [
      'Al utilizar TiendaPOS, aceptas los siguientes términos y condiciones de servicio.',
      { label: '1. Servicio:', text: 'TiendaPOS es una plataforma SaaS de punto de venta multi-tenant. Nos reservamos el derecho de modificar, suspender o discontinuar cualquier funcionalidad con previo aviso.' },
      { label: '2. Responsabilidades del usuario:', text: 'Eres responsable de mantener la confidencialidad de tus credenciales, de la veracidad de los datos fiscales ingresados y del cumplimiento de las leyes locales aplicables.' },
      { label: '3. Limitación de responsabilidad:', text: 'TiendaPOS no será responsable por daños indirectos, pérdida de datos o interrupción del servicio más allá de lo establecido en el SLA del plan contratado.' },
      { label: '4. Facturación:', text: 'Los planes se facturan de forma mensual o anual. El cargo se realiza el día de activación y se renueva automáticamente a menos que se cancele con 7 días de anticipación.' },
      { label: '5. Uso aceptable:', text: 'Queda prohibido el uso de la plataforma para actividades ilícitas, fraude fiscal, lavado de dinero o cualquier otra actividad que infrinja las leyes aplicables.' },
    ],
  },
  aviso: {
    title: 'Aviso Legal',
    content: [
      { label: 'TiendaPOS no es un sistema fiscal certificado.', text: '' },
      'TiendaPOS proporciona herramientas de facturación y gestión empresarial diseñadas para facilitar la operación de tu negocio. Sin embargo, es responsabilidad exclusiva de cada comerciante:',
      '• Verificar que la facturación generada cumpla con los requisitos fiscales locales de su país.',
      '• Obtener las certificaciones o autorizaciones necesarias ante las autoridades competentes (SENIAT, DIAN, SRI, SUNAT, SII, AFIP, SAT, DGII, etc.).',
      '• Mantener sus datos fiscales actualizados y correctos en la plataforma.',
      '• Cumplir con todas las obligaciones tributarias, declaraciones y pagos de impuestos aplicables.',
      'TiendaPOS no asume ninguna responsabilidad por el uso indebido de la plataforma ni por el incumplimiento de obligaciones fiscales por parte del usuario. Recomendamos consultar con un contador o asesor fiscal calificado en tu país.',
    ],
  },
};

type ModalKey = keyof typeof modals;

export default function Footer() {
  const [activeModal, setActiveModal] = useState<ModalKey | null>(null);
  const modal = activeModal ? modals[activeModal] : null;

  return (
    <footer className="bg-gradient-to-br from-gray-900 via-gray-900 to-violet-950 px-4 py-16 text-white md:py-20">
      <div className="mx-auto max-w-7xl">
        <div className="mb-14 grid gap-10 md:grid-cols-3">
          <div className="max-w-xs">
            <div className="mb-4 flex items-center gap-2.5">
              <span className="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-violet-400 to-violet-600 text-sm font-bold">T</span>
              <span className="text-lg font-bold">TiendaPOS</span>
            </div>
            <p className="text-sm leading-relaxed text-white/50">
              Tu negocio merece un POS sin fronteras. Multi-tenant, multimoneda y multi-país para Latinoamérica.
            </p>
          </div>
          <div>
            <h4 className="mb-5 text-sm font-semibold tracking-wide text-white/80 uppercase">Producto</h4>
            <ul className="space-y-3">
              {[
                ['Beneficios', '#beneficios'],
                ['Características', '#caracteristicas'],
                ['Países', '#paises'],
                ['Planes', '#planes'],
              ].map(([label, href]) => (
                <li key={label}>
                  <a href={href} className="text-sm text-white/50 no-underline transition-colors hover:text-white">{label}</a>
                </li>
              ))}
            </ul>
          </div>
          <div>
            <h4 className="mb-5 text-sm font-semibold tracking-wide text-white/80 uppercase">Legal</h4>
            <ul className="space-y-3">
              {(['privacidad', 'terminos', 'aviso'] as ModalKey[]).map((key) => (
                <li key={key}>
                  <button
                    onClick={() => setActiveModal(key)}
                    className="text-sm text-white/50 no-underline transition-colors hover:text-white"
                  >
                    {key === 'privacidad' ? 'Privacidad' : key === 'terminos' ? 'Términos' : 'Aviso Legal'}
                  </button>
                </li>
              ))}
            </ul>
          </div>
        </div>

        <div className="border-t border-white/10 pt-8 text-center text-sm text-white/30">
          &copy; {new Date().getFullYear()} TiendaPOS. Todos los derechos reservados.
        </div>
      </div>

      {activeModal && modal && (
        <div
          className="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
          onClick={() => setActiveModal(null)}
        >
          <div
            className="relative max-h-[80vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-8 shadow-2xl"
            onClick={(e) => e.stopPropagation()}
          >
            <button
              onClick={() => setActiveModal(null)}
              className="absolute right-5 top-5 text-gray-400 transition-colors hover:text-gray-600"
            >
              <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
            <h3 className="mb-5 text-xl font-bold text-gray-900">{modal.title}</h3>
            <div className="space-y-3 text-sm leading-relaxed text-gray-600">
              {modal.content.map((item, i) => {
                if (typeof item === 'string') {
                  if (item.startsWith('•')) {
                    return <p key={i} className="pl-4">{item}</p>;
                  }
                  return <p key={i}>{item}</p>;
                }
                return (
                  <p key={i}>
                    <strong>{item.label}</strong> {item.text}
                  </p>
                );
              })}
            </div>
          </div>
        </div>
      )}
    </footer>
  );
}
