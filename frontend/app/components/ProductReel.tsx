"use client";

import Link from "next/link";
import { useTranslations } from "next-intl";
import type { Product } from "@/lib/products";

function inStock(product: Product) {
  return product.sizes.reduce((sum, s) => sum + Math.max(0, s.quantity), 0);
}

function ProductCard({ product }: { product: Product }) {
  const soldOut = product.status === "sold_out" || inStock(product) <= 0;

  return (
    <Link
      href="/dashboard/shop"
      className="group relative mx-3 flex w-[210px] shrink-0 flex-col overflow-hidden rounded-2xl border border-white/5 bg-white/[0.01] transition-all duration-500 hover:-translate-y-2 hover:border-yellow-300/30 hover:bg-white/[0.03] hover:shadow-[0_15px_30px_rgba(0,0,0,0.5)] sm:w-[250px]"
    >
      <div className="relative aspect-square w-full overflow-hidden bg-white/[0.02]">
        {product.imageUrl ? (
          <>
            {/* eslint-disable-next-line @next/next/no-img-element */}
            <img
              src={product.imageUrl}
              alt={product.title}
              loading="lazy"
              className="h-full w-full object-cover transition-all duration-700 ease-out group-hover:scale-108"
            />
            {/* Subtle vignette gradient overlay */}
            <div className="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-transparent opacity-85 transition-opacity duration-500 group-hover:opacity-40" />
          </>
        ) : (
          <div className="flex h-full w-full items-center justify-center text-xs font-bold uppercase tracking-widest text-white/20">
            Tbilisi Style 21
          </div>
        )}
        {product.isVip ? (
          <span className="absolute left-3 top-3 z-10 rounded-md bg-gradient-to-r from-yellow-300 to-amber-300 px-2.5 py-0.5 text-[9px] font-extrabold uppercase tracking-wider text-black shadow-md shadow-yellow-300/10">
            VIP
          </span>
        ) : null}
        {soldOut ? (
          <span className="absolute right-3 top-3 z-10 rounded-md border border-white/10 bg-black/75 px-2.5 py-0.5 text-[9px] font-extrabold uppercase tracking-wider text-white/90 backdrop-blur-md">
            Sold out
          </span>
        ) : null}
        <span className="absolute bottom-3 right-3 z-10 rounded-full bg-gradient-to-r from-yellow-300 to-yellow-400 px-3.5 py-1 text-sm font-black text-black shadow-md shadow-black/30 transition-transform duration-500 group-hover:scale-105">
          {product.priceGel} ₾
        </span>
      </div>
      <div className="flex flex-col gap-1.5 p-4">
        {product.category ? (
          <p className="text-[9px] font-bold uppercase tracking-[0.25em] text-yellow-300/80">
            {product.category}
          </p>
        ) : null}
        <h3 className="truncate font-heading text-sm font-extrabold uppercase leading-tight text-white transition-colors duration-300 group-hover:text-yellow-300">
          {product.title}
        </h3>
      </div>
    </Link>
  );
}

/**
 * Auto-rotating, infinitely-looping product reel. We render the products twice
 * back-to-back and translate the track by -50%, so the loop is seamless. Hover
 * pauses it (handled in CSS), and clicking any card goes to the shop.
 */
export default function ProductReel({ products }: { products: Product[] }) {
  const t = useTranslations();

  if (!products.length) return null;

  // Duplicate so the marquee can loop without a visible seam.
  const loop = [...products, ...products];

  return (
    <section className="relative z-10 w-full overflow-hidden border-t border-white/5 bg-black py-20 md:py-28">
      {/* Ambient background glow */}
      <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_center,rgba(253,224,71,0.035)_0%,transparent_70%)]" />

      <div className="relative z-10 mx-auto mb-10 flex w-full max-w-6xl items-baseline justify-between gap-6 px-6">
        <div className="flex flex-col gap-1">
          <p className="text-[10px] font-bold uppercase tracking-[0.25em] text-yellow-300">
            Festival Merchandise
          </p>
          <h2 className="font-heading text-2xl font-extrabold uppercase tracking-wide text-white md:text-3xl">
            {t("nav.shop")}
          </h2>
        </div>
        <Link
          href="/dashboard/shop"
          className="font-heading group inline-flex items-center gap-2 text-xs font-bold uppercase tracking-[0.2em] text-white/50 transition duration-300 hover:text-yellow-300"
        >
          <span>{t("nav.shop")}</span>
          <span aria-hidden className="transition-transform duration-300 group-hover:translate-x-1.5">
            →
          </span>
        </Link>
      </div>

      <div className="ts-marquee relative z-10 w-full overflow-hidden">
        {/* edge fades */}
        <div className="pointer-events-none absolute inset-y-0 left-0 z-20 w-24 bg-gradient-to-r from-black via-black/80 to-transparent" />
        <div className="pointer-events-none absolute inset-y-0 right-0 z-20 w-24 bg-gradient-to-l from-black via-black/80 to-transparent" />

        <div className="ts-marquee-track py-4">
          {loop.map((product, i) => (
            <ProductCard key={`${product.id}-${i}`} product={product} />
          ))}
        </div>
      </div>
    </section>
  );
}
