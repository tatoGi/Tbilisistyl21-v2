import { api } from './api';
import type { Partner } from './types';

export async function listPartners(locale: string) {
  const res = await api<{ data: Partner[] }>('/api/partners', { locale });
  return res.data;
}
