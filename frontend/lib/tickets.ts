import { api } from './api';
import type { Ticket } from './types';

export async function listTickets(locale: string) {
  const res = await api<{ data: Ticket[] }>('/api/tickets', { locale });
  return res.data;
}

export async function getTicket(id: string, locale: string) {
  const res = await api<{ data: Ticket }>(`/api/tickets/${id}`, { locale });
  return res.data;
}
