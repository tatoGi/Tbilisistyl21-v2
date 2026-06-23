import { getLocale } from 'next-intl/server';
import { getTicket } from '@/lib/tickets';
import { t, formatPrice } from '@/lib/utils';
import { notFound } from 'next/navigation';
import { TicketPurchaseForm } from '@/components/TicketPurchaseForm';

export default async function TicketDetailPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;
  const locale = await getLocale();

  let ticket;
  try {
    ticket = await getTicket(id, locale);
  } catch {
    notFound();
  }

  const isSoldOut = ticket.quantity <= 0;

  return (
    <div className="max-w-3xl mx-auto px-4 py-12">
      <h1 className="text-4xl font-bold mb-4">{t(ticket.title, locale)}</h1>

      <div className="flex items-center gap-4 text-gray-400 mb-6">
        <span>{ticket.location}</span>
        <span>&middot;</span>
        <span>{ticket.event_date}</span>
      </div>

      {ticket.description && (
        <div className="text-gray-300 mb-8 leading-relaxed">
          {t(ticket.description, locale)}
        </div>
      )}

      <div className="bg-surface-light border border-border rounded-lg p-6">
        <div className="flex items-center justify-between mb-6">
          <span className="text-3xl font-bold text-primary">
            {formatPrice(ticket.price_gel)} GEL
          </span>
          {isSoldOut && (
            <span className="px-4 py-2 bg-red-900 text-red-300 rounded-lg font-semibold">
              {locale === 'ka' ? 'გაყიდულია' : 'Sold Out'}
            </span>
          )}
        </div>

        {!isSoldOut && <TicketPurchaseForm ticketId={ticket.id} />}
      </div>
    </div>
  );
}
