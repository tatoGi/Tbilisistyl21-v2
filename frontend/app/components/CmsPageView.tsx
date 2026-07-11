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
    <main className="relative min-h-screen overflow-hidden bg-black text-white">
      {/* Immersive background festival ambient glows */}
      <div className="pointer-events-none absolute -left-40 -top-40 z-0 h-[45rem] w-[45rem] rounded-full bg-gradient-to-tr from-yellow-400/10 via-amber-500/5 to-transparent blur-[120px] ts-ambient-orb-1" />
      <div className="pointer-events-none absolute -right-40 top-1/3 z-0 h-[50rem] w-[50rem] rounded-full bg-gradient-to-bl from-purple-600/8 via-fuchsia-500/3 to-transparent blur-[140px] ts-ambient-orb-2" />

      <div className="relative z-10">
        {title ? (
          <header className="mx-auto max-w-[72rem] px-6 pt-32 sm:pt-36 ts-fade-up">
            {/* Sleek breadcrumbs / back-arrow button */}
            <div className="mb-6 flex justify-start">
              <Link
                href="/dashboard/festival"
                className="group inline-flex items-center gap-2 text-xs font-bold uppercase tracking-[0.25em] text-white/50 transition-colors duration-300 hover:text-yellow-300"
              >
                <span className="inline-block transition-transform duration-300 group-hover:-translate-x-1">
                  ←
                </span>
                <span>{backLabel}</span>
              </Link>
            </div>

            <p className="font-heading mb-4 text-[10px] font-bold uppercase tracking-[0.4em] text-yellow-300/70">
              Tbilisi Style 21
            </p>
            <h1 className="font-heading text-[clamp(2.25rem,6vw,4rem)] font-black leading-[1.02] uppercase tracking-[0.02em] text-white drop-shadow-[0_2px_15px_rgba(253,224,71,0.15)]">
              {title}
            </h1>
            <span className="mt-8 block h-[2px] w-full bg-gradient-to-r from-yellow-300/50 via-white/10 to-transparent" />
          </header>
        ) : null}
        
        <div className="pb-24 pt-16">
          <RenderBlocks blocks={page.blocks} locale={locale} contact={contact} />
        </div>
      </div>
    </main>
  );
}
