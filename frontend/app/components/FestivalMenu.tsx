"use client";

import Image from "next/image";
import Link from "next/link";
import { useTranslations } from "next-intl";
import { usePathname } from "next/navigation";
import { useState, useEffect } from "react";
import { navItems } from "./navItems";
import LanguageSwitcher from "./LanguageSwitcher";
import SocialLinksRow from "./SocialLinks";
import type { SocialLinks } from "@/lib/nav";

export type NavLink = { label: string; href: string };

type FestivalMenuProps = {
  /** CMS-driven content links (Pages flagged "Show in site menu"). */
  pages?: NavLink[];
  /** Social profile links from Site settings (Instagram / TikTok). */
  social?: SocialLinks;
};

export default function FestivalMenu({
  pages = [],
  social = { instagram: null, tiktok: null },
}: FestivalMenuProps) {
  const t = useTranslations();
  const pathname = usePathname();
  const isFestivalPage = pathname === "/dashboard/festival";
  const [navOpen, setNavOpen] = useState(false);

  useEffect(() => {
    document.body.style.overflow = navOpen ? "hidden" : "auto";
    return () => {
      document.body.style.overflow = "auto";
    };
  }, [navOpen]);

  // Functional routes are not CMS content — keep them fixed.
  const functional: NavLink[] = [
    { label: t("nav.ticket"), href: "/dashboard/tickets" },
    { label: t("nav.shop"), href: "/dashboard/shop" },
  ];

  // CMS pages + functional links; fall back to the static list if the CMS
  // returns nothing, so the menu is never empty. Dedupe by href because the CMS
  // nav may already include the Shop/Tickets pages (seeded with their
  // /dashboard/* routePath), which would otherwise show up twice.
  const merged: NavLink[] = pages.length
    ? [...pages, ...functional]
    : navItems.map((item) => ({ label: t(`nav.${item.labelKey}`), href: item.href }));

  const seenHrefs = new Set<string>();
  const links: NavLink[] = merged.filter((link) => {
    if (seenHrefs.has(link.href)) return false;
    seenHrefs.add(link.href);
    return true;
  });

  return (
    <>
      <div className={`fixed top-0 left-0 right-0 z-30 flex items-center justify-between gap-3 px-4 py-4 sm:gap-4 sm:px-5 sm:py-5 ${isFestivalPage ? 'bg-transparent' : 'bg-black/95 backdrop-blur-md'}`}>

        <Link
          href="/dashboard/festival"
          className="flex min-w-0 shrink items-center gap-2 sm:gap-3"
          aria-label="Tbilisi Style 21"
        >
          <Image
            src="/images/logo2.jpeg"
            alt="Tbilisi Style 21"
            width={44}
            height={44}
            priority
            className="h-9 w-9 shrink-0 rounded-full object-cover ring-2 ring-white/30 sm:h-11 sm:w-11"
          />
          <span className="font-heading truncate text-[clamp(0.9rem,4.6vw,1.5rem)] font-extrabold uppercase leading-none tracking-[0.06em] text-white sm:text-2xl md:text-3xl lg:text-4xl">
            Tbilisi Style 21
          </span>
        </Link>

        {/* Language switcher (+ social icons) — centered on xl screens only.
            Below xl it lives in the drawer so the title never overlaps it. */}
        <div className="pointer-events-none absolute inset-x-0 hidden justify-center xl:flex">
          <div className="pointer-events-auto flex items-center gap-4">
            <LanguageSwitcher />
            <SocialLinksRow social={social} />
          </div>
        </div>

        <div className="ml-auto flex shrink-0 items-center gap-2 sm:gap-3">
          {/* On mobile the language switcher lives inside the drawer (below) to
              keep the top bar uncluttered. */}
          <button
            onClick={() => setNavOpen(!navOpen)}
            onMouseEnter={() => setNavOpen(true)}
            aria-label="Menu"
            className="relative z-50 flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-white/15 bg-black/25 backdrop-blur-md sm:h-9 sm:w-9"
          >
            <span
              className="absolute h-[2px] w-5 bg-white transition-transform duration-200"
              style={{
                transform: navOpen ? "rotate(45deg)" : "translateY(-6px)",
              }}
            />
            <span
              className="absolute h-[2px] w-5 bg-white transition-opacity duration-200"
              style={{ opacity: navOpen ? 0 : 1 }}
            />
            <span
              className="absolute h-[2px] w-5 bg-white transition-transform duration-200"
              style={{
                transform: navOpen ? "rotate(-45deg)" : "translateY(6px)",
              }}
            />
          </button>
        </div>
      </div>

      <div
        className="fixed inset-0 z-40 bg-black transition-opacity"
        style={{
          opacity: navOpen ? 0.6 : 0,
          pointerEvents: navOpen ? "auto" : "none",
        }}
        onClick={() => setNavOpen(false)}
      />

      {/* DRAWER */}
      <div
        className="fixed top-0 right-0 z-50 h-full w-[360px] max-w-full bg-black/90 backdrop-blur-md transition-transform"
        style={{
          transform: navOpen ? "translateX(0)" : "translateX(100%)",
        }}
        onMouseLeave={() => setNavOpen(false)}
      >
        <div className="relative flex h-full flex-col overflow-y-auto px-6 pb-8 pt-24 sm:px-8 sm:pt-28">

          <button
            onClick={() => setNavOpen(false)}
            aria-label="Close menu"
            className="absolute right-5 top-5 flex h-10 w-10 items-center justify-center sm:right-6 sm:top-6"
          >
            <span className="absolute h-[2px] w-6 rotate-45 bg-white" />
            <span className="absolute h-[2px] w-6 -rotate-45 bg-white" />
          </button>

          {/* Language switcher — shown here on mobile/tablet (xl+ uses the
              centered one in the top bar). */}
          <div className="mb-6 flex justify-center xl:hidden">
            <LanguageSwitcher />
          </div>

          <nav className="flex flex-1 flex-col justify-center gap-0">
            {links.map((item, i) => (
              <Link
                key={`${item.href}-${i}`}
                href={item.href}
                onClick={() => setNavOpen(false)}
                className="font-heading block uppercase font-bold tracking-[0.12em] text-white transition-all duration-200 hover:pl-2 hover:text-yellow-300"
                style={{
                  fontSize: "clamp(0.75rem, 2.2vh, 1.05rem)",
                  padding: "10px 0",
                  borderBottom:
                    i < links.length - 1
                      ? "1px solid rgba(255,255,255,0.07)"
                      : "none",
                }}
              >
                {item.label}
              </Link>
            ))}
          </nav>

          <div className="w-full h-[1px] bg-white/20 my-4" />

          <SocialLinksRow
            social={social}
            label={t("common.followUs")}
            className="mb-4 items-start"
          />

          <p className="text-white/50 uppercase text-xs leading-6">
            {t("common.slogan")}
          </p>
        </div>
      </div>
    </>
  );
}
