import { getCurrentLocale } from "./locale";
import { getSiteSettings } from "./site-settings";
import { t } from "./utils";

export type LandingContent = {
  title: string;
  subtitle: string;
  buttonLabel: string;
  /** Background image URL from admin Site settings, or null to use the bundled fallback. */
  imageUrl: string | null;
};

/** Literals from the original TbilisiStyle21 splash — used until an admin fills the CMS fields. */
const FALLBACK = {
  title: "TBILISI STYLE 21",
  subtitle: "ELECTRONIC MUSIC FESTIVAL",
  buttonLabel: "ENTER THE ENERGY",
} as const;

function resolveStorageUrl(path: unknown): string | null {
  if (typeof path !== "string" || !path.trim()) return null;
  if (path.startsWith("http://") || path.startsWith("https://")) return path;
  if (path.startsWith("/")) return path;
  return `/storage/${path.replace(/^\/+/, "")}`;
}

/** Landing splash copy and image for the site root (/) — admin Site settings only. */
export async function getLandingContent(): Promise<LandingContent> {
  const locale = await getCurrentLocale();
  const { landing } = await getSiteSettings();

  return {
    title: t(landing?.title, locale) || FALLBACK.title,
    subtitle: t(landing?.subtitle, locale) || FALLBACK.subtitle,
    buttonLabel: t(landing?.buttonLabel, locale) || FALLBACK.buttonLabel,
    imageUrl: resolveStorageUrl(landing?.image),
  };
}
