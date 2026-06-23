import { getLocale } from 'next-intl/server';
import { listProducts } from '@/lib/products';
import { t, formatPrice, mediaUrl } from '@/lib/utils';
import { Link } from '@/i18n/navigation';
import type { Product } from '@/lib/types';

export default async function ProductsPage() {
  const locale = await getLocale();
  let products: Product[] = [];
  try {
    products = await listProducts(locale);
  } catch {
    // API unavailable
  }

  return (
    <div className="max-w-7xl mx-auto px-4 py-12">
      <h1 className="text-4xl font-bold mb-8">
        {locale === 'ka' ? 'პროდუქცია' : 'Products'}
      </h1>

      {products.length === 0 ? (
        <p className="text-gray-400">
          {locale === 'ka' ? 'პროდუქცია არ მოიძებნა' : 'No products available'}
        </p>
      ) : (
        <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
          {products.map((product) => (
            <Link
              key={product.id}
              href={`/products/${product.id}`}
              className="bg-surface-light border border-border rounded-lg overflow-hidden hover:border-primary transition-colors"
            >
              {product.image && (
                <img
                  src={mediaUrl(product.image.filename)}
                  alt={t(product.title, locale)}
                  className="w-full h-48 object-cover"
                />
              )}
              <div className="p-6">
                <h2 className="text-xl font-semibold mb-2">
                  {t(product.title, locale)}
                </h2>
                <p className="text-primary text-lg font-bold">
                  {formatPrice(product.price_gel)} GEL
                </p>
                {product.is_vip && (
                  <span className="inline-block mt-2 px-2 py-1 bg-primary/20 text-primary text-xs rounded">
                    VIP
                  </span>
                )}
              </div>
            </Link>
          ))}
        </div>
      )}
    </div>
  );
}
