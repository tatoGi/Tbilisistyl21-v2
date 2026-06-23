import { getLocale } from 'next-intl/server';
import { getProduct } from '@/lib/products';
import { t, formatPrice, mediaUrl } from '@/lib/utils';
import { notFound } from 'next/navigation';
import { ProductPurchaseForm } from '@/components/ProductPurchaseForm';

export default async function ProductDetailPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;
  const locale = await getLocale();

  let product;
  try {
    product = await getProduct(id, locale);
  } catch {
    notFound();
  }

  return (
    <div className="max-w-4xl mx-auto px-4 py-12">
      <div className="grid md:grid-cols-2 gap-8">
        {product.image && (
          <img
            src={mediaUrl(product.image.filename)}
            alt={t(product.title, locale)}
            className="w-full rounded-lg"
          />
        )}

        <div>
          <h1 className="text-3xl font-bold mb-4">{t(product.title, locale)}</h1>

          {product.description && (
            <p className="text-gray-300 mb-6 leading-relaxed">
              {t(product.description, locale)}
            </p>
          )}

          <p className="text-3xl font-bold text-primary mb-6">
            {formatPrice(product.price_gel)} GEL
          </p>

          {product.is_vip && (
            <span className="inline-block mb-6 px-3 py-1 bg-primary/20 text-primary text-sm rounded">
              VIP
            </span>
          )}

          <ProductPurchaseForm
            productId={product.id}
            sizes={product.sizes.map((s) => ({
              size: s.size,
              available: s.quantity > 0,
            }))}
          />
        </div>
      </div>
    </div>
  );
}
