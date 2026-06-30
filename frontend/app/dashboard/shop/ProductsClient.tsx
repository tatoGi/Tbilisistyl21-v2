"use client";

import { useState } from "react";
import type { Product } from "@/lib/products";
import BuyProductModal from "./BuyProductModal";

function inStock(product: Product) {
  return product.sizes.reduce((sum, s) => sum + Math.max(0, s.quantity), 0);
}

export default function ProductsClient({ products }: { products: Product[] }) {
  const [selected, setSelected] = useState<Product | null>(null);

  return (
    <>
      <div className="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        {products.length ? (
          products.map((product) => {
            const stock = inStock(product);
            const soldOut = product.status === "sold_out" || stock <= 0;

            return (
              <article
                key={product.id}
                className="group flex flex-col overflow-hidden border border-white/10 bg-white/[0.03]"
              >
                <div className="relative aspect-square w-full overflow-hidden bg-white/[0.04]">
                  {product.imageUrl ? (
                    // eslint-disable-next-line @next/next/no-img-element
                    <img
                      src={product.imageUrl}
                      alt={product.title}
                      className="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                    />
                  ) : (
                    <div className="flex h-full w-full items-center justify-center text-xs font-bold uppercase tracking-widest text-white/30">
                      No image
                    </div>
                  )}
                  {product.isVip ? (
                    <span className="absolute left-3 top-3 bg-yellow-300 px-2.5 py-1 text-[11px] font-black uppercase tracking-wider text-black shadow">
                      VIP
                    </span>
                  ) : null}
                  {soldOut ? (
                    <span className="absolute right-3 top-3 bg-black/80 px-2.5 py-1 text-[11px] font-black uppercase tracking-wider text-white">
                      Sold out
                    </span>
                  ) : null}
                </div>

                <div className="flex flex-1 flex-col gap-3 p-5">
                  <div className="flex items-start justify-between gap-3">
                    <div>
                      {product.category ? (
                        <p className="text-[10px] font-bold uppercase tracking-[0.2em] text-white/40">
                          {product.category}
                        </p>
                      ) : null}
                      <h2 className="text-xl font-black uppercase leading-tight">
                        {product.title}
                      </h2>
                    </div>
                    <span className="shrink-0 bg-yellow-300 px-3 py-1.5 text-sm font-black text-black">
                      {product.priceGel} ₾
                    </span>
                  </div>

                  {product.description ? (
                    <p className="line-clamp-3 whitespace-pre-line text-sm leading-6 text-white/65">
                      {product.description}
                    </p>
                  ) : null}

                  {product.sizes.length ? (
                    <div className="flex flex-wrap gap-1.5">
                      {product.sizes.map((s) => (
                        <span
                          key={s.size}
                          className={`border px-2 py-0.5 text-[11px] font-bold uppercase ${
                            s.quantity > 0
                              ? "border-white/20 text-white/70"
                              : "border-white/10 text-white/25 line-through"
                          }`}
                        >
                          {s.size}
                        </span>
                      ))}
                    </div>
                  ) : null}

                  <div className="mt-auto pt-2">
                    {soldOut ? (
                      <button
                        disabled
                        className="w-full cursor-not-allowed bg-white/10 py-3 text-xs font-black uppercase tracking-wider text-white/40"
                      >
                        Sold out
                      </button>
                    ) : (
                      <button
                        onClick={() => setSelected(product)}
                        className="w-full bg-yellow-300 py-3 text-xs font-black uppercase tracking-wider text-black transition hover:bg-white"
                      >
                        Buy now
                      </button>
                    )}
                  </div>
                </div>
              </article>
            );
          })
        ) : (
          <p className="border border-white/10 p-5 text-white/60">
            Products are not available yet.
          </p>
        )}
      </div>

      {selected ? (
        <BuyProductModal
          isOpen={!!selected}
          onClose={() => setSelected(null)}
          product={selected}
        />
      ) : null}
    </>
  );
}
