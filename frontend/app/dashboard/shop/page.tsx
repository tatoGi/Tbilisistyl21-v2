import { listProducts } from "@/lib/products";
import ProductsClient from "./ProductsClient";

export const dynamic = "force-dynamic";

export default async function ShopPage() {
  const products = await listProducts({ publicOnly: true });

  return (
    <main className="mx-auto min-h-screen w-full max-w-6xl px-5 pb-16 pt-28 text-white md:px-10">
      <div className="grid gap-3">
        <p className="text-xs font-bold uppercase tracking-[0.2em] text-yellow-300">
          Tbilisi Style 21
        </p>
        <h1 className="text-4xl font-black uppercase md:text-6xl">Shop</h1>
      </div>

      <ProductsClient products={products} />
    </main>
  );
}
