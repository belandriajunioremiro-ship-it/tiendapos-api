import ScrollReveal from './ScrollReveal';

interface Props {
  badge: string;
  title: string;
  subtitle?: string;
}

export default function SectionHeader({ badge, title, subtitle }: Props) {
  return (
    <ScrollReveal className="mb-14 md:mb-18 text-center">
      <span className="mb-4 inline-block rounded-full bg-violet-100 px-4 py-1.5 text-xs font-semibold tracking-wide text-violet-700 uppercase">
        {badge}
      </span>
      <h2 className="mb-4 text-3xl font-black tracking-tight md:text-4xl lg:text-5xl">
        {title}
      </h2>
      {subtitle && (
        <p className="mx-auto max-w-2xl text-base text-gray-500 md:text-lg">
          {subtitle}
        </p>
      )}
    </ScrollReveal>
  );
}
