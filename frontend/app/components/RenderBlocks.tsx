import type { ReactNode } from "react";
import Image from "next/image";
import Link from "next/link";
import { t } from "@/lib/utils";
import { sanitizeRichHtml } from "@/lib/sanitize";
import type { PageBlock } from "@/lib/pages";
import type { SiteContact } from "@/lib/nav";
import { LightboxProvider, LightboxTrigger, type LightboxImage } from "./Lightbox";

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

  // Every clickable image on the page is registered here, in visual order, so
  // the lightbox can page through all of them. Registration MUST happen in this
  // loop (synchronously) — not inside child components — because `images` is
  // read when <LightboxProvider> renders, before its children do.
  const images: LightboxImage[] = [];
  const register = (img: LightboxImage) => {
    images.push(img);
    return images.length - 1;
  };

  const nodes: ReactNode[] = [];
  let i = 0;

  while (i < blocks.length) {
    const block = blocks[i];
    const next = blocks[i + 1];

    if (block.type === "image" && next?.type === "richText") {
      const data = block.data ?? {};
      const src = imageSrc(data.image);
      const caption = t(data.caption as Localized, locale);
      const lightboxIndex = src ? register({ src, caption }) : null;
      nodes.push(
        <ImageTextRow
          key={i}
          imageBlock={block}
          textBlock={next}
          locale={locale}
          lightboxIndex={lightboxIndex}
        />,
      );
      i += 2;
      continue;
    }

    if (block.type === "image") {
      const data = block.data ?? {};
      const src = imageSrc(data.image);
      const caption = t(data.caption as Localized, locale);
      const lightboxIndex = src ? register({ src, caption }) : null;
      nodes.push(
        <ImageBlock key={i} data={data} src={src} caption={caption} lightboxIndex={lightboxIndex} />,
      );
      i += 1;
      continue;
    }

    if (block.type === "gallery") {
      const data = block.data ?? {};
      const raw = Array.isArray(data.images) ? (data.images as Record<string, unknown>[]) : [];
      const items = raw
        .map((it) => ({ src: imageSrc(it.image), caption: t(it.caption as Localized, locale) }))
        .filter((x): x is { src: string; caption: string } => Boolean(x.src))
        .map((x) => ({ ...x, lightboxIndex: register({ src: x.src, caption: x.caption }) }));
      nodes.push(<GalleryBlock key={i} columns={data.columns} items={items} />);
      i += 1;
      continue;
    }

    nodes.push(
      <BlockRenderer key={i} block={block} locale={locale} contact={contact} />,
    );
    i += 1;
  }

  return (
    <LightboxProvider images={images}>
      <div className="mx-auto flex max-w-[72rem] flex-col gap-20 px-6 sm:gap-24">
        {nodes}
      </div>
    </LightboxProvider>
  );
}

function ImageTextRow({
  imageBlock,
  textBlock,
  locale,
  lightboxIndex,
}: {
  imageBlock: PageBlock;
  textBlock: PageBlock;
  locale: string;
  lightboxIndex: number | null;
}) {
  const imageData = imageBlock.data ?? {};
  const textData = textBlock.data ?? {};
  const src = imageSrc(imageData.image);
  const caption = t(imageData.caption as Localized, locale);
  const content = t(textData.content as Localized, locale);

  if (!src && !content) return null;

  // Stacked, Untold-style: a large full-width image on top, a centered readable
  // text column beneath it.
  const picture = src ? (
    <div className="group relative aspect-[16/9] w-full overflow-hidden rounded-3xl shadow-2xl shadow-black/50 ring-1 ring-white/5">
      <Image
        src={src}
        alt={caption || ""}
        fill
        className="object-cover object-center transition-transform duration-700 ease-out group-hover:scale-[1.03]"
        sizes="(max-width: 1152px) 100vw, 1152px"
      />
    </div>
  ) : null;

  return (
    <section className="flex flex-col items-center gap-8">
      {picture ? (
        <figure className="w-full">
          {lightboxIndex !== null ? (
            <LightboxTrigger index={lightboxIndex}>{picture}</LightboxTrigger>
          ) : (
            picture
          )}
          {caption ? (
            <figcaption className="mt-3 text-center text-sm italic text-white/40">
              {caption}
            </figcaption>
          ) : null}
        </figure>
      ) : null}

      {content ? (
        <div
          className="rich-text mx-auto w-full max-w-2xl text-white/85"
          dangerouslySetInnerHTML={{ __html: sanitizeRichHtml(content) }}
        />
      ) : null}
    </section>
  );
}

