import { getLocale } from "next-intl/server";

/** Active request locale (cookie-driven), falling back to `ka`. */
export async function getCurrentLocale(): Promise<string> {
  try {
    return await getLocale();
  } catch {
    return "ka";
  }
}
