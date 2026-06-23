import { api } from './api';
import type { Page } from './types';

export async function getPage(slug: string, locale: string) {
  const res = await api<{ data: Page }>(`/api/pages/${slug}`, { locale });
  return res.data;
}