function ImageBlock({
  data,
  src,
  caption,
  lightboxIndex,
}: {
  data: Record<string, unknown>;
  src: string | null;
  caption: string;
  lightboxIndex: number | null;
}) {
  if (!src) return null;
  const contained = data.width === "contained";
  // "full" shows the whole image at its natural aspect ratio (no crop);
  // default "cover" crops to a uniform 16:9 banner.
  const full = data.fit === "full";

  const picture = full ? (
    // Unknown intrinsic size: the width=0/height=0 + sizes pattern lets
    // next/image render responsively at the image's natural ratio.
    <div className="group relative overflow-hidden rounded-3xl shadow-2xl shadow-black/60 border border-white/10 transition-all duration-500 hover:border-[#e8b84b]/30">
      <Image
        src={src}
        alt={caption || ""}
        width={0}
        height={0}
        sizes="100vw"
        className="h-auto w-full transition-transform duration-700 ease-out group-hover:scale-[1.02]"
      />
    </div>
  ) : (
    <div className="group relative aspect-[16/9] overflow-hidden rounded-3xl shadow-2xl shadow-black/60 border border-white/10 transition-all duration-500 hover:border-[#e8b84b]/30">
      <Image
        src={src}
        alt={caption || ""}
        fill
        className="object-cover transition-transform duration-700 ease-out group-hover:scale-[1.02]"
        sizes="100vw"
      />
    </div>
  );

  return (
    // `w-full` is required: the figure is a flex item and `<Image fill>` is
    // absolutely positioned (zero intrinsic width). Without an explicit width
    // the `mx-auto` (contained) auto-margins disable flex stretch and the
    // figure collapses to 0, hiding the image.
    <figure className={contained ? "mx-auto w-full max-w-2xl" : "w-full"}>
      {lightboxIndex !== null ? (
        <LightboxTrigger index={lightboxIndex}>{picture}</LightboxTrigger>
      ) : (
        picture
      )}
      {caption ? (
        <figcaption className="mt-3 flex items-center justify-center gap-2 text-center text-sm font-semibold tracking-wide text-white/50">
          <span className="h-1.5 w-1.5 rounded-full bg-[#e8b84b]" />
          <span>{caption}</span>
        </figcaption>
      ) : null}
    </figure>
  );
}

