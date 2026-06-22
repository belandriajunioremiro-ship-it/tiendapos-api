import { FaWhatsapp, FaInstagram, FaFacebook, FaTiktok } from 'react-icons/fa';

export default function SocialIcons() {
  return (
    <div class="flex items-center justify-center gap-4">
      <a href="https://wa.me/584247253544" target="_blank" class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-white/70 transition-all hover:bg-[#25d366] hover:text-white hover:shadow-lg" aria-label="WhatsApp">
        <FaWhatsapp size={16} />
      </a>
      <a href="#" class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-white/70 transition-all hover:bg-violet-500 hover:text-white" aria-label="Instagram">
        <FaInstagram size={16} />
      </a>
      <a href="#" class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-white/70 transition-all hover:bg-blue-500 hover:text-white" aria-label="Facebook">
        <FaFacebook size={16} />
      </a>
      <a href="#" class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-white/70 transition-all hover:bg-black hover:text-white" aria-label="TikTok">
        <FaTiktok size={16} />
      </a>
    </div>
  );
}
