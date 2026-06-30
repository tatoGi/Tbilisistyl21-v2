import type { Metadata } from "next";
import { notFound } from "next/navigation";
import CmsPageView from "@/app/components/CmsPageView";
import { getPage } from "@/lib/pages";
import { getCurrentLocale } from "@/lib/locale";
import { t } from "@/lib/utils";

export const dynamic = "force-dynamic";

type PageProps = { params: Promise<{ slug: string }> };

export async function generateMetadata({ params }: PageProps): Promise<Metadata> {
  const { slug } = await params;
  const page = await getPage(slug);
  if (!page) return {};
  const locale = await getCurrentLocale();
  return { title: t(page.title, locale) };
}

export default async function CmsSlugPage({ params }: PageProps) {
  const { slug } = await params;
  const page = await getPage(slug);
  if (!page) notFound();
  return <CmsPageView slug={slug} />;
}
