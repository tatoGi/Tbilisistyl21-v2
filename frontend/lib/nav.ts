import { api } from "./api";
import { t, mediaUrl } from "./utils";
import { getCurrentLocale } from "./locale";
import { listNavPages, listFeaturedPages, pageHref, pageLabel } from "./pages";
import { getSiteSettings } from "./site-settings";
import type { Partner as ApiPartner, Post as ApiPost, Media } from "./types";

export type NavLink = { label: string; href: string };

export type SiteContact = {
  phone: string;
  phoneHref: string;
  email: string;
  address: string | null;
};

export type SocialLinks = { instagram: string | null; tiktok: string | null };

export type PartnerCard = {
  id: string;
  name: string;
  description: string | null;
  logoUrl: string | null;
  website: string | null;
};

export type NewsCard = {
  id: string;
  title: string;
  slug: string;
  excerpt: string | null;
  coverUrl: string | null;
  publishedAt: string | null;
};

/**
 * Site menu links from the admin (Pages → Show in site menu, Menu order).
 */
export async function getNavPages(): Promise<NavLink[]> {
  const locale = await getCurrentLocale();
  const pages = await listNavPages();
  return pages.map((p) => ({ label: pageLabel(p, locale), href: pageHref(p) }));
}

/**
 * Pages flagged "Feature on homepage" in the admin, shown over the festival
 * hero. Falls back to the nav pages if none are explicitly featured.
 */
export async function getFeaturedPages(): Promise<NavLink[]> {
  const locale = await getCurrentLocale();
  const featured = await listFeaturedPages();
  const pages = featured.length ? featured : await listNavPages();
  return pages.map((p) => ({ label: pageLabel(p, locale), href: pageHref(p) }));
}

export async function getSiteContact(): Promise<SiteContact> {
  const { contact } = await getSiteSettings();
  return {
    phone: contact?.phone?.trim() || "",
    phoneHref: contact?.phoneHref?.trim() || "",
    email: contact?.email?.trim() || "",
    address: contact?.address?.trim() || null,
  };
}

export async function getSocialLinks(): Promise<SocialLinks> {
  const data = await getSiteSettings();
  const instagram = typeof data.instagramUrl === "string" ? data.instagramUrl.trim() : null;
  const tiktok = typeof data.tiktokUrl === "string" ? data.tiktokUrl.trim() : null;
  return { instagram: instagram || null, tiktok: tiktok || null };
}

function logoUrlOf(logo: Media | null): string | null {
  if (logo && logo.filename) return mediaUrl(logo.filename);
  return null;
}

export async function getFeaturedPartners(): Promise<PartnerCard[]> {
  const locale = await getCurrentLocale();
  try {
    const res = await api<{ data: ApiPartner[] }>("/api/partners", { locale });
    const data = Array.isArray(res?.data) ? res.data : [];
    return data.map((p) => ({
      id: String(p.id),
      name: p.name ?? "",
      description: t(p.description, locale) || null,
      logoUrl: logoUrlOf(p.logo ?? null),
      website: (p.url ?? "").trim() || null,
    }));
  } catch {
    return [];
  }
}

export async function getFeaturedNews(limit = 6): Promise<NewsCard[]> {
  const locale = await getCurrentLocale();
  try {
    const res = await api<{ data: ApiPost[] }>("/api/posts", { locale });
    const data = Array.isArray(res?.data) ? res.data : [];
    return data.slice(0, limit).map((p) => ({
      id: String(p.id),
      title: t(p.title, locale),
      slug: p.slug ?? "",
      excerpt: null,
      coverUrl: null,
      publishedAt: p.created_at ?? null,
    }));
  } catch {
    return [];
  }
}
