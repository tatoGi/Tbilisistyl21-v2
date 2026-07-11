import type { Metadata } from "next";
import Link from "next/link";
import Image from "next/image";
import { getFeaturedPartners, type PartnerCard, type PartnerTier } from "@/lib/nav";

export const dynamic = "force-dynamic";

export const metadata: Metadata = {
  title: "პარტნიორები — Tbilisi Style 21",
};

/** A single light logo tile (logos need a light background for contrast). */
function LogoTile({
  partner,
  size,
}: {
  partner: PartnerCard;
  size: "title" | "official" | "media";
}) {
  const tile =
    size === "title"
      ? "rounded-[20px] p-16 h-auto"
      : size === "official"
        ? "rounded-2xl p-7 h-[140px]"
        : "rounded-[14px] p-[18px] h-24";
  const logoMax = size === "title" ? 180 : size === "official" ? 80 : 56;

  const inner = partner.logoUrl ? (
    <Image
      src={partner.logoUrl}
      alt={partner.name}
      width={520}
      height={logoMax}
      style={{ maxHeight: logoMax }}
      className="w-auto object-contain"
    />
  ) : (
    <span className="px-2 text-center text-sm font-bold uppercase tracking-[0.08em] text-[#5a5142]">
      {partner.name}
    </span>
  );

  const cls = `flex items-center justify-center bg-[#f5f0e6] transition duration-200 hover:-translate-y-0.5 ${tile}`;

  return partner.website ? (
    <a href={partner.website} target="_blank" rel="noopener noreferrer" title={partner.name} className={cls}>
      {inner}
    </a>
  ) : (
    <div title={partner.name} className={cls}>
      {inner}
    </div>
  );
}

function SectionLabel({ children }: { children: React.ReactNode }) {
  return (
    <div className="mb-5 flex items-center gap-3">
      <div className="font-unbounded whitespace-nowrap text-xs font-bold uppercase tracking-[0.18em] text-[#e8b84b]">
        {children}
      </div>
      <div className="h-px flex-1 bg-white/10" />
    </div>
  );
}

export default async function PartnersPage() {
  const partners = await getFeaturedPartners();
  const byTier = (tier: PartnerTier) => partners.filter((p) => p.tier === tier);
  const title = byTier("title");
  const official = byTier("official");
  const media = byTier("media");

  return (
    <main className="relative min-h-screen w-full overflow-hidden bg-[#0b0906] text-[color:var(--ts-body)]">
      <div className="pointer-events-none absolute left-1/2 top-[-200px] z-0 h-[600px] w-[1200px] max-w-full -translate-x-1/2 rounded-full bg-[radial-gradient(ellipse_at_center,rgba(232,184,75,0.25)_0%,transparent_70%)] opacity-35" />

      <div className="relative z-10 mx-auto w-full max-w-[1440px] px-6 pb-28 pt-28 md:px-14">
        {/* Hero */}
        <div className="mb-14">
          <div className="mb-5 flex items-center gap-3">
            <span className="h-[2px] w-[34px] bg-[#e8b84b]" />
            <span className="font-unbounded text-[13px] font-bold uppercase tracking-[0.22em] text-[#e8b84b]">
              Tbilisi Style 21
            </span>
          </div>
          <h1 className="font-unbounded text-[clamp(2.4rem,6vw,4.25rem)] font-extrabold tracking-[-0.01em] text-[color:var(--ts-head)]">
            პარტნიორები
          </h1>
          <p className="mt-4 max-w-[620px] text-[18px] leading-relaxed text-[color:var(--ts-muted)]">
            ბრენდები და მედია, რომლებიც გვერდში გვედგნენ და ერთად ვქმნით ტბილისის ყველაზე დიდ ღამის ფესტივალს.
          </p>
        </div>

        {partners.length === 0 ? (
          <p className="text-white/50">პარტნიორები ჯერ არ დამატებულა.</p>
        ) : (
          <>
            {title.length ? (
              <section className="mb-12">
                <SectionLabel>მთავარი პარტნიორი</SectionLabel>
                <div className="grid gap-5">
                  {title.map((p) => (
                    <LogoTile key={p.id} partner={p} size="title" />
                  ))}
                </div>
              </section>
            ) : null}

            {official.length ? (
              <section className="mb-12">
                <SectionLabel>ოფიციალური პარტნიორები</SectionLabel>
                <div className="grid grid-cols-2 gap-5 sm:grid-cols-3 lg:grid-cols-4">
                  {official.map((p) => (
                    <LogoTile key={p.id} partner={p} size="official" />
                  ))}
                </div>
              </section>
            ) : null}

            {media.length ? (
              <section className="mb-12">
                <SectionLabel>მედია პარტნიორები</SectionLabel>
                <div className="grid grid-cols-3 gap-4 sm:grid-cols-4 lg:grid-cols-6">
                  {media.map((p) => (
                    <LogoTile key={p.id} partner={p} size="media" />
                  ))}
                </div>
              </section>
            ) : null}
          </>
        )}

        {/* Become a partner CTA */}
        <div className="mt-4 flex flex-wrap items-center justify-between gap-8 rounded-[24px] border border-white/[0.12] bg-[linear-gradient(160deg,rgba(232,184,75,0.1),rgba(255,255,255,0.02))] p-10 sm:p-14">
          <div>
            <div className="font-unbounded mb-2.5 text-[26px] font-bold text-[color:var(--ts-head)]">
              გახდი Tbilisi Style-ის პარტნიორი
            </div>
            <div className="max-w-[480px] text-[15px] text-[color:var(--ts-muted)]">
              დაუკავშირდი ჩვენს გუნდს პარტნიორობის შესაძლებლობებზე და მოიპოვე წვდომა 5000+ ვიზიტორზე.
            </div>
          </div>
          <Link
            href="/dashboard/contactUs"
            className="inline-flex items-center gap-2 whitespace-nowrap rounded-full bg-[#e8b84b] px-8 py-4 text-sm font-bold text-[#1a1206] transition duration-300 hover:bg-white"
          >
            დაგვიკავშირდი <span>↗</span>
          </Link>
        </div>
      </div>
    </main>
  );
}
