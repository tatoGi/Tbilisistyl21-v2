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

/** Fixed React routes (shop UI, ticket checkout) — not plain CMS slug pages. */
const FUNCTIONAL_ROUTE_PATHS = new Set(["/dashboard/shop", "/dashboard/tickets"]);

/** Public URL: prefer CMS slug (`/main-stage`); functional pages keep their route. */
export function pageHref(p: { routePath: string | null; slug: string }): string {
  const route = p.routePath?.trim();
  if (route && FUNCTIONAL_ROUTE_PATHS.has(route)) {
    return route;
  }
  const slug = p.slug?.trim();
  if (slug) {
    return `/${slug}`;
  }
  return route || "/";
}

/** Best label for a page in the current locale (nav label, then title). */
export function pageLabel(p: { navLabel: Translatable; title: Translatable }, locale: string): string {
  return t(p.navLabel, locale) || t(p.title, locale);
}

async function listPages(params: "nav" | "featured" | "all"): Promise<PageSummary[]> {
  const query = params === "all" ? "" : `?${params}=1`;
  try {
    const res = await api<{ data: ApiPageSummary[] }>(`/api/pages${query}`, { fresh: true });
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

function toDetail(p: ApiPageDetail): PageDetail {
  return {
    ...toSummary(p),
    isPublished: Boolean(p.is_published),
    blocks: Array.isArray(p.content_blocks) ? p.content_blocks : [],
  };
}

export async function getPage(slug: string): Promise<PageDetail | null> {
  const locale = await getCurrentLocale();
  try {
    const res = await api<{ data: ApiPageDetail }>(`/api/pages/${encodeURIComponent(slug)}`, {
      locale,
      fresh: true,
    });
    const p = res?.data;
    if (!p) return null;
    return toDetail(p);
  } catch {
    return null;
  }
}

/** Load a CMS page by its fixed React route (e.g. `/dashboard/mainStage`). */
export async function getPageByRoute(routePath: string): Promise<PageDetail | null> {
  const locale = await getCurrentLocale();
  try {
    const res = await api<{ data: ApiPageDetail }>(
      `/api/pages/by-route?path=${encodeURIComponent(routePath)}`,
      { locale, fresh: true },
    );
    const p = res?.data;
    if (!p) return null;
    return toDetail(p);
  } catch {
    return null;
  }
}
