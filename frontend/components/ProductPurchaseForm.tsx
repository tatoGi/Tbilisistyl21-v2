'use client';

import { useState } from 'react';
import { useLocale, useTranslations } from 'next-intl';
import { createProductOrder } from '@/lib/orders';

interface Props {
  productId: string;
  sizes: { size: string; available: boolean }[];
}

export function ProductPurchaseForm({ productId, sizes }: Props) {
  const t = useTranslations('productForm');
  const locale = useLocale();
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function handleSubmit(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault();
    setLoading(true);
    setError(null);

    const form = new FormData(e.currentTarget);

    try {
      const result = await createProductOrder(
        {
          productId,
          size: form.get('size') as string,
          name: form.get('name') as string,
          email: form.get('email') as string,
          phone: form.get('phone') as string,
        },
        locale,
      );
      window.location.href = result.redirect_url;
    } catch (err: unknown) {
      const message =
        err instanceof Error ? err.message : 'Something went wrong';
      setError(message);
      setLoading(false);
    }
  }

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      {error && (
        <div className="bg-red-900/50 border border-red-700 text-red-300 px-4 py-3 rounded">
          {error}
        </div>
      )}

      {sizes.length > 0 && (
        <div>
          <label className="block text-sm text-gray-400 mb-1">{t('size')}</label>
          <select
            name="size"
            required
            className="w-full bg-black border border-border rounded px-3 py-2 text-white focus:border-primary outline-none"
          >
            {sizes.map((s) => (
              <option key={s.size} value={s.size} disabled={!s.available}>
                {s.size} {!s.available ? '(sold out)' : ''}
              </option>
            ))}
          </select>
        </div>
      )}

      <div>
        <label className="block text-sm text-gray-400 mb-1">{t('name')}</label>
        <input
          name="name"
          required
          className="w-full bg-black border border-border rounded px-3 py-2 text-white focus:border-primary outline-none"
        />
      </div>

      <div>
        <label className="block text-sm text-gray-400 mb-1">{t('email')}</label>
        <input
          name="email"
          type="email"
          required
          className="w-full bg-black border border-border rounded px-3 py-2 text-white focus:border-primary outline-none"
        />
      </div>

      <div>
        <label className="block text-sm text-gray-400 mb-1">{t('phone')}</label>
        <input
          name="phone"
          required
          className="w-full bg-black border border-border rounded px-3 py-2 text-white focus:border-primary outline-none"
        />
      </div>

      <button
        type="submit"
        disabled={loading}
        className="w-full bg-primary hover:bg-primary-dark text-black font-bold py-3 rounded transition-colors disabled:opacity-50"
      >
        {loading ? '...' : t('submit')}
      </button>
    </form>
  );
}
