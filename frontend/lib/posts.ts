import { api } from "./api";
import { getCurrentLocale } from "./locale";
import { t } from "./utils";
import type { PageBlock } from "./pages";
import type { Post as ApiPost } from "./types";

export type NewsItem = {
  id: string;
  title: string;
  slug: string;
  coverUrl: string | null;
  excerpt: string | null;
  publishedAt: string | null;
};

export type NewsPost = NewsItem & { blocks: PageBlock[] };

function coverUrlOf(cover: unknown): string | null {
  if (typeof cover !== "string" || !cover) return null;
  if (cover.startsWith("http://") || cover.startsWith("https://")) return cover;
  if (cover.startsWith("/")) return cover;
  return `/storage/${cover}`;
}

/** All published posts, most recent first (backs the /news list). */
export async function listNews(): Promise<NewsItem[]> {
  const locale = await getCurrentLocale();
  try {
    const res = await api<{ data: ApiPost[] }>("/api/posts", { locale, fresh: true });
    const data = Array.isArray(res?.data) ? res.data : [];
    return data.map((p) => ({
      id: String(p.id),
      title: t(p.title, locale),
      slug: (p.slug ?? "").trim(),
      coverUrl: coverUrlOf(p.cover),
      excerpt: t(p.excerpt, locale) || null,
      publishedAt: p.created_at ?? null,
    }));
  } catch {
    return [];
  }
}

/** A single published post by slug (backs /news/[slug]); null when not found. */
export async function getNewsPost(slug: string): Promise<NewsPost | null> {
  const locale = await getCurrentLocale();
  try {
    const res = await api<{ data: ApiPost }>(`/api/posts/${encodeURIComponent(slug)}`, {
      locale,
      fresh: true,
    });
    const p = res?.data;
    if (!p) return null;
    return {
      id: String(p.id),
      title: t(p.title, locale),
      slug: p.slug ?? "",
      coverUrl: coverUrlOf(p.cover),
      excerpt: t(p.excerpt, locale) || null,
      publishedAt: p.created_at ?? null,
      blocks: Array.isArray(p.content_blocks) ? (p.content_blocks as PageBlock[]) : [],
    };
  } catch {
    return null;
  }
}

/** DD Mon YYYY, or "" for a missing/invalid date. */
export function formatNewsDate(value: string | null): string {
  if (!value) return "";
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return "";
  return date.toLocaleDateString("en-GB", { day: "2-digit", month: "short", year: "numeric" });
}
