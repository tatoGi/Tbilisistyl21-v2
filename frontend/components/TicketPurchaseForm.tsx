'use client';

import { useState } from 'react';
import { useLocale, useTranslations } from 'next-intl';
import { createTicketOrder } from '@/lib/orders';

export function TicketPurchaseForm({ ticketId }: { ticketId: string }) {
  const t = useTranslations('ticketForm');
  const locale = useLocale();
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function handleSubmit(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault();
    setLoading(true);
    setError(null);

    const form = new FormData(e.currentTarget);

    try {
      const result = await createTicketOrder(
        {
          ticketId,
          name: form.get('name') as string,
          surname: form.get('surname') as string,
          email: form.get('email') as string,
          personalNumber: form.get('personalNumber') as string,
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

      <div className="grid grid-cols-2 gap-4">
        <div>
          <label className="block text-sm text-gray-400 mb-1">{t('name')}</label>
          <input
            name="name"
            required
            className="w-full bg-black border border-border rounded px-3 py-2 text-white focus:border-primary outline-none"
          />
        </div>
        <div>
          <label className="block text-sm text-gray-400 mb-1">
            {t('surname')}
          </label>
          <input
            name="surname"
            required
            className="w-full bg-black border border-border rounded px-3 py-2 text-white focus:border-primary outline-none"
          />
        </div>
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
        <label className="block text-sm text-gray-400 mb-1">
          {t('personalNumber')}
        </label>
        <input
          name="personalNumber"
          required
          pattern="\d{11}"
          maxLength={11}
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
