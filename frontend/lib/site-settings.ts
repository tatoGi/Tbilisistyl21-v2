import { api } from './api';
import type { SiteSettings } from './types';

export async function getSiteSettings(locale: string) {
  const res = await api<{ data: SiteSettings }>('/api/site-settings', { locale });
  return res.data;
}
