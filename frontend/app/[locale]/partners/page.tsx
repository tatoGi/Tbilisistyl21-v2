import { getLocale } from 'next-intl/server';
import { listPartners } from '@/lib/partners';
import { t, mediaUrl } from '@/lib/utils';
import type { Partner } from '@/lib/types';

export default async function PartnersPage() {
  const locale = await getLocale();
  let partners: Partner[] = [];
  try {
    partners = await listPartners(locale);
  } catch {
    // API unavailable
  }

  return (
    <div className="max-w-7xl mx-auto px-4 py-12">
      <h1 className="text-4xl font-bold mb-8">
        {locale === 'ka' ? 'პარტნიორები' : 'Partners'}
      </h1>

      {partners.length === 0 ? (
        <p className="text-gray-400">
          {locale === 'ka' ? 'პარტნიორები არ მოიძებნა' : 'No partners yet'}
        </p>
      ) : (
        <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
          {partners.map((partner) => (
            <a
              key={partner.id}
              href={partner.url}
              target="_blank"
              rel="noopener noreferrer"
              className="bg-surface-light border border-border rounded-lg p-6 flex flex-col items-center text-center hover:border-primary transition-colors"
            >
              {partner.logo && (
                <img
                  src={mediaUrl(partner.logo.filename)}
                  alt={partner.name}
                  className="h-16 object-contain mb-4"
                />
              )}
              <h2 className="text-lg font-semibold mb-2">{partner.name}</h2>
              {partner.description && (
                <p className="text-gray-400 text-sm">
                  {t(partner.description, locale)}
                </p>
              )}
            </a>
          ))}
        </div>
      )}
    </div>
  );
}
