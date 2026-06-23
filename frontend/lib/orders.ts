import { api } from './api';

interface TicketOrderPayload {
  ticketId: string;
  name: string;
  surname: string;
  email: string;
  personalNumber: string;
}

interface ProductOrderPayload {
  productId: string;
  size: string;
  name: string;
  email: string;
  phone: string;
}

interface OrderResponse {
  redirect_url: string;
}

export async function createTicketOrder(data: TicketOrderPayload, locale: string) {
  return api<OrderResponse>('/api/orders/tickets', {
    method: 'POST',
    locale,
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data),
  });
}

export async function createProductOrder(data: ProductOrderPayload, locale: string) {
  return api<OrderResponse>('/api/orders/products', {
    method: 'POST',
    locale,
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data),
  });
}

export async function checkPersonalNumber(personalNumber: string, locale: string) {
  return api<{ data: { count: number } }>('/api/check-personal-number', {
    method: 'POST',
    locale,
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ personalNumber }),
  });
}
