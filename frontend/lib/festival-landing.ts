import { getCurrentLocale } from "./locale";
import { getSiteSettings } from "./site-settings";
import { t } from "./utils";

export type FestivalHeroContent = {
  badge: string;
  title: string;
  tagline: string;
  /** Background image URL from Site settings (admin). */
  imageUrl: string | null;
};

function resolveStorageUrl(path: unknown): string | null {
  if (typeof path !== "string" || !path.trim()) return null;
  if (path.startsWith("http://") || path.startsWith("https://")) return path;
  if (path.startsWith("/")) return path;
  return `/storage/${path.replace(/^\/+/, "")}`;
}

/** Hero copy and image for /dashboard/festival — admin Site settings only. */
export async function getFestivalHeroContent(): Promise<FestivalHeroContent> {
  const locale = await getCurrentLocale();
  const { hero } = await getSiteSettings();

  return {
    badge: t(hero?.badge as Record<string, string> | undefined, locale),
    title: t(hero?.heading, locale),
    tagline: t(hero?.subheading, locale),
    imageUrl: resolveStorageUrl(hero?.image),
  };
}

export type FestivalMusic = {
  url: string;
  title: string | null;
  loop: boolean;
};

export async function getFestivalMusic(): Promise<FestivalMusic | null> {
  return null;
}
