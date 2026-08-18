"use client";

import Link from "next/link";
import { useTranslations } from "next-intl";

type Props = {
  basePath: string; // e.g. "/news"
  currentPage: number;
  totalPages: number;
};

/**
 * Query-param pagination (`?page=N`) for server-rendered list pages. Renders
 * nothing when there is a single page. SEO-friendly (plain links).
 */
export default function Pagination({ basePath, currentPage, totalPages }: Props) {
  const t = useTranslations();
  if (totalPages <= 1) return null;

  const href = (p: number) => (p <= 1 ? basePath : `${basePath}?page=${p}`);
  const pages = Array.from({ length: totalPages }, (_, i) => i + 1);

  const base =
    "inline-flex h-10 min-w-10 items-center justify-center rounded-full px-3 text-sm font-bold transition duration-200";
  const inactive = `${base} border border-white/10 text-[color:var(--ts-body)] hover:border-[#e8b84b]/40 hover:text-[#e8b84b]`;
  const active = `${base} bg-[#e8b84b] text-[#1a1206]`;
  const disabled = `${base} border border-white/5 text-white/20 pointer-events-none`;

  return (
    <nav
      aria-label={t("a11y.pagination")}
      className="mt-14 flex flex-wrap items-center justify-center gap-2"
    >
      {currentPage > 1 ? (
        <Link href={href(currentPage - 1)} className={inactive} aria-label={t("a11y.previous")}>
          ←
        </Link>
      ) : (
        <span className={disabled} aria-hidden>
          ←
        </span>
      )}

      {pages.map((p) => (
        <Link
          key={p}
          href={href(p)}
          aria-current={p === currentPage ? "page" : undefined}
          className={p === currentPage ? active : inactive}
        >
          {p}
        </Link>
      ))}

      {currentPage < totalPages ? (
        <Link href={href(currentPage + 1)} className={inactive} aria-label={t("a11y.next")}>
          →
        </Link>
      ) : (
        <span className={disabled} aria-hidden>
          →
        </span>
      )}
    </nav>
  );
}
