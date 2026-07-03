import { getPage } from "./pages";
import { getCurrentLocale } from "./locale";
import { t } from "./utils";

/** Page title from the admin (by CMS slug, e.g. `shop`, `tickets`). */
export async function getCmsPageTitle(slug: string): Promise<string> {
  const page = await getPage(slug);
  if (!page) return "";
  const locale = await getCurrentLocale();
  return t(page.title, locale);
}
