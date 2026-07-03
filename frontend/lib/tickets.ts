import { api } from "./api";
import { t } from "./utils";
import { getCurrentLocale } from "./locale";
import type { Ticket as ApiTicket } from "./types";

export type TicketStatus = "draft" | "active" | "sold_out";

export type Ticket = {
  id: string;
  title: string;
  description: string;
  imageUrl: string | null;
  priceGel: number;
  eventDate: string;
  location: string;
  quantity: number;
  saleUrl: string;
  status: TicketStatus;
  createdAt: string;
  updatedAt: string;
};

function imageUrlOf(image: unknown): string | null {
  if (typeof image !== "string" || !image) return null;
  if (image.startsWith("http://") || image.startsWith("https://")) return image;
  if (image.startsWith("/")) return image;
  return `/storage/${image}`;
}

function mapTicket(t0: ApiTicket, locale: string): Ticket {
  return {
    id: String(t0.id),
    title: t(t0.title, locale),
    description: t(t0.description, locale),
    imageUrl: imageUrlOf(t0.image),
    priceGel: Number(t0.price_gel ?? 0),
    eventDate: t0.event_date ?? "",
    location: t0.location ?? "",
    quantity: Number(t0.quantity ?? 0),
    saleUrl: t0.sale_url ?? "",
    status: (t0.status as TicketStatus) ?? "draft",
    createdAt: t0.created_at ?? "",
    updatedAt: t0.updated_at ?? "",
  };
}

export async function listTickets({
  publicOnly = false,
  locale,
}: { publicOnly?: boolean; locale?: string } = {}): Promise<Ticket[]> {
  const loc = locale ?? (await getCurrentLocale());
  try {
    const res = await api<{ data: ApiTicket[] }>("/api/tickets", { locale: loc });
    const data = Array.isArray(res?.data) ? res.data : [];
    let tickets = data.map((tk) => mapTicket(tk, loc));
    if (publicOnly) {
      tickets = tickets.filter(
        (tk) => tk.status === "active" || tk.status === "sold_out",
      );
    }
    return tickets;
  } catch {
    return [];
  }
}

export async function getTicket(
  id: string,
  locale?: string,
): Promise<Ticket | null> {
  if (!id) return null;
  const loc = locale ?? (await getCurrentLocale());
  try {
    const res = await api<{ data: ApiTicket }>(`/api/tickets/${id}`, {
      locale: loc,
    });
    return mapTicket(res.data, loc);
  } catch {
    return null;
  }
}
