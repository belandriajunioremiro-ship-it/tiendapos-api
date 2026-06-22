import { motion } from 'framer-motion';
import SectionHeader from './SectionHeader';
import { StaggerContainer, StaggerItem } from './StaggerGrid';

const plans = [
  {
    name: 'Emprendedor',
    price: '$9',
    period: '/mes',
    popular: false,
    premium: false,
    features: ['1 usuario', '1 negocio', 'Facturación básica', 'Reportes simples', 'Soporte email'],
    notIncluded: ['API REST', 'Multimoneda', 'Roles y permisos'],
  },
  {
    name: 'Pro',
    price: '$19',
    period: '/mes',
    popular: true,
    premium: false,
    features: ['3 usuarios', '3 negocios', 'Facturación avanzada', 'Inventario completo', 'Reportes detallados', 'Soporte prioritario', 'API REST'],
    notIncluded: ['Multimoneda real', 'Créditos y cobranzas'],
  },
  {
    name: 'Premium',
    price: '$39',
    period: '/mes',
    popular: false,
    premium: true,
    features: ['10 usuarios', '10 negocios', 'Todo lo de Pro', 'Multimoneda real', 'Roles y permisos', 'Créditos y cobranzas', 'Suscripciones', 'Soporte 24/7'],
    notIncluded: [],
  },
  {
    name: 'Enterprise',
    price: 'A medida',
    period: '',
    popular: false,
    premium: false,
    features: ['Usuarios ilimitados', 'Negocios ilimitados', 'Todo lo de Premium', 'Onboarding dedicado', 'SLA garantizado', 'Infraestructura dedicada', 'Soporte 24/7', 'Capacitación presencial'],
    notIncluded: [],
  },
];

export default function Pricing() {
  return (
    <section id="planes" className="px-4 py-20 md:py-32">
      <div className="mx-auto max-w-7xl">
        <SectionHeader
          badge="Planes flexibles"
          title="Inversión que se adapta a ti"
          subtitle="Elige el plan que mejor se ajuste a tu negocio. Puedes cambiar de plan en cualquier momento sin penalización."
        />

        <StaggerContainer className="grid gap-8 lg:grid-cols-4 md:grid-cols-2">
          {plans.map((p) => (
            <StaggerItem key={p.name}>
              <motion.div
                whileHover={p.popular ? { y: -4, boxShadow: '0 24px 48px -12px rgba(124, 58, 237, 0.25)' } : { y: -4, boxShadow: '0 20px 40px -12px rgba(0,0,0,0.08)' }}
                className={`relative flex flex-col rounded-3xl border p-8 ${
                  p.popular
                    ? 'border-violet-300 bg-white shadow-xl shadow-violet-500/10'
                    : p.premium
                      ? 'border-gray-200 bg-gradient-to-br from-gray-900 via-gray-900 to-violet-950 text-white'
                      : 'border-gray-200 bg-white'
                }`}
              >
                {p.popular && (
                  <span className="absolute -top-3.5 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-full bg-gradient-to-r from-violet-500 to-violet-700 px-5 py-1.5 text-xs font-bold text-white shadow-md">
                    Más popular
                  </span>
                )}

                <h3 className={`mb-1.5 text-xl font-bold ${p.premium ? 'text-white' : 'text-gray-900'}`}>{p.name}</h3>
                <p className={`mb-6 text-sm ${p.premium ? 'text-gray-400' : 'text-gray-500'}`}>{p.desc}</p>

                <div className="mb-8">
                  <span className={`text-4xl font-black tracking-tight ${p.premium ? 'text-white' : 'text-gray-900'}`}>{p.price}</span>
                  {p.period && (
                    <span className={`ml-0.5 text-sm ${p.premium ? 'text-gray-400' : 'text-gray-500'}`}>{p.period}</span>
                  )}
                </div>

                <ul className="mb-8 flex-1 space-y-3.5">
                  {p.features.map((f) => (
                    <li key={f} className="flex items-start gap-3 text-sm">
                      <svg className={`mt-0.5 h-4 w-4 flex-shrink-0 ${p.premium ? 'text-emerald-400' : 'text-emerald-500'}`} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
                        <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                      </svg>
                      <span className={p.premium ? 'text-gray-200' : 'text-gray-600'}>{f}</span>
                    </li>
                  ))}
                  {p.notIncluded.map((f) => (
                    <li key={f} className="flex items-start gap-3 text-sm">
                      <svg className={`mt-0.5 h-4 w-4 flex-shrink-0 ${p.premium ? 'text-gray-600' : 'text-gray-300'}`} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                        <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                      </svg>
                      <span className={p.premium ? 'text-gray-500' : 'text-gray-400'}>{f}</span>
                    </li>
                  ))}
                </ul>

                <motion.a
                  whileHover={{ scale: 1.02 }}
                  whileTap={{ scale: 0.98 }}
                  href={`https://wa.me/584247253544?text=Hola%2C%20quiero%20el%20plan%20${encodeURIComponent(p.name)}%20de%20TiendaPOS`}
                  target="_blank"
                  rel="noopener"
                  className={`block rounded-xl py-3.5 text-center text-sm font-semibold no-underline transition-all ${
                    p.popular
                      ? 'bg-gradient-to-r from-violet-500 to-violet-700 text-white shadow-md shadow-violet-500/25 hover:shadow-lg hover:shadow-violet-500/30'
                      : p.premium
                        ? 'border border-white/20 bg-white/10 text-white backdrop-blur-sm hover:bg-white/20'
                        : 'border-2 border-violet-200 text-violet-700 hover:border-violet-500 hover:bg-violet-50'
                  }`}
                >
                  {p.price === 'A medida' ? 'Contáctanos' : 'Elegir plan'}
                </motion.a>
              </motion.div>
            </StaggerItem>
          ))}
        </StaggerContainer>
      </div>
    </section>
  );
}
