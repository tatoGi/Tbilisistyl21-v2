"use client";

import Link from "next/link";
import { useTranslations } from "next-intl";
import { usePathname } from "next/navigation";

/**
 * Persistent floating "Buy Tickets" button. Stays visible on every festival
 * page (except the tickets page itself) so the primary conversion action is
 * always one tap away — the single most important goal for selling tickets.
 */
export default function TicketCta() {
  const t = useTranslations();
  const pathname = usePathname();

  // Don't show it on the tickets page — the user is already there.
  if (pathname?.startsWith("/dashboard/tickets")) return null;

  return (
    <Link
      href="/dashboard/tickets"
      aria-label={t("common.buyTickets")}
      className="ts-ticket-pulse fixed bottom-5 right-5 z-40 flex items-center gap-2 rounded-full bg-yellow-300 px-5 py-3.5 text-sm font-extrabold uppercase tracking-wide text-black transition-transform duration-200 hover:scale-105 hover:bg-white sm:bottom-7 sm:right-7 sm:px-7 sm:py-4 sm:text-base"
    >
      <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 24 24"
        fill="currentColor"
        className="h-5 w-5"
        aria-hidden="true"
      >
        <path d="M2.25 9.75A2.25 2.25 0 0 1 4.5 7.5h15a2.25 2.25 0 0 1 2.25 2.25v.69a.75.75 0 0 1-.53.72 1.5 1.5 0 0 0 0 2.88.75.75 0 0 1 .53.72v.69a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25v-.69a.75.75 0 0 1 .53-.72 1.5 1.5 0 0 0 0-2.88.75.75 0 0 1-.53-.72v-.69Zm13.5-.75a.75.75 0 0 0-1.5 0v.75a.75.75 0 0 0 1.5 0V9Zm0 4.125a.75.75 0 0 0-1.5 0v.75a.75.75 0 0 0 1.5 0v-.75Z" />
      </svg>
      <span>{t("common.buyTickets")}</span>
    </Link>
  );
}
