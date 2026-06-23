import { getLocale } from 'next-intl/server';
import { listTickets } from '@/lib/tickets';
import { t, formatPrice } from '@/lib/utils';
import { Link } from '@/i18n/navigation';
import type { Ticket } from '@/lib/types';

export default async function TicketsPage() {
  const locale = await getLocale();
  let tickets: Ticket[] = [];
  try {
    tickets = await listTickets(locale);
  } catch {
    // API unavailable
  }

  return (
    <div className="max-w-7xl mx-auto px-4 py-12">
      <h1 className="text-4xl font-bold mb-8">
        {locale === 'ka' ? 'ბილეთები' : 'Tickets'}
      </h1>

      {tickets.length === 0 ? (
        <p className="text-gray-400">
          {locale === 'ka' ? 'ბილეთები არ მოიძებნა' : 'No tickets available'}
        </p>
      ) : (
        <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
          {tickets.map((ticket) => (
            <Link
              key={ticket.id}
              href={`/tickets/${ticket.id}`}
              className="bg-surface-light border border-border rounded-lg p-6 hover:border-primary transition-colors"
            >
              <h2 className="text-xl font-semibold mb-2">
                {t(ticket.title, locale)}
              </h2>
              {ticket.description && (
                <p className="text-gray-400 text-sm mb-4 line-clamp-2">
                  {t(ticket.description, locale)}
                </p>
              )}
              <div className="flex items-center justify-between mt-4">
                <span className="text-primary text-lg font-bold">
                  {formatPrice(ticket.price_gel)} GEL
                </span>
                <span className="text-gray-500 text-sm">
                  {ticket.location} &middot; {ticket.event_date}
                </span>
              </div>
              {ticket.quantity <= 0 && (
                <span className="inline-block mt-2 px-3 py-1 bg-red-900 text-red-300 text-xs rounded">
                  {locale === 'ka' ? 'გაყიდულია' : 'Sold Out'}
                </span>
              )}
            </Link>
          ))}
        </div>
      )}
    </div>
  );
}