function GalleryBlock({
  columns,
  items,
}: {
  columns: unknown;
  items: { src: string; caption: string; lightboxIndex: number }[];
}) {
  if (!items.length) return null;
  const cols = String(columns ?? "3");
  const gridCols =
    cols === "2" ? "sm:grid-cols-2" : cols === "4" ? "sm:grid-cols-4" : "sm:grid-cols-3";
  return (
    <div className={`grid grid-cols-1 gap-6 sm:gap-4 ${gridCols}`}>
      {items.map((item) => (
        <figure key={item.lightboxIndex} className="group/fig">
          <LightboxTrigger index={item.lightboxIndex}>
            <div className="group relative aspect-square overflow-hidden rounded-2xl border border-white/10 shadow-lg shadow-black/40 transition-all duration-500 hover:-translate-y-1 hover:border-[#e8b84b]/30 hover:shadow-2xl hover:shadow-[#e8b84b]/5">
              <Image
                src={item.src}
                alt={item.caption || ""}
                fill
                className="object-cover transition-transform duration-700 ease-out group-hover:scale-[1.04]"
                sizes="(max-width: 640px) 100vw, 33vw"
              />
              <div className="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100" />
            </div>
          </LightboxTrigger>
          {item.caption ? (
            <figcaption className="mt-3 flex items-center justify-center gap-1.5 text-center text-xs font-semibold tracking-wide text-white/45 transition-colors duration-300 group-hover/fig:text-white/70">
              <span className="h-1 w-1 rounded-full bg-[#e8b84b]/60" />
              <span>{item.caption}</span>
            </figcaption>
          ) : null}
        </figure>
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
        <section className="group/hero relative flex min-h-[60vh] flex-col items-center justify-center overflow-hidden rounded-3xl bg-gradient-to-b from-[#141414] to-black px-6 py-16 text-center border border-white/10 shadow-2xl">
          {bg ? (
            <>
              <Image
                src={bg}
                alt=""
                fill
                className="object-cover object-[center_30%] opacity-60 transition-transform duration-[1200ms] ease-out group-hover/hero:scale-[1.04]"
                sizes="100vw"
              />
              <span className="absolute inset-0 bg-gradient-to-t from-black via-black/45 to-black/35" />
            </>
          ) : null}
          <div className="relative z-10">
            {heading ? (
              <h2 className="font-unbounded text-[clamp(1.75rem,4.5vw,3.25rem)] font-extrabold leading-[1.05] tracking-[-0.01em] text-[color:var(--ts-head)] drop-shadow-[0_2px_15px_rgba(0,0,0,0.85)]">
                {heading}
              </h2>
            ) : null}
            {subheading ? (
              <div
                className="rich-text text-center mx-auto mt-4 max-w-2xl text-sm text-white/90 sm:text-base"
                dangerouslySetInnerHTML={{ __html: sanitizeRichHtml(subheading) }}
              />
            ) : null}
            {ctaLabel && ctaHref ? (
              <Link
                href={ctaHref}
                className="font-heading mt-8 inline-flex items-center gap-2 rounded-full bg-[#e8b84b] px-8 py-3.5 text-xs font-extrabold uppercase tracking-[0.15em] text-[#1a1206] transition-all duration-300 hover:scale-105 hover:bg-white hover:shadow-[0_0_25px_rgba(253,224,71,0.45)] active:scale-95"
              >
                <span>{ctaLabel}</span>
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  strokeWidth={3}
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  className="h-3.5 w-3.5"
                >
                  <path d="M5 12h14" />
                  <path d="m13 6 6 6-6 6" />
                </svg>
              </Link>
            ) : null}
          </div>
        </section>
      );
    }

    case "richText": {
      // Admin-authored HTML from the Filament rich-text editor. Rendered as HTML
      // and styled by the `.rich-text` rules in globals.css.
      const content = t(data.content as Localized, locale);
      if (!content) return null;
      return (
        <div
          className="rich-text w-full max-w-2xl mx-auto text-white/85"
          dangerouslySetInnerHTML={{ __html: sanitizeRichHtml(content) }}
        />
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
            className="font-heading inline-block rounded-full bg-[#e8b84b] px-10 py-3 text-sm font-bold uppercase tracking-[0.12em] text-[#1a1206] transition hover:bg-white"
          >
            {label}
          </Link>
        </div>
      );
    }

    case "contact": {
      const showPayments = data.showPayments !== false;
      return (
        <section className="mx-auto w-full max-w-4xl rounded-3xl border border-white/10 bg-gradient-to-b from-white/[0.03] to-transparent p-6 shadow-2xl backdrop-blur-md sm:p-10">
          <div className="grid gap-5 sm:grid-cols-3">
            {contact?.phone ? (
              <a
                href={`tel:${contact.phoneHref}`}
                className="group flex flex-col items-center justify-center rounded-2xl border border-white/5 bg-black/30 p-5 text-center transition duration-300 hover:border-[#e8b84b]/30 hover:bg-[#e8b84b]/[0.04] hover:-translate-y-1"
              >
                <span className="font-heading text-[10px] font-bold uppercase tracking-[0.25em] text-[#e8b84b]/80 group-hover:text-[#e8b84b]">Phone</span>
                <span className="mt-2 block font-heading text-lg font-bold text-white group-hover:text-[#e8b84b] transition-colors duration-300">{contact.phone}</span>
              </a>
            ) : null}
            {contact?.email ? (
              <a
                href={`mailto:${contact.email}`}
                className="group flex flex-col items-center justify-center rounded-2xl border border-white/5 bg-black/30 p-5 text-center transition duration-300 hover:border-[#e8b84b]/30 hover:bg-[#e8b84b]/[0.04] hover:-translate-y-1"
              >
                <span className="font-heading text-[10px] font-bold uppercase tracking-[0.25em] text-[#e8b84b]/80 group-hover:text-[#e8b84b]">Email</span>
                <span className="mt-2 block break-all font-heading text-base font-bold text-white group-hover:text-[#e8b84b] transition-colors duration-300">{contact.email}</span>
              </a>
            ) : null}
            {contact?.address ? (
              <div
                className="flex flex-col items-center justify-center rounded-2xl border border-white/5 bg-black/30 p-5 text-center transition duration-300 hover:border-white/20"
              >
                <span className="font-heading text-[10px] font-bold uppercase tracking-[0.25em] text-[#e8b84b]/80">Address</span>
                <span className="mt-2 block font-heading text-base font-bold text-white">{contact.address}</span>
              </div>
            ) : null}
          </div>
          {showPayments ? (
            <div className="mt-8 border-t border-white/5 pt-8">
              <p className="font-heading text-center text-xs font-bold uppercase tracking-[0.2em] text-white/40">Secure payments accepted</p>
              <div className="mt-5 flex items-center justify-center gap-6 opacity-60 hover:opacity-80 transition-opacity duration-300">
                <Image src="/paymentLogo/Visa_Brandmark_White_RGB_2021.png" alt="Visa" width={64} height={40} className="h-7 w-auto" />
                <Image src="/paymentLogo/ms_hrz_opt_rev_75_3x.png" alt="Mastercard" width={64} height={40} className="h-7 w-auto" />
              </div>
            </div>
          ) : null}
        </section>
      );
    }

    case "eventInfo": {
      const label = t(data.label as Localized, locale);
      const note = t(data.note as Localized, locale);
      const rawRows = Array.isArray(data.rows) ? (data.rows as Record<string, unknown>[]) : [];
      const rows = rawRows
        .map((r) => ({ k: t(r.k as Localized, locale), v: t(r.v as Localized, locale) }))
        .filter((r) => r.k || r.v);
      if (!label && !note && !rows.length) return null;
      return (
        <section className="mx-auto w-full max-w-md rounded-[20px] border border-white/10 bg-[linear-gradient(160deg,rgba(232,184,75,0.1),rgba(255,255,255,0.02))] p-8">
          {label ? (
            <div className="font-unbounded mb-5 text-xs uppercase tracking-[0.18em] text-[#e8b84b]">
              {label}
            </div>
          ) : null}
          <div className="flex flex-col gap-[18px]">
            {rows.map((r, i) => (
              <div key={i} className="flex justify-between gap-4 text-[15px]">
                <span className="text-[color:var(--ts-muted)]">{r.k}</span>
                <span className="font-semibold text-[color:var(--ts-tile)]">{r.v}</span>
              </div>
            ))}
          </div>
          {note ? (
            <>
              <div className="my-6 h-px bg-white/10" />
              <div className="text-[13px] leading-relaxed text-[color:var(--ts-body)]">{note}</div>
            </>
          ) : null}
        </section>
      );
    }

    case "tags": {
      const rawItems = Array.isArray(data.items) ? (data.items as Record<string, unknown>[]) : [];
      const items = rawItems.map((it) => t(it.label as Localized, locale)).filter(Boolean);
      if (!items.length) return null;
      return (
        <div className="mx-auto flex w-full max-w-2xl flex-wrap gap-3">
          {items.map((label, i) => (
            <span
              key={i}
              className="rounded-full border border-white/[0.12] px-[18px] py-2 text-[13px] text-[color:var(--ts-body)]"
            >
              {label}
            </span>
          ))}
        </div>
      );
    }

    default:
      return null;
  }
}
