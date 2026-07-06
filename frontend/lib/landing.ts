import { getCurrentLocale } from "./locale";
import { getSiteSettings } from "./site-settings";
import { t } from "./utils";

export type LandingContent = {
  /** Splash title, or null when the admin hasn't filled it (element is hidden). */
  title: string | null;
  /** Splash subtitle, or null when the admin hasn't filled it (element is hidden). */
  subtitle: string | null;
  /** Festival button label, or null when the admin hasn't filled it (button is hidden). */
  buttonLabel: string | null;
  /** Background image URL from admin Site settings, or null to use the bundled fallback. */
  imageUrl: string | null;
};

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
    title: t(landing?.title, locale) || null,
    subtitle: t(landing?.subtitle, locale) || null,
    buttonLabel: t(landing?.buttonLabel, locale) || null,
    imageUrl: resolveStorageUrl(landing?.image),
  };
}
