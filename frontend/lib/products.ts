import { api } from "./api";
import { t, mediaUrl } from "./utils";
import { getCurrentLocale } from "./locale";
import type { Product as ApiProduct, Media } from "./types";

export type ProductStatus = "draft" | "active" | "sold_out";

export type ProductSize = {
  size: string;
  quantity: number;
};

export type Product = {
  id: string;
  title: string;
  description: string;
  priceGel: number;
  imageUrl: string;
  category: string;
  isVip: boolean;
  sizes: ProductSize[];
  status: ProductStatus;
  createdAt: string;
  updatedAt: string;
};

function imageUrlOf(image: Media | null): string {
  if (!image) return "";
  if (image.filename) return mediaUrl(image.filename);
  return "";
}

function mapProduct(p: ApiProduct, locale: string): Product {
  return {
    id: String(p.id),
    title: t(p.title, locale),
    description: t(p.description, locale),
    priceGel: Number(p.price_gel ?? 0),
    imageUrl: imageUrlOf(p.image ?? null),
    category: p.category ?? "",
    isVip: Boolean(p.is_vip),
    sizes: (p.sizes ?? []).map((s) => ({
      size: s.size ?? "",
      quantity: Number(s.quantity ?? 0),
    })),
    status: (p.status as ProductStatus) ?? "draft",
    createdAt: p.created_at ?? "",
    updatedAt: p.updated_at ?? "",
  };
}

export function totalStock(sizes: ProductSize[]): number {
  return sizes.reduce((sum, s) => sum + Math.max(0, s.quantity), 0);
}

export async function listProducts({
  publicOnly = false,
  locale,
}: { publicOnly?: boolean; locale?: string } = {}): Promise<Product[]> {
  const loc = locale ?? (await getCurrentLocale());
  try {
    const res = await api<{ data: ApiProduct[] }>("/api/products", { locale: loc });
    const data = Array.isArray(res?.data) ? res.data : [];
    let products = data.map((p) => mapProduct(p, loc));
    if (publicOnly) {
      products = products.filter(
        (p) => p.status === "active" || p.status === "sold_out",
      );
    }
    return products;
  } catch {
    return [];
  }
}

export async function getProduct(
  id: string,
  locale?: string,
): Promise<Product | null> {
  if (!id) return null;
  const loc = locale ?? (await getCurrentLocale());
  try {
    const res = await api<{ data: ApiProduct }>(`/api/products/${id}`, {
      locale: loc,
    });
    return mapProduct(res.data, loc);
  } catch {
    return null;
  }
}
