export const locales = ["ka", "en", "ru", "ua"] as const;

export type Locale = (typeof locales)[number];

export const defaultLocale: Locale = "ka";
export const localeCookieName = "NEXT_LOCALE";

export function isLocale(locale: string | undefined): locale is Locale {
  return locales.includes(locale as Locale);
}
