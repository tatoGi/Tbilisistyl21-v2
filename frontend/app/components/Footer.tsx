"use client";

import Link from "next/link";
import { useTranslations } from "next-intl";

import type { NavLink, SocialLinks, SiteContact } from "@/lib/nav";
import SocialLinksRow from "./SocialLinks";
import PaymentCardBadges from "./PaymentCardBadges";

export default function Footer({
  social,
  contact,
  footerPages,
}: {
  social: SocialLinks;
  contact: SiteContact;
  footerPages: NavLink[];
}) {
  const t = useTranslations();

  return (
    <footer className="relative z-10 border-t border-white/10 bg-black/95 px-6 py-12 text-white backdrop-blur-md">
      <div className="mx-auto grid max-w-6xl gap-10 md:grid-cols-3 md:items-start">
        {/* Contact */}
        <div className="flex flex-col items-center gap-3 text-center md:items-start md:text-left">
          <p className="mb-1 text-[11px] font-bold uppercase tracking-[0.28em] text-[#e8b84b]/80">
            {t("contactUs.title")}
          </p>
          <a
            href={`mailto:${contact.email}`}
            className="text-sm font-medium tracking-wide text-white/80 transition-colors hover:text-[#e8b84b]"
          >
            {t("contactUs.email")}: {contact.email}
          </a>
          <a
            href={`tel:${contact.phoneHref}`}
            className="text-sm font-medium tracking-wide text-white/80 transition-colors hover:text-[#e8b84b]"
          >
            {t("contactUs.phone")}: {contact.phone}
          </a>

          <SocialLinksRow
            social={social}
            label={t("common.followUs")}
            className="mt-4 items-center md:items-start"
          />
        </div>

        {/* CMS footer links */}
        {footerPages.length > 0 ? (
          <nav
            aria-label="Footer"
            className="flex flex-col items-center gap-2.5 text-center md:items-start md:text-left"
          >
            <p className="mb-1 text-[11px] font-bold uppercase tracking-[0.28em] text-[#e8b84b]/80">
              Tbilisi Style 21
            </p>
            {footerPages.map((link) => (
              <Link
                key={link.href}
                href={link.href}
                className="text-xs font-bold uppercase tracking-[0.18em] text-white/65 transition-colors hover:text-[#e8b84b]"
              >
                {link.label}
              </Link>
            ))}
          </nav>
        ) : (
          <div />
        )}

        {/* Quick actions */}
        <div className="flex flex-col items-center gap-3 md:items-end">
          <Link
            href="/dashboard/tickets"
            className="rounded-full bg-[#e8b84b] px-6 py-2.5 text-xs font-extrabold uppercase tracking-wide text-black shadow-[0_0_24px_rgba(253,224,71,0.28)] transition-all duration-200 hover:bg-white"
          >
            {t("common.buyTickets")}
          </Link>
          <Link
            href="/dashboard/shop"
            className="rounded-full border border-white/25 px-6 py-2.5 text-xs font-extrabold uppercase tracking-wide text-white transition-all duration-200 hover:border-[#e8b84b] hover:text-[#e8b84b]"
          >
            {t("nav.shop")}
          </Link>
        </div>
      </div>

      <div className="mx-auto mt-10 flex max-w-6xl flex-col items-center gap-5 border-t border-white/10 pt-6">
        <PaymentCardBadges />
        <p className="text-center text-[11px] uppercase tracking-[0.2em] text-white/35">
          © {new Date().getFullYear()} Tbilisi Style 21
        </p>
      </div>
    </footer>
  );
}
