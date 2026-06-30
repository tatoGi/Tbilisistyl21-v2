import { api } from "./api";
import { t } from "./utils";
import { getCurrentLocale } from "./locale";
import type { Translatable } from "./types";

/** Block as returned by Laravel (`{ type, data }`), images already resolved. */
export type PageBlock = {
  type: string;
  data: Record<string, unknown>;
};

export type PageSummary = {
  id: string;
  slug: string;
  routePath: string | null;
  showInNav: boolean;
  navOrder: number;
  featuredOnHome: boolean;
  title: Translatable;
  navLabel: Translatable;
};

export type PageDetail = PageSummary & {
  isPublished: boolean;
  blocks: PageBlock[];
};

type ApiPageSummary = {
  id: string;
  slug: string;
  route_path: string | null;
  show_in_nav: boolean;
  nav_order: number;
  featured_on_home: boolean;
  title: Translatable;
  nav_label: Translatable;
};

type ApiPageDetail = ApiPageSummary & {
  is_published: boolean;
  content_blocks: PageBlock[];
};

function toSummary(p: ApiPageSummary): PageSummary {
  return {
    id: String(p.id),
    slug: p.slug,
    routePath: p.route_path ?? null,
    showInNav: Boolean(p.show_in_nav),
    navOrder: Number(p.nav_order ?? 0),
    featuredOnHome: Boolean(p.featured_on_home),
    title: p.title ?? {},
    navLabel: p.nav_label ?? {},
  };
}

/** The public URL for a page: its custom React route, or the CMS /{slug}. */
export function pageHref(p: { routePath: string | null; slug: string }): string {
  return p.routePath?.trim() || `/${p.slug}`;
}

/** Best label for a page in the current locale (nav label, then title). */
export function pageLabel(p: { navLabel: Translatable; title: Translatable }, locale: string): string {
  return t(p.navLabel, locale) || t(p.title, locale);
}

async function listPages(params: "nav" | "featured" | "all"): Promise<PageSummary[]> {
  const query = params === "all" ? "" : `?${params}=1`;
  try {
    const res = await api<{ data: ApiPageSummary[] }>(`/api/pages${query}`);
    const data = Array.isArray(res?.data) ? res.data : [];
    return data.map(toSummary);
  } catch {
    return [];
  }
}

export function listNavPages(): Promise<PageSummary[]> {
  return listPages("nav");
}

export function listFeaturedPages(): Promise<PageSummary[]> {
  return listPages("featured");
}

export async function getPage(slug: string): Promise<PageDetail | null> {
  const locale = await getCurrentLocale();
  try {
    const res = await api<{ data: ApiPageDetail }>(`/api/pages/${slug}`, { locale });
    const p = res?.data;
    if (!p) return null;
    return {
      ...toSummary(p),
      isPublished: Boolean(p.is_published),
      blocks: Array.isArray(p.content_blocks) ? p.content_blocks : [],
    };
  } catch {
    return null;
  }
}
