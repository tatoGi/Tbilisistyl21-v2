import type { Metadata } from "next";
import { Bebas_Neue, Noto_Sans_Georgian, Oswald } from "next/font/google";
import { cookies } from "next/headers";
import { NextIntlClientProvider } from "next-intl";
import { getMessages } from "next-intl/server";
import { defaultLocale, isLocale, localeCookieName } from "@/i18n/config";
import { getNavPages, getSocialLinks, getSiteContact } from "@/lib/nav";
import { getFestivalMusic } from "@/lib/festival-landing";
import { listMusicTracks } from "@/lib/music-tracks";
import SiteChrome from "./components/SiteChrome";
import "./globals.css";

/** Body + Georgian/Cyrillic fallback — readable at all sizes. */
const notoSansGeorgian = Noto_Sans_Georgian({
  variable: "--font-display",
  subsets: ["georgian", "latin", "cyrillic-ext"],
  weight: ["400", "500", "600", "700", "800", "900"],
});

/** Festival poster face for Latin headlines (logo, hero, CTAs). */
const bebasNeue = Bebas_Neue({
  variable: "--font-bebas",
  subsets: ["latin"],
  weight: "400",
});

/** Cyrillic headlines (RU/UA) when Bebas has no glyph. */
const oswald = Oswald({
  variable: "--font-oswald",
  subsets: ["latin", "cyrillic"],
  weight: ["500", "600", "700"],
});

export const metadata: Metadata = {
  title: "Tbilisi Style 21",
  description: "Electronic Music Festival",
  icons: {
    icon: "/images/logo2.jpeg",
    shortcut: "/images/logo2.jpeg",
    apple: "/images/logo2.jpeg",
  },
};

export const dynamic = "force-dynamic";

export default async function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  const store = await cookies();
  const requestedLocale = store.get(localeCookieName)?.value;
  const locale = isLocale(requestedLocale) ? requestedLocale : defaultLocale;
  const messages = await getMessages();
  const [pages, social, contact, music, musicTracks] = await Promise.all([
    getNavPages(),
    getSocialLinks(),
    getSiteContact(),
    getFestivalMusic(),
    listMusicTracks({ publicOnly: true }),
  ]);

  return (
    <html
      lang={locale}
      className={`${notoSansGeorgian.variable} ${bebasNeue.variable} ${oswald.variable} h-full antialiased`}
    >
      <head>
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossOrigin="anonymous" />
        <link
          href="https://fonts.googleapis.com/css2?family=Bitcount+Single:wght@100..900&display=swap"
          rel="stylesheet"
        />
      </head>
      <body className="min-h-full flex flex-col" suppressHydrationWarning>
        <NextIntlClientProvider locale={locale} messages={messages}>
          <SiteChrome
            pages={pages}
            social={social}
            contact={contact}
            music={music}
            musicTracks={musicTracks}
          >
            {children}
          </SiteChrome>
        </NextIntlClientProvider>
      </body>
    </html>
  );
}
