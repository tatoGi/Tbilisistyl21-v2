/**
 * Helpers for translated content. Kept for compatibility with components that
 * were written against per-locale suffixed fields. The Laravel API returns
 * translatable fields as JSON objects, so most call sites use `t()` in
 * `lib/utils` instead — this remains for any component still importing it.
 */

export const CONTENT_LOCALES = ["ka", "en", "ru", "ua"] as const;
export type ContentLocale = (typeof CONTENT_LOCALES)[number];
export const CONTENT_FALLBACK_LOCALE: ContentLocale = "ka";

export function pickField<T = unknown>(
  doc: Record<string, unknown> | null | undefined,
  base: string,
  locale: string,
): T | undefined {
  if (!doc) return undefined;
  const value = doc[`${base}_${locale}`];
  if (value !== undefined && value !== null && value !== "") {
    return value as T;
  }
  const fallback = doc[`${base}_${CONTENT_FALLBACK_LOCALE}`];
  return (fallback === null ? undefined : fallback) as T | undefined;
}

export function pickLocalized<T = unknown>(
  doc: Record<string, unknown> | null | undefined,
  base: string,
  locale: string,
): T | undefined {
  if (!doc) return undefined;
  if (locale !== CONTENT_FALLBACK_LOCALE) {
    const value = doc[`${base}_${locale}`];
    if (value !== undefined && value !== null && value !== "") {
      return value as T;
    }
  }
  const fallback = doc[base];
  return (fallback === null ? undefined : fallback) as T | undefined;
}
