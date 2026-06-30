import Image from "next/image";
import Link from "next/link";
import type { PartnerCard } from "@/lib/nav";

type Props = {
  partners: PartnerCard[];
  heading: string;
};

/**
 * Featured-partners band for the festival landing: an even grid of quiet logo
 * tiles. Renders nothing when there are no featured partners, so the page
 * stays clean until the admin marks some with "Show on festival landing".
 */
export default function PartnersStrip({ partners, heading }: Props) {
  if (!partners.length) return null;

  return (
    <section className="relative w-full overflow-hidden border-t border-white/5 bg-black px-6 py-20 text-white md:py-28">
      {/* Ambient background glow */}
      <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_center,rgba(253,224,71,0.045)_0%,transparent_70%)]" />

      <div className="relative z-10 mx-auto w-full max-w-6xl">
        <div className="mb-12 flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
          <div className="flex flex-col gap-1">
            <p className="text-[10px] font-bold uppercase tracking-[0.25em] text-yellow-300">
              Tbilisi Style 21
            </p>
            <h2 className="font-heading text-2xl font-extrabold uppercase tracking-wide text-white md:text-3xl">
              {heading}
            </h2>
          </div>
          <Link
            href="/partners"
            className="font-heading group inline-flex items-center gap-2 text-xs font-bold uppercase tracking-[0.2em] text-white/50 transition duration-300 hover:text-yellow-300"
          >
            <span>{heading}</span>
            <span aria-hidden className="transition-transform duration-300 group-hover:translate-x-1.5">
              →
            </span>
          </Link>
        </div>

        <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
          {partners.map((partner) => {
            const inner = partner.logoUrl ? (
              <Image
                src={partner.logoUrl}
                alt={partner.name}
                width={240}
                height={120}
                className="max-h-16 w-auto object-contain opacity-70 transition-all duration-500 group-hover:scale-105 group-hover:opacity-100"
              />
            ) : (
              <div className="flex flex-col items-center gap-1.5">
                <span className="px-4 text-center text-xs font-extrabold uppercase tracking-[0.25em] text-white/60 transition-all duration-500 group-hover:tracking-[0.3em] group-hover:text-white">
                  {partner.name}
                </span>
                <span className="h-1 w-1 rounded-full bg-yellow-300/0 transition-all duration-500 group-hover:scale-125 group-hover:bg-yellow-300" />
              </div>
            );

            const tileClass =
              "group flex h-28 items-center justify-center rounded-2xl border border-white/5 bg-white/[0.01] p-6 backdrop-blur-md transition-all duration-500 hover:-translate-y-1.5 hover:border-yellow-300/30 hover:bg-white/[0.03] hover:shadow-[0_10px_25px_-5px_rgba(253,224,71,0.06)] md:h-32";

            return partner.website ? (
              <a
                key={partner.id}
                href={partner.website}
                target="_blank"
                rel="noopener noreferrer"
                aria-label={partner.name}
                title={partner.name}
                className={tileClass}
              >
                {inner}
              </a>
            ) : (
              <div key={partner.id} aria-label={partner.name} title={partner.name} className={tileClass}>
                {inner}
              </div>
            );
          })}
        </div>
      </div>
    </section>
  );
}
