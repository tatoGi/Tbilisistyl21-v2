import Image, { type StaticImageData } from "next/image";
import Link from "next/link";
import type { NavLink } from "@/lib/nav";

type Props = {
  image: StaticImageData;
  badge: string;
  pages: NavLink[];
  emptyPagesMessage: string;
};

export default function FestivalHero({
  image,
  badge,
  pages,
  emptyPagesMessage,
}: Props) {
  const mid = Math.ceil(pages.length / 2);
  const left = pages.slice(0, mid);
  const right = pages.slice(mid);

  return (
    <section className="relative min-h-screen w-full overflow-hidden">
      <Image
        src={image}
        alt=""
        fill
        priority
        sizes="100vw"
        className="object-cover object-center ts-hero-kenburns"
      />

      {/* Light overlay — keep the glowing figure visible */}
      <div className="absolute inset-0 bg-gradient-to-b from-black/35 via-black/15 to-black/75" />
      <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_55%_45%_at_50%_42%,transparent_0%,rgba(0,0,0,0.45)_100%)]" />

    

      {/* Featured pages — left & right hands */}
      <div className="relative z-10 flex min-h-screen items-center justify-center px-4 pb-16 pt-36 sm:px-6 sm:pb-20 sm:pt-40">
        {pages.length ? (
          <div className="grid w-full max-w-7xl grid-cols-1 gap-8 uppercase text-white md:grid-cols-2 md:gap-16 lg:gap-24">
            <div className="flex flex-col items-center gap-7 md:items-start md:gap-8 lg:gap-9">
              {left.map((item) => (
                <Link
                  key={item.href}
                  href={item.href}
                  className="group"
                >
                  <p className="font-heading text-center text-sm font-semibold tracking-[0.14em] text-white/90 transition-all duration-300 group-hover:tracking-[0.24em] group-hover:text-yellow-300 sm:text-lg sm:tracking-[0.22em] md:text-left md:text-xl lg:text-[1.35rem] xl:whitespace-nowrap">
                    {item.label}
                  </p>
                </Link>
              ))}
            </div>

            <div className="flex flex-col items-center gap-7 md:items-end md:gap-8 md:text-right lg:gap-9">
              {right.map((item) => (
                <Link
                  key={item.href}
                  href={item.href}
                  className="group"
                >
                  <p className="font-heading text-center text-sm font-semibold tracking-[0.14em] text-white/90 transition-all duration-300 group-hover:tracking-[0.24em] group-hover:text-yellow-300 sm:text-lg sm:tracking-[0.22em] md:text-right md:text-xl lg:text-[1.35rem] xl:whitespace-nowrap">
                    {item.label}
                  </p>
                </Link>
              ))}
            </div>
          </div>
        ) : (
          <p className="max-w-md text-center text-xs uppercase tracking-[0.16em] text-white/50">
            {emptyPagesMessage}
          </p>
        )}
      </div>
    </section>
  );
}
