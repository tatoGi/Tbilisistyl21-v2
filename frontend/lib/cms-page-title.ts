import { getPageByRoute } from "./pages";
import { getCurrentLocale } from "./locale";
import { t } from "./utils";

/** Page title from the admin for functional routes (shop, tickets, …). */
export async function getCmsPageTitle(routePath: string): Promise<string> {
  const page = await getPageByRoute(routePath);
  if (!page) return "";
  const locale = await getCurrentLocale();
  return t(page.title, locale);
}
