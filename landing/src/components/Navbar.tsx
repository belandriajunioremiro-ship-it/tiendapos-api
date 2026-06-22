import { useState, useEffect } from 'react';

const links = [
  { href: '#por-que', label: 'Por qué TiendaPOS' },
  { href: '#caracteristicas', label: 'Características' },
  { href: '#paises', label: 'Países' },
  { href: '#planes', label: 'Planes' },
];

export default function Navbar() {
  const [open, setOpen] = useState(false);
  const [scrolled, setScrolled] = useState(false);

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 20);
    window.addEventListener('scroll', onScroll, { passive: true });
    return () => window.removeEventListener('scroll', onScroll);
  }, []);

  const close = () => {
    setOpen(false);
    document.body.style.overflow = '';
  };

  const toggle = () => {
    setOpen((v) => {
      const next = !v;
      document.body.style.overflow = next ? 'hidden' : '';
      return next;
    });
  };

  return (
    <>
      {/* Mobile overlay */}
      <div
        className={`fixed inset-0 z-40 bg-black/30 backdrop-blur-sm transition-opacity duration-300 ${open ? 'opacity-100 pointer-events-auto' : 'opacity-0 pointer-events-none'}`}
        onClick={close}
      />

      {/* Mobile menu */}
      <div
        className={`fixed inset-0 z-50 flex flex-col items-center justify-center gap-3 bg-white/95 backdrop-blur-2xl transition-all duration-300 ${open ? 'opacity-100 scale-100' : 'opacity-0 scale-95 pointer-events-none'}`}
      >
        <button onClick={close} className="absolute right-5 top-4 rounded-xl p-2 text-gray-400 transition-colors hover:bg-violet-50 hover:text-violet-600" aria-label="Cerrar menú">
          <svg width="24" height="24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <line x1="18" y1="6" x2="6" y2="18" /><line x1="6" y1="6" x2="18" y2="18" />
          </svg>
        </button>
        {links.map((l) => (
          <a key={l.href} href={l.href} onClick={close} className="rounded-xl px-10 py-3 text-xl font-semibold tracking-tight text-gray-900 no-underline transition-colors hover:bg-violet-50 hover:text-violet-600">
            {l.label}
          </a>
        ))}
        <a
          href="https://wa.me/584247253544?text=Hola%2C%20quiero%20informaci%C3%B3n%20sobre%20TiendaPOS"
          target="_blank"
          onClick={close}
          className="mt-4 inline-flex items-center gap-2.5 rounded-full bg-[#25d366] px-7 py-3 text-base font-semibold text-white no-underline shadow-lg shadow-[#25d366]/30 transition-all hover:scale-105 hover:bg-[#1ebe5d]"
        >
          <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
          Contáctanos
        </a>
      </div>

      {/* Navbar */}
      <nav className={`sticky top-0 z-30 transition-all duration-300 ${scrolled ? 'bg-white/80 backdrop-blur-xl border-b border-gray-200/60' : 'bg-transparent'}`}>
        <div className="mx-auto flex max-w-[1140px] items-center justify-between px-6 py-4">
          <a href="#" className="flex items-center gap-2 text-xl font-extrabold no-underline text-[#1a1a2e]">
            <svg width="28" height="28" viewBox="0 0 28 28" fill="none" className="text-violet-600">
              <rect x="2" y="2" width="24" height="24" rx="6" stroke="currentColor" strokeWidth="2.5" />
              <path d="M9 14h10M14 9v10" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" />
            </svg>
            Tienda<span className="text-violet-600">POS</span>
          </a>

          <div className="hidden items-center gap-8 md:flex">
            {links.map((l) => (
              <a key={l.href} href={l.href} className="text-sm font-medium text-gray-600 no-underline transition-colors hover:text-violet-600">{l.label}</a>
            ))}
            <a
              href="https://wa.me/584247253544?text=Hola%2C%20quiero%20informaci%C3%B3n%20sobre%20TiendaPOS"
              target="_blank"
              className="inline-flex items-center gap-1.5 rounded-full bg-[#25d366] px-4 py-2 text-xs font-semibold text-white no-underline shadow-sm shadow-[#25d366]/30 transition-all hover:bg-[#1ebe5d] hover:shadow-md hover:shadow-[#25d366]/40"
            >
              <svg width="15" height="15" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
              Contáctanos
            </a>
          </div>

          <button onClick={toggle} className={`relative z-50 flex flex-col gap-1 border-none bg-transparent p-1.5 md:hidden ${open ? 'active' : ''}`} id="hamburgerBtn" aria-label="Menú">
            <span className={`ham-line block h-0.5 w-5.5 rounded-sm bg-[#1a1a2e] transition-all duration-300 ${open ? 'translate-y-[6.5px] rotate-45' : ''}`} />
            <span className={`ham-line block h-0.5 w-5.5 rounded-sm bg-[#1a1a2e] transition-all duration-300 ${open ? 'opacity-0' : ''}`} />
            <span className={`ham-line block h-0.5 w-5.5 rounded-sm bg-[#1a1a2e] transition-all duration-300 ${open ? '-translate-y-[6.5px] -rotate-45' : ''}`} />
          </button>
        </div>
      </nav>
    </>
  );
}
