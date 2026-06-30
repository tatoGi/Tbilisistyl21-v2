import type { Metadata } from "next";
import { notFound, redirect } from "next/navigation";
import { getPage } from "@/lib/pages";
import { getSiteContact } from "@/lib/nav";
import { getCurrentLocale } from "@/lib/locale";
import { t } from "@/lib/utils";
import { RenderBlocks } from "@/app/components/RenderBlocks";

export const dynamic = "force-dynamic";

type PageProps = { params: Promise<{ slug: string }> };

export async function generateMetadata({ params }: PageProps): Promise<Metadata> {
  const { slug } = await params;
  const page = await getPage(slug);
  if (!page) return {};
  const locale = await getCurrentLocale();
  return { title: t(page.title, locale) };
}

export default async function CmsPage({ params }: PageProps) {
  const { slug } = await params;
  const page = await getPage(slug);

  // Pages that live on a fixed React route (route_path) redirect there.
  if (page?.routePath) {
    redirect(page.routePath);
  }

  if (!page) {
    notFound();
  }

  const locale = await getCurrentLocale();
  const contact = await getSiteContact();
  const title = t(page.title, locale);

  return (
    <main className="min-h-screen bg-black text-white">
      {title ? (
        <header className="border-b border-white/10 bg-gradient-to-b from-[#0c0c0c] to-black px-6 pb-10 pt-28 text-center sm:pt-32">
          <p className="font-heading mb-2 text-[10px] font-bold uppercase tracking-[0.32em] text-yellow-300/80">
            Tbilisi Style 21
          </p>
          <h1 className="font-heading text-[clamp(1.5rem,4.5vw,2.75rem)] font-extrabold uppercase tracking-[0.05em]">
            {title}
          </h1>
          <span className="mx-auto mt-5 block h-[3px] w-16 rounded-full bg-yellow-300" />
        </header>
      ) : null}
      <div className="pb-16 pt-10">
        <RenderBlocks blocks={page.blocks} locale={locale} contact={contact} />
      </div>
    </main>
  );
}
