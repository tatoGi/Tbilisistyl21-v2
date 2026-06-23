import { getLocale } from 'next-intl/server';
import { listTickets } from '@/lib/tickets';
import { listPartners } from '@/lib/partners';
import { getSiteSettings } from '@/lib/site-settings';
import { t, formatPrice, mediaUrl } from '@/lib/utils';
import { Link } from '@/i18n/navigation';
import type { Ticket, Partner, SiteSettings, Translatable } from '@/lib/types';

export default async function HomePage() {
  const locale = await getLocale();

  let tickets: Ticket[] = [];
  let partners: Partner[] = [];
  let settings: SiteSettings = {};
  try {
    [tickets, partners, settings] = await Promise.all([
      listTickets(locale),
      listPartners(locale),
      getSiteSettings(locale),
    ]);
  } catch {
    // API unavailable
  }

  const heroTitle = settings?.heroTitle as Translatable | undefined;

  return (
    <div>
      {/* Hero */}
      <section className="py-20 text-center">
        <div className="max-w-4xl mx-auto px-4">
          <h1 className="text-5xl md:text-7xl font-bold mb-6">
            {heroTitle ? t(heroTitle, locale) : 'TbilisiStyle21'}
          </h1>
        </div>
      </section>

      {/* Tickets */}
      {tickets.length > 0 && (
        <section className="py-16 bg-surface">
          <div className="max-w-7xl mx-auto px-4">
            <h2 className="text-3xl font-bold mb-8 text-primary">
              {locale === 'ka' ? 'ბილეთები' : 'Tickets'}
            </h2>
            <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
              {tickets.slice(0, 6).map((ticket) => (
                <Link
                  key={ticket.id}
                  href={`/tickets/${ticket.id}`}
                  className="bg-surface-light border border-border rounded-lg p-6 hover:border-primary transition-colors"
                >
                  <h3 className="text-xl font-semibold mb-2">
                    {t(ticket.title, locale)}
                  </h3>
                  <p className="text-gray-400 text-sm mb-4">
                    {ticket.location} &middot; {ticket.event_date}
                  </p>
                  <p className="text-primary text-lg font-bold">
                    {formatPrice(ticket.price_gel)} GEL
                  </p>
                </Link>
              ))}
            </div>
          </div>
        </section>
      )}

      {/* Partners */}
      {partners.length > 0 && (
        <section className="py-16">
          <div className="max-w-7xl mx-auto px-4">
            <h2 className="text-3xl font-bold mb-8 text-primary">
              {locale === 'ka' ? 'პარტნიორები' : 'Partners'}
            </h2>
            <div className="flex flex-wrap items-center justify-center gap-8">
              {partners.map((partner) => (
                <a
                  key={partner.id}
                  href={partner.url}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="opacity-60 hover:opacity-100 transition-opacity"
                >
                  {partner.logo ? (
                    <img
                      src={mediaUrl(partner.logo.filename)}
                      alt={partner.name}
                      className="h-12 object-contain"
                    />
                  ) : (
                    <span className="text-gray-400">{partner.name}</span>
                  )}
                </a>
              ))}
            </div>
          </div>
        </section>
      )}
    </div>
  );
}
