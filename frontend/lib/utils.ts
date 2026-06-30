import type { Translatable } from './types';

export function t(field: Translatable | undefined, locale: string): string {
  if (!field) return '';
  return field[locale as keyof Translatable] || field.ka || field.en || '';
}

export function formatPrice(price: number): string {
  return new Intl.NumberFormat('ka-GE', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(price);
}

export function mediaUrl(filename: string): string {
  return `/storage/media/${encodeURIComponent(filename)}`;
}
