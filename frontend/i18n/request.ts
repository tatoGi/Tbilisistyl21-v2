import { cookies } from "next/headers";
import { getRequestConfig } from "next-intl/server";
import { defaultLocale, isLocale, localeCookieName } from "./config";

export default getRequestConfig(async () => {
  const store = await cookies();
  const requestedLocale = store.get(localeCookieName)?.value;
  const locale = isLocale(requestedLocale) ? requestedLocale : defaultLocale;

  return {
    locale,
    messages: (await import(`../messages/${locale}.json`)).default,
  };
});
