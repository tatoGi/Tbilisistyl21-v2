import { getTranslations } from "next-intl/server";
import { getSiteSettings } from "./site-settings";
import { getCurrentLocale } from "./locale";
import { t } from "./utils";

export type FestivalHeroContent = {
  badge: string;
  title: string;
  tagline: string;
};

/**
 * Hero copy for /dashboard/festival. Title/tagline come from the admin
 * (Site settings → Festival hero) when set, otherwise fall back to the i18n
 * translations so the landing is never empty.
 */
export async function getFestivalHeroContent(): Promise<FestivalHeroContent> {
  let badge = "";
  let title = "Tbilisi Style 21";
  let tagline = "";

  try {
    const tHome = await getTranslations("home");
    const tLanding = await getTranslations("festivalLanding");
    badge = tHome("subtitle");
    title = tHome("title");
    tagline = tLanding("heroTagline");
  } catch {
    // keep defaults
  }

  try {
    const locale = await getCurrentLocale();
    const { hero } = await getSiteSettings();
    const adminTitle = t(hero?.heading, locale);
    const adminTagline = t(hero?.subheading, locale);
    if (adminTitle) title = adminTitle;
    if (adminTagline) tagline = adminTagline;
  } catch {
    // keep i18n / defaults
  }

  return { badge, title, tagline };
}

export type FestivalMusic = {
  url: string;
  title: string | null;
  loop: boolean;
};

/**
 * Background-music track for the festival landing. The site uses the
 * `MusicTrackList` playlist player instead, so this returns null.
 */
export async function getFestivalMusic(): Promise<FestivalMusic | null> {
  return null;
}
