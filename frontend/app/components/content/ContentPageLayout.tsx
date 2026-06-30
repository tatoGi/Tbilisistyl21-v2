import Image, { type StaticImageData } from "next/image";
import type { ReactNode } from "react";

type Props = {
  title: string;
  subtitle?: string;
  eyebrow?: string;
  heroImage?: string | StaticImageData;
  children: ReactNode;
  /** prose max width — default 3xl, use 6xl for grids/galleries */
  contentWidth?: "prose" | "wide" | "full";
};

const widthClass = {
  prose: "max-w-3xl",
  wide: "max-w-6xl",
  full: "max-w-7xl",
};

export default function ContentPageLayout({
  title,
  subtitle,
  eyebrow,
  heroImage,
  children,
  contentWidth = "prose",
}: Props) {
  return (
    <main className="min-h-screen w-full bg-black text-white">
      {heroImage ? (
        <section className="relative min-h-[40vh] w-full overflow-hidden sm:min-h-[44vh]">
          <Image
            src={heroImage}
            alt=""
            fill
            priority
            sizes="100vw"
            className="object-cover object-center"
          />
          <div className="absolute inset-0 bg-gradient-to-b from-black/45 via-black/35 to-black" />
          <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_70%_60%_at_50%_40%,transparent_0%,rgba(0,0,0,0.5)_100%)]" />

          <div className="relative z-10 flex min-h-[40vh] flex-col items-center justify-end px-6 pb-12 pt-28 text-center sm:min-h-[44vh] sm:pb-14 sm:pt-32">
            {eyebrow ? (
              <p className="font-heading mb-2 text-[10px] font-bold uppercase tracking-[0.32em] text-yellow-300/90 sm:text-[11px]">
                {eyebrow}
              </p>
            ) : null}
            <h1 className="font-heading text-[clamp(1.75rem,5.5vw,3.5rem)] font-extrabold uppercase leading-[0.95] tracking-[0.05em]">
              {title}
            </h1>
            {subtitle ? (
              <p className="font-heading mt-3 max-w-2xl text-sm font-semibold uppercase tracking-[0.16em] text-white/70 sm:text-base">
                {subtitle}
              </p>
            ) : null}
          </div>
        </section>
      ) : (
        <header className="border-b border-white/10 bg-gradient-to-b from-[#0c0c0c] to-black px-6 pb-12 pt-28 text-center sm:pt-32">
          {eyebrow ? (
            <p className="font-heading mb-2 text-[10px] font-bold uppercase tracking-[0.32em] text-yellow-300/90">
              {eyebrow}
            </p>
          ) : null}
          <h1 className="font-heading text-[clamp(1.75rem,5.5vw,3.25rem)] font-extrabold uppercase leading-[0.95] tracking-[0.05em]">
            {title}
          </h1>
          {subtitle ? (
            <p className="font-heading mx-auto mt-3 max-w-2xl text-sm font-semibold uppercase tracking-[0.16em] text-white/65">
              {subtitle}
            </p>
          ) : null}
        </header>
      )}

      <div
        className={`mx-auto px-6 py-14 sm:py-16 ${widthClass[contentWidth]} ${
          heroImage ? "" : "pt-4"
        }`}
      >
        {children}
      </div>
    </main>
  );
}
