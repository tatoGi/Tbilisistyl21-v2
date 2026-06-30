import { getPage } from "./pages";
import { getCurrentLocale } from "./locale";
import { t } from "./utils";

/**
 * Admin-managed content for a bespoke page, extracted from its CMS blocks and
 * localized for the current request. Everything is optional: the bespoke React
 * page uses these values when present and otherwise keeps its own i18n copy and
 * static images, so the design is preserved either way.
 */
export type PageContent = {
  /** Localized page title (empty when the admin left it blank). */
  title: string;
  /** Raw localized text of each Text block, in order (newlines preserved). */
  texts: string[];
  /** Paragraphs split out of every Text block (on blank lines), in order. */
  paragraphs: string[];
  /** Absolute image URLs from Image/Gallery blocks, in order. */
  images: string[];
};

function absolute(url: unknown): string | null {
  if (typeof url !== "string" || !url) return null;
  if (url.startsWith("http://") || url.startsWith("https://")) return url;
  // Admin uploads live on the backend (/storage/...); seeded references to the
  // frontend's own assets (/images/...) are served locally and pass through.
  if (url.startsWith("/storage/") || url.startsWith("/")) return url;
  return `/storage/${url}`;
}

export async function getPageContent(slug: string): Promise<PageContent> {
  const locale = await getCurrentLocale();
  const page = await getPage(slug);

  if (!page) {
    return { title: "", texts: [], paragraphs: [], images: [] };
  }

  const texts: string[] = [];
  const paragraphs: string[] = [];
  const images: string[] = [];

  for (const block of page.blocks) {
    const data = block.data ?? {};
    if (block.type === "richText") {
      const text = t(data.content as Record<string, string> | undefined, locale).trim();
      if (text) texts.push(text);
      for (const part of text.split(/\n{2,}/).map((s) => s.trim())) {
        if (part) paragraphs.push(part);
      }
    } else if (block.type === "image") {
      const src = absolute(data.image);
      if (src) images.push(src);
    } else if (block.type === "gallery" && Array.isArray(data.images)) {
      for (const item of data.images as Record<string, unknown>[]) {
        const src = absolute(item.image);
        if (src) images.push(src);
      }
    }
  }

  return { title: t(page.title, locale), texts, paragraphs, images };
}
