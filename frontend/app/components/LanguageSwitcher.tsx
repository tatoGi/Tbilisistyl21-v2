"use client";

import { useLocale } from "next-intl";
import { useRouter } from "next/navigation";
import { useTransition } from "react";
import { defaultLocale, locales, type Locale } from "@/i18n/config";

const localeLabels: Record<Locale, string> = {
  ru: "RU",
  en: "EN",
  ka: "KA",
  ua: "UA",
};

export default function LanguageSwitcher() {
  const currentLocale = useLocale();
  const router = useRouter();
  const [isPending, startTransition] = useTransition();

  function selectLocale(locale: Locale) {
    startTransition(async () => {
      await fetch("/api/locale", {
        method: "POST",
        headers: {
          "content-type": "application/json",
        },
        body: JSON.stringify({ locale }),
      });

      router.refresh();
    });
  }

  return (
    <div
      className="flex items-center gap-1 rounded-full border border-white/50 bg-black/80 p-1 shadow-[0_4px_24px_rgba(0,0,0,0.45)] backdrop-blur-md"
      aria-label="Language selector"
    >
      {locales.map((locale) => {
        const isActive = (currentLocale || defaultLocale) === locale;

        return (
          <button
            key={locale}
            type="button"
            onClick={() => selectLocale(locale)}
            disabled={isPending}
            aria-pressed={isActive}
            className={[
              "font-sans h-9 min-w-11 rounded-full px-3.5 text-xs font-semibold tracking-wide transition-all duration-200",
              isActive
                ? "bg-yellow-300 text-black shadow-[0_0_16px_rgba(253,224,71,0.45)]"
                : "text-white hover:bg-white/15",
            ].join(" ")}
          >
            {localeLabels[locale]}
          </button>
        );
      })}
    </div>
  );
}
