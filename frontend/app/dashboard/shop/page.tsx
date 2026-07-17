import { notFound } from "next/navigation";
import { listProducts } from "@/lib/products";
import { getCmsPageTitle } from "@/lib/cms-page-title";
import Pagination from "@/app/components/Pagination";
import ProductsClient from "./ProductsClient";

export const dynamic = "force-dynamic";

const PER_PAGE = 12;

type PageProps = { searchParams: Promise<{ page?: string; product?: string }> };

export default async function ShopPage({ searchParams }: PageProps) {
  const [allProducts, title] = await Promise.all([
    listProducts({ publicOnly: true }),
    getCmsPageTitle("shop"),
  ]);

  if (!title) notFound();

  const { page, product } = await searchParams;
  const totalPages = Math.max(1, Math.ceil(allProducts.length / PER_PAGE));
  const current = Math.min(Math.max(1, Number(page) || 1), totalPages);
  const products = allProducts.slice((current - 1) * PER_PAGE, current * PER_PAGE);

  // Deep link from the home-page product reel: open this product's buy modal.
  const initialProduct = product
    ? allProducts.find((p) => p.id === product) ?? null
    : null;

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

      <ProductsClient products={products} initialProduct={initialProduct} />
      <Pagination basePath="/dashboard/shop" currentPage={current} totalPages={totalPages} />
    </main>
  );
}
