import { useState, useEffect } from 'react';
import { motion, AnimatePresence } from 'framer-motion';

const links = [
  { href: '#beneficios', label: 'Beneficios' },
  { href: '#caracteristicas', label: 'Características' },
  { href: '#paises', label: 'Países' },
  { href: '#planes', label: 'Planes' },
];

const menuVariants = {
  hidden: { opacity: 0, y: -20, scaleY: 0.95 },
  visible: {
    opacity: 1, y: 0, scaleY: 1,
    transition: { duration: 0.35, ease: [0.25, 0.46, 0.45, 0.94], when: 'beforeChildren', staggerChildren: 0.06 },
  },
  exit: {
    opacity: 0, y: -20, scaleY: 0.95,
    transition: { duration: 0.25, ease: 'easeIn' },
  },
};

const linkVariants = {
  hidden: { opacity: 0, x: -20 },
  visible: { opacity: 1, x: 0, transition: { duration: 0.3 } },
};

export default function Navbar() {
  const [open, setOpen] = useState(false);
  const [scrolled, setScrolled] = useState(false);

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 20);
    window.addEventListener('scroll', onScroll, { passive: true });
    return () => window.removeEventListener('scroll', onScroll);
  }, []);

  const close = () => setOpen(false);

  return (
    <header
      className={`fixed top-0 right-0 left-0 z-50 transition-all duration-300 ${
        scrolled ? 'bg-white/80 shadow-sm backdrop-blur-xl' : 'bg-transparent'
      }`}
    >
      <div className="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 md:px-8">
        <a href="#" className="flex items-center gap-2.5 no-underline">
          <span className="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-violet-500 to-violet-700 text-sm font-bold text-white shadow-sm">
            T
          </span>
          <span className="text-lg font-bold text-gray-900">TiendaPOS</span>
        </a>

        <nav className="hidden items-center gap-8 md:flex">
          {links.map((l) => (
            <a
              key={l.href}
              href={l.href}
              className="group relative text-sm font-medium text-gray-600 no-underline transition-colors hover:text-violet-600"
            >
              {l.label}
              <span className="absolute -bottom-1 left-0 h-0.5 w-0 rounded-full bg-violet-500 transition-all duration-300 group-hover:w-full" />
            </a>
          ))}
          <a
            href="https://wa.me/584247253544?text=Hola%2C%20quiero%20informaci%C3%B3n%20sobre%20TiendaPOS"
            target="_blank"
            rel="noopener"
            className="rounded-full bg-gradient-to-r from-violet-500 to-violet-700 px-5 py-2.5 text-sm font-semibold text-white no-underline shadow-sm shadow-violet-500/25 transition-all hover:shadow-md hover:shadow-violet-500/30"
          >
            Contáctanos
          </a>
        </nav>

        <button
          onClick={() => setOpen(!open)}
          className="relative z-50 flex h-10 w-10 flex-col items-center justify-center gap-1.5 md:hidden"
          aria-label="Menú"
        >
          <motion.span
            animate={open ? { rotate: 45, y: 6 } : { rotate: 0, y: 0 }}
            className="h-0.5 w-6 rounded-full bg-gray-700"
          />
          <motion.span
            animate={open ? { opacity: 0 } : { opacity: 1 }}
            className="h-0.5 w-6 rounded-full bg-gray-700"
          />
          <motion.span
            animate={open ? { rotate: -45, y: -6 } : { rotate: 0, y: 0 }}
            className="h-0.5 w-6 rounded-full bg-gray-700"
          />
        </button>
      </div>

      <AnimatePresence>
        {open && (
          <motion.div
            variants={menuVariants}
            initial="hidden"
            animate="visible"
            exit="exit"
            className="fixed inset-0 z-40 flex origin-top flex-col items-center justify-center gap-8 bg-white md:hidden"
            style={{ transformOrigin: 'top center' }}
          >
            {links.map((l) => (
              <motion.a
                key={l.href}
                variants={linkVariants}
                href={l.href}
                onClick={close}
                className="text-2xl font-semibold text-gray-900 no-underline transition-colors hover:text-violet-600"
              >
                {l.label}
              </motion.a>
            ))}
            <motion.a
              variants={linkVariants}
              href="https://wa.me/584247253544?text=Hola%2C%20quiero%20informaci%C3%B3n%20sobre%20TiendaPOS"
              target="_blank"
              rel="noopener"
              onClick={close}
              className="mt-4 rounded-full bg-gradient-to-r from-violet-500 to-violet-700 px-10 py-3.5 text-lg font-semibold text-white no-underline shadow-lg"
            >
              Contáctanos
            </motion.a>
          </motion.div>
        )}
      </AnimatePresence>
    </header>
  );
}
