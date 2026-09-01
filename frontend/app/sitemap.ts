import type { MetadataRoute } from "next";
import { listAllPages, pageHref } from "@/lib/pages";
import { listNews } from "@/lib/posts";

const baseUrl = (process.env.NEXT_PUBLIC_APP_URL ?? "https://tbilisistyle.com").replace(/\/$/, "");

const STATIC_ROUTES = ["/", "/dashboard/festival", "/dashboard/shop", "/dashboard/tickets", "/partners", "/news"];

export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  const [pages, news] = await Promise.all([listAllPages(), listNews()]);

  const staticEntries: MetadataRoute.Sitemap = STATIC_ROUTES.map((path) => ({
    url: `${baseUrl}${path}`,
  }));

  const pageEntries: MetadataRoute.Sitemap = pages
    .map((p) => pageHref(p))
    .filter((href) => !STATIC_ROUTES.includes(href))
    .map((href) => ({ url: `${baseUrl}${href}` }));

  const newsEntries: MetadataRoute.Sitemap = news
    .filter((n) => n.slug)
    .map((n) => ({
      url: `${baseUrl}/news/${n.slug}`,
      lastModified: n.publishedAt ?? undefined,
    }));

  return [...staticEntries, ...pageEntries, ...newsEntries];
}
