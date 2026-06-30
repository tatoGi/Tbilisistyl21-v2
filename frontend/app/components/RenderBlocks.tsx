import Image from "next/image";
import Link from "next/link";
import { t } from "@/lib/utils";
import type { PageBlock } from "@/lib/pages";
import type { SiteContact } from "@/lib/nav";

/** Resolve an image value coming from the API (absolute, or /storage-relative). */
function imageSrc(value: unknown): string | null {
  if (typeof value !== "string" || !value) return null;
  if (value.startsWith("http://") || value.startsWith("https://")) return value;
  if (value.startsWith("/")) return value;
  return `/storage/${value}`;
}

type Localized = Record<string, string> | undefined;

type RenderBlocksProps = {
  blocks: PageBlock[];
  locale: string;
  contact?: SiteContact;
};

export function RenderBlocks({ blocks, locale, contact }: RenderBlocksProps) {
  if (!blocks?.length) return null;

  return (
    <div className="mx-auto flex max-w-4xl flex-col gap-12 px-6">
      {blocks.map((block, i) => (
        <BlockRenderer key={i} block={block} locale={locale} contact={contact} />
      ))}
    </div>
  );
}

function BlockRenderer({
  block,
  locale,
  contact,
}: {
  block: PageBlock;
  locale: string;
  contact?: SiteContact;
}) {
  const data = block.data ?? {};

  switch (block.type) {
    case "hero": {
      const heading = t(data.heading as Localized, locale);
      const subheading = t(data.subheading as Localized, locale);
      const ctaLabel = t(data.ctaLabel as Localized, locale);
      const ctaHref = typeof data.ctaHref === "string" ? data.ctaHref : "";
      const bg = imageSrc(data.image);
      return (
        <section className="relative overflow-hidden rounded-2xl border border-white/10 bg-gradient-to-b from-[#111] to-black px-6 py-16 text-center">
          {bg ? (
            <Image src={bg} alt="" fill className="object-cover opacity-30" sizes="100vw" />
          ) : null}
          <div className="relative">
            {heading ? (
              <h2 className="font-heading text-[clamp(1.6rem,5vw,3rem)] font-extrabold uppercase tracking-[0.05em] text-white">
                {heading}
              </h2>
            ) : null}
            {subheading ? (
              <p className="mx-auto mt-4 max-w-2xl text-white/70">{subheading}</p>
            ) : null}
            {ctaLabel && ctaHref ? (
              <Link
                href={ctaHref}
                className="font-heading mt-7 inline-block rounded-full bg-yellow-300 px-8 py-3 text-sm font-bold uppercase tracking-[0.12em] text-black transition hover:bg-yellow-200"
              >
                {ctaLabel}
              </Link>
            ) : null}
          </div>
        </section>
      );
    }

    case "richText": {
      const content = t(data.content as Localized, locale);
      if (!content) return null;
      return (
        <div className="whitespace-pre-line text-[1.05rem] leading-8 text-white/80">
          {content}
        </div>
      );
    }

    case "image": {
      const src = imageSrc(data.image);
      if (!src) return null;
      const caption = t(data.caption as Localized, locale);
      const contained = data.width === "contained";
      return (
        <figure className={contained ? "mx-auto max-w-2xl" : ""}>
          <div className="relative aspect-[16/9] overflow-hidden rounded-2xl border border-white/10">
            <Image src={src} alt={caption || ""} fill className="object-cover" sizes="100vw" />
          </div>
          {caption ? (
            <figcaption className="mt-2 text-center text-sm text-white/50">{caption}</figcaption>
          ) : null}
        </figure>
      );
    }

    case "gallery": {
      const images = Array.isArray(data.images) ? (data.images as Record<string, unknown>[]) : [];
      const cols = String(data.columns ?? "3");
      const gridCols = cols === "2" ? "sm:grid-cols-2" : cols === "4" ? "sm:grid-cols-4" : "sm:grid-cols-3";
      if (!images.length) return null;
      return (
        <div className={`grid grid-cols-1 gap-4 ${gridCols}`}>
          {images.map((item, idx) => {
            const src = imageSrc(item.image);
            if (!src) return null;
            const caption = t(item.caption as Localized, locale);
            return (
              <figure key={idx}>
                <div className="relative aspect-square overflow-hidden rounded-xl border border-white/10">
                  <Image src={src} alt={caption || ""} fill className="object-cover" sizes="33vw" />
                </div>
                {caption ? (
                  <figcaption className="mt-1 text-center text-xs text-white/50">{caption}</figcaption>
                ) : null}
              </figure>
            );
          })}
        </div>
      );
    }

    case "cta": {
      const label = t(data.label as Localized, locale);
      const href = typeof data.href === "string" ? data.href : "";
      if (!label || !href) return null;
      return (
        <div className="text-center">
          <Link
            href={href}
            className="font-heading inline-block rounded-full bg-yellow-300 px-10 py-3 text-sm font-bold uppercase tracking-[0.12em] text-black transition hover:bg-yellow-200"
          >
            {label}
          </Link>
        </div>
      );
    }

    case "contact": {
      const showPayments = data.showPayments !== false;
      return (
        <section className="rounded-2xl border border-white/10 bg-white/5 p-8">
          <div className="grid gap-4 sm:grid-cols-3">
            {contact?.phone ? (
              <a href={`tel:${contact.phoneHref}`} className="block rounded-xl bg-black/30 p-4 text-center transition hover:bg-black/50">
                <span className="block text-xs uppercase tracking-widest text-yellow-300/80">Phone</span>
                <span className="mt-1 block text-white">{contact.phone}</span>
              </a>
            ) : null}
            {contact?.email ? (
              <a href={`mailto:${contact.email}`} className="block rounded-xl bg-black/30 p-4 text-center transition hover:bg-black/50">
                <span className="block text-xs uppercase tracking-widest text-yellow-300/80">Email</span>
                <span className="mt-1 block break-all text-white">{contact.email}</span>
              </a>
            ) : null}
            {contact?.address ? (
              <div className="block rounded-xl bg-black/30 p-4 text-center">
                <span className="block text-xs uppercase tracking-widest text-yellow-300/80">Address</span>
                <span className="mt-1 block text-white">{contact.address}</span>
              </div>
            ) : null}
          </div>
          {showPayments ? (
            <div className="mt-6 flex items-center justify-center gap-4 opacity-80">
              <Image src="/paymentLogo/Visa_Brandmark_White_RGB_2021.png" alt="Visa" width={64} height={40} className="h-8 w-auto" />
              <Image src="/paymentLogo/ms_hrz_opt_rev_75_3x.png" alt="Mastercard" width={64} height={40} className="h-8 w-auto" />
            </div>
          ) : null}
        </section>
      );
    }

    default:
      return null;
  }
}
