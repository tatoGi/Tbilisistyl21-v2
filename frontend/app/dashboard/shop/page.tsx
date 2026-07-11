import { notFound } from "next/navigation";
import { listProducts } from "@/lib/products";
import { getCmsPageTitle } from "@/lib/cms-page-title";
import ProductsClient from "./ProductsClient";

export const dynamic = "force-dynamic";

export default async function ShopPage() {
  const [products, title] = await Promise.all([
    listProducts({ publicOnly: true }),
    getCmsPageTitle("shop"),
  ]);

  if (!title) notFound();

  return (
    <main className="mx-auto min-h-screen w-full max-w-6xl px-5 pb-16 pt-28 text-[color:var(--ts-body)] md:px-10">
      <div className="mb-2 flex items-center gap-3">
        <span className="h-[2px] w-[34px] bg-[#e8b84b]" />
        <p className="font-unbounded text-[13px] font-bold uppercase tracking-[0.22em] text-[#e8b84b]">
          Tbilisi Style 21
        </p>
      </div>
      <h1 className="font-unbounded text-[clamp(2.2rem,5vw,4rem)] font-extrabold tracking-[-0.01em] text-[color:var(--ts-head)]">
        {title}
      </h1>

      <ProductsClient products={products} />
    </main>
  );
}
