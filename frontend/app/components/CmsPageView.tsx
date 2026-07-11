import Link from "next/link";
import { notFound } from "next/navigation";
import { RenderBlocks } from "@/app/components/RenderBlocks";
import { getSiteContact } from "@/lib/nav";
import { getCurrentLocale } from "@/lib/locale";
import { getPage, getPageByRoute } from "@/lib/pages";
import { t } from "@/lib/utils";
import { getTranslations } from "next-intl/server";

type CmsPageViewProps = {
  slug?: string;
  routePath?: string;
};

/** Renders a published CMS page from the admin (Pages). No static fallbacks. */
export default async function CmsPageView({ slug, routePath }: CmsPageViewProps) {
  const page = routePath
    ? await getPageByRoute(routePath)
    : slug
      ? await getPage(slug)
      : null;

  if (!page?.isPublished) {
    notFound();
  }

  const locale = await getCurrentLocale();
  const contact = await getSiteContact();
  const title = t(page.title, locale);
  const translations = await getTranslations("contactUs");
  const backLabel = translations("backToFestival");

  return (
    <main className="relative min-h-screen overflow-hidden bg-[#0b0906] text-[color:var(--ts-body)]">
      {/* Ambient gold glow (redesign) */}
      <div className="pointer-events-none absolute left-1/2 top-[-200px] z-0 h-[600px] w-[1200px] max-w-full -translate-x-1/2 rounded-full bg-[radial-gradient(ellipse_at_center,rgba(232,184,75,0.33)_0%,transparent_70%)] opacity-35" />

      <div className="relative z-10">
        {title ? (
          <header className="mx-auto max-w-[1440px] px-8 pt-28 sm:pt-32 ts-fade-up">
            {/* Back-arrow button */}
            <div className="mb-8 flex justify-start">
              <Link
                href="/dashboard/festival"
                className="group inline-flex items-center gap-2 text-xs font-bold uppercase tracking-[0.22em] text-[color:var(--ts-muted)] transition-colors duration-300 hover:text-[color:var(--ts-gold)]"
              >
                <span className="inline-block transition-transform duration-300 group-hover:-translate-x-1">
                  ←
                </span>
                <span>{backLabel}</span>
              </Link>
            </div>

            {/* Eyebrow */}
            <div className="mb-5 flex items-center gap-3">
              <span className="h-[2px] w-[34px] bg-[color:var(--ts-gold)]" />
              <span className="font-unbounded text-[13px] font-bold uppercase tracking-[0.22em] text-[color:var(--ts-gold)]">
                Tbilisi Style 21
              </span>
            </div>
            <h1 className="font-unbounded max-w-[900px] text-[clamp(2.4rem,6vw,4.75rem)] font-extrabold leading-[1.02] tracking-[-0.01em] text-[color:var(--ts-head)]">
              {title}
            </h1>
          </header>
        ) : null}

        <div className="pb-28 pt-16">
          <RenderBlocks blocks={page.blocks} locale={locale} contact={contact} />
        </div>
      </div>
    </main>
  );
}
