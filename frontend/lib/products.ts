import { api } from './api';
import type { Product } from './types';

export async function listProducts(locale: string) {
  const res = await api<{ data: Product[] }>('/api/products', { locale });
  return res.data;
}

export async function getProduct(id: string, locale: string) {
  const res = await api<{ data: Product }>(`/api/products/${id}`, { locale });
  return res.data;
}
